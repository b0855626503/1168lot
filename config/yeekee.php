<?php

return [
    'enabled' => (bool) env('YEEKEE_ENABLED', false),
    'api_enabled' => (bool) env('YEEKEE_API_ENABLED', false),
    'shoot_enabled' => (bool) env('YEEKEE_SHOOT_ENABLED', false),
    'result_enabled' => (bool) env('YEEKEE_RESULT_ENABLED', false),
    'reward_enabled' => (bool) env('YEEKEE_REWARD_ENABLED', false),
    'settlement_enabled' => (bool) env('YEEKEE_SETTLEMENT_ENABLED', false),
];
