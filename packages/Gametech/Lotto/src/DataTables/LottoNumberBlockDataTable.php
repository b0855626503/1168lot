<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LottoNumberBlock;
use Gametech\Lotto\Transformers\LottoNumberBlockTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoNumberBlockDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoNumberBlockTransformer);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(LottoNumberBlock $model)
    {
        $query = $model->newQuery()
            ->select('lotto_number_blocks.*')
            ->with('draw.market')
            ->orderByDesc('blocked_at')
            ->orderByDesc('id');

        $drawId = (int) request('draw_id');
        if ($drawId > 0) {
            $query->where('draw_id', $drawId);
        }

        $betType = trim((string) request('bet_type', ''));
        if ($betType !== '') {
            $query->where('bet_type', $betType);
        }

        $numberSearch = trim((string) request('number_search', ''));
        if ($numberSearch !== '') {
            $query->where('number', 'like', '%' . $numberSearch . '%');
        }

        return $query;
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
                'searching'   => true,
                'deferRender' => true,
                'retrieve'    => true,
                'ordering'    => true,
                'order'       => [[6, 'desc']],
                'buttons'     => ['pageLength'],
                'columnDefs'  => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    /**
     * Get columns.
     */
    protected function getColumns(): array
    {
        return [
            [
                'data' => 'select',
                'name' => 'select',
                'title' => '<input type="checkbox" class="js-lotto-select-all-number-blocks" />',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'width' => '38px',
            ],
            ['data' => 'id', 'name' => 'id', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center', 'width' => '60px'],
            ['data' => 'draw', 'name' => 'draw.draw_date', 'title' => 'งวดหวย', 'orderable' => false, 'searchable' => false, 'className' => 'text-left'],
            ['data' => 'bet_type', 'name' => 'bet_type', 'title' => 'ประเภทเดิมพัน', 'orderable' => true, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'number', 'name' => 'number', 'title' => 'เลข', 'orderable' => true, 'searchable' => true, 'className' => 'text-center'],
            ['data' => 'mode', 'name' => 'mode', 'title' => 'โหมด', 'orderable' => true, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'blocked_at', 'name' => 'blocked_at', 'title' => 'เวลาอั้น', 'orderable' => true, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'action', 'name' => 'action', 'title' => 'จัดการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '90px'],
        ];
    }
}
