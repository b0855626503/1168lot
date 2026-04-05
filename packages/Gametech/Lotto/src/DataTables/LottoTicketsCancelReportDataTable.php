<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Transformers\LottoTicketsCancelReportTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoTicketsCancelReportDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoTicketsCancelReportTransformer());
    }

    public function query(LottoTicket $model)
    {
        $query = $model->newQuery()
            ->selectRaw('
                lotto_tickets.*,
                members.user_name as member_user_name,
                members.name as member_name,
                lotto_draws.draw_date as draw_date,
                lotto_markets.name as market_name,
                lotto_markets.logo as market_logo,
                lotto_markets.icon as market_icon,
                cancel_admin.user_name as cancel_admin_user_name,
                cancel_member.user_name as cancel_member_user_name,
                cancel_member.name as cancel_member_name
            ')
            ->join('lotto_draws', 'lotto_draws.id', '=', 'lotto_tickets.draw_id')
            ->join('lotto_markets', 'lotto_markets.id', '=', 'lotto_draws.market_id')
            ->leftJoin('members', 'members.code', '=', 'lotto_tickets.member_id')
            ->leftJoin('employees as cancel_admin', 'cancel_admin.code', '=', 'lotto_tickets.cancelled_by')
            ->leftJoin('members as cancel_member', 'cancel_member.code', '=', 'lotto_tickets.cancelled_by')
            ->orderByRaw('COALESCE(lotto_tickets.cancelled_at, lotto_tickets.created_at) DESC')
            ->orderByDesc('lotto_tickets.id');

        if ($dateStart = trim((string) request('date_start'))) {
            $query->whereRaw('date(COALESCE(lotto_tickets.cancelled_at, lotto_tickets.created_at)) >= ?', [$dateStart]);
        }

        if ($dateStop = trim((string) request('date_stop'))) {
            $query->whereRaw('date(COALESCE(lotto_tickets.cancelled_at, lotto_tickets.created_at)) <= ?', [$dateStop]);
        }

        if ($marketId = (int) request('market_id')) {
            $query->where('lotto_draws.market_id', $marketId);
        }

        if ($status = trim((string) request('status'))) {
            $query->where('lotto_tickets.status', $status);
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
                'order' => [[0, 'desc']],
                'buttons' => ['pageLength'],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'event_at', 'name' => 'created_at', 'title' => 'เวลา', 'className' => 'text-center'],
            ['data' => 'id', 'name' => 'id', 'title' => 'เลขโพย', 'className' => 'text-center'],
            ['data' => 'member_display', 'name' => 'member_user_name', 'title' => 'สมาชิก', 'className' => 'text-left'],
            ['data' => 'market_name', 'name' => 'market_name', 'title' => 'รายการหวย', 'className' => 'text-left'],
            ['data' => 'draw_date', 'name' => 'draw_date', 'title' => 'วันงวด', 'className' => 'text-center'],
            ['data' => 'total_bet_amount', 'name' => 'total_bet_amount', 'title' => 'ยอดแทง', 'className' => 'text-right'],
            ['data' => 'status', 'name' => 'status', 'title' => 'สถานะ', 'className' => 'text-center'],
            ['data' => 'cancelled_by_name', 'name' => 'cancel_admin_user_name', 'title' => 'ผู้ยกเลิก', 'className' => 'text-left'],
        ];
    }
}
