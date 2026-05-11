<?php

namespace Gametech\Lotto\DataTables;

use Gametech\Lotto\Contracts\MemberLottoMarketPolicy;
use Gametech\Lotto\Transformers\MemberLottoPermissionTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class MemberLottoPermissionDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new MemberLottoPermissionTransformer);
    }

    public function query(MemberLottoMarketPolicy $model)
    {
        return $model->newQuery()
            ->select('member_lotto_market_policies.*')
            ->with(['member', 'group', 'market'])
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
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'width' => '60px', 'className' => 'text-center'],
            ['data' => 'member_code', 'name' => 'member_id', 'title' => 'รหัสสมาชิก', 'className' => 'text-center'],
            ['data' => 'member_name', 'name' => 'member.user_name', 'title' => 'ชื่อสมาชิก', 'className' => 'text-left'],
            ['data' => 'group_name', 'name' => 'group.name', 'title' => 'กลุ่มหวย', 'className' => 'text-left'],
            ['data' => 'market_name', 'name' => 'market.name', 'title' => 'รายการหวย', 'className' => 'text-left'],
            ['data' => 'is_allowed', 'name' => 'is_allowed', 'title' => 'สถานะ', 'className' => 'text-center', 'orderable' => false],
            ['data' => 'source', 'name' => 'source', 'title' => 'ที่มา', 'className' => 'text-center'],
            ['data' => 'policy_version', 'name' => 'policy_version', 'title' => 'Version', 'className' => 'text-center'],
            ['data' => 'action', 'name' => 'action', 'title' => 'จัดการ', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ];
    }
}
