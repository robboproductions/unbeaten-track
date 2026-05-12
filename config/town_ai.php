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
    'anthropic_model' => env('ANTHROPIC_MODEL', 'claude-3-5-haiku-20241022'),
];
