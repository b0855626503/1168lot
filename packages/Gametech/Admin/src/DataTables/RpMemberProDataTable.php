<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpMemberProTransformer;
use Gametech\Admin\Transformers\RpTopProTransformer;
use Gametech\Member\Models\Member;
use Gametech\Payment\Contracts\Bill;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpMemberProDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {
//        $promotion = Bill::query()->select('pro_code')->distinct()->get();


        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new RpMemberProTransformer);

    }


    /**
     * @param Bill $model
     * @return mixed
     */
    public function query(Member $model)
    {

        $ip = request()->input('ip');
        $game = request()->input('game_code');
        $type = request()->input('transfer_type');
        $pro = request()->input('pro');
        $enable = request()->input('enable');
        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');

        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }
//
//        return $model->newQuery()
//            ->select('members.*')
//            ->where('enable','Y')
//            ->whereNotExists(function($query)
//            {
//                $query->select(DB::raw(1))
//                    ->from('DismissedRequest')
//                    ->whereRaw('RepairJob.id = DismissedRequest.id');
//            })->get();
//
        if ($pro == 'Y') {

            $data = $model->newQuery()
                ->select('members.*')
                ->where('enable', 'Y')
                ->whereExists(function ($query) use ($startdate, $enddate) {
                    $query->select('member_code')
                        ->from('bills')
                        ->where('pro_code', '!=', 0)
                        ->whereColumn('member_code', 'members.code')
                        ->when($startdate, function ($query, $startdate) use ($enddate) {
                            $query->whereBetween('bills.date_create', array($startdate, $enddate));
                        });
//                    ->whereColumn('bills.member_code', 'members.code')->get();
                })->orderByDesc('members.code');

        } else {

            $payment = DB::table('bank_payment')->distinct()->select('bank_payment.member_topup')
                ->where('bank_payment.enable', 'Y')
                ->where('bank_payment.status', 1)
                ->where('bank_payment.bankstatus', 1)
                ->where('bank_payment.value', '<>', 0)
                ->when($startdate, function ($query, $startdate) use ($enddate) {
                    $query->whereBetween('bank_payment.date_approve', array($startdate, $enddate));
                })->groupBy('bank_payment.member_topup')
                ->orderByDesc('bank_payment.member_topup');

            $data = $model->newQuery()
//                ->with(['bank_payments' => function ($model) use ($startdate, $enddate) {
//                    $model->when($startdate, function ($model, $startdate) use ($enddate) {
//                        $model->whereBetween('date_approve', array($startdate, $enddate));
//                    });
//                }])
//                ->with(['bank_payments'])

                ->select('members.*')
                ->where('enable', 'Y')
                ->joinSub($payment, 'bank_payment', function ($join) {
                    $join->on('bank_payment.member_topup', '=', 'members.code');

                })
                ->whereNotExists(function ($model) use ($startdate, $enddate) {
                    $model->select('member_code')
                        ->from('bills')
                        ->where('pro_code', '!=', 0)
                        ->whereColumn('member_code', 'members.code')
                        ->when($startdate, function ($model, $startdate) use ($enddate) {
                            $model->whereBetween('bills.date_create', array($startdate, $enddate));
                        });
//                    ->whereColumn('bills.member_code', 'members.code')->get();
                })->orderByDesc('members.code');


        }


//            $bill = DB::table('bills')->distinct()->select('bills.member_code')
//                ->when($startdate, function ($query, $startdate) use ($enddate) {
//                    $query->whereBetween('bills.date_create', array($startdate, $enddate));
//                });
//
//            $data = $data->intersect($bill);


        return $data;


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
                'ordering' => false,
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
            ['data' => 'code', 'name' => 'members.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'user_name', 'name' => 'members.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'firstname', 'name' => 'members.firstname', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'lastname', 'name' => 'members.lastname', 'title' => 'นามสกุล', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],

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
