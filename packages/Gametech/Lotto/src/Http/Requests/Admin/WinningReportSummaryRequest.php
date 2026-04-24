<?php

namespace Gametech\Lotto\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WinningReportSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'round_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'lottery_type' => ['nullable', 'string', 'max:100'],
            'market' => ['nullable', 'string', 'max:100'],
        ];
    }
}
