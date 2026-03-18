<?php

namespace Gametech\Reward\DataTables;

use Gametech\Reward\Contracts\RewardList;
use Gametech\Reward\Transformers\RewardListTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class RewardListDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new RewardListTransformer);
    }

    /**
     * @return mixed
     */
    public function query(RewardList $model)
    {
        $req = request();

        $status    = trim((string) $req->input('status', ''));
        $startdate = trim((string) $req->input('startDate', ''));
        $enddate   = trim((string) $req->input('endDate', ''));

        // default ช่วงวันนี้ (เหมือนเดิม)
        if ($startdate === '') $startdate = now()->toDateString() . ' 00:00:00';
        if ($enddate === '')   $enddate   = now()->toDateString() . ' 23:59:59';

        $q = $model->newQuery()->select('rewards_list.*');

        // filter: status (รองรับ active/inactive/draft/archived)
        if ($status !== '' && $status !== 'all') {
            $q->where('rewards_list.status', $status);
        }

        // filter: created_at ช่วงเวลา (ใช้ created_at เป็นหลัก)
        // ถ้าคุณอยากให้ filter ตาม start_at/end_at แทน บอกผมได้ ผมจะปรับ logic ให้ตรง business
//        if ($startdate !== '' && $enddate !== '') {
//            $q->whereBetween('rewards_list.created_at', [$startdate, $enddate]);
//        }

        return $q;
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): Builder
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
                'scrollX' => true,
                'paging' => false,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,

                'pageLength' => 50,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows'],
                ],
                'buttons' => [],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    /**
     * Get columns.
     */
    protected function getColumns(): array
    {
        return [
            // PK
            ['data' => 'id', 'name' => 'rewards_list.id', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],

            // Core info
            ['data' => 'is_featured ', 'name' => 'rewards_list.is_featured ', 'title' => 'แนะนำ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'code', 'name' => 'rewards_list.code', 'title' => 'รหัส', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'name', 'name' => 'rewards_list.name', 'title' => 'ชื่อรางวัล', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],

            // Type / mode
            ['data' => 'reward_type', 'name' => 'rewards_list.reward_type', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'fulfillment_mode', 'name' => 'rewards_list.fulfillment_mode', 'title' => 'โหมดจ่าย', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],

            // Cost / reward amounts
            ['data' => 'point_cost', 'name' => 'rewards_list.point_cost', 'title' => 'แต้มที่ใช้', 'orderable' => true, 'searchable' => false, 'className' => 'text-end text-nowrap'],
            ['data' => 'credit_amount', 'name' => 'rewards_list.credit_amount', 'title' => 'เครดิต', 'orderable' => true, 'searchable' => false, 'className' => 'text-end text-nowrap'],
            ['data' => 'gem_amount', 'name' => 'rewards_list.gem_amount', 'title' => 'เพชร', 'orderable' => true, 'searchable' => false, 'className' => 'text-end text-nowrap'],

            // NEW: rule label (ต้องให้ transformer คืนค่า)
            ['data' => 'limit_label', 'name' => 'limit_label', 'title' => 'กติกาแลก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],

            // Availability (ช่วงเปิดแลก)
            ['data' => 'start_at', 'name' => 'rewards_list.start_at', 'title' => 'เริ่ม', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'end_at', 'name' => 'rewards_list.end_at', 'title' => 'สิ้นสุด', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],

            // Stock
            ['data' => 'stock_unlimited', 'name' => 'rewards_list.stock_unlimited', 'title' => 'สต๊อก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'stock', 'name' => 'rewards_list.stock', 'title' => 'ตั้งไว้', 'orderable' => true, 'searchable' => false, 'className' => 'text-end text-nowrap'],
            ['data' => 'reserved_stock', 'name' => 'rewards_list.reserved_stock', 'title' => 'จอง', 'orderable' => true, 'searchable' => false, 'className' => 'text-end text-nowrap'],

            // computed (ต้องให้ transformer คืนค่า)
            ['data' => 'stock_remaining', 'name' => 'stock_remaining', 'title' => 'คงเหลือ', 'orderable' => false, 'searchable' => false, 'className' => 'text-end text-nowrap'],

            // Status
            ['data' => 'status', 'name' => 'rewards_list.status', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],

            // Audit
            ['data' => 'created_at', 'name' => 'rewards_list.created_at', 'title' => 'สร้างเมื่อ', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],

            // Action
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'reward_list_datatable_' . time();
    }
}
