<?php

namespace Gametech\Lotto\Services\InternalResultSources\Drivers;

use Gametech\Lotto\Services\InternalResultSources\Contracts\InternalResultSourceDriver;
use Gametech\Lotto\Services\InternalResultSources\HttpResultFetcher;

class ExpalertResultDriver implements InternalResultSourceDriver
{
    public function __construct(private HttpResultFetcher $fetcher)
    {
    }

    public function sourceKey(): string
    {
        return 'expalert';
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function fetch(array $params): array
    {
        $slug = (string) ($params['slug'] ?? '');
        $url = 'https://api.expalert.cc/data/result/' . rawurlencode($slug);

        $apiKey = (string) config('lotto_auto_result.internal_result_sources.expalert.api_key', (string) env('EXPHUAY_API_KEY', ''));
        $headers = [];
        if ($apiKey !== '') {
            $headers['x-api-key'] = $apiKey;
        }

        $fetch = $this->fetcher->get(
            $url,
            [],
            (int) config('lotto_auto_result.internal_result_sources.timeout_seconds', 15),
            $headers
        );

        // Transform expalert response to legacy-compatible format
        $transformedBody = $this->transformResponse($fetch['response_body'] ?? '');

        $fetch['response_body'] = $transformedBody;

        return [
            'source' => $this->sourceKey(),
            'type' => $slug,
            'remote_url' => $url,
            'fetch' => $fetch,
        ];
    }

    private function transformResponse(string $responseBody): string
    {
        $payload = json_decode($responseBody, true);
        if (! is_array($payload)) {
            return $responseBody;
        }

        $data = $payload['data'] ?? [];
        if (! is_array($data)) {
            return $responseBody;
        }

        $result = $data['result'] ?? [];
        if (! is_array($result)) {
            return $responseBody;
        }

        $number = (string) ($result['number'] ?? '');
        $under = (string) ($result['under'] ?? '');
        $isoDate = (string) ($result['date'] ?? '');
        $slug = (string) ($data['en'] ?? '');

        // Convert to Bangkok draw date
        $drawDate = '';
        $lottosDate = null;
        if ($isoDate !== '') {
            try {
                $dt = \Carbon\Carbon::parse($isoDate)->setTimezone('Asia/Bangkok');
                $drawDate = $dt->format('Y-m-d');
                $lottosDate = $dt->toIso8601String();
            } catch (\Throwable) {
                $drawDate = $isoDate;
            }
        }

        // 203-compatible format — parser/mapping configs work unchanged
        $transformed = [
            'type' => $slug,
            'nameTH' => $data['th'] ?? '',
            'date' => $drawDate,
            'page' => 1,
            'count' => $number !== '' ? 1 : 0,
            'results' => $number !== ''
                ? [[
                    'id' => 0,
                    'lottosName' => $slug,
                    'lottosTH' => $data['th'] ?? '',
                    'lottosDate' => $lottosDate ?? $isoDate,
                    'lottosTime' => $data['time'] ?? '',
                    'lottosNumber' => $number,
                    'lottosUnder' => $under,
                ]]
                : [],
        ];

        return json_encode($transformed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
