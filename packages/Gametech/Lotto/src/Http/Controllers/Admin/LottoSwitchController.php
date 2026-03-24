<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Illuminate\View\View;

class LottoSwitchController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(): View
    {
        $groups = LotteryGroup::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(static function ($item): array {
                return [
                    'id' => (int) $item->id,
                    'name' => (string) $item->name,
                    'code' => (string) $item->code,
                    'is_enabled' => (bool) $item->is_enabled,
                ];
            })
            ->values()
            ->all();

        $markets = LotteryMarket::query()
            ->with('group')
            ->orderBy('group_id')
            ->orderBy('id')
            ->get()
            ->map(static function ($item): array {
                return [
                    'id' => (int) $item->id,
                    'name' => (string) $item->name,
                    'code' => (string) $item->code,
                    'logo' => (string) ($item->logo ?? ''),
                    'icon' => (string) ($item->icon ?? ''),
                    'group_id' => (int) $item->group_id,
                    'group_name' => optional($item->group)->name ? (string) $item->group->name : '-',
                    'is_enabled' => (bool) $item->is_enabled,
                ];
            })
            ->values()
            ->all();

        return view($this->_config['view'], compact('groups', 'markets'));
    }
}
