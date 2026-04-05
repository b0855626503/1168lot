<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpWithdrawTransformer;
use Gametech\Payment\Contracts\Withdraw;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpWithdrawDataTable extends DataTable
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
//
//                if (request()->input('status')) {
//                    $query->where('status', request('status'));
//                }
//
//                if ($user = request()->input('user_name')) {
//                    $query->whereIn('withdraws.member_code', function ($q)  use ($user){
//                        $q->from('members')->select('members.code')->where('members.user_name', $user);
//                    });
//                }
//
//                if (request()->input('ip')) {
//                    $query->where('ip', 'like', "%" . request('ip') . "%");
//                }
//            })
            ->with('withdraw_all', function () use ($query) {
                return core()->currency((clone $query)->sum('amount'));
            })
            ->with('withdraw_balance_all', function () use ($query) {
                return core()->currency((clone $query)->sum('balance'));
            })
            ->with('withdraw_balance', function () use ($query) {
                return core()->currency((clone $query)->where('status', 1)->sum('balance'));
            })
            ->with('withdraw_yes', function () use ($query) {
                return core()->currency((clone $query)->where('status', 1)->sum('amount'));
            })
            ->with('withdraw_no', function () use ($query) {
                return core()->currency((clone $query)->where('status', 2)->sum('amount'));
            })
            ->setTransformer(new RpWithdrawTransformer);

    }


    /**
     * @param Withdraw $model
     * @return mixed
     */
    public function query(Withdraw $model)
    {
        $ip = request()->input('ip');
        $status = request()->input('status');
        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        $wd = request()->input('status_withdraw');
        $remark_admin = request()->input('remark_admin');
        $bankm_code = request()->input('bankm_code');
        $pro = request()->input('pro_code');
        $account_code = request()->input('account_code');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model->newQuery()->with(['member', 'admin', 'bank','promotion'])
            ->active()->where('status', '>', 0)
            ->select('withdraws.*')->withCasts([
                'date_create' => 'datetime:Y-m-d H:00'
            ])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_update', array($startdate, $enddate));
            })
            ->when($wd, function ($query, $wd) {
                if($wd == 'N'){
                    $query->where('status_withdraw', 'W');
                }elseif($wd == 'Y'){
                    $query->whereIn('status_withdraw', ['A','C']);
                }

            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($bankm_code, function ($query, $bankm_code) {
                $query->where('bankm_code', $bankm_code);
            })
            ->when($account_code, function ($query, $account_code) {
                $query->where('account_code', $account_code);
            })
            ->when($ip, function ($query, $ip) {
                $query->where('ip', 'like', "%" . $ip . "%");
            })
            ->when($remark_admin, function ($query, $remark_admin) {
                $query->where('remark_admin', 'like', "%" . $remark_admin . "%");
            })
            ->when($pro !== null && $pro !== '', function ($q) use ($pro) {
                if ((string)$pro === '0') {
                    // ไม่รับโปร: ถ้าระบบคุณเก็บเป็น 0
                    $q->where('pro_code', 0);

                    // ถ้าบางตารางเก็บ "ไม่รับโปร" เป็น NULL ให้ใช้แบบนี้แทน:
                    // $q->where(function($w){
                    //     $w->whereNull('pro_id')->orWhere('pro_id', 0);
                    // });
                } elseif ((string)$pro === '1') {
                    // รับโปร
                    $q->where('pro_code', '>', 0);
                }
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('withdraws.member_code', function ($q) use ($user) {
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
            ['data' => 'code', 'name' => 'withdraws.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_update', 'name' => 'withdraws.date_approve', 'title' => 'วันเวลาที่ดำเนินการ', 'orderable' => true, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'date_approve', 'name' => 'withdraws.date_approve', 'title' => 'วันเวลาที่ดำเนินการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'bank', 'name' => 'withdraws.member_name', 'title' => 'ธนาคารลูกค้า', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//             ['data' => 'time', 'name' => 'withdraws.member_name', 'title' => 'เวลาที่แจ้ง', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'status_withdraw', 'name' => 'withdraws.status_withdraw', 'title' => 'ถอนผ่าน Payment', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'account_code', 'name' => 'withdraws.account_code', 'title' => 'บัญชีที่ใช้ดำเนินการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'txid', 'name' => 'withdraws.txid', 'title' => 'เลขรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'date', 'name' => 'withdraws.member_name', 'title' => 'วันเวลาที่แจ้ง', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],

            ['data' => 'member_name', 'name' => 'withdraws.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'withdraws.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'game_user', 'name' => 'withdraws.user_name', 'title' => 'Game ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'balance', 'name' => 'withdraws.balance', 'title' => 'ยอดแจ้งถอน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'amount_limit_rate', 'name' => 'withdraws.amount_limit_rate', 'title' => 'ยอดอั้นถอน', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],

            ['data' => 'amount', 'name' => 'withdraws.user_name', 'title' => 'ยอดถอน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'pro_name', 'name' => 'withdraws.pro_name', 'title' => 'โปรตอนถอน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'remark', 'name' => 'withdraws.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'status', 'name' => 'withdraws.status', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'emp_name', 'name' => 'withdraws.emp_name', 'title' => 'ผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'ip', 'name' => 'withdraws.ip', 'title' => 'ip', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],


//            ['data' => 'emp_approve' , 'name' => 'withdraws.e' , 'title' => 'ผู้ดำเนินการ' , 'orderable' => false , 'searchable' => false, 'className' => 'text-left text-nowrap' ],

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
