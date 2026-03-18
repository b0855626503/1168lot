<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpAllLogTransformer;
use Gametech\Member\Contracts\MemberCreditLog;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class RpAllLogDataTable extends DataTable
{
    public function dataTable($query)
    {
        //        $promotion = Bill::query()->select('pro_code')->distinct()->get();

        //        $keyword = request()->input('viewtype');

        //        dd($query);

        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->setTransformer(new RpAllLogTransformer(request()->get('start')));

    }

    public function query(MemberCreditLog $model)
    {

        $ip = request()->input('ip');
        $game = request()->input('game_code');
        $kind = request()->input('kind');
        $user = request()->input('user_name');
        $code = request()->input('member_code');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');

        if (empty($startdate)) {
            $startdate = now()->toDateString().' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString().' 23:59:59';
        }

        return $model->newQuery()->with(['member', 'admin', 'bank', 'game_user', 'promotion', 'game'])
            ->select('members_credit_log.*')->orderByDesc('code')
            ->when($kind, function ($query, $kind) {
                if ($kind === 'FREE') {
                    $query->whereIn('kind', ['TRANBONUS', 'TRANFT', 'TRANCB', 'TRANIC', 'G_BONUS']);
                } elseif ($kind === 'MONEY') {
                    $query->whereIn('kind', ['TOPUP', 'WITHDRAW', 'SETWALLET']);

                } else {
                    $query->where('kind', $kind);
                }

            })
            ->when($game, function ($query, $game) {
                $query->where('game_code', $game);
            })
            ->when($code, function ($query, $code) {
                $query->where('member_code', $code);
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('members_credit_log.member_code', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
                });
            })
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', [$startdate, $enddate]);
            });

        //        $first = DB::table('payments_promotion')
        //            ->when($startdate, function ($query, $startdate) use ($enddate) {
        //                $query->whereBetween('payments_promotion.date_create', array($startdate, $enddate));
        //            })
        //
        //            ->where('payments_promotion.pro_code',6)
        //            ->select([
        //                'code',
        //                DB::raw('"FASTSTART" as viewtype'),
        //                'member_code',
        //                'remark as detail',
        //                DB::raw('1 as transfer_type'),
        //                'amount',
        //                'credit_bonus',
        //                'credit_balance',
        //                'credit_before as balance_before',
        //                'credit_after as balance_after',
        //                DB::raw('0 as credit_before'),
        //                DB::raw('0 as credit_after'),
        //                'user_create',
        //                'date_create',
        //                'ip',
        //                'pro_code',
        //                DB::raw('0 as game_code'),
        //                'enable',
        //                DB::raw('0 as emp_code'),
        //                DB::raw('"" as bank_name'),
        //            ]);
        //
        //        $two = DB::table('withdraws')
        //            ->when($startdate, function ($query, $startdate) use ($enddate) {
        //                $query->whereBetween('withdraws.date_create', array($startdate, $enddate));
        //            })
        //
        //            ->where('withdraws.code','<>', 0)
        //            ->select([
        //                'withdraws.code',
        //                DB::raw('"WITHDRAW" as viewtype'),
        //                'withdraws.member_code',
        //                'withdraws.remark_admin as detail',
        //                'withdraws.status as transfer_type',
        //                'withdraws.amount',
        //                DB::raw('0 as credit_bonus'),
        //                'withdraws.amount as credit_balance',
        //                'withdraws.oldcredit as balance_before',
        //                'withdraws.aftercredit as balance_after',
        //                DB::raw('0 as credit_before'),
        //                DB::raw('0 as credit_after'),
        //                'withdraws.user_create',
        //                'withdraws.date_create',
        //                'withdraws.ip',
        //                DB::raw('0 as pro_code'),
        //                DB::raw('0 as game_code'),
        //                'withdraws.enable',
        //                DB::raw('0 as emp_code'),
        //                'banks.shortcode as bank_name'
        //            ])
        //            ->join('banks','banks.code','=','withdraws.bankm_code');
        //
        //        $three = DB::table('members_credit_log')
        //            ->when($startdate, function ($query, $startdate) use ($enddate) {
        //                $query->whereBetween('members_credit_log.date_create', array($startdate, $enddate));
        //            })
        //
        //            ->whereIn('kind' , ["MEMBER","ROLLBACK"])
        // //            ->whereIn('kind' , ["MEMBER","ROLLBACK","FASTSTART"])
        //            ->select([
        //                'code',
        //
        //                DB::raw('IF(kind = "MEMBER" , "CREDIT" , kind) as viewtype'),
        //                'member_code',
        //                'remark as detail',
        //                DB::raw('IF(credit_type = "D" , 1 , 2) as transfer_type'),
        //                'credit_amount as amount',
        //                DB::raw('0 as credit_bonus'),
        //                'credit_amount as credit_balance',
        //                'credit_before as balance_before',
        //                'credit_balance as balance_after',
        //                DB::raw('0 as credit_before'),
        //                DB::raw('0 as credit_after'),
        //                'user_create',
        //                'date_create',
        //                'ip',
        //                DB::raw('0 as pro_code'),
        //                DB::raw('0 as game_code'),
        //                'enable',
        //                DB::raw('0 as emp_code'),
        //                DB::raw('"" as bank_name'),
        //            ]);
        //
        //        $bankacc = DB::table('banks_account')
        //                    ->select(['banks_account.code','banks.shortcode as name'])
        //                    ->join('banks','banks.code','=','banks_account.banks');
        //
        //        $four = DB::table('bank_payment')
        //
        //            ->when($startdate, function ($query, $startdate) use ($enddate) {
        //                $query->whereBetween('bank_payment.date_create', array($startdate, $enddate));
        //            })
        //
        //            ->where('value' , '>' , 0)
        //            ->where('bankstatus' , 1)
        //            ->where('status' , 1)
        //            ->select([
        //                'bank_payment.code',
        //                DB::raw('"DEPOSIT" as viewtype'),
        //                'bank_payment.member_topup as member_code',
        //                DB::raw('"Deposit" as detail'),
        //                'bank_payment.status as transfer_type',
        //                'bank_payment.value as amount',
        //                'bank_payment.pro_amount as credit_bonus',
        //                DB::raw('(value + pro_amount) as credit_balance'),
        //                'bank_payment.before_credit as balance_before',
        //                'bank_payment.after_credit as balance_after',
        //                DB::raw('0 as credit_before'),
        //                DB::raw('0 as credit_after'),
        //                'bank_payment.user_create',
        //                'bank_payment.date_create',
        //                'bank_payment.ip_admin as ip',
        //                'bank_payment.pro_id as pro_code',
        //                DB::raw('0 as game_code'),
        //                'bank_payment.enable',
        //                'bank_payment.emp_topup as emp_code',
        //                'banks_acc.name as bank_name',
        //            ])
        //            ->joinSub($bankacc, 'banks_acc', function ($join) {
        //                $join->on('banks_acc.code', '=', 'bank_payment.account_code');
        //            });

        //        $union = $model->newQuery()
        //            ->select([
        //                'code',
        //                DB::raw('"BILL" as viewtype'),
        //                'member_code',
        //                DB::raw('IF(transfer_type = 1,"WALLETTO" , " TOWALLET") as detail'),
        //                'transfer_type',
        //                'amount',
        //                'credit_bonus',
        //                'credit_balance',
        //                'balance_before',
        //                'balance_after',
        //                'credit_before',
        //                'credit_after',
        //                'user_create',
        //                'date_create',
        //                'ip',
        //                'pro_code',
        //                'game_code',
        //                'enable',
        //                DB::raw('0 as emp_code'),
        //                DB::raw('"" as bank_name')
        //            ])
        //
        //            ->when($startdate, function ($query, $startdate) use ($enddate) {
        //                $query->whereBetween('bills.date_create', array($startdate, $enddate));
        //            })
        //
        //            ->unionAll($first)
        //            ->unionAll($two)
        //            ->unionAll($three)
        //            ->unionAll($four);

        //        $union = DB::table('bills')
        //            ->select([
        //                'code',
        //                DB::raw('"BILL" as viewtype'),
        //                'member_code',
        //                DB::raw('IF(transfer_type = 1,"WALLETTO" , " TOWALLET") as detail'),
        //                'transfer_type',
        //                'amount',
        //                'credit_bonus',
        //                'credit_balance',
        //                'balance_before',
        //                'balance_after',
        //                'credit_before',
        //                'credit_after',
        //                'user_create',
        //                'date_create',
        //                'ip',
        //                'pro_code',
        //                'game_code',
        //                'enable',
        //                DB::raw('0 as emp_code'),
        //                DB::raw('"" as bank_name')
        //            ])
        //
        //            ->when($startdate, function ($query, $startdate) use ($enddate) {
        //                $query->whereBetween('bills.date_create', array($startdate, $enddate));
        //            })
        //
        //            ->unionAll($first)
        //            ->unionAll($two)
        //            ->unionAll($three)
        //            ->unionAll($four)
        //            ->orderByDesc('date_create');

        //        $union = $union
        //            ->with(['game','member','game_user','emp','promotion'])
        //            ->orderByDesc('date_create');

        //        dd($union->toSql());

        //        return DB::table(DB::raw("({$union->toSql()}) as alllog"))->mergeBindings($union)->orderByDesc('date_create');

    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return Builder
     */
    public function html()
    {
        $config = core()->getConfigData();
        if ($config->seamless == 'Y') {

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
                        ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows'],
                    ],
                    'buttons' => [
                        'pageLength',
                    ],
                    'columnDefs' => [
                        ['targets' => '_all', 'className' => 'text-nowrap'],
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
                            if(i == 6 || i == 8 || i == 9 || i == 10 || i == 11){
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
                                    if(sum < 0){
                                        $(this.column()).css('background-color','red');
                                    }
                                $(this.footer()).html(n.toLocaleString(myObj));
                                }
                            });
                        }",
                ]);

        } else {

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
                        ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows'],
                    ],
                    'buttons' => [
                        'pageLength',
                    ],
                    'columnDefs' => [
                        ['targets' => '_all', 'className' => 'text-nowrap'],
                    ],
                ]);

        }

    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $config = core()->getConfigData();

        if ($config->seamless == 'Y') {

            return [

                ['data' => 'code', 'name' => 'bills.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'date', 'name' => 'bills.date_create', 'title' => 'วันที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'user_name', 'name' => 'bills.pro_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ['data' => 'name', 'name' => 'bills.member_name', 'title' => 'ชื่อสมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ['data' => 'method', 'name' => 'members.user_name', 'title' => 'กิจกรรม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                //                ['data' => 'enable', 'name' => 'members.enable', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'remark', 'name' => 'members.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                //                ['data' => 'game_user', 'name' => 'members.user_name', 'title' => 'User Game', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ['data' => 'amount', 'name' => 'members.user_name', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'pro_name', 'name' => 'members.user_name', 'title' => 'โปรโมชั่น', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ['data' => 'bonus', 'name' => 'members.user_name', 'title' => 'โบนัสที่ได้', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'total', 'name' => 'members.user_name', 'title' => 'รวมทั้งหมด', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                //                ['data' => 'wallet_before', 'name' => 'members.user_name', 'title' => 'Wallet ก่อน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-success'],
                //                ['data' => 'wallet_after', 'name' => 'members.user_name', 'title' => 'Wallet หลัง', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-danger'],
                ['data' => 'credit_before', 'name' => 'members.user_name', 'title' => 'Credit ก่อน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-success'],
                ['data' => 'credit_after', 'name' => 'members.user_name', 'title' => 'Credit หลัง', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-danger'],
                ['data' => 'amount_balance', 'name' => 'members.user_name', 'title' => 'ตอนนี้ติดเทินอยู่', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-info'],
                ['data' => 'withdraw_limit_amount', 'name' => 'members.user_name', 'title' => 'ถูกอั้นถอนที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-info'],
                //                ['data' => 'withdraw_limit', 'name' => 'members.user_name', 'title' => 'ยอดอั้นคงที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-info'],
                ['data' => 'ip', 'name' => 'members.user_name', 'title' => 'IP', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ['data' => 'user_create', 'name' => 'members.user_name', 'title' => 'ผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ];

        } else {

            if ($config->multigame_open == 'Y') {
                return [

                    ['data' => 'code', 'name' => 'bills.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                    ['data' => 'date', 'name' => 'bills.date_create', 'title' => 'วันที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                    ['data' => 'user_name', 'name' => 'bills.pro_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                    ['data' => 'name', 'name' => 'bills.member_name', 'title' => 'ชื่อสมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                    ['data' => 'method', 'name' => 'members.user_name', 'title' => 'กิจกรรม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                    ['data' => 'enable', 'name' => 'members.enable', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                    ['data' => 'remark', 'name' => 'members.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                    ['data' => 'game_user', 'name' => 'members.user_name', 'title' => 'User Game', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                    ['data' => 'amount', 'name' => 'members.user_name', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                    ['data' => 'pro_name', 'name' => 'members.user_name', 'title' => 'โปรโมชั่น', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                    ['data' => 'bonus', 'name' => 'members.user_name', 'title' => 'โบนัสที่ได้', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                    ['data' => 'total', 'name' => 'members.user_name', 'title' => 'รวมทั้งหมด', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                    ['data' => 'wallet_before', 'name' => 'members.user_name', 'title' => 'Wallet ก่อน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-success'],
                    ['data' => 'wallet_after', 'name' => 'members.user_name', 'title' => 'Wallet หลัง', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-danger'],
                    ['data' => 'credit_before', 'name' => 'members.user_name', 'title' => 'Credit ก่อน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-success'],
                    ['data' => 'credit_after', 'name' => 'members.user_name', 'title' => 'Credit หลัง', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-danger'],
                    ['data' => 'ip', 'name' => 'members.user_name', 'title' => 'IP', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                    ['data' => 'user_create', 'name' => 'members.user_name', 'title' => 'ผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ];
            } else {
                return [

                    ['data' => 'code', 'name' => 'bills.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                    ['data' => 'date', 'name' => 'bills.date_create', 'title' => 'วันที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                    ['data' => 'user_name', 'name' => 'bills.pro_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                    ['data' => 'name', 'name' => 'bills.member_name', 'title' => 'ชื่อสมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                    ['data' => 'method', 'name' => 'members.user_name', 'title' => 'กิจกรรม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                    ['data' => 'enable', 'name' => 'members.enable', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                    ['data' => 'remark', 'name' => 'members.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                    ['data' => 'game_user', 'name' => 'members.user_name', 'title' => 'User Game', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                    ['data' => 'amount', 'name' => 'members.user_name', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                    ['data' => 'pro_name', 'name' => 'members.user_name', 'title' => 'โปรโมชั่น', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                    ['data' => 'bonus', 'name' => 'members.user_name', 'title' => 'โบนัสที่ได้', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                    ['data' => 'total', 'name' => 'members.user_name', 'title' => 'รวมทั้งหมด', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                    //                ['data' => 'wallet_before', 'name' => 'members.user_name', 'title' => 'Wallet ก่อน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-success'],
                    //                ['data' => 'wallet_after', 'name' => 'members.user_name', 'title' => 'Wallet หลัง', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-danger'],
                    ['data' => 'credit_before', 'name' => 'members.user_name', 'title' => 'Credit ก่อน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-success'],
                    ['data' => 'credit_after', 'name' => 'members.user_name', 'title' => 'Credit หลัง', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-danger'],
                    ['data' => 'amount_balance', 'name' => 'members.user_name', 'title' => 'ตอนนี้ติดเทินอยู่', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-info'],
                    ['data' => 'withdraw_limit_amount', 'name' => 'members.user_name', 'title' => 'ถูกอั้นถอนที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap text-info'],
                    ['data' => 'ip', 'name' => 'members.user_name', 'title' => 'IP', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                    ['data' => 'user_create', 'name' => 'members.user_name', 'title' => 'ผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ];
            }

        }

    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'bankin_datatable_'.time();
    }
}
