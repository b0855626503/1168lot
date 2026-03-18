<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\PaymentTransformer;
use Gametech\Payment\Contracts\Payment;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpSumPaymentDataTable extends DataTable
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
            ->setTransformer(new PaymentTransformer);

    }


    public function query(Payment $model)
    {
        return $model->newQuery()
            ->select('payments.*');


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
            ->minifiedAjax()
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
                           api.columns().every(function (i) {
                            if(i > 3){
                           var sum = this.data()
                                      .reduce(function(a, b) {
                                        var x = parseFloat(a) || 0;
                                        var y = parseFloat(b) || 0;
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
            ['data' => 'code', 'name' => 'payments.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_pay', 'name' => 'payments.question', 'title' => 'วันที่', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'name', 'name' => 'payments.sort', 'title' => 'รายการ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'amount', 'name' => 'payments.sort', 'title' => 'จำนวนเงิน', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
            ['data' => 'user_create', 'name' => 'payments.enable', 'title' => 'ผู้สร้างรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'ip', 'name' => 'faq.enable', 'title' => 'IP', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
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
