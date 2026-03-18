<?php

namespace Gametech\Admin\DataTables;

use App\Exports\UsersExport;
use Gametech\Admin\Transformers\MemberTransformer;
use Gametech\Member\Contracts\Member;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;


class MemberCreditDataTable extends DataTable
{
    protected $exportClass = UsersExport::class;
//    protected $fastExcel = true;

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {
        $config = core()->getConfigData();

        $prem = bouncer()->hasPermission('wallet.member.tel');

        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->setTransformer(new MemberTransformer($config, $prem));
//        return $dataTable->setTransformer(new WithdrawTransformer);
//        return $dataTables->addColumn('action', 'admins::withdraw.datatables_confirm');
//        return $dataTable
//            ->editColumn('member_acc', function($query) {
//            return $query->bankCode->shortcode.'['.$query->memberCode->acc_no.']';
//        });
    }


    /**
     * @param Member $model
     * @return mixed
     */
    public function query(Member $model)
    {
        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');

        if (empty($startdate)) {
            $startdate = now()->subMonths(3)->startOfMonth()->startOfDay()->toDateString() . ' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString() . ' 23:59:59';
        }

        return $model->newQuery()
            ->with(['member_remark' => function ($query) {
                $query->orderBy('code', 'desc')->latest();
            }])
            ->confirm()
            ->select('members.*')
            ->with(['bank', 'up'])->withCount(['downs' => function ($model) {
                $model->active();
            }])->withCasts([
                'date_regis' => 'date:Y-m-d'
            ])
//            ->select(['members.code','members.date_regis','members.firstname','members.lastname','members.upline_code','members.acc_no','members.user_name','members.user_pass','members.lineid','members.tel','members.count_deposit','members.point_deposit','members.diamond','members.balance','members.remark','members.enable','members.status_pro','members.confirm','members.date_create'])
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('members.date_create', array($startdate, $enddate));
            })->when($user, function ($query, $user) {
                $query->where('members.user_name', $user);
            });


//        return $model->newQuery()
//            ->confirm()->active()
//            ->select('members.*')->withCount(['downs' => function ($query) {
//                $query->active();
//            }])->with(['bank','up']);


//        return $model->newQuery()->withoutGlobalScope('code')
//            ->where('members.enable','Y')->where('members.confirm','Y')->where('members.code','<>',0)
//            ->select('members.*',DB::raw('count(member.upline_code) as downs_count'))
//            ->with(['bank','up'])->join('members as member','member.upline_code','=','members.code')
//            ->groupBy('member.upline_code')
//            ->groupBy('members.code');

