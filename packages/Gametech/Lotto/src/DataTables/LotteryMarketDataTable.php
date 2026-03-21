<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LotteryMarket;
use Gametech\Lotto\Transformers\LotteryMarketTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LotteryMarketDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LotteryMarketTransformer);
    }

    public function query(LotteryMarket $model)
    {
        return $model->newQuery()
            ->with('group')
            ->select('lotto_markets.*');
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
                'order'       => [[1, 'asc']],
                'buttons'     => ['pageLength'],
                'columnDefs'  => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'selector',   'name' => 'selector',    'title' => '<input type="checkbox" class="js-lotto-select-all-markets">', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '44px'],
            ['data' => 'id',         'name' => 'id',          'title' => '#',            'orderable' => true,  'searchable' => false, 'className' => 'text-center', 'width' => '60px'],
            ['data' => 'name',       'name' => 'name',        'title' => 'ชื่อรายการหวย', 'orderable' => true,  'searchable' => true,  'className' => 'text-left'],
            ['data' => 'group_name', 'name' => 'group_name',  'title' => 'กลุ่มหวย',      'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'code',       'name' => 'code',        'title' => 'Code',          'orderable' => true,  'searchable' => true,  'className' => 'text-center'],
            ['data' => 'is_enabled', 'name' => 'is_enabled',  'title' => 'สถานะ',         'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '100px'],
            ['data' => 'action',     'name' => 'action',      'title' => 'จัดการ',        'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '80px'],
        ];
    }
}

