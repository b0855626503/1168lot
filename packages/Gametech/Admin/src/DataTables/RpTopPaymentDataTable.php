<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\RpTopPaymentTransformer;
use Gametech\Member\Contracts\Member;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpTopPaymentDataTable extends DataTable
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
            ->setTransformer(new RpTopPaymentTransformer);

    }

//    public function ajax()
//    {
//        return $this->datatables
//            ->eloquent($this->query())
//            ->make(true);
//    }

    public function query(Member $model)
    {

        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        $user = request()->input('user_name');

        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        $payment = DB::table('bank_payment')->select(DB::raw('SUM(bank_payment.value)  as amount'), 'bank_payment.member_topup')
            ->where('bank_payment.enable', 'Y')
            ->where('bank_payment.status', 1)
            ->where('bank_payment.bankstatus', 1)
            ->where('bank_payment.value', '<>', 0)

            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('bank_payment.date_approve', array($startdate, $enddate));
            })->groupBy('bank_payment.member_topup')
            ->orderByDesc(DB::raw('SUM(bank_payment.value)'));

        $counts = DB::table('bank_payment')->distinct()->select(DB::raw('COUNT(DATE(bank_payment.date_create))  as date_refill'), 'bank_payment.member_topup')
            ->where('bank_payment.enable', 'Y')
            ->where('bank_payment.status', 1)
            ->where('bank_payment.bankstatus', 1)
            ->where('bank_payment.value', '<>', 0)
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('bank_payment.date_approve', array($startdate, $enddate));
            })->groupBy('bank_payment.member_topup')
            ->orderByDesc(DB::raw('COUNT(DATE(bank_payment.date_create))'));

        return $model->select('members.user_name', 'members.name','bank_payment.amount')
            ->joinSub($payment, 'bank_payment', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'members.code');

            })->orderByDesc('bank_payment.amount')
            ->joinSub($counts, 'bank_payment_new', function ($join) {
                $join->on('bank_payment_new.member_topup', '=', 'members.code');

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
            ->ajaxWithForm('/rp_top_payment', '.frmsearch')
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
            ['data' => 'code', 'name' => 'bank_payment.code', 'title' => '#', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'username', 'name' => 'bank_payment.username', 'title' => 'User', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'name', 'name' => 'bank_payment.name', 'title' => 'Name', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'amount', 'name' => 'bank_payment.amount', 'title' => 'Total', 'orderable' => true, 'searchable' => true, 'className' => 'text-right text-nowrap'],
            ['data' => 'refill', 'name' => 'bank_payment.refill', 'title' => 'Refill', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
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
