<?php

namespace Gametech\Admin\DataTables;

use App\Exports\UsersExport;
use Gametech\Admin\Transformers\MemberTransformer;
use Gametech\Member\Contracts\Member;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class MemberDataTable extends DataTable
{
    protected $exportClass = UsersExport::class;
    //    protected $fastExcel = true;

    /**
     * Build DataTable class.
     *
     * @param  mixed  $query  Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable($query)
    {
        // ดึง config ครั้งเดียวเพื่อส่งเข้า Transformer
        $config = core()->getConfigData();

        // คำนวณสิทธิ์เป็น boolean ชัดเจน
        $canViewTel = bouncer()->hasPermission('wallet.member.tel');
        $canViewPass = bouncer()->hasPermission('wallet.member.password');

        $dataTable = new EloquentDataTable($query);

        // ส่ง boolean เข้าไปแทนการส่ง $prem ที่ไม่ชัดเจน
        return $dataTable->setTransformer(
            new MemberTransformer($config, $canViewTel, $canViewPass)
        );
    }

    /**
     * @return mixed
     */
    public function query(Member $model)
    {
        $legacy = request()->input('legacy'); // '1' หรือ '0'
        $user = request()->input('user_name');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');

        if (empty($startdate)) {
            $startdate = now()->subMonths(3)->startOfMonth()->startOfDay()->toDateString().' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString().' 23:59:59';
        }

        return $model->newQuery()
            ->confirm()
            ->leftJoin('member_deposit_stats as mds', 'mds.member_code', '=', 'members.code')
            ->select([
                'members.*',
            ])
            ->selectRaw("IF(mds.legacy_at IS NULL, 0, 1) as is_legacy")
            ->selectRaw("mds.legacy_at as legacy_at")
            ->with(['up'])
            ->withCount(['downs' => function ($q) { $q->active(); }])
            ->withCasts(['date_regis' => 'date:Y-m-d'])
            ->when($startdate, fn($q) => $q->whereBetween('members.date_create', [$startdate, $enddate]))
            ->when($legacy !== null && $legacy !== '', function ($q) use ($legacy) {
                if ((string)$legacy === '1') $q->whereNotNull('mds.legacy_at');
                if ((string)$legacy === '0') $q->whereNull('mds.legacy_at');
            })
            ->when($user, fn($q) => $q->where('members.user_name', $user));

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
                'retrieve' => false,
                'ordering' => true,

                'pageLength' => 50,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows'],
                ],
                'buttons' => [
                    'pageLength',
                    'excel',
                    [
                        'text' => '<i class="bi bi-download"></i> Export (Server)',
                        'action' => 'function ( e, dt, node, config ) {
                            let params = $("#frmsearch").serialize();
                            let url = "'.route('admin.member.export').'?" + params;
                            window.open(url, "_blank");
                        }',
                        'className' => 'btn btn-success',
                    ],
                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-center text-nowrap'],
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
        $config = core()->getConfigData();
        if ($config->seamless == 'Y') {

            return [
                ['data' => 'code', 'name' => 'members.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'date_regis', 'name' => 'members.date_regis', 'title' => 'วันที่สม้คร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'firstname', 'name' => 'members.firstname', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                ['data' => 'lastname', 'name' => 'members.lastname', 'title' => 'นามสกุล', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                ['data' => 'up', 'name' => 'members.upline_code', 'title' => 'Upline', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ['data' => 'down', 'name' => 'members.upline_code', 'title' => 'Downline', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'bank', 'name' => 'bank.name_th', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                ['data' => 'acc_no', 'name' => 'members.acc_no', 'title' => 'เลขที่บัญชี', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'user_name', 'name' => 'members.user_name', 'title' => 'UserName', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'pass', 'name' => 'members.user_pass', 'title' => 'รหัสผ่าน', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
//                ['data' => 'lineid', 'name' => 'members.lineid', 'title' => 'ไอดีไลน์', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                ['data' => 'tel', 'name' => 'members.tel', 'title' => 'เบอร์โทร', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                //                ['data' => 'wallet', 'name' => 'members.wallet_id', 'title' => 'Wallet ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'count_deposit', 'name' => 'members.count_deposit', 'title' => 'ฝาก(ครั้ง)', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
//                ['data' => 'credit', 'name' => 'members.credit', 'title' => 'เครดิตสะสม', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'balance', 'name' => 'members.balance', 'title' => 'ยอดเงิน', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'point', 'name' => 'members.point_deposit', 'title' => 'พ้อยท์', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'diamond', 'name' => 'members.diamond', 'title' => 'เพชร', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'cashback', 'name' => 'members.cashback', 'title' => 'ยอดเสีย', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'bonus', 'name' => 'members.bonus', 'title' => 'วงล้อ', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'faststart', 'name' => 'members.faststart', 'title' => 'แนะนำ', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'ic', 'name' => 'members.ic', 'title' => 'ยอดเสียเพื่อน', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
//                ['data' => 'remark', 'name' => 'members.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                //                ['data' => 'pro', 'name' => 'members.promotion', 'title' => 'รับโปร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'newuser', 'name' => 'members.promotion', 'title' => 'โปรสมาชิกใหม่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'enable', 'name' => 'members.enable', 'title' => 'เปิดใช้งาน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ];

        } elseif ($config->multigame_open == 'Y') {
            return [
                ['data' => 'code', 'name' => 'members.code', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'date_regis', 'name' => 'members.date_regis', 'title' => 'วันที่สม้คร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'firstname', 'name' => 'members.firstname', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                ['data' => 'lastname', 'name' => 'members.lastname', 'title' => 'นามสกุล', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                ['data' => 'up', 'name' => 'members.upline_code', 'title' => 'Upline', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ['data' => 'down', 'name' => 'members.upline_code', 'title' => 'Downline', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'bank', 'name' => 'bank.shortcode', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ['data' => 'acc_no', 'name' => 'members.acc_no', 'title' => 'เลขที่บัญชี', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'user_name', 'name' => 'members.user_name', 'title' => 'Username', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'pass', 'name' => 'members.user_pass', 'title' => 'รหัสผ่าน', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'lineid', 'name' => 'members.lineid', 'title' => 'ไอดีไลน์', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                ['data' => 'tel', 'name' => 'members.tel', 'title' => 'เบอร์โทร', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'wallet', 'name' => 'members.wallet_id', 'title' => 'Wallet ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],

                ['data' => 'deposit', 'name' => 'members.count_deposit', 'title' => 'ฝาก', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'point', 'name' => 'members.point_deposit', 'title' => 'Point', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'diamond', 'name' => 'members.diamond', 'title' => 'Diamond', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'balance', 'name' => 'members.balance', 'title' => 'Wallet', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'remark', 'name' => 'members.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                //              ['data' => 'pro', 'name' => 'members.promotion', 'title' => 'รับโปร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'newuser', 'name' => 'members.promotion', 'title' => 'โปรสมาชิกใหม่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'enable', 'name' => 'members.enable', 'title' => 'เปิดใช้งาน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ];
        } else {
            return [
                ['data' => 'code', 'name' => 'members.code', 'title' => '#', 'orderable' => true, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'date_regis', 'name' => 'members.date_regis', 'title' => 'วันที่สม้คร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'name', 'name' => 'members.name', 'title' => 'ชื่อ - นามสกุล', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                //                ['data' => 'firstname', 'name' => 'members.firstname', 'title' => 'ชื่อ', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                //                ['data' => 'lastname', 'name' => 'members.lastname', 'title' => 'นามสกุล', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                ['data' => 'up', 'name' => 'members.upline_code', 'title' => 'Upline', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
                ['data' => 'down', 'name' => 'members.upline_code', 'title' => 'Downline', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'bank', 'name' => 'bank.shortcode', 'title' => 'ธนาคาร', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                ['data' => 'acc_no', 'name' => 'members.acc_no', 'title' => 'เลขที่บัญชี', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'user_name', 'name' => 'members.user_name', 'title' => 'Username', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'pass', 'name' => 'members.user_pass', 'title' => 'รหัสผ่าน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'game_user', 'name' => 'members.game_user', 'title' => 'ID GAME', 'orderable' => false, 'searchable' => true, 'className' => 'text-left text-nowrap'],
                ['data' => 'tel', 'name' => 'members.tel', 'title' => 'เบอร์โทร', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                //                ['data' => 'wallet', 'name' => 'members.wallet_id', 'title' => 'Wallet ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
                ['data' => 'legacy_label', 'name' => 'is_legacy', 'title' => 'เก่า/ใหม่', 'orderable' => true, 'searchable' => false],
                ['data' => 'count_deposit', 'name' => 'members.count_deposit', 'title' => 'ฝาก (ครั้ง)', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'sum_deposit', 'name' => 'members.count_deposit', 'title' => 'ยอดฝาก (รวม)', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                ['data' => 'sum_withdraw', 'name' => 'members.count_deposit', 'title' => 'ยอดถอน (รวม)', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                //                ['data' => 'credit', 'name' => 'members.credit', 'title' => 'แต้ม', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                //                ['data' => 'diamond', 'name' => 'members.diamond', 'title' => 'Diamond', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                //                ['data' => 'balance', 'name' => 'members.balance', 'title' => 'ยอดคงเหลือ', 'orderable' => true, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                //                ['data' => 'refer', 'name' => 'members.refer', 'title' => 'รู้จักเราจาก', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                //                ['data' => 'remark', 'name' => 'members.remark', 'title' => 'หมายเหตุ', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
                //                ['data' => 'pro', 'name' => 'members.promotion', 'title' => 'รับโปร', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'newuser', 'name' => 'members.promotion', 'title' => 'โปรสมาชิกใหม่', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'cashback', 'name' => 'members.cashback', 'title' => 'ยอดเสีย', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'bonus', 'name' => 'members.bonus', 'title' => 'วงล้อ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'faststart', 'name' => 'members.faststart', 'title' => 'ค่าแนะนำ', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'ic', 'name' => 'members.ic', 'title' => 'ยอดเสียเพื่อน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'enable', 'name' => 'members.enable', 'title' => 'เปิดใช้งาน', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
                ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ];
        }

    }

    public function myexport(): StreamedResponse
    {
        /** @var \Illuminate\Database\Eloquent\Builder $builder */
        $builder = $this->query(app(\Gametech\Member\Contracts\Member::class));

        // เรียงตาม code ให้เหมือน Datatables (แก้ alias ให้ถูกเป็น `members.code`)
        $builder->orderBy('members.code', 'desc');

        $filename = $this->filename().'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-transform, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Content-Encoding' => 'identity',
        ];

        // map column จาก getColumns() → ให้ export ตามหัวตารางหน้าเว็บ
        [$columnKeys, $columnTitles] = $this->columnsForExport();

        $config = core()->getConfigData();
        $canViewTel = bouncer()->hasPermission('wallet.member.tel');
        $canViewPass = bouncer()->hasPermission('wallet.member.password');

        $transformer = new MemberTransformer($config, $canViewTel, $canViewPass, true); // plain = true

        return new StreamedResponse(function () use ($builder, $columnKeys, $columnTitles, $transformer) {
            // กันเศษ output เก่า ๆ
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            // BOM ให้ Excel อ่าน UTF-8 ถูก
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header แถวแรก
            fputcsv($out, $columnTitles);

            // ใช้ cursor() ลด memory
            foreach ($builder->cursor() as $model) {
                $row = $transformer->transform($model);

                $line = [];
                foreach ($columnKeys as $key) {
                    $val = data_get($row, $key);

                    if (is_null($val)) {
                        $val = '';
                    } elseif (is_scalar($val)) {
                        $val = $this->plainText((string) $val);
                    } else {
                        $val = $this->plainText(json_encode($val, JSON_UNESCAPED_UNICODE));
                    }

                    $line[] = $val;
                }

                fputcsv($out, $line);
            }

            fclose($out);

            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
        }, 200, $headers);
    }

    /**
     * ดึง key (data) และ title ของคอลัมน์จาก getColumns()
     * - return: [array $keys, array $titles]
     */
    protected function columnsForExport(): array
    {
        $cols = $this->getColumns();
        $keys = [];
        $titles = [];

        foreach ($cols as $col) {
            if (! isset($col['data']) || $col['data'] === null || $col['data'] === '') {
                continue;
            }
            $keys[] = $col['data'];
            $titles[] = $col['title'] ?? $col['data'];
        }

        return [$keys, $titles];
    }

    /**
     * แปลง HTML → ข้อความล้วน (strip tag + decode entity + normalize space)
     */
    protected function plainText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return (string) $text;
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return config('app.name').'_member_datatable_'.date('YmdHis');
    }
}
