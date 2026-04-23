<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\LottoNavbar;
use Gametech\Lotto\Transformers\LottoNavbarTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class LottoNavbarDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new LottoNavbarTransformer);
    }

    public function query(LottoNavbar $model)
    {
        $query = $model->newQuery()
            ->select('lotto_navbars.*')
            ->where('lotto_navbars.is_active', true)
            ->orderByDesc('id');

        $code = trim((string) request()->get('code', ''));
        if ($code !== '') {
            $query->where('code', 'like', '%'.$code.'%');
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
                'order' => [[0, 'desc']],
                'buttons' => ['pageLength'],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => '#', 'className' => 'text-center', 'width' => '70px'],
            ['data' => 'code', 'name' => 'code', 'title' => 'Code', 'className' => 'text-left'],
            ['data' => 'name', 'name' => 'name', 'title' => 'Name', 'className' => 'text-left'],
            ['data' => 'state', 'name' => 'state', 'title' => 'สถานะ', 'className' => 'text-center', 'searchable' => false],
            ['data' => 'published_version', 'name' => 'published_version', 'title' => 'Published Version', 'className' => 'text-center', 'width' => '140px'],
            ['data' => 'updated_at_text', 'name' => 'updated_at', 'title' => 'Updated At', 'className' => 'text-center', 'width' => '150px'],
            ['data' => 'action', 'name' => 'action', 'title' => 'จัดการ', 'className' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '230px'],
        ];
    }
}
