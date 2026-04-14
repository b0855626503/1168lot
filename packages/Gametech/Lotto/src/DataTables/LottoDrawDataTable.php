<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LottoDraw;
use Gametech\Lotto\Transformers\LottoDrawTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoDrawDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->setTransformer(new LottoDrawTransformer)
            ->order(function ($query): void {
                $status = (string) request('status', '');

                if (in_array($status, ['draft', 'open', 'closed', 'resulted'], true)) {
                    $query->orderBy('close_at', 'asc')
                        ->orderBy('id', 'asc');

                    return;
                }

                $query->orderByRaw("CASE status WHEN 'closed' THEN 0 WHEN 'open' THEN 1 ELSE 2 END")
                    ->orderBy('close_at', 'asc')
                    ->orderBy('id', 'asc');
            });
    }

    /**
     * Get query source of dataTable.
     */
    public function query(LottoDraw $model)
    {
        $query = $model->newQuery()
            ->select('lotto_draws.*')
            ->with('market')
            ->withCount([
                'blockedNumbers as blocked_numbers_count',
                'tickets as tickets_count',
                'tickets as active_tickets_count' => function ($query): void {
                    $query->where('status', 'active');
                },
            ]);

        if ($groupId = (int) request('group_id')) {
            $query->whereHas('market', function ($builder) use ($groupId): void {
                $builder->where('group_id', $groupId);
            });
        }

        if ($marketId = (int) request('market_id')) {
            $query->where('market_id', $marketId);
        }

        $drawDate = (string) request('draw_date', '');
        if ($drawDate !== '') {
            $query->whereDate('draw_date', $drawDate);
        }

        $status = (string) request('status', '');
        if (in_array($status, ['draft', 'open', 'closed', 'resulted'], true)) {
            $query->where('status', $status);
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
                'order' => [[4, 'asc']],
                'buttons' => ['pageLength'],
                'columnDefs' => [
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
            ['data' => 'market_name', 'name' => 'market.name', 'title' => 'ตลาด', 'className' => 'text-left'],
            ['data' => 'draw_date', 'name' => 'draw_date', 'title' => 'วันงวด'],
            ['data' => 'open_at', 'name' => 'open_at', 'title' => 'เปิดรับ'],
            ['data' => 'close_at', 'name' => 'close_at', 'title' => 'ปิดรับ'],
            ['data' => 'result_at', 'name' => 'result_at', 'title' => 'เวลาออกผล'],
            ['data' => 'status', 'name' => 'status', 'title' => 'สถานะ'],
            ['data' => 'blocked_numbers_count', 'name' => 'blocked_numbers_count', 'title' => 'จำนวนเลขอั้น', 'className' => 'text-center', 'searchable' => false],
            ['data' => 'tickets_count', 'name' => 'tickets_count', 'title' => 'จำนวนรายการ', 'className' => 'text-center', 'searchable' => false],
            ['data' => 'top_3', 'name' => 'result_number', 'title' => '3 ตัวบน', 'className' => 'text-center'],
            ['data' => 'bottom_2', 'name' => 'result_number', 'title' => '2 ตัวล่าง', 'className' => 'text-center'],
            ['data' => 'action', 'name' => 'action', 'title' => 'ดำเนินการ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '3%'],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'LottoDraw_'.date('YmdHis');
    }
}
