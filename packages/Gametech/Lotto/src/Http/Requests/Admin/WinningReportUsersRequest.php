<?php

namespace Gametech\Lotto\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WinningReportUsersRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
