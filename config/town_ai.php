<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Town "About" AI drafting (admin only)
    |--------------------------------------------------------------------------
    |
    | Keys are read only on the server. Set one or both provider keys.
    | TOWN_ABOUT_AI_PROVIDER: auto | anthropic | openai
    | "auto" prefers Anthropic when ANTHROPIC_API_KEY is set, otherwise OpenAI.
    |
    */
    'provider' => env('TOWN_ABOUT_AI_PROVIDER', 'auto'),

    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    'anthropic_api_key' => env('ANTHROPIC_API_KEY'),
    // Use a current Messages API id (see Anthropic model docs). Override via ANTHROPIC_MODEL in .env.
    'anthropic_model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),

    /*
    |--------------------------------------------------------------------------
    | Hidden style rules (admin "Draft with Claude" only)
    |--------------------------------------------------------------------------
    |
    | Sent as the API "system" instruction. Not shown in the browser. Override
    | the entire block by setting TOWN_ABOUT_DRAFT_SYSTEM in .env (multi-line
    | supported in double quotes). Leave unset to use the default below.
    |
    */
    'draft_system_instructions' => env('TOWN_ABOUT_DRAFT_SYSTEM') ?: <<<'EOT'
Follow these rules on every draft, in addition to the user message:
- Do not use the em dash character (Unicode U+2014). Use commas, colons, parentheses, or split into two sentences instead.
- Write for visitors and road-trippers: warm, inviting, and practical (why someone might linger, what to expect, what to plan for). Stay truthful; do not invent named businesses, street addresses, dates, or events.
- Prefer clear Australian English. Avoid hollow hype; ground colour in the facts supplied.
- Obey the HTML output rules in the user message exactly.
EOT,
];
