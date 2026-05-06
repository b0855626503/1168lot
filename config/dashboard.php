<?php

return [
    'lotto_risk' => [
        'threshold' => (float) env('DASHBOARD_LOTTO_RISK_THRESHOLD', 1000000),
        // BOA-229: 'read_source' is retained for back-compat with operator
        // configuration but is no longer honored at runtime. The risk dashboard
        // reads exclusively from lotto_dashboard_risk_current. The legacy
        // 'snapshot' value is intentionally ignored (no silent fallback).
        'read_source' => env('LOTTO_DASHBOARD_RISK_READ_SOURCE', 'current'),
    ],
    'lotto' => [
        'risk_snapshot_retention_days' => (int) env('LOTTO_RISK_SNAPSHOT_RETENTION_DAYS', 7),
        'legacy_snapshot_write_enabled' => (bool) env('LOTTO_RISK_SNAPSHOT_LEGACY_WRITE_ENABLED', false),
    ],
];
