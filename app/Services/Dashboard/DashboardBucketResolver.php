<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;

class DashboardBucketResolver
{
    private DashboardWebCodeResolver $webCodeResolver;

    public function __construct(DashboardWebCodeResolver $webCodeResolver)
    {
        $this->webCodeResolver = $webCodeResolver;
    }

    public function resolve(string $domain, $model, array $overrideSections = []): array
    {
        return match ($domain) {
            'deposit' => $this->resolveDeposit($model, $overrideSections),
            'withdraw' => $this->resolveWithdraw($model, $overrideSections),
            'register' => $this->resolveRegister($model, $overrideSections),
            'bonus' => $this->resolveBonus($model, $overrideSections),
            'lotto' => $this->resolveLotto($model, $overrideSections),
            default => [],
        };
    }

    public function resolveDeposit($model, array $overrideSections = []): array
    {
        $sections = $this->normalizeSections($overrideSections ?: ['deposit', 'net']);
        $webCode = $this->webCodeResolver->fromDeposit($model);

        $dates = [
            $this->normalizeDate($model->date_create ?? null),
            $this->normalizeDate($model->getOriginal('date_create') ?? null),
        ];

        return $this->buildBuckets($dates, $webCode, $sections);
    }

    public function resolveWithdraw($model, array $overrideSections = []): array
    {
        $sections = $this->normalizeSections($overrideSections ?: ['withdraw', 'net']);
        $webCode = $this->webCodeResolver->resolve();

        $before = $this->withdrawBucketDate([
            'status' => $model->getOriginal('status'),
            'enable' => $model->getOriginal('enable'),
            'date_create' => $model->getOriginal('date_create'),
            'date_approve' => $model->getOriginal('date_approve'),
        ]);

        $after = $this->withdrawBucketDate([
            'status' => $model->status,
            'enable' => $model->enable,
            'date_create' => $model->date_create,
            'date_approve' => $model->date_approve,
        ]);

        return $this->buildBuckets([$before, $after], $webCode, $sections);
    }

    public function resolveRegister($model, array $overrideSections = []): array
    {
        $sections = $this->normalizeSections($overrideSections ?: ['register', 'conversion', 'funnel']);
        $webCode = $this->webCodeResolver->resolve();

        $beforeDate = $this->normalizeDate($model->getOriginal('date_regis') ?? null)
            ?: $this->normalizeDate($model->getOriginal('date_create') ?? null);
        $afterDate = $this->normalizeDate($model->date_regis ?? null)
            ?: $this->normalizeDate($model->date_create ?? null);

        return $this->buildBuckets([$beforeDate, $afterDate], $webCode, $sections);
    }

    public function resolveBonus($model, array $overrideSections = []): array
    {
        $sections = $this->normalizeSections($overrideSections ?: ['bonus']);
        $webCode = $this->webCodeResolver->resolve();

        $dates = [
            $this->normalizeDate($model->date_create ?? null),
            $this->normalizeDate($model->getOriginal('date_create') ?? null),
        ];

        return $this->buildBuckets($dates, $webCode, $sections);
    }

