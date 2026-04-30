<?php

namespace Gametech\Admin\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ApiDocsController extends AppBaseController
{
    protected $_config;

    public function __construct()
    {
        $this->_config = (array) request('_config', []);
        $this->middleware('admin');
    }

    public function frontendApiV1(): View
    {
        return view($this->_config['view'], [
            'title' => 'Frontend API V1',
            'markdown' => $this->loadFrontendApiV1Markdown(),
            'meta' => 'docs/public/api/archive/api-frontend-v1.md',
            'rawRoute' => 'admin.docs.api.frontend_v1.raw',
        ]);
    }

    public function frontendApiV1Raw(): Response
    {
        return response($this->loadFrontendApiV1Markdown(), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    public function laravelEchoNextjsInstall(): View
    {
        $path = base_path('docs/public/api/laravel-echo-nextjs-install.md');

        abort_unless(File::exists($path), 404, 'API docs file not found');

        return view($this->_config['view'], [
            'title' => 'Laravel Echo Nextjs Install',
            'markdown' => File::get($path),
            'meta' => 'docs/public/api/laravel-echo-nextjs-install.md',
            'rawRoute' => 'admin.docs.api.laravel_echo_nextjs_install.raw',
        ]);
    }

    public function laravelEchoNextjsInstallRaw(): Response
    {
        $path = base_path('docs/public/api/laravel-echo-nextjs-install.md');

        abort_unless(File::exists($path), 404, 'API docs file not found');

        return response(File::get($path), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    private function loadFrontendApiV1Markdown(): string
    {
        $path = base_path('docs/public/api/archive/api-frontend-v1.md');
        abort_unless(File::exists($path), 404, 'API docs file not found');

        return File::get($path);
    }
}
