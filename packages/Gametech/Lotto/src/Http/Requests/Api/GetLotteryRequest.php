<?php

namespace Gametech\Lotto\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GetLotteryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'กรุณาระบุ lottery type',
            'type.string' => 'lottery type ต้องเป็นข้อความ',
            'type.max' => 'lottery type ยาวเกินกำหนด',
            'date.required' => 'กรุณาระบุ business date',
            'date.date_format' => 'business date ต้องอยู่ในรูปแบบ Y-m-d',
        ];
    }
}
