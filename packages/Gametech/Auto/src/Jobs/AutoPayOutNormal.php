<?php

namespace Gametech\Auto\Jobs;


use App\Libraries\PomPayOut;
use App\Libraries\ScbOut;
use Gametech\Payment\Models\WithdrawSeamless;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Request;


class AutoPayOutNormal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = true;
    public $uniqueFor = 60;

    public $timeout = 40;

    public $tries = 0;

    public $maxExceptions = 3;

    public $retryAfter = 0;

    protected $item;

    protected $bank;



    public function __construct($bank,$item)
    {
        $this->item = $item;
        $this->bank = $bank;

    }

    public function handle(): bool
    {
        $ip = Request::ip();
        $datenow = now()->toDateTimeString();

        $item = $this->item;
        $bank = $this->bank;

        $bank_code = $bank->bank->code;


//        $path = storage_path('logs/pompay/auto' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- AUTO DATA --', true), FILE_APPEND);
//        file_put_contents($path, print_r($item, true), FILE_APPEND);

//        file_put_contents($path, print_r('-- AUTO BANK --', true), FILE_APPEND);
//        file_put_contents($path, print_r($bank, true), FILE_APPEND);


        if ($bank_code == 2) {

//            $item = WithdrawSeamless::where('code',$item);

            $scb = new ScbOut();
            $member_bank = $scb->Banks($item->bankm_code);
            if ($member_bank != '500') {

                $item->account_code = $bank->code;
                $item->ip_admin = $ip;
                $item->user_update = 'SYSTEM';
                $item->date_approve = $datenow;
                $item->save();

//                $path = storage_path('logs/pompay/auto' . now()->format('Y_m_d') . '.log');
//                file_put_contents($path, print_r('-- AUTO --', true), FILE_APPEND);
//                file_put_contents($path, print_r($item, true), FILE_APPEND);


//                $return = $this->dispatch(
//                    (new PaymentOutSeamlessPomPay($item->code))->onQueue('topup')
//                );

                $return = PaymentOutScb::dispatchNow($item->code);

                switch ($return['success']) {
                    case 'NORMAL':
                        $item->status = 1;
                        $item->save();
                        break;

                    case 'NOMONEY':
                    case 'FAIL_AUTO':
                    $item->account_code = 0;
                    $item->status_withdraw = 'W';
                    $item->status = 0;
                    $item->emp_approve = 0;
                    $item->ip_admin = '';
                    $item->save();

                        break;

                    case 'COMPLETE':
                    case 'NOTWAIT':
                    case 'MONEY':
                        break;
                }

                if ($return['complete'] === true) {

                    $member = app('Gametech\Member\Repositories\MemberRepository')->find($item->member_code);

                    $game_user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('member_code', $item->member_code);

                    app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'balance_before' => $member->balance,
                        'balance_after' => $member->balance,
                        'credit' => 0,
                        'total' => $item->amount,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => $member->balance,
                        'credit_after' => $member->balance,
                        'pro_code' => 0,
                        'bank_code' => $item->bankm_code,
                        'auto' => 'Y',
                        'enable' => 'Y',
                        'user_create' => "System Auto",
                        'user_update' => "System Auto",
                        'refer_code' => $item->code,
                        'refer_table' => 'withdraws',
                        'remark' => 'ระบบโอนเงินออโต้แล้ว รายการที่ : ' . $item->code . ' / ไอดี : ' . $member->user_name . ' / ยอด : ' . $item->amount . ' / บัญชี : ' . $member->acc_no,
                        'kind' => 'AUTO_WDS',
                        'amount' => $item->amount,
                        'amount_balance' => $game_user->amount_balance,
                        'withdraw_limit' => $game_user->withdraw_limit,
                        'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                        'method' => 'D',
                        'member_code' => $item->member_code,
                        'user_name' => $member->user_name,
                        'emp_code' => 0,
                        'emp_name' => 'SYSTEM'
                    ]);

                } else {

                    $member = app('Gametech\Member\Repositories\MemberRepository')->find($item->member_code);

                    $game_user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('member_code', $item->member_code);

                    app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'balance_before' => $member->balance,
                        'balance_after' => $member->balance,
                        'credit' => 0,
                        'total' => $item->amount,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => $member->balance,
                        'credit_after' => $member->balance,
                        'pro_code' => 0,
                        'bank_code' => $item->bankm_code,
                        'auto' => 'Y',
                        'enable' => 'Y',
                        'user_create' => "System Auto",
                        'user_update' => "System Auto",
                        'refer_code' => $item->code,
                        'refer_table' => 'withdraws',
                        'remark' => 'ระบบไม่สามารถโอนเงินออโต้ได้ รายการที่ : ' . $item->code . ' / ไอดี ' . $member->user_name . ' ทีมงานโปรดดำเนินการเอง',
                        'kind' => 'AUTO_WDF',
                        'amount' => $item->amount,
                        'amount_balance' => $game_user->amount_balance,
                        'withdraw_limit' => $game_user->withdraw_limit,
                        'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                        'method' => 'D',
                        'member_code' => $item->member_code,
                        'user_name' => $member->user_name,
                        'emp_code' => 0,
                        'emp_name' => 'SYSTEM'
                    ]);

                }

            }
        }

        return true;
    }
}
