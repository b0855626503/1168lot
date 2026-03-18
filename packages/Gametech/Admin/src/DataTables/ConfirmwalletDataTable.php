<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\ConfirmwalletTransformer;
use Gametech\Payment\Contracts\PaymentWaiting;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class ConfirmwalletDataTable extends DataTable
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
            ->filter(function ($query) {

                if ($name = request()->input('user_name')) {
                    $query->whereHas('member', function ($q) use ($name) {
//                        $q->where('user_name', 'like', "%" . $name . "%");
                        $q->where('members.user_name', $name);
                    });
                }
            })
            ->setTransformer(new ConfirmwalletTransformer);
//        return $dataTable->setTransformer(new WithdrawTransformer);
//        return $dataTables->addColumn('action', 'admins::withdraw.datatables_confirm');
//        return $dataTable
//            ->editColumn('member_acc', function($query) {
//            return $query->bankCode->shortcode.'['.$query->memberCode->acc_no.']';
//        });
    }


    /**
     * @param PaymentWaiting $model
     * @return mixed
     */
    public function query(PaymentWaiting $model)
    {
//        return $model->newQuery()
//            ->waiting()->active()
//            ->select('payments_waiting.*')->members();

        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model->newQuery()
            ->waiting()->active()
            ->with(['member', 'game', 'promotion'])
            ->select('payments_waiting.*')
            ->withCasts([
                'date_create' => 'datetime:Y-m-d H:00'
            ])->when($startdate, function ($query, $startdate) use ($enddate) {
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
    protected function getColumns()
    {
        return [
            ['data' => 'code', 'name' => 'payments_waiting.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'date', 'name' => 'payments_waiting.date_create', 'title' => 'วันเวลา', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'name', 'name' => 'member.name', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'user_name', 'name' => 'member.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'game', 'name' => 'game.name', 'title' => 'โอนเข้าเกม', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'pro', 'name' => 'promotion.name_th', 'title' => 'โปรโมชั่น', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'amount', 'name' => 'payments_waiting.amount', 'title' => 'จำนวนที่โยก', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
            ['data' => 'balance', 'name' => 'member.balance', 'title' => 'Wallet ปัจจุบัน', 'orderable' => false, 'searchable' => true, 'className' => 'text-right text-nowrap'],
            ['data' => 'ip', 'name' => 'payments_waiting.ip', 'title' => 'IP', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'confirm', 'name' => 'confirm', 'title' => 'อนุมัติ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'cancel', 'name' => 'cancel', 'title' => 'คืนยอด', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'delete', 'name' => 'delete', 'title' => 'ลบ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
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
