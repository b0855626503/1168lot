<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LotteryGroup;
use Gametech\Lotto\Transformers\LotteryGroupTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LotteryGroupDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LotteryGroupTransformer);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(LotteryGroup $model)
    {
        return $model->newQuery()
            ->select('lotto_groups.*')
            ->orderBy('sort')
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
                'order'       => [[4, 'asc']],
                'buttons'     => ['pageLength'],
                'columnDefs'  => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    /**
     * Get columns.
     */
    protected function getColumns(): array
    {
        return [
            ['data' => 'selector',   'name' => 'selector',   'title' => '<input type="checkbox" class="js-lotto-select-all-groups">', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '44px'],
            ['data' => 'id',         'name' => 'id',         'title' => '#',        'orderable' => true,  'searchable' => false, 'className' => 'text-center', 'width' => '60px'],
            ['data' => 'name',       'name' => 'name',       'title' => 'ชื่อกลุ่ม', 'orderable' => true,  'searchable' => true,  'className' => 'text-left'],
            ['data' => 'code',       'name' => 'code',       'title' => 'Code',      'orderable' => true,  'searchable' => true,  'className' => 'text-center'],
            ['data' => 'sort',       'name' => 'sort',       'title' => 'Sort',      'orderable' => true,  'searchable' => false, 'className' => 'text-center', 'width' => '80px'],
            ['data' => 'is_enabled', 'name' => 'is_enabled', 'title' => 'สถานะ',    'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '100px'],
            ['data' => 'action',     'name' => 'action',     'title' => 'จัดการ',   'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '80px'],
        ];
    }
}

