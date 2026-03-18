<?php

namespace Gametech\Game\Http\Requests;

use Gametech\Game\Contracts\Game;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGameRequest extends FormRequest
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
        return Game::$rules;
    }
}
