<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpDepositTransformer;
use Gametech\Payment\Contracts\BankPayment;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpPaymentDataTable extends DataTable
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
            ->with('deposit', function () use ($query) {
                return core()->currency((clone $query)->sum('value'));
            })
            ->setTransformer(new RpDepositTransformer);

    }


    /**
     * @param BankPayment $model
     * @return mixed
     */
    public function query(BankPayment $model)
    {

        $ip = request()->input('ip');
        $user = request()->input('user_name');
        $channel = request()->input('channel');
        $bankname = request()->input('bankname');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }




        return $model->newQuery()
            ->with('member', 'admin')->with(['bank_account' => function ($query) {
                $query->with('bank');
            }])->withCasts([
                'bank_time' => 'datetime:Y-m-d H:i:s',
                'date_approve' => 'datetime:Y-m-d H:i:s'
            ])
            ->active()->income()
            ->select('bank_payment.*')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            })
            ->when($ip, function ($query, $ip) {
                $query->where('ip_admin', 'like', "%" . $ip . "%");
            })
            ->when($channel, function ($query, $channel) {
                $query->where('channel',$channel);
            })
            ->when($bankname, function ($query, $bankname) {
                $query->where('account_code',$bankname);
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('bank_payment.member_topup', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
                });
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
                    ['targets' => '_all', 'className' => 'text-center text-nowrap']
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
            ['data' => 'code', 'name' => 'bank_payment.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'bank', 'name' => 'withdraws.member_name', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'acc_no', 'name' => 'bank_payment.account_code', 'title' => 'เลขบัญชี', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'date', 'name' => 'withdraws.member_name', 'title' => 'เวลาธนาคาร', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'channel', 'name' => 'withdraws.member_name', 'title' => 'ช่องทาง', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'detail', 'name' => 'withdraws.member_name', 'title' => 'รายละเอียด', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'member_name', 'name' => 'withdraws.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'member.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'amount', 'name' => 'withdraws.user_name', 'title' => 'จำนวนเงิน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'emp_name', 'name' => 'withdraws.emp_name', 'title' => 'ผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_create', 'name' => 'withdraws.emp_name', 'title' => 'วัน/เวลา (Add)', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_approve', 'name' => 'withdraws.emp_name', 'title' => 'วัน/เวลา (เติม)', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'remark', 'name' => 'withdraws.emp_name', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'ip', 'name' => 'bank_payment.ip', 'title' => 'ip', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
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
