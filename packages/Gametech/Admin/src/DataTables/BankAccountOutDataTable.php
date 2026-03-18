<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\BankAccountOutTransformer;
use Gametech\Payment\Contracts\BankAccount;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class BankAccountOutDataTable extends DataTable
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

        $prem = bouncer()->hasPermission('ats.bank_account_out.tel');

        return $dataTable
            ->setTransformer(new BankAccountOutTransformer($prem));

    }


    /**
     * @param BankAccount $model
     * @return mixed
     */
    public function query(BankAccount $model)
    {
        return $model->newQuery()->out()
            ->select('banks_account.*')->with('bank');


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
                    ['targets' => '_all', 'className' => 'text-nowrap'],
//                    [ 'targets' => 2 , 'render' => 'function(data){ return data.length > 100 ? data.substr(0,100)+"...." : data }'],
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
            ['data' => 'code', 'name' => 'banks_account.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'bank', 'name' => 'bank.name_th', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'name', 'name' => 'banks_account.acc_name', 'title' => 'ชื่อบัญชี', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'acc_no', 'name' => 'banks_account.acc_no', 'title' => 'เลขบัญชี', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'username', 'name' => 'banks_account.user_name', 'title' => 'Username', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'password', 'name' => 'banks_account.user_pass', 'title' => 'Password', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'balance', 'name' => 'banks_account.balance', 'title' => 'ยอดเงิน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//            ['data' => 'sort' , 'name' => 'banks_account.sort' , 'title' => 'ลำดับ' , 'orderable' => false , 'searchable' => false, 'className' => 'text-center text-nowrap' ],
            ['data' => 'auto', 'name' => 'banks_account.status_auto', 'title' => 'เปิดถอนอัตโนมัติ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'topup', 'name' => 'banks_account.status_topup', 'title' => 'เติมอัตโนมัติ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'display', 'name' => 'banks_account.display_wellet', 'title' => 'แสดงหน้าเวบ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],

            ['data' => 'enable', 'name' => 'enable', 'title' => 'เปิดใช้งาน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'user_update', 'name' => 'banks_account.user_update', 'title' => 'แก้ไขโดย', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
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
