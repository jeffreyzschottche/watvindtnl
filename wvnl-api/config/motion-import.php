<?php

return [
    'base_url' => env('MOTION_SOURCE_BASE', 'https://gegevensmagazijn.tweedekamer.nl/OData/v4/2.0'),
    'default_limit' => (int) env('MOTION_BATCH_SIZE', 5),
    'state_path' => env('MOTION_STATE_PATH', storage_path('motions/state.json')),
    'work_dir' => env('MOTION_WORK_DIR', storage_path('motions/tmp')),
    'output_dir' => env('MOTION_OUTPUT_DIR', storage_path('motions/output')),
    'request_delay_microseconds' => (int) env('MOTION_REQUEST_DELAY', 150000),
    'openai' => [
        'endpoint' => env('OPENAI_CHAT_COMPLETIONS_URL', 'https://api.openai.com/v1/chat/completions'),
        'model' => env('MOTION_OPENAI_MODEL', env('OPENAI_MODEL', 'gpt-4.1-mini')),
    ],
];
