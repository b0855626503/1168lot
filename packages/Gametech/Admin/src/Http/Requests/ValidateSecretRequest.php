<?php

namespace Gametech\Admin\Http\Requests;

use App\Http\Requests\Request;
use Exception;

use Gametech\Admin\Models\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Factory as ValidatonFactory;

class ValidateSecretRequest extends Request
{

    /**
     * @var Admin
     */
    private $user;

    /**
     * ValidateSecretRequest constructor.
     * @param ValidatonFactory $factory
     */
    public function __construct(ValidatonFactory $factory)
    {


        $factory->extend(
            'valid_token',
            function ($attribute, $value, $parameters, $validator) {

                $google2fa = app('pragmarx.google2fa');
                $secret = $google2fa->generateSecretKey(32);
                dd($secret);

                return $google2fa->verifyKey($secret, $value);
            },
            'Not a valid token'
        );

    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */

    public function authorize()
    {
        try {
            $this->user = Admin::findOrFail(
                session('google2fa')
            );
        } catch (Exception $exc) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'one_time_password' => 'bail|required|digits:6|valid_token|used_token',
        ];
    }
}