    public function resolveLotto($model, array $overrideSections = []): array
    {
        $payload = is_array($model) ? $model : [];
        $webCode = $this->webCodeResolver->resolve((string) ($payload['web_code'] ?? ''));

        $cashDates = $this->collectLottoDates($payload, ['cash_date', 'cash_dates', 'created_at', 'cash_original_date']);
        $productDates = $this->collectLottoDates($payload, ['product_date', 'product_dates', 'ticket_date', 'ticket_dates']);
        $riskDates = $this->collectLottoDates($payload, ['risk_date', 'risk_dates', 'updated_at', 'snapshot_at']);
        $operationDates = $this->collectLottoDates($payload, ['operation_date', 'operation_dates', 'settled_at', 'result_at']);
        $insightDates = $this->collectLottoDates($payload, ['insight_date', 'insight_dates', 'bet_confirmed_at', 'bet_confirmed_dates']);

        if (!empty($overrideSections)) {
            $dates = array_values(array_unique(array_filter(array_merge(
                $cashDates,
                $productDates,
                $riskDates,
                $operationDates,
                $insightDates
            ))));

            if (empty($dates)) {
                $dates[] = now()->toDateString();
            }

            return $this->buildBuckets($dates, $webCode, $this->normalizeSections($overrideSections));
        }

        $buckets = [];
        $this->mergeLottoBuckets($buckets, $cashDates, $webCode, ['lotto_cash', 'net']);
        $this->mergeLottoBuckets($buckets, $productDates, $webCode, ['lotto_product']);
        $this->mergeLottoBuckets($buckets, $riskDates, $webCode, ['lotto_risk']);
        $this->mergeLottoBuckets($buckets, $operationDates, $webCode, ['lotto_operations', 'lotto_product']);
        $this->mergeLottoBuckets($buckets, $insightDates, $webCode, ['lotto_bet_type_insights']);

        if (empty($buckets)) {
            $this->mergeLottoBuckets(
                $buckets,
                [now()->toDateString()],
                $webCode,
                ['lotto_cash', 'lotto_product', 'lotto_risk', 'lotto_bet_type_insights']
            );
        }

        return array_values($buckets);
    }

    private function withdrawBucketDate(array $attrs): ?string
    {
        $enable = (string) ($attrs['enable'] ?? 'Y');
        $status = (int) ($attrs['status'] ?? 0);

        if ($enable !== 'Y') {
            return $this->normalizeDate($attrs['date_approve'] ?? null)
                ?: $this->normalizeDate($attrs['date_create'] ?? null);
        }

        if ($status === 1) {
            return $this->normalizeDate($attrs['date_approve'] ?? null)
                ?: $this->normalizeDate($attrs['date_create'] ?? null);
        }

        return $this->normalizeDate($attrs['date_create'] ?? null)
            ?: $this->normalizeDate($attrs['date_approve'] ?? null);
    }

    private function buildBuckets(array $dates, string $webCode, array $sections): array
    {
        $buckets = [];

        foreach ($dates as $date) {
            if (!$date) {
                continue;
            }

            $key = $webCode . '|' . $date;
            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'summary_date' => $date,
                    'web_code' => $webCode,
                    'updated_sections' => $sections,
                ];
            } else {
                $buckets[$key]['updated_sections'] = $this->normalizeSections(array_merge(
                    $buckets[$key]['updated_sections'],
                    $sections
                ));
            }
        }

        return array_values($buckets);
    }

    private function normalizeDate($value): ?string
    {
        if (empty($value) || in_array($value, ['0000-00-00', '0000-00-00 00:00:00'], true)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeSections(array $sections): array
    {
        $sections = array_values(array_unique(array_filter(array_map(
            fn ($section) => is_string($section) ? trim($section) : '',
            $sections
        ))));

        sort($sections);

        return $sections;
    }

    /**
     * @param array<string, mixed> $payload
     * @param string[] $keys
     * @return string[]
     */
    private function collectLottoDates(array $payload, array $keys): array
    {
        $dates = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];
            if (is_array($value)) {
                foreach ($value as $item) {
                    $date = $this->normalizeDate($item);
                    if ($date) {
                        $dates[] = $date;
                    }
                }
                continue;
            }

            $date = $this->normalizeDate($value);
            if ($date) {
                $dates[] = $date;
            }
        }

        return array_values(array_unique($dates));
    }

    /**
     * @param array<string, array<string, mixed>> $buckets
     * @param string[] $dates
     * @param string[] $sections
     */
    private function mergeLottoBuckets(array &$buckets, array $dates, string $webCode, array $sections): void
    {
        $sections = $this->normalizeSections($sections);
        foreach ($dates as $date) {
            if (!$date) {
                continue;
            }

            $key = $webCode . '|' . $date;
            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'summary_date' => $date,
                    'web_code' => $webCode,
                    'updated_sections' => $sections,
                ];
                continue;
            }

            $buckets[$key]['updated_sections'] = $this->normalizeSections(array_merge(
                $buckets[$key]['updated_sections'],
                $sections
            ));
        }
    }
}
