<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LottoResultSource;
use Gametech\Lotto\Transformers\LottoResultSourceTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoResultSourceDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoResultSourceTransformer());
    }

    public function query(LottoResultSource $model)
    {
        $query = $model->newQuery()
            ->select(
                'lotto_result_sources.*',
                'lotto_markets.name as market_name',
                'lotto_markets.logo as market_logo',
                'lotto_markets.icon as market_icon',
                'lotto_markets.group_id as group_id',
                'lotto_groups.name as group_name'
            )
            ->leftJoin('lotto_markets', 'lotto_markets.id', '=', 'lotto_result_sources.market_id')
            ->leftJoin('lotto_groups', 'lotto_groups.id', '=', 'lotto_markets.group_id');

        $groupId = (int) request()->get('group_id', 0);
        if ($groupId > 0) {
            $query->where('lotto_markets.group_id', $groupId);
        }

        $marketId = (int) request()->get('market_id', 0);
        if ($marketId > 0) {
            $query->where('lotto_result_sources.market_id', $marketId);
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
                'searching' => true,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'order' => [[0, 'desc'],[3, 'asc']],
                'buttons' => ['pageLength'],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'lotto_result_sources.id', 'title' => '#', 'className' => 'text-center', 'width' => '60px'],
            ['data' => 'group_name', 'name' => 'lotto_groups.name', 'title' => 'กลุ่มหวย'],
            ['data' => 'market_name', 'name' => 'lotto_markets.name', 'title' => 'รายการหวย'],
            ['data' => 'priority', 'name' => 'lotto_result_sources.priority', 'title' => 'Priority', 'className' => 'text-center', 'width' => '90px'],
            ['data' => 'source_type', 'name' => 'lotto_result_sources.source_type', 'title' => 'Type', 'className' => 'text-center', 'width' => '90px'],
            ['data' => 'http_method', 'name' => 'lotto_result_sources.http_method', 'title' => 'Method', 'className' => 'text-center', 'width' => '90px'],
            ['data' => 'endpoint_url', 'name' => 'lotto_result_sources.endpoint_url', 'title' => 'Endpoint', 'width' => '150px'],
            ['data' => 'lookup_date_mode', 'name' => 'lotto_result_sources.lookup_date_mode', 'title' => 'Lookup', 'className' => 'text-center', 'width' => '170px'],
            ['data' => 'parser_type', 'name' => 'lotto_result_sources.parser_type', 'title' => 'Parser', 'className' => 'text-center', 'width' => '110px'],
            ['data' => 'is_active', 'name' => 'lotto_result_sources.is_active', 'title' => 'สถานะ', 'className' => 'text-center', 'width' => '90px'],
            ['data' => 'action', 'name' => 'action', 'title' => 'จัดการ', 'className' => 'text-center', 'width' => '100px', 'orderable' => false, 'searchable' => false],
        ];
    }
}
