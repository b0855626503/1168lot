<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpWithdrawSeamlessTransformer;
use Gametech\Admin\Transformers\RpWithdrawTransformer;
use Gametech\Payment\Contracts\Withdraw;
use Gametech\Payment\Contracts\WithdrawSeamless;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpWithdrawSeamlessDataTable extends DataTable
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
            ->with('withdraw_yes', function () use ($query) {
                return core()->currency((clone $query)->where('status', 1)->sum('amount'));
            })
            ->with('withdraw_no', function () use ($query) {
                return core()->currency((clone $query)->where('status', 2)->sum('amount'));
            })
            ->setTransformer(new RpWithdrawSeamlessTransformer);

    }


    /**
     * @param Withdraw $model
     * @return mixed
     */
    public function query(WithdrawSeamless $model)
    {
        $sourceTable = $this->sourceTable();
        $ip = request()->input('ip');
        $status = request()->input('status');
        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        $wd = request()->input('status_withdraw');
        $remark_admin = request()->input('remark_admin');
        $bankm_code = request()->input('bankm_code');
        $account_code = request()->input('account_code');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model->newQuery()->from($sourceTable . ' as withdraws_seamless')->with(['member', 'admin', 'bank','bank_tran'])
            ->active()->where('status', '>', 0)
            ->select('withdraws_seamless.*')->withCasts([
                'date_create' => 'datetime:Y-m-d H:00'
            ])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
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
            ->when($user, function ($query, $user) {
                $query->whereIn('withdraws_seamless.member_code', function ($q) use ($user) {
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
            ['data' => 'code', 'name' => 'withdraws_seamless.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_update', 'name' => 'withdraws_seamless.date_approve', 'title' => 'วันเวลาที่ดำเนินการ', 'orderable' => true, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'date_approve', 'name' => 'withdraws.date_approve', 'title' => 'วันเวลาที่ดำเนินการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'bank', 'name' => 'withdraws_seamless.member_name', 'title' => 'ธนาคารลูกค้า', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//             ['data' => 'time', 'name' => 'withdraws.member_name', 'title' => 'เวลาที่แจ้ง', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'status_withdraw', 'name' => 'withdraws_seamless.status_withdraw', 'title' => 'ถอนผ่าน Payment', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'account_code', 'name' => 'withdraws_seamless.account_code', 'title' => 'บัญชีที่ใช้ดำเนินการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'txid', 'name' => 'withdraws_seamless.txid', 'title' => 'เลขรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'date', 'name' => 'withdraws_seamless.member_name', 'title' => 'วันเวลาที่แจ้ง', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],

            ['data' => 'member_name', 'name' => 'withdraws_seamless.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'withdraws_seamless.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'game_user', 'name' => 'withdraws.user_name', 'title' => 'Game ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'balance', 'name' => 'withdraws.balance', 'title' => 'ยอดแจ้งถอน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'amount', 'name' => 'withdraws_seamless.user_name', 'title' => 'ยอดถอน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'remark', 'name' => 'withdraws_seamless.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'status', 'name' => 'withdraws_seamless.status', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'emp_name', 'name' => 'withdraws_seamless.emp_name', 'title' => 'ผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'ip', 'name' => 'withdraws_seamless.ip', 'title' => 'ip', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],

//            ['data' => 'code', 'name' => 'withdraws_seamless.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'bank', 'name' => 'withdraws_seamless.member_name', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'date', 'name' => 'withdraws_seamless.member_name', 'title' => 'วันที่แจ้ง', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'time', 'name' => 'withdraws_seamless.member_name', 'title' => 'เวลาที่แจ้ง', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'member_name', 'name' => 'withdraws_seamless.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'user_name', 'name' => 'withdraws_seamless.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'status_withdraw', 'name' => 'withdraws_seamless.status_withdraw', 'title' => 'ถอนออโต้', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//            ['data' => 'amount', 'name' => 'withdraws_seamless.user_name', 'title' => 'จำนวนเงิน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//            ['data' => 'remark', 'name' => 'withdraws_seamless.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'account_code', 'name' => 'withdraws_seamless.account_code', 'title' => 'โอนเงินจาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'ip', 'name' => 'withdraws_seamless.ip', 'title' => 'ip', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'status', 'name' => 'withdraws_seamless.status', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'date_approve', 'name' => 'withdraws_seamless.date_approve', 'title' => 'วันเวลาที่ดำเนินการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'emp_name', 'name' => 'withdraws_seamless.emp_name', 'title' => 'ผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],

//            ['data' => 'emp_approve' , 'name' => 'withdraws.e' , 'title' => 'ผู้ดำเนินการ' , 'orderable' => false , 'searchable' => false, 'className' => 'text-left text-nowrap' ],

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

    private function sourceTable(): string
    {
        $config = core()->getConfigData();

        return ($config->seamless ?? 'N') === 'Y'
            ? 'withdraws'
            : 'withdraws_seamless';
    }
}
