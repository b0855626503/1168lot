<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Wallet\Http\Controllers\AppBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BaseController extends AppBaseController
{
    /**
     * @var string[]
     */
    private array $mediaStorageFolders = [
        'game_img/',
        'icon_img/',
        'slide_img/',
        'promotion_img/',
        'procontent_img/',
        'bank_img/',
        'bank_qr/',
        'spin_img/',
        'reward_img/',
        'lotto/',
    ];

    public function sendResponse($result, $message): JsonResponse
    {
        return $this->normalizeJsonResponseImages(parent::sendResponse($result, $message));
    }

    public function sendResponseNew($result, $message, $code = 200): JsonResponse
    {
        return $this->normalizeJsonResponseImages(parent::sendResponseNew($result, $message, $code));
    }

    public function sendResponseFail($result, $message, $code = 200): JsonResponse
    {
        $payload = is_array($result) ? $result : ['data' => $result];
        $payload['success'] = false;
        $payload['message'] = $message;

        return $this->normalizeJsonResponseImages(response()->json($payload, $code));
    }

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

    protected function normalizeJsonResponseImages(JsonResponse $response): JsonResponse
    {
        $payload = json_decode((string) $response->getContent(), true);
        if (! is_array($payload)) {
            return $response;
        }

        $normalized = $this->normalizeImageFieldsRecursive($payload);
        $response->setData($normalized);

        return $response;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function normalizeImageFieldsRecursive($value, ?string $key = null, ?string $parentKey = null)
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $childKey => $childValue) {
                $normalized[$childKey] = $this->normalizeImageFieldsRecursive(
                    $childValue,
                    is_string($childKey) ? $childKey : null,
                    $key
                );
            }

            return $normalized;
        }

        if (! is_string($value) || ! $this->shouldNormalizeImageValue($key, $parentKey)) {
            return $value;
        }

        return $this->absoluteMediaUrl($value);
    }

    protected function shouldNormalizeImageValue(?string $key, ?string $parentKey): bool
    {
        $current = strtolower((string) $key);
        $parent = strtolower((string) $parentKey);

        $directKeys = [
            'image', 'img', 'filepic', 'icon', 'logo', 'thumbnail', 'avatar',
            'banner', 'market_logo', 'market_icon', 'bank_pic', 'qr_pic',
            'logourl', 'logomobileurl', 'logotransparenturl',
        ];

        if (in_array($current, $directKeys, true)) {
            return true;
        }

        if (
            str_ends_with($current, '_image') ||
            str_ends_with($current, '_img') ||
            str_ends_with($current, '_icon') ||
            str_ends_with($current, '_logo') ||
            str_ends_with($current, '_banner') ||
            str_ends_with($current, '_avatar') ||
            str_ends_with($current, '_thumbnail')
        ) {
            return true;
        }

        $imageContainers = ['image', 'images', 'providerlogo', 'provider_logo', 'media'];
        $containerKeys = ['vertical', 'horizontal', 'banner', 'src', 'url', 'logo', 'icon', 'thumbnail'];

        return in_array($parent, $imageContainers, true) && in_array($current, $containerKeys, true);
    }

    protected function absoluteMediaUrl(string $value): string
    {
        $path = trim($value);
        if ($path === '') {
            return '';
        }

        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://') ||
            str_starts_with($path, 'data:')
        ) {
            return $this->appendMediaCacheBust($path);
        }

        if (str_starts_with($path, '//')) {
            return $this->appendMediaCacheBust(request()->getScheme() . ':' . $path);
        }

        if (str_starts_with($path, '/')) {
            return $this->appendMediaCacheBust(url($path));
        }

        if (str_starts_with($path, 'storage/')) {
            return $this->appendMediaCacheBust(url('/' . $path));
        }

        foreach ($this->mediaStorageFolders as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $this->appendMediaCacheBust(url(Storage::url($path)));
            }
        }

        return $path;
    }

    protected function storageMediaUrls(string $storagePath): array
    {
        $relative = ltrim($storagePath, '/');
        $path = Storage::url($relative);
        $version = $this->mediaVersionForStoragePath($relative);
        $pathWithVersion = $this->appendCacheBust($path, $version);

        return [
            'path' => $pathWithVersion,
            'url' => url($pathWithVersion),
        ];
    }

    protected function appendMediaCacheBust(string $url): string
    {
        $relative = $this->extractStorageRelativePath($url);
        if ($relative === null) {
            return $url;
        }

        return $this->appendCacheBust($url, $this->mediaVersionForStoragePath($relative));
    }

    private function appendCacheBust(string $url, int $version): string
    {
        if (str_contains($url, 'v=')) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . $version;
    }

    private function mediaVersionForStoragePath(string $path): int
    {
        try {
            return (int) Storage::lastModified($path) * 1000;
        } catch (\Throwable $e) {
            return (int) round(microtime(true) * 1000);
        }
    }

    private function extractStorageRelativePath(string $url): ?string
    {
        $rawPath = trim((string) parse_url($url, PHP_URL_PATH));
        if ($rawPath === '') {
            return null;
        }

        $candidate = ltrim($rawPath, '/');
        if (str_starts_with($candidate, 'storage/')) {
            $candidate = substr($candidate, 8);
        }

        foreach ($this->mediaStorageFolders as $prefix) {
            if (str_starts_with($candidate, $prefix)) {
                return $candidate;
            }
        }

        return null;
    }
}
