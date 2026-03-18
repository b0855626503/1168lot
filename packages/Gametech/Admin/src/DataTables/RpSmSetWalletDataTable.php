<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\RpSmSetWalletTransformer;
use Gametech\Member\Contracts\MemberCreditLog;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpSmSetWalletDataTable extends DataTable
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
            ->setTransformer(new RpSmSetWalletTransformer);

    }

//    public function ajax()
//    {
//        return $this->datatables
//            ->eloquent($this->query())
//            ->make(true);
//    }

    public function query(MemberCreditLog $model)
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

        return $model->newQuery()->where('kind','SETWALLET')->with(['member'])
            ->select('members_credit_log.*')->orderByDesc('code')

            ->when($user, function ($query, $user) {
                $query->whereIn('members_credit_log.member_code', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
                });
            })
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
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
            ->ajaxWithForm('/rp_sm_setwallet', '.frmsearch')
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
                'ordering' => false,
                'autoWidth' => false,
                'scrollX' => true,
                'order' => [[0, 'desc']],
                'buttons' => [
                    'pageLength'
                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                    ['target' => 0, 'visible' => false],
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
//            ['data' => 'code', 'name' => 'bank_payment.code', 'title' => '#', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap hide'],
            ['data' => 'no', 'name' => 'bank_payment.code', 'title' => '#', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_approve', 'name' => 'bank_payment.username', 'title' => 'วัน/เวลา', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'username', 'name' => 'bank_payment.username', 'title' => 'User', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'type', 'name' => 'bank_payment.name', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'amount', 'name' => 'bank_payment.sort', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
//            ['data' => 'fee', 'name' => 'bank_payment.sort', 'title' => 'ค่าธรรมเนียม', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
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
