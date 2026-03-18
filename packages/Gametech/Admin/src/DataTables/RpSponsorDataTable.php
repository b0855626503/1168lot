<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpSponsorTransformer;
use Gametech\Payment\Contracts\PaymentPromotion;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class RpSponsorDataTable extends DataTable
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
            ->with('deposit', function () use ($query) {
                return core()->currency((clone $query)->sum('amount'));
            })
            ->with('bonus', function () use ($query) {
                return core()->currency((clone $query)->sum('credit_bonus'));
            })
            ->setTransformer(new RpSponsorTransformer);

    }


    /**
     * @param PaymentPromotion $model
     * @return mixed
     */
    public function query(PaymentPromotion $model)
    {
        $ip = request()->input('ip');
        $up_id = request()->input('upline_id');
        $down_id = request()->input('downline_id');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

//        return $model->newQuery()->with('member','down')
//            ->active()->aff()->orderBy('code','desc')
//            ->select('payments_promotion.*')->withCasts([
//                'date_create' => 'datetime:Y-m-d H:00'
//            ])->when($startdate, function ($query, $startdate) use ($enddate) {
//                $query->whereBetween('payments_promotion.date_create', array($startdate, $enddate));
//            });

        return $model->newQuery()->with('member', 'down')
            ->active()->aff()
            ->select('payments_promotion.*')->withCasts([
                'date_create' => 'datetime:Y-m-d H:00'
            ])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('payments_promotion.date_create', array($startdate, $enddate));
            })
            ->when($ip, function ($query, $ip) {
                $query->where('ip', 'like', "%" . $ip . "%");
            })
            ->when($up_id, function ($query, $up_id) {
                $query->whereHas('member', function ($q) use ($up_id) {
                    $q->where('user_name', $up_id);
                });
            })
            ->when($down_id, function ($query, $down_id) {
                $query->whereHas('down', function ($q) use ($down_id) {
                    $q->where('user_name', $down_id);
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
            ['data' => 'code', 'name' => 'payments_promotion.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_create', 'name' => 'payments_promotion.member_name', 'title' => 'วันที่ได้โบนัส', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'up_name', 'name' => 'payments_promotion.user_name', 'title' => 'Name (Upline)', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'up_id', 'name' => 'payments_promotion.bonus', 'title' => 'User ID (Upline)', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'bonus', 'name' => 'payments_promotion.credit_before', 'title' => 'Bonus (Upline)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'down_name', 'name' => 'payments_promotion.credit', 'title' => 'Name (Downline)', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'down_id', 'name' => 'payments_promotion.credit', 'title' => 'User ID (Downline)', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'amount', 'name' => 'payments_promotion.credit_before', 'title' => 'ยอดที่ฝากเข้ามา (Downline)', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
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
