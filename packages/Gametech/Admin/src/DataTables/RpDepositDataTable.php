<?php

namespace Gametech\Admin\DataTables;

use App\Exports\PaymentExport;
use Carbon\Carbon;
use Gametech\Admin\Transformers\RpDepositTransformer;
use Gametech\Payment\Contracts\BankPayment;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class RpDepositDataTable extends DataTable
{
    protected string $exportClass = PaymentExport::class;

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
            ->with('deposit', function () use ($query) {
                return core()->currency((clone $query)->sum('value'));
            })
//            ->with('sumfee', function () use ($query) {
//                return core()->currency((clone $query)->sum('fees'));
//            })
            ->with('member_count', function () use ($query) {
                return (clone $query)->distinct('member_topup')->count('member_topup');
            })
            ->with('fees_sum', function () use ($query) {
                // แปลง Eloquent\Builder เป็น Query\Builder ที่รองรับ subquery
                $qb = (clone $query)->getQuery(); // Illuminate\Database\Query\Builder
                $total = DB::query()
                    ->fromSub($qb, 't')
                    ->sum('fees');

                return core()->currency((float) $total);
            })
            ->setTransformer(new RpDepositTransformer);
    }

    /**
     * (คงไว้เพื่อ backward; ไม่ได้ใช้จริง)
     */
    public function query๘(BankPayment $model)
    {
        $ip = request()->input('ip');
        $user = request()->input('user_name');
        $channel = request()->input('channel');
        $status = request()->input('status');
        $enable = request()->input('enable');
        $bankname = request()->input('bankname');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');
        if (empty($startdate)) {
            $startdate = now()->toDateString().' 00:00:00';
        }
        if (empty($enddate)) {
            $enddate = now()->toDateString().' 23:59:59';
        }

        return $model->newQuery()
            ->with(['member', 'admin', 'promotion'])->with(['bank_account' => function ($query) {
                $query->with('bank');
            }])->withCasts([
                'bank_time' => 'datetime:Y-m-d H:i:s',
                'date_approve' => 'datetime:Y-m-d H:i:s',
            ])
            ->income()
            ->select('bank_payment.*')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('date_create', [$startdate, $enddate]);
            })
            ->when($ip, function ($query, $ip) {
                $query->where('ip_admin', 'like', '%'.$ip.'%');
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($enable, function ($query, $enable) {
                $query->where('enable', $enable);
            })
            ->when($channel, function ($query, $channel) {
                $query->where('channel', $channel);
            })
            ->when($bankname, function ($query, $bankname) {
                $query->where('account_code', $bankname);
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('bank_payment.member_topup', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
                });
            });
    }

    /**
     * Query หลัก (ใช้งานจริง)
     */
    public function query(BankPayment $model)
    {
        $req = request();
        $today = now('Asia/Bangkok')->toDateString();

        $ip = trim((string) $req->input('ip', ''));
        $user = trim((string) $req->input('user_name', ''));
        $channel = $req->input('channel');
        $status = $req->input('status');
        $enable = $req->input('enable');
        $bankname = $req->input('bankname');
        $startdate = $req->input('startDate');
        $enddate = $req->input('endDate');
        $pro = $req->input('pro_id');
        $member_status = $req->input('member_status');

        // --- Normalize dates (Asia/Bangkok) ---
        $tz = 'Asia/Bangkok';
        $start = $startdate
            ? rescue(fn () => Carbon::parse($startdate, $tz), fn () => null, true)
            : now($tz)->startOfDay();
        $end = $enddate
            ? rescue(fn () => Carbon::parse($enddate, $tz), fn () => null, true)
            : now($tz)->endOfDay();

        if (! $start) {
            $start = now($tz)->startOfDay();
        }
        if (! $end) {
            $end = now($tz)->endOfDay();
        }
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        // จำกัด scope ของ subquery ให้ครอบทั้งเดือน (RN แยกเดือนอยู่แล้ว)
        $monthStart = $start->copy()->startOfMonth();
        $monthEnd = $end->copy()->endOfMonth();

        // --- channel/bankname รองรับ CSV เป็นหลายค่า ---
        $toArray = function ($val) {
            if (is_array($val)) {
                return array_values(array_filter(array_map('trim', $val), fn ($v) => $v !== ''));
            }
            if (is_string($val) && str_contains($val, ',')) {
                return array_values(array_filter(array_map('trim', explode(',', $val)), fn ($v) => $v !== ''));
            }

            return $val !== null && $val !== '' ? [$val] : [];
        };

        $channels = $toArray($channel);
        $banks = $toArray($bankname);

        /**
         * feerank: นับลำดับรายการที่เข้าเกณฑ์นับ ต่อเดือน/ต่อบัญชี
         * เงื่อนไขเดิม: bankname=TW, status=1, enable<>'N', value>0
         */
        $eligible = DB::table('bank_payment as bp')
            ->selectRaw("
                bp.code,
                ROW_NUMBER() OVER (
                  PARTITION BY bp.bankname, bp.account_code, DATE_FORMAT(bp.bank_time, '%Y-%m')
                  ORDER BY bp.bank_time ASC, bp.code ASC
                ) AS rn
            ")
            ->where('bp.bankname', 'TW')
            ->where('bp.status', 1)
            ->where('bp.enable', '<>', 'N')
            ->where('bp.value', '>', 0)
            ->whereBetween('bp.bank_time', [$monthStart->toDateTimeString(), $monthEnd->toDateTimeString()]);

        /**
         * matchrank: ลำดับ “เฉพาะรายการที่ atranferer ตรงกับ acc_no”
         * - ตัด REGEXP ออก
         * - ไม่ join banks (ใช้ bp.bankname='TW')
         * - กัน atranferer ว่าง/NULL
         */
        $matchrank = DB::table('bank_payment as bp')
            ->join('banks_account as ba', 'ba.code', '=', 'bp.account_code')
            ->selectRaw("
                bp.code,
                ROW_NUMBER() OVER (
                  PARTITION BY bp.account_code, DATE_FORMAT(bp.bank_time, '%Y-%m')
                  ORDER BY bp.bank_time ASC, bp.code ASC
                ) AS accmatch_rn
            ")
            ->where('bp.bankname', 'TW')
            ->whereNotNull('bp.atranferer')
            ->where('bp.atranferer', '<>', '')
            ->whereColumn('ba.acc_no', 'bp.atranferer')
            ->whereBetween('bp.bank_time', [$monthStart->toDateTimeString(), $monthEnd->toDateTimeString()]);

        $q = $model->newQuery()
            // eager load เท่าที่ transformer ใช้
            ->with([
                // transformer ใช้: member->name, member->user_name, member->date_regis
                'member:code,name,user_name,date_regis',
                'admin:code,user_name',
                // transformer ใช้: promotion->name_th
                'promotion:code,name_th',
                'bank_account:code,acc_no,banks',
                'bank_account.bank:code,shortcode,filepic,name_th',
            ])
            ->withCasts([
                'bank_time' => 'datetime:Y-m-d H:i:s',
                'date_create' => 'datetime:Y-m-d H:i:s',
                'date_approve' => 'datetime:Y-m-d H:i:s',
            ])
            ->income()
            ->select('bank_payment.*')

            ->leftJoinSub($eligible, 'feerank', function ($join) {
                $join->on('feerank.code', '=', 'bank_payment.code');
            })

            ->leftJoinSub($matchrank, 'matchrank', function ($join) {
                $join->on('matchrank.code', '=', 'bank_payment.code');
            })

            ->addSelect([
                DB::raw('feerank.rn as rn'),
                DB::raw('matchrank.accmatch_rn as accmatch_rn'),
                DB::raw('
                    CASE
                      WHEN feerank.rn IS NULL THEN 0
                      WHEN feerank.rn <= 100 THEN 0
                      WHEN matchrank.accmatch_rn BETWEEN 1 AND 20 THEN 0
                      WHEN matchrank.accmatch_rn > 20 THEN 6.00
                      WHEN bank_payment.value <= 1 THEN 0
                      ELSE LEAST(ROUND(bank_payment.value * 0.029, 2), 20.00)
                    END AS fees
                '),
            ])

            ->whereBetween('date_create', [$start->toDateTimeString(), $end->toDateTimeString()])

            ->when($ip !== '', function ($q) use ($ip) {
                $q->where('ip_admin', 'like', "%{$ip}%");
            })

            ->when($pro !== null && $pro !== '', function ($q) use ($pro) {
                if ((string) $pro === '0') {
                    $q->where('pro_id', 0);
                } elseif ((string) $pro === '1') {
                    $q->where('pro_id', '>', 0);
                }
            })

            ->when($member_status === 'today', function ($q) use ($today) {
                $q->whereHas('member', fn ($m) => $m->whereDate('date_regis', $today));
            })
            ->when($member_status === 'old', function ($q) use ($today) {
                $q->whereHas('member', fn ($m) => $m->whereNull('date_regis')
                    ->orWhereDate('date_regis', '<', $today)
                );
            })

            ->when($enable !== null && $enable !== '', fn ($q) => $q->where('enable', (string) $enable))
            ->when($status !== null && $status !== '', fn ($q) => $q->where('status', (int) $status))

            ->when(! empty($channels), function ($q) use ($channels) {
                $q->whereIn('channel', $channels);
            })

            ->when(! empty($banks), function ($q) use ($banks) {
                $q->whereIn('account_code', $banks);
            })

            ->when($user !== '', function ($q) use ($user) {
                $q->whereExists(function ($sub) use ($user) {
                    $sub->select(DB::raw(1))
                        ->from('members')
                        ->whereColumn('members.code', 'bank_payment.member_topup')
                        ->where(function ($w) use ($user) {
                            $w->where('members.user_name', $user);
                        });
                });
            })

            ->orderByDesc('date_create');

        return $q;
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
                'ordering' => true,
                'autoWidth' => false,
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
            let url = "'.route('admin.rp_deposit.export').'?" + params;
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

    public function myexport(): StreamedResponse
    {
        /** @var \Illuminate\Database\Eloquent\Builder $builder */
        $builder = $this->query(app(BankPayment::class));

        $builder->orderBy('bank_payment.code', 'asc')
            ->with(['member', 'admin', 'bank_account.bank', 'promotion']);

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

        return new StreamedResponse(function () use ($builder) {
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, [
                'Bank', 'Account No', 'Bank Time', 'Channel', 'Detail',
                'User ID', 'Member Name', 'Amount', 'Fees', 'Code', 'Created At', 'Approved At', 'IP',
            ]);

            foreach ($builder->cursor() as $row) {
                $bankName = $row->bank_account->bank->name_th
                    ?? $row->bank_account->bank->name
                    ?? '';

                $memberName = $row->member->member_name
                    ?? $row->member->name
                    ?? '';

                $userName = (string) ($row->member->user_name ?? '');

                $accNo = (string) ($row->bank_account->acc_no ?? '');
                $bankTime = optional($row->bank_time)->format('Y-m-d H:i:s') ?? '';
                $channel = (string) ($row->channel ?? '');
                $detail = (string) ($row->detail ?? ($row->remark ?? ''));
                $amount = is_numeric($row->value ?? null) ? (float) $row->value : null;

                $fees = is_numeric($row->fees ?? null) ? (float) $row->fees : 0.0;

                $code = (string) ($row->code ?? '');
                $created = optional($row->date_create)->format('Y-m-d H:i:s') ?? '';
                $approved = optional($row->date_approve)->format('Y-m-d H:i:s') ?? '';
                $ip = (string) ($row->ip ?? $row->ip_admin ?? '');

                fputcsv($out, [
                    $bankName, $accNo, $bankTime, $channel, $detail,
                    $userName, $memberName, $amount, $fees, $code, $created, $approved, $ip,
                ]);
            }

            fclose($out);
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
        }, 200, $headers);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            ['data' => 'code',          'name' => 'bank_payment.code',        'title' => '#',                'orderable' => true,  'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'bank',          'name' => 'withdraws.member_name',    'title' => 'ธนาคาร',           'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'acc_no',        'name' => 'bank_payment.account_code', 'title' => 'เลขบัญชี',        'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'date',          'name' => 'withdraws.member_name',    'title' => 'เวลาธนาคาร',       'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'channel',       'name' => 'withdraws.member_name',    'title' => 'ช่องทาง',           'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'detail',        'name' => 'withdraws.member_name',    'title' => 'รายละเอียด',        'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'member_name',   'name' => 'withdraws.member_name',    'title' => 'สมาชิก',            'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'date_regis',    'name' => 'member.date_regis',        'title' => 'วันที่สมัคร',        'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'user_name',     'name' => 'member.user_name',         'title' => 'User ID',           'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'amount',        'name' => 'withdraws.user_name',      'title' => 'จำนวนเงิน',         'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'fees',          'name' => 'fees',                     'title' => 'ค่าธรรมเนียม',     'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'pro_id',        'name' => 'withdraws.pro_id',         'title' => 'โปรที่รับ',         'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'pro_amount',    'name' => 'withdraws.pro_amount',     'title' => 'โบนัสที่ได้',       'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'emp_name',      'name' => 'withdraws.emp_name',       'title' => 'ผู้ทำรายการ',        'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_create',   'name' => 'withdraws.emp_name',       'title' => 'วัน/เวลา (Add)',     'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'date_approve',  'name' => 'withdraws.emp_name',       'title' => 'วัน/เวลา (เติม)',    'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'remark',        'name' => 'withdraws.emp_name',       'title' => 'หมายเหตุ',           'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'ip',            'name' => 'bank_payment.ip',          'title' => 'ip',                 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            //            ['data' => 'action',            'name' => 'action',          'title' => 'action',                  'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return config('app.name').'_payment_datatable_'.date('YmdHis');
    }

    public function fastExcelCallback()
    {
        return function ($row) {
            return [
                'bank' => $row['bank'],
                'acc_no' => $row['acc_no'],
                'date' => $row['date'],
                'amount' => $row['amount'],
                'fee' => $row['fee'],
                'user_name' => $row['user_name'],
                'member_name' => $row['member_name'],
            ];
        };
    }
}
