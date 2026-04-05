<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoMemberBetTypesReportDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LotteryMarket;

class LottoMemberBetTypesReportController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoMemberBetTypesReportDataTable $dataTable)
    {
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
            'marketOptions' => $marketOptions,
            'betTypeOptions' => $betTypeOptions,
        ]);
    }
}
