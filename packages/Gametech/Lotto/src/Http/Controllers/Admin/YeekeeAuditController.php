<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Carbon\Carbon;
use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class YeekeeAuditController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(): mixed
    {
        $this->assertCanView();

        $markets = LotteryMarket::query()
            ->where('result_mode', 'yeekee')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (LotteryMarket $m): array => [
                'value' => (int) $m->id,
                'text' => (string) $m->name,
            ])
            ->values()
            ->all();

        return view($this->_config['view'], [
            'markets' => $markets,
            'canViewShoots' => bouncer()->hasPermission('lotto_settings.yeekee_audit.view_shoots'),
            'canViewSnapshot' => bouncer()->hasPermission('lotto_settings.yeekee_audit.view_snapshot'),
        ]);
    }

    public function loadRounds(Request $request): JsonResponse
    {
        $this->assertCanView();

        $marketId = $request->query('market_id') ? (int) $request->query('market_id') : null;
        $roundDate = $this->resolveDate($request->query('round_date'));

        $query = YeekeeRound::query()
            ->select([
                'id',
                'market_id',
                'lotto_draw_id',
                'round_date',
                'round_no',
                'status',
                'shoot_count',
                'last_shoot_position',
                'shoot_open_at',
                'shoot_close_at',
                'shoot_closed_at',
                'shoot_snapshot_hash',
            ])
            ->with('market:id,name')
            ->orderByDesc('round_date')
            ->orderByDesc('round_no');

        if ($marketId !== null) {
            $query->where('market_id', $marketId);
        }

        if ($roundDate !== null) {
            $query->whereDate('round_date', $roundDate);
        }

        $rounds = $query->limit(100)->get()->map(static fn (YeekeeRound $r): array => [
            'id' => (int) $r->id,
            'market_id' => (int) $r->market_id,
            'market_name' => (string) (optional($r->market)->name ?? ''),
            'lotto_draw_id' => (int) $r->lotto_draw_id,
            'round_date' => (string) ($r->round_date ?? ''),
            'round_no' => (int) $r->round_no,
            'status' => (string) $r->status,
            'shoot_count' => (int) $r->shoot_count,
            'last_shoot_position' => (int) $r->last_shoot_position,
            'shoot_open_at' => optional($r->shoot_open_at)->format('Y-m-d H:i:s'),
            'shoot_close_at' => optional($r->shoot_close_at)->format('Y-m-d H:i:s'),
            'shoot_closed_at' => optional($r->shoot_closed_at)->format('Y-m-d H:i:s'),
            'has_snapshot' => $r->shoot_snapshot_hash !== null,
        ]);

        return response()->json([
            'rounds' => $rounds->all(),
            'serverTime' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function loadShoots(Request $request, int $roundId): JsonResponse
    {
        $this->assertCanView();
        $this->assertCanViewShoots();

        $round = YeekeeRound::query()
            ->select(['id', 'market_id', 'round_date', 'round_no', 'shoot_count', 'last_shoot_position', 'status'])
            ->with('market:id,name')
            ->findOrFail($roundId);

        $shoots = YeekeeShoot::query()
            ->select(['id', 'yeekee_round_id', 'position', 'number_text', 'submitted_at', 'member_id'])
            ->where('yeekee_round_id', $roundId)
            ->orderBy('position')
            ->get()
            ->map(static fn (YeekeeShoot $s): array => [
                'id' => (int) $s->id,
                'position' => (int) $s->position,
                'number_text' => (string) $s->number_text,
                'member_id' => (int) $s->member_id,
                'submitted_at' => optional($s->submitted_at)->format('Y-m-d H:i:s'),
            ])
            ->all();

        return response()->json([
            'round' => [
                'id' => (int) $round->id,
                'market_name' => (string) (optional($round->market)->name ?? ''),
                'round_date' => (string) ($round->round_date ?? ''),
                'round_no' => (int) $round->round_no,
                'shoot_count' => (int) $round->shoot_count,
                'last_shoot_position' => (int) $round->last_shoot_position,
                'status' => (string) $round->status,
            ],
            'shoots' => $shoots,
            'serverTime' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function showSnapshot(int $roundId): JsonResponse
    {
        $this->assertCanView();
        $this->assertCanViewSnapshot();

        $round = YeekeeRound::query()
            ->select([
                'id',
                'market_id',
                'lotto_draw_id',
                'round_date',
                'round_no',
                'status',
                'shoot_count',
                'last_shoot_position',
                'shoot_snapshot_json',
                'shoot_snapshot_hash',
                'shoot_closed_at',
            ])
            ->with('market:id,name')
            ->findOrFail($roundId);

        if ($round->shoot_snapshot_json === null) {
            return response()->json([
                'has_snapshot' => false,
                'round_id' => $roundId,
                'serverTime' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }

        return response()->json([
            'has_snapshot' => true,
            'round' => [
                'id' => (int) $round->id,
                'market_name' => (string) (optional($round->market)->name ?? ''),
                'round_date' => (string) ($round->round_date ?? ''),
                'round_no' => (int) $round->round_no,
                'status' => (string) $round->status,
                'shoot_closed_at' => optional($round->shoot_closed_at)->format('Y-m-d H:i:s'),
            ],
            'snapshot' => $round->shoot_snapshot_json,
            'snapshot_hash' => (string) ($round->shoot_snapshot_hash ?? ''),
            'serverTime' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function assertCanView(): void
    {
        if (! bouncer()->hasPermission('lotto_settings.yeekee_audit.index')) {
            abort(403, 'Forbidden');
        }
    }

    private function assertCanViewShoots(): void
    {
        if (! bouncer()->hasPermission('lotto_settings.yeekee_audit.view_shoots')) {
            abort(403, 'Forbidden');
        }
    }

    private function assertCanViewSnapshot(): void
    {
        if (! bouncer()->hasPermission('lotto_settings.yeekee_audit.view_snapshot')) {
            abort(403, 'Forbidden');
        }
    }

    private function resolveDate(mixed $value): ?string
    {
        $date = trim((string) ($value ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date;
        }

        return null;
    }
}
