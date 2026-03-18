<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpTopProTransformer;
use Gametech\Payment\Contracts\Bill;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpTopProDataTable extends DataTable
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
            ->with('count', function () use ($query) {
                return (clone $query)->count();
            })
            ->with('sum', function () use ($query) {
                return (clone $query)->sum('credit_bonus');
            })
//            ->with('p2', function () use ($query) {
//                return (clone $query)->where('pro_code', 2)->count();
//            })
//            ->with('p4', function () use ($query) {
//                return (clone $query)->where('pro_code', 4)->count();
//            })
//            ->with('p6', function () use ($query) {
//                return (clone $query)->where('pro_code', 6)->count();
//            })
            ->setTransformer(new RpTopProTransformer);

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


        $first = DB::table('payments_promotion')
            ->where('payments_promotion.enable', 'Y')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('payments_promotion.date_create', array($startdate, $enddate));
            })
            ->when($pro, function ($query, $pro) {
                $query->where('payments_promotion.pro_code', $pro);
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('payments_promotion.member_code', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
                });
            })
            ->select(['payments_promotion.code', 'payments_promotion.member_code', 'payments_promotion.pro_code', 'payments_promotion.date_create', 'payments_promotion.credit_bonus', 'payments_promotion.amount']);


        $union = $model->newQuery()
            ->where('bills.enable', 'Y')
            ->where('bills.transfer_type', 1)
            ->where('bills.pro_code', '>', 0)
            ->select(['bills.code', 'bills.member_code', 'bills.pro_code', 'bills.date_create','bills.credit_bonus','bills.amount'])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('bills.date_create', array($startdate, $enddate));
            })
            ->when($pro, function ($query, $pro) {
                $query->where('bills.pro_code', $pro);
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('bills.member_code', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
                });
            })
            ->union($first);

        return $union->orderByDesc('date_create');


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
                'ordering' => false,
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
            ['data' => 'code', 'name' => 'bills.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_create', 'name' => 'bills.date_create', 'title' => 'วันที่รับโปร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'pro_name', 'name' => 'bills.pro_name', 'title' => 'โปรโมชั่น', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'member_name', 'name' => 'bills.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'members.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'amount', 'name' => 'bills.amount', 'title' => 'ยอดเงิน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'bonus', 'name' => 'bills.bonus', 'title' => 'โบนัสที่ได้', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
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
