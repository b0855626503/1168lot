<?php

namespace Gametech\Admin\Transformers;

use Gametech\Core\Contracts\Log;
use League\Fractal\TransformerAbstract;

class RpLogTransformer extends TransformerAbstract
{
    public function transform(Log $model)
    {
        $user_name = $model->menu === 'members'
            ? ($model->user ? $model->user->user_name : '')
            : $model->record;

        return [
            'code'         => (int) $model->code,
            'mode'         => $model->mode,
            'menu'         => $model->menu,
            'emp'          => ($model->admin ? $model->admin->user_name : '-'),
            'record'       => $model->record,
            'user_name'    => $user_name,
            // ⬇⬇ แสดง JSON แบบสวย + กล่องเล็กมีสกอลล์
            'item_before'  => $this->prettyBox($model->item_before),
            'item'         => $this->prettyBox($model->item),
            'ip'           => $model->ip,
            'date_create'  => core()->formatDate($model->date_create,'Y-m-d H:i:s'),
        ];
    }

    /**
     * สร้างกล่อง <pre> สำหรับ JSON:
     * - แปลงเป็น pretty JSON ถ้า decode ได้
     * - จำกัดขนาดข้อความรวม (bytes/ตัวอักษร) กันหนัก
     * - escape HTML กัน XSS
     */
    private function prettyBox(?string $raw, int $maxChars = 4000): string
    {
        $raw = (string) $raw;

        // พยายาม decode
        $pretty = $raw;
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $pretty = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // ตัดความยาวสูงสุด (เพื่อกัน payload มหึมา)
        if (mb_strlen($pretty) > $maxChars) {
            $pretty = mb_substr($pretty, 0, $maxChars) . "…";
        }

        // escape
        $escaped = htmlspecialchars($pretty, ENT_QUOTES, 'UTF-8');

        // คืน HTML พร้อมคลาสสำหรับจัดสไตล์
        return '<pre class="json-pretty" title="คลิกเพื่อเลือก/คัดลอก">' . $escaped . '</pre>';
    }
}
