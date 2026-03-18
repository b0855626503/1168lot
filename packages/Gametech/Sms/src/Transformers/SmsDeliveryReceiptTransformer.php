<?php

namespace Gametech\Sms\Transformers;

use League\Fractal\TransformerAbstract;

class SmsDeliveryReceiptTransformer extends TransformerAbstract
{
    /**
     * Fractal transformer ต้องมี method transform($item): array
     */
    public function transform($item): array
    {
        return [
            'id' => (int) $item->id,

            'provider' => e((string) ($item->provider ?? '-')),
            'status' => e((string) ($item->status ?? '-')),

            'message_id' => e((string) ($item->message_id ?? '-')),
            'msisdn' => e((string) ($item->msisdn ?? '-')),
            'to' => e((string) ($item->to ?? '-')),

            'err_code' => e((string) ($item->err_code ?? '')),
            'scts' => e((string) ($item->scts ?? '')),

            'campaign_id' => (int) ($item->campaign_id ?? 0),
            'recipient_id' => (int) ($item->recipient_id ?? 0),

            'received_at' => $item->received_at ? (string) $item->received_at : '-',
            'processed_at' => $item->processed_at ? (string) $item->processed_at : '-',

            'process_status' => e((string) ($item->process_status ?? '-')),

        ];
    }

    private function actionButtons($item): string
    {
        $id = (int) $item->id;

        // ปุ่มเรียก Vue เหมือนแพตเทิร์นระบบคุณ
        // ตั้งชื่อเป็น view / payload แทน "แก้ไข" เพราะ DLR log ปกติไม่ควรถูกแก้
        return '
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary"
                    onclick="window.app && window.app.viewModal && window.app.viewModal(' . $id . ')">
                    ดู
                </button>
                <button type="button" class="btn btn-outline-danger"
                    onclick="window.app && window.app.delModal && window.app.delModal(' . $id . ')">
                    ลบ
                </button>
            </div>
        ';
    }
}
