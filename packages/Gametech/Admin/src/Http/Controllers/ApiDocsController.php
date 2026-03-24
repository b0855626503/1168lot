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
        $path = base_path('docs/API_FRONTEND_V1.md');

        abort_unless(File::exists($path), 404, 'API docs file not found');

        return view($this->_config['view'], [
            'title' => 'Frontend API V1',
            'markdown' => File::get($path),
        ]);
    }

    public function frontendApiV1Raw(): Response
    {
        $path = base_path('docs/API_FRONTEND_V1.md');

        abort_unless(File::exists($path), 404, 'API docs file not found');

        return response(File::get($path), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }
}
