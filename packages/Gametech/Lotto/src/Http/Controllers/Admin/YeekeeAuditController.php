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

    public function loadRounds(Request $request): JsonResponse
    {
        $this->assertCanView();

        $marketId = $request->query('market_id') ? (int) $request->query('market_id') : null;
        $roundDate = $this->resolveDate($request->query('round_date'));

        $lottoDrawId = null;
        if ($request->has('lotto_draw_id')) {
            $lottoDrawId = (int) $request->query('lotto_draw_id');
            if ($lottoDrawId <= 0) {
                return response()->json(['rounds' => [], 'serverTime' => Carbon::now()->format('Y-m-d H:i:s')]);
            }
        }

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
            ->with('market:id,name,result_mode')
            ->whereHas('market', static function ($query): void {
                $query->where('result_mode', LotteryMarket::RESULT_MODE_YEEKEE);
            })
            ->orderByDesc('round_date')
            ->orderByDesc('round_no');

        if ($marketId !== null) {
            $query->where('market_id', $marketId);
        }

        if ($roundDate !== null) {
            $query->whereDate('round_date', $roundDate);
        }

        if ($lottoDrawId !== null) {
            $query->where('lotto_draw_id', $lottoDrawId);
        }

        $rounds = $query->limit(200)->get()->map(static fn (YeekeeRound $r): array => [
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

    public function show(int $roundId): JsonResponse
    {
        $this->assertCanView();

        $isSensitive = bouncer()->hasPermission('lotto.yeekee.audit.view_sensitive');

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
                'shoot_open_at',
                'shoot_close_at',
                'shoot_closed_at',
                'shoot_snapshot_json',
                'shoot_snapshot_hash',
            ])
            ->with('market:id,name,result_mode')
            ->findOrFail($roundId);

        if (! $round->market || (string) ($round->market->result_mode ?? LotteryMarket::RESULT_MODE_NORMAL) !== LotteryMarket::RESULT_MODE_YEEKEE) {
            abort(404, 'Yeekee round not found');
        }

        $shootColumns = $isSensitive
            ? ['id', 'yeekee_round_id', 'position', 'number_text', 'member_id', 'submitted_at', 'ip_address', 'user_agent']
            : ['id', 'yeekee_round_id', 'position', 'number_text', 'submitted_at'];

        $shoots = YeekeeShoot::query()
            ->select($shootColumns)
            ->where('yeekee_round_id', $roundId)
            ->orderBy('position')
            ->get()
            ->map(function (YeekeeShoot $s) use ($isSensitive): array {
                $masked = $this->maskNumberText((string) $s->number_text);

                $entry = [
                    'id' => (int) $s->id,
                    'position' => (int) $s->position,
                    'number_text' => $isSensitive ? (string) $s->number_text : $masked,
                    'submitted_at' => optional($s->submitted_at)->format('Y-m-d H:i:s'),
                ];

                if ($isSensitive) {
                    $entry['member_id'] = (int) $s->member_id;
                    $entry['ip_address'] = (string) ($s->ip_address ?? '');
                    $entry['user_agent'] = (string) ($s->user_agent ?? '');
                }

                return $entry;
            })
            ->all();

        $payload = [
            'round' => [
                'id' => (int) $round->id,
                'lotto_draw_id' => (int) $round->lotto_draw_id,
                'market_id' => (int) $round->market_id,
                'market_name' => (string) (optional($round->market)->name ?? ''),
                'round_no' => (int) $round->round_no,
                'round_date' => (string) ($round->round_date ?? ''),
                'status' => (string) $round->status,
                'shoot_closed_at' => optional($round->shoot_closed_at)->format('Y-m-d H:i:s'),
                'shoot_snapshot_hash' => (string) ($round->shoot_snapshot_hash ?? ''),
                'shoot_count' => (int) $round->shoot_count,
                'last_shoot_position' => (int) $round->last_shoot_position,
            ],
            'shoots' => $shoots,
            'has_snapshot' => $round->shoot_snapshot_json !== null,
            '_sensitive_permission_required' => $isSensitive ? null : 'lotto.yeekee.audit.view_sensitive',
            'serverTime' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        if ($isSensitive && $round->shoot_snapshot_json !== null) {
            $payload['snapshot'] = $round->shoot_snapshot_json;
            $payload['snapshot_hash'] = (string) ($round->shoot_snapshot_hash ?? '');
        }

        return response()->json($payload);
    }

    private function assertCanView(): void
    {
        if (! bouncer()->hasPermission('lotto.yeekee.audit.view')) {
            abort(403, 'Forbidden');
        }
    }

    private function maskNumberText(string $text): string
    {
        if (preg_match('/^\d{5}$/', $text) !== 1) {
            return '*****';
        }

        return substr($text, 0, 3).'**';
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
