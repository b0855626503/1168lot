<?php

namespace Gametech\Payment\Repositories;

use App\Events\RealTimeNewMessage;
use App\Notifications\RealTimeNotification;
use Carbon\Carbon;
use DateTime;
use Gametech\Core\Eloquent\Repository;
use Gametech\Core\Repositories\AllLogRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberDepositStatRepository;
use Gametech\Member\Repositories\MemberDiamondLogRepository;
use Gametech\Member\Repositories\MemberPointLogRepository;
use Gametech\Member\Repositories\MemberPromotionLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Member\Repositories\MemberSelectProRepository;
use Gametech\Payment\Models\BankAccount;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BankPaymentRepository extends Repository
{
    use ActivityLogger;
    use ActivityLoggerUser;

    private $memberRepository;

    private $memberCreditLogRepository;

    private $memberPromotionLogRepository;

    private $allLogRepository;

    private $paymentPromotionRepository;

    private $promotionRepository;

    private $bankAccountRepository;

    private $memberPointLogRepository;

    private $memberSelectProRepository;

    private $memberDiamondLogRepository;

    private $gameUserRepository;

    private $memberDepositStatRepository;

    public function __construct(
        MemberRepository $memberRepo,
        MemberCreditLogRepository $memberCreditLogRepo,
        AllLogRepository $allLogRepo,
        PaymentPromotionRepository $paymentPromotionRepo,
        PromotionRepository $promotionRepo,
        BankAccountRepository $bankAccountRepo,
        MemberPointLogRepository $memberPointLogRepo,
        MemberDiamondLogRepository $memberDiamondLogRepo,
        GameUserRepository $gameUserRepo,
        MemberPromotionLogRepository $memberPromotionLogRepo,
        MemberSelectProRepository $memberSelectProRepo,
        MemberDepositStatRepository $memberDepositStatRepo,
        App $app
    ) {
        $this->memberRepository = $memberRepo;

        $this->memberCreditLogRepository = $memberCreditLogRepo;

        $this->allLogRepository = $allLogRepo;

        $this->paymentPromotionRepository = $paymentPromotionRepo;

        $this->promotionRepository = $promotionRepo;

        $this->bankAccountRepository = $bankAccountRepo;

        $this->memberPointLogRepository = $memberPointLogRepo;

        $this->memberSelectProRepository = $memberSelectProRepo;

        $this->memberDiamondLogRepository = $memberDiamondLogRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->memberPromotionLogRepository = $memberPromotionLogRepo;

        $this->memberDepositStatRepository = $memberDepositStatRepo;

        parent::__construct($app);
    }

    public function loadDeposit($id, $date_start = null, $date_stop = null)
    {
        return $this->with('promotion')->orderBy('date_create', 'desc')->findWhere(['member_topup' => $id, 'enable' => 'Y', ['value', '>', 0]])
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?", [$date_start, $date_stop]);
            });

    }

    public function checkPayment_($limit = 5, $bank = 'tw')
    {

        return $this->when($bank, function ($query, $bank) {
            if ($bank === 'tw') {
                return $query->select(['bank_payment.tx_hash', 'bank_payment.*'])->distinct('tx_hash');
            } else {
                return $query->select(['bank_payment.tx_hash', 'bank_payment.*'])->distinct('tx_hash');

            }
        })->orderBy('code', 'asc')
            ->waiting()->active()->income()->where('tx_hash', '!=', '')
            ->where('bankstatus', 1)
            ->where('autocheck', 'N')
//            ->whereIn('create_by', ['SYSAUTO','BAYAUTO1','BAYAUTO2','BAYAUTO3','BAYAUTO4','BAYAUTO5'])
            ->whereNotIn('create_by', ['SCBAUTO1', 'SCBAUTO2', 'SCBAUTO3', 'SCBAUTO4', 'SCBAUTO5', 'TOPUPSCBAUTO1', 'TOPUPSCBAUTO2', 'TOPUPSCBAUTO3', 'TOPUPSCBAUTO4', 'TOPUPSCBAUTO5', 'KBANKAUTO1', 'KBANKAUTO2', 'KBANKAUTO3', 'KBANKAUTO4', 'KBANKAUTO5'])
            ->with('bank_account')
            ->whereHas('bank_account', function ($model) use ($bank) {
                $model->active()->topup()->in()->with('bank')->whereHas('bank', function ($model) use ($bank) {
                    $model->where('shortcode', strtoupper($bank));
                });
            })
            ->limit($limit)->get();

    }

    public function checkPayment($limit = 5, $bank = 'tw')
    {
        return $this->scopeQuery(function ($query) use ($limit, $bank) {
            return $query->orderBy('bank_time', 'asc')->orderBy('code', 'asc')
                ->waiting()->active()->income()
                ->where('bankstatus', 1)
                ->where('autocheck', 'N')
                ->where('member_topup', 0)
                // ✅ มี bank_account เท่านั้น และต้อง active + in + status_topup = 'Y'
                ->whereHas('bank_account', function ($q) use ($bank) {
                    $q->active()->in()->topup()->whereHas('bank', function ($model) use ($bank) {
                        $model->where('shortcode', strtoupper($bank));
                    });
                })
                ->limit($limit);
        })
            // ✅ eager load ให้สอดคล้องกับเงื่อนไขเดียวกัน
            ->with(['bank_account' => function ($q) {
                $q->active()->in()->topup()->with('bank');
            }])
            ->all();
    }

    public function loadPayment($limit = 5)
    {
        return $this->scopeQuery(function ($query) use ($limit) {
            return $query->orderBy('bank_time', 'asc')->orderBy('code', 'asc')
                ->waiting()->active()->income()
                ->where('bankstatus', 1)
                ->where('autocheck', 'W')
                ->where('member_topup', '<>', 0)
                // ✅ มี bank_account เท่านั้น และต้อง active + in + status_topup = 'Y'
                ->whereHas('bank_account', function ($q) {
                    $q->active()->in()->topup(); // topup() = status_topup = 'Y'
                })
                ->limit($limit);
        })
            // ✅ eager load ให้สอดคล้องกับเงื่อนไขเดียวกัน
            ->with(['bank_account' => function ($q) {
                $q->active()->in()->topup()->with('bank');
            }])
            ->all();
    }

    protected function isBetweenDates(string $start, string $end, ?string $current = null): bool
    {
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $currentDate = $current ? new DateTime($current) : new DateTime;

        return $currentDate >= $startDate && $currentDate <= $endDate;
    }

    public function isActiveNow($row, string $tz = 'Asia/Bangkok'): bool
    {
        $now = Carbon::now($tz);

        // รองรับชื่อ end/stop ได้ทั้งคู่
        $dateEnd = $row->date_end ?? $row->date_stop ?? null;
        $timeEnd = $row->time_end ?? $row->time_stop ?? null;

        $start = Carbon::parse(
            trim(($row->date_start ?? '').' '.($row->time_start ?? '00:00:00')),
            $tz
        );

        // ถ้าไม่ระบุเวลาสิ้นสุด → ปิดท้ายวัน 23:59:59
        $end = ($dateEnd)
            ? Carbon::parse(trim($dateEnd.' '.($timeEnd ?: '23:59:59')), $tz)
            : null;

        // inclusive ทั้งหัว-ท้าย
        return $now->gte($start) && (is_null($end) || $now->lte($end));
    }

    /**
     * ✅ อัปเดตสรุปสถิติฝาก: นับบิล + ยอดรวม (amount ไม่รวมโบนัส)
     * เงื่อนไขลูกค้าเก่า: count>=10 && sum>10000 แล้วล็อก legacy_at ครั้งแรก
     * ต้องเรียกภายใน DB::transaction()
     */
    private function updateMemberDepositStatsOnSuccess(int $memberCode, float $amount): void
    {
        $row = DB::table('member_deposit_stats')
            ->where('member_code', $memberCode)
            ->lockForUpdate()
            ->first();

        $now = now();

        if (! $row) {
            $count = 1;
            $sum = (float) $amount;
            $legacyAt = ($count >= 10 && $sum > 10000) ? $now : null;

            DB::table('member_deposit_stats')->insert([
                'member_code' => $memberCode,
                'deposit_success_count' => $count,
                'deposit_success_sum' => $sum,
                'legacy_at' => $legacyAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        $count = (int) $row->deposit_success_count + 1;
        $sum = (float) $row->deposit_success_sum + (float) $amount;

        $legacyAt = $row->legacy_at;
        if (empty($legacyAt) && $count >= 10 && $sum > 10000) {
            $legacyAt = $now; // ล็อกครั้งแรก กันสถานะแกว่ง
        }

        DB::table('member_deposit_stats')
            ->where('member_code', $memberCode)
            ->update([
                'deposit_success_count' => $count,
                'deposit_success_sum' => $sum,
                'legacy_at' => $legacyAt,
                'updated_at' => $now,
            ]);
    }

    public function refillPaymentSingle($data): bool
    {
        $ip = request()->ip();

        $now = now();
        $today = $now->toDateString();
        $datenow = $now->toDateTimeString();

        $config = $this->getCoreConfig();
        $special = false;

        $finalizeOnly = false;
        $staleSeconds = 180; // 3 นาที (ใช้สำหรับ assume DEPOSITING ว่าฝากแล้ว เพื่อกันฝากซ้ำ)

        $paymentId   = Arr::get($data, 'code');
        $memberCode  = Arr::get($data, 'member_topup');
        $accountCode = Arr::get($data, 'account_code');
        $amount      = (float) Arr::get($data, 'value', 0);
        $empTopup    = Arr::get($data, 'emp_topup');

        if (! $paymentId || ! $memberCode || ! $accountCode || $amount <= 0) {
            return false;
        }

        // ---- lock payment row + guard ด้วย deposit_status ----
        $payment = null;
        try {
            DB::transaction(function () use (&$payment, &$finalizeOnly, $paymentId, $datenow, $now, $staleSeconds, $amount, $memberCode, $ip) {
                $payment = \Gametech\Payment\Models\BankPayment::query()
                    ->where('code', $paymentId)
                    ->lockForUpdate()
                    ->first();

                if (! $payment) {
                    return;
                }

                // ถ้ารายการนี้เติมสำเร็จแล้ว กันยิงซ้ำ
                if ((int) $payment->status === 1) {
                    ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'รายการนี้สถานะสำเร็จแล้ว (payment.status=1) ข้ามการทำงาน');
                    $payment = null;
                    return;
                }

                $ds = strtoupper((string) ($payment->deposit_status ?? 'NEW'));
                $startedAt = $payment->deposit_started_at ? Carbon::parse($payment->deposit_started_at) : null;
                $isStale = $startedAt ? $startedAt->diffInSeconds($now) > $staleSeconds : false;


                // ── policy: ถ้า DEPOSITING ค้างเกิน window ให้ "assume" ว่าค่ายฝากแล้ว เพื่อกันฝากซ้ำ
                // หมายเหตุ: เคสนี้อาจทำให้บางครั้งค่ายไม่ได้ฝากจริง แต่ระบบจะไป finalize (ตาม policy ที่เลือก)
                if ($ds === 'DEPOSITING' && $isStale) {

                    $payment->deposit_status = 'DEPOSITED';
                    $payment->deposited_at = $datenow;
                    if (array_key_exists('deposit_last_error', $payment->getAttributes())) {
                        $payment->deposit_last_error = trim((string) ($payment->deposit_last_error ?? '') . ' ASSUMED_DEPOSITED_FROM_STALE_DEPOSITING');
                    }
                    $payment->save();

                    $finalizeOnly = true;
                    try {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => 0,
                        'gameuser_code' => 0,
                        'amount' => $amount,
                        'bonus' => 0,
                        'total' => $amount,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => $amount,
                        'credit_bonus' => 0,
                        'credit_total' => $amount,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $memberCode,
                        'user_name' => '',
                        'pro_code' => 0,
                        'pro_name' => '',
                        'bank_code' => 0,
                        'refer_code' => $paymentId,
                        'refer_table' => 'bank_payment',
                        'emp_code' => 0,
                        'auto' => 'Y',
                        'remark' => 'เหมือนรายการเติมเงิน รายการ #'.$paymentId.' จะมีปัญหาเติมไม่เข้า โปรดตรวจสอบ',
                        'kind' => 'LOG',
                        'user_create' => 'System Auto',
                        'user_update' => 'System Auto',
                    ]);

                    broadcast(new RealTimeNewMessage(
                        'รายการฝากเงิน #'.$payment->code.' ยอดเงิน '.$payment->amount.' เหมือนจะมีปัญหา ฝากเงินไม่ได้ รายการถูกสิ้นสุด โปรดตรวจสอบ และเติมมือให้ลูกค้า',
                        [
                            'ui' => 'toast',
                            'as' => 'RealTime.Message.All',
                            'sound' => 'withdraw',
                            'toast' => [
                                'className' => 'gt-toast gt-toast-error',
                                'duration' => 30000,
                                'gravity' => 'top',
                                'position' => 'right',
                                'avatar' => '/assets/admin/icons/deposit.webp',
                            ],
                        ]
                    ));
                    } catch (Throwable $e) {}


                    return; // ไป finalize ต่อ (ห้ามเรียก UserDeposit ซ้ำ)
                }

                if ($ds === 'FINALIZED') {
                    ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'รายการนี้ deposit_status=FINALIZED ข้ามการทำงาน');
                    $payment = null;
                    return;
                }

                if (in_array($ds, ['DEPOSITED', 'FINALIZING'], true)) {
                    $finalizeOnly = true;
                    return; // ไป finalize ต่อ
                }

                if (in_array($ds, ['PROCESSING', 'DEPOSITING'], true) && ! $isStale) {
                    ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'รายการนี้ deposit_status=' . $ds . ' และยังไม่ stale ข้ามการทำงาน');
                    $payment = null;
                    return;
                }

                // จองงาน (กันชนกัน)
                $payment->deposit_status = 'PROCESSING';
                $payment->deposit_started_at = $datenow;
                if (array_key_exists('deposit_attempt', $payment->getAttributes())) {
                    $payment->deposit_attempt = (int) ($payment->deposit_attempt ?? 0) + 1;
                }
                $payment->save();
            });
        } catch (Throwable $e) {
            report($e);
            return false;
        }


        if (! $payment) {
            return false;
        }


        $member = $this->memberRepository->find($memberCode);
        if (! $member) {
            return false;
        }

        $bank_acc = $this->bankAccountRepository->find($accountCode);
        if (! $bank_acc) {
            return false;
        }

        $game = core()->getGame();
        if (! $game) {
            return false;
        }

        $game_user = $this->gameUserRepository->findOneWhere([
            'member_code' => $member->code,
            'game_code' => $game->code,
            'enable' => 'Y',
        ]);

        if (! $game_user) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่พบ game user ที่ enable=Y ของสมาชิก', $member->code);
            return false;
        }

        $game_code    = $game->code;
        $game_name    = $game->name;
        $game_balance = (float) $game_user->balance;

        $user_name   = $game_user->user_name;
        $user_code   = $game_user->code;
        $member_code = $member->code;

        // ====== โปรโมชั่นที่เลือก ======
        $bonus = 0;
        $pro_code = 0;
        $pro_name = '';
        $total = $amount;
        $status_pro = $member->status_pro;
        $turnpro = 0;
        $withdraw_limit = 0;
        $withdraw_limit_rate = 0;

        // ✅ bank_payment.pro_id เป็น source of truth (กัน rerun แล้วโปรหลุด)
        if ((int) ($payment->pro_id ?? 0) > 0) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบ pro_id บน bank_payment (' . (int) $payment->pro_id . ') ข้ามการเช็ค selectpro', $member->code);

            $promotion = $this->promotionRepository->checkSelectPro(
                (int) $payment->pro_id,
                $member_code,
                $amount,
                $datenow
            );

            $bonus = (float) ($promotion['bonus'] ?? (float) ($payment->pro_amount ?? 0));
            $pro_code = (int) ($promotion['pro_code'] ?? (int) $payment->pro_id);
            $pro_name = (string) ($promotion['pro_name'] ?? '');
            $total = (float) ($promotion['total'] ?? ($amount + $bonus));
            $status_pro = 1;
            $turnpro = (float) ($promotion['turnpro'] ?? 0);
            $withdraw_limit = (float) ($promotion['withdraw_limit'] ?? 0);
            $withdraw_limit_rate = (float) ($promotion['withdraw_limit_rate'] ?? 0);

            // sync pro_amount ให้ล่าสุด (กันกรณีโปรใน DB ถูกแก้)
            try {
                $payment->pro_id = $pro_code;
                $payment->pro_amount = $bonus;
                $payment->save();
            } catch (Throwable $e) {
                report($e);
                return false;
            }

        } else {

            $selectpro = $this->memberSelectProRepository->findOneWhere(['member_code' => $member_code]);

            if ($selectpro) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'มีการเลือกโปรโมชั่น โปรรหัส ' . $selectpro->pro_code, $member->code);

                if ($game_balance <= (float) $config->pro_reset) {
                    ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ยอดเงินปัจจุบัน น้อยกว่าหรือเท่ากับโปรรีเซต ผ่านเงื่อนไข โปรรหัส ' . $selectpro->pro_code, $member->code);

                    $promotion = $this->promotionRepository->checkSelectPro(
                        $selectpro->pro_code,
                        $member_code,
                        $amount,
                        $datenow
                    );

                    $bonus = (float) ($promotion['bonus'] ?? 0);
                    $pro_code = (int) ($promotion['pro_code'] ?? 0);
                    $pro_name = (string) ($promotion['pro_name'] ?? '');
                    $total = (float) ($promotion['total'] ?? $amount);
                    $status_pro = 1;
                    $turnpro = (float) ($promotion['turnpro'] ?? 0);
                    $withdraw_limit = (float) ($promotion['withdraw_limit'] ?? 0);
                    $withdraw_limit_rate = (float) ($promotion['withdraw_limit_rate'] ?? 0);
                } else {
                    ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ยอดเงินปัจจุบัน มากกว่าโปรรีเซต ไม่ผ่านเงื่อนไข โปรรหัส ' . $selectpro->pro_code, $member->code);
                }

                // ✅ ต้อง save pro_id/pro_amount ลง bank_payment ก่อนฝาก เพื่อกัน rerun แล้วโปรหาย
                if ($pro_code > 0) {
                    try {
                        $payment->pro_id = $pro_code;
                        $payment->pro_amount = $bonus;
                        $payment->save();
                    } catch (Throwable $e) {
                        report($e);
                        return false;
                    }
                }

                try {
                    $selectpro->delete();
                } catch (Throwable $e) {
                    report($e);
                }
            } else {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่ได้กดรับโปร', $member->code);
            }

        }

        // ====== เงื่อนไขพิเศษตามบัญชีที่เติมเข้า ======
        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เช็คเงื่อนไขพิเศษของ บช ที่เติมเข้ามา ' . $bank_acc->acc_no, $member->code);

        if ((float) $bank_acc->bonus > 0) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบบัญชีมีโบนัสเพิ่ม ' . $bank_acc->bonus . '% ของ บช ' . $bank_acc->acc_no, $member->code);

            $isActive = $this->isBetweenDates($bank_acc->start_at, $bank_acc->end_at, $now);
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ตรวจสอบช่วงเวลาโบนัสของ บช ' . $bank_acc->acc_no, $member->code);

            if ($isActive) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'อยู่ในช่วงกิจกรรมโบนัส บช ' . $bank_acc->acc_no, $member->code);

                if ($pro_code === 0) {
                    $bonusSpecial = ($amount * (float) $bank_acc->bonus) / 100;

                    if ((float) $bank_acc->bonus_max > 0 && $bonusSpecial > (float) $bank_acc->bonus_max) {
                        $bonusSpecial = (float) $bank_acc->bonus_max;
                    }

                    ActivityLoggerUser::activity(
                        'Single Topup ID : ' . $paymentId,
                        'คำนวนโบนัสพิเศษจากยอดฝาก ' . $amount . ' ได้โบนัส ' . $bonusSpecial . ' (' . $bank_acc->bonus . '%) บช ' . $bank_acc->acc_no,
                        $member->code
                    );

                    $bonus = $bonusSpecial;
                    $pro_name = 'ช่วงเวลา พิเศษ รับยอดเพิ่มขึ้น ' . $bank_acc->bonus . '% จากยอดฝาก';
                    $total = $total + $bonus;
                    $special = true;
                }
            }
        } else {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'บัญชีนี้ไม่มีโบนัสเพิ่ม บช ' . $bank_acc->acc_no, $member->code);
        }

        $point = 0;
        $diamond = 0;
        $count_deposit = 1;

        // checkpoint: บางเคส relation bank มีปัญหา/DB สะดุด จะได้รู้ว่าตายตรงนี้หรือไม่
        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'กำลังดึง bank_code จาก bank_acc', $member->code);
        $bank_code = optional($bank_acc->bank)->code ?? 0;

        $credit_before = $game_balance;
        $credit_after  = $credit_before + $total;

        // ✅ ถ้าค่ายฝากสำเร็จแล้ว (deposit_status=DEPOSITED/FINALIZING) ให้ข้ามฝากซ้ำ และไป finalize-only
        if ($finalizeOnly) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'deposit_status=DEPOSITED/FINALIZING -> ข้าม UserDeposit() และจะ finalize-only', $member->code);
            $response = [
                'success' => true,
                'before' => $payment->before_credit ?? 0,
                'after' => $payment->after_credit ?? 0,
                'ref_id' => '',
            ];
            // ไปต่อที่ transaction ใหญ่ด้านล่าง โดยต้องมี $alllog
        }

        /**
         * ===========================
         * กันเคส "alllog ค้าง" แล้ว block ตลอดกาล
         * - ถ้าเจอ alllog แต่ payment ยังไม่ complete => ถือว่า orphan จาก crash รอบก่อน
         *   -> ลบ/mark failed แล้วทำต่อได้
         * - ถ้า payment complete แล้ว => return false ตามเดิม
         * ===========================
         */
        $chk = $this->allLogRepository->findOneByField('bank_payment_id', $paymentId);
        if ($chk) {
            if ($finalizeOnly) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบ alllog และเป็น finalize-only -> จะไม่ลบ alllog', $member->code);
                $alllog = $chk;
            } else {
                // ถ้า payment ยังไม่สำเร็จ ถือว่า alllog อาจค้างจาก crash/exception รอบก่อน
                if ((int) $payment->status !== 1) {
                    ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบ alllog ค้างแต่ payment ยังไม่ complete -> จะลบ/mark failed เพื่อให้ทำงานต่อได้', $member->code);

                    try {
                        // พยายามลบก่อน (คง behavior เดิม)
                        $chk->delete();
                    } catch (Throwable $e) {
                        // ถ้าลบไม่ได้อย่างน้อย mark ไว้ว่าเสีย เพื่อไม่ block รอบหน้า
                        try {
                            $chk->status_log = 9; // failed/orphan
                            $chk->remark = trim((string) $chk->remark) . ' [AUTO] orphan cleanup failed delete @ ' . $datenow;
                            $chk->user_update = 'System Auto';
                            $chk->save();
                        } catch (Throwable $ex) {
                            report($ex);
                        }

                        report($e);
                        return false;
                    }
                } else {
                    ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบรายการเติมเงินนี้ในระบบแล้ว', $member->code);
                    return false;
                }
            }
        }

        if (! isset($alllog)) {
            try {
                $alllog = $this->allLogRepository->create([
                    'before_credit' => $credit_before,
                    'after_credit' => $credit_after,
                    'status_log' => 0,
                    'pro_id' => $pro_code,
                    'pro_amount' => $bonus,
                    'bonus' => $bonus,
                    'game_code' => $game_code,
                    'type_record' => 0,
                    'gamebalance' => $game_balance,
                    'member_code' => $member_code,
                    'member_user' => $member->user_name,
                    'amount' => $amount,
                    'bank_payment_id' => $paymentId,
                    'ip' => $ip,
                    'username' => $user_name,
                    'remark' => '',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);
            } catch (Throwable $e) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่สามารถเพิ่มรายการ all log ได้');
                report($e);
                return false;
            }
        }

        $money_text = 'User ' . $member->user_name . ' Game ID : ' . $user_name . ' จำนวนเงิน ' . $amount . ' โบนัส ' . $bonus . ' จากโปร ' . $pro_name . ' รวมเป็น ' . $total;

        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เริ่มรายการเติมเงินให้กับ User : ' . $member->user_name . ' Game ID : ' . $user_name);
        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, $money_text);

        /**
         * ===========================
         * จุดปิดรูรั่วหลัก: ครอบ UserDeposit() ด้วย try/catch
         * เพื่อกัน "exception หลุด" แล้วทิ้ง alllog ค้าง
         * ===========================
         */
        if (! $finalizeOnly) {

            try {

                // mark DEPOSITING ก่อนเรียกค่ายเกม (กัน rerun มองสถานะ)
                try {
                    $payment->deposit_status = 'DEPOSITING';
                    $payment->deposit_started_at = $datenow;
                    $payment->save();
                } catch (Throwable $e) {
                    report($e);
                    return false;
                }

                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'กำลังเรียก UserDeposit() ฝากเข้าเกม', $member->code);
                $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $total, false);
            } catch (Throwable $e) {
                ActivityLoggerUser::activity(
                    'Single ฝากเงินเข้าเกม ' . $game_name,
                    $money_text . ' เกิด exception ตอนฝากเข้าเกม (จะลบ/mark failed alllog)'
                );

                try {
                    $alllog->delete();
                } catch (Throwable $ex) {
                    try {
                        $alllog->status_log = 9; // failed
                        $alllog->remark = trim((string) $alllog->remark) . ' [AUTO] delete failed after deposit exception @ ' . $datenow;
                        $alllog->user_update = 'System Auto';
                        $alllog->save();
                    } catch (Throwable $ex2) {
                        report($ex2);
                    }
                    report($ex);
                }

                report($e);
                return false;
            }

            // กันเคส provider คืนค่าไม่เป็น array
            if (! is_array($response)) {
                $response = ['success' => false, 'msg' => 'Invalid deposit response'];
            }

            if (! ($response['success'] ?? false)) {

                // update deposit_status=FAILED เพื่อกันรอบถัดไปถูก assume เป็น DEPOSITED
//                try {
//                    $payment->deposit_status = 'FAILED';
//                    if (array_key_exists('deposit_last_error', $payment->getAttributes())) {
//                        $msg = (string) ($response['msg'] ?? $response['message'] ?? '');
//                        $msg = $msg !== '' ? (' PROVIDER_FAILED:' . $msg) : ' PROVIDER_FAILED';
//                        $payment->deposit_last_error = trim((string) ($payment->deposit_last_error ?? '') . $msg);
//                    }
//                    $payment->save();
//                } catch (Throwable $ex3) {
//                    report($ex3);
//                }

                ActivityLoggerUser::activity(
                    'Single ฝากเงินเข้าเกม ' . $game_name,
                    $money_text . ' ไม่สามารถฝากเงินเข้าเกมได้ ระบบจะลบรายการ all log ที่สร้างไว้'
                );

                try {
                    $alllog->delete();
                } catch (Throwable $e) {
                    ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ลบ all log ไม่สำเร็จ หลังจากฝากเงินเข้าเกมไม่สำเร็จ');
                    report($e);
                }

                return false;
            }

            // ✅ สำเร็จแล้ว: update deposit_status=DEPOSITED ให้เร็วที่สุด + เก็บ before/after ไว้สำหรับ finalize-only
            try {
                $payment->deposit_status = 'DEPOSITED';
                $payment->deposited_at = $datenow;
                $payment->before_credit = $response['before'] ?? 0;
                $payment->after_credit = $response['after'] ?? 0;
                $payment->user_id = $user_name;
                $payment->save();
            } catch (Throwable $e) {
                report($e);
                return false;
            }

            ActivityLoggerUser::activity(
                'Single ฝากเงินเข้าเกม ' . $game_name,
                $money_text . ' ระบบทำการฝากเงินเข้าเกมแล้ว ยอดก่อน ' . ($response['before'] ?? '-') . ' ยอดหลัง ' . ($response['after'] ?? '-')
            );
        }


        try {
            DB::transaction(function () use (
                $ip,
                $paymentId,
                $datenow,
                $today,
                $config,
                $special,
                $bank_acc,
                $payment,
                $member,
                $game_code,
                $game_name,
                $user_name,
                $user_code,
                $bank_code,
                $amount,
                $bonus,
                $total,
                $pro_code,
                $pro_name,
                $status_pro,
                $turnpro,
                $withdraw_limit,
                $withdraw_limit_rate,
                $count_deposit,
                $response,
                $alllog,
                $empTopup,
                &$point,
                &$diamond
            ) {
                /** @var \Illuminate\Database\Eloquent\Model $lockedGameUser */
                $lockedGameUser = $this->gameUserRepository->find($user_code);
                if (! $lockedGameUser) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'ไม่พบ game_user ระหว่างทำ transaction (จะ rollback)');
                    throw new \RuntimeException('Missing game_user');
                }

                $pk = method_exists($lockedGameUser, 'getKeyName') ? $lockedGameUser->getKeyName() : 'code';
                $id = method_exists($lockedGameUser, 'getKey') ? $lockedGameUser->getKey() : $user_code;

                $lockedGameUser = $lockedGameUser->newQuery()
                    ->where($pk, $id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedGameUser) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'lockForUpdate ไม่เจอแถว game_user (จะ rollback)');
                    throw new \RuntimeException('Lock game_user failed');
                }

                $sum_amount_turn  = ((float) $lockedGameUser->balance + ($total * (float) $turnpro));
                $sum_amount_limit = ((float) $lockedGameUser->balance + (($amount + $bonus) * (float) $withdraw_limit_rate));

                ActivityLoggerUser::activity(
                    'Single Topup ID : ' . $paymentId,
                    'DEBUG(sum): locked_balance=' . (float) $lockedGameUser->balance
                    . ' total=' . $total
                    . ' turnpro=' . (float) $turnpro
                    . ' rate=' . (float) $withdraw_limit_rate
                    . ' => sum_turn=' . $sum_amount_turn
                    . ' sum_limit=' . $sum_amount_limit,
                    $member->code
                );

                $chknew = $this->memberCreditLogRepository->findOneWhere([
                    'member_code' => $member->code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'kind' => 'TOPUP',
                ]);

                if ($chknew) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'หยุดการทำงาน เนื่องจาก Log ซ้ำ (จะ rollback)');
                    throw new \RuntimeException('Duplicate member_credit_log');
                }

                $remark = $special
                    ? ('ช่วงเวลาสุดพิเศษ ' . $bank_acc->start_at . ' ถึง ' . $bank_acc->end_at . ' รับเพิ่ม ' . $bank_acc->bonus . '% อิงรายการฝาก ID ' . $paymentId)
                    : ('เติมเงินฝากอ้างอิงรายการฝาก ID : ' . $paymentId . ' RefID : ' . ($response['ref_id'] ?? ''));

                $bill = $this->memberCreditLogRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'amount' => $amount,
                    'bonus' => $bonus,
                    'total' => $total,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_total' => $total,
                    'credit_before' => $response['before'] ?? 0,
                    'credit_after' => $response['after'] ?? 0,
                    'member_code' => $member->code,
                    'user_name' => $member->user_name,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'bank_code' => $bank_code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'emp_code' => $empTopup,
                    'auto' => 'Y',
                    'remark' => $remark,
                    'kind' => 'TOPUP',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);

                if ($special) {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => $game_code,
                        'gameuser_code' => $user_code,
                        'amount' => 0,
                        'bonus' => $bonus,
                        'total' => 0,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'credit_bonus' => $bonus,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'bank_code' => $bank_code,
                        'refer_code' => $paymentId,
                        'refer_table' => 'bank_payment',
                        'emp_code' => $empTopup,
                        'auto' => 'Y',
                        'remark' => 'อ้างอิงรายการฝาก ID : ' . $paymentId . ' RefID : ' . ($response['ref_id'] ?? '') . ' ได้โบนัสจากช่องทางการฝาก เพิ่ม ' . $bank_acc->bonus . '%',
                        'kind' => 'G_BONUS',
                        'user_create' => 'System Auto',
                        'user_update' => 'System Auto',
                    ]);
                }

                $alllog->remark = 'Auto Topup and Refer Credit Log ID : ' . $bill->code;
                $alllog->user_update = 'System Auto';
                $alllog->save();

                // ====== point ======
                if ($config->point_open == 'Y') {
                    if ($config->point_per_bill == 'N') {
                        if ($amount >= $config->points && $config->points > 0) {
                            $point = (int) floor($amount / $config->points);

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน ' . $amount . ' บาท เติม ' . $config->points . ' ได้รับ 1 แต้ม สรุปได้รับ ' . $point,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->points_topup && $config->points_topup > 0 && $config->points_amount > 0) {
                            $point = (int) $config->points_amount;

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน ' . $amount . ' บาท (นับเป็นบิล) เติมยอด >= ' . $config->points_topup . ' ได้รับ ' . $point . ' แต้ม',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                // ====== diamond ======
                if ($config->diamond_open == 'Y') {
                    if ($config->diamond_per_bill == 'N') {
                        if ($amount >= $config->diamonds && $config->diamonds > 0) {
                            $diamond = (int) floor($amount / $config->diamonds);

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน ' . $amount . ' บาท เติม ' . $config->diamonds . ' ได้รับ 1 เม็ด สรุปได้รับ ' . $diamond,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->diamonds_topup && $config->diamonds_topup > 0 && $config->diamonds_amount > 0) {
                            $diamond = (int) $config->diamonds_amount;

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน ' . $amount . ' บาท (นับเป็นบิล) เติมยอด >= ' . $config->diamonds_topup . ' ได้รับ ' . $diamond . ' เม็ด',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                // ====== update payment ======
                $payment->user_id = $user_name;
                $payment->status = 1;
                $payment->before_credit = $response['before'] ?? 0;
                $payment->after_credit = $response['after'] ?? 0;
                $payment->pro_id = $pro_code;
                $payment->amount = $amount;
                $payment->pro_amount = $bonus;
                $payment->score = $total;
                $payment->date_topup = $datenow;
                $payment->date_approve = $datenow;
                $payment->autocheck = 'Y';
                $payment->remark_admin = trim((string) $payment->remark_admin) . ' (เติมแล้ว)';
                $payment->topup_by = 'System Auto';
                $payment->ip_topup = $ip;
                $payment->deposit_status = 'FINALIZED';
                $payment->finalized_at = $datenow;
                $payment->save();

                // ====== bill ======
                $bills = app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'ref_id' => $response['ref_id'] ?? '',
                    'credit_before' => $response['before'] ?? 0,
                    'credit_after' => $response['after'] ?? 0,
                    'member_code' => $member->code,
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'method' => 'TOPUP',
                    'transfer_type' => 1,
                    'amount' => $amount,
                    'balance_before' => $response['before'] ?? 0,
                    'balance_after' => $response['after'] ?? 0,
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_balance' => $total,
                    'amount_request' => $sum_amount_turn,
                    'amount_limit' => $sum_amount_limit,
                    'ip' => $ip,
                    'user_create' => $member->name,
                    'user_update' => $member->name,
                ]);

                // ====== promotion effect on game_user ======
                $billcode = 0;

                ActivityLoggerUser::activity(
                    'Single Topup ID : ' . $paymentId,
                    'DEBUG(beforeUpdate): amount_balance=' . (float) $lockedGameUser->amount_balance
                    . ' withdraw_limit_amount=' . (float) $lockedGameUser->withdraw_limit_amount,
                    $member->code
                );

                if ($pro_code > 0) {
                    $this->memberPromotionLogRepository->create([
                        'date_start' => now()->toDateString(),
                        'bill_code' => $bills->code,
                        'member_code' => $member->code,
                        'game_code' => $game_code,
                        'game_name' => $game_name,
                        'gameuser_code' => $user_code,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'turnpro' => $turnpro,
                        'balance' => (($response['before'] ?? 0) - $amount),
                        'amount' => $amount,
                        'bonus' => $bonus,
                        'amount_balance' => $sum_amount_turn,
                        'total_amount_balance' => $sum_amount_limit,
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_rate' => 0,
                        'complete' => 'N',
                        'enable' => 'Y',
                        'user_create' => $member->name,
                        'user_update' => $member->name,
                    ]);

                    $billcode = $bills->code;

                    $lockedGameUser->balance = $response['after'] ?? $lockedGameUser->balance;
                    $lockedGameUser->pro_code = $pro_code;
                    $lockedGameUser->bill_code = $billcode;
                    $lockedGameUser->turnpro = $turnpro;
                    $lockedGameUser->amount = $amount;
                    $lockedGameUser->bonus = $bonus;

                    $lockedGameUser->amount_balance = $sum_amount_turn;
                    $lockedGameUser->withdraw_limit = $withdraw_limit;
                    $lockedGameUser->withdraw_limit_rate = $withdraw_limit_rate;
                    $lockedGameUser->withdraw_limit_amount = $sum_amount_limit;

                    $lockedGameUser->save();
                } else {
                    if ($lockedGameUser->amount_balance > 0 || $lockedGameUser->pro_code > 0) {
                        if (($response['before'] ?? 0) > (float) $config->pro_reset) {
                            $lockedGameUser->amount_balance += ($total * (float) $lockedGameUser->turnpro);
                            $lockedGameUser->withdraw_limit_amount += ($total * (float) $lockedGameUser->withdraw_limit_rate);
                            $lockedGameUser->save();
                        } else {
                            $lockedGameUser->balance = $response['after'] ?? $lockedGameUser->balance;
                            $lockedGameUser->pro_code = 0;
                            $lockedGameUser->bill_code = 0;
                            $lockedGameUser->turnpro = 0;
                            $lockedGameUser->amount = 0;
                            $lockedGameUser->bonus = 0;
                            $lockedGameUser->amount_balance = 0;
                            $lockedGameUser->withdraw_limit = 0;
                            $lockedGameUser->withdraw_limit_rate = 0;
                            $lockedGameUser->withdraw_limit_amount = 0;
                            $lockedGameUser->save();
                        }
                    }
                }

                ActivityLoggerUser::activity(
                    'Single Topup ID : ' . $paymentId,
                    'DEBUG(afterUpdate): amount_balance=' . (float) $lockedGameUser->amount_balance
                    . ' withdraw_limit_amount=' . (float) $lockedGameUser->withdraw_limit_amount,
                    $member->code
                );

                $bill->amount_balance = $lockedGameUser->amount_balance;
                $bill->withdraw_limit = $lockedGameUser->withdraw_limit;
                $bill->withdraw_limit_amount = $lockedGameUser->withdraw_limit_amount;
                $bill->save();

                // ====== update member ======
                $member->credit += $amount;
                $member->sum_deposit += $amount;
                $member->status_pro = $status_pro;
                $member->point_deposit += $point;
                $member->diamond += $diamond;
                $member->balance = $response['after'] ?? $member->balance;
                $member->count_deposit += $count_deposit;
                $member->save();

                $this->updateMemberDepositStatsOnSuccess((int) $member->code, (float) $amount);
            });
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบปัญหาใน Transaction');
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'Rollback Transaction');

            try {
                $rollbackRes = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $total);
                if (($rollbackRes['success'] ?? false) === true) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'Rollback: ถอนเงินออกจากเกมแล้ว');
                } else {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'Rollback: ไม่สามารถถอนเงินออกจากเกมได้');
                }
            } catch (Throwable $ex) {
                report($ex);
            }

            try {
                $this->allLogRepository->where('bank_payment_id', $paymentId)->delete();
            } catch (Throwable $ex) {
                report($ex);
            }

            report($e);
            return false;
        }

        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เติมเงินสำเร็จให้กับ User : ' . $member->user_name);
        Notification::send($member, new RealTimeNotification(Lang::get('app.topup.complete') . $total));

        // ====== update sum_deposit account ======
        $account = $payment->bank_account;

        $sumToday = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->income()
            ->active()
            ->complete()
            ->where('account_code', $payment->account_code)
            ->whereDate('date_topup', $today)
            ->sum('value');

        $account->update(['sum_deposit' => $sumToday]);

        if ((float) $account->sum_limit > 0 && $sumToday >= (float) $account->sum_limit) {
            $account->update(['display_wallet' => 'N']);

            $alt = BankAccount::where('banks', $account->banks)
                ->where('bank_type', 1)
                ->where('display_wallet', 'N')
                ->where('status_auto', 'Y')
                ->where('enable', 'Y')
                ->where('sum_deposit', 0)
                ->orderBy('sort', 'asc')
                ->first();

            if ($alt) {
                $alt->update(['display_wallet' => 'Y']);
            }
        }

        return true;
    }

    public function refillPaymentSingle_backup($data): bool
    {
        $ip = request()->ip();

        $now = now();
        $today = $now->toDateString();
        $datenow = $now->toDateTimeString();

        $config = $this->getCoreConfig();
        $special = false;

        $paymentId   = Arr::get($data, 'code');
        $memberCode  = Arr::get($data, 'member_topup');
        $accountCode = Arr::get($data, 'account_code');
        $amount      = (float) Arr::get($data, 'value', 0);
        $empTopup    = Arr::get($data, 'emp_topup');

        if (! $paymentId || ! $memberCode || ! $accountCode || $amount <= 0) {
            return false;
        }

        $payment = $this->find($paymentId);
        if (! $payment) {
            return false;
        }

        // ถ้ารายการนี้เติมสำเร็จแล้ว กันยิงซ้ำ
        if ((int) $payment->status === 1) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'รายการนี้สถานะสำเร็จแล้ว (payment.status=1) ข้ามการทำงาน');
            return false;
        }

        $member = $this->memberRepository->find($memberCode);
        if (! $member) {
            return false;
        }

        $bank_acc = $this->bankAccountRepository->find($accountCode);
        if (! $bank_acc) {
            return false;
        }

        $game = core()->getGame();
        if (! $game) {
            return false;
        }

        $game_user = $this->gameUserRepository->findOneWhere([
            'member_code' => $member->code,
            'game_code' => $game->code,
            'enable' => 'Y',
        ]);

        if (! $game_user) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่พบ game user ที่ enable=Y ของสมาชิก', $member->code);
            return false;
        }

        $game_code    = $game->code;
        $game_name    = $game->name;
        $game_balance = (float) $game_user->balance;

        $user_name   = $game_user->user_name;
        $user_code   = $game_user->code;
        $member_code = $member->code;

        // ====== โปรโมชั่นที่เลือก ======
        $bonus = 0;
        $pro_code = 0;
        $pro_name = '';
        $total = $amount;
        $status_pro = $member->status_pro;
        $turnpro = 0;
        $withdraw_limit = 0;
        $withdraw_limit_rate = 0;

        $selectpro = $this->memberSelectProRepository->findOneWhere(['member_code' => $member_code]);

        if ($selectpro) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'มีการเลือกโปรโมชั่น โปรรหัส ' . $selectpro->pro_code, $member->code);

            if ($game_balance <= (float) $config->pro_reset) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ยอดเงินปัจจุบัน น้อยกว่าหรือเท่ากับโปรรีเซต ผ่านเงื่อนไข โปรรหัส ' . $selectpro->pro_code, $member->code);

                $promotion = $this->promotionRepository->checkSelectPro(
                    $selectpro->pro_code,
                    $member_code,
                    $amount,
                    $datenow
                );

                $bonus = (float) ($promotion['bonus'] ?? 0);
                $pro_code = (int) ($promotion['pro_code'] ?? 0);
                $pro_name = (string) ($promotion['pro_name'] ?? '');
                $total = (float) ($promotion['total'] ?? $amount);
                $status_pro = 1;
                $turnpro = (float) ($promotion['turnpro'] ?? 0);
                $withdraw_limit = (float) ($promotion['withdraw_limit'] ?? 0);
                $withdraw_limit_rate = (float) ($promotion['withdraw_limit_rate'] ?? 0);
            } else {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ยอดเงินปัจจุบัน มากกว่าโปรรีเซต ไม่ผ่านเงื่อนไข โปรรหัส ' . $selectpro->pro_code, $member->code);
            }

            try {
                $selectpro->delete();
            } catch (Throwable $e) {
                report($e);
            }
        } else {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่ได้กดรับโปร', $member->code);
        }

        // ====== เงื่อนไขพิเศษตามบัญชีที่เติมเข้า ======
        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เช็คเงื่อนไขพิเศษของ บช ที่เติมเข้ามา ' . $bank_acc->acc_no, $member->code);

        if ((float) $bank_acc->bonus > 0) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบบัญชีมีโบนัสเพิ่ม ' . $bank_acc->bonus . '% ของ บช ' . $bank_acc->acc_no, $member->code);

            $isActive = $this->isBetweenDates($bank_acc->start_at, $bank_acc->end_at, $now);
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ตรวจสอบช่วงเวลาโบนัสของ บช ' . $bank_acc->acc_no, $member->code);

            if ($isActive) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'อยู่ในช่วงกิจกรรมโบนัส บช ' . $bank_acc->acc_no, $member->code);

                if ($pro_code === 0) {
                    $bonusSpecial = ($amount * (float) $bank_acc->bonus) / 100;

                    if ((float) $bank_acc->bonus_max > 0 && $bonusSpecial > (float) $bank_acc->bonus_max) {
                        $bonusSpecial = (float) $bank_acc->bonus_max;
                    }

                    ActivityLoggerUser::activity(
                        'Single Topup ID : ' . $paymentId,
                        'คำนวนโบนัสพิเศษจากยอดฝาก ' . $amount . ' ได้โบนัส ' . $bonusSpecial . ' (' . $bank_acc->bonus . '%) บช ' . $bank_acc->acc_no,
                        $member->code
                    );

                    $bonus = $bonusSpecial;
                    $pro_name = 'ช่วงเวลา พิเศษ รับยอดเพิ่มขึ้น ' . $bank_acc->bonus . '% จากยอดฝาก';
                    $total = $total + $bonus;
                    $special = true;
                }
            }
        } else {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'บัญชีนี้ไม่มีโบนัสเพิ่ม บช ' . $bank_acc->acc_no, $member->code);
        }

        $point = 0;
        $diamond = 0;
        $count_deposit = 1;

        // checkpoint: บางเคส relation bank มีปัญหา/DB สะดุด จะได้รู้ว่าตายตรงนี้หรือไม่
        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'กำลังดึง bank_code จาก bank_acc', $member->code);
        $bank_code = optional($bank_acc->bank)->code ?? 0;

        $credit_before = $game_balance;
        $credit_after  = $credit_before + $total;

        /**
         * ===========================
         * กันเคส "alllog ค้าง" แล้ว block ตลอดกาล
         * - ถ้าเจอ alllog แต่ payment ยังไม่ complete => ถือว่า orphan จาก crash รอบก่อน
         *   -> ลบ/mark failed แล้วทำต่อได้
         * - ถ้า payment complete แล้ว => return false ตามเดิม
         * ===========================
         */
        $chk = $this->allLogRepository->findOneByField('bank_payment_id', $paymentId);
        if ($chk) {
            // ถ้า payment ยังไม่สำเร็จ ถือว่า alllog อาจค้างจาก crash/exception รอบก่อน
            if ((int) $payment->status !== 1) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบ alllog ค้างแต่ payment ยังไม่ complete -> จะลบ/mark failed เพื่อให้ทำงานต่อได้', $member->code);

                try {
                    // พยายามลบก่อน (คง behavior เดิม)
                    $chk->delete();
                } catch (Throwable $e) {
                    // ถ้าลบไม่ได้อย่างน้อย mark ไว้ว่าเสีย เพื่อไม่ block รอบหน้า
                    try {
                        $chk->status_log = 9; // failed/orphan
                        $chk->remark = trim((string) $chk->remark) . ' [AUTO] orphan cleanup failed delete @ ' . $datenow;
                        $chk->user_update = 'System Auto';
                        $chk->save();
                    } catch (Throwable $ex) {
                        report($ex);
                    }

                    report($e);
                    return false;
                }
            } else {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบรายการเติมเงินนี้ในระบบแล้ว', $member->code);
                return false;
            }
        }

        try {
            $alllog = $this->allLogRepository->create([
                'before_credit' => $credit_before,
                'after_credit' => $credit_after,
                'status_log' => 0,
                'pro_id' => $pro_code,
                'pro_amount' => $bonus,
                'bonus' => $bonus,
                'game_code' => $game_code,
                'type_record' => 0,
                'gamebalance' => $game_balance,
                'member_code' => $member_code,
                'member_user' => $member->user_name,
                'amount' => $amount,
                'bank_payment_id' => $paymentId,
                'ip' => $ip,
                'username' => $user_name,
                'remark' => '',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
            ]);
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่สามารถเพิ่มรายการ all log ได้');
            report($e);
            return false;
        }

        $money_text = 'User ' . $member->user_name . ' Game ID : ' . $user_name . ' จำนวนเงิน ' . $amount . ' โบนัส ' . $bonus . ' จากโปร ' . $pro_name . ' รวมเป็น ' . $total;

        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เริ่มรายการเติมเงินให้กับ User : ' . $member->user_name . ' Game ID : ' . $user_name);
        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, $money_text);

        /**
         * ===========================
         * จุดปิดรูรั่วหลัก: ครอบ UserDeposit() ด้วย try/catch
         * เพื่อกัน "exception หลุด" แล้วทิ้ง alllog ค้าง
         * ===========================
         */
        try {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'กำลังเรียก UserDeposit() ฝากเข้าเกม', $member->code);
            $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $total, false);
        } catch (Throwable $e) {
            ActivityLoggerUser::activity(
                'Single ฝากเงินเข้าเกม ' . $game_name,
                $money_text . ' เกิด exception ตอนฝากเข้าเกม (จะลบ/mark failed alllog)'
            );

            try {
                $alllog->delete();
            } catch (Throwable $ex) {
                try {
                    $alllog->status_log = 9; // failed
                    $alllog->remark = trim((string) $alllog->remark) . ' [AUTO] delete failed after deposit exception @ ' . $datenow;
                    $alllog->user_update = 'System Auto';
                    $alllog->save();
                } catch (Throwable $ex2) {
                    report($ex2);
                }
                report($ex);
            }

            report($e);
            return false;
        }

        // กันเคส provider คืนค่าไม่เป็น array
        if (! is_array($response)) {
            $response = ['success' => false, 'msg' => 'Invalid deposit response'];
        }

        if (! ($response['success'] ?? false)) {
            ActivityLoggerUser::activity(
                'Single ฝากเงินเข้าเกม ' . $game_name,
                $money_text . ' ไม่สามารถฝากเงินเข้าเกมได้ ระบบจะลบรายการ all log ที่สร้างไว้'
            );

            try {
                $alllog->delete();
            } catch (Throwable $e) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ลบ all log ไม่สำเร็จ หลังจากฝากเงินเข้าเกมไม่สำเร็จ');
                report($e);
            }

            return false;
        }

        ActivityLoggerUser::activity(
            'Single ฝากเงินเข้าเกม ' . $game_name,
            $money_text . ' ระบบทำการฝากเงินเข้าเกมแล้ว ยอดก่อน ' . ($response['before'] ?? '-') . ' ยอดหลัง ' . ($response['after'] ?? '-')
        );

        try {
            DB::transaction(function () use (
                $ip,
                $paymentId,
                $datenow,
                $today,
                $config,
                $special,
                $bank_acc,
                $payment,
                $member,
                $game_code,
                $game_name,
                $user_name,
                $user_code,
                $bank_code,
                $amount,
                $bonus,
                $total,
                $pro_code,
                $pro_name,
                $status_pro,
                $turnpro,
                $withdraw_limit,
                $withdraw_limit_rate,
                $count_deposit,
                $response,
                $alllog,
                $empTopup,
                &$point,
                &$diamond
            ) {
                /** @var \Illuminate\Database\Eloquent\Model $lockedGameUser */
                $lockedGameUser = $this->gameUserRepository->find($user_code);
                if (! $lockedGameUser) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'ไม่พบ game_user ระหว่างทำ transaction (จะ rollback)');
                    throw new \RuntimeException('Missing game_user');
                }

                $pk = method_exists($lockedGameUser, 'getKeyName') ? $lockedGameUser->getKeyName() : 'code';
                $id = method_exists($lockedGameUser, 'getKey') ? $lockedGameUser->getKey() : $user_code;

                $lockedGameUser = $lockedGameUser->newQuery()
                    ->where($pk, $id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedGameUser) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'lockForUpdate ไม่เจอแถว game_user (จะ rollback)');
                    throw new \RuntimeException('Lock game_user failed');
                }

                $sum_amount_turn  = ((float) $lockedGameUser->balance + ($total * (float) $turnpro));
                $sum_amount_limit = ((float) $lockedGameUser->balance + (($amount + $bonus) * (float) $withdraw_limit_rate));

                ActivityLoggerUser::activity(
                    'Single Topup ID : ' . $paymentId,
                    'DEBUG(sum): locked_balance=' . (float) $lockedGameUser->balance
                    . ' total=' . $total
                    . ' turnpro=' . (float) $turnpro
                    . ' rate=' . (float) $withdraw_limit_rate
                    . ' => sum_turn=' . $sum_amount_turn
                    . ' sum_limit=' . $sum_amount_limit,
                    $member->code
                );

                $chknew = $this->memberCreditLogRepository->findOneWhere([
                    'member_code' => $member->code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'kind' => 'TOPUP',
                ]);

                if ($chknew) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'หยุดการทำงาน เนื่องจาก Log ซ้ำ (จะ rollback)');
                    throw new \RuntimeException('Duplicate member_credit_log');
                }

                $remark = $special
                    ? ('ช่วงเวลาสุดพิเศษ ' . $bank_acc->start_at . ' ถึง ' . $bank_acc->end_at . ' รับเพิ่ม ' . $bank_acc->bonus . '% อิงรายการฝาก ID ' . $paymentId)
                    : ('เติมเงินฝากอ้างอิงรายการฝาก ID : ' . $paymentId . ' RefID : ' . ($response['ref_id'] ?? ''));

                $bill = $this->memberCreditLogRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'amount' => $amount,
                    'bonus' => $bonus,
                    'total' => $total,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_total' => $total,
                    'credit_before' => $response['before'] ?? 0,
                    'credit_after' => $response['after'] ?? 0,
                    'member_code' => $member->code,
                    'user_name' => $member->user_name,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'bank_code' => $bank_code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'emp_code' => $empTopup,
                    'auto' => 'Y',
                    'remark' => $remark,
                    'kind' => 'TOPUP',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);

                if ($special) {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => $game_code,
                        'gameuser_code' => $user_code,
                        'amount' => 0,
                        'bonus' => $bonus,
                        'total' => 0,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'credit_bonus' => $bonus,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'bank_code' => $bank_code,
                        'refer_code' => $paymentId,
                        'refer_table' => 'bank_payment',
                        'emp_code' => $empTopup,
                        'auto' => 'Y',
                        'remark' => 'อ้างอิงรายการฝาก ID : ' . $paymentId . ' RefID : ' . ($response['ref_id'] ?? '') . ' ได้โบนัสจากช่องทางการฝาก เพิ่ม ' . $bank_acc->bonus . '%',
                        'kind' => 'G_BONUS',
                        'user_create' => 'System Auto',
                        'user_update' => 'System Auto',
                    ]);
                }

                $alllog->remark = 'Auto Topup and Refer Credit Log ID : ' . $bill->code;
                $alllog->user_update = 'System Auto';
                $alllog->save();

                // ====== point ======
                if ($config->point_open == 'Y') {
                    if ($config->point_per_bill == 'N') {
                        if ($amount >= $config->points && $config->points > 0) {
                            $point = (int) floor($amount / $config->points);

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน ' . $amount . ' บาท เติม ' . $config->points . ' ได้รับ 1 แต้ม สรุปได้รับ ' . $point,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->points_topup && $config->points_topup > 0 && $config->points_amount > 0) {
                            $point = (int) $config->points_amount;

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน ' . $amount . ' บาท (นับเป็นบิล) เติมยอด >= ' . $config->points_topup . ' ได้รับ ' . $point . ' แต้ม',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                // ====== diamond ======
                if ($config->diamond_open == 'Y') {
                    if ($config->diamond_per_bill == 'N') {
                        if ($amount >= $config->diamonds && $config->diamonds > 0) {
                            $diamond = (int) floor($amount / $config->diamonds);

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน ' . $amount . ' บาท เติม ' . $config->diamonds . ' ได้รับ 1 เม็ด สรุปได้รับ ' . $diamond,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->diamonds_topup && $config->diamonds_topup > 0 && $config->diamonds_amount > 0) {
                            $diamond = (int) $config->diamonds_amount;

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน ' . $amount . ' บาท (นับเป็นบิล) เติมยอด >= ' . $config->diamonds_topup . ' ได้รับ ' . $diamond . ' เม็ด',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                // ====== update payment ======
                $payment->user_id = $user_name;
                $payment->status = 1;
                $payment->before_credit = $response['before'] ?? 0;
                $payment->after_credit = $response['after'] ?? 0;
                $payment->pro_id = $pro_code;
                $payment->amount = $amount;
                $payment->pro_amount = $bonus;
                $payment->score = $total;
                $payment->date_topup = $datenow;
                $payment->date_approve = $datenow;
                $payment->autocheck = 'Y';
                $payment->remark_admin = trim((string) $payment->remark_admin) . ' (เติมแล้ว)';
                $payment->topup_by = 'System Auto';
                $payment->ip_topup = $ip;
                $payment->save();

                // ====== bill ======
                $bills = app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'ref_id' => $response['ref_id'] ?? '',
                    'credit_before' => $response['before'] ?? 0,
                    'credit_after' => $response['after'] ?? 0,
                    'member_code' => $member->code,
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'method' => 'TOPUP',
                    'transfer_type' => 1,
                    'amount' => $amount,
                    'balance_before' => $response['before'] ?? 0,
                    'balance_after' => $response['after'] ?? 0,
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_balance' => $total,
                    'amount_request' => $sum_amount_turn,
                    'amount_limit' => $sum_amount_limit,
                    'ip' => $ip,
                    'user_create' => $member->name,
                    'user_update' => $member->name,
                ]);

                // ====== promotion effect on game_user ======
                $billcode = 0;

                ActivityLoggerUser::activity(
                    'Single Topup ID : ' . $paymentId,
                    'DEBUG(beforeUpdate): amount_balance=' . (float) $lockedGameUser->amount_balance
                    . ' withdraw_limit_amount=' . (float) $lockedGameUser->withdraw_limit_amount,
                    $member->code
                );

                if ($pro_code > 0) {
                    $this->memberPromotionLogRepository->create([
                        'date_start' => now()->toDateString(),
                        'bill_code' => $bills->code,
                        'member_code' => $member->code,
                        'game_code' => $game_code,
                        'game_name' => $game_name,
                        'gameuser_code' => $user_code,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'turnpro' => $turnpro,
                        'balance' => (($response['before'] ?? 0) - $amount),
                        'amount' => $amount,
                        'bonus' => $bonus,
                        'amount_balance' => $sum_amount_turn,
                        'total_amount_balance' => $sum_amount_limit,
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_rate' => 0,
                        'complete' => 'N',
                        'enable' => 'Y',
                        'user_create' => $member->name,
                        'user_update' => $member->name,
                    ]);

                    $billcode = $bills->code;

                    $lockedGameUser->balance = $response['after'] ?? $lockedGameUser->balance;
                    $lockedGameUser->pro_code = $pro_code;
                    $lockedGameUser->bill_code = $billcode;
                    $lockedGameUser->turnpro = $turnpro;
                    $lockedGameUser->amount = $amount;
                    $lockedGameUser->bonus = $bonus;

                    $lockedGameUser->amount_balance = $sum_amount_turn;
                    $lockedGameUser->withdraw_limit = $withdraw_limit;
                    $lockedGameUser->withdraw_limit_rate = $withdraw_limit_rate;
                    $lockedGameUser->withdraw_limit_amount = $sum_amount_limit;

                    $lockedGameUser->save();
                } else {
                    if ($lockedGameUser->amount_balance > 0 || $lockedGameUser->pro_code > 0) {
                        if (($response['before'] ?? 0) > (float) $config->pro_reset) {
                            $lockedGameUser->amount_balance += ($total * (float) $lockedGameUser->turnpro);
                            $lockedGameUser->withdraw_limit_amount += ($total * (float) $lockedGameUser->withdraw_limit_rate);
                            $lockedGameUser->save();
                        } else {
                            $lockedGameUser->balance = $response['after'] ?? $lockedGameUser->balance;
                            $lockedGameUser->pro_code = 0;
                            $lockedGameUser->bill_code = 0;
                            $lockedGameUser->turnpro = 0;
                            $lockedGameUser->amount = 0;
                            $lockedGameUser->bonus = 0;
                            $lockedGameUser->amount_balance = 0;
                            $lockedGameUser->withdraw_limit = 0;
                            $lockedGameUser->withdraw_limit_rate = 0;
                            $lockedGameUser->withdraw_limit_amount = 0;
                            $lockedGameUser->save();
                        }
                    }
                }

                ActivityLoggerUser::activity(
                    'Single Topup ID : ' . $paymentId,
                    'DEBUG(afterUpdate): amount_balance=' . (float) $lockedGameUser->amount_balance
                    . ' withdraw_limit_amount=' . (float) $lockedGameUser->withdraw_limit_amount,
                    $member->code
                );

                $bill->amount_balance = $lockedGameUser->amount_balance;
                $bill->withdraw_limit = $lockedGameUser->withdraw_limit;
                $bill->withdraw_limit_amount = $lockedGameUser->withdraw_limit_amount;
                $bill->save();

                // ====== update member ======
                $member->credit += $amount;
                $member->sum_deposit += $amount;
                $member->status_pro = $status_pro;
                $member->point_deposit += $point;
                $member->diamond += $diamond;
                $member->balance = $response['after'] ?? $member->balance;
                $member->count_deposit += $count_deposit;
                $member->save();

                $this->updateMemberDepositStatsOnSuccess((int) $member->code, (float) $amount);
            });
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบปัญหาใน Transaction');
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'Rollback Transaction');

            try {
                $rollbackRes = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $total);
                if (($rollbackRes['success'] ?? false) === true) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'Rollback: ถอนเงินออกจากเกมแล้ว');
                } else {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'Rollback: ไม่สามารถถอนเงินออกจากเกมได้');
                }
            } catch (Throwable $ex) {
                report($ex);
            }

            try {
                $this->allLogRepository->where('bank_payment_id', $paymentId)->delete();
            } catch (Throwable $ex) {
                report($ex);
            }

            report($e);
            return false;
        }

        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เติมเงินสำเร็จให้กับ User : ' . $member->user_name);
        Notification::send($member, new RealTimeNotification(Lang::get('app.topup.complete') . $total));

        // ====== update sum_deposit account ======
        $account = $payment->bank_account;

        $sumToday = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->income()
            ->active()
            ->complete()
            ->where('account_code', $payment->account_code)
            ->whereDate('date_topup', $today)
            ->sum('value');

        $account->update(['sum_deposit' => $sumToday]);

        if ((float) $account->sum_limit > 0 && $sumToday >= (float) $account->sum_limit) {
            $account->update(['display_wallet' => 'N']);

            $alt = BankAccount::where('banks', $account->banks)
                ->where('bank_type', 1)
                ->where('display_wallet', 'N')
                ->where('status_auto', 'Y')
                ->where('enable', 'Y')
                ->where('sum_deposit', 0)
                ->orderBy('sort', 'asc')
                ->first();

            if ($alt) {
                $alt->update(['display_wallet' => 'Y']);
            }
        }

        return true;
    }

    public function refillPaymentSingle_real($data): bool
    {
        $ip = request()->ip();

        $now = now();
        $today = $now->toDateString();
        $datenow = $now->toDateTimeString();

        $config = $this->getCoreConfig();
        $special = false;

        $paymentId = Arr::get($data, 'code');
        $memberCode = Arr::get($data, 'member_topup');
        $accountCode = Arr::get($data, 'account_code');
        $amount = (float) Arr::get($data, 'value', 0);
        $empTopup = Arr::get($data, 'emp_topup');

        if (! $paymentId || ! $memberCode || ! $accountCode || $amount <= 0) {
            return false;
        }

        $payment = $this->find($paymentId);
        if (! $payment) {
            return false;
        }

        $member = $this->memberRepository->find($memberCode);
        if (! $member) {
            return false;
        }

        $bank_acc = $this->bankAccountRepository->find($accountCode);
        if (! $bank_acc) {
            return false;
        }

        $game = core()->getGame();
        if (! $game) {
            return false;
        }

        $game_user = $this->gameUserRepository->findOneWhere([
            'member_code' => $member->code,
            'game_code' => $game->code,
            'enable' => 'Y',
        ]);

        if (! $game_user) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่พบ game user ที่ enable=Y ของสมาชิก', $member->code);
            return false;
        }

        $game_code = $game->code;
        $game_name = $game->name;
        $game_balance = (float) $game_user->balance;

        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $member_code = $member->code;

        // ====== โปรโมชั่นที่เลือก ======
        $bonus = 0;
        $pro_code = 0;
        $pro_name = '';
        $total = $amount;
        $status_pro = $member->status_pro;
        $turnpro = 0;
        $withdraw_limit = 0;
        $withdraw_limit_rate = 0;

        $selectpro = $this->memberSelectProRepository->findOneWhere(['member_code' => $member_code]);

        if ($selectpro) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'มีการเลือกโปรโมชั่น โปรรหัส ' . $selectpro->pro_code, $member->code);

            if ($game_balance <= (float) $config->pro_reset) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ยอดเงินปัจจุบัน น้อยกว่าหรือเท่ากับโปรรีเซต ผ่านเงื่อนไข โปรรหัส ' . $selectpro->pro_code, $member->code);

                $promotion = $this->promotionRepository->checkSelectPro(
                    $selectpro->pro_code,
                    $member_code,
                    $amount,
                    $datenow
                );

                $bonus = (float) ($promotion['bonus'] ?? 0);
                $pro_code = (int) ($promotion['pro_code'] ?? 0);
                $pro_name = (string) ($promotion['pro_name'] ?? '');
                $total = (float) ($promotion['total'] ?? $amount);
                $status_pro = 1;
                $turnpro = (float) ($promotion['turnpro'] ?? 0);
                $withdraw_limit = (float) ($promotion['withdraw_limit'] ?? 0);
                $withdraw_limit_rate = (float) ($promotion['withdraw_limit_rate'] ?? 0);
            } else {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ยอดเงินปัจจุบัน มากกว่าโปรรีเซต ไม่ผ่านเงื่อนไข โปรรหัส ' . $selectpro->pro_code, $member->code);
            }

            $selectpro->delete();
        } else {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่ได้กดรับโปร', $member->code);
        }

        // ====== เงื่อนไขพิเศษตามบัญชีที่เติมเข้า ======
        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เช็คเงื่อนไขพิเศษของ บช ที่เติมเข้ามา ' . $bank_acc->acc_no, $member->code);

        if ((float) $bank_acc->bonus > 0) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบบัญชีมีโบนัสเพิ่ม ' . $bank_acc->bonus . '% ของ บช ' . $bank_acc->acc_no, $member->code);

            $isActive = $this->isBetweenDates($bank_acc->start_at, $bank_acc->end_at, $now);
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ตรวจสอบช่วงเวลาโบนัสของ บช ' . $bank_acc->acc_no, $member->code);

            if ($isActive) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'อยู่ในช่วงกิจกรรมโบนัส บช ' . $bank_acc->acc_no, $member->code);

                if ($pro_code === 0) {
                    $bonusSpecial = ($amount * (float) $bank_acc->bonus) / 100;

                    if ((float) $bank_acc->bonus_max > 0 && $bonusSpecial > (float) $bank_acc->bonus_max) {
                        $bonusSpecial = (float) $bank_acc->bonus_max;
                    }

                    ActivityLoggerUser::activity(
                        'Single Topup ID : ' . $paymentId,
                        'คำนวนโบนัสพิเศษจากยอดฝาก ' . $amount . ' ได้โบนัส ' . $bonusSpecial . ' (' . $bank_acc->bonus . '%) บช ' . $bank_acc->acc_no,
                        $member->code
                    );

                    $bonus = $bonusSpecial;
                    $pro_name = 'ช่วงเวลา พิเศษ รับยอดเพิ่มขึ้น ' . $bank_acc->bonus . '% จากยอดฝาก';
                    $total = $total + $bonus;
                    $special = true;
                }
            }
        } else {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'บัญชีนี้ไม่มีโบนัสเพิ่ม บช ' . $bank_acc->acc_no, $member->code);
        }

        $point = 0;
        $diamond = 0;
        $count_deposit = 1;

        $bank_code = optional($bank_acc->bank)->code ?? 0;

        $credit_before = $game_balance;
        $credit_after = $credit_before + $total;

        $chk = $this->allLogRepository->findOneByField('bank_payment_id', $paymentId);
        if ($chk) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบรายการเติมเงินนี้ในระบบแล้ว', $member->code);
            return false;
        }

        try {
            $alllog = $this->allLogRepository->create([
                'before_credit' => $credit_before,
                'after_credit' => $credit_after,
                'status_log' => 0,
                'pro_id' => $pro_code,
                'pro_amount' => $bonus,
                'bonus' => $bonus,
                'game_code' => $game_code,
                'type_record' => 0,
                'gamebalance' => $game_balance,
                'member_code' => $member_code,
                'member_user' => $member->user_name,
                'amount' => $amount,
                'bank_payment_id' => $paymentId,
                'ip' => $ip,
                'username' => $user_name,
                'remark' => '',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
            ]);
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่สามารถเพิ่มรายการ all log ได้');
            report($e);
            return false;
        }

        $money_text = 'User ' . $member->user_name . ' Game ID : ' . $user_name . ' จำนวนเงิน ' . $amount . ' โบนัส ' . $bonus . ' จากโปร ' . $pro_name . ' รวมเป็น ' . $total;

        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เริ่มรายการเติมเงินให้กับ User : ' . $member->user_name . ' Game ID : ' . $user_name);
        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, $money_text);

        // =========================================================
        // ✅ สำคัญ: ครอบ UserDeposit กัน exception หลุด (กัน alllog ค้าง)
        // =========================================================
        try {
            $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $total, false);
        } catch (Throwable $e) {
            ActivityLoggerUser::activity(
                'Single ฝากเงินเข้าเกม ' . $game_name,
                $money_text . ' เกิด exception ตอนฝากเงินเข้าเกม ระบบจะลบรายการ all log ที่สร้างไว้'
            );

            try {
                $alllog->delete();
            } catch (Throwable $ex) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ลบ all log ไม่สำเร็จ หลังจากเกิด exception ตอนฝากเงินเข้าเกม');
                report($ex);
            }

            report($e);
            return false;
        }

        // กันเคส provider คืนค่าไม่ใช่ array
        if (! is_array($response)) {
            $response = [];
        }

        if (! ($response['success'] ?? false)) {
            ActivityLoggerUser::activity(
                'Single ฝากเงินเข้าเกม ' . $game_name,
                $money_text . ' ไม่สามารถฝากเงินเข้าเกมได้ ระบบจะลบรายการ all log ที่สร้างไว้'
            );

            try {
                $alllog->delete();
            } catch (Throwable $e) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ลบ all log ไม่สำเร็จ หลังจากฝากเงินเข้าเกมไม่สำเร็จ');
                report($e);
            }

            return false;
        }

        ActivityLoggerUser::activity(
            'Single ฝากเงินเข้าเกม ' . $game_name,
            $money_text . ' ระบบทำการฝากเงินเข้าเกมแล้ว ยอดก่อน ' . $response['before'] . ' ยอดหลัง ' . $response['after']
        );

        try {
            DB::transaction(function () use (
                $ip,
                $paymentId,
                $datenow,
                $today,
                $config,
                $special,
                $bank_acc,
                $payment,
                $member,
                $game_code,
                $game_name,
                $user_name,
                $user_code,
                $bank_code,
                $amount,
                $bonus,
                $total,
                $pro_code,
                $pro_name,
                $status_pro,
                $turnpro,
                $withdraw_limit,
                $withdraw_limit_rate,
                $count_deposit,
                $response,
                $alllog,
                $empTopup,
                &$point,
                &$diamond
            ) {
                // =========================================================
                // ✅ สำคัญ: โหลด game_user ใหม่ใน transaction + lockForUpdate
                // =========================================================
                /** @var \Illuminate\Database\Eloquent\Model $lockedGameUser */
                $lockedGameUser = $this->gameUserRepository->find($user_code);
                if (! $lockedGameUser) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'ไม่พบ game_user ระหว่างทำ transaction (จะ rollback)');
                    throw new \RuntimeException('Missing game_user');
                }

                $pk = method_exists($lockedGameUser, 'getKeyName') ? $lockedGameUser->getKeyName() : 'code';
                $id = method_exists($lockedGameUser, 'getKey') ? $lockedGameUser->getKey() : $user_code;

                $lockedGameUser = $lockedGameUser->newQuery()
                    ->where($pk, $id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedGameUser) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'lockForUpdate ไม่เจอแถว game_user (จะ rollback)');
                    throw new \RuntimeException('Lock game_user failed');
                }

                $sum_amount_turn  = ((float) $lockedGameUser->balance + ($total * (float) $turnpro));
                $sum_amount_limit = ((float) $lockedGameUser->balance + (($amount + $bonus) * (float) $withdraw_limit_rate));

                ActivityLoggerUser::activity(
                    'Single Topup ID : ' . $paymentId,
                    'DEBUG(sum): locked_balance=' . (float) $lockedGameUser->balance
                    . ' total=' . $total
                    . ' turnpro=' . (float) $turnpro
                    . ' rate=' . (float) $withdraw_limit_rate
                    . ' => sum_turn=' . $sum_amount_turn
                    . ' sum_limit=' . $sum_amount_limit,
                    $member->code
                );

                $chknew = $this->memberCreditLogRepository->findOneWhere([
                    'member_code' => $member->code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'kind' => 'TOPUP',
                ]);

                if ($chknew) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'หยุดการทำงาน เนื่องจาก Log ซ้ำ (จะ rollback)');
                    throw new \RuntimeException('Duplicate member_credit_log');
                }

                $remark = $special
                    ? ('ช่วงเวลาสุดพิเศษ ' . $bank_acc->start_at . ' ถึง ' . $bank_acc->end_at . ' รับเพิ่ม ' . $bank_acc->bonus . '% อิงรายการฝาก ID ' . $paymentId)
                    : ('เติมเงินฝากอ้างอิงรายการฝาก ID : ' . $paymentId . ' RefID : ' . $response['ref_id']);

                $bill = $this->memberCreditLogRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'amount' => $amount,
                    'bonus' => $bonus,
                    'total' => $total,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_total' => $total,
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member->code,
                    'user_name' => $member->user_name,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'bank_code' => $bank_code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'emp_code' => $empTopup,
                    'auto' => 'Y',
                    'remark' => $remark,
                    'kind' => 'TOPUP',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);

                if ($special) {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => $game_code,
                        'gameuser_code' => $user_code,
                        'amount' => 0,
                        'bonus' => $bonus,
                        'total' => 0,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'credit_bonus' => $bonus,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'bank_code' => $bank_code,
                        'refer_code' => $paymentId,
                        'refer_table' => 'bank_payment',
                        'emp_code' => $empTopup,
                        'auto' => 'Y',
                        'remark' => 'อ้างอิงรายการฝาก ID : ' . $paymentId . ' RefID : ' . $response['ref_id'] . ' ได้โบนัสจากช่องทางการฝาก เพิ่ม ' . $bank_acc->bonus . '%',
                        'kind' => 'G_BONUS',
                        'user_create' => 'System Auto',
                        'user_update' => 'System Auto',
                    ]);
                }

                $alllog->remark = 'Auto Topup and Refer Credit Log ID : ' . $bill->code;
                $alllog->user_update = 'System Auto';
                $alllog->save();

                // ====== point ======
                if ($config->point_open == 'Y') {
                    if ($config->point_per_bill == 'N') {
                        if ($amount >= $config->points && $config->points > 0) {
                            $point = (int) floor($amount / $config->points);

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน ' . $amount . ' บาท เติม ' . $config->points . ' ได้รับ 1 แต้ม สรุปได้รับ ' . $point,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->points_topup && $config->points_topup > 0 && $config->points_amount > 0) {
                            $point = (int) $config->points_amount;

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน ' . $amount . ' บาท (นับเป็นบิล) เติมยอด >= ' . $config->points_topup . ' ได้รับ ' . $point . ' แต้ม',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                // ====== diamond ======
                if ($config->diamond_open == 'Y') {
                    if ($config->diamond_per_bill == 'N') {
                        if ($amount >= $config->diamonds && $config->diamonds > 0) {
                            $diamond = (int) floor($amount / $config->diamonds);

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน ' . $amount . ' บาท เติม ' . $config->diamonds . ' ได้รับ 1 เม็ด สรุปได้รับ ' . $diamond,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->diamonds_topup && $config->diamonds_topup > 0 && $config->diamonds_amount > 0) {
                            $diamond = (int) $config->diamonds_amount;

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน ' . $amount . ' บาท (นับเป็นบิล) เติมยอด >= ' . $config->diamonds_topup . ' ได้รับ ' . $diamond . ' เม็ด',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                // ====== update payment ======
                $payment->user_id = $user_name;
                $payment->status = 1;
                $payment->before_credit = $response['before'];
                $payment->after_credit = $response['after'];
                $payment->pro_id = $pro_code;
                $payment->amount = $amount;
                $payment->pro_amount = $bonus;
                $payment->score = $total;
                $payment->date_topup = $datenow;
                $payment->date_approve = $datenow;
                $payment->autocheck = 'Y';
                $payment->remark_admin = trim((string) $payment->remark_admin) . ' (เติมแล้ว)';
                $payment->topup_by = 'System Auto';
                $payment->ip_topup = $ip;
                $payment->save();

                // ====== bill ======
                $bills = app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'ref_id' => $response['ref_id'],
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member->code,
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'method' => 'TOPUP',
                    'transfer_type' => 1,
                    'amount' => $amount,
                    'balance_before' => $response['before'],
                    'balance_after' => $response['after'],
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_balance' => $total,
                    'amount_request' => $sum_amount_turn,
                    'amount_limit' => $sum_amount_limit,
                    'ip' => $ip,
                    'user_create' => $member->name,
                    'user_update' => $member->name,
                ]);

                // ====== promotion effect on game_user ======
                $billcode = 0;

                ActivityLoggerUser::activity(
                    'Single Topup ID : ' . $paymentId,
                    'DEBUG(beforeUpdate): amount_balance=' . (float) $lockedGameUser->amount_balance
                    . ' withdraw_limit_amount=' . (float) $lockedGameUser->withdraw_limit_amount,
                    $member->code
                );

                if ($pro_code > 0) {
                    $this->memberPromotionLogRepository->create([
                        'date_start' => now()->toDateString(),
                        'bill_code' => $bills->code,
                        'member_code' => $member->code,
                        'game_code' => $game_code,
                        'game_name' => $game_name,
                        'gameuser_code' => $user_code,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'turnpro' => $turnpro,
                        'balance' => ($response['before'] - $amount),
                        'amount' => $amount,
                        'bonus' => $bonus,
                        'amount_balance' => $sum_amount_turn,
                        'total_amount_balance' => $sum_amount_limit,
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_rate' => 0,
                        'complete' => 'N',
                        'enable' => 'Y',
                        'user_create' => $member->name,
                        'user_update' => $member->name,
                    ]);

                    $billcode = $bills->code;

                    $lockedGameUser->balance = $response['after'];
                    $lockedGameUser->pro_code = $pro_code;
                    $lockedGameUser->bill_code = $billcode;
                    $lockedGameUser->turnpro = $turnpro;
                    $lockedGameUser->amount = $amount;
                    $lockedGameUser->bonus = $bonus;

                    $lockedGameUser->amount_balance = $sum_amount_turn;
                    $lockedGameUser->withdraw_limit = $withdraw_limit;
                    $lockedGameUser->withdraw_limit_rate = $withdraw_limit_rate;
                    $lockedGameUser->withdraw_limit_amount = $sum_amount_limit;

                    $lockedGameUser->save();
                } else {
                    if ($lockedGameUser->amount_balance > 0 || $lockedGameUser->pro_code > 0) {
                        if ($response['before'] > (float) $config->pro_reset) {
                            $lockedGameUser->amount_balance += ($total * (float) $lockedGameUser->turnpro);
                            $lockedGameUser->withdraw_limit_amount += ($total * (float) $lockedGameUser->withdraw_limit_rate);
                            $lockedGameUser->save();
                        } else {
                            $lockedGameUser->balance = $response['after'];
                            $lockedGameUser->pro_code = 0;
                            $lockedGameUser->bill_code = 0;
                            $lockedGameUser->turnpro = 0;
                            $lockedGameUser->amount = 0;
                            $lockedGameUser->bonus = 0;
                            $lockedGameUser->amount_balance = 0;
                            $lockedGameUser->withdraw_limit = 0;
                            $lockedGameUser->withdraw_limit_rate = 0;
                            $lockedGameUser->withdraw_limit_amount = 0;
                            $lockedGameUser->save();
                        }
                    }
                }

                ActivityLoggerUser::activity(
                    'Single Topup ID : ' . $paymentId,
                    'DEBUG(afterUpdate): amount_balance=' . (float) $lockedGameUser->amount_balance
                    . ' withdraw_limit_amount=' . (float) $lockedGameUser->withdraw_limit_amount,
                    $member->code
                );

                $bill->amount_balance = $lockedGameUser->amount_balance;
                $bill->withdraw_limit = $lockedGameUser->withdraw_limit;
                $bill->withdraw_limit_amount = $lockedGameUser->withdraw_limit_amount;
                $bill->save();

                // ====== update member ======
                $member->credit += $amount;
                $member->sum_deposit += $amount;
                $member->status_pro = $status_pro;
                $member->point_deposit += $point;
                $member->diamond += $diamond;
                $member->balance = $response['after'];
                $member->count_deposit += $count_deposit;
                $member->save();

                $this->updateMemberDepositStatsOnSuccess((int) $member->code, (float) $amount);
            });
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบปัญหาใน Transaction');
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'Rollback Transaction');

            try {
                $rollbackRes = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $total);
                if (($rollbackRes['success'] ?? false) === true) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'Rollback: ถอนเงินออกจากเกมแล้ว');
                } else {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'Rollback: ไม่สามารถถอนเงินออกจากเกมได้');
                }
            } catch (Throwable $ex) {
                report($ex);
            }

            try {
                $this->allLogRepository->where('bank_payment_id', $paymentId)->delete();
            } catch (Throwable $ex) {
                report($ex);
            }

            report($e);
            return false;
        }

        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เติมเงินสำเร็จให้กับ User : ' . $member->user_name);
        Notification::send($member, new RealTimeNotification(Lang::get('app.topup.complete') . $total));

        // ====== update sum_deposit account ======
        $account = $payment->bank_account;

        $sumToday = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->income()
            ->active()
            ->complete()
            ->where('account_code', $payment->account_code)
            ->whereDate('date_topup', $today)
            ->sum('value');

        $account->update(['sum_deposit' => $sumToday]);

        if ((float) $account->sum_limit > 0 && $sumToday >= (float) $account->sum_limit) {
            $account->update(['display_wallet' => 'N']);

            $alt = BankAccount::where('banks', $account->banks)
                ->where('bank_type', 1)
                ->where('display_wallet', 'N')
                ->where('status_auto', 'Y')
                ->where('enable', 'Y')
                ->where('sum_deposit', 0)
                ->orderBy('sort', 'asc')
                ->first();

            if ($alt) {
                $alt->update(['display_wallet' => 'Y']);
            }
        }

        return true;
    }



    public function refillPaymentSingle_last($data): bool
    {
        $ip = request()->ip();

        $now = now();
        $today = $now->toDateString();
        $datenow = $now->toDateTimeString();

        $config = $this->getCoreConfig();
        $special = false;

        $paymentId = Arr::get($data, 'code');
        $memberCode = Arr::get($data, 'member_topup');
        $accountCode = Arr::get($data, 'account_code');
        $amount = (float) Arr::get($data, 'value', 0);
        $empTopup = Arr::get($data, 'emp_topup');

        if (! $paymentId || ! $memberCode || ! $accountCode || $amount <= 0) {
            return false;
        }

        $payment = $this->find($paymentId);
        if (! $payment) {
            return false;
        }

        $member = $this->memberRepository->find($memberCode);
        if (! $member) {
            return false;
        }

        $bank_acc = $this->bankAccountRepository->find($accountCode);
        if (! $bank_acc) {
            return false;
        }

        $game = core()->getGame();
        if (! $game) {
            return false;
        }

        $game_user = $this->gameUserRepository->findOneWhere([
            'member_code' => $member->code,
            'game_code' => $game->code,
            'enable' => 'Y',
        ]);

        if (! $game_user) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่พบ game user ที่ enable=Y ของสมาชิก', $member->code);
            return false;
        }

        $game_code = $game->code;
        $game_name = $game->name;
        $game_balance = (float) $game_user->balance;

        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $member_code = $member->code;

        // ====== โปรโมชั่นที่เลือก ======
        $bonus = 0;
        $pro_code = 0;
        $pro_name = '';
        $total = $amount;
        $status_pro = $member->status_pro;
        $turnpro = 0;
        $withdraw_limit = 0;
        $withdraw_limit_rate = 0;

        $selectpro = $this->memberSelectProRepository->findOneWhere(['member_code' => $member_code]);

        if ($selectpro) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'มีการเลือกโปรโมชั่น โปรรหัส ' . $selectpro->pro_code, $member->code);

            if ($game_balance <= (float) $config->pro_reset) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ยอดเงินปัจจุบัน น้อยกว่าหรือเท่ากับโปรรีเซต ผ่านเงื่อนไข โปรรหัส ' . $selectpro->pro_code, $member->code);

                $promotion = $this->promotionRepository->checkSelectPro(
                    $selectpro->pro_code,
                    $member_code,
                    $amount,
                    $datenow
                );

                $bonus = (float) ($promotion['bonus'] ?? 0);
                $pro_code = (int) ($promotion['pro_code'] ?? 0);
                $pro_name = (string) ($promotion['pro_name'] ?? '');
                $total = (float) ($promotion['total'] ?? $amount);
                $status_pro = 1;
                $turnpro = (float) ($promotion['turnpro'] ?? 0);
                $withdraw_limit = (float) ($promotion['withdraw_limit'] ?? 0);
                $withdraw_limit_rate = (float) ($promotion['withdraw_limit_rate'] ?? 0);
            } else {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ยอดเงินปัจจุบัน มากกว่าโปรรีเซต ไม่ผ่านเงื่อนไข โปรรหัส ' . $selectpro->pro_code, $member->code);
            }

            $selectpro->delete();
        } else {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่ได้กดรับโปร', $member->code);
        }

        // ====== เงื่อนไขพิเศษตามบัญชีที่เติมเข้า ======
        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เช็คเงื่อนไขพิเศษของ บช ที่เติมเข้ามา ' . $bank_acc->acc_no, $member->code);

        if ((float) $bank_acc->bonus > 0) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบบัญชีมีโบนัสเพิ่ม ' . $bank_acc->bonus . '% ของ บช ' . $bank_acc->acc_no, $member->code);

            $isActive = $this->isBetweenDates($bank_acc->start_at, $bank_acc->end_at, $now);
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ตรวจสอบช่วงเวลาโบนัสของ บช ' . $bank_acc->acc_no, $member->code);

            if ($isActive) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'อยู่ในช่วงกิจกรรมโบนัส บช ' . $bank_acc->acc_no, $member->code);

                if ($pro_code === 0) {
                    $bonusSpecial = ($amount * (float) $bank_acc->bonus) / 100;

                    if ((float) $bank_acc->bonus_max > 0 && $bonusSpecial > (float) $bank_acc->bonus_max) {
                        $bonusSpecial = (float) $bank_acc->bonus_max;
                    }

                    ActivityLoggerUser::activity(
                        'Single Topup ID : ' . $paymentId,
                        'คำนวนโบนัสพิเศษจากยอดฝาก ' . $amount . ' ได้โบนัส ' . $bonusSpecial . ' (' . $bank_acc->bonus . '%) บช ' . $bank_acc->acc_no,
                        $member->code
                    );

                    $bonus = $bonusSpecial;
                    $pro_name = 'ช่วงเวลา พิเศษ รับยอดเพิ่มขึ้น ' . $bank_acc->bonus . '% จากยอดฝาก';
                    $total = $total + $bonus;
                    $special = true;
                }
            }
        } else {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'บัญชีนี้ไม่มีโบนัสเพิ่ม บช ' . $bank_acc->acc_no, $member->code);
        }

        $point = 0;
        $diamond = 0;
        $count_deposit = 1;

        $bank_code = optional($bank_acc->bank)->code ?? 0;

        $credit_before = $game_balance;
        $credit_after = $credit_before + $total;

        $chk = $this->allLogRepository->findOneByField('bank_payment_id', $paymentId);
        if ($chk) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบรายการเติมเงินนี้ในระบบแล้ว', $member->code);
            return false;
        }

        try {
            $alllog = $this->allLogRepository->create([
                'before_credit' => $credit_before,
                'after_credit' => $credit_after,
                'status_log' => 0,
                'pro_id' => $pro_code,
                'pro_amount' => $bonus,
                'bonus' => $bonus,
                'game_code' => $game_code,
                'type_record' => 0,
                'gamebalance' => $game_balance,
                'member_code' => $member_code,
                'member_user' => $member->user_name,
                'amount' => $amount,
                'bank_payment_id' => $paymentId,
                'ip' => $ip,
                'username' => $user_name,
                'remark' => '',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
            ]);
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ไม่สามารถเพิ่มรายการ all log ได้');
            report($e);
            return false;
        }

        $money_text = 'User ' . $member->user_name . ' Game ID : ' . $user_name . ' จำนวนเงิน ' . $amount . ' โบนัส ' . $bonus . ' จากโปร ' . $pro_name . ' รวมเป็น ' . $total;

        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เริ่มรายการเติมเงินให้กับ User : ' . $member->user_name . ' Game ID : ' . $user_name);
        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, $money_text);

        $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $total, false);

        if (! ($response['success'] ?? false)) {
            ActivityLoggerUser::activity(
                'Single ฝากเงินเข้าเกม ' . $game_name,
                $money_text . ' ไม่สามารถฝากเงินเข้าเกมได้ ระบบจะลบรายการ all log ที่สร้างไว้'
            );

            try {
                $alllog->delete();
            } catch (Throwable $e) {
                ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'ลบ all log ไม่สำเร็จ หลังจากฝากเงินเข้าเกมไม่สำเร็จ');
                report($e);
            }

            return false;
        }

        ActivityLoggerUser::activity(
            'Single ฝากเงินเข้าเกม ' . $game_name,
            $money_text . ' ระบบทำการฝากเงินเข้าเกมแล้ว ยอดก่อน ' . $response['before'] . ' ยอดหลัง ' . $response['after']
        );

        try {
            DB::transaction(function () use (
                $ip,
                $paymentId,
                $datenow,
                $today,
                $config,
                $special,
                $bank_acc,
                $payment,
                $member,
                $game_user,
                $game_code,
                $game_name,
                $user_name,
                $user_code,
                $bank_code,
                $amount,
                $bonus,
                $total,
                $pro_code,
                $pro_name,
                $status_pro,
                $turnpro,
                $withdraw_limit,
                $withdraw_limit_rate,
                $count_deposit,
                $response,
                $alllog,
                $empTopup,
                &$point,
                &$diamond
            ) {
                $chknew = $this->memberCreditLogRepository->findOneWhere([
                    'member_code' => $member->code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'kind' => 'TOPUP',
                ]);

                if ($chknew) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'หยุดการทำงาน เนื่องจาก Log ซ้ำ (จะ rollback)');
                    throw new \RuntimeException('Duplicate member_credit_log');
                }

                $remark = $special
                    ? ('ช่วงเวลาสุดพิเศษ ' . $bank_acc->start_at . ' ถึง ' . $bank_acc->end_at . ' รับเพิ่ม ' . $bank_acc->bonus . '% อิงรายการฝาก ID ' . $paymentId)
                    : ('เติมเงินฝากอ้างอิงรายการฝาก ID : ' . $paymentId . ' RefID : ' . $response['ref_id']);

                $bill = $this->memberCreditLogRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'amount' => $amount,
                    'bonus' => $bonus,
                    'total' => $total,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_total' => $total,
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member->code,
                    'user_name' => $member->user_name,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'bank_code' => $bank_code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'emp_code' => $empTopup,
                    'auto' => 'Y',
                    'remark' => $remark,
                    'kind' => 'TOPUP',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);

                if ($special) {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => $game_code,
                        'gameuser_code' => $user_code,
                        'amount' => 0,
                        'bonus' => $bonus,
                        'total' => 0,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'credit_bonus' => $bonus,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'bank_code' => $bank_code,
                        'refer_code' => $paymentId,
                        'refer_table' => 'bank_payment',
                        'emp_code' => $empTopup,
                        'auto' => 'Y',
                        'remark' => 'อ้างอิงรายการฝาก ID : ' . $paymentId . ' RefID : ' . $response['ref_id'] . ' ได้โบนัสจากช่องทางการฝาก เพิ่ม ' . $bank_acc->bonus . '%',
                        'kind' => 'G_BONUS',
                        'user_create' => 'System Auto',
                        'user_update' => 'System Auto',
                    ]);
                }

                $alllog->remark = 'Auto Topup and Refer Credit Log ID : ' . $bill->code;
                $alllog->user_update = 'System Auto';
                $alllog->save();

                // ====== point ======
                if ($config->point_open == 'Y') {
                    if ($config->point_per_bill == 'N') {
                        if ($amount >= $config->points && $config->points > 0) {
                            $point = (int) floor($amount / $config->points);

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน ' . $amount . ' บาท เติม ' . $config->points . ' ได้รับ 1 แต้ม สรุปได้รับ ' . $point,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->points_topup && $config->points_topup > 0 && $config->points_amount > 0) {
                            $point = (int) $config->points_amount;

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน ' . $amount . ' บาท (นับเป็นบิล) เติมยอด >= ' . $config->points_topup . ' ได้รับ ' . $point . ' แต้ม',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                // ====== diamond ======
                if ($config->diamond_open == 'Y') {
                    if ($config->diamond_per_bill == 'N') {
                        if ($amount >= $config->diamonds && $config->diamonds > 0) {
                            $diamond = (int) floor($amount / $config->diamonds);

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน ' . $amount . ' บาท เติม ' . $config->diamonds . ' ได้รับ 1 เม็ด สรุปได้รับ ' . $diamond,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->diamonds_topup && $config->diamonds_topup > 0 && $config->diamonds_amount > 0) {
                            $diamond = (int) $config->diamonds_amount;

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน ' . $amount . ' บาท (นับเป็นบิล) เติมยอด >= ' . $config->diamonds_topup . ' ได้รับ ' . $diamond . ' เม็ด',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                // ====== update payment ======
                $payment->user_id = $user_name;
                $payment->status = 1;
                $payment->before_credit = $response['before'];
                $payment->after_credit = $response['after'];
                $payment->pro_id = $pro_code;
                $payment->amount = $amount;
                $payment->pro_amount = $bonus;
                $payment->score = $total;
                $payment->date_topup = $datenow;
                $payment->date_approve = $datenow;
                $payment->autocheck = 'Y';
                $payment->remark_admin = trim((string) $payment->remark_admin) . ' (เติมแล้ว)';
                $payment->topup_by = 'System Auto';
                $payment->ip_topup = $ip;
                $payment->save();


                $sum_amount_turn = ($game_user->balance + ($total * $turnpro));
                $sum_amount_limit = ($game_user->balance + (($amount + $bonus) * $withdraw_limit_rate));

                // ====== bill ======
                $bills = app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'ref_id' => $response['ref_id'],
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member->code,
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'method' => 'TOPUP',
                    'transfer_type' => 1,
                    'amount' => $amount,
                    'balance_before' => $response['before'],
                    'balance_after' => $response['after'],
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_balance' => $total,
                    'amount_request' => $sum_amount_turn,
                    'amount_limit' => $sum_amount_limit,
                    'ip' => $ip,
                    'user_create' => $member->name,
                    'user_update' => $member->name,
                ]);

                // ====== promotion effect on game_user ======
                $billcode = 0;

                if ($pro_code > 0) {
                    $this->memberPromotionLogRepository->create([
                        'date_start' => now()->toDateString(),
                        'bill_code' => $bills->code,
                        'member_code' => $member->code,
                        'game_code' => $game_code,
                        'game_name' => $game_name,
                        'gameuser_code' => $user_code,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'turnpro' => $turnpro,
                        'balance' => ($response['before'] - $amount),
                        'amount' => $amount,
                        'bonus' => $bonus,
                        'amount_balance' => $sum_amount_turn,
                        'total_amount_balance' => $sum_amount_limit,
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_rate' => 0,
                        'complete' => 'N',
                        'enable' => 'Y',
                        'user_create' => $member->name,
                        'user_update' => $member->name,
                    ]);

                    $billcode = $bills->code;

                    $game_user->balance = $response['after'];
                    $game_user->pro_code = $pro_code;
                    $game_user->bill_code = $billcode;
                    $game_user->turnpro = $turnpro;
                    $game_user->amount = $amount;
                    $game_user->bonus = $bonus;
                    $game_user->amount_balance += $sum_amount_turn;
                    $game_user->withdraw_limit = $withdraw_limit;
                    $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                    $game_user->withdraw_limit_amount += $sum_amount_limit;
                    $game_user->save();
                } else {
                    if ($game_user->amount_balance > 0 || $game_user->pro_code > 0) {
                        if ($response['before'] > (float) $config->pro_reset) {
                            $game_user->amount_balance += ($total * (float) $game_user->turnpro);
                            $game_user->withdraw_limit_amount += ($total * (float) $game_user->withdraw_limit_rate);
                            $game_user->save();
                        } else {
                            $game_user->balance = $response['after'];
                            $game_user->pro_code = 0;
                            $game_user->bill_code = 0;
                            $game_user->turnpro = 0;
                            $game_user->amount = 0;
                            $game_user->bonus = 0;
                            $game_user->amount_balance = 0;
                            $game_user->withdraw_limit = 0;
                            $game_user->withdraw_limit_rate = 0;
                            $game_user->withdraw_limit_amount = 0;
                            $game_user->save();
                        }
                    }
                }

                $bill->amount_balance = $game_user->amount_balance;
                $bill->withdraw_limit = $game_user->withdraw_limit;
                $bill->withdraw_limit_amount = $game_user->withdraw_limit_amount;
                $bill->save();

                // ====== update member ======
                $member->credit += $amount;
                $member->sum_deposit += $amount;
                $member->status_pro = $status_pro;
                $member->point_deposit += $point;
                $member->diamond += $diamond;
                $member->balance = $response['after'];
                $member->count_deposit += $count_deposit;
                $member->save();

                // ✅ อัปเดตสรุปสถิติ “ใน transaction เดียวกัน”
                $this->updateMemberDepositStatsOnSuccess((int) $member->code, (float) $amount);
            });
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'พบปัญหาใน Transaction');
            ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'Rollback Transaction');

            try {
                $rollbackRes = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $total);
                if (($rollbackRes['success'] ?? false) === true) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'Rollback: ถอนเงินออกจากเกมแล้ว');
                } else {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม ' . $game_name, 'Rollback: ไม่สามารถถอนเงินออกจากเกมได้');
                }
            } catch (Throwable $ex) {
                report($ex);
            }

            try {
                $this->allLogRepository->where('bank_payment_id', $paymentId)->delete();
            } catch (Throwable $ex) {
                report($ex);
            }

            report($e);
            return false;
        }

        ActivityLoggerUser::activity('Single Topup ID : ' . $paymentId, 'เติมเงินสำเร็จให้กับ User : ' . $member->user_name);
        Notification::send($member, new RealTimeNotification(Lang::get('app.topup.complete') . $total));

        // ====== update sum_deposit account ======
        $account = $payment->bank_account;

        $sumToday = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->income()
            ->active()
            ->complete()
            ->where('account_code', $payment->account_code)
            ->whereDate('date_topup', $today)
            ->sum('value');

        $account->update(['sum_deposit' => $sumToday]);

        if ((float) $account->sum_limit > 0 && $sumToday >= (float) $account->sum_limit) {
            $account->update(['display_wallet' => 'N']);

            $alt = BankAccount::where('banks', $account->banks)
                ->where('bank_type', 1)
                ->where('display_wallet', 'N')
                ->where('status_auto', 'Y')
                ->where('enable', 'Y')
                ->where('sum_deposit', 0)
                ->orderBy('sort', 'asc')
                ->first();

            if ($alt) {
                $alt->update(['display_wallet' => 'Y']);
            }
        }

        return true;
    }

    public function refillPaymentSingle_($data): bool
    {
        $ip = request()->ip();

        $now = now();
        $today = $now->toDateString();
        $datenow = $now->toDateTimeString();

        $config = $this->getCoreConfig();
        $special = false;

        $paymentId = Arr::get($data, 'code');
        $memberCode = Arr::get($data, 'member_topup');
        $accountCode = Arr::get($data, 'account_code');
        $amount = (float) Arr::get($data, 'value', 0);
        $empTopup = Arr::get($data, 'emp_topup');

        if (! $paymentId || ! $memberCode || ! $accountCode || $amount <= 0) {
            return false;
        }

        $payment = $this->find($paymentId);
        if (! $payment) {
            return false;
        }

        $member = $this->memberRepository->find($memberCode);
        if (! $member) {
            return false;
        }

        $bank_acc = $this->bankAccountRepository->find($accountCode);
        if (! $bank_acc) {
            return false;
        }

        $game = core()->getGame();
        if (! $game) {
            return false;
        }

        $game_user = $this->gameUserRepository->findOneWhere([
            'member_code' => $member->code,
            'game_code' => $game->code,
            'enable' => 'Y',
        ]);

        // กันพัง: ถ้าไม่เจอ game_user เดิมจะ fatal
        if (! $game_user) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ไม่พบ game user ที่ enable=Y ของสมาชิก', $member->code);

            return false;
        }

        $game_code = $game->code;
        $game_name = $game->name;
        $game_balance = (float) $game_user->balance;

        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $member_code = $member->code;

        // ====== โปรโมชั่นที่เลือก ======
        $bonus = 0;
        $pro_code = 0;
        $pro_name = '';
        $total = $amount;
        $status_pro = $member->status_pro;
        $turnpro = 0;
        $withdraw_limit = 0;
        $withdraw_limit_rate = 0;

        $selectpro = $this->memberSelectProRepository->findOneWhere(['member_code' => $member_code]);

        if ($selectpro) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'มีการเลือกโปรโมชั่น โปรรหัส '.$selectpro->pro_code, $member->code);

            if ($game_balance <= (float) $config->pro_reset) {
                ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ยอดเงินปัจจุบัน น้อยกว่าหรือเท่ากับโปรรีเซต ผ่านเงื่อนไข โปรรหัส '.$selectpro->pro_code, $member->code);

                $promotion = $this->promotionRepository->checkSelectPro(
                    $selectpro->pro_code,
                    $member_code,
                    $amount,
                    $datenow
                );

                $bonus = (float) ($promotion['bonus'] ?? 0);
                $pro_code = (int) ($promotion['pro_code'] ?? 0);
                $pro_name = (string) ($promotion['pro_name'] ?? '');
                $total = (float) ($promotion['total'] ?? $amount);
                $status_pro = 1;
                $turnpro = (float) ($promotion['turnpro'] ?? 0);
                $withdraw_limit = (float) ($promotion['withdraw_limit'] ?? 0);
                $withdraw_limit_rate = (float) ($promotion['withdraw_limit_rate'] ?? 0);
            } else {
                ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ยอดเงินปัจจุบัน มากกว่าโปรรีเซต ไม่ผ่านเงื่อนไข โปรรหัส '.$selectpro->pro_code, $member->code);
                // คงค่า default (ไม่รับโปร)
            }

            // ลบ select pro ตามของเดิม
            $selectpro->delete();
        } else {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ไม่ได้กดรับโปร', $member->code);
        }

        // ====== เงื่อนไขพิเศษตามบัญชีที่เติมเข้า ======
        ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'เช็คเงื่อนไขพิเศษของ บช ที่เติมเข้ามา '.$bank_acc->acc_no, $member->code);

        if ((float) $bank_acc->bonus > 0) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'พบบัญชีมีโบนัสเพิ่ม '.$bank_acc->bonus.'% ของ บช '.$bank_acc->acc_no, $member->code);

            $isActive = $this->isBetweenDates($bank_acc->start_at, $bank_acc->end_at, $now);
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ตรวจสอบช่วงเวลาโบนัสของ บช '.$bank_acc->acc_no, $member->code);

            if ($isActive) {
                ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'อยู่ในช่วงกิจกรรมโบนัส บช '.$bank_acc->acc_no, $member->code);

                // เดิม: โบนัสพิเศษใช้ได้เฉพาะตอน "ไม่ติดโปร"
                if ($pro_code === 0) {
                    $bonusSpecial = ($amount * (float) $bank_acc->bonus) / 100;

                    if ((float) $bank_acc->bonus_max > 0 && $bonusSpecial > (float) $bank_acc->bonus_max) {
                        $bonusSpecial = (float) $bank_acc->bonus_max;
                    }

                    ActivityLoggerUser::activity(
                        'Single Topup ID : '.$paymentId,
                        'คำนวนโบนัสพิเศษจากยอดฝาก '.$amount.' ได้โบนัส '.$bonusSpecial.' ('.$bank_acc->bonus.'%) บช '.$bank_acc->acc_no,
                        $member->code
                    );

                    $bonus = $bonusSpecial;
                    $pro_name = 'ช่วงเวลา พิเศษ รับยอดเพิ่มขึ้น '.$bank_acc->bonus.'% จากยอดฝาก';
                    $total = $total + $bonus;
                    $special = true;
                }
            }
        } else {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'บัญชีนี้ไม่มีโบนัสเพิ่ม บช '.$bank_acc->acc_no, $member->code);
        }

        $point = 0;
        $diamond = 0;
        $count_deposit = 1;

        // ระวัง relation bank อาจยังไม่ถูกโหลด
        $bank_code = optional($bank_acc->bank)->code ?? 0;

        $credit_before = $game_balance;
        $credit_after = $credit_before + $total;

        // ====== กันซ้ำก่อนสร้าง all log ======
        $chk = $this->allLogRepository->findOneByField('bank_payment_id', $paymentId);
        if ($chk) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'พบรายการเติมเงินนี้ในระบบแล้ว', $member->code);

            return false;
        }

        // ====== สร้าง all log ก่อนฝากเข้าเกม ======
        try {
            $alllog = $this->allLogRepository->create([
                'before_credit' => $credit_before,
                'after_credit' => $credit_after,
                'status_log' => 0,
                'pro_id' => $pro_code,
                'pro_amount' => $bonus,
                'bonus' => $bonus,
                'game_code' => $game_code,
                'type_record' => 0,
                'gamebalance' => $game_balance,
                'member_code' => $member_code,
                'member_user' => $member->user_name,
                'amount' => $amount,
                'bank_payment_id' => $paymentId,
                'ip' => $ip,
                'username' => $user_name,
                'remark' => '',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
            ]);
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ไม่สามารถเพิ่มรายการ all log ได้');
            report($e);

            return false;
        }

        $money_text = 'User '.$member->user_name.' Game ID : '.$user_name.' จำนวนเงิน '.$amount.' โบนัส '.$bonus.' จากโปร '.$pro_name.' รวมเป็น '.$total;

        ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'เริ่มรายการเติมเงินให้กับ User : '.$member->user_name.' Game ID : '.$user_name);
        ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, $money_text);

        // ====== ฝากเข้าเกม (external) ======
        $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $total, false);

        if (! ($response['success'] ?? false)) {
            ActivityLoggerUser::activity(
                'Single ฝากเงินเข้าเกม '.$game_name,
                $money_text.' ไม่สามารถฝากเงินเข้าเกมได้ ระบบจะลบรายการ all log ที่สร้างไว้'
            );

            try {
                $alllog->delete();
            } catch (Throwable $e) {
                ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ลบ all log ไม่สำเร็จ หลังจากฝากเงินเข้าเกมไม่สำเร็จ');
                report($e);
            }

            return false;
        }

        ActivityLoggerUser::activity(
            'Single ฝากเงินเข้าเกม '.$game_name,
            $money_text.' ระบบทำการฝากเงินเข้าเกมแล้ว ยอดก่อน '.$response['before'].' ยอดหลัง '.$response['after']
        );

        // ====== ทำรายการใน DB แบบ atomic ======
        try {
            DB::transaction(function () use (
                $ip,
                $paymentId,
                $datenow,
                $config,
                $special,
                $bank_acc,
                $payment,
                $member,
                $game_user,
                $game_code,
                $game_name,
                $user_name,
                $user_code,
                $bank_code,
                $amount,
                $bonus,
                $total,
                $pro_code,
                $pro_name,
                $status_pro,
                $turnpro,
                $withdraw_limit,
                $withdraw_limit_rate,
                $count_deposit,
                $response,
                $alllog,
                $empTopup,
                &$point,
                &$diamond
            ) {
                // กันซ้ำระดับ credit log (สำคัญ) + ต้อง throw เพื่อให้ rollback
                $chknew = $this->memberCreditLogRepository->findOneWhere([
                    'member_code' => $member->code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'kind' => 'TOPUP',
                ]);

                if ($chknew) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม '.$game_name, 'หยุดการทำงาน เนื่องจาก Log ซ้ำ (จะ rollback)');
                    throw new \RuntimeException('Duplicate member_credit_log');
                }

                if ($special) {
                    $remark = 'ช่วงเวลาสุดพิเศษ '.$bank_acc->start_at.' ถึง '.$bank_acc->end_at.' รับเพิ่ม '.$bank_acc->bonus.'% อิงรายการฝาก ID '.$paymentId;
                } else {
                    $remark = 'เติมเงินฝากอ้างอิงรายการฝาก ID : '.$paymentId.' RefID : '.$response['ref_id'];
                }

                $bill = $this->memberCreditLogRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'amount' => $amount,
                    'bonus' => $bonus,
                    'total' => $total,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_total' => $total,
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member->code,
                    'user_name' => $member->user_name,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'bank_code' => $bank_code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'emp_code' => $empTopup,
                    'auto' => 'Y',
                    'remark' => $remark,
                    'kind' => 'TOPUP',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);

                if ($special) {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => $game_code,
                        'gameuser_code' => $user_code,
                        'amount' => 0,
                        'bonus' => $bonus,
                        'total' => 0,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'credit_bonus' => $bonus,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'bank_code' => $bank_code,
                        'refer_code' => $paymentId,
                        'refer_table' => 'bank_payment',
                        'emp_code' => $empTopup,
                        'auto' => 'Y',
                        'remark' => 'อ้างอิงรายการฝาก ID : '.$paymentId.' RefID : '.$response['ref_id'].' ได้โบนัสจากช่องทางการฝาก เพิ่ม '.$bank_acc->bonus.'%',
                        'kind' => 'G_BONUS',
                        'user_create' => 'System Auto',
                        'user_update' => 'System Auto',
                    ]);
                }

                // update all log
                $alllog->remark = 'Auto Topup and Refer Credit Log ID : '.$bill->code;
                $alllog->user_update = 'System Auto';
                $alllog->save();

                // ====== point ======
                if ($config->point_open == 'Y') {
                    if ($config->point_per_bill == 'N') {
                        if ($amount >= $config->points && $config->points > 0) {
                            $point = (int) floor($amount / $config->points);

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน '.$amount.' บาท เติม '.$config->points.' ได้รับ 1 แต้ม สรุปได้รับ '.$point,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->points_topup && $config->points_topup > 0 && $config->points_amount > 0) {
                            $point = (int) $config->points_amount;

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน '.$amount.' บาท (นับเป็นบิล) เติมยอด >= '.$config->points_topup.' ได้รับ '.$point.' แต้ม',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                // ====== diamond ======
                if ($config->diamond_open == 'Y') {
                    if ($config->diamond_per_bill == 'N') {
                        if ($amount >= $config->diamonds && $config->diamonds > 0) {
                            $diamond = (int) floor($amount / $config->diamonds);

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน '.$amount.' บาท เติม '.$config->diamonds.' ได้รับ 1 เม็ด สรุปได้รับ '.$diamond,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->diamonds_topup && $config->diamonds_topup > 0 && $config->diamonds_amount > 0) {
                            $diamond = (int) $config->diamonds_amount;

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน '.$amount.' บาท (นับเป็นบิล) เติมยอด >= '.$config->diamonds_topup.' ได้รับ '.$diamond.' เม็ด',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                // ====== update payment ======
                $payment->user_id = $user_name;
                $payment->status = 1;
                $payment->before_credit = $response['before'];
                $payment->after_credit = $response['after'];
                $payment->pro_id = $pro_code;
                $payment->amount = $amount;
                $payment->pro_amount = $bonus;
                $payment->score = $total;
                $payment->date_topup = $datenow;
                $payment->date_approve = $datenow;
                $payment->autocheck = 'Y';
                $payment->remark_admin = trim((string) $payment->remark_admin).' (เติมแล้ว)';
                $payment->topup_by = 'System Auto';
                $payment->ip_topup = $ip;
                $payment->save();

                // ====== bill ======
                $bills = app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'ref_id' => $response['ref_id'],
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member->code,
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'method' => 'TOPUP',
                    'transfer_type' => 1,
                    'amount' => $amount,
                    'balance_before' => $response['before'],
                    'balance_after' => $response['after'],
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_balance' => $total,
                    'amount_request' => ($game_user->balance + ($total * $turnpro)), // คง intent เดิม
                    'amount_limit' => ($game_user->balance + (($amount + $bonus) * $withdraw_limit_rate)),
                    'ip' => $ip,
                    'user_create' => $member->name,
                    'user_update' => $member->name,
                ]);

                // ====== promotion effect on game_user ======
                $billcode = 0;

                if ($pro_code > 0) {
                    $this->memberPromotionLogRepository->create([
                        'date_start' => now()->toDateString(),
                        'bill_code' => $bills->code,
                        'member_code' => $member->code,
                        'game_code' => $game_code,
                        'game_name' => $game_name,
                        'gameuser_code' => $user_code,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'turnpro' => $turnpro,
                        'balance' => ($response['before'] - $amount),
                        'amount' => $amount,
                        'bonus' => $bonus,
                        'amount_balance' => ($total * $turnpro),
                        'total_amount_balance' => (($response['before'] - $amount) + ($total * $turnpro)),
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_rate' => 0,
                        'complete' => 'N',
                        'enable' => 'Y',
                        'user_create' => $member->name,
                        'user_update' => $member->name,
                    ]);

                    $billcode = $bills->code;

                    $game_user->balance = $response['after'];
                    $game_user->pro_code = $pro_code;
                    $game_user->bill_code = $billcode;
                    $game_user->turnpro = $turnpro;
                    $game_user->amount = $amount;
                    $game_user->bonus = $bonus;
                    $game_user->amount_balance += ($game_user->balance + ($total * $turnpro));
                    $game_user->withdraw_limit = $withdraw_limit;
                    $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                    $game_user->withdraw_limit_amount += ($game_user->balance + (($amount + $bonus) * $withdraw_limit_rate));
                    $game_user->save();
                } else {
                    if ($game_user->amount_balance > 0 || $game_user->pro_code > 0) {
                        if ($response['before'] > (float) $config->pro_reset) {
                            $game_user->amount_balance += ($total * (float) $game_user->turnpro);
                            $game_user->withdraw_limit_amount += ($total * (float) $game_user->withdraw_limit_rate);
                            $game_user->save();
                        } else {
                            $game_user->balance = $response['after'];
                            $game_user->pro_code = 0;
                            $game_user->bill_code = 0;
                            $game_user->turnpro = 0;
                            $game_user->amount = 0;
                            $game_user->bonus = 0;
                            $game_user->amount_balance = 0;
                            $game_user->withdraw_limit = 0;
                            $game_user->withdraw_limit_rate = 0;
                            $game_user->withdraw_limit_amount = 0;
                            $game_user->save();
                        }
                    }
                }

                $bill->amount_balance = $game_user->amount_balance;
                $bill->withdraw_limit = $game_user->withdraw_limit;
                $bill->withdraw_limit_amount = $game_user->withdraw_limit_amount;
                $bill->save();

                // ====== update member ======
                $member->credit += $amount;
                $member->sum_deposit += $amount;
                $member->status_pro = $status_pro;
                $member->point_deposit += $point;
                $member->diamond += $diamond;
                $member->balance = $response['after'];
                $member->count_deposit += $count_deposit;
                $member->save();
            });
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'พบปัญหาใน Transaction');
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'Rollback Transaction');

            // ฝากเข้าเกมไปแล้ว -> พยายามถอนคืน (คง behavior เดิม)
            try {
                $rollbackRes = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $total);
                if (($rollbackRes['success'] ?? false) === true) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม '.$game_name, 'Rollback: ถอนเงินออกจากเกมแล้ว');
                } else {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม '.$game_name, 'Rollback: ไม่สามารถถอนเงินออกจากเกมได้');
                }
            } catch (Throwable $ex) {
                report($ex);
            }

            // ลบ allLog ที่ผูกกับรายการนี้
            try {
                $this->allLogRepository->where('bank_payment_id', $paymentId)->delete();
            } catch (Throwable $ex) {
                report($ex);
            }

            report($e);

            return false;
        }

        ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'เติมเงินสำเร็จให้กับ User : '.$member->user_name);
        Notification::send($member, new RealTimeNotification(Lang::get('app.topup.complete').$total));

        // ====== update sum_deposit account ======
        $account = $payment->bank_account;

        $sumToday = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->income()
            ->active()
            ->complete()
            ->where('account_code', $payment->account_code)
            ->whereDate('date_topup', $today)
            ->sum('value');

        $account->update(['sum_deposit' => $sumToday]);

        if ((float) $account->sum_limit > 0 && $sumToday >= (float) $account->sum_limit) {
            $account->update(['display_wallet' => 'N']);

            $alt = BankAccount::where('banks', $account->banks)
                ->where('bank_type', 1)
                ->where('display_wallet', 'N')
                ->where('status_auto', 'Y')
                ->where('enable', 'Y')
                ->where('sum_deposit', 0)
                ->orderBy('sort', 'asc')
                ->first();

            if ($alt) {
                $alt->update(['display_wallet' => 'Y']);
            }
        }

        return true;
    }

    public function refillPaymentSingle__($data): bool
    {
        $ip = request()->ip();

        $now = now();
        $today = $now->toDateString();
        $datenow = $now->toDateTimeString();

        $config = $this->getCoreConfig();
        $special = false;

        $paymentId = Arr::get($data, 'code');
        $memberCode = Arr::get($data, 'member_topup');
        $accountCode = Arr::get($data, 'account_code');
        $amount = (float) Arr::get($data, 'value', 0);
        $empTopup = Arr::get($data, 'emp_topup');

        if (! $paymentId || ! $memberCode || ! $accountCode || $amount <= 0) {
            return false;
        }

        $payment = $this->find($paymentId);
        if (! $payment) {
            return false;
        }

        $member = $this->memberRepository->find($memberCode);
        if (! $member) {
            return false;
        }

        $bank_acc = $this->bankAccountRepository->find($accountCode);
        if (! $bank_acc) {
            return false;
        }

        $game = core()->getGame();
        if (! $game) {
            return false;
        }

        $game_user = $this->gameUserRepository->findOneWhere([
            'member_code' => $member->code,
            'game_code' => $game->code,
            'enable' => 'Y',
        ]);

        if (! $game_user) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ไม่พบ game user ที่ enable=Y ของสมาชิก', $member->code);
            return false;
        }

        $game_code = $game->code;
        $game_name = $game->name;
        $game_balance = (float) $game_user->balance;

        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $member_code = $member->code;

        $bonus = 0;
        $pro_code = 0;
        $pro_name = '';
        $total = $amount;
        $status_pro = $member->status_pro;
        $turnpro = 0;
        $withdraw_limit = 0;
        $withdraw_limit_rate = 0;

        $selectpro = $this->memberSelectProRepository->findOneWhere(['member_code' => $member_code]);

        if ($selectpro) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'มีการเลือกโปรโมชั่น โปรรหัส '.$selectpro->pro_code, $member->code);

            if ($game_balance <= (float) $config->pro_reset) {
                ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ยอดเงินปัจจุบัน น้อยกว่าหรือเท่ากับโปรรีเซต ผ่านเงื่อนไข โปรรหัส '.$selectpro->pro_code, $member->code);

                $promotion = $this->promotionRepository->checkSelectPro(
                    $selectpro->pro_code,
                    $member_code,
                    $amount,
                    $datenow
                );

                $bonus = (float) ($promotion['bonus'] ?? 0);
                $pro_code = (int) ($promotion['pro_code'] ?? 0);
                $pro_name = (string) ($promotion['pro_name'] ?? '');
                $total = (float) ($promotion['total'] ?? $amount);
                $status_pro = 1;
                $turnpro = (float) ($promotion['turnpro'] ?? 0);
                $withdraw_limit = (float) ($promotion['withdraw_limit'] ?? 0);
                $withdraw_limit_rate = (float) ($promotion['withdraw_limit_rate'] ?? 0);
            } else {
                ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ยอดเงินปัจจุบัน มากกว่าโปรรีเซต ไม่ผ่านเงื่อนไข โปรรหัส '.$selectpro->pro_code, $member->code);
            }

            $selectpro->delete();
        } else {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ไม่ได้กดรับโปร', $member->code);
        }

        ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'เช็คเงื่อนไขพิเศษของ บช ที่เติมเข้ามา '.$bank_acc->acc_no, $member->code);

        if ((float) $bank_acc->bonus > 0) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'พบบัญชีมีโบนัสเพิ่ม '.$bank_acc->bonus.'% ของ บช '.$bank_acc->acc_no, $member->code);

            $isActive = $this->isBetweenDates($bank_acc->start_at, $bank_acc->end_at, $now);
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ตรวจสอบช่วงเวลาโบนัสของ บช '.$bank_acc->acc_no, $member->code);

            if ($isActive) {
                ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'อยู่ในช่วงกิจกรรมโบนัส บช '.$bank_acc->acc_no, $member->code);

                if ($pro_code === 0) {
                    $bonusSpecial = ($amount * (float) $bank_acc->bonus) / 100;

                    if ((float) $bank_acc->bonus_max > 0 && $bonusSpecial > (float) $bank_acc->bonus_max) {
                        $bonusSpecial = (float) $bank_acc->bonus_max;
                    }

                    ActivityLoggerUser::activity(
                        'Single Topup ID : '.$paymentId,
                        'คำนวนโบนัสพิเศษจากยอดฝาก '.$amount.' ได้โบนัส '.$bonusSpecial.' ('.$bank_acc->bonus.'%) บช '.$bank_acc->acc_no,
                        $member->code
                    );

                    $bonus = $bonusSpecial;
                    $pro_name = 'ช่วงเวลา พิเศษ รับยอดเพิ่มขึ้น '.$bank_acc->bonus.'% จากยอดฝาก';
                    $total = $total + $bonus;
                    $special = true;
                }
            }
        } else {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'บัญชีนี้ไม่มีโบนัสเพิ่ม บช '.$bank_acc->acc_no, $member->code);
        }

        $point = 0;
        $diamond = 0;
        $count_deposit = 1;

        $bank_code = optional($bank_acc->bank)->code ?? 0;

        $credit_before = $game_balance;
        $credit_after = $credit_before + $total;

        $chk = $this->allLogRepository->findOneByField('bank_payment_id', $paymentId);
        if ($chk) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'พบรายการเติมเงินนี้ในระบบแล้ว', $member->code);
            return false;
        }

        try {
            $alllog = $this->allLogRepository->create([
                'before_credit' => $credit_before,
                'after_credit' => $credit_after,
                'status_log' => 0,
                'pro_id' => $pro_code,
                'pro_amount' => $bonus,
                'bonus' => $bonus,
                'game_code' => $game_code,
                'type_record' => 0,
                'gamebalance' => $game_balance,
                'member_code' => $member_code,
                'member_user' => $member->user_name,
                'amount' => $amount,
                'bank_payment_id' => $paymentId,
                'ip' => $ip,
                'username' => $user_name,
                'remark' => '',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
            ]);
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ไม่สามารถเพิ่มรายการ all log ได้');
            report($e);
            return false;
        }

        $money_text = 'User '.$member->user_name.' Game ID : '.$user_name.' จำนวนเงิน '.$amount.' โบนัส '.$bonus.' จากโปร '.$pro_name.' รวมเป็น '.$total;

        ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'เริ่มรายการเติมเงินให้กับ User : '.$member->user_name.' Game ID : '.$user_name);
        ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, $money_text);

        $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $total, false);

        if (! ($response['success'] ?? false)) {
            ActivityLoggerUser::activity(
                'Single ฝากเงินเข้าเกม '.$game_name,
                $money_text.' ไม่สามารถฝากเงินเข้าเกมได้ ระบบจะลบรายการ all log ที่สร้างไว้'
            );

            try {
                $alllog->delete();
            } catch (Throwable $e) {
                ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'ลบ all log ไม่สำเร็จ หลังจากฝากเงินเข้าเกมไม่สำเร็จ');
                report($e);
            }

            return false;
        }

        ActivityLoggerUser::activity(
            'Single ฝากเงินเข้าเกม '.$game_name,
            $money_text.' ระบบทำการฝากเงินเข้าเกมแล้ว ยอดก่อน '.$response['before'].' ยอดหลัง '.$response['after']
        );

        try {
            DB::transaction(function () use (
                $ip,
                $paymentId,
                $datenow,
                $today,
                $config,
                $special,
                $bank_acc,
                $payment,
                $member,
                $game_user,
                $game_code,
                $game_name,
                $user_name,
                $user_code,
                $bank_code,
                $amount,
                $bonus,
                $total,
                $pro_code,
                $pro_name,
                $status_pro,
                $turnpro,
                $withdraw_limit,
                $withdraw_limit_rate,
                $count_deposit,
                $response,
                $alllog,
                $empTopup,
                &$point,
                &$diamond
            ) {
                $chknew = $this->memberCreditLogRepository->findOneWhere([
                    'member_code' => $member->code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'kind' => 'TOPUP',
                ]);

                if ($chknew) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม '.$game_name, 'หยุดการทำงาน เนื่องจาก Log ซ้ำ (จะ rollback)');
                    throw new \RuntimeException('Duplicate member_credit_log');
                }

                if ($special) {
                    $remark = 'ช่วงเวลาสุดพิเศษ '.$bank_acc->start_at.' ถึง '.$bank_acc->end_at.' รับเพิ่ม '.$bank_acc->bonus.'% อิงรายการฝาก ID '.$paymentId;
                } else {
                    $remark = 'เติมเงินฝากอ้างอิงรายการฝาก ID : '.$paymentId.' RefID : '.$response['ref_id'];
                }

                $bill = $this->memberCreditLogRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'amount' => $amount,
                    'bonus' => $bonus,
                    'total' => $total,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_total' => $total,
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member->code,
                    'user_name' => $member->user_name,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'bank_code' => $bank_code,
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'emp_code' => $empTopup,
                    'auto' => 'Y',
                    'remark' => $remark,
                    'kind' => 'TOPUP',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);

                if ($special) {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => $game_code,
                        'gameuser_code' => $user_code,
                        'amount' => 0,
                        'bonus' => $bonus,
                        'total' => 0,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'credit_bonus' => $bonus,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'bank_code' => $bank_code,
                        'refer_code' => $paymentId,
                        'refer_table' => 'bank_payment',
                        'emp_code' => $empTopup,
                        'auto' => 'Y',
                        'remark' => 'อ้างอิงรายการฝาก ID : '.$paymentId.' RefID : '.$response['ref_id'].' ได้โบนัสจากช่องทางการฝาก เพิ่ม '.$bank_acc->bonus.'%',
                        'kind' => 'G_BONUS',
                        'user_create' => 'System Auto',
                        'user_update' => 'System Auto',
                    ]);
                }

                $alllog->remark = 'Auto Topup and Refer Credit Log ID : '.$bill->code;
                $alllog->user_update = 'System Auto';
                $alllog->save();

                if ($config->point_open == 'Y') {
                    if ($config->point_per_bill == 'N') {
                        if ($amount >= $config->points && $config->points > 0) {
                            $point = (int) floor($amount / $config->points);

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน '.$amount.' บาท เติม '.$config->points.' ได้รับ 1 แต้ม สรุปได้รับ '.$point,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->points_topup && $config->points_topup > 0 && $config->points_amount > 0) {
                            $point = (int) $config->points_amount;

                            $this->memberPointLogRepository->create([
                                'point_type' => 'D',
                                'point_amount' => $point,
                                'point_before' => $member->point_deposit,
                                'point_balance' => ($member->point_deposit + $point),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับ Point จากการเติมเงิน '.$amount.' บาท (นับเป็นบิล) เติมยอด >= '.$config->points_topup.' ได้รับ '.$point.' แต้ม',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                if ($config->diamond_open == 'Y') {
                    if ($config->diamond_per_bill == 'N') {
                        if ($amount >= $config->diamonds && $config->diamonds > 0) {
                            $diamond = (int) floor($amount / $config->diamonds);

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน '.$amount.' บาท เติม '.$config->diamonds.' ได้รับ 1 เม็ด สรุปได้รับ '.$diamond,
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    } else {
                        if ($amount >= $config->diamonds_topup && $config->diamonds_topup > 0 && $config->diamonds_amount > 0) {
                            $diamond = (int) $config->diamonds_amount;

                            $this->memberDiamondLogRepository->create([
                                'diamond_type' => 'D',
                                'diamond_amount' => $diamond,
                                'diamond_before' => $member->diamond,
                                'diamond_balance' => ($member->diamond + $diamond),
                                'member_code' => $member->code,
                                'remark' => 'ได้รับเพชรจากการเติมเงิน '.$amount.' บาท (นับเป็นบิล) เติมยอด >= '.$config->diamonds_topup.' ได้รับ '.$diamond.' เม็ด',
                                'emp_code' => $empTopup,
                                'ip' => $ip,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);
                        }
                    }
                }

                $payment->user_id = $user_name;
                $payment->status = 1;
                $payment->before_credit = $response['before'];
                $payment->after_credit = $response['after'];
                $payment->pro_id = $pro_code;
                $payment->amount = $amount;
                $payment->pro_amount = $bonus;
                $payment->score = $total;
                $payment->date_topup = $datenow;
                $payment->date_approve = $datenow;
                $payment->autocheck = 'Y';
                $payment->remark_admin = trim((string) $payment->remark_admin).' (เติมแล้ว)';
                $payment->topup_by = 'System Auto';
                $payment->ip_topup = $ip;
                $payment->save();

                $bills = app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $paymentId,
                    'refer_table' => 'bank_payment',
                    'ref_id' => $response['ref_id'],
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member->code,
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'method' => 'TOPUP',
                    'transfer_type' => 1,
                    'amount' => $amount,
                    'balance_before' => $response['before'],
                    'balance_after' => $response['after'],
                    'credit' => $amount,
                    'credit_bonus' => $bonus,
                    'credit_balance' => $total,
                    'amount_request' => ($game_user->balance + ($total * $turnpro)),
                    'amount_limit' => ($game_user->balance + (($amount + $bonus) * $withdraw_limit_rate)),
                    'ip' => $ip,
                    'user_create' => $member->name,
                    'user_update' => $member->name,
                ]);

                $billcode = 0;

                if ($pro_code > 0) {
                    $this->memberPromotionLogRepository->create([
                        'date_start' => now()->toDateString(),
                        'bill_code' => $bills->code,
                        'member_code' => $member->code,
                        'game_code' => $game_code,
                        'game_name' => $game_name,
                        'gameuser_code' => $user_code,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'turnpro' => $turnpro,
                        'balance' => ($response['before'] - $amount),
                        'amount' => $amount,
                        'bonus' => $bonus,
                        'amount_balance' => ($total * $turnpro),
                        'total_amount_balance' => (($response['before'] - $amount) + ($total * $turnpro)),
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_rate' => 0,
                        'complete' => 'N',
                        'enable' => 'Y',
                        'user_create' => $member->name,
                        'user_update' => $member->name,
                    ]);

                    $billcode = $bills->code;

                    $game_user->balance = $response['after'];
                    $game_user->pro_code = $pro_code;
                    $game_user->bill_code = $billcode;
                    $game_user->turnpro = $turnpro;
                    $game_user->amount = $amount;
                    $game_user->bonus = $bonus;
                    $game_user->amount_balance += ($game_user->balance + ($total * $turnpro));
                    $game_user->withdraw_limit = $withdraw_limit;
                    $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                    $game_user->withdraw_limit_amount += ($game_user->balance + (($amount + $bonus) * $withdraw_limit_rate));
                    $game_user->save();
                } else {
                    if ($game_user->amount_balance > 0 || $game_user->pro_code > 0) {
                        if ($response['before'] > (float) $config->pro_reset) {
                            $game_user->amount_balance += ($total * (float) $game_user->turnpro);
                            $game_user->withdraw_limit_amount += ($total * (float) $game_user->withdraw_limit_rate);
                            $game_user->save();
                        } else {
                            $game_user->balance = $response['after'];
                            $game_user->pro_code = 0;
                            $game_user->bill_code = 0;
                            $game_user->turnpro = 0;
                            $game_user->amount = 0;
                            $game_user->bonus = 0;
                            $game_user->amount_balance = 0;
                            $game_user->withdraw_limit = 0;
                            $game_user->withdraw_limit_rate = 0;
                            $game_user->withdraw_limit_amount = 0;
                            $game_user->save();
                        }
                    }
                }

                $bill->amount_balance = $game_user->amount_balance;
                $bill->withdraw_limit = $game_user->withdraw_limit;
                $bill->withdraw_limit_amount = $game_user->withdraw_limit_amount;
                $bill->save();

                // ✅ ====== update deposit stats (legacy gate) ======
                // นับ “ยอดฝากจริง” = $amount (ไม่รวมโบนัส)
                // เงื่อนไขลูกค้าเก่า: count >= 10 และ sum > 10000
                $statsRow = DB::table('member_deposit_stats')
                    ->where('member_code', $member->code)
                    ->lockForUpdate()
                    ->first();

                if (! $statsRow) {
                    DB::table('member_deposit_stats')->insert([
                        'member_code' => $member->code,
                        'deposit_success_count' => 0,
                        'deposit_success_sum' => 0,
                        'legacy_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $statsRow = DB::table('member_deposit_stats')
                        ->where('member_code', $member->code)
                        ->lockForUpdate()
                        ->first();
                }

                if ($statsRow) {
                    $newCount = (int) $statsRow->deposit_success_count + 1;
                    $newSum = (float) $statsRow->deposit_success_sum + (float) $amount;

                    $legacyAt = $statsRow->legacy_at;

                    if ($legacyAt === null && $newCount >= 10 && $newSum > 10000) {
                        $legacyAt = now();
                    }

                    DB::table('member_deposit_stats')
                        ->where('member_code', $member->code)
                        ->update([
                            'deposit_success_count' => $newCount,
                            'deposit_success_sum' => $newSum,
                            'legacy_at' => $legacyAt,
                            'updated_at' => now(),
                        ]);
                }

                $member->credit += $amount;
                $member->sum_deposit += $amount;
                $member->status_pro = $status_pro;
                $member->point_deposit += $point;
                $member->diamond += $diamond;
                $member->balance = $response['after'];
                $member->count_deposit += $count_deposit;
                $member->save();
            });
        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'พบปัญหาใน Transaction');
            ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'Rollback Transaction');

            try {
                $rollbackRes = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $total);
                if (($rollbackRes['success'] ?? false) === true) {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม '.$game_name, 'Rollback: ถอนเงินออกจากเกมแล้ว');
                } else {
                    ActivityLoggerUser::activity('Single ฝากเงินเข้าเกม '.$game_name, 'Rollback: ไม่สามารถถอนเงินออกจากเกมได้');
                }
            } catch (Throwable $ex) {
                report($ex);
            }

            try {
                $this->allLogRepository->where('bank_payment_id', $paymentId)->delete();
            } catch (Throwable $ex) {
                report($ex);
            }

            report($e);
            return false;
        }

        ActivityLoggerUser::activity('Single Topup ID : '.$paymentId, 'เติมเงินสำเร็จให้กับ User : '.$member->user_name);
        Notification::send($member, new RealTimeNotification(Lang::get('app.topup.complete').$total));

        $account = $payment->bank_account;

        $sumToday = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->income()
            ->active()
            ->complete()
            ->where('account_code', $payment->account_code)
            ->whereDate('date_topup', $today)
            ->sum('value');

        $account->update(['sum_deposit' => $sumToday]);

        if ((float) $account->sum_limit > 0 && $sumToday >= (float) $account->sum_limit) {
            $account->update(['display_wallet' => 'N']);

            $alt = BankAccount::where('banks', $account->banks)
                ->where('bank_type', 1)
                ->where('display_wallet', 'N')
                ->where('status_auto', 'Y')
                ->where('enable', 'Y')
                ->where('sum_deposit', 0)
                ->orderBy('sort', 'asc')
                ->first();

            if ($alt) {
                $alt->update(['display_wallet' => 'Y']);
            }
        }

        return true;
    }

    public function refillPaymentSeamless($data): bool
    {
        $ip = request()->ip();

        $today = now()->toDateString();
        $datenow = now()->toDateTimeString();

        $config = $this->getCoreConfig();
        $special = false;

        $payment = $this->find($data['code']);
        if (! $payment) {
            return false;
        }

        $member = $this->memberRepository->find($data['member_topup']);
        if (! $member) {
            return false;
        }

        $bank_acc = $this->bankAccountRepository->find($data['account_code']);
        if (! $bank_acc) {
            return false;
        }

        $game = core()->getGame();
        $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        if (! $game_user) {
            $res = $this->gameUserRepository->addGameUser($game->code, $member->code, ['username' => $member->user_name, 'product_id' => 'PGSOFT', 'user_create' => $member->user_name]);
            if ($res['success'] !== true) {
                return false;
            }
            $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);

        }
        $game_code = $game->code;
        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $game_name = $game->name;
        $game_balance = $game_user->balance;
        $member_code = $member->code;
        $amount = $data['value'];

        $selectpro = $this->memberSelectProRepository->findOneWhere(['member_code' => $member_code]);
        if ($selectpro) {
            if ($game_user->balance <= $config->pro_reset) {
                $promotion = $this->promotionRepository->checkSelectPro($selectpro->pro_code, $member_code, $amount, $datenow);
                $bonus = $promotion['bonus'];
                $pro_code = $promotion['pro_code'];
                $pro_name = $promotion['pro_name'];
                $total = $promotion['total'];
                $status_pro = 1;
                $turnpro = $promotion['turnpro'];
                $withdraw_limit = $promotion['withdraw_limit'];
                $withdraw_limit_rate = $promotion['withdraw_limit_rate'];
            } else {
                $bonus = 0;
                $pro_code = 0;
                $pro_name = '';
                $total = $amount;
                $status_pro = $member['status_pro'];
                $turnpro = 0;
                $withdraw_limit = 0;
                $withdraw_limit_rate = 0;
            }
            $selectpro->delete();
        } else {
            $bonus = 0;
            $pro_code = 0;
            $pro_name = '';
            $total = $amount;
            $status_pro = $member['status_pro'];
            $turnpro = 0;
            $withdraw_limit = 0;
            $withdraw_limit_rate = 0;
        }

        if ($bank_acc->bonus > 0) {

            ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'พบมี โบนัสเพิ่ม '.$bank_acc->bonus.' ของ บช ที่เติมเข้ามา '.$bank_acc->acc_no, $member->code);

            $now = now(); // ตาม app.timezone

            $isActive = $this->isBetweenDates($bank_acc->start_at, $bank_acc->end_at, $now);
            ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'ตรวจสอบ ช่วงเวลา ที่กำหนด ของระบุไว้ ในบช ที่เติมเข้ามา '.$bank_acc->acc_no, $member->code);

            if ($isActive) {
                ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'ยังอยู่ใมนช่วงเวลากิจกรรม โบนัสเพิื่ม '.$bank_acc->bonus.' ในบช ที่เติมเข้ามา '.$bank_acc->acc_no, $member->code);

                if ($pro_code === 0) {
                    $bonus = ($amount * $bank_acc->bonus) / 100;
                    if ($bank_acc->bonus_max > 0) {
                        if ($bonus > $bank_acc->bonus_max) {
                            $bonus = $bank_acc->bonus_max;
                        }
                    }

                    ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'คำนวนจากยอดฝาก '.$amount.' โบนัสเพิื่ม '.$bank_acc->bonus.'% ได้ โบนัส '.$bonus.' ในบช ที่เติมเข้ามา '.$bank_acc->acc_no, $member->code);

                    $pro_name = 'ช่วงเวลา พิเศษ รับยอดเพิ่มขึ้น '.$bank_acc->bonus.'% จากยอดฝาก';
                    $total = ($total + $bonus);
                    $special = true;

                }

            }
        } else {
            ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'พบ ไม่มี โบนัสเพิ่ม ของ บช ที่เติมเข้ามา '.$bank_acc->acc_no, $member->code);

        }

        $point = 0;
        $diamond = 0;
        $count_deposit = 1;

        $bank_code = $bank_acc->bank->code;

        $credit_before = $member->balance;
        $credit_after = ($credit_before + $total);

        $chk = $this->allLogRepository->findOneByField('bank_payment_id', $data['code']);
        if ($chk) {
            ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'พบรายการเติมเงิน นี้ในระบบแล้ว', $member->code);

            return false;
        }

        try {

            $alllog = $this->allLogRepository->create([
                'before_credit' => $credit_before,
                'after_credit' => $credit_after,
                'status_log' => 0,
                'pro_id' => $pro_code,
                'pro_amount' => $bonus,
                'bonus' => $bonus,
                'game_code' => $game_code,
                'type_record' => 0,
                'gamebalance' => $game_balance,
                'member_code' => $member_code,
                'member_user' => $member['user_name'],
                'amount' => $amount,
                'bank_payment_id' => $data['code'],
                'ip' => $ip,
                'username' => $user_name,
                'remark' => '',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
            ]);

        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'ไม่สามารถ เพิ่มรายการ all log ได้');
            report($e);

            return false;
        }

        $money_text = 'User '.$member->user_name.' Game ID : '.$user_name.' จำนวนเงิน '.$amount.' โบนัส '.$bonus.' จากโปร '.$pro_name.' รวมเป็น '.$total;

        ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'เริ่มรายการเติมเงิน ให้กับ User : '.$member->user_name.' Game ID : '.$user_name);
        //        ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], $money_text);

        DB::beginTransaction();

        try {

            $chknew = $this->memberCreditLogRepository->findOneWhere(['member_code' => $member_code, 'refer_code' => $data['code'], 'refer_table' => 'bank_payment', 'kind' => 'TOPUP']);
            if ($chknew) {
                ActivityLoggerUser::activity('Seamless ฝากเงินเข้าเกม '.$game_name, $money_text.' หยุดการทำงาน เนื่องจาก Log ซ้ำ');
                DB::rollBack();

                return false;
            }

            $bill = $this->memberCreditLogRepository->create([
                'ip' => $ip,
                'credit_type' => 'D',
                'game_code' => $game_code,
                'gameuser_code' => $user_code,
                'amount' => $amount,
                'bonus' => $bonus,
                'total' => $total,
                'balance_before' => 0,
                'balance_after' => 0,
                'credit' => $amount,
                'credit_bonus' => $bonus,
                'credit_total' => $total,
                'credit_before' => $member->balance,
                'credit_after' => ($member->balance + $total),
                'member_code' => $member_code,
                'user_name' => $member->user_name,
                'pro_code' => $pro_code,
                'pro_name' => $pro_name,
                'bank_code' => $bank_code,
                'refer_code' => $data['code'],
                'refer_table' => 'bank_payment',
                'emp_code' => $data['emp_topup'],
                'auto' => 'Y',
                'remark' => ($payment->emp_topup == 0 ? '(อิงรายการฝากที่ : '.$data['code'].') ' : $payment->remark_admin),
                'kind' => 'TOPUP',
                'amount_balance' => $game_user->amount_balance,
                'withdraw_limit' => $game_user->withdraw_limit,
                'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
            ]);

            if ($special) {
                $this->memberCreditLogRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'amount' => 0,
                    'bonus' => $bonus,
                    'total' => 0,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'credit_bonus' => $bonus,
                    'credit_total' => 0,
                    'credit_before' => 0,
                    'credit_after' => 0,
                    'member_code' => $member_code,
                    'user_name' => $member->user_name,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'bank_code' => $bank_code,
                    'refer_code' => $data['code'],
                    'refer_table' => 'bank_payment',
                    'emp_code' => $data['emp_topup'],
                    'auto' => 'Y',
                    'remark' => 'อ้างอิงรายการฝากที่   ID : '.$data['code'].' ได้โบนัส จากช่องทางการฝากที่กำหนด เพิ่ม '.$bank_acc->bonus.' %',
                    'kind' => 'G_BONUS',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);

            }

            $billcode = 0;

            $alllog->remark = 'Auto Topup and Refer Credit Log ID : '.$bill->code;
            $alllog->user_update = 'System Auto';
            $alllog->save();

            if ($config->point_open == 'Y') {

                if ($config->point_per_bill == 'N') {

                    if ($amount >= $config->points && $config->points > 0) {
                        $point = floor($amount / $config->points);

                        $this->memberPointLogRepository->create([
                            'point_type' => 'D',
                            'point_amount' => $point,
                            'point_before' => $member->point_deposit,
                            'point_balance' => ($member->point_deposit + $point),
                            'member_code' => $member_code,
                            'remark' => 'ได้รับ Point จากการเติมเงิน '.$amount.' บาท เติม '.$config->points.' ได้รับ 1 แต้ม สรุปได้รับ '.$point,
                            'emp_code' => $data['emp_topup'],
                            'ip' => $ip,
                            'user_create' => 'System Auto',
                            'user_update' => 'System Auto',
                        ]);
                    }

                } else {

                    if ($amount >= $config->points_topup && $config->points_topup > 0 && $config->points_amount > 0) {
                        $point = $config->points_amount;

                        $this->memberPointLogRepository->create([
                            'point_type' => 'D',
                            'point_amount' => $point,
                            'point_before' => $member->point_deposit,
                            'point_balance' => ($member->point_deposit + $point),
                            'member_code' => $member_code,
                            'remark' => 'ได้รับ Point จากการเติมเงิน '.$amount.' บาท ประเภทนับเป็นบิล เติมยอดมากกว่าหรือเท่ากับ '.$config->points_topup.' ได้รับ '.$point.' แต้ม',
                            'emp_code' => $data['emp_topup'],
                            'ip' => $ip,
                            'user_create' => 'System Auto',
                            'user_update' => 'System Auto',
                        ]);

                    }

                }

            }

            if ($config->diamond_open == 'Y') {

                if ($config->diamond_per_bill == 'N') {

                    if ($amount >= $config->diamonds && $config->diamonds > 0) {
                        $diamond = floor($amount / $config->diamonds);

                        $this->memberDiamondLogRepository->create([
                            'diamond_type' => 'D',
                            'diamond_amount' => $diamond,
                            'diamond_before' => $member->diamond,
                            'diamond_balance' => ($member->diamond + $diamond),
                            'member_code' => $member_code,
                            'remark' => 'ได้รับเพชร จากการเติมเงิน '.$amount.' บาท เติม '.$config->diamonds.' ได้รับ 1 เม็ด สรุปได้รับ '.$diamond,
                            'emp_code' => $data['emp_topup'],
                            'ip' => $ip,
                            'user_create' => 'System Auto',
                            'user_update' => 'System Auto',
                        ]);
                    }

                } else {

                    if ($amount >= $config->diamonds_topup && $config->diamonds_topup > 0 && $config->diamonds_amount > 0) {
                        $diamond = $config->diamonds_amount;

                        $this->memberDiamondLogRepository->create([
                            'diamond_type' => 'D',
                            'diamond_amount' => $diamond,
                            'diamond_before' => $member->diamond,
                            'diamond_balance' => ($member->diamond + $diamond),
                            'member_code' => $member_code,
                            'remark' => 'ได้รับเพชร จากการเติมเงิน '.$amount.' บาท ประเภทนับเป็นบิล เติมยอดมากกว่าหรือเท่ากับ '.$config->diamonds_topup.' ได้รับ '.$diamond.' เม็ด',
                            'emp_code' => $data['emp_topup'],
                            'ip' => $ip,
                            'user_create' => 'System Auto',
                            'user_update' => 'System Auto',
                        ]);

                    }

                }

            }

            $payment->user_id = $user_name;
            $payment->status = 1;
            $payment->before_credit = $member->balance;
            $payment->after_credit = ($member->balance + $total);
            $payment->pro_id = $pro_code;
            $payment->amount = $amount;
            $payment->pro_amount = $bonus;
            $payment->score = $total;
            $payment->date_topup = $datenow;
            $payment->date_approve = $datenow;
            $payment->autocheck = 'Y';
            $payment->remark_admin = $payment->remark_admin.' (เติมแล้ว)';
            $payment->topup_by = 'System Auto';
            $payment->ip_topup = $ip;
            $payment->save();

            $bills = app('Gametech\Payment\Repositories\BillRepository')->create([
                'complete' => 'Y',
                'enable' => 'Y',
                'refer_code' => $data['code'],
                'refer_table' => 'bank_payment',
                'ref_id' => '',
                'credit_before' => $member->balance,
                'credit_after' => ($member->balance + $total),
                'member_code' => $member_code,
                'game_code' => $game_code,
                'gameuser_code' => $user_code,
                'pro_code' => $pro_code,
                'pro_name' => $pro_name,
                'method' => 'TOPUP',
                'transfer_type' => 1,
                'amount' => $amount,
                'balance_before' => $member->balance,
                'balance_after' => ($member->balance + $total),
                'credit' => $amount,
                'credit_bonus' => $bonus,
                'credit_balance' => $total,
                'amount_request' => ($credit_before + ($total * $turnpro)),
                'amount_limit' => ($credit_before + (($amount + $bonus) * $withdraw_limit_rate)),
                'ip' => $ip,
                'user_create' => $member['name'],
                'user_update' => $member['name'],
            ]);

            if ($pro_code > 0) {

                $this->memberPromotionLogRepository->create([
                    'date_start' => now()->toDateString(),
                    'bill_code' => $bills->code,
                    'member_code' => $member_code,
                    'game_code' => $game_code,
                    'game_name' => $game_name,
                    'gameuser_code' => $user_code,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'turnpro' => $turnpro,
                    'balance' => ($credit_before - $amount),
                    'amount' => $amount,
                    'bonus' => $bonus,
                    'amount_balance' => ($total * $turnpro),
                    'total_amount_balance' => (($credit_before - $amount) + ($total * $turnpro)),
                    'withdraw_limit' => $withdraw_limit,
                    'withdraw_limit_rate' => 0,
                    'complete' => 'N',
                    'enable' => 'Y',
                    'user_create' => $member['name'],
                    'user_update' => $member['name'],
                ]);

                $billcode = $bills->code;

                $game_user->balance = ($member->balance + $total);
                $game_user->pro_code = $pro_code;
                $game_user->bill_code = $billcode;
                $game_user->turnpro = $turnpro;
                $game_user->amount = $amount;
                $game_user->bonus = $bonus;
                $game_user->amount_balance += ($credit_before + ($total * $turnpro));
                $game_user->withdraw_limit = $withdraw_limit;
                $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                $game_user->withdraw_limit_amount += ($credit_before + (($amount + $bonus) * $withdraw_limit_rate));
                $game_user->save();

            } else {

                if ($game_user->amount_balance > 0 || $game_user->pro_code > 0) {
                    if ($member->balance > $config->pro_reset) {
                        $game_user->amount_balance += ($total * $game_user->turnpro);
                        $game_user->withdraw_limit_amount += ($total * $game_user->withdraw_limit_rate);
                        $game_user->save();
                    } else {
                        $game_user->balance = ($member->balance + $total);
                        $game_user->pro_code = 0;
                        $game_user->bill_code = 0;
                        $game_user->turnpro = 0;
                        $game_user->amount = 0;
                        $game_user->bonus = 0;
                        $game_user->amount_balance = 0;
                        $game_user->withdraw_limit = 0;
                        $game_user->withdraw_limit_rate = 0;
                        $game_user->withdraw_limit_amount = 0;
                        $game_user->save();
                    }
                }

            }

            $bill->amount_balance = $game_user->amount_balance;
            $bill->withdraw_limit = $game_user->withdraw_limit;
            $bill->withdraw_limit_amount = $game_user->withdraw_limit_amount;
            $bill->save();

            $walletBalanceBefore = (float) $member->balance;
            $walletBalanceAfter = $walletBalanceBefore + (float) $total;

            $member->sum_deposit += $amount;
            $member->status_pro = $status_pro;
            $member->point_deposit += $point;
            $member->diamond += $diamond;
            $member->balance = $walletBalanceAfter;
            $member->count_deposit += $count_deposit;
            $member->save();

            $this->recordWalletDepositTransaction(
                (int) $member->code,
                (string) $data['code'],
                (float) $total,
                $walletBalanceBefore,
                $walletBalanceAfter,
                (string) $data['account_code'],
                isset($bills->code) ? (string) $bills->code : null
            );

            DB::commit();

        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'พบปัญหาใน Transaction');
            DB::rollBack();
            ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'Rollback Transaction');

            $this->allLogRepository->where('bank_payment_id', $data['code'])->delete();
            report($e);

            return false;

        }

        ActivityLoggerUser::activity('Seamless Topup ID : '.$data['code'], 'เติมเงินสำเร็จให้กับ User : '.$member->user_name);
        Notification::send($member, new RealTimeNotification(Lang::get('app.topup.complete').$total));

        $account = $payment->bank_account;

        $sumToday = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()->complete()->where('account_code', $payment->account_code)->whereDate('date_topup', $today)->sum('value');
        $account->update(['sum_deposit' => $sumToday]);

        if ($account->sum_limit > 0) {
            if ($sumToday >= $account->sum_limit) {
                $account->update(['display_wallet' => 'N']);
                $alt = BankAccount::where('banks', $account->banks)
                    ->where('bank_type', 1)
                    ->where('display_wallet', 'N')
                    ->where('status_auto', 'Y')
                    ->where('enable', 'Y')
                    ->where('sum_deposit', 0)
                    ->orderBy('sort', 'asc')
                    ->first();

                if ($alt) {
                    $alt->update(['display_wallet' => 'Y']);
                }
            }
        }

        return true;

    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return \Gametech\Payment\Models\BankPayment::class;

    }

    /**
     * อ่าน config ครั้งเดียวต่อ request เพื่อหลีกเลี่ยง query ซ้ำ
     */
    private function getCoreConfig()
    {
        if (app()->bound('request')) {
            $request = app('request');
            $cacheKey = '_bank_payment_repo.core_config';

            if ($request->attributes->has($cacheKey)) {
                return $request->attributes->get($cacheKey);
            }

            $config = core()->getConfigData();
            $request->attributes->set($cacheKey, $config);

            return $config;
        }

        return core()->getConfigData();
    }

    private function recordWalletDepositTransaction(
        int $memberId,
        string $paymentCode,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $bankAccountCode,
        ?string $billCode = null
    ): void {
        if ($amount <= 0 || ! Schema::hasTable('wallet_transactions')) {
            return;
        }

        $exists = DB::table('wallet_transactions')
            ->where('member_id', $memberId)
            ->where('direction', 'CREDIT')
            ->where('ref_type', 'DEPOSIT')
            ->where('ref_id', (int) $paymentCode)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('wallet_transactions')->insert([
            'member_id' => $memberId,
            'scope' => 'MEMBER',
            'game_user_id' => null,
            'direction' => 'CREDIT',
            'amount' => number_format($amount, 2, '.', ''),
            'balance_before' => number_format($balanceBefore, 2, '.', ''),
            'balance_after' => number_format($balanceAfter, 2, '.', ''),
            'ref_type' => 'DEPOSIT',
            'ref_id' => (int) $paymentCode,
            'ref_code' => $paymentCode,
            'group_code' => 'DEPOSIT_' . $paymentCode,
            'related_txn_id' => null,
            'status' => 'SUCCESS',
            'description' => 'Auto topup from bank payment #' . $paymentCode,
            'meta' => json_encode([
                'source' => 'BankPaymentRepository::refillPaymentSeamless',
                'bank_account_code' => $bankAccountCode,
                'bill_code' => $billCode,
            ], JSON_UNESCAPED_UNICODE),
            'created_by_type' => 'system',
            'created_by_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
