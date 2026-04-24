<?php

namespace Gametech\Lotto\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WinningReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'round_id' => ['required', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'bet_type' => ['nullable', 'string', 'max:100'],
            'number' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', Rule::in(['pending', 'settled', 'credited', 'failed', 'voided'])],
            'level' => ['required', Rule::in(['summary', 'users', 'bets'])],
            'format' => ['required', Rule::in(['csv', 'xlsx'])],
        ];
    }
}
