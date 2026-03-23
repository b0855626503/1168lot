<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\MemberLottoSetting;
use Gametech\Lotto\Transformers\MemberLottoSettingTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class MemberLottoSettingDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new MemberLottoSettingTransformer);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(MemberLottoSetting $model)
    {
        return $model->newQuery()
            ->select('member_lotto_settings.*')
            ->with('member', 'ratePlan')
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
                'order'       => [[0, 'desc']],
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
            ['data' => 'member_code', 'name' => 'member.code', 'title' => 'รหัสสมาชิก'],
            ['data' => 'member_name', 'name' => 'member.name', 'title' => 'ชื่อสมาชิก'],
            ['data' => 'rate_plan_name', 'name' => 'ratePlan.name', 'title' => 'อัตราจ่าย'],
            ['data' => 'action', 'name' => 'action', 'title' => 'ดำเนินการ', 'orderable' => false, 'searchable' => false],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'MemberLottoSetting_' . date('YmdHis');
    }
}

