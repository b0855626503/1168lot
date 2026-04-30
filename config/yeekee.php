<?php

return [
    'enabled' => (bool) env('YEEKEE_ENABLED', false),
    'api_enabled' => (bool) env('YEEKEE_API_ENABLED', false),
    'shoot_enabled' => (bool) env('YEEKEE_SHOOT_ENABLED', false),
    'result_enabled' => (bool) env('YEEKEE_RESULT_ENABLED', false),
    'reward_enabled' => (bool) env('YEEKEE_REWARD_ENABLED', false),
    'settlement_enabled' => (bool) env('YEEKEE_SETTLEMENT_ENABLED', false),
    'max_shoots_per_member_per_round' => (int) env('YEEKEE_MAX_SHOOTS_PER_MEMBER_PER_ROUND', 100),
];
