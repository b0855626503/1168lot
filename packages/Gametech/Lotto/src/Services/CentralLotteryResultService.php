<?php

namespace Gametech\Lotto\Services;

use Carbon\CarbonImmutable;
use Gametech\Lotto\Services\InternalResultSources\InternalResultService;

class CentralLotteryResultService
{
    public function __construct(private InternalResultService $internalResultService) {}

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $type, string $date): array
    {
        $canonicalType = strtolower(trim($type));
        $businessDate = trim($date);
        $source = $this->resolveSource($canonicalType);

        if ($source === null) {
            return $this->buildFailurePayload($canonicalType, $businessDate, [[
                'code' => 'UNSUPPORTED_TYPE',
                'message' => 'Unsupported lottery type.',
            ]]);
        }

        $params = ['date' => $businessDate];
        if ($source === 'exphuay') {
            $params['type'] = $canonicalType;
        }

        $result = $this->internalResultService->fetch($source, $params);
        if (($result['success'] ?? false) !== true) {
            return $this->buildFailurePayload(
                $canonicalType,
                $businessDate,
                $this->normalizeErrors($result['errors'] ?? []),
                $this->resolveNameTh($canonicalType, is_array($result['raw_result'] ?? null) ? $result['raw_result'] : [])
            );
        }

        $rawResult = is_array($result['raw_result'] ?? null) ? $result['raw_result'] : [];
        $normalizedResult = is_array($result['normalized_result'] ?? null) ? $result['normalized_result'] : [];

        return [
            'type' => $canonicalType,
            'nameTH' => $this->resolveNameTh($canonicalType, $rawResult),
            'date' => $businessDate,
            'page' => 1,
            'count' => 1,
            'results' => [[
                'id' => $this->resolveResultId($rawResult),
                'lottosName' => $this->stringOrDefault($rawResult['lottosName'] ?? null, $canonicalType),
                'lottosTH' => $this->resolveNameTh($canonicalType, $rawResult),
                'lottosDate' => $this->resolveLottosDate($businessDate, $rawResult),
                'lottosTime' => $this->resolveLottosTime($result, $rawResult),
                'lottosNumber' => $this->stringOrDefault(
                    $rawResult['lottosNumber'] ?? null,
                    $this->stringOrDefault($normalizedResult['first_prize'] ?? null, $this->stringOrDefault($normalizedResult['digit_5'] ?? null, ''))
                ),
                'lottosUnder' => $this->stringOrDefault(
                    $rawResult['lottosUnder'] ?? null,
                    $this->stringOrDefault($normalizedResult['bottom_2'] ?? null, '')
                ),
            ]],
        ];
    }

    private function resolveSource(string $canonicalType): ?string
    {
        if (in_array($canonicalType, ['dowjones-midnight', 'dowjones-extra'], true)) {
            return $canonicalType;
        }

        if (in_array($canonicalType, $this->supportedExphuayTypes(), true)) {
            return 'exphuay';
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function supportedExphuayTypes(): array
    {
        return [
            'gsb',
            'baac',
            'goverment',
            'xsthm',
            'minhngoc',
            'mlnhngo',
            'magnum4d',
            'laosvip',
            'nikkei-vip-morning',
            'hanoiasean',
            'szse-vip-morning',
            'laotv',
            'hsi-vip-morning',
            'xosohd',
            'twse-vip',
            'minhngocstar',
            'ktop30-vip',
            'nikkei-vip-afternoon',
            'laoshd',
            'minhngoctv',
            'szse-vip-afternoon',
            'hsi-vip-afternoon',
            'laostars',
            'xosoredcross',
            'sgx-vip',
            'xosounion',
            'xosodevelop',
            'laounion',
            'laosasean',
            'laounionvip',
            'laostarsvip',
            'england-vip',
            'xosoextra',
            'germany-vip',
            'laoredcross',
            'russia-vip',
            'dowjonestar',
            'laosantipap',
            'laopatuxay',
            'laocitizen',
            'nikkei-morning',
            'nikkei-afternoon',
            'egx30',
            'szse-morning',
            'hsi-morning',
            'twse',
            'ktop30',
            'szse-afternoon',
            'hsi-afternoon',
            'sgx',
            'set',
            'bsesn',
            'ftse100',
            'gdaxi',
            'moexbc',
            'dji',
            'laosdevelops',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeErrors(mixed $errors): array
    {
        if (! is_array($errors)) {
            return [[
                'code' => 'FETCH_FAILED',
                'message' => 'Failed to fetch lottery result.',
            ]];
        }

        $normalized = [];
        foreach ($errors as $error) {
            if (! is_array($error)) {
                continue;
            }

            $normalized[] = [
                'code' => (string) ($error['code'] ?? 'FETCH_FAILED'),
                'message' => (string) ($error['message'] ?? 'Failed to fetch lottery result.'),
            ];
        }

        if ($normalized === []) {
            return [[
                'code' => 'FETCH_FAILED',
                'message' => 'Failed to fetch lottery result.',
            ]];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $errors
     * @return array<string, mixed>
     */
    private function buildFailurePayload(string $canonicalType, string $businessDate, array $errors, string $nameTh = ''): array
    {
        return [
            'type' => $canonicalType,
            'nameTH' => $nameTh,
            'date' => $businessDate,
            'page' => 1,
            'count' => 0,
            'results' => [],
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $rawResult
     */
    private function resolveNameTh(string $canonicalType, array $rawResult): string
    {
        $name = $this->stringOrDefault($rawResult['lottosTH'] ?? null, '');
        if ($name !== '') {
            return $name;
        }

        return match ($canonicalType) {
            'dowjones-midnight' => 'ดาวโจนส์เที่ยงคืน',
            'dowjones-extra' => 'ดาวโจนส์ Extra',
            default => $canonicalType,
        };
    }

    /**
     * @param  array<string, mixed>  $rawResult
     */
    private function resolveLottosDate(string $businessDate, array $rawResult): string
    {
        $candidate = $this->stringOrDefault($rawResult['lottosDate'] ?? null, '');
        if ($candidate !== '') {
            return $candidate;
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $businessDate.' 00:00:00', 'Asia/Bangkok')
            ->utc()
            ->format('Y-m-d\TH:i:s.000\Z');
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $rawResult
     */
    private function resolveLottosTime(array $result, array $rawResult): ?string
    {
        $time = $this->stringOrDefault($rawResult['lottosTime'] ?? null, '');
        if ($time !== '') {
            return $time;
        }

        $meta = is_array($result['meta'] ?? null) ? $result['meta'] : [];
        $supplemental = is_array($meta['dowjones_supplemental'] ?? null) ? $meta['dowjones_supplemental'] : [];

        $startSpin = $this->stringOrDefault($supplemental['start_spin'] ?? null, '');

        return $startSpin !== '' ? $startSpin : null;
    }

    /**
     * @param  array<string, mixed>  $rawResult
     */
    private function resolveResultId(array $rawResult): int
    {
        $id = $rawResult['id'] ?? null;

        return is_numeric($id) ? (int) $id : 0;
    }

    private function stringOrDefault(mixed $value, string $default): string
    {
        if ($value === null) {
            return $default;
        }

        if (is_scalar($value)) {
            $resolved = trim((string) $value);

            return $resolved !== '' ? $resolved : $default;
        }

        return $default;
    }
}
