<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\RpSpinTransformer;
use Gametech\Payment\Contracts\BonusSpin;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpSpinDataTable extends DataTable
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
//            ->filter(function ($query) {
//
//                if (request()->input('bonus_name')) {
//                    $query->where('bonus_name', request('bonus_name'));
//                }
//
////                if ($name = request()->input('user_name')) {
////                    $query->whereHas('member', function ($q) use ($name) {
////                        $q->where('user_name', 'like', "%" . $name . "%");
////                    });
////                }
//
//                if ($user = request()->input('user_name')) {
//                    $query->whereIn('bonus_spin.member_code', function ($q)  use ($user){
//                        $q->from('members')->select('members.code')->where('members.user_name', $user);
//                    });
//                }
//
//                if (request()->input('ip')) {
//                    $query->where('ip', 'like', "%" . request('ip') . "%");
//                }
//            })
            ->with('spin', function () use ($query) {
                return core()->currency((clone $query)->sum('amount'));
            })
            ->setTransformer(new RpSpinTransformer);

    }


    /**
     * @param BonusSpin $model
     * @return mixed
     */
    public function query(BonusSpin $model)
    {
        $ip = request()->input('ip');
        $bonus = request()->input('bonus_name');
        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model->newQuery()
            ->active()
            ->select('bonus_spin.*')->with('member')->withCasts([
                'date_create' => 'datetime:Y-m-d H:00'
            ])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            })
            ->when($bonus, function ($query, $bonus) {
                $query->where('bonus_name', $bonus);
            })
            ->when($ip, function ($query, $ip) {
                $query->where('ip', 'like', "%" . $ip . "%");
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('bonus_spin.member_code', function ($q) use ($user) {
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
            ['data' => 'code', 'name' => 'bonus_spin.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_name', 'name' => 'bonus_spin.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'bonus_spin.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'bonus', 'name' => 'bonus_spin.bonus', 'title' => 'รางวัลที่ได้รับ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'reward_type', 'name' => 'bonus_spin.credit', 'title' => 'ประเภทรางวัล', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'amount', 'name' => 'bonus_spin.credit', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'diamond', 'name' => 'bonus_spin.diamond_balance', 'title' => 'เพชรคงเหลือ', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'date_create', 'name' => 'bonus_spin.date_create', 'title' => 'วันที่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'ip', 'name' => 'bonus_spin.ip', 'title' => 'ip', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
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
