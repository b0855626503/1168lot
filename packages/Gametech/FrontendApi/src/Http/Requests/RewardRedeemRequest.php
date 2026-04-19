<?php

namespace Gametech\FrontendApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RewardRedeemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reward_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'reward_id.required' => 'กรุณาระบุ reward_id',
            'reward_id.integer' => 'reward_id ต้องเป็นตัวเลขเท่านั้น',
            'reward_id.min' => 'reward_id ต้องมากกว่า 0',
        ];
    }
}
