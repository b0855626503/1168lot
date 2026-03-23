<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LottoMarketBetSetting;
use Gametech\Lotto\Transformers\LottoMarketBetSettingTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoMarketBetSettingDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoMarketBetSettingTransformer);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(LottoMarketBetSetting $model)
    {
        return $model->newQuery()
            ->select('lotto_market_bet_settings.*')
            ->with('market')
            ->orderBy('market_id')
            ->orderBy('bet_type');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): Builder
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'dom'         => 'Bfrtip',
                'processing'  => true,
                'serverSide'  => true,
                'responsive'  => false,
                'stateSave'   => true,
                'scrollX'     => true,
                'paging'      => true,
                'searching'   => false,
                'deferRender' => true,
                'retrieve'    => true,
                'ordering'    => true,
                'order'       => [[1, 'asc']],
                'buttons'     => ['pageLength'],
                'columnDefs'  => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    /**
     * Get columns.
     */
    protected function getColumns()
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'width' => '50px'],
            ['data' => 'market_name', 'name' => 'market.name', 'title' => 'ตลาด'],
            ['data' => 'bet_type', 'name' => 'bet_type', 'title' => 'ประเภทเดิมพัน'],
            ['data' => 'payout', 'name' => 'payout', 'title' => 'อัตราจ่าย'],
            ['data' => 'min_bet', 'name' => 'min_bet', 'title' => 'ขั้นต่ำ'],
            ['data' => 'max_bet', 'name' => 'max_bet', 'title' => 'สูงสุด'],
            ['data' => 'max_per_number', 'name' => 'max_per_number', 'title' => 'สูงสุดต่อเลข'],
            ['data' => 'is_enabled', 'name' => 'is_enabled', 'title' => 'เปิด/ปิด'],
            ['data' => 'action', 'name' => 'action', 'title' => 'ดำเนินการ', 'orderable' => false, 'searchable' => false],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'LottoMarketBetSetting_' . date('YmdHis');
    }
}
