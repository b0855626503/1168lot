<?php

namespace Gametech\FrontendApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackMarketingClickRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'registration_code' => ['required', 'string', 'max:40'],
            'visitor_id' => ['nullable', 'string', 'max:64'],
            'session_id' => ['nullable', 'string', 'max:128'],
            'landing_url' => ['nullable', 'url', 'max:2048'],
            'referrer' => ['nullable', 'url', 'max:2048'],
            'utm_source' => ['nullable', 'string', 'max:191'],
            'utm_medium' => ['nullable', 'string', 'max:191'],
            'utm_campaign' => ['nullable', 'string', 'max:191'],
            'utm_content' => ['nullable', 'string', 'max:191'],
            'utm_term' => ['nullable', 'string', 'max:191'],
        ];
    }
}
