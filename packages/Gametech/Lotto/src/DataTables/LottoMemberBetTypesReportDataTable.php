<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Transformers\LottoMemberBetTypesReportTransformer;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoMemberBetTypesReportDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoMemberBetTypesReportTransformer);
    }

    public function query(LottoTicketItem $model)
    {
        $includeYeekeeRound = Schema::hasTable('yeekee_rounds');
        $yeekeeRoundSelect = $includeYeekeeRound
            ? ',
                (
                    select yeekee_rounds.round_no
                    from yeekee_rounds
                    where yeekee_rounds.lotto_draw_id = lotto_draws.id
                    order by yeekee_rounds.id desc
                    limit 1
                ) as yeekee_round_no'
            : '';

        $query = $model->newQuery()
            ->selectRaw('
                lotto_tickets.member_id as member_id,
                lotto_draws.market_id as market_id,
                lotto_ticket_items.bet_type as bet_type,
                members.user_name as member_user_name,
                members.name as member_name,
                lotto_markets.name as market_name,
                lotto_markets.logo as market_logo,
                lotto_markets.icon as market_icon,
                lotto_markets.result_mode as market_result_mode'.$yeekeeRoundSelect.',
                COUNT(DISTINCT lotto_tickets.id) as ticket_count,
                COALESCE(SUM(lotto_ticket_items.amount), 0) as total_bet_amount,
                COALESCE(SUM(lotto_ticket_items.win_amount), 0) as total_win_amount,
                COALESCE(SUM(lotto_ticket_items.win_amount), 0) - COALESCE(SUM(lotto_ticket_items.amount), 0) as net_result
            ')
            ->join('lotto_tickets', 'lotto_tickets.id', '=', 'lotto_ticket_items.ticket_id')
            ->join('lotto_draws', 'lotto_draws.id', '=', 'lotto_tickets.draw_id')
            ->join('lotto_markets', 'lotto_markets.id', '=', 'lotto_draws.market_id')
            ->leftJoin('members', 'members.code', '=', 'lotto_tickets.member_id')
            ->where('lotto_tickets.status', '!=', 'cancelled')
            ->groupBy(
                'lotto_tickets.member_id',
                'lotto_draws.market_id',
                'lotto_ticket_items.bet_type',
                'members.user_name',
                'members.name',
                'lotto_markets.name',
                'lotto_markets.logo',
                'lotto_markets.icon',
                'lotto_markets.result_mode'
            )
            ->orderBy('market_name')
            ->orderBy('member_user_name');

        if ($includeYeekeeRound) {
            $query->groupBy('lotto_draws.id');
        }

        if ($dateStart = trim((string) request('date_start'))) {
            $query->whereDate('lotto_draws.draw_date', '>=', $dateStart);
        }

        if ($dateStop = trim((string) request('date_stop'))) {
            $query->whereDate('lotto_draws.draw_date', '<=', $dateStop);
        }

        if ($marketId = (int) request('market_id')) {
            $query->where('lotto_draws.market_id', $marketId);
        } else {
            $query->where('lotto_markets.result_mode', $this->resolveMarketTypeFilter());
        }

        if ($betType = trim((string) request('bet_type'))) {
            $query->where('lotto_ticket_items.bet_type', $betType);
        }

        if ($memberKeyword = trim((string) request('member_keyword'))) {
            $query->where(function ($builder) use ($memberKeyword): void {
                $builder->where('members.user_name', 'like', '%'.$memberKeyword.'%')
                    ->orWhere('members.name', 'like', '%'.$memberKeyword.'%')
                    ->orWhere('lotto_tickets.member_id', 'like', '%'.$memberKeyword.'%');
            });
        }

        return $query;
    }

    private function resolveMarketTypeFilter(): string
    {
        $marketType = strtolower(trim((string) request('market_type', LotteryMarket::RESULT_MODE_NORMAL)));
        if (in_array($marketType, [LotteryMarket::RESULT_MODE_NORMAL, LotteryMarket::RESULT_MODE_YEEKEE], true)) {
            return $marketType;
        }

        return LotteryMarket::RESULT_MODE_NORMAL;
    }

    public function html(): Builder
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'dom' => 'Bfrtip',
                'processing' => true,
                'serverSide' => true,
                'responsive' => false,
                'stateSave' => true,
                'scrollX' => true,
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'order' => [[0, 'asc']],
                'buttons' => ['pageLength'],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'member_display', 'name' => 'member_user_name', 'title' => 'สมาชิก', 'className' => 'text-left'],
            ['data' => 'market_name', 'name' => 'market_name', 'title' => 'ตลาด', 'className' => 'text-left'],
            ['data' => 'bet_type', 'name' => 'bet_type', 'title' => 'ประเภท', 'className' => 'text-center'],
            ['data' => 'ticket_count', 'name' => 'ticket_count', 'title' => 'จำนวนโพย', 'className' => 'text-center'],
            ['data' => 'total_bet_amount', 'name' => 'total_bet_amount', 'title' => 'ยอดแทง', 'className' => 'text-right'],
            ['data' => 'net_result', 'name' => 'net_result', 'title' => 'ได้/เสีย', 'className' => 'text-right'],
        ];
    }
}
