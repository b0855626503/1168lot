<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\GameSeamlessTransformer;
use Gametech\Admin\Transformers\GameTransformer;
use Gametech\Game\Contracts\Game;
use Gametech\Game\Contracts\GameSeamless;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class GameSeamlessDataTable extends DataTable
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
            ->setTransformer(new GameSeamlessTransformer);

    }


    /**
     * @param Game $model
     * @return mixed
     */
    public function query(GameSeamless $model)
    {
        $admin = auth()->guard('admin')->user()->superadmin === 'N';

        $game_type = request()->input('game_type');

        return $model->newQuery()
            ->when($admin, function ($query) {
                $query->where('enable', 'Y');
            })
            ->when($game_type, function ($query, $game_type) {
                $query->where('games_seamless.game_type', $game_type);
            })
            ->select('games_seamless.*');


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
            ->ajaxWithForm('', '#frmsearch')
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
            ['data' => 'code', 'name' => 'games_seamless.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'icon', 'name' => 'games_seamless.icon', 'title' => 'ภาพ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'filepic', 'name' => 'games_seamless.filepic', 'title' => 'ภาพ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'game_type', 'name' => 'games_seamless.game_type', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'name', 'name' => 'games_seamless.name', 'title' => 'ชื่อเกม', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'demo', 'name' => 'games.name', 'title' => 'ID Test', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'batch_game', 'name' => 'games.batch_game', 'title' => 'บัญชีเกมได้จาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'account', 'name' => 'games.name', 'title' => 'บัญชีคงเหลือ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'user_demofree' , 'name' => 'games.name' , 'title' => 'User Demo Free' , 'orderable' => false , 'searchable' => true , 'className' => 'text-left text-nowrap' ],
//            ['data' => 'sort' , 'name' => 'games.sort' , 'title' => 'ลำดับ' , 'orderable' => false , 'searchable' => true , 'className' => 'text-center text-nowrap' ],
//            ['data' => 'status', 'name' => 'games.batch_game', 'title' => 'สถานะเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],

//            ['data' => 'auto_open', 'name' => 'games.auto_open', 'title' => 'เปิดบัญชีอัตโนมัติ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'status_open', 'name' => 'games_seamless.status_open', 'title' => 'แสดงผล', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'enable' , 'name' => 'games.enable' , 'title' => 'เปิดใช้งาน' , 'orderable' => false , 'searchable' => false, 'className' => 'text-center text-nowrap' , 'width' => '3%' ],
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
