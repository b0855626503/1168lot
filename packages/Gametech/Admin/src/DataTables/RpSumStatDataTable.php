<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpSumStatTransformer;
use Gametech\Core\Contracts\DailyStat;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpSumStatDataTable extends DataTable
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
            ->setTransformer(new RpSumStatTransformer);

    }


    /**
     * @param DailyStat $model
     * @return mixed
     */
    public function query(DailyStat $model)
    {
        $today = now()->toDateString();
        $yesterday = now()->subDays(1)->toDateString();

//        app('Gametech\Core\Repositories\DailyStatRepository')->sumData($today);
//        app('Gametech\Core\Repositories\DailyStatRepository')->sumData($yesterday);


        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');

        if (empty($startdate)) {
            $startdate = $today;
        }
        if (empty($enddate)) {
            $enddate = $today;
        }

        return $model->newQuery()->select('daily_stat.*')->orderBy('date')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date', array($startdate, $enddate));
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
                            if(i > 3){
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
            ['data' => 'date', 'name' => 'date', 'title' => 'วันที่', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_all', 'name' => 'member_all', 'title' => 'สมาชิกทั้งหมด', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_new', 'name' => 'member_new', 'title' => 'สมาชิกใหม่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_new_refill', 'name' => 'member_new_refill', 'title' => 'สมาชิกใหม่เติมเงิน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_all_refill', 'name' => 'member_all_refill', 'title' => 'สมาชิกเติมเงินทั้งหมด', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'deposit_count', 'name' => 'deposit_count', 'title' => 'จำนวนรายการฝาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'deposit_sum', 'name' => 'deposit_sum', 'title' => 'รวมยอดฝาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'withdraw_count', 'name' => 'withdraw_count', 'title' => 'จำนวนรายการถอน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'withdraw_sum', 'name' => 'withdraw_sum', 'title' => 'รวมยอดถอน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'total', 'name' => 'total', 'title' => 'ฝาก-ถอน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'bonus_sum', 'name' => 'bonus_sum', 'title' => 'รวมยอดรับโปร', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'setwallet_d_sum', 'name' => 'withdraw_sum', 'title' => 'ทีมงานเพิ่ม Wallet', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'setwallet_w_sum', 'name' => 'withdraw_sum', 'title' => 'ทีมงานลด Wallet', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'updated_at', 'name' => 'updated_at', 'title' => 'อัพเดทล่าสุดเมื่อ', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
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
