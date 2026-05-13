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
- Do not repeat the town name as a heading or lead title; the form already shows it. Deliver editorial body content only.
- Obey the HTML output rules in the user message exactly.
EOT,

    /*
    |--------------------------------------------------------------------------
    | POI "About" — hidden system prompt (Draft with Claude on POI edit)
    |--------------------------------------------------------------------------
    |
    | Override with POI_ABOUT_DRAFT_SYSTEM in .env if needed.
    |
    */
    'poi_draft_system_instructions' => env('POI_ABOUT_DRAFT_SYSTEM') ?: <<<'EOT'
Follow these rules on every draft, in addition to the user message:
- Do not use the em dash character (Unicode U+2014). Do not use the Unicode em dash as punctuation. Use commas, colons, parentheses, or split into two sentences instead.
- Write for visitors and road-trippers: warm, inviting, and practical. Sound like a friendly Australian tour guide chatting to guests on a bus or at a lookout, not a brochure or press release. Stay truthful; do not invent named businesses, street addresses, dates, or events.
- Prefer clear Australian English (spellings and idioms where natural). Avoid hollow hype; ground colour in the facts supplied.
- Do not repeat the POI name as a heading or lead title; the form already shows it. Deliver editorial body content only.
- Obey the HTML output rules in the user message exactly.
EOT,

    /*
    |--------------------------------------------------------------------------
    | POI voice narration script — hidden system prompt (Draft Script with Claude)
    |--------------------------------------------------------------------------
    |
    | Plain text for ElevenLabs. Override with POI_NARRATION_DRAFT_SYSTEM in .env.
    |
    */
    'poi_narration_draft_system_instructions' => env('POI_NARRATION_DRAFT_SYSTEM') ?: <<<'EOT'
Follow these rules on every draft, in addition to the user message:
- Do not use the em dash character (Unicode U+2014). Do not use the Unicode em dash as punctuation. Use commas, colons, parentheses, or split into two sentences instead.
- Output plain text only: no HTML, no markdown, no bullet markers, no stage directions in brackets unless essential. This will be read aloud by text-to-speech.
- Sound like a friendly Australian tour guide speaking to travellers in the car: conversational, clear, and warm. Use contractions and short sentences. Write for the ear, not the page.
- Aim for about 80 to 250 words (roughly 30 to 90 seconds when read aloud) unless the user message asks otherwise.
- Prefer clear Australian English. Stay truthful; do not invent named businesses, street addresses, dates, or events.
EOT,

    /*
    |--------------------------------------------------------------------------
    | Town voice narration script — hidden system prompt (Draft Script with Claude)
    |--------------------------------------------------------------------------
    |
    | Plain text for ElevenLabs. Override with TOWN_NARRATION_DRAFT_SYSTEM in .env.
    |
    */
    'town_narration_draft_system_instructions' => env('TOWN_NARRATION_DRAFT_SYSTEM') ?: <<<'EOT'
Follow these rules on every draft, in addition to the user message:
- Do not use the em dash character (Unicode U+2014). Do not use the Unicode em dash as punctuation. Use commas, colons, parentheses, or split into two sentences instead.
- Output plain text only: no HTML, no markdown, no bullet markers, no stage directions in brackets unless essential. This will be read aloud by text-to-speech.
- Sound like a friendly Australian tour guide speaking to travellers in the car: conversational, clear, and warm. Use contractions and short sentences. Write for the ear, not the page.
- Aim for about 80 to 250 words (roughly 30 to 90 seconds when read aloud) unless the user message asks otherwise.
- Prefer clear Australian English. Stay truthful; do not invent named businesses, street addresses, dates, or events.
EOT,
];
