<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\GameListTransformer;

use Gametech\API\Models\GameList;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class GameListDataTable extends DataTable
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
            ->setTransformer(new GameListTransformer);

    }


    /**
     * @param Game $model
     * @return mixed
     */
    public function query(GameList $model)
    {
        $admin = auth()->guard('admin')->user()->superadmin === 'N';

        $game = request()->input('game');

        return $model->newQuery()
            ->when($game, function ($query, $game) {
                $query->where('product', $game);
            });


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
            ['data' => 'product', 'name' => 'product', 'title' => 'ค่ายเกม', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'name', 'name' => 'name', 'title' => 'ชื่อเกม', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'id', 'name' => 'games_type.id', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
//            ['data' => 'status_open', 'name' => 'games_type.status_open', 'title' => 'แสดงผล', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'enable' , 'name' => 'enable' , 'title' => 'เปิดใช้งาน' , 'orderable' => false , 'searchable' => false, 'className' => 'text-center text-nowrap' , 'width' => '3%' ],
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
