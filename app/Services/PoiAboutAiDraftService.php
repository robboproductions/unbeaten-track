<?php

namespace App\Services;

use App\Models\Poi;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PoiAboutAiDraftService
{
    public function __construct(
        private readonly TownAboutHtmlSanitizer $sanitizer,
    ) {}

    /**
     * @throws RuntimeException
     */
    public function draftHtml(Poi $poi): string
    {
        $provider = $this->resolvedProvider();
        $prompt = $this->buildPrompt($poi);

        $raw = match ($provider) {
            'anthropic' => $this->callAnthropic($prompt, $this->draftPoiSystemInstructions()),
            'openai' => $this->callOpenAi($prompt, $this->draftPoiSystemInstructions()),
            default => throw new RuntimeException('No AI provider is configured.'),
        };

        $stripped = $this->stripCodeFences($raw);

        return $this->sanitizer->sanitize($stripped);
    }

    /**
     * Plain-text spoken narration for ElevenLabs (admin "Draft Script with Claude").
     *
     * @throws RuntimeException
     */
    public function draftNarrationScript(Poi $poi): string
    {
        $provider = $this->resolvedProvider();
        $prompt = $this->buildNarrationPrompt($poi);

        $raw = match ($provider) {
            'anthropic' => $this->callAnthropic($prompt, $this->draftNarrationSystemInstructions()),
            'openai' => $this->callOpenAi($prompt, $this->draftNarrationSystemInstructions()),
            default => throw new RuntimeException('No AI provider is configured.'),
        };

        $normalized = $this->normalizeNarrationScript($this->stripCodeFences($raw));
        if ($normalized === '') {
            throw new RuntimeException('Claude returned no usable narration text. Try again.');
        }

        return $normalized;
    }

    public function isConfigured(): bool
    {
        return $this->resolvedProvider() !== null;
    }

    private function resolvedProvider(): ?string
    {
        $mode = strtolower((string) config('town_ai.provider', 'auto'));
        $hasAnthropic = filled(config('town_ai.anthropic_api_key'));
        $hasOpenai = filled(config('town_ai.openai_api_key'));

        if ($mode === 'anthropic') {
            return $hasAnthropic ? 'anthropic' : null;
        }
        if ($mode === 'openai') {
            return $hasOpenai ? 'openai' : null;
        }

        if ($hasAnthropic) {
            return 'anthropic';
        }
        if ($hasOpenai) {
            return 'openai';
        }

        return null;
    }

    private function buildPrompt(Poi $poi): string
    {
        $poi->loadMissing('town');
        $townName = $poi->town?->name ?? '(unknown town)';

        $cats = implode(', ', $poi->categoryList());

        $lines = [
            'You write concise editorial copy for an Australian road-trip / regional travel website called Unbeaten Track.',
            'Write 2 to 4 short paragraphs about the following point of interest (POI). Tone: warm, practical, not hype. Do not invent specific businesses, street addresses, or events unless they are clearly implied by the facts below.',
            'If facts are thin, keep it general and mention that details should be verified locally.',
            '',
            'Output rules:',
            '- Return HTML fragment only (no <!DOCTYPE>, no <html>, no <head>, no <body> wrapper).',
            '- Allowed tags: <p>, <br>, <strong>, <em>, <ul>, <ol>, <li>, <blockquote>, <a href="https://...">.',
            '- Do not use headings (<h2>–<h6>) or open with the POI name as a title line: the admin page already shows the official name above this field. Write body copy only (paragraphs and lists as needed).',
            '- Do not use markdown. Do not use inline styles or class names except plain tags.',
            '',
            'POI facts:',
            '- Name: '.($poi->name ?? ''),
            '- Linked town: '.$townName,
            '- State / territory: '.($poi->state ?? ''),
            '- Categories: '.($cats !== '' ? $cats : '(none)'),
            '- Status: '.($poi->status ?? ''),
            '- Verification: '.($poi->verification_status?->value ?? 'not_verified'),
            '- Short description: '.($poi->short_description ?? '(none)'),
        ];

        $notes = trim((string) ($poi->spreadsheet_notes ?? ''));
        if ($notes !== '') {
            $lines[] = '- Internal / import notes (do not quote verbatim; use only if helpful): '.$notes;
        }

        return implode("\n", $lines);
    }

    private function buildNarrationPrompt(Poi $poi): string
    {
        $poi->loadMissing('town');
        $townName = $poi->town?->name ?? '(unknown town)';
        $cats = implode(', ', $poi->categoryList());
        $aboutPlain = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($poi->about_html ?? ''))) ?? '');
        if (strlen($aboutPlain) > 1_800) {
            $aboutPlain = substr($aboutPlain, 0, 1_797).'...';
        }

        $lines = [
            'You write a spoken narration script for Unbeaten Track, an Australian road-trip and regional travel product.',
            'Write one continuous narration for a driver or passenger approaching or arriving at this point of interest. Follow the system rules exactly.',
            '',
            'POI facts:',
            '- Name: '.($poi->name ?? ''),
            '- Linked town: '.$townName,
            '- State / territory: '.($poi->state ?? ''),
            '- Categories: '.($cats !== '' ? $cats : '(none)'),
            '- Status: '.($poi->status ?? ''),
            '- Verification: '.($poi->verification_status?->value ?? 'not_verified'),
            '- Short description: '.($poi->short_description ?? '(none)'),
        ];

        if ($aboutPlain !== '') {
            $lines[] = '- Long description (plain text excerpt for facts only; do not read it verbatim as a list): '.$aboutPlain;
        }

        $notes = trim((string) ($poi->spreadsheet_notes ?? ''));
        if ($notes !== '') {
            $lines[] = '- Internal / import notes (do not quote verbatim; use only if helpful): '.$notes;
        }

        return implode("\n", $lines);
    }

    private function draftPoiSystemInstructions(): string
    {
        $text = trim((string) config('town_ai.poi_draft_system_instructions', ''));

        return $text !== '' ? $text : 'Follow the user message.';
    }

    private function draftNarrationSystemInstructions(): string
    {
        $text = trim((string) config('town_ai.poi_narration_draft_system_instructions', ''));

        return $text !== '' ? $text : 'Follow the user message.';
    }

    private function callAnthropic(string $prompt, string $system): string
    {
        $key = (string) config('town_ai.anthropic_api_key');
        $model = (string) config('town_ai.anthropic_model');

        $response = Http::timeout(90)
            ->withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 2_048,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->formatAnthropicHttpError($response));
        }

        $text = $this->firstAnthropicTextBlock($response->json('content'));
        if ($text === null || trim($text) === '') {
            throw new RuntimeException('Claude returned no text. Try again or pick another model in ANTHROPIC_MODEL.');
        }

        return $text;
    }

    private function formatAnthropicHttpError(Response $response): string
    {
        $status = $response->status();
        $json = $response->json();
        $msg = null;
        if (is_array($json)) {
            $err = $json['error'] ?? null;
            if (is_array($err)) {
                $msg = $err['message'] ?? $err['type'] ?? null;
            }
        }
        if (! is_string($msg) || $msg === '') {
            $msg = $response->body();
        }
        $msg = trim(preg_replace('/\s+/', ' ', (string) $msg) ?? '');
        if (strlen($msg) > 400) {
            $msg = substr($msg, 0, 397).'…';
        }

        return 'Claude API error ('.$status.'): '.($msg !== '' ? $msg : 'no details');
    }

    private function firstAnthropicTextBlock(mixed $content): ?string
    {
        if (! is_array($content)) {
            return null;
        }
        foreach ($content as $block) {
            if (! is_array($block)) {
                continue;
            }
            if (($block['type'] ?? '') === 'text' && isset($block['text']) && is_string($block['text'])) {
                return $block['text'];
            }
        }

        return null;
    }

    private function callOpenAi(string $prompt, string $system): string
    {
        $key = (string) config('town_ai.openai_api_key');
        $model = (string) config('town_ai.openai_model');

        try {
            $response = Http::timeout(90)
                ->withToken($key)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                ])
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('OpenAI request failed: '.$e->getMessage(), 0, $e);
        }

        $text = $response->json('choices.0.message.content');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Unexpected response from OpenAI.');
        }

        return $text;
    }

    private function stripCodeFences(string $raw): string
    {
        $t = trim($raw);
        if (preg_match('/^```[a-zA-Z0-9]*\s*\R(.*)\R```$/s', $t, $m)) {
            return trim($m[1]);
        }

        return $raw;
    }

    private function normalizeNarrationScript(string $raw): string
    {
        $t = trim($raw);
        $t = str_replace(
            ["\u{2014}", "\u{2013}"],
            [' - ', '-'],
            $t
        );
        $t = strip_tags($t);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = trim(preg_replace("/[ \t]+/u", ' ', $t) ?? '');
        $t = preg_replace("/\R{3,}/u", "\n\n", $t) ?? $t;

        return trim((string) $t);
    }
}
