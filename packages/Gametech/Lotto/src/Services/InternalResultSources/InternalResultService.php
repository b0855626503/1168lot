<?php

namespace Gametech\Lotto\Services\InternalResultSources;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class InternalResultService
{
    public function __construct(
        private DateInputNormalizer $dateNormalizer,
        private InternalResultSourceResolver $resolver
    ) {
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function fetch(string $source, array $params): array
    {
        $inputDate = trim((string) ($params['date'] ?? ''));
        $normalizedInputDate = null;

        try {
            if ($inputDate !== '') {
                $normalizedInputDate = $this->dateNormalizer->normalize($inputDate);
            }
        } catch (InvalidArgumentException $exception) {
            return $this->buildFailure($source, $source, null, [[
                'code' => 'INVALID_DATE_FORMAT',
                'message' => $exception->getMessage(),
            ]]);
        }

        $driverParams = $params;
        if ($normalizedInputDate instanceof \Carbon\Carbon) {
            $driverParams['date'] = $normalizedInputDate->format('Y-m-d');
        } else {
            unset($driverParams['date']);
        }

        try {
            $driver = $this->resolver->resolve($source);
        } catch (InvalidArgumentException $exception) {
            return $this->buildFailure($source, $source, $normalizedInputDate instanceof Carbon ? $normalizedInputDate->format('Y-m-d') : null, [[
                'code' => 'UNSUPPORTED_SOURCE',
                'message' => $exception->getMessage(),
            ]]);
        }

        $payload = $driver->fetch($driverParams);
        $fetch = is_array($payload['fetch'] ?? null) ? $payload['fetch'] : [];
        $errors = [];

        $rawResult = $this->decodeResponseBody($fetch['response_body'] ?? null);
        if (! ($fetch['ok'] ?? false)) {
            $errors[] = [
                'code' => (string) ($fetch['error_code'] ?? 'FETCH_FAILED'),
                'message' => (string) ($fetch['error_message'] ?? 'Failed to fetch upstream result.'),
                'http_status' => $fetch['http_status'] ?? null,
            ];
        }

        if ($source === 'exphuay') {
            $selection = $this->selectExphuayRecord(
                $rawResult,
                $normalizedInputDate instanceof Carbon ? $normalizedInputDate : null
            );
            $rawResult = $selection['record'];
            $errors = array_merge($errors, $selection['errors']);
        } elseif ($source === 'dowjones-extra') {
            $selection = $this->selectDowjonesExtraRecord(
                $rawResult,
                $normalizedInputDate instanceof Carbon ? $normalizedInputDate : null
            );
            $rawResult = $selection['record'];
            $errors = array_merge($errors, $selection['errors']);
        }

        $normalized = $this->extractNormalizedResult($source, $rawResult);
        $resolvedDrawDate = $this->resolveDrawDateFromRawResult($rawResult);
        $drawDate = $resolvedDrawDate;
        if (
            $normalizedInputDate instanceof Carbon
            && ! $this->rawResultHasResolvableDrawDate($rawResult)
        ) {
            $drawDate = $normalizedInputDate->format('Y-m-d');
        }
        $meta = [
            'remote_url' => (string) ($payload['remote_url'] ?? ''),
            'request_params' => is_array($payload['request_params'] ?? null) ? $payload['request_params'] : [],
            'fetched_at' => now()->toIso8601String(),
            'latency_ms' => (int) ($fetch['duration_ms'] ?? 0),
            'http_status' => $fetch['http_status'] ?? null,
        ];

        if (in_array($source, ['dowjones-midnight', 'dowjones-extra'], true)) {
            $meta['dowjones_supplemental'] = [
                'start_spin' => $this->pullFirst($rawResult, [['data', 'start_spin'], ['start_spin']]),
                'show_result' => $this->pullFirst($rawResult, [['data', 'show_result'], ['show_result']]),
                'now' => $this->pullFirst($rawResult, [['now']]),
                'update' => $this->pullFirst($rawResult, [['update']]),
            ];
        }

        return [
            'success' => $errors === [],
            'source' => (string) ($payload['source'] ?? $source),
            'type' => (string) ($payload['type'] ?? $source),
            'draw_date' => $drawDate,
            'raw_result' => $rawResult,
            'normalized_result' => $normalized,
            'meta' => $meta,
            'errors' => $errors,
        ];
    }

    /**
     * @param mixed $responseBody
     * @return array<string,mixed>
     */
    private function decodeResponseBody($responseBody): array
    {
        if (! is_string($responseBody) || trim($responseBody) === '') {
            return [];
        }

        $decoded = json_decode($responseBody, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return [
            'raw_body' => $responseBody,
        ];
    }

    /**
     * @param array<string,mixed> $rawResult
     * @return array<string,string|null>
     */
    private function extractNormalizedResult(string $source, array $rawResult): array
    {
        $firstPrize = $this->asDigitString($this->pullFirst($rawResult, [
            ['lottosNumber'],
            ['data', 'results', 'first_prize'],
            ['data', 'results', 'firstPrize'],
            ['data', 'results', 'digit5'],
            ['results', 'first_prize'],
            ['results', 'firstPrize'],
            ['results', 'digit5'],
            ['first_prize'],
            ['firstPrize'],
            ['digit5'],
        ]));
        $digit5 = $this->asDigitString($this->pullFirst($rawResult, [
            ['data', 'results', 'digit5'],
            ['results', 'digit5'],
            ['digit5'],
            ['digit_5'],
        ]));
        $primaryFiveDigit = $digit5 ?? $firstPrize;
        $bottom2 = $this->asDigitString($this->pullFirst($rawResult, [
            ['lottosUnder'],
            ['data', 'results', 'bottom_2'],
            ['data', 'results', 'last_2'],
            ['results', 'bottom_2'],
            ['results', 'last_2'],
            ['bottom_2'],
            ['last_2'],
        ]));

        if ($bottom2 === null && in_array($source, ['dowjones-midnight', 'dowjones-extra'], true)) {
            $bottom2 = $this->takeLeftDigits($primaryFiveDigit, 2);
        }

        return [
            'first_prize' => $firstPrize,
            'top_3' => $this->asDigitString($this->pullFirst($rawResult, [
                ['data', 'results', 'top_3'],
                ['results', 'top_3'],
                ['top_3'],
            ])) ?? $this->takeRightDigits($firstPrize, 3),
            'top_2' => $this->asDigitString($this->pullFirst($rawResult, [
                ['data', 'results', 'top_2'],
                ['results', 'top_2'],
                ['top_2'],
            ])) ?? $this->takeRightDigits($firstPrize, 2),
            'bottom_2' => $bottom2,
            'digit_4' => $this->asDigitString($this->pullFirst($rawResult, [
                ['data', 'results', 'digit4'],
                ['results', 'digit4'],
                ['digit4'],
                ['digit_4'],
            ])),
            'digit_5' => $digit5 ?? $firstPrize,
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<int,array<int,string>> $paths
     */
    private function pullFirst(array $payload, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = Arr::get($payload, implode('.', $path));
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function asDigitString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);
        if (! is_string($digits) || $digits === '') {
            return null;
        }

        return $digits;
    }

    private function takeRightDigits(?string $value, int $length): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return strlen($value) >= $length ? substr($value, -$length) : null;
    }

    private function takeLeftDigits(?string $value, int $length): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return strlen($value) >= $length ? substr($value, 0, $length) : null;
    }

    /**
     * @param array<string,mixed> $rawResult
     */
    private function resolveDrawDateFromRawResult(array $rawResult): string
    {
        $candidates = [
            $this->resolveExphuayDrawDate($rawResult),
            $this->pullFirst($rawResult, [['data', 'lotto_date']]),
            $this->pullFirst($rawResult, [['lotto_date']]),
            $this->pullFirst($rawResult, [['date']]),
            $this->pullFirst($rawResult, [['draw_date']]),
        ];

        foreach ($candidates as $candidate) {
            if (! is_scalar($candidate)) {
                continue;
            }

            $value = trim((string) $candidate);
            if ($value === '') {
                continue;
            }

            try {
                return $this->dateNormalizer->normalize($value)->format('Y-m-d');
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return now()->format('Y-m-d');
    }

    /**
     * @param array<string,mixed> $rawResult
     * @return array{record: array<string,mixed>, errors: array<int,array<string,mixed>>}
     */
    private function selectExphuayRecord(array $rawResult, ?Carbon $requestedDate): array
    {
        $records = $this->extractExphuayRecords($rawResult);
        if ($records === []) {
            return [
                'record' => $rawResult,
                'errors' => [],
            ];
        }

        if ($requestedDate instanceof Carbon) {
            $expected = $requestedDate->format('Y-m-d');
            foreach ($records as $record) {
                $recordDate = $this->resolveExphuayDrawDate($record);
                if ($recordDate === $expected) {
                    $record['draw_date'] = $recordDate;
                    return [
                        'record' => $record,
                        'errors' => [],
                    ];
                }
            }

            return [
                'record' => [],
                'errors' => [[
                    'code' => 'DRAW_DATE_NOT_FOUND',
                    'message' => 'No exphuay draw matched the requested date.',
                    'requested_date' => $expected,
                ]],
            ];
        }

        $selected = $records[0];
        $selected['draw_date'] = $this->resolveExphuayDrawDate($selected);

        return [
            'record' => $selected,
            'errors' => [],
        ];
    }

    /**
     * @param array<string,mixed> $rawResult
     * @return array<int,array<string,mixed>>
     */
    private function extractExphuayRecords(array $rawResult): array
    {
        $nodes = $rawResult['nodes'] ?? null;
        if (! is_array($nodes)) {
            return [];
        }

        $nodePayload = Arr::get($nodes, '1.data');
        if (! is_array($nodePayload)) {
            return [];
        }

        $recordRefs = $nodePayload[1] ?? null;
        if (! is_array($recordRefs)) {
            return [];
        }

        $records = [];
        foreach ($recordRefs as $reference) {
            $resolved = $this->resolveExphuaySerializedValue($nodePayload, $reference);
            if (is_array($resolved) && Arr::has($resolved, ['lottosDate', 'lottosNumber'])) {
                $records[] = $resolved;
            }
        }

        return $records;
    }

    /**
     * @param array<int,mixed> $payload
     */
    private function resolveExphuaySerializedValue(array $payload, mixed $value, array $seen = []): mixed
    {
        if (is_int($value) && array_key_exists($value, $payload)) {
            if (isset($seen[$value])) {
                return null;
            }

            $seen[$value] = true;
            return $this->resolveExphuaySerializedValue($payload, $payload[$value], $seen);
        }

        if (! is_array($value)) {
            return $value;
        }

        $resolved = [];
        foreach ($value as $key => $child) {
            $resolved[$key] = $this->resolveExphuaySerializedValue($payload, $child, $seen);
        }

        return $resolved;
    }

    /**
     * @param array<string,mixed> $rawResult
     */
    private function resolveExphuayDrawDate(array $rawResult): ?string
    {
        $lottoDate = $this->pullFirst($rawResult, [['lottosDate']]);
        if (! is_scalar($lottoDate) || trim((string) $lottoDate) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $lottoDate)
                ->setTimezone('Asia/Bangkok')
                ->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string,mixed> $rawResult
     * @return array{record: array<string,mixed>, errors: array<int,array<string,mixed>>}
     */
    private function selectDowjonesExtraRecord(array $rawResult, ?Carbon $requestedDate): array
    {
        $history = $rawResult['data'] ?? null;
        if (! is_array($history) || ! array_is_list($history)) {
            return [
                'record' => $rawResult,
                'errors' => [],
            ];
        }

        if (! ($requestedDate instanceof Carbon)) {
            return [
                'record' => $rawResult,
                'errors' => [],
            ];
        }

        $expected = $requestedDate->format('Y-m-d');
        foreach ($history as $record) {
            if (! is_array($record)) {
                continue;
            }

            $lottoDate = $this->asDateString($record['lotto_date'] ?? null);
            if ($lottoDate === $expected) {
                return [
                    'record' => $record,
                    'errors' => [],
                ];
            }
        }

        return [
            'record' => [],
            'errors' => [[
                'code' => 'DRAW_DATE_NOT_FOUND',
                'message' => 'No dowjones-extra draw matched the requested date.',
                'requested_date' => $expected,
            ]],
        ];
    }

    private function asDateString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $date = trim((string) $value);
        if ($date === '') {
            return null;
        }

        try {
            return $this->dateNormalizer->normalize($date)->format('Y-m-d');
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param array<string,mixed> $rawResult
     */
    private function rawResultHasResolvableDrawDate(array $rawResult): bool
    {
        $candidates = [
            $this->resolveExphuayDrawDate($rawResult),
            $this->pullFirst($rawResult, [['data', 'lotto_date']]),
            $this->pullFirst($rawResult, [['lotto_date']]),
            $this->pullFirst($rawResult, [['date']]),
            $this->pullFirst($rawResult, [['draw_date']]),
        ];

        foreach ($candidates as $candidate) {
            if ($this->asDateString($candidate) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     * @return array<string,mixed>
     */
    private function buildFailure(string $source, string $type, ?string $drawDate, array $errors): array
    {
        return [
            'success' => false,
            'source' => $source,
            'type' => $type,
            'draw_date' => $drawDate,
            'raw_result' => [],
            'normalized_result' => [
                'first_prize' => null,
                'top_3' => null,
                'top_2' => null,
                'bottom_2' => null,
                'digit_4' => null,
                'digit_5' => null,
            ],
            'meta' => [
                'remote_url' => null,
                'request_params' => [],
                'fetched_at' => now()->toIso8601String(),
                'latency_ms' => 0,
            ],
            'errors' => $errors,
        ];
    }
}
