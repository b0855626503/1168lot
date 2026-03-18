<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpLogIcTransformer;
use Gametech\Member\Contracts\MemberCashback;
use Gametech\Member\Contracts\MemberCreditLog;
use Gametech\Member\Contracts\MemberFreeCredit;
use Gametech\Member\Contracts\MemberIc;
use Gametech\Payment\Contracts\WithdrawFree;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpLogIcDataTable extends DataTable
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
            ->with('sum', function () use ($query) {
                return core()->currency((clone $query)->sum('members_ic.cashback'));
            })
            ->setTransformer(new RpLogIcTransformer);

    }


    /**
     * @param WithdrawFree $model
     * @return mixed
     */
    public function query_(MemberCreditLog $model)
    {

        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model
            ->with(['member', 'admin'])
            ->active()->where('members_credit_log.kind', 'IC')
            ->select(['members_credit_log.code', 'members_credit_log.member_code', 'members_credit_log.credit_type', 'members_credit_log.amount', 'members_credit_log.credit_before', 'members_credit_log.credit_balance', 'members_credit_log.remark', 'members_credit_log.emp_code', 'members_credit_log.ip', 'members_credit_log.date_create', 'members_credit_log.kind', 'members_credit_log.user_create'])
            ->withCasts([
                'date_create' => 'datetime:Y-m-d H:00'
            ])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('members_credit_log.date_create', [$startdate, $enddate]);
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('members_credit_log.member_code', function ($q) use ($user) {
                    $q->select('code')
                        ->from(function ($sub) use ($user) {
                            $sub->select('members.code')
                                ->from('members')
                                ->where('members.user_name', $user)
                                ->union(
                                    \DB::table('games_user')
                                        ->select('games_user.member_code')
                                        ->where('games_user.user_name', $user)
                                );
                        }, 'combined');
                });
            });


    }

    public function query(MemberIc $model)
    {

        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');

        if (empty($startdate)) {
            $startdate = now()->toDateString();
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString();
        }

        return $model
            ->with(['me','down'])
            ->withCasts([
                'date_create' => 'datetime:Y-m-d H:00',
                'date_cashback' => 'datetime:Y-m-d',
            ])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('members_ic.date_create', array($startdate, $enddate));
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('members_ic.member_code', function ($q) use ($user) {
                    $q->select('code')
                        ->from(function ($sub) use ($user) {
                            $sub->select('members.code')
                                ->from('members')
                                ->where('members.user_name', $user)
                                ->union(
                                    \DB::table('games_user')
                                        ->select('games_user.member_code')
                                        ->where('games_user.user_name', $user)
                                );
                        }, 'combined');
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
                'autoWidth' => true,
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
    protected function getColumns_()
    {
        return [
            ['data' => 'code', 'name' => 'members_credit_log.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_name', 'name' => 'members_credit_log.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'members_credit_log.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'credit_type', 'name' => 'members_credit_log.credit_type', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'credit_amount', 'name' => 'members_credit_log.credit_amount', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//            ['data' => 'credit_before', 'name' => 'members_credit_log.credit_before', 'title' => 'Credit (ก่อน)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//            ['data' => 'credit_balance', 'name' => 'members_credit_log.credit_balance', 'title' => 'Credit (หลัง)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'remark', 'name' => 'members_credit_log.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'emp_name', 'name' => 'members_credit_log.emp_name', 'title' => 'ผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'date_create', 'name' => 'members_credit_log.date_create', 'title' => 'วันที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'ip', 'name' => 'members_credit_log.ip', 'title' => 'ip', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
        ];
    }

    protected function getColumns()
    {
        return [
            ['data' => 'date_create', 'name' => 'members_ic.date_create', 'title' => 'วันที่คำนวน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_cashback', 'name' => 'members_ic.date_cashback', 'title' => 'คำนวน รอบวันที่', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_name', 'name' => 'members_ic.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'members_ic.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'game_user', 'name' => 'members_ic.user_name', 'title' => 'Game ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'downline_name', 'name' => 'members_ic.member_name', 'title' => 'ได้รับจาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'downline_name_user_name', 'name' => 'members_ic.user_name', 'title' => 'Downline User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'downline_name_game_user', 'name' => 'members_ic.user_name', 'title' => 'Downline Game ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'turn_over', 'name' => 'members_ic.turn_over', 'title' => 'Turn', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'winlose', 'name' => 'members_ic.winlose', 'title' => 'Winlose', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'cashback', 'name' => 'members_ic.Cashback', 'title' => 'Cashback', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],

//            ['data' => 'credit_before', 'name' => 'members_cashback.credit_before', 'title' => 'Credit (ก่อน)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//            ['data' => 'credit_balance', 'name' => 'members_cashback.credit_balance', 'title' => 'Credit (หลัง)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//            ['data' => 'startdate', 'name' => 'members_cashback.remark', 'title' => 'วันที่ (เริ่ม)', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'enddate', 'name' => 'members_cashback.remark', 'title' => 'วันที่ (สิ้นสุด)', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'emp_name', 'name' => 'members_cashback.emp_name', 'title' => 'ผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],


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
