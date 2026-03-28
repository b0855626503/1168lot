<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LotteryMarket;
use Gametech\Lotto\Transformers\LotteryMarketTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LotteryMarketDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LotteryMarketTransformer);
    }

    public function query(LotteryMarket $model)
    {
        $query = $model->newQuery()
            ->with('group')
            ->withCount('resultSources')
            ->select('lotto_markets.*');

        if ($groupId = (int) request('group_id')) {
            $query->where('group_id', $groupId);
        }

        $marketName = trim((string) request('market_name', ''));
        if ($marketName !== '') {
            $query->where('name', 'like', '%' . $marketName . '%');
        }

        return $query;
    }

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
                'order'       => [[0, 'asc']],
                'buttons'     => ['pageLength'],
                'columnDefs'  => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id',         'name' => 'id',          'title' => '#',            'orderable' => true,  'searchable' => false, 'className' => 'text-center', 'width' => '60px'],
            ['data' => 'thumbnail',  'name' => 'thumbnail',   'title' => 'รูป',           'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '80px'],
            ['data' => 'name',       'name' => 'name',        'title' => 'ชื่อรายการหวย', 'orderable' => true,  'searchable' => true,  'className' => 'text-left'],
            ['data' => 'group_name', 'name' => 'group_name',  'title' => 'กลุ่มหวย',      'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'code',       'name' => 'code',        'title' => 'Code',          'orderable' => true,  'searchable' => true,  'className' => 'text-center'],
            ['data' => 'draw_mode',  'name' => 'draw_mode',   'title' => 'โหมดงวด',       'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'auto_open_time',   'name' => 'auto_open_time',   'title' => 'เปิดรับ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'auto_close_time',  'name' => 'auto_close_time',  'title' => 'ปิดรับ',  'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'auto_result_time', 'name' => 'auto_result_time', 'title' => 'ออกผล',   'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'result_url', 'name' => 'result_url',  'title' => 'ลิงก์ออกผล',     'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'auto_result_source_status', 'name' => 'result_sources_count', 'title' => 'Auto Source', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'is_enabled', 'name' => 'is_enabled',  'title' => 'สถานะ',         'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '100px'],
            ['data' => 'action',     'name' => 'action',      'title' => 'จัดการ',        'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '80px'],
        ];
    }
}
