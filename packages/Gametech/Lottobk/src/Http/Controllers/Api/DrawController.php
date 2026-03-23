<?php

namespace Gametech\Lotto\Http\Controllers\Api;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrawController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $limit = $this->resolveLimit($request);

        $draws = LottoDraw::query()
            ->with('market:id,name')
            ->whereIn('status', ['open', 'closed'])
            ->orderBy('draw_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $payload = $draws->map(fn (LottoDraw $draw): array => $this->mapDrawSummary($draw))->values();

        return $this->sendResponse($payload, 'ดึงรายการงวดสำเร็จ');
    }

    public function show(int $id): JsonResponse
    {
        $draw = LottoDraw::query()
            ->with(['market:id,name,group_id', 'betSettings'])
            ->find($id);

        if (! $draw) {
            return $this->sendError('ไม่พบงวดที่ระบุ', 404);
        }

        $payload = [
            'id' => (int) $draw->id,
            'market' => [
                'id' => (int) $draw->market_id,
                'name' => $draw->market?->name,
                'group_id' => (int) ($draw->market?->group_id ?? 0),
            ],
            'draw_date' => optional($draw->draw_date)->toDateString(),
            'open_at' => optional($draw->open_at)->toDateTimeString(),
            'close_at' => optional($draw->close_at)->toDateTimeString(),
            'status' => (string) $draw->status,
            'result_number' => $draw->result_number,
            'bet_settings' => $draw->betSettings->map(fn ($setting): array => $this->mapBetSetting($setting))->values(),
        ];

        return $this->sendResponse($payload, 'ดึงรายละเอียดงวดสำเร็จ');
    }

    private function resolveLimit(Request $request): int
    {
        return max(1, min((int) $request->input('limit', 20), 100));
    }

    private function mapDrawSummary(LottoDraw $draw): array
    {
        return [
            'id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'market_name' => $draw->market?->name,
            'draw_date' => optional($draw->draw_date)->toDateString(),
            'open_at' => optional($draw->open_at)->toDateTimeString(),
            'close_at' => optional($draw->close_at)->toDateTimeString(),
            'status' => (string) $draw->status,
        ];
    }

    private function mapBetSetting($setting): array
    {
        return [
            'bet_type' => (string) $setting->bet_type,
            'bet_type_label' => BetType::label((string) $setting->bet_type),
            'is_enabled' => (bool) $setting->is_enabled,
            'min_bet' => (float) $setting->min_bet,
            'max_bet' => (float) $setting->max_bet,
            'max_per_number' => (float) $setting->max_per_number,
        ];
    }
}

