<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpRecommenderTransformer;
use Gametech\Admin\Transformers\RpSponsorTransformer;
use Gametech\Member\Contracts\Member;
use Gametech\Payment\Models\BankPayment;
use Gametech\Payment\Contracts\PaymentPromotion;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpRecommenderDataTable extends DataTable
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

        return $dataTable->setTransformer(new RpRecommenderTransformer);

    }


    /**
     * @param PaymentPromotion $model
     * @return mixed
     */
    public function query(Member $model)
    {
        $ip = request()->input('ip');
        $username = request()->input('user_name');
//        $down_id = request()->input('downline_id');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }



//        return $model->newQuery()->with('member','down')
//            ->active()->aff()->orderBy('code','desc')
//            ->select('payments_promotion.*')->withCasts([
//                'date_create' => 'datetime:Y-m-d H:00'
//            ])->when($startdate, function ($query, $startdate) use ($enddate) {
//                $query->whereBetween('payments_promotion.date_create', array($startdate, $enddate));
//            });

//        $amountSum = BankPayment::selectRaw('sum(value) as value')
//            ->where('status',1)->where('enable','Y')
//            ->when($startdate, function ($query, $startdate) use ($enddate) {
//            $query->whereBetween('date_create', array($startdate, $enddate));
//        })
//            ->whereColumn('member_topup', 'members.code')->getQuery();


        return $model->newQuery()->active()->without('bank')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('members.date_create', array($startdate, $enddate));
            })
            ->when($ip, function ($query, $ip) {
                $query->where('ip', 'like', "%" . $ip . "%");
            })
//            ->when($username, function ($query, $username) {
//                $query->where('members.user_name', $username);
//            });

            ->when($username, function ($query, $username) {
                $query->whereIn('members.upline_code', function ($q) use ($username) {
                    $q->from('members')->select('members.code')->where('members.user_name', $username);
                });
            }, function ($query) {
                $query->where('code', 0);
            })

            ->withSum(['payment:value' => function (\Illuminate\Database\Eloquent\Builder $query) use ($startdate, $enddate) {

                $query->whereBetween('date_create', array($startdate, $enddate));

            }])
            ->withSum(['payout:amount' => function (\Illuminate\Database\Eloquent\Builder $query) use ($startdate, $enddate) {

                $query->whereBetween('date_create', array($startdate, $enddate));

            }])
            ;
//            ->when($username, function ($query, $username) {
//                $query->whereHas('up', function ($q) use ($username) {
//                    $q->where('user_name', $username);
//                });
//            })
//            ;


    }


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
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows']
                ],
                'buttons' => [
                    'pageLength'
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
                            if(i == 4 || i == 5){
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
            ['data' => 'code', 'name' => 'members.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_regis', 'name' => 'members.date_regis', 'title' => 'วันที่สมัคร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'name', 'name' => 'members.name', 'title' => 'Name', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'members.user_name', 'title' => 'User ID ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'bonus', 'name' => 'payments_promotion.credit_before', 'title' => 'Bonus (Upline)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//            ['data' => 'down_name', 'name' => 'payments_promotion.credit', 'title' => 'Name (Downline)', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'down_id', 'name' => 'payments_promotion.credit', 'title' => 'User ID (Downline)', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'amount', 'name' => 'members.value', 'title' => 'ยอดที่ฝากเข้ามา (รวม)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'payout', 'name' => 'members.payout', 'title' => 'ยอดที่ถอนเข้ามา (รวม)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
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
