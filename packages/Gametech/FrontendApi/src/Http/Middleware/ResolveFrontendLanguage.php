<?php

namespace Gametech\FrontendApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResolveFrontendLanguage
{
    public function handle(Request $request, Closure $next)
    {
        $language = $this->resolveLanguage($request);

        app()->setLocale($language);

        $request->attributes->set('frontend_language', $language);
        $request->merge([
            'language' => $language,
            'lang' => $language,
            'locale' => $language,
        ]);

        return $next($request);
    }

    private function resolveLanguage(Request $request): string
    {
        $fallback = 'th';
        $available = array_keys((array) config('languages.available', []));
        if (empty($available)) {
            $available = [$fallback];
        }

        $candidate = $request->input('language')
            ?? $request->input('lang')
            ?? $request->input('locale')
            ?? $request->header('X-Language')
            ?? $this->parseAcceptLanguage((string) $request->header('Accept-Language', ''));

        $candidate = strtolower(trim((string) $candidate));

        if (! in_array($candidate, $available, true)) {
            return in_array($fallback, $available, true) ? $fallback : (string) $available[0];
        }

        return $candidate;
    }

    private function parseAcceptLanguage(string $header): ?string
    {
        if ($header === '') {
            return null;
        }

        $first = strtolower(trim((string) explode(',', $header)[0]));
        if ($first === '') {
            return null;
        }

        return (string) explode('-', $first)[0];
    }
}

