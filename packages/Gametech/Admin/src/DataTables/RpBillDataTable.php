<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpBillTransformer;
use Gametech\Payment\Contracts\Bill;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpBillDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {
//        $promotion = Bill::query()->select('pro_code')->distinct()->get();

        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->with('in_all', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 1)->sum('amount'));
            })
            ->with('in_yes', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 1)->where('enable', 'Y')->sum('amount'));
            })
            ->with('in_no', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 1)->where('enable', 'N')->sum('amount'));
            })
            ->with('out_all', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 2)->sum('amount'));
            })
            ->with('out_yes', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 2)->where('enable', 'Y')->sum('amount'));
            })
            ->with('out_no', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 2)->where('enable', 'N')->sum('amount'));
            })
            ->with('request', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 2)->where('enable', 'Y')->sum('amount_request'));
            })
            ->with('limit', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 2)->where('enable', 'Y')->sum('amount_limit'));
            })
//            ->with('diff', function () use ($query) {
//                return core()->currency((clone $query)->where('transfer_type', 2)->where('enable', 'Y')->sum(DB::raw('amount_request - amount_limit')));
//            })
            ->with('bonus', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 1)->where('enable', 'Y')->sum('credit_bonus'));
            })
            ->with('p1', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 1)->where('enable', 'Y')->where('pro_code', 1)->sum('credit_bonus'));
            })
            ->with('p2', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 1)->where('enable', 'Y')->where('pro_code', 2)->sum('credit_bonus'));
            })
            ->with('p4', function () use ($query) {
                return core()->currency((clone $query)->where('transfer_type', 1)->where('enable', 'Y')->where('pro_code', 4)->sum('credit_bonus'));
            })
            ->setTransformer(new RpBillTransformer);

    }


    /**
     * @param Bill $model
     * @return mixed
     */
    public function query(Bill $model)
    {

        $ip = request()->input('ip');
        $game = request()->input('game_code');
        $type = request()->input('transfer_type');
        $pro = request()->input('pro_code');
        $enable = request()->input('enable');
        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');

        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

//        return $model->newQuery()
//            ->select('bills.*')->with(['member','game','promotion'])
//            ->with(['game_user'  => function ($model) use ($user) {
//                $model->when($user, function ($query, $user) {
//                    $query->where('games_user.user_name', '=', $user);
//                });
//            }])
//            ->withCasts([
//                'date_create' => 'datetime:Y-m-d H:00'
//            ])
//            ->when($startdate, function ($query, $startdate) use ($enddate) {
//                $query->whereBetween('date_create', array($startdate, $enddate));
//            });

        return $model->newQuery()
            ->with('game', 'promotion', 'game_user', 'member')
            ->select('bills.*')
            ->withCasts([
                'date_create' => 'datetime:Y-m-d H:00'
            ])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            })
            ->when($type, function ($query, $type) {
                $query->where('transfer_type', $type);
            })
            ->when($game, function ($query, $game) {
                $query->where('game_code', $game);
            })
            ->when($pro, function ($query, $pro) {
                $query->where('pro_code', $pro);
            })
            ->when($enable, function ($query, $enable) {
                $query->where('enable', $enable);
            })
            ->when($ip, function ($query, $ip) {
                $query->where('ip', 'like', "%" . $ip . "%");
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('bills.member_code', function ($q) use ($user) {
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
            ['data' => 'id', 'name' => 'bills.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_name', 'name' => 'bills.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'members.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'transfer_type', 'name' => 'bills.transfer_type', 'title' => 'การโอน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'game_user', 'name' => 'bills.game_user', 'title' => 'Game User', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'enable', 'name' => 'bills.enable', 'title' => 'สถานะการโยก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'amount_request', 'name' => 'bills.amount_request', 'title' => 'จำนวนที่แจ้ง', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'amount_limit', 'name' => 'bills.amount_limit', 'title' => 'ถูกลิมิตที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'credit', 'name' => 'bills.credit', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'pro_name', 'name' => 'bills.pro_name', 'title' => 'โปรโมชั่น', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'credit_bonus', 'name' => 'bills.credit_bonus', 'title' => 'โบนัส', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'credit_balance', 'name' => 'bills.credit_balance', 'title' => 'รวม', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'balance_before', 'name' => 'bills.balance_before', 'title' => 'Wallet (ก่อน)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'balance_after', 'name' => 'bills.balance_after', 'title' => 'Wallet (หลัง)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'credit_before', 'name' => 'bills.credit_before', 'title' => 'Game (ก่อน)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'credit_after', 'name' => 'bills.credit_after', 'title' => 'Game (หลัง)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'ip', 'name' => 'bills.remark', 'title' => 'IP', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_create', 'name' => 'bills.date_create', 'title' => 'วันที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
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
