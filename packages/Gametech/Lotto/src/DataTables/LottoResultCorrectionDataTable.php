<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Models\LottoResultCorrection;
use Gametech\Lotto\Transformers\LottoResultCorrectionTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoResultCorrectionDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        return (new EloquentDataTable($query))
            ->setTransformer(new LottoResultCorrectionTransformer)
            ->rawColumns(['status', 'action']);
    }

    public function query(LottoResultCorrection $model)
    {
        return $model->newQuery()
            ->with(['draw.market:id,name'])
            ->where('status', '!=', 'previewed')
            ->orderByDesc('id');
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
                'autoWidth' => false,
                'pageLength' => 50,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows'],
                ],
                'buttons' => [
                    'pageLength',
                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => 'ID'],
            ['data' => 'draw_id', 'name' => 'draw_id', 'title' => 'งวด'],
            ['data' => 'market_name', 'name' => 'draw.market.name', 'title' => 'ชื่อหวย'],
            ['data' => 'old_result_number', 'name' => 'old_result_number', 'title' => 'ผลเดิม'],
            ['data' => 'new_result_number', 'name' => 'new_result_number', 'title' => 'ผลใหม่'],
            ['data' => 'reason', 'name' => 'reason', 'title' => 'เหตุผล'],
            ['data' => 'affected_ticket_count', 'name' => 'affected_ticket_count', 'title' => 'โพยที่กระทบ'],
            ['data' => 'total_reversed_amount', 'name' => 'total_reversed_amount', 'title' => 'ยอดหักคืน'],
            ['data' => 'total_reverse_failed_amount', 'name' => 'total_reverse_failed_amount', 'title' => 'ยอดหักคืนไม่ครบ'],
            ['data' => 'total_new_payout_amount', 'name' => 'total_new_payout_amount', 'title' => 'ยอดจ่ายเพิ่ม'],
            ['data' => 'status', 'name' => 'status', 'title' => 'สถานะ'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'วันที่ทำรายการ'],
            ['data' => 'action', 'name' => 'action', 'title' => 'รายละเอียด', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
        ];
    }
}
