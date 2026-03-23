<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoExposureReportDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LotteryMarket;

class LottoExposureReportController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoExposureReportDataTable $dataTable)
    {
        $drawOptions = LottoDraw::query()
            ->with('market')
            ->orderByDesc('draw_date')
            ->orderByDesc('id')
            ->get(['id', 'market_id', 'draw_date'])
            ->map(function (LottoDraw $draw): array {
                return [
                    'value' => (int) $draw->id,
                    'text' => ($draw->draw_date ? $draw->draw_date->format('d/m/Y') : '-') . ' (' . ($draw->market->name ?? '-') . ')',
                ];
            })
            ->values()
            ->toArray();

        $marketOptions = LotteryMarket::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($market) => ['value' => (int) $market->id, 'text' => $market->name])
            ->values()
            ->toArray();

        $betTypeOptions = collect(BetType::all())
            ->map(fn (string $type) => [
                'value' => $type,
                'text' => $type . ' = ' . BetType::label($type),
            ])
            ->values()
            ->toArray();

        return $dataTable->render($this->_config['view'], [
            'drawOptions' => $drawOptions,
            'marketOptions' => $marketOptions,
            'betTypeOptions' => $betTypeOptions,
        ]);
    }
}

