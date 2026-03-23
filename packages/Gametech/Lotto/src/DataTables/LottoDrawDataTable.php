<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LottoDraw;
use Gametech\Lotto\Transformers\LottoDrawTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoDrawDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoDrawTransformer);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(LottoDraw $model)
    {
        return $model->newQuery()
            ->select('lotto_draws.*')
            ->with('market')
            ->orderByDesc('draw_date')
            ->orderByDesc('id');
    }

    /**
     * Optional method if you want to use html builder.
     */
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
                'order'       => [[2, 'desc']],
                'buttons'     => ['pageLength'],
                'columnDefs'  => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    /**
     * Get columns.
     */
    protected function getColumns()
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'width' => '50px'],
            ['data' => 'market_name', 'name' => 'market.name', 'title' => 'ตลาด'],
            ['data' => 'draw_date', 'name' => 'draw_date', 'title' => 'วันงวด'],
            ['data' => 'open_at', 'name' => 'open_at', 'title' => 'เปิดรับ'],
            ['data' => 'close_at', 'name' => 'close_at', 'title' => 'ปิดรับ'],
            ['data' => 'status', 'name' => 'status', 'title' => 'สถานะ'],
            ['data' => 'result_number', 'name' => 'result_number', 'title' => 'เลขที่ออก'],
            ['data' => 'action', 'name' => 'action', 'title' => 'ดำเนินการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '130px'],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'LottoDraw_' . date('YmdHis');
    }
}
