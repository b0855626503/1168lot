<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Services\LottoProfitLossForecastReportService;
use Illuminate\Http\Request;
use RuntimeException;

class LottoProfitLossForecastReportController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(Request $request)
    {
        return view($this->_config['view'], [
            'marketOptions' => $this->buildMarketOptions(),
            'initialFilters' => [
                'market_id' => $this->normalizePositiveInt($request->query('market_id')),
                'draw_id' => $this->normalizePositiveInt($request->query('draw_id')),
            ],
        ]);
    }

    public function loadDrawOptions(Request $request)
    {
        $marketId = $this->normalizePositiveInt($request->query('market_id'));

        if ($marketId === null) {
            return response()->json([
                'market_id' => null,
                'draws' => [],
            ]);
        }

        $draws = LottoDraw::query()
            ->where('market_id', $marketId)
            ->orderByDesc('draw_date')
            ->orderByDesc('id')
            ->get(['id', 'draw_date', 'status', 'close_at', 'result_at'])
            ->map(function (LottoDraw $draw): array {
                return [
                    'value' => (int) $draw->id,
                    'text' => sprintf(
                        '%s | %s',
                        optional($draw->draw_date)->format('d/m/Y') ?: '-',
                        $this->mapDrawStatusLabel((string) $draw->status)
                    ),
                    'draw_date' => optional($draw->draw_date)->format('Y-m-d'),
                    'draw_date_display' => optional($draw->draw_date)->format('d/m/Y'),
                    'status' => (string) $draw->status,
                    'status_label' => $this->mapDrawStatusLabel((string) $draw->status),
                    'close_at' => optional($draw->close_at)->format('Y-m-d H:i:s'),
                    'result_at' => optional($draw->result_at)->format('Y-m-d H:i:s'),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'market_id' => $marketId,
            'draws' => $draws,
        ]);
    }

    public function loadData(Request $request, LottoProfitLossForecastReportService $service)
    {
        $marketId = $this->normalizePositiveInt($request->query('market_id'));
        $drawId = $this->normalizePositiveInt($request->query('draw_id'));

        if ($marketId === null || $drawId === null) {
            return response()->json([
                'message' => 'กรุณาเลือกตลาดและงวดหวยก่อน',
            ], 422);
        }

        try {
            return response()->json($service->build($marketId, $drawId));
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    private function buildMarketOptions(): array
    {
        return LotteryMarket::query()
            ->with('group:id,name,sort')
            ->orderBy('group_id')
            ->orderBy('name')
            ->get(['id', 'group_id', 'name', 'logo', 'icon'])
            ->groupBy(static function (LotteryMarket $market): string {
                return (string) optional($market->group)->name ?: 'ไม่ระบุกลุ่ม';
            })
            ->map(static function ($markets, $groupName): array {
                return [
                    'label' => (string) $groupName,
                    'options' => $markets->map(static fn (LotteryMarket $market): array => [
                        'value' => (int) $market->id,
                        'text' => (string) $market->name,
                        'logo' => (string) ($market->logo ?: $market->icon ?: ''),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizePositiveInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $normalized === false ? null : (int) $normalized;
    }

    private function mapDrawStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'ฉบับร่าง',
            'open' => 'เปิดรับ',
            'closed' => 'ปิดรับแล้ว',
            'resulted' => 'ออกผลแล้ว',
            default => $status !== '' ? $status : '-',
        };
    }
}
