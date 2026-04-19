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
        $latestArchive = $this->resolveLatestFrontendApiArchivePath();

        return view($this->_config['view'], [
            'title' => 'Frontend API V1',
            'markdown' => $this->loadFrontendApiV1Markdown(),
            'meta' => $latestArchive !== null
                ? str_replace(base_path().'/', '', $latestArchive).' (preferred)'
                : 'docs/public/api/archive/api-frontend-v1.<latest>.md (preferred)',
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
        $archivePath = $this->resolveLatestFrontendApiArchivePath();
        if ($archivePath !== null && File::exists($archivePath)) {
            return File::get($archivePath);
        }

        $paths = [
            base_path('docs/public/api/frontend-v1/index.md'),
            base_path('docs/public/api/frontend-v1/01-overview.md'),
            base_path('docs/public/api/frontend-v1/02-flows.md'),
            base_path('docs/public/api/frontend-v1/03-endpoints.md'),
            base_path('docs/public/api/frontend-v1/04-edge-cases.md'),
            base_path('docs/public/api/frontend-v1/05-route-reference.md'),
        ];

        $chunks = [];
        foreach ($paths as $path) {
            if (File::exists($path)) {
                $chunks[] = trim(File::get($path));
            }
        }

        if (! empty($chunks)) {
            return implode("\n\n---\n\n", $chunks);
        }

        $legacyPath = base_path('docs/public/api/api-frontend-v1.md');
        abort_unless(File::exists($legacyPath), 404, 'API docs file not found');

        return File::get($legacyPath);
    }

    private function resolveLatestFrontendApiArchivePath(): ?string
    {
        $files = glob(base_path('docs/public/api/archive/api-frontend-v1.*.md'));
        if ($files === false || empty($files)) {
            return null;
        }

        sort($files, SORT_STRING);

        return end($files) ?: null;
    }
}
