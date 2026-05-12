<?php

namespace App\Services;

use App\Models\Town;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class TownAboutAiDraftService
{
    public function __construct(
        private readonly TownAboutHtmlSanitizer $sanitizer,
    ) {}

    /**
     * @throws RuntimeException
     */
    public function draftHtml(Town $town): string
    {
        $provider = $this->resolvedProvider();
        $prompt = $this->buildPrompt($town);

        $raw = match ($provider) {
            'anthropic' => $this->callAnthropic($prompt),
            'openai' => $this->callOpenAi($prompt),
            default => throw new RuntimeException('No AI provider is configured.'),
        };

        $stripped = $this->stripCodeFences($raw);

        return $this->sanitizer->sanitize($stripped);
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

    private function buildPrompt(Town $town): string
    {
        $lines = [
            'You write concise editorial copy for an Australian road-trip / regional travel website called Unbeaten Track.',
            'Write 2 to 4 short paragraphs about the following town. Tone: warm, practical, not hype. Do not invent specific businesses, street addresses, or events unless they are clearly implied by the facts below.',
            'If facts are thin, keep it general and mention that details should be verified locally.',
            '',
            'Output rules:',
            '- Return HTML fragment only (no <!DOCTYPE>, no <html>, no <head>, no <body> wrapper).',
            '- Allowed tags: <p>, <br>, <strong>, <em>, <ul>, <ol>, <li>, <h2>, <h3>, <blockquote>, <a href="https://...">.',
            '- Do not use markdown. Do not use inline styles or class names except plain tags.',
            '',
            'Town facts:',
            '- Name: '.($town->name ?? ''),
            '- State / territory: '.($town->state ?? ''),
            '- Region: '.($town->region ?? '(none)'),
            '- Approx. population: '.($town->population_approx !== null ? (string) $town->population_approx : '(unknown)'),
            '- Status: '.($town->status ?? ''),
            '- Services (yes/no): pub='.($town->has_pub ? 'yes' : 'no').', cafe='.($town->has_cafe ? 'yes' : 'no').', shop='.($town->has_shop ? 'yes' : 'no').', fuel='.($town->has_fuel ? 'yes' : 'no').', caravan park='.($town->has_caravan_park ? 'yes' : 'no'),
        ];

        $hook = trim((string) ($town->editorial_hook ?? ''));
        if ($hook !== '') {
            $lines[] = '- Existing editorial hook / notes (may be rough): '.$hook;
        }

        $notes = trim((string) ($town->spreadsheet_notes ?? ''));
        if ($notes !== '') {
            $lines[] = '- Internal / import notes (do not quote verbatim; use only if helpful): '.$notes;
        }

        return implode("\n", $lines);
    }

    private function draftSystemInstructions(): string
    {
        $text = trim((string) config('town_ai.draft_system_instructions', ''));

        return $text !== '' ? $text : 'Follow the user message.';
    }

    private function callAnthropic(string $prompt): string
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
                'system' => $this->draftSystemInstructions(),
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

    /**
     * Human-readable error from a non-success Claude response.
     */
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

    private function callOpenAi(string $prompt): string
    {
        $key = (string) config('town_ai.openai_api_key');
        $model = (string) config('town_ai.openai_model');

        try {
            $response = Http::timeout(90)
                ->withToken($key)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->draftSystemInstructions()],
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
        if (preg_match('/^```(?:html)?\s*\R(.*)\R```$/s', $t, $m)) {
            return trim($m[1]);
        }

        return $raw;
    }
}
