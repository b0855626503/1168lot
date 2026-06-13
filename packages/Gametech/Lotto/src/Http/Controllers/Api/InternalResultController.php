<?php

namespace Gametech\Lotto\Http\Controllers\Api;

use Gametech\Lotto\Services\InternalResultSources\InternalResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class InternalResultController
{
    public function __construct(private InternalResultService $service) {}

    public function exphuay(string $type, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->service->fetch('exphuay', [
            'type' => $type,
            'date' => (string) $request->query('date', ''),
            'page' => $page,
        ]);

        return new JsonResponse($result, 200);
    }

    public function dowjonesMidnight(Request $request): JsonResponse
    {
        $result = $this->service->fetch('dowjones-midnight', [
            'date' => (string) $request->query('date', ''),
        ]);

        return new JsonResponse($result, 200);
    }

    public function dowjonesExtra(Request $request): JsonResponse
    {
        $result = $this->service->fetch('dowjones-extra', [
            'date' => (string) $request->query('date', ''),
        ]);

        return new JsonResponse($result, 200);
    }

    public function expalert(string $slug, Request $request): JsonResponse
    {
        $result = $this->service->fetch('expalert', [
            'slug' => $slug,
            'date' => (string) $request->query('date', ''),
        ]);

        return new JsonResponse($result, 200);
    }

    public function expalertByType(Request $request): JsonResponse
    {
        $type = (string) $request->query('type', '');
        $requestedDate = (string) $request->query('date', '');

        if ($type === '') {
            return $this->empty203Response($type, $requestedDate);
        }

        $apiKey = (string) config('lotto_auto_result.internal_result_sources.expalert.api_key', (string) env('EXPHUAY_API_KEY', ''));
        $headers = [];
        if ($apiKey !== '') {
            $headers['x-api-key'] = $apiKey;
        }

        // Try /data/backward/{slug} first when a date is given — it has all historical entries.
        // Use tolerance-aware matching to account for market date offsets.
        if ($requestedDate !== '') {
            $backwardUrl = 'https://api.expalert.cc/data/backward/'.rawurlencode($type);
            $backwardResponse = Http::timeout(15)->withHeaders($headers)->get($backwardUrl);

            if ($backwardResponse->successful()) {
                $payload = $backwardResponse->json();
                $entries = is_array($payload) ? ($payload['data'] ?? []) : [];

                if (is_array($entries)) {
                    foreach ($entries as $entry) {
                        if (! is_array($entry)) {
                            continue;
                        }
                        $result = $entry['result'] ?? [];
                        $isoDate = (string) ($result['date'] ?? '');
                        $entryDate = $this->isoToBangkokDate($isoDate);

                        if ($this->dateWithinTolerance($entryDate, $requestedDate, 2)) {
                            return $this->build203Response($type, $entry, $entryDate);
                        }
                    }
                }
            }
        }

        // Fallback: /data/result/{slug} for latest
        $resultUrl = 'https://api.expalert.cc/data/result/'.rawurlencode($type);
        $response = Http::timeout(15)->withHeaders($headers)->get($resultUrl);

        if (! $response->successful()) {
            return $this->empty203Response($type, $requestedDate);
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return $this->empty203Response($type, $requestedDate);
        }

        $data = $payload['data'] ?? [];
        if (! is_array($data)) {
            return $this->empty203Response($type, $requestedDate);
        }

        $result = $data['result'] ?? [];
        $isoDate = (string) ($result['date'] ?? '');
        $entryDate = $this->isoToBangkokDate($isoDate);

        // If a specific date was requested but the latest result is too far
        // from the requested date, the result is stale / not ready yet.
        if ($requestedDate !== '' && $entryDate !== '' && ! $this->dateWithinTolerance($entryDate, $requestedDate, 2)) {
            return $this->empty203Response($type, $requestedDate);
        }

        return $this->build203Response($type, $data, $entryDate);
    }

    private function isoToBangkokDate(string $isoDate): string
    {
        if ($isoDate === '') {
            return '';
        }

        try {
            return Carbon::parse($isoDate)->setTimezone('Asia/Bangkok')->format('Y-m-d');
        } catch (\Throwable) {
            return $isoDate;
        }
    }

    /**
     * Check whether two Y-m-d date strings are within the given day tolerance.
     */
    private function dateWithinTolerance(string $date1, string $date2, int $maxDays): bool
    {
        if ($date1 === '' || $date2 === '') {
            return false;
        }

        try {
            $d1 = Carbon::createFromFormat('Y-m-d', $date1);
            $d2 = Carbon::createFromFormat('Y-m-d', $date2);

            return abs($d1->diffInDays($d2)) <= $maxDays;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string,mixed>  $entry
     */
    private function build203Response(string $type, array $entry, string $drawDate): JsonResponse
    {
        $result = $entry['result'] ?? [];
        $number = (string) ($result['number'] ?? '');
        $under = (string) ($result['under'] ?? '');
        $isoDate = (string) ($result['date'] ?? '');

        $lottosDate = null;
        if ($isoDate !== '') {
            try {
                $lottosDate = Carbon::parse($isoDate)->setTimezone('Asia/Bangkok')->toIso8601String();
            } catch (\Throwable) {
            }
        }

        return new JsonResponse([
            'type' => $type,
            'nameTH' => $entry['th'] ?? '',
            'date' => $drawDate,
            'page' => 1,
            'count' => $number !== '' ? 1 : 0,
            'results' => $number !== ''
                ? [[
                    'id' => 0,
                    'lottosName' => $type,
                    'lottosTH' => $entry['th'] ?? '',
                    'lottosDate' => $lottosDate ?? $isoDate,
                    'lottosTime' => $entry['time'] ?? '',
                    'lottosNumber' => $number,
                    'lottosUnder' => $under,
                ]]
                : [],
        ], 200);
    }

    private function empty203Response(string $type, string $date = ''): JsonResponse
    {
        return new JsonResponse([
            'type' => $type,
            'nameTH' => '',
            'date' => $date,
            'page' => 1,
            'count' => 0,
            'results' => [],
        ], 200);
    }
}
