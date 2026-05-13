<?php

namespace App\Services\Narration;

use App\Services\Narration\Contracts\NarrationProvider;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ElevenLabsNarrationProvider implements NarrationProvider
{
    private const MPEG_SCAN_MAX_BYTES = 524_288;

    public function synthesize(string $script, string $voiceId, string $modelId): NarrationResult
    {
        $apiKey = (string) config('poi_narration.elevenlabs.api_key');
        if ($apiKey === '') {
            throw new NarrationGenerationException('ElevenLabs API key is not configured.');
        }

        $baseUrl = rtrim((string) config('poi_narration.elevenlabs.base_url'), '/');
        $outputFormat = (string) config('poi_narration.elevenlabs.output_format');
        $timeout = (int) config('poi_narration.elevenlabs.timeout_seconds', 120);

        $url = $baseUrl.'/v1/text-to-speech/'.$voiceId.'?output_format='.rawurlencode($outputFormat);

        $payload = [
            'text' => $script,
            'model_id' => $modelId,
            'voice_settings' => config('poi_narration.elevenlabs.voice_settings', []),
        ];

        $jsonBody = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $tmp = tempnam(sys_get_temp_dir(), 'el11-');
        if ($tmp === false) {
            throw new NarrationGenerationException('Could not create a temp file for the ElevenLabs download.');
        }

        try {
            $response = Http::timeout($timeout)
                ->withOptions(['decode_content' => false])
                ->sink($tmp)
                ->withHeaders([
                    'xi-api-key' => $apiKey,
                    'Accept' => 'application/octet-stream',
                ])
                ->withBody($jsonBody, 'application/json')
                ->post($url);

            if (! $response->successful()) {
                $body = $this->readTempOrResponseBody($tmp, $response);
                $snippet = mb_substr(preg_replace('/xi-api-key[^\s]*/i', '[redacted]', $body) ?? $body, 0, 2000);

                throw new NarrationGenerationException('ElevenLabs error ('.$response->status().'): '.$snippet);
            }

            $audio = $this->readTempOrResponseBody($tmp, $response);
            $bytes = strlen($audio);

            $this->assertBinaryMp3Response($audio, $this->normalizeContentTypeHeader($response->header('Content-Type')));

            return new NarrationResult(
                audio: $audio,
                mimeType: 'audio/mpeg',
                durationSeconds: null,
                bytes: $bytes,
            );
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    private function readTempOrResponseBody(string $tmp, Response $response): string
    {
        if (is_file($tmp) && filesize($tmp) > 0) {
            $content = file_get_contents($tmp);

            return is_string($content) ? $content : '';
        }

        return $response->body();
    }

    /**
     * @throws NarrationGenerationException
     */
    private function assertBinaryMp3Response(string $audio, ?string $contentType): void
    {
        if ($audio === '') {
            throw new NarrationGenerationException('ElevenLabs returned an empty body.');
        }

        $ct = strtolower((string) $contentType);
        if ($ct !== '' && str_contains($ct, 'application/json')) {
            $snippet = mb_substr($audio, 0, 800);
            throw new NarrationGenerationException('ElevenLabs returned JSON instead of audio: '.$snippet);
        }

        $trim = ltrim($audio);
        if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
            $snippet = mb_substr($trim, 0, 800);
            throw new NarrationGenerationException('ElevenLabs response looks like JSON, not an MP3 file: '.$snippet);
        }

        if (str_starts_with($trim, 'RIFF')) {
            throw new NarrationGenerationException('ElevenLabs returned a WAV/RIFF file, but this app expects MP3. Check ELEVENLABS_OUTPUT_FORMAT (use an mp3_* value).');
        }

        if (strlen($audio) >= 64 && ! $this->payloadContainsMp3MpegFrame($audio)) {
            $snippet = mb_substr($audio, 0, 120);
            $hex = bin2hex(substr($audio, 0, 8));

            throw new NarrationGenerationException(
                'ElevenLabs returned data without a valid MPEG audio frame after metadata (file may be truncated or not MP3). First bytes (hex): '.$hex.'. Snippet: '.$snippet
            );
        }
    }

    /**
     * True if a plausible MPEG Layer III frame sync exists (after any leading ID3v2 tag).
     */
    private function payloadContainsMp3MpegFrame(string $audio): bool
    {
        $scan = substr($audio, 0, self::MPEG_SCAN_MAX_BYTES);
        $offset = 0;
        $len = strlen($scan);

        while ($offset + 10 <= $len && substr($scan, $offset, 3) === 'ID3') {
            $tagLen = $this->id3v2DeclaredLength(substr($scan, $offset));
            if ($tagLen < 10 || $offset + $tagLen > $len) {
                break;
            }
            $offset += $tagLen;
        }

        $haystack = substr($scan, $offset);

        return $this->findMpegFrameSyncOffset($haystack, strlen($haystack)) !== null;
    }

    private function id3v2DeclaredLength(string $tagStart): int
    {
        if (strlen($tagStart) < 10) {
            return 0;
        }

        for ($i = 0; $i < 4; $i++) {
            if ((ord($tagStart[6 + $i]) & 0x80) !== 0) {
                return 0;
            }
        }

        $size = ((ord($tagStart[6]) & 0x7F) << 21)
            | ((ord($tagStart[7]) & 0x7F) << 14)
            | ((ord($tagStart[8]) & 0x7F) << 7)
            | (ord($tagStart[9]) & 0x7F);

        return 10 + $size;
    }

    private function findMpegFrameSyncOffset(string $s, int $len): ?int
    {
        for ($i = 0; $i < $len - 1; $i++) {
            if (ord($s[$i]) === 0xFF && (ord($s[$i + 1]) & 0xE0) === 0xE0) {
                return $i;
            }
        }

        return null;
    }

    private function normalizeContentTypeHeader(array|string|null $header): ?string
    {
        if ($header === null) {
            return null;
        }

        return is_array($header) ? implode(';', $header) : $header;
    }
}
