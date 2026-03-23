<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Lotto\Http\Controllers\Api\BetController as LottoBetController;
use Gametech\Lotto\Http\Controllers\Api\DrawController as LottoDrawController;
use Gametech\Lotto\Http\Controllers\Api\TicketController as LottoTicketController;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LottoController extends BaseController
{
    public function draws(Request $request)
    {
        try {
            $language = $this->requestLanguage($request);
            $response = app(LottoDrawController::class)->index($request);

            return $this->localizeDrawsResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการงวดได้ในขณะนี้', 422);
        }
    }

    public function draw(Request $request, int $id)
    {
        try {
            $language = $this->requestLanguage($request);
            $response = app(LottoDrawController::class)->show($id);

            return $this->localizeDrawResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายละเอียดงวดได้ในขณะนี้', 422);
        }
    }

    public function bet(Request $request)
    {
        try {
            return app(LottoBetController::class)->store($request, app('Gametech\Lotto\Services\BetService'));
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถส่งโพยได้ในขณะนี้', 422);
        }
    }

    public function tickets(Request $request)
    {
        try {
            $language = $this->requestLanguage($request);
            $response = app(LottoTicketController::class)->index($request);

            return $this->localizeTicketsResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการโพยได้ในขณะนี้', 422);
        }
    }

    public function ticket(Request $request, int $id)
    {
        try {
            $language = $this->requestLanguage($request);
            $response = app(LottoTicketController::class)->show($request, $id);

            return $this->localizeTicketResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายละเอียดโพยได้ในขณะนี้', 422);
        }
    }

    public function cancel(Request $request, int $id)
    {
        try {
            $response = app(LottoTicketController::class)->cancel($request, $id);
            if ($response instanceof JsonResponse) {
                $payload = json_decode((string) $response->getContent(), true);
                $message = (string) ($payload['message'] ?? '');
                if (($payload['success'] ?? false) === false && Str::contains($message, 'No query results for model')) {
                    return $this->sendError('ไม่พบโพยที่ระบุ', 404);
                }
            }

            return $response;
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถยกเลิกโพยได้ในขณะนี้', 422);
        }
    }

    private function localizeDrawsResponse(JsonResponse $response, string $language): JsonResponse
    {
        $payload = $this->responsePayload($response);
        $rows = $payload['data'] ?? null;
        if (! is_array($rows)) {
            return $response;
        }

        $marketIds = collect($rows)
            ->pluck('market_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $marketMap = $this->marketMapByIds($marketIds);

        $payload['data'] = collect($rows)->map(function ($row) use ($language, $marketMap) {
            if (! is_array($row)) {
                return $row;
            }

            $marketId = (int) ($row['market_id'] ?? 0);
            if ($marketId > 0 && isset($marketMap[$marketId])) {
                $row['market_name'] = $this->localizedMarketName($marketMap[$marketId], $language);
                $row['market_logo'] = $marketMap[$marketId]['logo'];
                $row['market_icon'] = $marketMap[$marketId]['icon'];
                $row['group_name'] = $this->localizedGroupName($marketMap[$marketId], $language);
            }

            return $row;
        })->values()->all();

        $payload['language'] = $language;
        return response()->json($payload, $response->getStatusCode());
    }

    private function localizeDrawResponse(JsonResponse $response, string $language): JsonResponse
    {
        $payload = $this->responsePayload($response);
        $data = $payload['data'] ?? null;
        if (! is_array($data)) {
            return $response;
        }

        $marketId = (int) ($data['market']['id'] ?? 0);
        if ($marketId > 0) {
            $marketMap = $this->marketMapByIds([$marketId]);
            if (isset($marketMap[$marketId])) {
                $market = &$data['market'];
                $market['name'] = $this->localizedMarketName($marketMap[$marketId], $language);
                $market['logo'] = $marketMap[$marketId]['logo'];
                $market['icon'] = $marketMap[$marketId]['icon'];
                $market['group_name'] = $this->localizedGroupName($marketMap[$marketId], $language);
            }
        }

        $payload['data'] = $data;
        $payload['language'] = $language;

        return response()->json($payload, $response->getStatusCode());
    }

    private function localizeTicketsResponse(JsonResponse $response, string $language): JsonResponse
    {
        $payload = $this->responsePayload($response);
        $rows = $payload['data'] ?? null;
        if (! is_array($rows)) {
            return $response;
        }

        $drawIds = collect($rows)
            ->pluck('draw_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $marketMapByDraw = $this->marketMapByDrawIds($drawIds);

        $payload['data'] = collect($rows)->map(function ($row) use ($language, $marketMapByDraw) {
            if (! is_array($row)) {
                return $row;
            }

            $drawId = (int) ($row['draw_id'] ?? 0);
            if ($drawId > 0 && isset($marketMapByDraw[$drawId])) {
                $market = $marketMapByDraw[$drawId];
                $row['market_name'] = $this->localizedMarketName($market, $language);
                $row['market_logo'] = $market['logo'];
                $row['market_icon'] = $market['icon'];
                $row['group_name'] = $this->localizedGroupName($market, $language);
            }

            return $row;
        })->values()->all();

        $payload['language'] = $language;

        return response()->json($payload, $response->getStatusCode());
    }

    private function localizeTicketResponse(JsonResponse $response, string $language): JsonResponse
    {
        $payload = $this->responsePayload($response);
        $data = $payload['data'] ?? null;
        if (! is_array($data)) {
            return $response;
        }

        $drawId = (int) ($data['draw_id'] ?? 0);
        if ($drawId > 0) {
            $marketMapByDraw = $this->marketMapByDrawIds([$drawId]);
            if (isset($marketMapByDraw[$drawId])) {
                $market = $marketMapByDraw[$drawId];
                $data['market_name'] = $this->localizedMarketName($market, $language);
                $data['market_logo'] = $market['logo'];
                $data['market_icon'] = $market['icon'];
                $data['group_name'] = $this->localizedGroupName($market, $language);
            }
        }

        $payload['data'] = $data;
        $payload['language'] = $language;

        return response()->json($payload, $response->getStatusCode());
    }

    private function responsePayload(JsonResponse $response): array
    {
        $decoded = json_decode((string) $response->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int> $marketIds
     * @return array<int, array<string, mixed>>
     */
    private function marketMapByIds(array $marketIds): array
    {
        if (empty($marketIds)) {
            return [];
        }

        /** @var Collection<int, LotteryMarket> $markets */
        $markets = LotteryMarket::query()
            ->select([
                'id',
                'group_id',
                'name',
                'name_en',
                'name_kh',
                'name_laos',
                'logo',
                'icon',
            ])
            ->whereIn('id', $marketIds)
            ->get();

        $groupIds = $markets->pluck('group_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $groupMap = LotteryGroup::query()
            ->select(['id', 'name', 'name_en', 'name_kh', 'name_laos'])
            ->whereIn('id', $groupIds)
            ->get()
            ->mapWithKeys(static function (LotteryGroup $group): array {
                return [(int) $group->id => [
                    'name' => (string) $group->name,
                    'name_en' => (string) ($group->name_en ?? ''),
                    'name_kh' => (string) ($group->name_kh ?? ''),
                    'name_laos' => (string) ($group->name_laos ?? ''),
                ]];
            })
            ->all();

        return $markets->mapWithKeys(function (LotteryMarket $market) use ($groupMap): array {
            $group = $groupMap[(int) $market->group_id] ?? null;

            return [(int) $market->id => [
                'id' => (int) $market->id,
                'group_id' => (int) $market->group_id,
                'name' => (string) $market->name,
                'name_en' => (string) ($market->name_en ?? ''),
                'name_kh' => (string) ($market->name_kh ?? ''),
                'name_laos' => (string) ($market->name_laos ?? ''),
                'logo' => (string) ($market->logo ?? ''),
                'icon' => (string) ($market->icon ?? ''),
                'group_name' => (string) ($group['name'] ?? ''),
                'group_name_en' => (string) ($group['name_en'] ?? ''),
                'group_name_kh' => (string) ($group['name_kh'] ?? ''),
                'group_name_laos' => (string) ($group['name_laos'] ?? ''),
            ]];
        })->all();
    }

    /**
     * @param array<int> $drawIds
     * @return array<int, array<string, mixed>>
     */
    private function marketMapByDrawIds(array $drawIds): array
    {
        if (empty($drawIds)) {
            return [];
        }

        $draws = LottoDraw::query()
            ->select(['id', 'market_id'])
            ->whereIn('id', $drawIds)
            ->get();

        $marketMap = $this->marketMapByIds(
            $draws->pluck('market_id')
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all()
        );

        return $draws->mapWithKeys(function (LottoDraw $draw) use ($marketMap): array {
            $market = $marketMap[(int) $draw->market_id] ?? null;
            if (! is_array($market)) {
                return [];
            }

            return [(int) $draw->id => $market];
        })->all();
    }

    /**
     * @param array<string, mixed> $market
     */
    private function localizedMarketName(array $market, string $language): string
    {
        return $this->localizedNameByLanguage($market, $language, 'name');
    }

    /**
     * @param array<string, mixed> $market
     */
    private function localizedGroupName(array $market, string $language): string
    {
        return $this->localizedNameByLanguage($market, $language, 'group_name');
    }

    /**
     * @param array<string, mixed> $entity
     */
    private function localizedNameByLanguage(array $entity, string $language, string $baseField): string
    {
        $suffix = $this->languageSuffix($language);
        $preferredField = $suffix === '' ? $baseField : $baseField . '_' . $suffix;

        $preferred = trim((string) ($entity[$preferredField] ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        return trim((string) ($entity[$baseField] ?? ''));
    }

    private function languageSuffix(string $language): string
    {
        return match ($language) {
            'en' => 'en',
            'kh' => 'kh',
            'la' => 'laos',
            default => '',
        };
    }
}
