<?php

namespace Gametech\LineOA\DataTables;

use Gametech\LineOA\Contracts\LineAccount;
use Gametech\LineOA\Contracts\LineTemplate;
use Gametech\LineOA\Transformers\LineAccountTransformer;
use Gametech\LineOA\Transformers\LineTemplateTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LineTemplateDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     */
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LineTemplateTransformer());

    }

    /**
     * @return mixed
     */
    public function query(LineTemplate $model)
    {
        $status = request()->input('status');

        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString().' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString().' 23:59:59';
        }

        return $model->newQuery()->select('line_templates.*');
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
                'paging' => false,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,

                'pageLength' => 50,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows'],
                ],
                'buttons' => [

                ],
                'columnDefs' => [
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
            ['data' => 'id', 'name' => 'line_templates.id', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'category', 'name' => 'line_templates.category', 'title' => 'หมวด', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'key', 'name' => 'line_templates.key', 'title' => 'key', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'description', 'name' => 'line_templates.description', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],

            ['data' => 'message', 'name' => 'line_templates.message', 'title' => 'ข้อความ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'description', 'name' => 'line_templates.description', 'title' => 'คำอธิบาย', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'bankin_datatable_'.time();
    }
}
