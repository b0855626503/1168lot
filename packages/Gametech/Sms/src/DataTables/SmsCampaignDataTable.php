<?php

namespace Gametech\Sms\DataTables;

use Carbon\Carbon;
use Gametech\Sms\Contracts\SmsCampaign;
use Gametech\Sms\Transformers\SmsCampaignTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class SmsCampaignDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new SmsCampaignTransformer);
    }

    /**
     * @return mixed
     */
    public function query(SmsCampaign $model)
    {
        // Filters
        $status = request()->input('status');
        $provider = request()->input('provider');
        $audienceMode = request()->input('audience_mode');
        $teamId = request()->input('team_id');
        $keyword = trim((string) request()->input('keyword', ''));

        // Date range: startDate/endDate (รองรับทั้ง Y-m-d และ Y-m-d H:i:s)
        $startDateInput = request()->input('startDate');
        $endDateInput = request()->input('endDate');

        $start = $this->parseDateStart($startDateInput) ?? now()->startOfDay();
        $end = $this->parseDateEnd($endDateInput) ?? now()->endOfDay();

        // Base query
        $q = $model->newQuery()->select('sms_campaigns.*');

        // Apply filters (เฉพาะตอนมีค่า)
        if (!empty($teamId)) {
            $q->where('sms_campaigns.team_id', (int) $teamId);
        }

        if (!empty($status)) {
            $q->where('sms_campaigns.status', $status);
        }

        if (!empty($provider)) {
            $q->where('sms_campaigns.provider', $provider);
        }

        if (!empty($audienceMode)) {
            $q->where('sms_campaigns.audience_mode', $audienceMode);
        }

        if ($keyword !== '') {
            // keyword search: name/description/sender_name/message
            $q->where(function ($qq) use ($keyword) {
                $qq->where('sms_campaigns.name', 'like', "%{$keyword}%")
                    ->orWhere('sms_campaigns.description', 'like', "%{$keyword}%")
                    ->orWhere('sms_campaigns.sender_name', 'like', "%{$keyword}%")
                    ->orWhere('sms_campaigns.message', 'like', "%{$keyword}%");
            });
        }

        // Date filter: ใช้ created_at เป็นหลัก (ชัดเจนสุดสำหรับรายงาน/คิว)
        $q->whereBetween('sms_campaigns.created_at', [$start, $end]);

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

                // เดิมคุณปิด paging/searching ไว้ ผมคงเดิมให้
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
                'buttons' => [
                    // ใส่ปุ่ม export ได้ทีหลัง
                ],
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
            [
                'data' => 'id',
                'name' => 'sms_campaigns.id',
                'title' => '#',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '3%',
            ],

            [
                'data' => 'name',
                'name' => 'sms_campaigns.name',
                'title' => 'ชื่อแคมเปญ',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-left',
            ],

            [
                'data' => 'status',
                'name' => 'sms_campaigns.status',
                'title' => 'สถานะ',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '6%',
            ],

            [
                'data' => 'audience_mode',
                'name' => 'sms_campaigns.audience_mode',
                'title' => 'กลุ่ม',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '7%',
            ],

            [
                'data' => 'provider',
                'name' => 'sms_campaigns.provider',
                'title' => 'Provider',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '7%',
            ],

            [
                'data' => 'message_short',
                'name' => 'sms_campaigns.message',
                'title' => 'ข้อความ',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-left',
            ],

            [
                'data' => 'recipients_total',
                'name' => 'sms_campaigns.total_recipients',
                'title' => 'ผู้รับ',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-right text-nowrap',
                'width' => '6%',
            ],

            [
                'data' => 'delivered_count',
                'name' => 'sms_campaigns.delivered_count',
                'title' => 'Delivered',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-right text-nowrap',
                'width' => '6%',
            ],

            [
                'data' => 'failed_count',
                'name' => 'sms_campaigns.failed_count',
                'title' => 'Failed',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-right text-nowrap',
                'width' => '6%',
            ],

            [
                'data' => 'scheduled_at',
                'name' => 'sms_campaigns.scheduled_at',
                'title' => 'นัดส่ง',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '8%',
            ],

            [
                'data' => 'updated_at',
                'name' => 'sms_campaigns.updated_at',
                'title' => 'อัปเดต',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '8%',
            ],

            [
                'data' => 'action',
                'name' => 'action',
                'title' => 'Action',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '4%',
            ],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'sms_campaigns_datatable_' . time();
    }

    private function parseDateStart($value): ?Carbon
    {
        if (empty($value)) return null;

        try {
            // ถ้าเป็นแค่วันที่ ให้ startOfDay
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                return Carbon::parse($value)->startOfDay();
            }
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseDateEnd($value): ?Carbon
    {
        if (empty($value)) return null;

        try {
            // ถ้าเป็นแค่วันที่ ให้ endOfDay
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                return Carbon::parse($value)->endOfDay();
            }
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
