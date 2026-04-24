<?php

namespace Gametech\Lotto\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WinningReportBetsRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
