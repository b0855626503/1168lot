<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\BankTransformer;
use Gametech\Payment\Contracts\Bank;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class BankDataTable extends DataTable
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

        return $dataTable->rawColumns(['value', 'detail'])
            ->setTransformer(new BankTransformer);

    }


    /**
     * @param Bank $model
     * @return mixed
     */
    public function query(Bank $model)
    {
        return $model->newQuery()
            ->select('banks.*');


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

                'processing' => true,
                'serverSide' => true,
                'responsive' => false,
                'stateSave' => true,
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => false,
                'scrollX' => true,
                'order' => [[0, 'desc']],
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
            ['data' => 'code', 'name' => 'banks.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'filepic', 'name' => 'banks.filepic', 'title' => 'รูป', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'name_th', 'name' => 'banks.name_th', 'title' => 'ชื่อ (TH)', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'name_en', 'name' => 'banks.name_en', 'title' => 'ชื่อ (EN)', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'shortcode', 'name' => 'banks.shortcode', 'title' => 'ชื่อย่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'enable', 'name' => 'banks.enable', 'title' => 'เปิดใช้งาน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'show', 'name' => 'banks.show_regis', 'title' => 'แสดงหน้าสมัคร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
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
