<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\RpSmWithdrawTransformer;
use Gametech\Payment\Contracts\Withdraw;
use Illuminate\Support\Carbon;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpSmWithdrawDataTable extends DataTable
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
                return core()->currency((clone $query)->sum('amount'));
            })
            ->setTransformer(new RpSmWithdrawTransformer);

    }

//    public function ajax()
//    {
//        return $this->datatables
//            ->eloquent($this->query())
//            ->make(true);
//    }

    public function query(Withdraw $model)
    {

        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        $user = request()->input('user_name');
        $check = request()->input('check');

        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        $onlystartdate = Carbon::parse($startdate)->toDateString();
        $onlyenddate = Carbon::parse($enddate)->toDateString();

        return $model->newQuery()->orderByDesc('code')
            ->active()
            ->with(['member'])
            ->with(['bills' => function ($model) {
                $model->with('promotion')->getpro()->active()->orderBy('date_create', 'desc');
            }])->withCasts([
                'date_approve' => 'datetime:Y-m-d H:00'
            ])
            ->select('withdraws.*')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('withdraws.member_code', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
                });
            })
            ->when($check, function ($query, $check) use ($onlystartdate, $onlyenddate) {
                $query->whereIn('withdraws.member_code', function ($q) use ($check,$onlystartdate, $onlyenddate) {
                    $q->from('members')->select('members.code')->whereBetween("members.$check", array($onlystartdate, $onlyenddate));
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
            ->ajaxWithForm('/rp_sm_withdraw', '.frmsearch')
            ->parameters([
                'dom' => 'Bfrtip',
                'processing' => true,
                'serverSide' => true,
                'responsive' => false,
                'stateSave' => true,
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => false,
                'scrollX' => true,
                'order' => [[0, 'desc']],
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
                            if(i == 3){
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
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
//            ['data' => 'code', 'name' => 'bank_payment.code', 'title' => '#', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap hide'],
            ['data' => 'no', 'name' => 'bank_payment.code', 'title' => '#', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_approve', 'name' => 'bank_payment.username', 'title' => 'วัน/เวลา', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'username', 'name' => 'bank_payment.username', 'title' => 'User', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
//            ['data' => 'name', 'name' => 'bank_payment.name', 'title' => 'Name', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'amount', 'name' => 'bank_payment.sort', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
            ['data' => 'fee', 'name' => 'bank_payment.sort', 'title' => 'ค่าธรรมเนียม', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
            ['data' => 'msg', 'name' => 'bank_payment.sort', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
//            ['data' => 'pro_amount', 'name' => 'bank_payment.sort', 'title' => 'โบนัส', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
//            ['data' => 'user_create', 'name' => 'payments.enable', 'title' => 'ผู้สร้างรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'ip', 'name' => 'faq.enable', 'title' => 'IP', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
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
