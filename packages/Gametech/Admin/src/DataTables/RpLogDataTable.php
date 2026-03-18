<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpLogTransformer;
use Gametech\Core\Contracts\Log;
use Gametech\LogAdmin\Contracts\Activity;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpLogDataTable extends DataTable
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
            ->setTransformer(new RpLogTransformer);

    }


    /**
     * @param Activity $model
     * @return mixed
     */
    public function query(Log $model)
    {
        $ip = request()->input('ip');
        $mode = request()->input('mode');
        $menu = request()->input('menu');
        $user = request()->input('user_name');
        $emp = request()->input('emp_user');
        $description = request()->input('description');
        $details = request()->input('details');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model->newQuery()
            ->with('admin')
            ->select('logs.*')->withCasts([
                'date_create' => 'datetime:Y-m-d H:00'
            ])
            ->when($mode, function ($query, $mode) {
                if($mode == 'LOG'){
                    $query->whereIn('mode', ['LOGIN','LOGOUT']);
                }else{
                    $query->where('mode', $mode);
                }

            })
            ->when($menu, function ($query, $menu) {
                $query->where('menu', $menu);
            })
            ->when($ip, function ($query, $ip) {
                $query->where('ip', $ip);
            })
            ->when($description, function ($query, $description) {
                $query->where('description', 'like', "%$description%");
            })
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            })
            ->when($user, function ($query, $user) {
                $query->where('menu', 'members')->whereIn('logs.record', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
                });
            })
            ->when($emp, function ($query, $emp) {
                $query->whereIn('logs.emp_code', function ($q) use ($emp) {
                    $q->from('employees')->select('employees.code')->where('employees.user_name', $emp);
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
            ->ajaxWithForm('', '#frmsearch')
            ->parameters([
                'dom' => 'Bfrtip',
                'processing' => true,
                'serverSide' => true,
                'responsive' => false,
                'stateSave' => true,
                'scrollX' => true,
                'scrollY' => '400px', // 👈 ความสูง fix ที่ 400px
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => true,
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
                    ['targets' => '_all']
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
            ['data' => 'code', 'name' => 'logs.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'emp', 'name' => 'logs.emp', 'title' => 'พนักงานผู้ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'mode', 'name' => 'logs.mode', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'menu', 'name' => 'logs.menu', 'title' => 'เมนู', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'record', 'name' => 'logs.record', 'title' => 'ข้อมูลที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'logs.record', 'title' => 'ไอดีสมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'ip', 'name' => 'logs.ip', 'title' => 'IP', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'date_create', 'name' => 'logs.date_create', 'title' => 'วันที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'item_before', 'name' => 'logs.item_before', 'title' => 'ข้อมูลก่อน', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-wrap' , 'width' => '150px'],
            ['data' => 'item', 'name' => 'logs.item', 'title' => 'ข้อมูลหลัง', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-wrap', 'width' => '150px'],

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
