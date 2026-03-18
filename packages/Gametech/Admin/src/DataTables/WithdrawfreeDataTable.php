<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\WithdrawfreeTransformer;
use Gametech\Payment\Contracts\WithdrawFree;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class WithdrawfreeDataTable extends DataTable
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
            ->with('in_all', function () use ($query) {
                return core()->currency((clone $query)->sum('amount'));
            })
            ->with('in_wait', function () use ($query) {
                return core()->currency((clone $query)->where('status', 0)->sum('amount'));
            })
            ->with('in_yes', function () use ($query) {
                return core()->currency((clone $query)->where('status', 1)->sum('amount'));
            })
            ->with('in_no', function () use ($query) {
                return core()->currency((clone $query)->where('status', 2)->sum('amount'));
            })
            ->setTransformer(new WithdrawfreeTransformer);

    }


    /**
     * @param WithdrawFree $model
     * @return mixed
     */
    public function query(WithdrawFree $model)
    {

        $status = request()->input('status');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model->newQuery()
            ->active()
            ->with('member', 'bank')
            ->select('withdraws_free.*')
            ->withCasts([
                'date_create' => 'date:Y-m-d',
                'date_record' => 'date:Y-m-d'
            ])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            })
            ->when($status, function ($query, $status) {
                $query->where('status', ($status == 'A' ? 0 : $status));
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
                    ['targets' => '_all', 'className' => 'text-nowrap']
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
            ['data' => 'code', 'name' => 'withdraws_free.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center'],
            ['data' => 'check', 'name' => 'check', 'title' => 'ตรวจสอบ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'acc_no', 'name' => 'member.acc_no', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => false, 'className' => 'text-left'],
            ['data' => 'date', 'name' => 'withdraws_free.date_record', 'title' => 'วันที่แจ้ง', 'orderable' => false, 'searchable' => true, 'className' => 'text-center'],
            ['data' => 'time', 'name' => 'withdraws_free.timedept', 'title' => 'เวลาที่แจ้ง', 'orderable' => false, 'searchable' => true, 'className' => 'text-center'],
            ['data' => 'username', 'name' => 'withdraws_free.member_user', 'title' => 'User ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-center'],
            ['data' => 'name', 'name' => 'member.name', 'title' => 'ชื่อลูกค้า', 'orderable' => false, 'searchable' => true, 'className' => 'text-left'],
            ['data' => 'balance', 'name' => 'withdraws_free.balance', 'title' => 'จำนวนเงินที่แจ้ง', 'orderable' => false, 'searchable' => true, 'className' => 'text-right'],
            ['data' => 'amount', 'name' => 'withdraws_free.amount', 'title' => 'จำนวนเงินที่ได้รับ', 'orderable' => false, 'searchable' => true, 'className' => 'text-right'],
            ['data' => 'ip', 'name' => 'withdraws_free.ip', 'title' => 'IP', 'orderable' => false, 'searchable' => true, 'className' => 'text-left'],
            ['data' => 'refill', 'name' => 'withdraws_free.refill', 'title' => 'เติมล่าสุดจาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'waiting', 'name' => 'waiting', 'title' => 'อนุมัติ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'cancel', 'name' => 'cancel', 'title' => 'คืนยอด', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'delete', 'name' => 'delete', 'title' => 'ลบ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'withdraw_free_datatable_' . time();
    }
}
