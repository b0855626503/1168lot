<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoTicketsCancelReportDataTable;
use Gametech\Lotto\Models\LotteryMarket;

class LottoTicketsCancelReportController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoTicketsCancelReportDataTable $dataTable)
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

        $statusOptions = [
            ['value' => 'active', 'text' => 'รอผล'],
            ['value' => 'cancelled', 'text' => 'ยกเลิก'],
            ['value' => 'resulted', 'text' => 'ตัดสินแล้ว'],
        ];

        return $dataTable->render($this->_config['view'], [
            'marketOptions' => $marketOptions,
            'statusOptions' => $statusOptions,
        ]);
    }
}
