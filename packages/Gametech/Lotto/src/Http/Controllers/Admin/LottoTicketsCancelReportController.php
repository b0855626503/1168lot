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
            ->with('group:id,name,sort')
            ->orderBy('group_id')
            ->orderBy('name')
            ->get(['id', 'group_id', 'name', 'logo', 'icon'])
            ->groupBy(static function (LotteryMarket $market): string {
                return (string) optional($market->group)->name ?: 'ไม่ระบุกลุ่ม';
            })
            ->map(static function ($markets, $groupName): array {
                return [
                    'label' => (string) $groupName,
                    'options' => $markets->map(static fn (LotteryMarket $market): array => [
                        'value' => (int) $market->id,
                        'text' => (string) $market->name,
                        'logo' => (string) ($market->logo ?: $market->icon ?: ''),
                    ])->values()->all(),
                ];
            })
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
