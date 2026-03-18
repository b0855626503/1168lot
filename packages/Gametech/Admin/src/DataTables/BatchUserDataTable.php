<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\BatchUserTransformer;
use Gametech\Core\Contracts\BatchUser;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class BatchUserDataTable extends DataTable
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
//            ->filter(function ($query) {
//                if (request()->has('game_code')) {
//                    $query->where('game_code', request('game_code'));
//                }
//
//                if (request()->has('freecredit')) {
//                    $query->where('freecredit', request('freecredit'));
//                }
//            })
//            ->startsWithSearch()
            ->setTransformer(new BatchUserTransformer);

    }


    /**
     * @param BatchUser $model
     * @return mixed
     */
    public function query(BatchUser $model)
    {
        $game = request()->input('game_code');
        $type = request()->input('freecredit');

        return $model->newQuery()
            ->active()
            ->select('batch_user.*')->with(['game' => function ($model) {
                $model->where('batch_game', 'Y');
            }])
            ->when($game, function ($query, $game) {
                $query->where('game_code', $game);
            })
            ->when($type, function ($query, $type) {
                $query->where('freecredit', $type);
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
            ['data' => 'code', 'name' => 'batch_user.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'game', 'name' => 'batch_user.game_code', 'title' => 'เกม', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'prefix', 'name' => 'batch_user.name', 'title' => 'Prefix', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'type', 'name' => 'batch_user.game_type', 'title' => 'Free Credit', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'start', 'name' => 'batch_user.sort', 'title' => 'เริ่ม', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
            ['data' => 'stop', 'name' => 'batch_user.batch_game', 'title' => 'สิ้นสุด', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'remain', 'name' => 'batch_user.auto_open', 'title' => 'คงเหลือ', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
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
