<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\RpCashbackTransformer;
use Gametech\Member\Contracts\Member;
use Gametech\Payment\Contracts\BankPayment;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpCashbackDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query): DataTableAbstract
    {
        $promotion = DB::table('promotions')->where('id', 'pro_cashback')->first();
        $bonus = $promotion->bonus_percent;

        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->with('deposit', function () use ($query) {
//                return core()->currency((clone $query)->sum(DB::raw('SUM(bank_payment.value)')));
                return core()->currency((clone $query)->sum('deposit_amount'));
//                return core()->currency((clone $query)->sum('bank_payment.value'));
            })
            ->with('withdraw', function () use ($query) {
                return core()->currency((clone $query)->sum('withdraw_amount'));
            })
            ->with('bonus', function () use ($query) {
                return core()->currency((clone $query)->sum('bonus_amount'));
            })
            ->with('totals', function () use ($query) {
                return core()->currency((clone $query)->sum(DB::raw('IF(bonus_amount > 0 , 0 ,IF((deposit_amount - withdraw_amount) > 0 , (deposit_amount - withdraw_amount) , 0))')));
//                return core()->currency((clone $query)->sum(DB::raw('IF((deposit_amount - withdraw_amount) > 0 , (deposit_amount - withdraw_amount) , 0)')));
//                return (clone $query)->select(DB::raw("SUM(bank_payment.amount) + SUM(withdraws.amount)"));
            })
            ->with('cashback', function () use ($query, $bonus) {
                return core()->currency((clone $query)->sum(DB::raw("IF(bonus_amount > 0 , 0 , IF((deposit_amount - withdraw_amount) > 0 , (deposit_amount - withdraw_amount) , 0)) * $bonus / 100")));
//                return core()->currency((clone $query)->sum(DB::raw("IF((deposit_amount - withdraw_amount) > 0 , (deposit_amount - withdraw_amount) , 0) * $bonus / 100")));
//                return(clone $query)->select(DB::raw("(SUM(bank_payment.amount) + SUM(withdraws.amount) * $bonus) / 100"));
            })
            ->setTransformer(new RpCashbackTransformer($bonus));

    }


    /**
     * @param BankPayment $model
     * @return mixed
     */
    public function query(Member $model)
    {

        $config = core()->getConfigData();

        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        if (empty($startdate)) {
            $startdate = now()->subDays(1)->toDateString();
        }

        $latestBi = DB::table('bills')
            ->select('bills.member_code', DB::raw('SUM(bills.credit_bonus)  as bonus_amount'), DB::raw("DATE_FORMAT(bills.date_create,'%Y-%m-%d') as date_approve"))
            ->where('bills.enable', 'Y')
            ->where('bills.transfer_type', 1)
            ->when($startdate, function ($query, $startdate) {
                $query->whereDate('bills.date_create', $startdate);
            })
            ->groupBy('bills.member_code', DB::raw('Date(bills.date_create)'));

        if($config->seamless == 'Y'){
            $latestWD = DB::table('withdraws_seamless','withdraws')
                ->select('withdraws.member_code', DB::raw('SUM(withdraws.amount)  as withdraw_amount'), DB::raw("DATE_FORMAT(withdraws.date_approve,'%Y-%m-%d') as date_approve"))
                ->where('withdraws.enable', 'Y')
                ->where('withdraws.status', 1)
                ->when($startdate, function ($query, $startdate) {
                    $query->whereDate('withdraws.date_approve', $startdate);

                })
                ->groupBy('withdraws.member_code', DB::raw('Date(withdraws.date_approve)'));

        }else{
            $latestWD = DB::table('withdraws')
                ->select('withdraws.member_code', DB::raw('SUM(withdraws.amount)  as withdraw_amount'), DB::raw("DATE_FORMAT(withdraws.date_approve,'%Y-%m-%d') as date_approve"))
                ->where('withdraws.enable', 'Y')
                ->where('withdraws.status', 1)
                ->when($startdate, function ($query, $startdate) {
                    $query->whereDate('withdraws.date_approve', $startdate);

                })
                ->groupBy('withdraws.member_code', DB::raw('Date(withdraws.date_approve)'));

        }


        $latestBP = DB::table('bank_payment')
            ->select(DB::raw('MAX(bank_payment.code) as code'), DB::raw('MAX(bank_payment.date_approve) as date_approve'), DB::raw('SUM(bank_payment.value) as deposit_amount'), DB::raw("DATE_FORMAT(bank_payment.date_approve,'%Y-%m-%d') as date_cashback"), 'bank_payment.member_topup')
            ->where('bank_payment.value', '>', 0)
            ->where('bank_payment.bankstatus', 1)
            ->where('bank_payment.enable', 'Y')
            ->where('bank_payment.status', 1)
            ->when($startdate, function ($query, $startdate) {
                $query->whereDate('bank_payment.date_approve', $startdate);
            })
            ->groupBy('bank_payment.member_topup', DB::raw('Date(bank_payment.date_approve)'));


        $cb =  $model->with(['member_cashback' => function ($model) use ($startdate) {
            $model->where('topupic', 'Y')->when($startdate, function ($query, $startdate) {
                $query->whereDate('members_cashback.date_cashback', $startdate);

            });
        }])
            ->select('members.upline_code', 'members.code as member_code', 'members.user_name as user_name', 'members.name as member_name', 'members.balance as balance', DB::raw('IFNULL(withdraw_amount,0) as withdraw_amount'), DB::raw('IFNULL(bonus_amount,0) as bonus_amount'), 'bank_payment.deposit_amount', 'bank_payment.date_cashback', 'bank_payment.date_approve', 'bank_payment.code')
//            ->with('member')
//            ->active()->complete()->income()->where('bankstatus', 1)
//            ->groupBy(DB::raw('Date(bank_payment.date_approve)'))
//            ->groupBy('bank_payment.member_topup')
//
            ->orderByDesc('bank_payment.code')
            ->withCasts([
                'date_approve' => 'datetime:Y-m-d H:00'
            ])
//            ->selectRaw([DB::raw('MAX(bank_payment.code) as code') , DB::raw('MAX(bank_payment.date_approve) as date_approve') , DB::raw('SUM(bank_payment.value) as deposit_amount') , DB::raw("DATE_FORMAT(bank_payment.date_approve,'%Y-%m-%d') as date_cashback")  , 'bank_payment.member_topup' , DB::raw('IFNULL(withdraws.amount,0) as withdraw_amount')] )
//
            ->when($user, function ($query, $user) {
                $query->where('members.user_name', $user);
            })
//
//            ->when($user, function ($query, $user) {
//                $query->whereIn('bank_payment.member_topup', function ($q) use ($user) {
//                    $q->from('members')->select('members.code')->where('members.user_name', $user);
//                });
//            })
//            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->joinSub($latestBP, 'bank_payment', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'members.code');
            })
            ->leftJoinSub($latestBi, 'bills', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'bills.member_code');
                $join->on(DB::raw('Date(bank_payment.date_approve)'), '=', 'bills.date_approve');

            })
            ->leftJoinSub($latestWD, 'withdraws', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'withdraws.member_code');
                $join->on(DB::raw('Date(bank_payment.date_approve)'), '=', 'withdraws.date_approve');
            });

        return $cb;
