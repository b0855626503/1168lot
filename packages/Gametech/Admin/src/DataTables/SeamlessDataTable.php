<?php

namespace Gametech\Admin\DataTables;

use Carbon\Carbon;
use Gametech\Admin\Transformers\SeamlessTransformer;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class SeamlessDataTable extends DataTable
{
    protected array $latestChunkMeta = [
        'next_id' => null,
        'has_next' => false,
        'req_id' => null,
        'summary' => [
            'count' => 0,
            'stake' => 0.0,
            'payout' => 0.0,
            'scope' => 'chunk',
        ],
        'provider_message' => '',
        'provider_success' => false,
    ];

    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {

        //        $dataTable = new EloquentDataTable($query);
        $dataTable = new CollectionDataTable($query);

        return $dataTable
            ->with('stake', fn () => core()->currency((float) ($this->latestChunkMeta['summary']['stake'] ?? 0)))
            ->with('payout', fn () => core()->currency((float) ($this->latestChunkMeta['summary']['payout'] ?? 0)))
            ->with('next_id', fn () => $this->latestChunkMeta['next_id'])
            ->with('has_next', fn () => (bool) $this->latestChunkMeta['has_next'])
            ->with('req_id', fn () => $this->latestChunkMeta['req_id'])
            ->with('summary_scope', fn () => 'Summary stake/payout is for currently loaded chunk only')
            ->with('provider_message', fn () => (string) ($this->latestChunkMeta['provider_message'] ?? ''))
            ->with('provider_success', fn () => (bool) ($this->latestChunkMeta['provider_success'] ?? false))
            ->setTransformer(new SeamlessTransformer);

        //        $datatables = app('datatables');
        //        $no = 0;
        // //        $dataTable = new MongodbDataTable();
        // //        dd($dataTable->toJson());
        // //        return $dataTable->setTotalRecords(10);
        //        $data = new Collection($query);
        // //        $resource = Seamless::collection($data);
        // //        dd($resource);
        // //        return DataTabless::collection($data);
        //        return Datatables::of($data);
        //            ->setTransformer(function ($item) use ($no) {
        //            return [
        //                'code' => ++$no,
        //                'username' => $item->username,
        //                'betStatus' => $item->betStatus,
        //                'gameName' => $item->gameName,
        //                'stake' => $item->stake,
        //                'payoutStatus' => $item->payoutStatus,
        //                'payout' => $item->payout,
        //                'updatedDate' => core()->formatDate($item->updatedDate,'Y-m-d H:i:s'),
        //            ];
        //        });
        //        return json_encode($query);

    }

    //    public function ajax()
    //    {
    //        return $this->datatables
    //            ->eloquent($this->query())
    //            ->make(true);
    //    }

    public function query()
    {
        if ((string) request()->input('hasSearched', '0') !== '1') {
            return collect();
        }

        $productId = (string) request()->input('productId', request()->input('gameType', ''));
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        $nextId = request()->input('nextId');

        if (empty($startdate)) {
            $startdate = now()->startOfHour()->toIso8601String();
        }
        if (empty($enddate)) {
            $enddate = now()->startOfHour()->addHour()->toIso8601String();
        }

        try {
            $start = Carbon::parse((string) $startdate, 'Asia/Bangkok');
            $end = Carbon::parse((string) $enddate, 'Asia/Bangkok');
        } catch (\Throwable $e) {
            $this->latestChunkMeta['provider_message'] = 'invalid date range';

            return collect();
        }

        if ($end->diffInSeconds($start) > 3600 || $end->lt($start)) {
            $this->latestChunkMeta['provider_message'] = 'range query must not exceed 1 hour';

            return collect();
        }

        if ($productId === '') {
            $this->latestChunkMeta['provider_message'] = 'productId is required';

            return collect();
        }

        $res = app('Gametech\Game\Repositories\GameUserRepository')->querySeamlessBetRecords([
            'productId' => $productId,
            'startTime' => $start->toIso8601String(),
            'endTime' => $end->toIso8601String(),
            'nextId' => $nextId,
        ]);

        $this->latestChunkMeta['next_id'] = $res['next_id'] ?? null;
        $this->latestChunkMeta['has_next'] = (bool) ($res['has_next'] ?? false);
        $this->latestChunkMeta['req_id'] = $res['req_id'] ?? null;
        $this->latestChunkMeta['summary'] = (array) ($res['summary'] ?? $this->latestChunkMeta['summary']);
        $this->latestChunkMeta['provider_message'] = (string) ($res['msg'] ?? '');
        $this->latestChunkMeta['provider_success'] = (bool) ($res['success'] ?? false);

        $rows = (array) ($res['transactions'] ?? []);

        return collect($rows);

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
                'paging' => false,
                'searching' => true,
                'deferRender' => true,
                'deferLoading' => false,
                'retrieve' => true,
                'ordering' => false,
                'autoWidth' => false,
                'pageLength' => 1000,
                'order' => [[0, 'desc']],
                'lengthMenu' => [[1000], ['Chunk rows']],
                'buttons' => [
                    'pageLength',
                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-nowrap'],
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
                            if(i == 3 || i == 5){
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
            //            ['data' => 'code', 'name' => 'code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'accountingDate', 'name' => 'accountingDate', 'title' => 'Accounting Date', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'username', 'name' => 'username', 'title' => 'Username', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'gameName', 'name' => 'gameName', 'title' => 'Game Name', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'stake', 'name' => 'stake', 'title' => 'Stake', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'betStatus', 'name' => 'betStatus', 'title' => 'Bet Status', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'payout', 'name' => 'payout', 'title' => 'Payout', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'updatedDate', 'name' => 'updatedDate', 'title' => 'Updated Date', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'betId', 'name' => 'betId', 'title' => 'Bet ID', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
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
