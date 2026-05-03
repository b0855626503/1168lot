<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Carbon\Carbon;
use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Support\LottoMarketDisplayFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LottoResultsByDateReportController extends AppBaseController
{
    protected array $_config;
    private LottoMarketDisplayFormatter $marketDisplayFormatter;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
        $this->marketDisplayFormatter = new LottoMarketDisplayFormatter;
    }

    public function index(Request $request)
    {
        $payload = $this->buildPayload($request);

        return view($this->_config['view'], $payload);
    }

    public function loadData(Request $request)
    {
        $payload = $this->buildPayload($request);

        return response()->json($payload);
    }

    private function buildPayload(Request $request): array
    {
        $drawDate = $this->resolveDrawDate($request);

        $rowsQuery = LottoDraw::query()
            ->with([
                'market:id,group_id,name,logo,icon,is_enabled',
                'market.group:id,code,name,is_enabled,sort',
            ])
            ->select(['id', 'market_id', 'draw_date', 'result_at', 'status', 'result_number'])
            ->whereDate('draw_date', $drawDate)
            ->where('status', 'resulted')
            ->orderBy('market_id')
            ->orderByDesc('id');

        if (Schema::hasTable('yeekee_rounds')) {
            $rowsQuery->selectSub(
                YeekeeRound::query()
                    ->select('round_no')
                    ->whereColumn('yeekee_rounds.lotto_draw_id', 'lotto_draws.id')
                    ->orderByDesc('yeekee_rounds.id')
                    ->limit(1),
                'yeekee_round_no'
            );
        }

        $rows = $rowsQuery->get();

        $latestByMarket = $rows
            ->groupBy('market_id')
            ->map(static fn ($marketRows) => $marketRows->sortByDesc('id')->first())
            ->values();

        $groups = $latestByMarket
            ->filter(static fn ($draw) => $draw->market && $draw->market->group)
            ->groupBy(static fn ($draw) => (int) $draw->market->group->id)
            ->map(function ($groupRows) {
                $first = $groupRows->first();
                $group = $first->market->group;

                return [
                    'group_id' => (int) $group->id,
                    'group_code' => (string) ($group->code ?? ''),
                    'group_name' => (string) ($group->name ?? ''),
                    'sort' => (int) ($group->sort ?? 9999),
                    'markets' => collect($groupRows)
                        ->map(function ($draw) {
                            $resultNumber = is_array($draw->result_number) ? $draw->result_number : [];
                            $noResult = (bool) ($resultNumber['no_result'] ?? false)
                                || (string) ($resultNumber['status'] ?? '') === 'no_result';

                            return [
                                'market_id' => (int) $draw->market->id,
                                'market_name' => $this->marketDisplayFormatter->formatPlain(
                                    (string) ($draw->market->name ?? ''),
                                    (string) ($draw->market->result_mode ?? LotteryMarket::RESULT_MODE_NORMAL),
                                    isset($draw->yeekee_round_no) ? (int) $draw->yeekee_round_no : null
                                ),
                                'market_logo' => (string) ($draw->market->logo ?? ''),
                                'draw_id' => (int) $draw->id,
                                'draw_date' => optional($draw->draw_date)->format('Y-m-d'),
                                'result_at' => optional($draw->result_at)->format('Y-m-d H:i:s'),
                                'no_result' => $noResult,
                                'first_prize' => (string) ($resultNumber['first_prize'] ?? ''),
                                'top_3' => (string) ($resultNumber['top_3'] ?? ''),
                                'top_2' => (string) ($resultNumber['top_2'] ?? ''),
                                'bottom_2' => (string) ($resultNumber['bottom_2'] ?? ($resultNumber['last_2_digits'] ?? '')),
                            ];
                        })
                        ->sortBy('market_name')
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('sort')
            ->values();

        return [
            'drawDate' => $drawDate,
            'groups' => $groups->values()->all(),
            'summary' => [
                'group_count' => $groups->count(),
                'market_count' => $groups->sum(static fn (array $group): int => count($group['markets'])),
                'result_count' => $latestByMarket->count(),
            ],
            'serverTime' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }

    private function resolveDrawDate(Request $request): string
    {
        $drawDate = trim((string) $request->query('draw_date', now()->toDateString()));
        $isValidDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $drawDate) === 1;

        if (! $isValidDate) {
            return now()->toDateString();
        }

        return $drawDate;
    }
}
