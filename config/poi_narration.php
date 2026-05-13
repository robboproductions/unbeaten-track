<?php

return [
    'provider' => env('POI_NARRATION_PROVIDER', 'elevenlabs'),
    'enabled' => env('POI_NARRATION_ENABLED', true),

    /*
    | Admin POI narration: Baxter / Zoe buttons (Generate audio with …).
    | Baxter uses ELEVENLABS_DEFAULT_VOICE_ID; Zoe uses ELEVENLABS_ZOE_VOICE_ID.
    */
    'voices' => [
        'baxter' => [
            'id' => env('ELEVENLABS_DEFAULT_VOICE_ID', 'jSuBIjxMKhqIfb0wCK1F'),
            'label' => 'Baxter',
        ],
        'zoe' => [
            'id' => env('ELEVENLABS_ZOE_VOICE_ID', 'IwFADcBfc7Yo8KGhxTR5'),
            'label' => 'Zoe',
        ],
    ],

    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io'),
        'default_voice_id' => env('ELEVENLABS_DEFAULT_VOICE_ID', 'jSuBIjxMKhqIfb0wCK1F'),
        'default_model_id' => env('ELEVENLABS_DEFAULT_MODEL_ID', 'eleven_multilingual_v2'),
        'output_format' => env('ELEVENLABS_OUTPUT_FORMAT', 'mp3_44100_128'),
        'timeout_seconds' => 120,
        'voice_settings' => [
            'stability' => 0.5,
            'similarity_boost' => 0.75,
            'style' => 0.0,
            'use_speaker_boost' => true,
        ],
    ],

    'storage' => [
        'disk' => 'public',
        'directory' => 'poi-narrations',
    ],

    /*
    | Town narration MP3 files (same ElevenLabs settings as POI narration).
    */
    'town_storage' => [
        'disk' => env('TOWN_NARRATION_STORAGE_DISK', 'public'),
        'directory' => env('TOWN_NARRATION_STORAGE_DIR', 'town-narrations'),
    ],

    'limits' => [
        'max_script_characters' => 5000,
    ],
];
