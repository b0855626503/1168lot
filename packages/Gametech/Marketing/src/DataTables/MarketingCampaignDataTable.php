<?php

namespace Gametech\Marketing\DataTables;

use Gametech\Marketing\Contracts\MarketingCampaign;
use Gametech\Marketing\Transformers\MarketingCampaignTransformer;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class MarketingCampaignDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->setTransformer(new MarketingCampaignTransformer);

    }

    /**
     * @param  Role  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(MarketingCampaign $model)
    {
        $enable = request()->input('enable');
        $user = auth()->guard('admin')->user();

//        dd($user->role->name);

        return $model->newQuery()
            ->when($enable, function ($query, $enable) {
                $query->where('enable', filter_var($enable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
            })
            ->when($user->role->name === 'marketing', function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->whereNull('admin_username')
                        ->orWhereRaw('FIND_IN_SET(?, admin_username)', [$user->user_name]);
                });
            })
            ->select('marketing_campaigns.*')
            ->with(['registrationLink', 'team']);

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
            ->ajaxWithForm('', '#frmsearch')
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
                'ordering' => false,

                'order' => [[0, 'asc']],
                'buttons' => [
                    'pageLength',
                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
                ],
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
            ['data' => 'id', 'name' => 'id', 'title' => '#', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'name', 'name' => 'name', 'title' => 'ชื่อแคมเปญ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'description', 'name' => 'description', 'title' => 'รายละเอียดคร่าวๆ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'team_id', 'name' => 'team_id', 'title' => 'ทีมที่ดูแล', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'is_ended', 'name' => 'is_ended', 'title' => 'สิ้นสุดแคมเปญ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'link', 'name' => 'link', 'title' => 'ลิงค์สมัคร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'enable', 'name' => 'enable', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'bankin_datatable_'.time();
    }
}
