<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\RpSetDiamondTransformer;
use Gametech\Member\Contracts\MemberCreditLog;
use Gametech\Member\Contracts\MemberDiamondLog;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpSetDiamondDataTable extends DataTable
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
            ->with('withdraw', function () use ($query) {
                return core()->currency((clone $query)->where('diamond_type', 'W')->sum('diamond_amount'));
            })
            ->with('deposit', function () use ($query) {
                return core()->currency((clone $query)->where('diamond_type', 'D')->sum('diamond_amount'));
            })
            ->setTransformer(new RpSetDiamondTransformer(request()->get('start')));

    }


    /**
     * @param MemberCreditLog $model
     * @return mixed
     */
    public function query(MemberDiamondLog $model)
    {

        $ip = request()->input('ip');
        $type = request()->input('credit_type');
        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model->newQuery()
            ->with('member', 'admin')
            ->active()
            ->select('members_diamondlog.*')->withCasts([
                'date_create' => 'datetime:Y-m-d H:00'
            ])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            })
            ->when($type, function ($query, $type) {
                $query->where('diamond_type', $type);
            })
            ->when($ip, function ($query, $ip) {
                $query->where('ip', 'like', "%" . $ip . "%");
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('members_diamondlog.member_code', function ($q) use ($user) {
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
                ],

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
            ['data' => 'code', 'name' => 'members_diamondlog.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_name', 'name' => 'members_credit_log.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'member.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'credit_type', 'name' => 'members_credit_log.credit_type', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'total', 'name' => 'members_credit_log.credit_amount', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'balance_before', 'name' => 'members_credit_log.credit_before', 'title' => 'Diamond (ก่อน)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'balance_after', 'name' => 'members_credit_log.credit_balance', 'title' => 'Diamond (หลัง)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'remark', 'name' => 'members_credit_log.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'emp_name', 'name' => 'members_credit_log.emp_name', 'title' => 'ผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'date_create', 'name' => 'members_credit_log.date_create', 'title' => 'วันที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'ip', 'name' => 'members_credit_log.ip', 'title' => 'ip', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
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
