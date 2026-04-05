<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpMemberEditTransformer;
use Gametech\Admin\Transformers\RpMemberRefTransformer;
use Gametech\Core\Contracts\Refer;
use Gametech\Member\Contracts\MemberEditLog;
use Gametech\Member\Contracts\MemberLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;


class RpMemberEditDataTable extends DataTable
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
//        dd($query->getQuery());
        return $dataTable

            ->setTransformer(new RpMemberEditTransformer);

    }


    /**
     * @param Refer $model
     * @return mixed
     */
    public function query(MemberEditLog $model)
    {


        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        $username = request()->input('user_name');
        $menu = request()->input('menu');

        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }


        return $model->newQuery()
            ->select('members_edit_log.*')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('members_edit_log.date_create', array($startdate, $enddate));
            })
            ->when($menu, function ($query, $menu) {
                $query->where('menu',$menu);
            })
            ->when($username, function ($query, $username) {
                $query->whereIn('members_edit_log.member_code', function ($q) use ($username) {
                    $q->from('members')->select('members.code')->where('members.user_name', $username);
                });
            })->orderByDesc('code');


    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
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
            ['data' => 'date_create', 'name' => 'date_create', 'title' => 'วันที่-เวลา', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'user_name', 'title' => 'Username/Tel', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'mode', 'name' => 'mode', 'title' => 'รายการแก้ไข', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'item_before', 'name' => 'item_before', 'title' => 'ข้อมูลเก่า', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'item', 'name' => 'item', 'title' => 'ข้อมูลใหม่', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'remark', 'name' => 'remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'emp_user', 'name' => 'emp_user', 'title' => 'แก้ไขโดย', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'ip', 'name' => 'ip', 'title' => 'IP', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
   ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'bankin_datatable_' . time();
    }
}
