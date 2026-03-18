<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\PGSlotTransformer;
use Gametech\Admin\Transformers\RpLogCashbackTransformer;
use Gametech\API\Models\PGSoft;
use Gametech\API\Models\PGSoftProxy;
use Gametech\Member\Contracts\MemberFreeCredit;
use Gametech\Payment\Contracts\WithdrawFree;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\EloquentDataTable;

use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;
use Pimlie\DataTables\MongodbDataTable;

class PGSlotDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {

        $dataTable = new MongodbDataTable($query);
//        dd($dataTable->toJson());
//        return $dataTable->setTotalRecords(10);
        return $dataTable->setTransformer(new PGSlotTransformer);;

    }

//    public function ajax()
//    {
//        return $this->datatables
//            ->eloquent($this->query())
//            ->make(true);
//    }


    /**
     * @param PGSoft $model
     * @return mixed
     */
    public function query()
    {

        $users = PGSoft::query();

        return $this->applyScopes($users);



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
                'scrollX' => true,
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => false,
                'pageLength' => 50,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows']
                ],
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
            ['data' => 'create_time', 'name' => 'create_time', 'title' => 'Date', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'method', 'name' => 'method', 'title' => 'Type', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'gameCode', 'name' => 'gameCode', 'title' => 'gameCode', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'player_name', 'name' => 'player_name', 'title' => 'Game User', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'transfer_amount', 'name' => 'transfer_amount', 'title' => 'Amount', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
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
