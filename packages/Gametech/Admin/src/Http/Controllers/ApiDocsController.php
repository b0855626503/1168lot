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
        $this->middleware('admin')->except(['frontendApiV1', 'frontendApiV1Raw']);
    }

    public function frontendApiV1(): View
    {
        return view($this->_config['view'], [
            'title' => 'Frontend API V1',
            'markdown' => $this->loadFrontendApiV1Markdown(),
            'meta' => 'docs/public/api/frontend-v1/index.md',
            'rawRoute' => 'admin.docs.api.frontend_v1.raw',
            'rawRouteParams' => [],
        ]);
    }

    public function frontendApiV1Raw(): Response
    {
        return response($this->loadFrontendApiV1Markdown(), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    public function frontendApiV1Chapter(string $slug): View
    {
        return view($this->_config['view'], [
            'title' => 'Frontend API V1 - '.$slug,
            'markdown' => $this->loadFrontendApiV1ChapterMarkdown($slug),
            'meta' => 'docs/public/api/frontend-v1/'.$slug.'.md',
            'rawRoute' => 'admin.docs.api.frontend_v1.chapter.raw',
            'rawRouteParams' => ['slug' => $slug],
        ]);
    }

    public function frontendApiV1ChapterRaw(string $slug): Response
    {
        return response($this->loadFrontendApiV1ChapterMarkdown($slug), 200, [
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
        $path = base_path('docs/public/api/frontend-v1/index.md');
        abort_unless(File::exists($path), 404, 'API docs file not found');

        return File::get($path);
    }

    private function loadFrontendApiV1ChapterMarkdown(string $slug): string
    {
        abort_unless(preg_match('/^[a-z0-9-]+$/', $slug) === 1, 404, 'API docs file not found');

        $path = base_path('docs/public/api/frontend-v1/'.$slug.'.md');
        abort_unless(File::exists($path), 404, 'API docs file not found');

        return File::get($path);
    }
}
