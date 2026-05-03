<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Transformers\LottoBlockedNumbersReportTransformer;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoBlockedNumbersReportDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoBlockedNumbersReportTransformer);
    }

    public function query(LottoNumberBlock $model)
    {
        $yeekeeRoundSelect = Schema::hasTable('yeekee_rounds')
            ? ',
                (
                    select yeekee_rounds.round_no
                    from yeekee_rounds
                    where yeekee_rounds.lotto_draw_id = lotto_draws.id
                    order by yeekee_rounds.id desc
                    limit 1
                ) as yeekee_round_no'
            : '';

        $query = $model->newQuery()
            ->selectRaw('
                lotto_number_blocks.*,
                lotto_draws.draw_date as draw_date,
                lotto_markets.name as market_name,
                lotto_markets.logo as market_logo,
                lotto_markets.icon as market_icon,
                lotto_markets.result_mode as market_result_mode'.$yeekeeRoundSelect.'
            ')
            ->join('lotto_draws', 'lotto_draws.id', '=', 'lotto_number_blocks.draw_id')
            ->join('lotto_markets', 'lotto_markets.id', '=', 'lotto_draws.market_id')
            ->orderByDesc('lotto_number_blocks.blocked_at')
            ->orderByDesc('lotto_number_blocks.id');

        if ($drawDate = trim((string) request('draw_date'))) {
            $query->whereDate('lotto_draws.draw_date', $drawDate);
        }

        if ($marketId = (int) request('market_id')) {
            $query->where('lotto_draws.market_id', $marketId);
        } else {
            $query->where('lotto_markets.result_mode', $this->resolveMarketTypeFilter());
        }

        if ($betType = trim((string) request('bet_type'))) {
            $query->where('lotto_number_blocks.bet_type', $betType);
        }

        if ($mode = trim((string) request('mode'))) {
            $query->where('lotto_number_blocks.mode', $mode);
        }

        return $query;
    }

    private function resolveMarketTypeFilter(): string
    {
        $marketType = strtolower(trim((string) request('market_type', LotteryMarket::RESULT_MODE_NORMAL)));
        if (in_array($marketType, [LotteryMarket::RESULT_MODE_NORMAL, LotteryMarket::RESULT_MODE_YEEKEE], true)) {
            return $marketType;
        }

        return LotteryMarket::RESULT_MODE_NORMAL;
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
                'order' => [[4, 'desc']],
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
            ['data' => 'number', 'name' => 'number', 'title' => 'เลข', 'className' => 'text-center'],
            ['data' => 'mode', 'name' => 'mode', 'title' => 'โหมด', 'className' => 'text-center'],
            ['data' => 'blocked_at', 'name' => 'blocked_at', 'title' => 'เวลาเริ่ม', 'className' => 'text-center'],
            ['data' => 'updated_at', 'name' => 'updated_at', 'title' => 'เวลาแก้ไขล่าสุด', 'className' => 'text-center'],
        ];
    }
}
