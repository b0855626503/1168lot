<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\RpMemberRefTransformer;
use Gametech\Core\Contracts\Refer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;


class RpMemberRefDataTable extends DataTable
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
//        dd($query->getQuery());
        return $dataTable

            ->setTransformer(new RpMemberRefTransformer);

    }


    /**
     * @param Refer $model
     * @return mixed
     */
    public function query(Refer $model)
    {


        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');

        if (empty($startdate)) {
            $startdate = now()->toDateString();
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString();
        }


        return $model->newQuery()->active()
            ->select('refers.*')
            ->withCount(['members as total' => function (Builder $query) use ($startdate, $enddate) {
                $query->select(DB::raw('count(members.refer_code)'))
                    ->where('members.confirm', 'Y')
                    ->where('members.enable', 'Y')
                    ->whereBetween('members.date_regis', array($startdate, $enddate));
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
                            if(i == 1){
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
            ['data' => 'name', 'name' => 'name', 'title' => 'ที่มาการสมัคร', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'total', 'name' => 'total', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'footer' => '0'],
            ['data' => 'more', 'name' => 'more', 'title' => 'ดูข้อมูล', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
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
