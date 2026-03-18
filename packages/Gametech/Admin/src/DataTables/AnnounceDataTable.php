<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\FaqTransformer;
use Gametech\Core\Contracts\Faq;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class AnnounceDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->setTransformer(new FaqTransformer);

    }


    /**
     * @param Faq $model
     * @return mixed
     */
    public function query(Faq $model)
    {
        return $model->newQuery()
            ->select('faq.*');


    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return Builder
     */
    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'dom' => 'Bfrtip',

                'processing' => true,
                'serverSide' => true,
                'responsive' => false,
                'stateSave' => false,
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => false,

                'order' => [[0, 'desc']],
                'buttons' => [
                    'pageLength'
                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap']
                ]
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            ['data' => 'code', 'name' => 'faq.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'name', 'name' => 'faq.question', 'title' => 'คำถาม', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'sort', 'name' => 'faq.sort', 'title' => 'ลำดับ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'enable', 'name' => 'faq.enable', 'title' => 'เปิดใช้งาน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'bankin_datatable_' . time();
    }
}
