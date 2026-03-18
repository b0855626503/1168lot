<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\CouponTransformer;
use Gametech\Core\Contracts\Coupon;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class CouponDataTable extends DataTable
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
            // ไม่ต้อง addColumn total_* ซ้ำ เพราะจะให้ Transformer จัดการทั้งหมด
            ->setTransformer(new CouponTransformer());
    }

    /**
     * @param Coupon $model
     * @return mixed
     */
    public function query(Coupon $model)
    {
        return $model->newQuery()
            // จำนวนคูปองทั้งหมดที่ถูก gen
            ->withCount('couponlists')
            // จำนวนคูปองที่ยังไม่ถูกใช้ (status = 'N')
            ->withCount([
                'couponlists as coupon_unused_count' => function ($q) {
                    $q->where('status', 'N');
                },
            ]);
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
                'responsive' => false,
                'stateSave' => true,
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => false,
                'scrollX' => true,
                'order' => [[0, 'desc']],
                'buttons' => [
                    'pageLength',
                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
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
            [
                'data' => 'code',
                'name' => 'coupons.code',
                'title' => '#',
                'orderable' => true,
                'searchable' => true,
                'className' => 'text-center text-nowrap',
            ],
            [
                'data' => 'name',
                'name' => 'coupons.name',
                'title' => 'ชื่อรายการ',
                'orderable' => false,
                'searchable' => true,
                'className' => 'text-left text-nowrap',
            ],
            [
                'data' => 'cashback',
                'name' => 'coupons.cashback',
                'title' => 'ประเภทคูปอง',
                'orderable' => false,
                'searchable' => true,
                'className' => 'text-center text-nowrap',
            ],
            [
                'data' => 'amount',
                'name' => 'coupons.amount',
                'title' => 'จำนวน',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
            ],
            [
                'data' => 'value',
                'name' => 'coupons.value',
                'title' => 'เครดิตที่ได้',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
            ],
            [
                'data' => 'turnpro',
                'name' => 'coupons.turnpro',
                'title' => 'ค่าเทิร์น',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
            ],
            [
                'data' => 'amount_limit',
                'name' => 'coupons.amount_limit',
                'title' => 'อั้นถอน',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
            ],
            [
                'data' => 'date_start',
                'name' => 'coupons.date_start',
                'title' => 'วันที่เริ่ม',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
            ],
            [
                'data' => 'date_stop',
                'name' => 'coupons.date_stop',
                'title' => 'วันที่สิ้นสุด',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
            ],
            [
                'data' => 'enable',
                'name' => 'coupons.enable',
                'title' => 'สถานะ',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
            ],
            [
                'data' => 'gen',
                'name' => 'coupons.gen',
                'title' => 'Gen',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
            ],

            // === คอลัมน์สรุปจำนวนคูปอง ===
            [
                'data' => 'total_generated',
                'name' => 'couponlists_count',      // ใช้ชื่อ column จริงที่มาจาก withCount
                'title' => 'จำนวนที่สร้างทั้งหมด',
                'orderable' => false,               // ถ้าอยากให้ sort ได้ ค่อยเปลี่ยนเป็น true
                'searchable' => false,
                'className' => 'text-center text-nowrap',
            ],
            [
                'data' => 'total_unused',
                'name' => 'coupon_unused_count',    // column จริงจาก withCount as ...
                'title' => 'จำนวนที่ยังไม่ใช้',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
            ],
            // ===============================

            [
                'data' => 'action',
                'name' => 'action',
                'title' => 'Action',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '3%',
            ],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'coupon_datatable_' . time();
    }
}
