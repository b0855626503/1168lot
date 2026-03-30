<?php

namespace Gametech\Lotto\Services\InternalResultSources;

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
            return $this->buildFailure($source, $source, $date->format('Y-m-d'), [[
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

        $normalized = $this->extractNormalizedResult($rawResult);
        $drawDate = $normalizedInputDate instanceof \Carbon\Carbon
            ? $normalizedInputDate->format('Y-m-d')
            : $this->resolveDrawDateFromRawResult($rawResult);
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
    private function extractNormalizedResult(array $rawResult): array
    {
        $firstPrize = $this->asDigitString($this->pullFirst($rawResult, [
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

        return [
            'first_prize' => $firstPrize,
            'top_3' => $this->asDigitString($this->pullFirst($rawResult, [
                ['data', 'results', 'top_3'],
                ['results', 'top_3'],
                ['top_3'],
            ])),
            'top_2' => $this->asDigitString($this->pullFirst($rawResult, [
                ['data', 'results', 'top_2'],
                ['results', 'top_2'],
                ['top_2'],
            ])),
            'bottom_2' => $this->asDigitString($this->pullFirst($rawResult, [
                ['data', 'results', 'bottom_2'],
                ['data', 'results', 'last_2'],
                ['results', 'bottom_2'],
                ['results', 'last_2'],
                ['bottom_2'],
                ['last_2'],
            ])),
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

    /**
     * @param array<string,mixed> $rawResult
     */
    private function resolveDrawDateFromRawResult(array $rawResult): string
    {
        $candidates = [
            $this->pullFirst($rawResult, [['data', 'lotto_date']]),
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
