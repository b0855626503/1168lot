<?php

return [
    'lotto_risk' => [
        'threshold' => (float) env('DASHBOARD_LOTTO_RISK_THRESHOLD', 1000000),
    ],
    'lotto' => [
        'risk_snapshot_retention_days' => (int) env('LOTTO_RISK_SNAPSHOT_RETENTION_DAYS', 7),
    ],
];
