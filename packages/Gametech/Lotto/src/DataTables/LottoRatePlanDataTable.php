<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LottoRatePlan;
use Gametech\Lotto\Transformers\LottoRatePlanTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoRatePlanDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoRatePlanTransformer);
    }

    public function query(LottoRatePlan $model)
    {
        return $model->newQuery()
            ->with('group')
            ->select('lotto_rate_plans.*')
            ->orderBy('group_id')
            ->orderByDesc('id');
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
            ['data' => 'id', 'name' => 'id', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center', 'width' => '60px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'ชื่อแผน', 'orderable' => true, 'searchable' => true, 'className' => 'text-left'],
            ['data' => 'group_name', 'name' => 'group_name', 'title' => 'กลุ่มหวย', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'is_enabled', 'name' => 'is_enabled', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '120px'],
            ['data' => 'action', 'name' => 'action', 'title' => 'จัดการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '80px'],
        ];
    }
}

