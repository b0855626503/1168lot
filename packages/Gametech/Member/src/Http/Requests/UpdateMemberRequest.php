<?php

namespace Gametech\Member\Http\Requests;

use Gametech\Member\Contracts\Member;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return Member::$rules;

    }
}
