<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Models\LottoDrawBetSetting;
use Gametech\Lotto\Transformers\LottoProfitLossForecastReportTransformer;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoProfitLossForecastReportDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoProfitLossForecastReportTransformer());
    }

    public function query(LottoDrawBetSetting $model)
    {
        $totalBetSubquery = DB::table('lotto_ticket_items')
            ->join('lotto_tickets', 'lotto_tickets.id', '=', 'lotto_ticket_items.ticket_id')
            ->selectRaw('COALESCE(SUM(lotto_ticket_items.amount), 0)')
            ->whereColumn('lotto_tickets.draw_id', 'lotto_draw_bet_settings.draw_id')
            ->whereColumn('lotto_ticket_items.bet_type', 'lotto_draw_bet_settings.bet_type')
            ->where('lotto_tickets.status', '!=', 'cancelled');

        $riskSubquery = DB::table('lotto_number_exposures')
            ->selectRaw('COALESCE(MAX(lotto_number_exposures.sold_amount * lotto_draw_bet_settings.payout), 0)')
            ->whereColumn('lotto_number_exposures.draw_id', 'lotto_draw_bet_settings.draw_id')
            ->whereColumn('lotto_number_exposures.bet_type', 'lotto_draw_bet_settings.bet_type');

        $query = $model->newQuery()
            ->select('lotto_draw_bet_settings.*')
            ->join('lotto_draws', 'lotto_draws.id', '=', 'lotto_draw_bet_settings.draw_id')
            ->join('lotto_markets', 'lotto_markets.id', '=', 'lotto_draws.market_id')
            ->selectRaw('
                lotto_draws.status as draw_status,
                lotto_draws.draw_date as draw_date,
                lotto_markets.id as market_id,
                lotto_markets.name as market_name,
                lotto_markets.logo as market_logo,
                lotto_markets.icon as market_icon
            ')
            ->selectSub($totalBetSubquery, 'total_bet_amount')
            ->selectSub($riskSubquery, 'max_risk_amount')
            ->where('lotto_draw_bet_settings.is_enabled', true)
            ->orderByDesc('lotto_draws.draw_date')
            ->orderBy('lotto_markets.name')
            ->orderBy('lotto_draw_bet_settings.bet_type');

        if ($drawDate = trim((string) request('draw_date'))) {
            $query->whereDate('lotto_draws.draw_date', $drawDate);
        }

        if ($marketId = (int) request('market_id')) {
            $query->where('lotto_markets.id', $marketId);
        }

        if ($betType = trim((string) request('bet_type'))) {
            $query->where('lotto_draw_bet_settings.bet_type', $betType);
        }

        return $query;
    }

    public function html(): Builder
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'dom' => 'Bfrtip',
                'processing' => true,
                'serverSide' => true,
                'responsive' => false,
                'stateSave' => true,
                'scrollX' => true,
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'order' => [[0, 'desc']],
                'buttons' => ['pageLength'],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'draw_date', 'name' => 'draw_date', 'title' => 'วันงวด', 'className' => 'text-center'],
            ['data' => 'market_name', 'name' => 'market_name', 'title' => 'ตลาด', 'className' => 'text-left'],
            ['data' => 'bet_type', 'name' => 'bet_type', 'title' => 'ประเภท', 'className' => 'text-center'],
            ['data' => 'total_bet_amount', 'name' => 'total_bet_amount', 'title' => 'ยอดแทงรวม', 'className' => 'text-right'],
            ['data' => 'max_risk_amount', 'name' => 'max_risk_amount', 'title' => 'ความเสี่ยงจ่าย', 'className' => 'text-right'],
            ['data' => 'forecast_net', 'name' => 'forecast_net', 'title' => 'คาดการณ์ได้/เสีย', 'className' => 'text-right'],
            ['data' => 'draw_status', 'name' => 'draw_status', 'title' => 'สถานะงวด', 'className' => 'text-center'],
        ];
    }
}
