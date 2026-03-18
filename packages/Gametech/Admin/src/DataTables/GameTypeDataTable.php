<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\GameTypeTransformer;
use Gametech\Game\Contracts\GameType;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class GameTypeDataTable extends DataTable
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
            ->setTransformer(new GameTypeTransformer);

    }


    /**
     * @param Game $model
     * @return mixed
     */
    public function query(GameType $model)
    {
//        $admin = auth()->guard('admin')->user()->superadmin === 'N';

        return $model->newQuery()
            ->select('games_type.*');


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
            ['data' => 'code', 'name' => 'games_type.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'id', 'name' => 'games_type.id', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'status_open', 'name' => 'games_type.status_open', 'title' => 'แสดงผล', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
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
