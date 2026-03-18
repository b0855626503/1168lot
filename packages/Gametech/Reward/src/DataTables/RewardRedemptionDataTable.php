<?php

namespace Gametech\Reward\DataTables;

use Gametech\Reward\Contracts\RewardRedemption;
use Gametech\Reward\Transformers\RewardRedemptionTransformer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class RewardRedemptionDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        // ✅ ใช้ transformer ของ redemption ให้ถูกต้อง
        return $dataTable->setTransformer(new RewardRedemptionTransformer());
    }

    /**
     * Query สำหรับ "รายการแลก" (reward_redemptions) + join rewards_list เพื่อเอาข้อมูลรางวัล
     *
     * Filters:
     * - status: pending|fulfilled|assigned|rejected|canceled|all
     * - startDate, endDate: ช่วง created_at ของ reward_redemptions
     */
    public function query(RewardRedemption $model): EloquentBuilder
    {
        $req = request();

        $status    = trim((string) $req->input('status', ''));
        $startdate = trim((string) $req->input('startDate', ''));
        $enddate   = trim((string) $req->input('endDate', ''));

        // default ช่วงวันนี้ (เหมือนเดิม)
        if ($startdate === '') {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if ($enddate === '') {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        // ✅ แกนหลักต้องเป็น reward_redemptions
        $q = $model->newQuery()
            ->from('reward_redemptions')
            ->leftJoin('rewards_list', 'rewards_list.id', '=', 'reward_redemptions.reward_id')

            // ✅ NEW: join members เพื่อดึงข้อมูล "ผู้แลก"
            // member_id ของคุณคือ members.code (จาก controller ใช้ code เป็น memberCode)
            ->leftJoin('members', 'members.code', '=', 'reward_redemptions.member_id')

            ->select([
                // redemption fields
                'reward_redemptions.id',
                'reward_redemptions.reward_id',
                'reward_redemptions.member_id',
                'reward_redemptions.status',
                'reward_redemptions.fulfilled_at',
                'reward_redemptions.handled_by',
                'reward_redemptions.idempotency_key',
                'reward_redemptions.created_at',
                'reward_redemptions.updated_at',

                // snapshots (สำคัญเพราะเป็นหลักฐาน ณ ตอนแลก)
                'reward_redemptions.reward_code_snapshot',
                'reward_redemptions.reward_name_snapshot',
                'reward_redemptions.point_cost_snapshot',
                'reward_redemptions.reward_type_snapshot',
                'reward_redemptions.fulfillment_mode_snapshot',
                'reward_redemptions.credit_amount_snapshot',
                'reward_redemptions.gem_amount_snapshot',

                // reward fields (ปัจจุบัน) เอาไว้แสดง/อ้างอิง
                'rewards_list.is_featured as reward_is_featured',
                'rewards_list.priority as reward_priority',
                'rewards_list.status as reward_status',
                'rewards_list.start_at as reward_start_at',
                'rewards_list.end_at as reward_end_at',
                'rewards_list.stock_unlimited as reward_stock_unlimited',
                'rewards_list.stock as reward_stock',
                'rewards_list.reserved_stock as reward_reserved_stock',
                'rewards_list.limit_type as reward_limit_type',
                'rewards_list.limit_per_user as reward_limit_per_user',
                'rewards_list.limit_period as reward_limit_period',
                'rewards_list.limit_per_period as reward_limit_per_period',
                'rewards_list.strict_limit as reward_strict_limit',
                'rewards_list.limit_total as reward_limit_total',
                'rewards_list.cooldown_minutes as reward_cooldown_minutes',

                // ✅ NEW: member fields (ผู้แลก)
                'members.code as member_code',
                'members.user_name as member_username',
                'members.name as member_name',
                'members.tel as member_tel',
            ]);

        // ✅ filter: status (ของ redemption)
        if ($status !== '' && $status !== 'all') {
            $q->where('reward_redemptions.status', $status);
        }

        // ✅ filter: created_at ของ redemption (ไม่ใช่ rewards_list)
        if ($startdate !== '' && $enddate !== '') {
            $q->whereBetween('reward_redemptions.created_at', [$startdate, $enddate]);
        }

        // ✅ order default (ให้สอดคล้องกับ html() ที่ order desc)
        $q->orderByDesc('reward_redemptions.id');

        return $q;
    }


    /**
     * Optional method if you want to use html builder.
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

                // ✅ สำคัญ: ถ้าจะใช้ paging จริง ให้เปิด (เดิมคุณปิดไว้แต่ตั้ง pageLength ไว้เยอะ)
                // ถ้าคุณ "ตั้งใจ" ไม่ให้ paginate ให้คง false ได้ แต่ส่วนใหญ่ทีมงานต้อง paginate
                'paging' => true,

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
            // PK (redemption id)
            ['data' => 'id', 'name' => 'reward_redemptions.id', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'created_at', 'name' => 'reward_redemptions.created_at', 'title' => 'วันที่แลกรางวัล', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],

            [
                'data' => 'member',
                'name' => 'members.code',
                'title' => 'แลกโดย',
                'orderable' => false,
                'searchable' => true,
                'className' => 'text-left text-nowrap'
            ],
            // featured ของ reward (alias)
//            ['data' => 'reward_is_featured', 'name' => 'rewards_list.is_featured', 'title' => 'แนะนำ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],

            // reward snapshot (แนะนำให้ใช้ snapshot เพื่อไม่ให้ชื่อรางวัลเปลี่ยนย้อนหลังแล้วงง)
//            ['data' => 'reward_code_snapshot', 'name' => 'reward_redemptions.reward_code_snapshot', 'title' => 'รหัส', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'reward_name_snapshot', 'name' => 'reward_redemptions.reward_name_snapshot', 'title' => 'ชื่อรางวัล', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],

            // type / mode snapshot
            ['data' => 'reward_type_snapshot', 'name' => 'reward_redemptions.reward_type_snapshot', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
//            ['data' => 'fulfillment_mode_snapshot', 'name' => 'reward_redemptions.fulfillment_mode_snapshot', 'title' => 'โหมดจ่าย', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],

            // cost / amounts snapshot
            ['data' => 'point_cost_snapshot', 'name' => 'reward_redemptions.point_cost_snapshot', 'title' => 'แต้มที่ใช้', 'orderable' => true, 'searchable' => false, 'className' => 'text-end text-nowrap'],
            ['data' => 'credit_amount_snapshot', 'name' => 'reward_redemptions.credit_amount_snapshot', 'title' => 'เครดิต', 'orderable' => true, 'searchable' => false, 'className' => 'text-end text-nowrap'],
            ['data' => 'gem_amount_snapshot', 'name' => 'reward_redemptions.gem_amount_snapshot', 'title' => 'เพชร', 'orderable' => true, 'searchable' => false, 'className' => 'text-end text-nowrap'],

            // rule label / stock remaining (computed ใน transformer)
            ['data' => 'limit_label', 'name' => 'limit_label', 'title' => 'กติกาแลก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'stock_remaining', 'name' => 'stock_remaining', 'title' => 'คงเหลือ', 'orderable' => false, 'searchable' => false, 'className' => 'text-end text-nowrap'],

            // redemption status
            ['data' => 'status', 'name' => 'reward_redemptions.status', 'title' => 'สถานะงาน', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],

            // created / fulfilled
//            ['data' => 'created_at', 'name' => 'reward_redemptions.created_at', 'title' => 'แลกเมื่อ', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'fulfilled_at', 'name' => 'reward_redemptions.fulfilled_at', 'title' => 'สำเร็จเมื่อ', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],

            // Action
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'reward_redemption_datatable_' . time();
    }
}
