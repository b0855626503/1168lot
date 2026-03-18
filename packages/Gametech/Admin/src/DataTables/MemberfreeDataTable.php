<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\MemberfreeTransformer;
use Gametech\Member\Contracts\Member;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class MemberfreeDataTable extends DataTable
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
            ->setTransformer(new MemberfreeTransformer);
//        return $dataTable->setTransformer(new WithdrawTransformer);
//        return $dataTables->addColumn('action', 'admins::withdraw.datatables_confirm');
//        return $dataTable
//            ->editColumn('member_acc', function($query) {
//            return $query->bankCode->shortcode.'['.$query->memberCode->acc_no.']';
//        });
    }


    /**
     * @param Member $model
     * @return mixed
     */
    public function query(Member $model)
    {


        return $model->newQuery()
            ->confirm()->active()
            ->select('members.*');


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
                'responsive' => true,
                'stateSave' => true,
                'scrollX' => false,

                'paging' => true,
                'searching' => true,
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
            ['data' => 'code', 'name' => 'members.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'date', 'name' => 'members.date_regis', 'title' => 'วันที่สม้คร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'firstname', 'name' => 'members.firstname', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'lastname', 'name' => 'members.lastname', 'title' => 'นามสกุล', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'members.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'pass', 'name' => 'members.user_pass', 'title' => 'รหัสผ่าน', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'balance', 'name' => 'members.balance_free', 'title' => 'Credit', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
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
