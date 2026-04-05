<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoProfitLossForecastReportDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LotteryMarket;

class LottoProfitLossForecastReportController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoProfitLossForecastReportDataTable $dataTable)
    {
        $drawDateOptions = LottoDraw::query()
            ->orderByDesc('draw_date')
            ->pluck('draw_date')
            ->filter()
            ->map(static fn ($date) => optional($date)->format('Y-m-d'))
            ->filter()
            ->unique()
            ->values()
            ->map(static fn (string $date): array => [
                'value' => $date,
                'text' => date('d/m/Y', strtotime($date)),
            ])
            ->all();

        $marketOptions = LotteryMarket::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (LotteryMarket $market): array => [
                'value' => (int) $market->id,
                'text' => (string) $market->name,
            ])
            ->values()
            ->all();

        $betTypeOptions = collect(BetType::all())
            ->map(static fn (string $type): array => [
                'value' => $type,
                'text' => $type . ' = ' . BetType::label($type),
            ])
            ->values()
            ->all();

        return $dataTable->render($this->_config['view'], [
            'drawDateOptions' => $drawDateOptions,
            'marketOptions' => $marketOptions,
            'betTypeOptions' => $betTypeOptions,
        ]);
    }
}
