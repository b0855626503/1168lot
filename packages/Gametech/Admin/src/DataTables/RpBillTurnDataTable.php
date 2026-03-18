<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpBillTurnTransformer;
use Gametech\Game\Contracts\GameUser;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpBillTurnDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
//            ->skipTotalRecords()
//            ->setTotalRecords(0)
//            ->filter(function ($query) {
//                $query->join('view_maxbill', function($join)
//                {
//                    $join->on('games_user.game_code', '=', 'view_maxbill.game_code');
//                    $join->on('games_user.member_code', '=', 'view_maxbill.member_code');
////                    $join->on('bills.transfer_type','=',1);
////                    $join->on('bills.enable','=','Y');
//
//                });
//            })
            ->setTransformer(new RpBillTurnTransformer);

    }


    /**
     * @param GameUser $model
     * @return mixed
     */
    public function query(GameUser $model)
    {

        $ip = request()->input('ip');
        $game = request()->input('game_code');
        $type = request()->input('type');
        $pro = request()->input('pro_code');
        $turn = request()->input('turn');
        $enable = request()->input('enable');
        $user = request()->input('user_name');


        return $model->newQuery()
            ->with(['membernew', 'game', 'promotion', 'billcode'])
            ->select('games_user.*')
            ->when($game, function ($query, $game) {
                $query->where('games_user.game_code', $game);
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('games_user.member_code', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
                });
            });


    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return Builder
     */
    public function html(): Builder
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
                    ['targets' => '_all', 'className' => 'text-center text-nowrap']
                ]
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        return [
            ['data' => 'code', 'name' => 'games_user.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'member_name', 'name' => 'bills.member_name', 'title' => 'สมาชิก', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'user_name', 'name' => 'membernew.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'game', 'name' => 'bills.user_name', 'title' => 'เกมส์', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'pro_name', 'name' => 'bills.user_name', 'title' => 'โปรโมชั่นล่าสุด', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'bonus', 'name' => 'bills.credit', 'title' => 'ได้รับโบนัส', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'amount', 'name' => 'bills.credit', 'title' => 'จำนวนเงิน', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'bonus', 'name' => 'bills.pro_name', 'title' => 'โบนัสที่ได้', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'total', 'name' => 'bills.credit_balance', 'title' => 'รวมได้รับ', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'turn', 'name' => 'bills.credit_balance', 'title' => 'ยอดเทินรวม', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'min', 'name' => 'bills.credit_balance', 'title' => 'อัตราอั้นถอน (เท่า)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//            ['data' => 'limit', 'name' => 'bills.credit_balance', 'title' => 'จำกัดยอดถอนได้', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'date_create', 'name' => 'bills.date_create', 'title' => 'วันที่ทำรายการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
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
