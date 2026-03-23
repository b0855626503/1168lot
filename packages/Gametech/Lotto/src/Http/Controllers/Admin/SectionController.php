<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class SectionController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function redirectToDefault(): RedirectResponse
    {
        return redirect()->route('admin.lotto.draws.index');
    }

    public function index(): View
    {
        $config = $this->_config;

        return view($config['view'], [
            'title' => $config['title'] ?? 'Lotto',
            'description' => $config['description'] ?? '',
            'section' => $config['section'] ?? 'overview',
            'filters' => $config['filters'] ?? [],
            'columns' => $config['columns'] ?? [],
            'links' => $this->lottoMenuLinks(),
            'routeName' => Route::currentRouteName(),
        ]);
    }

    protected function lottoMenuLinks(): Collection
    {
        return collect((array) config('menu.admin', []))
            ->filter(function (array $item): bool {
                return isset($item['key'], $item['route'])
                    && str_starts_with((string) $item['key'], 'lotto.')
                    && Route::has((string) $item['route']);
            })
            ->sortBy('sort')
            ->map(function (array $item): array {
                return [
                    'key' => $item['key'],
                    'name' => $item['name'] ?? $item['key'],
                    'route' => $item['route'],
                    'url' => route($item['route']),
                ];
            })
            ->values();
    }
}
