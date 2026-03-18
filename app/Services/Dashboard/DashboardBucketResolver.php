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
}
