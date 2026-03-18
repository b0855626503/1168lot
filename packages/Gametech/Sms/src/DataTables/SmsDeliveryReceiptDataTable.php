<?php

namespace Gametech\Sms\DataTables;

use Carbon\Carbon;
use Gametech\Sms\Contracts\SmsDeliveryReceipt;
use Gametech\Sms\Transformers\SmsDeliveryReceiptTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class SmsDeliveryReceiptDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new SmsDeliveryReceiptTransformer);
    }

    /**
     * Query สำหรับ DLR log จริง ๆ (sms_delivery_receipts)
     */
    public function query(SmsDeliveryReceipt $model)
    {
        // Filters
        $teamId = request()->input('team_id');
        $provider = request()->input('provider');
        $status = request()->input('status');

        $campaignId = request()->input('campaign_id');
        $recipientId = request()->input('recipient_id');

        $processStatus = request()->input('process_status'); // pending/processed/failed/ignored ฯลฯ
        $keyword = trim((string) request()->input('keyword', ''));

        // Date range: startDate/endDate
        $startDateInput = request()->input('startDate');
        $endDateInput = request()->input('endDate');

        $start = $this->parseDateStart($startDateInput) ?? now()->startOfDay();
        $end = $this->parseDateEnd($endDateInput) ?? now()->endOfDay();

        // เลือก field เวลา (default: received_at เหมาะกับ DLR)
        $dateField = (string) request()->input('date_field', 'received_at');
        if (! in_array($dateField, ['received_at', 'processed_at', 'created_at', 'updated_at'], true)) {
            $dateField = 'received_at';
        }

        // Base query
        $q = $model->newQuery()->select('sms_delivery_receipts.*');

        if (! empty($teamId)) {
            $q->where('sms_delivery_receipts.team_id', (int) $teamId);
        }

        if (! empty($provider)) {
            $q->where('sms_delivery_receipts.provider', $provider);
        }

        if (! empty($status)) {
            $q->where('sms_delivery_receipts.status', $status);
        }

        if (! empty($campaignId)) {
            $q->where('sms_delivery_receipts.campaign_id', (int) $campaignId);
        }

        if (! empty($recipientId)) {
            $q->where('sms_delivery_receipts.recipient_id', (int) $recipientId);
        }

        if (! empty($processStatus)) {
            $q->where('sms_delivery_receipts.process_status', $processStatus);
        }

        if ($keyword !== '') {
            // keyword search สำหรับ DLR
            $q->where(function ($qq) use ($keyword) {
                $qq->where('sms_delivery_receipts.message_id', 'like', "%{$keyword}%")
                    ->orWhere('sms_delivery_receipts.msisdn', 'like', "%{$keyword}%")
                    ->orWhere('sms_delivery_receipts.to', 'like', "%{$keyword}%")
                    ->orWhere('sms_delivery_receipts.err_code', 'like', "%{$keyword}%")
                    ->orWhere('sms_delivery_receipts.api_key', 'like', "%{$keyword}%");
            });
        }

        // Date filter
        $q->whereBetween("sms_delivery_receipts.$dateField", [$start, $end]);

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

                // คง behavior เดิมของคุณไว้
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
     * Columns สำหรับ DLR log
     */
    protected function getColumns(): array
    {
        return [
            [
                'data' => 'id',
                'name' => 'sms_delivery_receipts.id',
                'title' => '#',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '3%',
            ],

            [
                'data' => 'provider',
                'name' => 'sms_delivery_receipts.provider',
                'title' => 'Provider',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '7%',
            ],

            [
                'data' => 'status',
                'name' => 'sms_delivery_receipts.status',
                'title' => 'DLR Status',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '8%',
            ],

            [
                'data' => 'message_id',
                'name' => 'sms_delivery_receipts.message_id',
                'title' => 'Message ID',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-left',
            ],

            [
                'data' => 'msisdn',
                'name' => 'sms_delivery_receipts.msisdn',
                'title' => 'Mobile',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-left text-nowrap',
                'width' => '10%',
            ],

            [
                'data' => 'to',
                'name' => 'sms_delivery_receipts.to',
                'title' => 'Sender ID',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-left text-nowrap',
                'width' => '7%',
            ],

            [
                'data' => 'err_code',
                'name' => 'sms_delivery_receipts.err_code',
                'title' => 'Err',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '5%',
            ],

            [
                'data' => 'scts',
                'name' => 'sms_delivery_receipts.scts',
                'title' => 'SCTS',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '7%',
            ],

            [
                'data' => 'campaign_id',
                'name' => 'sms_delivery_receipts.campaign_id',
                'title' => 'Campaign',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '6%',
            ],

            [
                'data' => 'recipient_id',
                'name' => 'sms_delivery_receipts.recipient_id',
                'title' => 'Recipient',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '6%',
            ],

            [
                'data' => 'received_at',
                'name' => 'sms_delivery_receipts.received_at',
                'title' => 'Received',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '9%',
            ],

            [
                'data' => 'processed_at',
                'name' => 'sms_delivery_receipts.processed_at',
                'title' => 'Processed',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '9%',
            ],

            [
                'data' => 'process_status',
                'name' => 'sms_delivery_receipts.process_status',
                'title' => 'Proc',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center text-nowrap',
                'width' => '6%',
            ],

        ];
    }

    protected function filename(): string
    {
        return 'sms_delivery_receipts_datatable_' . time();
    }

    private function parseDateStart($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
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
        if (empty($value)) {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                return Carbon::parse($value)->endOfDay();
            }

            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
