<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\RpSmWithdrawSeamlessTransformer;
use Gametech\Payment\Contracts\WithdrawSeamless;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpSmWithdrawSeamlessDataTable extends DataTable
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
            ->setTransformer(new RpSmWithdrawSeamlessTransformer);

    }

//    public function ajax()
//    {
//        return $this->datatables
//            ->eloquent($this->query())
//            ->make(true);
//    }

    public function query(WithdrawSeamless $model)
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

        return $model->newQuery()->orderByDesc('code')
            ->active()
            ->with(['member'])
            ->with(['bills' => function ($model) {
                $model->with('promotion')->getpro()->active()->orderBy('date_create', 'desc');
            }])->withCasts([
                'date_approve' => 'datetime:Y-m-d H:00'
            ])
            ->select('withdraws_seamless.*')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('withdraws_seamless.member_code', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
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
            ->ajaxWithForm('/rp_sm_withdraw_seamless', '.frmsearch')
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
