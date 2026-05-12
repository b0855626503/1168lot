<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Repositories\ArchiveRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LottoResultArchiveController extends Controller
{
    protected ArchiveRepository $archiveRepo;

    public function __construct(?ArchiveRepository $archiveRepo = null)
    {
        $this->archiveRepo = $archiveRepo ?? new ArchiveRepository;
    }

    /**
     * GET /api/v1/lotto/results/{marketCode}
     * Paginated results for a market, grouped by draw_date.
     */
    public function index(Request $request, string $marketCode): JsonResponse
    {
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $perPage = (int) $request->query('per_page', 20);
        $page = (int) $request->query('page', 1);

        if ($page < 1) {
            return response()->json(['message' => 'page must be >= 1'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($perPage < 1 || $perPage > 50) {
            return response()->json(['message' => 'per_page must be between 1 and 50'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $this->marketExists($marketCode)) {
            return response()->json(['message' => 'Market not found'], Response::HTTP_NOT_FOUND);
        }

        if (($fromDate && ! $toDate) || (! $fromDate && $toDate)) {
            return response()->json([
                'message' => 'Both from_date and to_date must be provided, or neither',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($fromDate && ! preg_match($datePattern, (string) $fromDate)) {
            return response()->json(['message' => 'from_date must be YYYY-MM-DD'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($toDate && ! preg_match($datePattern, (string) $toDate)) {
            return response()->json(['message' => 'to_date must be YYYY-MM-DD'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $fromDate && ! $toDate) {
            $fromDate = now()->subDays(365)->format('Y-m-d');
            $toDate = now()->format('Y-m-d');
        }

        if (strtotime((string) $toDate) - strtotime((string) $fromDate) > 366 * 86400) {
            return response()->json([
                'message' => 'Date range must not exceed 366 days',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (strtotime((string) $fromDate) > strtotime((string) $toDate)) {
            return response()->json([
                'message' => 'from_date must be before or equal to to_date',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $queryFingerprint = md5("{$fromDate}|{$toDate}|{$page}|{$perPage}");
        $version = Cache::get("lotto:archive:{$marketCode}:version", 1);
        $cacheKey = "lotto:archive:{$marketCode}:v{$version}:list:{$queryFingerprint}";

        $payload = Cache::remember($cacheKey, 120, function () use (
            $marketCode, $fromDate, $toDate, $perPage
        ): array {
            $paginator = $this->archiveRepo->findDistinctDrawDates(
                $marketCode, $fromDate, $toDate, $perPage,
            );

            $drawDates = collect($paginator->items())->pluck('draw_date')->map(
                fn ($d) => $d instanceof Carbon ? $d->format('Y-m-d') : $d,
            )->filter()->values()->all();

            $rows = ! empty($drawDates)
                ? $this->archiveRepo->findByDrawDates($marketCode, $drawDates)
                : collect();

            $grouped = $rows->groupBy(fn ($r) => $r->draw_date instanceof Carbon
                ? $r->draw_date->format('Y-m-d')
                : $r->draw_date,
            )->map(fn ($dateRows) => [
                'market_code' => $dateRows->first()->market_code,
                'draw_date' => $dateRows->first()->draw_date instanceof Carbon
                    ? $dateRows->first()->draw_date->format('Y-m-d')
                    : $dateRows->first()->draw_date,
                'results' => $dateRows->pluck('result_set', 'draw_key')->all(),
            ])->values();

            return [
                'data' => $grouped,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ];
        });

        return response()->json($payload);
    }

    /**
     * GET /api/v1/lotto/results/{marketCode}/{drawDate}
     */
    public function show(string $marketCode, string $drawDate): JsonResponse
    {
        if (! $this->marketExists($marketCode)) {
            return response()->json(['message' => 'Market not found'], Response::HTTP_NOT_FOUND);
        }

        $cacheKey = "lotto:archive:{$marketCode}:{$drawDate}";

        $payload = Cache::remember($cacheKey, 86400, function () use ($marketCode, $drawDate): array {
            $rows = $this->archiveRepo->findByDrawDates($marketCode, [$drawDate]);

            if ($rows->isEmpty()) {
                return ['_empty' => true];
            }

            return [
                'data' => [
                    'market_code' => $rows->first()->market_code,
                    'draw_date' => $drawDate,
                    'results' => $rows->pluck('result_set', 'draw_key')->all(),
                ],
            ];
        });

        if (($payload['_empty'] ?? false) === true) {
            return response()->json(['message' => 'No results found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($payload);
    }

    /**
     * GET /api/v1/lotto/results/{marketCode}/{drawDate}/{drawKey}
     */
    public function item(string $marketCode, string $drawDate, string $drawKey): JsonResponse
    {
        if (! $this->marketExists($marketCode)) {
            return response()->json(['message' => 'Market not found'], Response::HTTP_NOT_FOUND);
        }

        $cacheKey = "lotto:archive:{$marketCode}:{$drawDate}:{$drawKey}";

        $payload = Cache::remember($cacheKey, 86400, function () use ($marketCode, $drawDate, $drawKey): array {
            $archive = $this->archiveRepo->findByIdentity($marketCode, $drawDate, $drawKey);

            if (! $archive) {
                return ['_empty' => true];
            }

            return ['data' => [
                'market_code' => $archive->market_code,
                'draw_date' => $archive->draw_date instanceof Carbon
                    ? $archive->draw_date->format('Y-m-d')
                    : $archive->draw_date,
                'draw_key' => $archive->draw_key,
                'result_set' => $archive->result_set,
            ]];
        });

        if (($payload['_empty'] ?? false) === true) {
            return response()->json(['message' => 'Result not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($payload);
    }

    /**
     * Check market existence. Disabled markets are still readable (historical record).
     */
    protected function marketExists(string $code): bool
    {
        return LotteryMarket::where('code', $code)->exists();
    }
}
