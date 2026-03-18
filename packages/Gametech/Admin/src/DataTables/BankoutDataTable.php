<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\BankoutTransformer;
use Gametech\Payment\Contracts\BankPayment;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class BankoutDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->with('out_all', function () use ($query) {
                return core()->currency((clone $query)->sum('value'));
            })
            ->setTransformer(new BankoutTransformer);

    }


    /**
     * @param BankPayment $model
     * @return mixed
     */
    public function query(BankPayment $model)
    {
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model->newQuery()
            ->with(['bank_account' => function ($model) {
                $model->with('bank');
            }])->withCasts([
                'date_create' => 'datetime:Y-m-d H:i:s',
                'bank_time' => 'datetime:Y-m-d H:i:s'
            ])
            ->waiting()->active()->profit()
            ->select('bank_payment.*')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            });


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
                'ordering' => true,

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
                ]
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
            ['data' => 'code', 'name' => 'bank_payment.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'bankcode', 'name' => 'bankcode', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'acc_no', 'name' => 'bank_account.acc_no', 'title' => 'เลขบัญชี', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'bank_time', 'name' => 'bank_payment.bank_time', 'title' => 'เวลาธนาคาร', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'channel', 'name' => 'bank_payment.channel', 'title' => 'ช่องทาง', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'detail', 'name' => 'bank_payment.detail', 'title' => 'รายละเอียด', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'value', 'name' => 'bank_payment.value', 'title' => 'จำนวนเงิน', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
            ['data' => 'date', 'name' => 'bank_payment.date_update', 'title' => 'เวลาตรวจสอบ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'cancel', 'name' => 'cancel', 'title' => 'เคลียร์', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'delete', 'name' => 'delete', 'title' => 'ลบ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'bankout_datatable_' . time();
    }
}
