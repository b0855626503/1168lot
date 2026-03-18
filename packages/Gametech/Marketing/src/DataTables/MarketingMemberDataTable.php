<?php

namespace Gametech\Marketing\DataTables;

use Gametech\Marketing\Contracts\MarketingMember;
use Gametech\Marketing\Transformers\MarketingMemberTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class MarketingMemberDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->setTransformer(new MarketingMemberTransformer);

    }

    /**
     * @param  Role  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(MarketingMember $model)
    {
        $filter = request()->input('filter', 'all');
        $today = now()->toDateString();

        $baseDepositQuery = function ($q) {
            $q->where('status', 1)->where('enable', 'Y');
        };

        $baseWithdrawQuery = function ($q) {
            $q->where('status', 1)->where('enable', 'Y');
        };

        return $model->newQuery()
            ->where('campaign_id', $this->campaign_id)
            ->with('firstDeposit')
            ->withSum(['deposits:value as total_deposit' => function (\Illuminate\Database\Eloquent\Builder $query) {

                $query->where('status', 1)->where('enable', 'Y');

            }])
            ->withSum(['deposits:value as today_deposit' => function (\Illuminate\Database\Eloquent\Builder $query) use ($today) {

                $query->where('status', 1)->where('enable', 'Y')->whereDate('date_approve', $today);

            }])
            ->withSum(['withdrawals:amount as total_withdraw' => function (\Illuminate\Database\Eloquent\Builder $query) {

                $query->where('status', 1)->where('enable', 'Y');

            }])
            ->withSum(['withdrawals:amount as today_withdraw' => function (\Illuminate\Database\Eloquent\Builder $query) use ($today) {

                $query->where('status', 1)->where('enable', 'Y')->whereDate('date_approve', $today);

            }])
//            ->withSum(['deposits as total_deposit' => $baseDepositQuery], 'value')
//            ->withSum(['withdrawals as total_withdraw' => $baseWithdrawQuery], 'amount')
            ->when($filter === 'has_deposit', function ($q) use ($baseDepositQuery) {
                return $q->whereHas('deposits', $baseDepositQuery);
            })
            ->when($filter === 'has_withdraw', function ($q) use ($baseWithdrawQuery) {
                return $q->whereHas('withdrawals', $baseWithdrawQuery);
            })
//            ->withSum(['deposits as today_deposit' => function ($q) use ($today) {
//                $q->where('status', 1)->where('enable', 'Y')
//                    ->whereDate('date_approve', $today);
//            }], 'value')
//            ->withSum(['withdrawals as today_withdraw' => function ($q) use ($today) {
//                $q->where('status', 1)->where('enable', 'Y')
//                    ->whereDate('date_approve', $today);
//            }], 'amount')
            ->when($filter === 'deposit_today', function ($q) use ($today) {
                $q->whereHas('deposits', function ($q) use ($today) {
                    $q->where('status', 1)->where('enable', 'Y')
                        ->whereDate('date_approve', $today);
                });
            })
            ->when($filter === 'withdraw_today', function ($q) use ($today) {
                $q->whereHas('withdrawals', function ($q) use ($today) {
                    $q->where('status', 1)->where('enable', 'Y')
                        ->whereDate('date_approve', $today);
                });
            })
            ->when($filter === 'no_deposit', function ($q) {
                $q->whereDoesntHave('deposits', function ($q) {
                    $q->where('status', 1)->where('enable', 'Y');
                });
            })

            ->orderByDesc('code'); // หรือชื่อ field อื่นที่ต้องการ

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
                'scrollY' => '400px',       // ความสูงตาราง (เช่น 400px)
                'scrollCollapse' => true,
                'paging' => false,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => false,
                'pageLength' => 100,
                'lengthMenu' => [
                    [100,  200, 500, 1000],
                    ['100 rows', '200 rows', '500 rows', '1000 rows'],
                ],
                'order' => [[0, 'asc']],
//                'buttons' => [
//                    'pageLength',
//                ],
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
                            if(i > 2){
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
    protected function getColumns()
    {
        return [
            ['data' => 'name', 'name' => 'name', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'username', 'name' => 'username', 'title' => 'Username', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_regis', 'name' => 'date_regis', 'title' => 'วันที่สมัคร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'total_deposit', 'name' => 'total_deposit', 'title' => 'ยอดฝากทั้งหมด', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'deposit', 'name' => 'deposit', 'title' => 'ยอดฝากวันนี้', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'total_withdraw', 'name' => 'total_withdraw', 'title' => 'ยอดถอนทั้งหมด', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'withdraw', 'name' => 'withdraw', 'title' => 'ยอดถอนวันนี้', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'first_deposit', 'name' => 'first_deposit', 'title' => 'ยอดฝากแรก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
        ];
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
