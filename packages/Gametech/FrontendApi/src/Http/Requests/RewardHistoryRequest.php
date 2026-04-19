<?php

namespace Gametech\FrontendApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RewardHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'q' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:pending,fulfilled,rejected,cancelled'],
            'reward_type' => ['sometimes', 'string', 'max:50'],
            'mode' => ['sometimes', 'string', 'in:auto,manual,approval'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'หน้าไม่ถูกต้อง',
            'page.min' => 'หน้าไม่ถูกต้อง',
            'per_page.integer' => 'จำนวนรายการต่อหน้าไม่ถูกต้อง',
            'per_page.min' => 'จำนวนรายการต่อหน้าต้องมากกว่า 0',
            'per_page.max' => 'จำนวนรายการต่อหน้าสูงสุดคือ 50',
            'q.string' => 'คำค้นหาไม่ถูกต้อง',
            'status.in' => 'สถานะที่ร้องขอไม่ถูกต้อง',
            'mode.in' => 'โหมดการจ่ายรางวัลไม่ถูกต้อง',
        ];
    }
}
