<?php

namespace Gametech\LineOA\DataTables;

use Gametech\LineOA\Transformers\TopupTransformer;
use Gametech\Payment\Contracts\BankPayment;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class TopupDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        $startdate = now()->toDateString().' 00:00:00';
        $enddate = now()->toDateString().' 23:59:59';

        return $dataTable->skipTotalRecords()
            ->setTransformer(new TopupTransformer);

    }

    /**
     * @return mixed
     */
    public function query(BankPayment $model)
    {
        $status = request()->input('status');

        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString().' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString().' 23:59:59';
        }

        return $model
            ->active()->income()
            ->with('member')
            ->with(['bank_account' => function ($model) {
                $model->with('bank');
            }])->withCasts([
                'date_update' => 'datetime:Y-m-d H:00',
                'bank_time' => 'datetime:Y-m-d H:00',
            ])
//            ->whereBetween('bank_payment.date_create', array($startdate, $enddate))
            ->select(['bank_payment.code', 'bank_payment.bank_time', 'bank_payment.date_create', 'bank_payment.value', 'bank_payment.bankstatus', 'bank_payment.enable', 'bank_payment.member_topup', 'bank_payment.autocheck', 'bank_payment.channel', 'bank_payment.remark_admin', 'bank_payment.detail', 'bank_payment.user_create', 'bank_payment.status', 'bank_payment.account_code', 'bank_payment.txid', 'bank_payment.autocheck', 'bank_payment.create_by']);
        //            ->when($startdate, function ($query, $startdate) use ($enddate) {
        //                $query->whereBetween('bank_payment.date_create', array($startdate, $enddate));
        //            });

    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): Builder
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax(route('admin.line_topup.index'))
            ->parameters([
                'dom' => 'Bfrtip',

                'processing' => true,
                'serverSide' => true,
                'responsive' => false,
                'stateSave' => true,
                'scrollX' => true,
                'paging' => false,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,

                'pageLength' => 50,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows'],
                ],
                'buttons' => [

                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-center text-nowrap'],
                ],
            ]);
    }

    /**
     * Get columns.
     */
    protected function getColumns(): array
    {
        return [
            ['data' => 'code', 'name' => 'bank_payment.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'bankcode', 'name' => 'bankcode', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'acc_no', 'name' => 'bank_account.acc_no', 'title' => 'เลขบัญชี', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'bank_time', 'name' => 'bank_payment.bank_time', 'title' => 'เวลาธนาคาร', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'channel', 'name' => 'bank_payment.channel', 'title' => 'ช่องทาง', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'detail', 'name' => 'bank_payment.detail', 'title' => 'รายละเอียด', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'value', 'name' => 'bank_payment.value', 'title' => 'จำนวนเงิน', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
            ['data' => 'user_name', 'name' => 'bank_payment.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'date', 'name' => 'bank_payment.date_update', 'title' => 'เวลาตรวจสอบ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'confirm', 'name' => 'confirm', 'title' => 'เติมเงิน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'cancel', 'name' => 'cancel', 'title' => 'ปฏิเสธ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'delete', 'name' => 'delete', 'title' => 'ลบ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'bankin_datatable_'.time();
    }
}
