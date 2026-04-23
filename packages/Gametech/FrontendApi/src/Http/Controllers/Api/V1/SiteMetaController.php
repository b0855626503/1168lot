<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SiteMetaController extends BaseController
{
    public function info(): JsonResponse
    {
        $config = core()->getConfigData();
        $logo = $this->resolveLogoUrl((string) ($config->logo ?? ''));

        return $this->sendResponseNew([
            'logo' => $logo,
            'title' => (string) ($config->title ?? ''),
            'name' => (string) ($config->sitename ?? ($config->name_th ?? '')),
            'description' => (string) ($config->description ?? ''),
        ], 'ดึงข้อมูลเว็บไซต์สำเร็จ');
    }

    private function resolveLogoUrl(string $logo): string
    {
        $logo = trim($logo);
        if ($logo === '') {
            return '';
        }

        if (Str::startsWith($logo, ['http://', 'https://', '//', '/', 'storage/'])) {
            return $this->absoluteMediaUrl($logo);
        }

        if (Str::startsWith($logo, ['img/', 'icon_img/', 'slide_img/', 'promotion_img/', 'bank_img/'])) {
            return $this->storageMediaUrls($logo)['url'];
        }

        return $this->storageMediaUrls('img/'.$logo)['url'];
    }
}
