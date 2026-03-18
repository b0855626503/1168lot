<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\SlideTransformer;
use Gametech\Core\Contracts\Slide;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class SlideDataTable extends DataTable
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
            ->setTransformer(new SlideTransformer);

    }


    /**
     * @param Game $model
     * @return mixed
     */
    public function query(Slide $model)
    {
//        $admin = auth()->guard('admin')->user()->superadmin === 'N';

        return $model->newQuery()
            ->select('slides.*');


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
                'stateSave' => false,
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => false,
                'scrollX' => false,

                'order' => [[0, 'asc']],
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
            ['data' => 'code', 'name' => 'slides.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'filepic', 'name' => 'slides.filepic', 'title' => 'ภาพ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
//            ['data' => 'game_type', 'name' => 'slides.game_type', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
//            ['data' => 'name', 'name' => 'slides.name', 'title' => 'ชื่อเกม', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'demo', 'name' => 'slides.name', 'title' => 'ID Test', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'batch_game', 'name' => 'slides.batch_game', 'title' => 'บัญชีเกมได้จาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'account', 'name' => 'slides.name', 'title' => 'บัญชีคงเหลือ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'user_demofree' , 'name' => 'slides.name' , 'title' => 'User Demo Free' , 'orderable' => false , 'searchable' => true , 'className' => 'text-left text-nowrap' ],
            ['data' => 'sort' , 'name' => 'slides.sort' , 'title' => 'ลำดับ' , 'orderable' => false , 'searchable' => true , 'className' => 'text-center text-nowrap' ],
//            ['data' => 'newuser', 'name' => 'slides.newuser', 'title' => 'สมัครได้', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'cashback', 'name' => 'slides.cashback', 'title' => 'Cashback', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'autologin', 'name' => 'slides.autologin', 'title' => 'Login', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'gamelist', 'name' => 'slides.gamelist', 'title' => 'มีรายการเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//
//            ['data' => 'status', 'name' => 'slides.batch_game', 'title' => 'สถานะเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],

//            ['data' => 'auto_open', 'name' => 'slides.auto_open', 'title' => 'เปิดบัญชีอัตโนมัติ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'status_open', 'name' => 'slides.status_open', 'title' => 'แสดงผล', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//              ['data' => 'newuser', 'name' => 'slides.newuser', 'title' => 'สมัครได้', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'enable' , 'name' => 'slides.enable' , 'title' => 'เปิดใช้งาน' , 'orderable' => false , 'searchable' => false, 'className' => 'text-center text-nowrap' , 'width' => '3%' ],
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
