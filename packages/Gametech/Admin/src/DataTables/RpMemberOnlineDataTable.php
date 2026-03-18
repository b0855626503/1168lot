<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\RpMemberOnlineTransformer;
use Gametech\Member\Contracts\Member;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpMemberOnlineDataTable extends DataTable
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

//            ->with('p1', function () use ($query) {
//                return (clone $query)->where('pro_code', 1)->count();
//            })
//            ->with('p2', function () use ($query) {
//                return (clone $query)->where('pro_code', 2)->count();
//            })
//            ->with('p4', function () use ($query) {
//                return (clone $query)->where('pro_code', 4)->count();
//            })
//            ->with('p6', function () use ($query) {
//                return (clone $query)->where('pro_code', 6)->count();
//            })


            ->setTransformer(new RpMemberOnlineTransformer);

    }


    public function query(Member $model)
    {


        $user = request()->input('user_name');
        $start_regis_date = request()->input('start_regis_date');
        $end_regis_date = request()->input('end_regis_date');
        $start_lastlogin_date = request()->input('start_lastlogin_date');
        $end_lastlogin_date = request()->input('end_lastlogin_date');
        $start_lasttopup_date = request()->input('start_lasttopup_date');
        $end_lasttopup_date = request()->input('end_lasttopup_date');

        if (empty($start_lastlogin_date)) {
            $start_lastlogin_date = now()->toDateString() . ' 00:00:00';
        }
        if (empty($end_lastlogin_date)) {
            $end_lastlogin_date = now()->toDateString() . ' 23:59:59';
        }


        return $model->newQuery()
            ->with('last_payment')
            ->with(['last_payment' => function ($model) use ($start_lasttopup_date, $end_lasttopup_date) {
                $model->when($start_lasttopup_date, function ($query, $start_lasttopup_date) use ($end_lasttopup_date) {
                    $query->whereBetween('date_topup', array($start_lasttopup_date, $end_lasttopup_date));
                });
            }])
            ->when($user, function ($query, $user) {
                $query->where('members.user_name', $user);
            })
            ->when($start_lastlogin_date, function ($query, $start_lastlogin_date) use ($end_lastlogin_date) {
                $query->whereBetween('lastlogin', array($start_lastlogin_date, $end_lastlogin_date));
            })
            ->when($start_regis_date, function ($query, $start_regis_date) use ($end_regis_date) {
                $query->whereBetween('date_regis', array($start_regis_date, $end_regis_date));
            })
            ->where('enable', 'Y')
            ->select('members.*')->withCount('bank_payments')->withSum('bank_payments:value');


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
                'order' => [[5, 'desc']],
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
            ['data' => 'code', 'name' => 'members.code', 'title' => '#', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_regis', 'name' => 'bills.date_create', 'title' => 'วันที่ลงทะเบียน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'user_name', 'name' => 'bills.pro_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'name', 'name' => 'bills.member_name', 'title' => 'ชื่อสมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'last_login', 'name' => 'members.lastlogin', 'title' => 'เข้าระบบล่าสุดเมื่อ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'last_status', 'name' => 'members.last_seen', 'title' => 'สถานะ', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'refill_cnt', 'name' => 'members.user_name', 'title' => 'จำนวนครั้งที่เติม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'refill_total', 'name' => 'members.user_name', 'title' => 'จำนวนเงินที่เติม', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'refill_last', 'name' => 'members.user_name', 'title' => 'วันที่เติมล่าสุด', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
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
