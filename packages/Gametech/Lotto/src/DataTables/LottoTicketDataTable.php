<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LottoTicket;
use Gametech\Lotto\Transformers\LottoTicketTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoTicketDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        $dataTable->filter(function ($query): void {
            $keyword = trim((string) request('search.value', ''));
            if ($keyword === '') {
                return;
            }

            $query->where(function ($builder) use ($keyword): void {
                $builder->where('lotto_tickets.id', 'like', '%' . $keyword . '%')
                    ->orWhere('lotto_tickets.member_id', 'like', '%' . $keyword . '%')
                    ->orWhereHas('member', function ($memberQuery) use ($keyword): void {
                        $memberQuery->where('user_name', 'like', '%' . $keyword . '%')
                            ->orWhere('name', 'like', '%' . $keyword . '%');
                    });
            });
        }, true);

        return $dataTable->setTransformer(new LottoTicketTransformer());
    }

    public function query(LottoTicket $model)
    {
        $query = $model->newQuery()
            ->select('lotto_tickets.*')
            ->with(['member', 'draw.market'])
            ->withCount([
                'items as winning_items_count' => function ($query) {
                    $query->where('result_status', 'win');
                },
            ])
            ->orderByDesc('id');

        if ($drawId = (int) request('draw_id')) {
            $query->where('lotto_tickets.draw_id', $drawId);
        }

        if ($marketId = (int) request('market_id')) {
            $query->whereHas('draw', function ($builder) use ($marketId): void {
                $builder->where('market_id', $marketId);
            });
        }

        return $query;
    }

    public function html(): Builder
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'dom'         => 'Bfrtip',
                'processing'  => true,
                'serverSide'  => true,
                'responsive'  => false,
                'stateSave'   => true,
                'scrollX'     => true,
                'paging'      => true,
                'searching'   => true,
                'searchDelay' => 350,
                'deferRender' => true,
                'retrieve'    => true,
                'ordering'    => true,
                'order'       => [[0, 'desc']],
                'buttons'     => ['pageLength'],
                'columnDefs'  => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => '#', 'className' => 'text-center', 'width' => '60px'],
            ['data' => 'member_code', 'name' => 'member_id', 'title' => 'สมาชิก', 'className' => 'text-center'],
            ['data' => 'draw', 'name' => 'draw.draw_date', 'title' => 'งวดหวย', 'className' => 'text-left'],
            ['data' => 'total_bet_amount', 'name' => 'total_bet_amount', 'title' => 'ยอดแทง', 'className' => 'text-right'],
            ['data' => 'total_discount_amount', 'name' => 'total_discount_amount', 'title' => 'ส่วนลด', 'className' => 'text-right'],
            ['data' => 'total_net_amount', 'name' => 'total_net_amount', 'title' => 'สุทธิ', 'className' => 'text-right'],
            ['data' => 'total_win_amount', 'name' => 'total_win_amount', 'title' => 'ยอดถูก', 'className' => 'text-right'],
            ['data' => 'status', 'name' => 'status', 'title' => 'สถานะ', 'className' => 'text-center'],
            ['data' => 'action', 'name' => 'action', 'title' => 'จัดการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '90px'],
        ];
    }
}
