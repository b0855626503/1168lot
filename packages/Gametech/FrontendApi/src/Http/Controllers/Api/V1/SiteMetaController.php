<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class SiteMetaController extends BaseController
{
    public function info(): JsonResponse
    {
        $config = core()->getConfigData();

        return $this->sendResponseNew([
            'logo' => (string) ($config->logo ?? ''),
            'title' => (string) ($config->title ?? ''),
            'name' => (string) ($config->sitename ?? ($config->name_th ?? '')),
            'description' => (string) ($config->description ?? ''),
        ], 'ดึงข้อมูลเว็บไซต์สำเร็จ');
    }
}
