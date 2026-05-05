<?php

return [
    'lotto_risk' => [
        'threshold' => (float) env('DASHBOARD_LOTTO_RISK_THRESHOLD', 1000000),
        'read_source' => env('LOTTO_DASHBOARD_RISK_READ_SOURCE', 'snapshot'),
    ],
    'lotto' => [
        'risk_snapshot_retention_days' => (int) env('LOTTO_RISK_SNAPSHOT_RETENTION_DAYS', 7),
    ],
];
