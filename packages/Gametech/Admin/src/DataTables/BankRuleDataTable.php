<?php

namespace Gametech\Admin\DataTables;


use Gametech\Admin\Transformers\BankRuleTransformer;
use Gametech\Payment\Contracts\BankRule;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class BankRuleDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {

        $bank = collect(DB::table('banks')->where('code', '<>', 0)->get()->toArray());

        $bank1 = [
            'value' => '0',
            'text' => ''
        ];

        $banks = $bank->map(function ($items) {
            $item = (object)$items;
            return [
                'value' => $item->code,
                'text' => $item->name_th
            ];

        })->prepend($bank1);

        $bankss = collect($banks);
        foreach ($bankss as $i => $item) {
            $newbank[$item['value']] = $item['text'];
        }


        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->setTransformer(new BankRuleTransformer($newbank));

    }


    /**
     * @param BankRule $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(BankRule $model)
    {
        return $model->newQuery()->with('bank')
            ->select('banks_rule.*');


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
                'stateSave' => true,
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => false,
                'scrollX' => true,
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
            ['data' => 'code', 'name' => 'banks_rule.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
//            ['data' => 'types', 'name' => 'banks_rule.types', 'title' => 'ประเภทเงื่อนไข', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'bank', 'name' => 'banks_rule.bank_code', 'title' => 'ลูกค้าทีสมัครด้วยธนาคาร', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'method', 'name' => 'banks_rule.method', 'title' => 'สิทธิ', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'other', 'name' => 'banks_rule.bank_number', 'title' => 'ธนาคารเหล่านี้', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
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
        return 'bankin_datatable_' . time();
    }
}
