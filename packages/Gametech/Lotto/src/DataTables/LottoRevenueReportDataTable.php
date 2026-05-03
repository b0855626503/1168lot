<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LottoDraw;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Transformers\LottoRevenueReportTransformer;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoRevenueReportDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoRevenueReportTransformer);
    }

    public function query(LottoDraw $model)
    {
        $ticketQuery = LottoTicket::query()
            ->selectRaw('COALESCE(SUM(total_amount), 0)')
            ->whereColumn('draw_id', 'lotto_draws.id')
            ->where('status', '!=', 'cancelled');

        $winQuery = LottoTicketItem::query()
            ->join('lotto_tickets', 'lotto_tickets.id', '=', 'lotto_ticket_items.ticket_id')
            ->selectRaw('COALESCE(SUM(lotto_ticket_items.win_amount), 0)')
            ->whereColumn('lotto_tickets.draw_id', 'lotto_draws.id')
            ->where('lotto_tickets.status', '!=', 'cancelled');

        $countQuery = LottoTicket::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('draw_id', 'lotto_draws.id')
            ->where('status', '!=', 'cancelled');

        $winningCountQuery = LottoTicket::query()
            ->join('lotto_ticket_items', 'lotto_ticket_items.ticket_id', '=', 'lotto_tickets.id')
            ->selectRaw('COUNT(DISTINCT lotto_tickets.id)')
            ->whereColumn('lotto_tickets.draw_id', 'lotto_draws.id')
            ->where('lotto_tickets.status', '!=', 'cancelled')
            ->where('lotto_ticket_items.result_status', 'win');

        $query = $model->newQuery()
            ->select('lotto_draws.*')
            ->with('market')
            ->selectSub($ticketQuery, 'total_bet_amount')
            ->selectSub($winQuery, 'total_win_amount')
            ->selectSub($countQuery, 'ticket_count')
            ->selectSub($winningCountQuery, 'winning_ticket_count')
            ->when($this->resolveMarketTypeFilter() !== 'all', function ($query): void {
                $query->whereHas('market', function ($builder): void {
                    $builder->where('result_mode', $this->resolveMarketTypeFilter());
                });
            })
            ->orderByDesc('draw_date')
            ->orderByDesc('id');

        if (Schema::hasTable('yeekee_rounds')) {
            $query->selectSub(
                YeekeeRound::query()
                    ->select('round_no')
                    ->whereColumn('yeekee_rounds.lotto_draw_id', 'lotto_draws.id')
                    ->orderByDesc('yeekee_rounds.id')
                    ->limit(1),
                'yeekee_round_no'
            );
        }

        return $query;
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
                'order' => [[1, 'desc']],
                'buttons' => ['pageLength'],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'market_name', 'name' => 'market.name', 'title' => 'ตลาด', 'className' => 'text-left'],
            ['data' => 'draw_date', 'name' => 'draw_date', 'title' => 'วันงวด', 'className' => 'text-center'],
            ['data' => 'status', 'name' => 'status', 'title' => 'สถานะ', 'className' => 'text-center'],
            ['data' => 'ticket_count', 'name' => 'ticket_count', 'title' => 'จำนวนโพย', 'className' => 'text-center'],
            ['data' => 'winning_ticket_count', 'name' => 'winning_ticket_count', 'title' => 'โพยที่ถูก', 'className' => 'text-center'],
            ['data' => 'total_bet_amount', 'name' => 'total_bet_amount', 'title' => 'ยอดแทงรวม', 'className' => 'text-right'],
            ['data' => 'total_win_amount', 'name' => 'total_win_amount', 'title' => 'ยอดจ่ายรวม', 'className' => 'text-right'],
            ['data' => 'net_revenue', 'name' => 'net_revenue', 'title' => 'รายได้สุทธิ', 'className' => 'text-right'],
        ];
    }

    private function resolveMarketTypeFilter(): string
    {
        $marketType = strtolower(trim((string) request('market_type', 'all')));
        if (in_array($marketType, [LotteryMarket::RESULT_MODE_NORMAL, LotteryMarket::RESULT_MODE_YEEKEE], true)) {
            return $marketType;
        }

        return 'all';
    }
}
