<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpSumGameTransformer;
use Gametech\Game\Contracts\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;


class RpSumGameDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {
//        $promotion = Bill::query()->select('pro_code')->distinct()->get();


        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->setTransformer(new RpSumGameTransformer);

    }


    /**
     * @param Game $model
     * @return mixed
     */
    public function query(Game $model)
    {


        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        $type = request()->input('type');

        if (empty($startdate)) {
            $startdate = now()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        if($type == 'NORMAL'){
            $bills = 'bills';
        }else{
            $bills = 'bills_free';
        }


        return $model->active()
            ->select('games.*')
            ->withCount([$bills.' as member_in' => function (Builder $query) use ($startdate, $enddate) {
                $query->select(DB::raw('count(distinct(member_code))'))
                    ->where('transfer_type', 1)
                    ->where('enable', 'Y')
                    ->whereBetween('date_create', array($startdate, $enddate));
            }])
            ->withCount([$bills.' as member_out' => function (Builder $query) use ($startdate, $enddate) {
                $query->select(DB::raw('count(distinct(member_code))'))
                    ->where('transfer_type', 2)
                    ->where('enable', 'Y')
                    ->whereBetween('date_create', array($startdate, $enddate));
            }])
            ->withCount([$bills.' as member_in_cnt' => function (Builder $query) use ($startdate, $enddate) {
                $query->select(DB::raw('count(member_code)'))
                    ->where('transfer_type', 1)
                    ->where('enable', 'Y')
                    ->whereBetween('date_create', array($startdate, $enddate));
            }])
            ->withCount([$bills.' as member_out_cnt' => function (Builder $query) use ($startdate, $enddate) {
                $query->select(DB::raw('count(member_code)'))
                    ->where('transfer_type', 2)
                    ->where('enable', 'Y')
                    ->whereBetween('date_create', array($startdate, $enddate));
            }])
            ->withSum([$bills.':credit_bonus as bonus' => function (Builder $query) use ($startdate, $enddate) {

                $query->where('transfer_type', 1)
                    ->where('enable', 'Y')
                    ->whereBetween('date_create', array($startdate, $enddate));

            }])
            ->withSum([$bills.':amount as in' => function (Builder $query) use ($startdate, $enddate) {

                $query->where('transfer_type', 1)
                    ->where('enable', 'Y')
                    ->whereBetween('date_create', array($startdate, $enddate));

            }])
            ->withSum([$bills.':amount as out' => function (Builder $query) use ($startdate, $enddate) {

                $query->where('transfer_type', 2)
                    ->where('enable', 'Y')
                    ->whereBetween('date_create', array($startdate, $enddate));

            }]);


    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
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
                'info' => false,
                'paging' => false,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => false,
                'autoWidth' => false,
                'pageLength' => 50,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows']
                ],
                'buttons' => [

                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap']
                ],
                'footerCallback' => "function (row, data, start, end, display) {
                           var api = this.api();

                           var intVal = function ( i ) {
                                return typeof i === 'string' ?
                                    i.replace(/[\$,]/g, '')*1 :
                                    typeof i === 'number' ?
                                        i : 0;
                            };

                           api.columns().every(function (i) {
                            if(i > 0){
                           var sum = this.data()
                                      .reduce(function(a, b) {
                                        var x = intVal(a) || 0;
                                        var y = intVal(b) || 0;
                                        return x + y;
                                      }, 0);

                                    var n = new Number(sum);
                                    var myObj = {
                                        style: 'decimal'
                                    };
                                    if(sum < 0){
                                        $(this.column()).css('background-color','red');
                                    }
                                $(this.footer()).html(n.toLocaleString(myObj));
                                }
                            });
                        }",
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
            ['data' => 'name', 'name' => 'name', 'title' => 'เกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'in', 'name' => 'in', 'title' => 'รวมยอดโยกเข้าเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap', 'footer' => '0'],
            ['data' => 'out', 'name' => 'out', 'title' => 'รวมยอดโยกออกเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap', 'footer' => '0'],
            ['data' => 'total', 'name' => 'total', 'title' => 'โยกเข้า - โยกออก', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap', 'footer' => '0'],
            ['data' => 'bonus', 'name' => 'bonus', 'title' => 'รวมยอดโบนัสที่โยกเข้า', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap', 'footer' => '0'],
            ['data' => 'member_in', 'name' => 'member_in', 'title' => 'จำนวนสมาชิกที่โยกเข้า', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap', 'footer' => '0'],
            ['data' => 'member_in_cnt', 'name' => 'member_in_cnt', 'title' => 'รวมรายการโยกเข้า', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap', 'footer' => '0'],
            ['data' => 'member_out', 'name' => 'member_out', 'title' => 'จำนวนสมาชิกที่โยกออก', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap', 'footer' => '0'],
            ['data' => 'member_out_cnt', 'name' => 'member_out_cnt', 'title' => 'รวมรายการโยกออก', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap', 'footer' => '0'],
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
