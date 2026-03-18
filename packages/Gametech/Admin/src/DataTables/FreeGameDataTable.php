<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\FreeGameTransformer;
use Gametech\Admin\Transformers\GameTransformer;
use Gametech\Game\Contracts\Game;
use Gametech\Game\Contracts\FreeGame;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class FreeGameDataTable extends DataTable
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
            ->setTransformer(new FreeGameTransformer);

    }


    /**
     * @param Game $model
     * @return mixed
     */
    public function query(FreeGame $model)
    {
      

        return $model->newQuery();


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
            ['data' => 'code', 'name' => 'freegames.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'member_user', 'name' => 'freegames.member_user', 'title' => 'Username', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_create', 'name' => 'freegames.date_create', 'title' => 'สร้างเมื่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'expired_date', 'name' => 'freegames.expired_date', 'title' => 'หมดอายุ (ชม)', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'demo', 'name' => 'freegames.name', 'title' => 'ID Test', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'free_game_name', 'name' => 'freegames.free_game_name', 'title' => 'ชื่อฟรีเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'bet_amount', 'name' => 'freegames.bet_amount', 'title' => 'ยอด Bet', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'user_demofree' , 'name' => 'freegames.name' , 'title' => 'User Demo Free' , 'orderable' => false , 'searchable' => true , 'className' => 'text-left text-nowrap' ],
//            ['data' => 'sort' , 'name' => 'freegames.sort' , 'title' => 'ลำดับ' , 'orderable' => false , 'searchable' => true , 'className' => 'text-center text-nowrap' ],
            ['data' => 'game_count', 'name' => 'freegames.game_count', 'title' => 'จำนวนเกมฟรี', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'product_id', 'name' => 'freegames.product_id', 'title' => 'ค่ายเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'game_name', 'name' => 'freegames.game_name', 'title' => 'ชื่อเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'emp_user', 'name' => 'freegames.emp_user', 'title' => 'ผูู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'gamelist', 'name' => 'freegames.gamelist', 'title' => 'แสดงรายการเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//
            ['data' => 'status', 'name' => 'freegames.status', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],

//            ['data' => 'auto_open', 'name' => 'freegames.auto_open', 'title' => 'เปิดบัญชีอัตโนมัติ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'status_open', 'name' => 'freegames.status_open', 'title' => 'แสดงผล', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//              ['data' => 'newuser', 'name' => 'freegames.newuser', 'title' => 'สมัครได้', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'ip' , 'name' => 'freegames.ip' , 'title' => 'ip' , 'orderable' => false , 'searchable' => false, 'className' => 'text-center text-nowrap' , 'width' => '3%' ],
//            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
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
