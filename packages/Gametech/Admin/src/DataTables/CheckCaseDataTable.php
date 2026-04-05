<?php

namespace Gametech\Admin\DataTables;


use App\Exports\CheckCaseExport;
use Gametech\Admin\Transformers\CheckCaseTransformer;
use Gametech\Admin\Transformers\GameTransformer;
use Gametech\Core\Contracts\CheckCase;
use Gametech\Game\Contracts\Game;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class CheckCaseDataTable extends DataTable
{

    protected string $exportClass = CheckCaseExport::class;

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
            ->setTransformer(new CheckCaseTransformer);

    }


    /**
     * @param Game $model
     * @return mixed
     */
    public function query(CheckCase $model)
    {
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');

        if (empty($startdate)) {
            $startdate = now()->subMonths(3)->startOfMonth()->startOfDay()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }
        $bank_code = request()->input('bank_code');
        $method = request()->input('method');
        return $model->newQuery()->with('bank_account')
            ->when($bank_code, function ($query, $bank_code) {
                $query->where('bank_code', $bank_code);
            })
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', array($startdate, $enddate));
            })
            ->when($method, function ($query, $method) {
                $query->where('method', $method);
            });


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
                'stateSave' => false,
                'paging' => true,
                'searching' => true,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => false,
                'scrollX' => true,
                'scrollY' => '400px',
                'pageLength' => 100,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [100, 200, 500, 1000, 5000],
                    ['100 rows', '200 rows', '500 rows', '1000 rows', '5000 rows']
                ],
                'buttons' => [
                    'pageLength', 'excel',
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
            ['data' => 'code', 'name' => 'check_case.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap', 'width' => '3%'],
            ['data' => 'date_create', 'name' => 'check_case.date_create', 'title' => 'วันที่สร้าง', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'txid', 'name' => 'check_case.txid', 'title' => 'เลขอ้างอิง', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'bank_code', 'name' => 'check_case.bank_code', 'title' => 'ช่องทาง', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'method', 'name' => 'check_case.method', 'title' => 'ประเภท', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'username', 'name' => 'check_case.username', 'title' => 'User ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'name', 'name' => 'check_case.name', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'amount', 'name' => 'check_case.amount', 'title' => 'จำนวน', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'payamount', 'name' => 'check_case.payamount', 'title' => 'จำนวนจ่าย', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'status', 'name' => 'check_case.status', 'title' => 'สถานะ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'detail', 'name' => 'check_case.detail', 'title' => 'เลขเคส', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
            ['data' => 'url', 'name' => 'check_case.url', 'title' => 'url', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],

            ['data' => 'date_update', 'name' => 'check_case.date_update', 'title' => 'วันที่อัพเดท', 'orderable' => true, 'searchable' => false, 'className' => 'text-left text-nowrap'],
//            ['data' => 'demo', 'name' => 'check_case.name', 'title' => 'ID Test', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'batch_game', 'name' => 'check_case.batch_game', 'title' => 'บัญชีเกมได้จาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'account', 'name' => 'check_case.name', 'title' => 'บัญชีคงเหลือ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
//            ['data' => 'user_demofree' , 'name' => 'check_case.name' , 'title' => 'User Demo Free' , 'orderable' => false , 'searchable' => true , 'className' => 'text-left text-nowrap' ],
//            ['data' => 'sort' , 'name' => 'check_case.sort' , 'title' => 'ลำดับ' , 'orderable' => false , 'searchable' => true , 'className' => 'text-center text-nowrap' ],
//            ['data' => 'newuser', 'name' => 'check_case.newuser', 'title' => 'เปิดให้สมัคร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//            ['data' => 'cashback', 'name' => 'check_case.cashback', 'title' => 'เปิดฟรีเดครดิต', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'autologin', 'name' => 'check_case.autologin', 'title' => 'มีปุ่ม Login', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'gamelist', 'name' => 'check_case.gamelist', 'title' => 'แสดงรายการเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//
//            ['data' => 'status', 'name' => 'check_case.batch_game', 'title' => 'สถานะเกม', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],

//            ['data' => 'auto_open', 'name' => 'check_case.auto_open', 'title' => 'เปิดบัญชีอัตโนมัติ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'status_open', 'name' => 'check_case.status_open', 'title' => 'แสดงผล', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//              ['data' => 'newuser', 'name' => 'check_case.newuser', 'title' => 'สมัครได้', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
//            ['data' => 'enable' , 'name' => 'check_case.enable' , 'title' => 'เปิดใช้งาน' , 'orderable' => false , 'searchable' => false, 'className' => 'text-center text-nowrap' , 'width' => '3%' ],
//            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap', 'width' => '3%'],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'checkcase_datatable_' . date('Y_M_d_H_i_s');
    }
}
