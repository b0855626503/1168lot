<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpStaffLogTransformer;
use Gametech\LogAdmin\Contracts\Activity;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpStaffLogDataTable extends DataTable
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
            ->setTransformer(new RpStaffLogTransformer);

    }


    /**
     * @param Activity $model
     * @return mixed
     */
    public function query(Activity $model)
    {
        $ip = request()->input('ip');
        $bonus = request()->input('bonus_name');
        $user = request()->input('user_name');
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
            ->select('logger_admin_activity.*')->withCasts([
                'created_at' => 'datetime:Y-m-d H:00'
            ])
            ->when($details, function ($query, $details)  {
                $query->where('details','like', "%$details%");
            })
            ->when($description, function ($query, $description)  {
                $query->where('description','like', "%$description%");
            })
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('created_at', array($startdate, $enddate));
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('logger_admin_activity.userId', function ($q) use ($user) {
                    $q->from('employees')->select('employees.code')->where('employees.user_name', $user);
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
                    ['targets' => '_all', 'className' => 'text-center text-nowrap']
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
            ['data' => 'id', 'name' => 'logger_admin_activity.id', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'time', 'name' => 'logger_user_activity.member_name', 'title' => 'เวลา', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'detail', 'name' => 'bonus_spin.user_name', 'title' => 'รายละเอียด', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'desctiption', 'name' => 'bonus_spin.user_name', 'title' => 'การดำเนินการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'bonus_spin.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_name', 'name' => 'bonus_spin.member_name', 'title' => 'ชื่อทีมงาน', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'route', 'name' => 'bonus_spin.credit_before', 'title' => 'เส้นทาง', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'ip', 'name' => 'bonus_spin.credit_before', 'title' => 'IP', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
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
