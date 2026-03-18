<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\RpNoRefillTransformer;
use Gametech\Member\Contracts\Member;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpNoRefillDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTables = new EloquentDataTable($query);

        return $dataTables
            ->setTransformer(new RpNoRefillTransformer);

    }

//    public function ajax()
//    {
//        return $this->datatables
//            ->eloquent($this->query())
//            ->make(true);
//    }

    public function query(Member $model)
    {

        $config = core()->getConfigData();

        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        $user = request()->input('user_name');

        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }



        return $model->newQuery()->where('count_deposit',0)
            ->with('bank')
            ->when($user, function ($query, $user) {
                $query->where('members.user_name', $user);
            })
            ->when($startdate, function ($query, $startdate) use ($enddate) {
            $query->whereBetween('members.date_regis', array($startdate, $enddate));
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
            ['data' => 'code', 'name' => 'withdraws.code', 'title' => '#', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_regis', 'name' => 'withdraws.username', 'title' => 'วันที่สมัคร', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'user_name', 'name' => 'withdraws.name', 'title' => 'ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'name', 'name' => 'withdraws.name', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'bank', 'name' => 'withdraws.amount', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'acc_no', 'name' => 'withdraws.amount', 'title' => 'เลขที่บัญชี', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'tel', 'name' => 'withdraws.amount', 'title' => 'เบอร์โทร', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'lineid', 'name' => 'withdraws.amount', 'title' => 'ไอดีไลน์', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'enable', 'name' => 'withdraws.amount', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
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