//        return $model->newQuery()->withoutGlobalScope('code')
//            ->where('members.enable','Y')->where('members.confirm','Y')->where('members.code','<>',0)
//            ->select(['members.*',DB::raw('count(downlines.upline_code) as downs_count')])
//            ->with(['bank','up'])->join('members as downlines', function ($join) {
//                $join->on('downlines.upline_code', '=', 'members.code')
//                    ->where('downlines.code', '<>', 0)
//                    ->where('downlines.enable', '=', 'Y')->groupBy('downlines.upline_code');
//            });

    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return Builder
     */
    public function html()
    {
        $prem = bouncer()->hasPermission('wallet.member.tel');
        if ($prem) {
            $btn = ['pageLength', 'excel'];
        } else {
            $btn = ['pageLength'];
        }


        return $this->builder()
            ->columns($this->getColumns())
            ->ajaxWithForm('', '#frmsearch')
            ->parameters([
                'dom' => 'Bfrtip',

                'processing' => true,
                'serverSide' => true,
                'responsive' => true,
                'stateSave' => false,
                'scrollX' => false,
                'paging' => true,
                'searching' => true,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,

                'pageLength' => 50,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows']
                ],
                'buttons' => $btn,
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-center text-nowrap']
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
      $config = core()->getConfigData();
      if($config->multigame_open == 'Y'){
          return [
              ['data' => 'code', 'name' => 'members.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
              ['data' => 'date_regis', 'name' => 'members.date_regis', 'title' => 'วันที่สม้คร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'firstname', 'name' => 'members.firstname', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
              ['data' => 'lastname', 'name' => 'members.lastname', 'title' => 'นามสกุล', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
              ['data' => 'up', 'name' => 'members.upline_code', 'title' => 'Upline', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
              ['data' => 'down', 'name' => 'members.upline_code', 'title' => 'Downline', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'bank', 'name' => 'bank.shortcode', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
              ['data' => 'acc_no', 'name' => 'members.acc_no', 'title' => 'เลขที่บัญชี', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
              ['data' => 'user_name', 'name' => 'members.user_name', 'title' => 'Username', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
              ['data' => 'pass', 'name' => 'members.user_pass', 'title' => 'รหัสผ่าน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'lineid', 'name' => 'members.lineid', 'title' => 'ไอดีไลน์', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
              ['data' => 'tel', 'name' => 'members.tel', 'title' => 'เบอร์โทร', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
              ['data' => 'deposit', 'name' => 'members.count_deposit', 'title' => 'ฝาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'point', 'name' => 'members.point_deposit', 'title' => 'Point', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
              ['data' => 'diamond', 'name' => 'members.diamond', 'title' => 'Diamond', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
              ['data' => 'balance', 'name' => 'members.balance', 'title' => 'Wallet', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
              ['data' => 'remark', 'name' => 'members.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//              ['data' => 'pro', 'name' => 'members.promotion', 'title' => 'รับโปร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'newuser', 'name' => 'members.promotion', 'title' => 'โปรสมาชิกใหม่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'enable', 'name' => 'members.enable', 'title' => 'เปิดใช้งาน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
          ];
      }else{
          return [
              ['data' => 'code', 'name' => 'members.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
              ['data' => 'date_regis', 'name' => 'members.date_regis', 'title' => 'วันที่สม้คร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'firstname', 'name' => 'members.firstname', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
              ['data' => 'lastname', 'name' => 'members.lastname', 'title' => 'นามสกุล', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
              ['data' => 'up', 'name' => 'members.upline_code', 'title' => 'Upline', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
              ['data' => 'down', 'name' => 'members.upline_code', 'title' => 'Downline', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'bank', 'name' => 'bank.shortcode', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
              ['data' => 'acc_no', 'name' => 'members.acc_no', 'title' => 'เลขที่บัญชี', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
              ['data' => 'user_name', 'name' => 'members.user_name', 'title' => 'Username', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
              ['data' => 'pass', 'name' => 'members.user_pass', 'title' => 'รหัสผ่าน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'lineid', 'name' => 'members.lineid', 'title' => 'ไอดีไลน์', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
              ['data' => 'tel', 'name' => 'members.tel', 'title' => 'เบอร์โทร', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
              ['data' => 'deposit', 'name' => 'members.count_deposit', 'title' => 'ฝาก', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//              ['data' => 'point', 'name' => 'members.point_deposit', 'title' => 'Point', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//              ['data' => 'diamond', 'name' => 'members.diamond', 'title' => 'Diamond', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
              ['data' => 'balance', 'name' => 'members.balance', 'title' => 'ยอดเงินคงเหลือ', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
              ['data' => 'remark', 'name' => 'members.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
              ['data' => 'pro', 'name' => 'members.promotion', 'title' => 'รับโปร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'newuser', 'name' => 'members.promotion', 'title' => 'โปรสมาชิกใหม่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'enable', 'name' => 'members.enable', 'title' => 'เปิดใช้งาน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
              ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
          ];
      }

    }

    public function fastExcelCallback()
    {


        return function ($row) {
            if ($prem = bouncer()->hasPermission('wallet.member.tel')) {
                return [
                    'Date Regis' => $row['date_regis'],
                    'UserName' => $row['user_name'],
                    'FirstName' => $row['firstname'],
                    'LastName' => $row['lastname'],
                    'Line ID' => $row['lineid'],
                    'Mobile Number' => $row['tel'],
                ];
            } else {
                return [
                    'Date Regis' => $row['date_regis'],
                    'UserName' => $row['user_name'],
                    'FirstName' => $row['firstname'],
                    'LastName' => $row['lastname'],
                    'Line ID' => $row['lineid']
                ];
            }

        };
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'member_datatable_' . time();
    }
}
