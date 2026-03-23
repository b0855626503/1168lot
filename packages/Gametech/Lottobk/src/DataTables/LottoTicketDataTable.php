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

        return $dataTable->setTransformer(new LottoTicketTransformer());
    }

    public function query(LottoTicket $model)
    {
        return $model->newQuery()
            ->select('lotto_tickets.*')
            ->with(['member', 'draw.market'])
            ->withSum('items as total_win_amount', 'win_amount')
            ->withCount([
                'items as winning_items_count' => function ($query) {
                    $query->where('result_status', 'win');
                },
            ])
            ->orderByDesc('id');
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
                'searching'   => false,
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
            ['data' => 'total_amount', 'name' => 'total_amount', 'title' => 'ยอดแทง', 'className' => 'text-right'],
            ['data' => 'total_win_amount', 'name' => 'total_win_amount', 'title' => 'ยอดถูก', 'className' => 'text-right'],
            ['data' => 'status', 'name' => 'status', 'title' => 'สถานะ', 'className' => 'text-center'],
            ['data' => 'action', 'name' => 'action', 'title' => 'จัดการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '90px'],
        ];
    }
}

