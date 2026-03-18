<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\PromotionTransformer;
use Gametech\Promotion\Contracts\Promotion;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class ConfigDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->setTransformer(new PromotionTransformer);

    }


    /**
     * @param Promotion $model
     * @return mixed
     */
    public function query(Promotion $model)
    {
        return $model->newQuery()
            ->select('configs.*');


    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return Builder
     */
    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'dom' => 'Bfrtip',

                'responsive' => false,
                'paging' => true,
                'searching' => true,
                'deferRender' => true,
                'serverSide' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => false,

                'order' => [[3, 'asc']],
                'buttons' => [
                    'pageLength'
                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap']
                ]
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            ['data' => 'code', 'name' => 'code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'name', 'name' => 'name_th', 'title' => 'โปรโมชั่น', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'id', 'name' => 'id', 'title' => 'รหัส', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'sort', 'name' => 'sort', 'title' => 'ลำดับ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'type', 'name' => 'length_type', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'pic', 'name' => 'filepic', 'title' => 'รูปภาพ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'manual', 'name' => 'use_manual', 'title' => 'Manual', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'auto', 'name' => 'use_auto', 'title' => 'Auto', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'wallet', 'name' => 'use_wallet', 'title' => 'Wallet', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'active', 'name' => 'active', 'title' => 'Display', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'enable', 'name' => 'enable', 'title' => 'Active', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'edit', 'name' => 'edit', 'title' => 'แก้ไข', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'bankin_datatable_' . time();
    }
}
