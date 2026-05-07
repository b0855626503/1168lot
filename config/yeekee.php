<?php

return [
    'enabled' => (bool) env('YEEKEE_ENABLED', false),
    'api_enabled' => (bool) env('YEEKEE_API_ENABLED', false),
    'shoot_enabled' => (bool) env('YEEKEE_SHOOT_ENABLED', false),
    'shooting_enabled' => (bool) env('YEEKEE_SHOOTING_ENABLED', true),
    'result_enabled' => (bool) env('YEEKEE_RESULT_ENABLED', false),
    'reward_enabled' => (bool) env('YEEKEE_REWARD_ENABLED', false),
    'settlement_enabled' => (bool) env('YEEKEE_SETTLEMENT_ENABLED', false),
    'max_shoots_per_member_per_round' => (int) env('YEEKEE_MAX_SHOOTS_PER_MEMBER_PER_ROUND', 100),
    'max_shoots_per_ip_per_minute' => (int) env('YEEKEE_MAX_SHOOTS_PER_IP_PER_MINUTE', 30),
    'shoot_cooldown_seconds' => (int) env('YEEKEE_SHOOT_COOLDOWN_SECONDS', 6),
    'shoot_list_default_limit' => (int) env('YEEKEE_SHOOT_LIST_DEFAULT_LIMIT', 50),
    'shoot_list_max_limit' => (int) env('YEEKEE_SHOOT_LIST_MAX_LIMIT', 100),
    'round_backfill_chunk_size' => (int) env('YEEKEE_ROUND_BACKFILL_CHUNK_SIZE', 500),
    'snapshot' => [
        'sample_limit' => (int) env('YEEKEE_SNAPSHOT_SAMPLE_LIMIT', 100),
        'store_hash' => (bool) env('YEEKEE_SNAPSHOT_STORE_HASH', true),
    ],
    'external_seed' => [
        'enabled' => (bool) env('YEEKEE_EXTERNAL_SEED_ENABLED', false),
        'providers' => [
            'ETH_BLOCK_HASH' => [
                'min_supported_round_duration_minutes' => 15,
                'default_timeout_seconds' => 15,
                'supports_fast_rounds' => false,
            ],
        ],
    ],
];
