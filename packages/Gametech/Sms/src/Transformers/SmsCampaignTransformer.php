<?php

namespace Gametech\Sms\Transformers;

use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class SmsCampaignTransformer extends TransformerAbstract
{
    /**
     * Fractal transformer ต้องมี method transform($item): array
     */
    public function transform($item): array
    {
        // กัน schema บางที่ยังใช้ description เป็นชื่อหลัก
        $name = $item->name ?? $item->description ?? '-';

        return [
            'id' => (int) $item->id,
            'name' => e($name),

            'status' => e((string) ($item->status ?? 'draft')),
            'audience_mode' => e((string) ($item->audience_mode ?? '-')),
            'provider' => e((string) ($item->provider ?? 'vonage')),

            'message_short' => e(Str::limit((string) ($item->message ?? ''), 80)),

            'recipients_total' => (int) ($item->total_recipients ?? 0),
            'delivered_count' => (int) ($item->delivered_count ?? 0),
            'failed_count' => (int) ($item->failed_count ?? 0),

            'scheduled_at' => $item->scheduled_at ? (string) $item->scheduled_at : '-',
            'updated_at' => $item->updated_at ? (string) $item->updated_at : '-',

            'action' => $this->actionButtons($item),
        ];
    }

    private function actionButtons($item): string
    {
        $id = (int) $item->id;

        // ปุ่มเรียก Vue เหมือนแพตเทิร์นระบบคุณ
        return '
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary"
                    onclick="window.app && window.app.editModal && window.app.editModal(' . $id . ')">
                    แก้ไข
                </button>
                <button type="button" class="btn btn-outline-danger"
                    onclick="window.app && window.app.delModal && window.app.delModal(' . $id . ')">
                    ลบ
                </button>
            </div>
        ';
    }
}
