<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class DocsController extends Controller
{
    public function frontendApiV1(): View
    {
        $path = base_path('docs/API_FRONTEND_V1.md');

        abort_unless(File::exists($path), 404, 'API docs file not found');

        return view('docs.frontend-api-v1', [
            'title' => 'Frontend API V1',
            'markdown' => File::get($path),
            'rawPath' => 'docs/API_FRONTEND_V1.md',
        ]);
    }
}

