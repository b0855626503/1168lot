<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Services\LegacyArchiveResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class LottoResultArchiveLegacyController extends Controller
{
    protected LegacyArchiveResultService $legacyService;

    public function __construct(?LegacyArchiveResultService $legacyService = null)
    {
        $this->legacyService = $legacyService ?? new LegacyArchiveResultService;
    }

    /**
     * GET /api/v1/lotto/result-archive-legacy
     *
     * Legacy-compatible public archive result endpoint.
     * Result data comes from lotto_result_archive_legacy_results (API snapshot table).
     * Lookup to lotto_markets is allowed only for display name and yeekee exclusion.
     * An unknown type is NOT rejected — snapshot data is served if available (market_id is optional per BOA-262).
     * Forbidden: lotto_draws / settlement / wallet / tickets.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'type' => 'nullable|string|max:100',
            'date' => 'nullable|date_format:Y-m-d|prohibits:from_date,to_date',
            'from_date' => 'nullable|date_format:Y-m-d|required_with:to_date',
            'to_date' => 'nullable|date_format:Y-m-d|required_with:from_date',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $type = $request->query('type');
        $singleDate = $request->query('date');
        $rawFrom = $request->query('from_date');
        $rawTo = $request->query('to_date');
        $perPage = (int) $request->query('per_page', 100);
        $page = (int) $request->query('page', 1);

        if (! $singleDate && ! $rawFrom && ! $rawTo) {
            return response()->json([
                'message' => 'Provide either date or from_date+to_date',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($singleDate) {
            if (! $this->validDrawDate($singleDate)) {
                return response()->json(['message' => 'date must be valid YYYY-MM-DD'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $fromDate = Carbon::createFromFormat('!Y-m-d', $singleDate);
            $toDate = $fromDate->copy();
            $dateLabel = $singleDate;
        } else {
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

            $dateLabel = $rawFrom.'..'.$rawTo;
        }

        // Resolve nameTH: prefer lotto_markets display name; fall back to snapshot name_th, then type code.
        // Per BOA-262, market_id is optional — snapshot-only types are served without rejection.
        // Yeekee types are still excluded via service-level whereNotIn on getYeekeeCodes().
        $nameTH = 'ทั้งหมด';

        if ($type) {
            $market = LotteryMarket::where('code', $type)
                ->where('result_mode', '!=', LotteryMarket::RESULT_MODE_YEEKEE)
                ->first();

            $nameTH = $market ? ($market->name ?: $type) : $type;
        }

        $fromDateStr = $fromDate->format('Y-m-d');
        $toDateStr = $toDate->format('Y-m-d');

        // Cache: uses archive writer's version key for invalidation
        $queryFingerprint = md5(($type ?: 'all').'|'.$fromDateStr.'|'.$toDateStr.'|'.$page.'|'.$perPage);

        if ($type) {
            $version = Cache::get("lotto:archive:{$type}:version", 1);
            $cacheKey = "lotto:archive:legacy:{$type}:v{$version}:list:{$queryFingerprint}";
            $ttl = 120;
        } else {
            $cacheKey = "lotto:archive:legacy:all:list:{$queryFingerprint}";
            $ttl = 60;
        }

        $payload = Cache::remember($cacheKey, $ttl, function () use (
            $type, $fromDateStr, $toDateStr, $perPage, $page, $dateLabel, $nameTH
        ): array {
            $result = $this->legacyService->query(
                type: $type,
                fromDate: $fromDateStr,
                toDate: $toDateStr,
                perPage: $perPage,
                page: $page,
            );

            $errors = [];
            if (empty($result['results'])) {
                $errors[] = [
                    'code' => 'DRAW_DATE_NOT_FOUND',
                    'message' => 'Archive result not found.',
                ];
            }

            return [
                'type' => $type ?: 'all',
                'nameTH' => $nameTH,
                'date' => $dateLabel,
                'page' => $page,
                'count' => count($result['results']),
                'results' => $result['results'],
                'errors' => $errors,
            ];
        });

        return response()->json($payload);
    }

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
