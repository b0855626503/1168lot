<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Repositories\ArchiveRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class LottoResultArchiveController extends Controller
{
    protected ArchiveRepository $archiveRepo;

    public function __construct(?ArchiveRepository $archiveRepo = null)
    {
        $this->archiveRepo = $archiveRepo ?? new ArchiveRepository;
    }

    /**
     * GET /api/v1/lotto/results?date=YYYY-MM-DD or ?from_date=&to_date=
     * Paginated results for all markets on a single date or date range.
     */
    public function allMarkets(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'date' => 'nullable|date_format:Y-m-d|prohibits:from_date,to_date',
            'from_date' => 'nullable|date_format:Y-m-d|required_with:to_date',
            'to_date' => 'nullable|date_format:Y-m-d|required_with:from_date',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $perPage = (int) $request->query('per_page', 20);
        $page = (int) $request->query('page', 1);

        $singleDate = $request->query('date');
        $rawFrom = $request->query('from_date');
        $rawTo = $request->query('to_date');

        if ($singleDate) {
            if (! $this->validDrawDate($singleDate)) {
                return response()->json(['message' => 'date must be valid YYYY-MM-DD'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $fromDate = Carbon::createFromFormat('!Y-m-d', $singleDate);
            $toDate = $fromDate->copy();
        } elseif ($rawFrom && $rawTo) {
            $fromDate = Carbon::createFromFormat('!Y-m-d', (string) $rawFrom);
            $toDate = Carbon::createFromFormat('!Y-m-d', (string) $rawTo);

            if ($fromDate->gt($toDate)) {
                return response()->json([
                    'message' => 'from_date must be before or equal to to_date',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($fromDate->diffInDays($toDate) > 366) {
                return response()->json([
                    'message' => 'Date range must not exceed 366 days',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } else {
            $fromDate = now()->subDays(365)->startOfDay();
            $toDate = now()->startOfDay();
        }

        $fromDateStr = $fromDate->format('Y-m-d');
        $toDateStr = $toDate->format('Y-m-d');

        $queryFingerprint = md5("all|{$fromDateStr}|{$toDateStr}|{$page}|{$perPage}");
        $cacheKey = "lotto:archive:all:v1:list:{$queryFingerprint}";

        $payload = Cache::remember($cacheKey, 120, function () use (
            $fromDateStr, $toDateStr, $perPage
        ): array {
            $paginator = $this->archiveRepo->findDistinctDrawDatesAll(
                $fromDateStr, $toDateStr, $perPage,
            );

            $drawDates = collect($paginator->items())->pluck('draw_date')->map(
                fn ($d) => $d instanceof Carbon ? $d->format('Y-m-d') : $d,
            )->filter()->values()->all();

            $rows = ! empty($drawDates)
                ? $this->archiveRepo->findAllByDrawDates($drawDates)
                : collect();

            $grouped = $rows->groupBy('market_code')->map(
                fn ($marketRows, $marketCode) => [
                    'market_code' => $marketCode,
                    'draw_dates' => $marketRows->groupBy(fn ($r) => $r->draw_date instanceof Carbon
                        ? $r->draw_date->format('Y-m-d')
                        : $r->draw_date,
                    )->map(fn ($dateRows, $drawDate) => [
                        'draw_date' => $drawDate,
                        'results' => $dateRows->pluck('result_set', 'draw_key')->all(),
                    ])->values()->all(),
                ]
            )->values();

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
     * GET /api/v1/lotto/results/{marketCode}
     * Paginated results for a market, grouped by draw_date.
     */
    public function index(Request $request, string $marketCode): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawFrom = $request->query('from_date');
        $rawTo = $request->query('to_date');
        $perPage = (int) $request->query('per_page', 20);
        $page = (int) $request->query('page', 1);

        if (! $this->marketExists($marketCode)) {
            return response()->json(['message' => 'Market not found'], Response::HTTP_NOT_FOUND);
        }

        if (($rawFrom && ! $rawTo) || (! $rawFrom && $rawTo)) {
            return response()->json([
                'message' => 'Both from_date and to_date must be provided, or neither',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($rawFrom && $rawTo) {
            $fromDate = Carbon::createFromFormat('!Y-m-d', (string) $rawFrom);
            $toDate = Carbon::createFromFormat('!Y-m-d', (string) $rawTo);

            if ($fromDate->gt($toDate)) {
                return response()->json([
                    'message' => 'from_date must be before or equal to to_date',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($fromDate->diffInDays($toDate) > 366) {
                return response()->json([
                    'message' => 'Date range must not exceed 366 days',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } else {
            $fromDate = now()->subDays(365)->startOfDay();
            $toDate = now()->startOfDay();
        }

        $fromDateStr = $fromDate->format('Y-m-d');
        $toDateStr = $toDate->format('Y-m-d');

        $queryFingerprint = md5("{$fromDateStr}|{$toDateStr}|{$page}|{$perPage}");
        $version = Cache::get("lotto:archive:{$marketCode}:version", 1);
        $cacheKey = "lotto:archive:{$marketCode}:v{$version}:list:{$queryFingerprint}";

        $payload = Cache::remember($cacheKey, 120, function () use (
            $marketCode, $fromDateStr, $toDateStr, $perPage
        ): array {
            $paginator = $this->archiveRepo->findDistinctDrawDates(
                $marketCode, $fromDateStr, $toDateStr, $perPage,
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
        if (! $this->validDrawDate($drawDate)) {
            return response()->json(['message' => 'drawDate must be valid YYYY-MM-DD'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

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
        if (! $this->validDrawDate($drawDate)) {
            return response()->json(['message' => 'drawDate must be valid YYYY-MM-DD'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

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

    /**
     * Strict date validation with round-trip check: parsed->format('Y-m-d') === raw.
     *
     * date_format:Y-m-d rejects invalid dates like 2026-99-99, 2026-02-31, abc, 2026-1-1.
     * The round-trip check catches Carbon coercions (e.g. 2026-02-30 → 2026-03-02).
     */
    protected function validDrawDate(string $raw): bool
    {
        $validator = Validator::make(['drawDate' => $raw], [
            'drawDate' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return false;
        }

        $parsed = Carbon::createFromFormat('!Y-m-d', $raw);

        return $parsed && $parsed->format('Y-m-d') === $raw;
    }
}
