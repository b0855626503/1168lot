<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Wallet\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;

class BaseController extends AppBaseController
{
    protected function tokenPayload(Request $request): array
    {
        return (array) $request->attributes->get('frontend_api_token_payload', []);
    }

    protected function requestLanguage(Request $request): string
    {
        $language = strtolower((string) $request->attributes->get('frontend_language', 'th'));
        $available = array_keys((array) config('languages.available', []));

        if (empty($available)) {
            return 'th';
        }

        return in_array($language, $available, true) ? $language : (in_array('th', $available, true) ? 'th' : (string) $available[0]);
    }
}