//        return Cache::remember('cashback', 1500, function () use ($cb) {
//            return $cb;
//        });

    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return Builder
     */
    public function html(): Builder
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
                ],
                'footerCallback' => "function (row, data, start, end, display) {
                           var api = this.api();

                           var intVal = function ( i ) {
                                return typeof i === 'string' ?
                                    i.replace(/[\$,]/g, '')*1 :
                                    typeof i === 'number' ?
                                        i : 0;
                            };

                           api.columns().every(function (i) {
                           if(i == 9){
                           var sum = this.data()
                                      .reduce(function(a, b) {
                                        var x = intVal(a) || 0;
                                        var y = intVal(b) || 0;
                                        return x + y;
                                      }, 0);

                                    var n = new Number(sum);
                                    var myObj = {
                                        style: 'decimal'
                                    };

                                $(this.footer()).html(n.toLocaleString(myObj));
                            }
                            });
                        }",
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        return [
            ['data' => 'code', 'name' => 'bank_payment.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_name', 'name' => 'bills.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'membernew.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'balance', 'name' => 'membernew.user_name', 'title' => 'Credit ปัจจุบัน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'date_approve', 'name' => 'bills.user_name', 'title' => 'วันที่ฝากล่าสุด', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'deposit_amount', 'name' => 'bills.credit', 'title' => 'ยอดฝาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'withdraw_amount', 'name' => 'bills.credit', 'title' => 'ยอดถอน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'balance', 'name' => 'membernew.user_name', 'title' => 'ยอดเงินปัจจุบัน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'bonus_amount', 'name' => 'bills.pro_name', 'title' => 'ยอดรับโบนัส', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'balance_amount', 'name' => 'bills.pro_name', 'title' => 'คิดเป็นยอดเสีย', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'cashback', 'name' => 'bills.credit_balance', 'title' => 'ได้รับ Cashback', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'status', 'name' => 'bills.credit_balance', 'title' => 'สถานะรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'action', 'name' => 'bills.credit_balance', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
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
