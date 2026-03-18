<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\GameUserFreeTransformer;
use Gametech\Game\Contracts\GameUserFree;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class GameUserFreeDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);


        return $dataTable
            ->setTransformer(new GameUserFreeTransformer);

    }


    public function query(GameUserFree $model): \Illuminate\Database\Eloquent\Builder
    {
        return $model->newQuery();

    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return Builder
     */
    public function html(): Builder
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'dom' => 'Bfrtip',

                'processing' => true,
                'serverSide' => true,
                'responsive' => true,
                'stateSave' => false,
                'scrollX' => false,
                'paging' => true,
                'searching' => true,
                'deferRender' => true,
                'retrieve' => false,
                'ordering' => true,

                'pageLength' => 50,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows']
                ],
                'buttons' => [

                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-center text-nowrap']
                ]
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        return [
            ['data' => 'code', 'name' => 'games_user_free.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'user_name', 'name' => 'games_user_free.user_name', 'title' => 'Username', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'turnpro', 'name' => 'games_user_free.turnpro', 'title' => 'เทรินโปร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'amount_balance', 'name' => 'games_user_free.amount_balance', 'title' => 'ยอดเทรินทั้งหมด', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'withdraw_limit_rate', 'name' => 'games_user_free.withdraw_limit_rate', 'title' => 'อัตราอั้นถอน (เท่า)', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'withdraw_limit_amount', 'name' => 'games_user_free.withdraw_limit_amount', 'title' => 'ยอดอั้นถอนทั้งหมด', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'bankin_datatable_' . time();
    }
}
