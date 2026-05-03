<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LottoNumberExposure;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Transformers\LottoExposureReportTransformer;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoExposureReportDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoExposureReportTransformer);
    }

    public function query(LottoNumberExposure $model)
    {
        $includeYeekeeRound = Schema::hasTable('yeekee_rounds');

        $query = $model->newQuery()
            ->select('lotto_number_exposures.*')
            ->with([
                'draw' => function ($drawQuery) use ($includeYeekeeRound): void {
                    $drawQuery->select(['id', 'market_id', 'draw_date']);
                    if ($includeYeekeeRound) {
                        $drawQuery->selectSub(
                            YeekeeRound::query()
                                ->select('round_no')
                                ->whereColumn('yeekee_rounds.lotto_draw_id', 'lotto_draws.id')
                                ->orderByDesc('yeekee_rounds.id')
                                ->limit(1),
                            'yeekee_round_no'
                        );
                    }
                },
                'draw.market:id,name,logo,icon,result_mode',
            ])
            ->orderByDesc('sold_amount')
            ->orderByDesc('id');

        if ($drawId = (int) request('draw_id')) {
            $query->where('draw_id', $drawId);
        }

        if ($betType = trim((string) request('bet_type'))) {
            $query->where('bet_type', $betType);
        }

        if ($marketId = (int) request('market_id')) {
            $query->whereHas('draw', function ($builder) use ($marketId): void {
                $builder->where('market_id', $marketId);
            });
        } else {
            $query->whereHas('draw.market', function ($builder): void {
                $builder->where('result_mode', $this->resolveMarketTypeFilter());
            });
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
                'order' => [[5, 'desc']],
                'buttons' => ['pageLength'],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'className' => 'text-center', 'width' => '60px'],
            ['data' => 'draw_id', 'name' => 'draw_id', 'title' => 'งวด', 'className' => 'text-center'],
            ['data' => 'draw_date', 'name' => 'draw.draw_date', 'title' => 'วันงวด', 'className' => 'text-center'],
            ['data' => 'market_name', 'name' => 'draw.market.name', 'title' => 'รายการหวย', 'className' => 'text-left'],
            ['data' => 'bet_type', 'name' => 'bet_type', 'title' => 'ประเภทเดิมพัน', 'className' => 'text-center'],
            ['data' => 'number', 'name' => 'number', 'title' => 'เลข', 'className' => 'text-center'],
            ['data' => 'sold_amount', 'name' => 'sold_amount', 'title' => 'ยอดรับรวม', 'className' => 'text-right'],
        ];
    }
}
