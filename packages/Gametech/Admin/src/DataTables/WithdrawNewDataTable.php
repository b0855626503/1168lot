<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\WithdrawNewTransformer;
use Gametech\Payment\Models\WithdrawNew;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class WithdrawNewDataTable extends DataTable
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
            ->with('in_yes', function () use ($query) {
                return core()->currency((clone $query)->where('withdraws_new.status', 1)->sum('withdraws_new.amount'));
            })
            ->with('in_no', function () use ($query) {
                return core()->currency((clone $query)->where('withdraws_new.status', 0)->sum('withdraws_new.amount'));
            })
            ->setTransformer(new WithdrawNewTransformer);

    }


    /**
     * @param WithdrawNew $model
     * @return mixed
     */
    public function query(WithdrawNew $model)
    {
        $status = request()->input('status');
        $bank = request()->input('bankname');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model->newQuery()
            ->with(['bank', 'admin'])
            ->select('withdraws_new.*')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            })
            ->when($status, function ($query, $status) {
                $query->where('status', ($status == 'A' ? 0 : $status));
            })
            ->when($bank, function ($query, $bank) {
                $query->where('account_code', $bank);
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
            ['data' => 'code', 'name' => 'withdraws_new.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'to_name', 'name' => 'withdraws_new.to_name', 'title' => 'ชื่อผู้รับ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'to_account', 'name' => 'withdraws_new.to_account', 'title' => 'บัญชีผู้รับ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'amount', 'name' => 'withdraws_new.amount', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
            ['data' => 'account_code', 'name' => 'withdraws_new.account_code', 'title' => 'โอนจาก', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'status', 'name' => 'withdraws_new.status', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_bank', 'name' => 'withdraws_new.date_bank', 'title' => 'วันที่โอน', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'time_bank', 'name' => 'withdraws_new.time_bank', 'title' => 'เวลาที่โอน', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'ref', 'name' => 'withdraws_new.ref', 'title' => 'เลขอ้างอิง', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'remark', 'name' => 'withdraws_new.remark_admin', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'emp', 'name' => 'withdraws_new.emp_code', 'title' => 'ผู้โอน', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'ip', 'name' => 'withdraws_new.ip', 'title' => 'IP', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap']
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'withdraw_datatable_' . time();
    }
}
