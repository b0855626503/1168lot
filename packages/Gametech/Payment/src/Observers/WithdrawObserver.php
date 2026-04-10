<?php

namespace Gametech\Payment\Observers;

use App\Events\RealtimeMemberActivityUpdated;
use App\Events\RealTimeNewMessage;
use App\Events\SumNewWithdraw;
use App\Helpers\TelegramBot;
use App\Services\Dashboard\DashboardSummarySyncService;
use Carbon\Carbon;
use Gametech\Core\Models\Log;
use Gametech\Payment\Models\Withdraw as EventData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class WithdrawObserver
{
    private const DEFAULT_ENABLE_REALTIME_MESSAGE = true;
    private const REALTIME_MESSAGE_FLAG_ENV = 'PAYMENT_WITHDRAW_OBSERVER_REALTIME_MESSAGE';
    private const DEFAULT_ENABLE_TELEGRAM_MESSAGE = false;
    private const TELEGRAM_MESSAGE_FLAG_ENV = 'PAYMENT_WITHDRAW_OBSERVER_TELEGRAM_MESSAGE';
    private const DASHBOARD_CACHE_VERSION_KEY = 'dashboard:summary:version';

    public function created(EventData $data): void
    {
        $this->writeLog('customer', 'ADD', $data, true);

        DB::afterCommit(function () use ($data) {
            $this->touchDashboardCacheVersion();
            $this->broadcastCount('created', $data->code);
            $this->dispatchDashboardSync($data);

            if ($this->shouldSendTelegramMessage()) {
                $this->sendWithdrawTelegramMessage($data);
            }

            if ($this->shouldBroadcastRealtimeMessage()) {
                $this->broadcastRealtimeMessage($data);
            }
        });
    }

    public function updated(EventData $data): void
    {
        $this->writeLog('admin', 'EDIT', $data, false);

        $shouldBroadcastCount = $this->shouldBroadcastCountOnUpdate($data);
        $shouldSyncDashboard = $this->shouldSyncDashboardOnUpdate($data);

        if (! $shouldBroadcastCount && ! $shouldSyncDashboard) {
            return;
        }

        DB::afterCommit(function () use ($data, $shouldBroadcastCount, $shouldSyncDashboard) {
            $this->touchDashboardCacheVersion();
            if ($shouldBroadcastCount) {
                $this->broadcastCount('updated', $data->code);
            }

            $this->broadcastMemberWalletActivity($data);

            if ($shouldSyncDashboard) {
                $this->dispatchDashboardSync($data);
            }
        });
    }

    public function deleted(EventData $data): void
    {
        $this->writeLog('admin', 'DEL', $data, false);

        DB::afterCommit(function () use ($data) {
            $this->touchDashboardCacheVersion();
            $this->broadcastCount('deleted', $data->code);
            $this->dispatchDashboardSync($data);
        });
    }

    private function shouldBroadcastCountOnUpdate(EventData $data): bool
    {
        return $data->wasChanged('status') || $data->wasChanged('enable') || $data->wasChanged('date_approve') || $data->wasChanged('emp_approve') || $data->wasChanged('status_withdraw');
    }

    private function shouldSyncDashboardOnUpdate(EventData $data): bool
    {
        foreach (['status', 'enable', 'amount', 'emp_approve', 'date_approve', 'account_code', 'status_withdraw'] as $field) {
            if ($data->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    private function writeLog(string $guard, string $mode, EventData $data, bool $created = false): void
    {
        $user = Auth::guard($guard)->user();
        if (! $user) {
            return;
        }

        $log = new Log;
        $log->emp_code = $user->code;
        $log->mode = $mode;
        $log->menu = 'withdraws';
        $log->record = $data->code;
        $log->item_before = json_encode($data->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item = json_encode($created ? $data->toArray() : $data->getChanges(), JSON_UNESCAPED_UNICODE);
        $log->ip = Request::ip();
        $log->user_create = $user->user_name;
        $log->save();
    }

    private function broadcastCount(string $action, string $code): void
    {
        $count = app('Gametech\Payment\Repositories\WithdrawRepository')
            ->where('status', 0)
            ->where('enable', 'Y')
            ->count();

        broadcast(new SumNewWithdraw($count, $action, $code));
    }

    private function shouldBroadcastRealtimeMessage(): bool
    {
        return (bool) filter_var(
            (string) env(self::REALTIME_MESSAGE_FLAG_ENV, self::DEFAULT_ENABLE_REALTIME_MESSAGE),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    private function shouldSendTelegramMessage(): bool
    {
        return (bool) filter_var(
            (string) env(self::TELEGRAM_MESSAGE_FLAG_ENV, self::DEFAULT_ENABLE_TELEGRAM_MESSAGE),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    private function sendWithdrawTelegramMessage(EventData $data): void
    {
        try {
            $paymentLast = $data->payment_last;
            if ($paymentLast) {
                $lastDate = $this->formatDateTime($paymentLast->date_create ?? ($paymentLast['date_create'] ?? null));
                $lastBank = $this->escapeTelegram($paymentLast->bank ?? ($paymentLast['bank'] ?? '-'));
                $lastAmount = $this->escapeTelegram($paymentLast->value ?? ($paymentLast['value'] ?? '-'));
                $refText = "ฝากล่่าสุด {$lastDate} - {$lastBank} [ {$lastAmount} ]";
            } else {
                $refText = 'ไม่พบข้อมูลการเติมก่อนหน้า';
            }

            $member = $data->member;
            $memberBank = $member?->bank;
            $datetimeText = $this->escapeTelegram($this->formatDateTime($data->date_create ?? null));
            $memberUser = $this->escapeTelegram($data->member_user);
            $amount = $this->escapeTelegram($data->amount);
            $memberName = $this->escapeTelegram($member?->name ?? '-');
            $bankName = $this->escapeTelegram($memberBank?->name_th ?? '-');
            $accNoDisplay = $this->escapeTelegram($member?->acc_no ?? '-');
            $countDeposit = $this->escapeTelegram($member?->count_deposit ?? '0');
            $balance = $this->escapeTelegram($member?->balance ?? '0');
            $refText = $this->escapeTelegram($refText);

            $message = <<<HTML
<b>แจ้งเตือนการทำรายการ</b>
———————
<b>ประเภท:</b> แจ้งถอนเงิน<br>
<b>ชื่อผู้ใช้:</b> {$memberUser}<br>
<b>จำนวนเงิน:</b> {$amount}<br>
<b>เวลา:</b> {$datetimeText} (GMT+7)<br>
<b>อ้างอิง:</b> {$refText}<br>
<b>รายละเอียดเพิ่มเติม:</b><br>
<b>ชื่อ:</b> {$memberName}<br>
<b>ธนาคาร:</b> {$bankName}<br>
<b>เลขบัญชี:</b> <code>{$accNoDisplay}</code><br>
<b>ฝากทั้งหมด (ครั้ง):</b> {$countDeposit}<br>
<b>ยอดเงินคงเหลือ:</b> {$balance}<br>
HTML;

            TelegramBot::Send('notify/send', $message, $this->telegramNotifyPayload('/withdraw'));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function telegramNotifyPayload(string $path = '/withdraw'): array
    {
        $baseUrl = 'https://'.config('app.admin_url').'.'.(is_null(config('app.admin_domain_url')) ? config('app.domain_url') : config('app.admin_domain_url'));

        return [
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'เข้าระบบ '.config('app.name'),
                            'url' => $baseUrl.$path,
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    private function formatDateTime($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return $this->escapeTelegram($value);
        }
    }

    private function escapeTelegram($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function broadcastRealtimeMessage(EventData $data): void
    {
        broadcast(new RealTimeNewMessage(
            'มีรายการแจ้งถอนเข้ามาใหม่ จาก ID '.$data->member_user.' โปรดตรวจสอบ',
            [
                'ui' => 'toast',
                'as' => 'RealTime.Message.All',
                'sound' => 'withdraw',
                'toast' => [
                    'className' => 'gt-toast gt-toast-withdraw',
                    'duration' => 30000,
                    'gravity' => 'top',
                    'position' => 'right',
                    'avatar' => '/assets/admin/icons/withdraw.webp',
                ],
            ]
        ));
    }

    private function broadcastMemberWalletActivity(EventData $data): void
    {
        $memberCode = (int) ($data->member_code ?? 0);
        if ($memberCode <= 0) {
            return;
        }

        $oldStatus = (int) $data->getOriginal('status');
        $newStatus = (int) $data->status;
        $oldEnable = (string) $data->getOriginal('enable');
        $newEnable = (string) $data->enable;

        $method = null;
        $event = null;

        if ($newStatus === 1 && $newEnable === 'Y') {
            $method = 'withdraw';
            $event = 'wallet.withdraw_approved';
        } elseif (($oldStatus === 1 && $newStatus !== 1) || ($oldEnable === 'Y' && $newEnable !== 'Y')) {
            $method = 'rollback';
            $event = 'wallet.rollback_applied';
        } elseif ($newStatus === 2) {
            $method = 'withdraw';
            $event = 'wallet.withdraw_rejected';
        }

        if (! $method || ! $event) {
            return;
        }

        $member = app('Gametech\Member\Repositories\MemberRepository')->find($memberCode);
        $balance = (float) ($member->balance ?? 0);
        $amount = (float) ($data->amount ?? 0);
        $message = match ($event) {
            'wallet.withdraw_approved' => 'ถอนเงินสำเร็จ -'.number_format($amount, 2, '.', ',').' บาท',
            'wallet.withdraw_rejected' => 'รายการถอนเงิน '.number_format($amount, 2, '.', ',').' บาทถูกปฏิเสธ',
            'wallet.rollback_applied' => 'ระบบคืนยอดสำเร็จ +'.number_format($amount, 2, '.', ',').' บาท',
            default => 'ยอดเงินของคุณถูกอัปเดต',
        };

        broadcast(new RealtimeMemberActivityUpdated(
            $memberCode,
            $method,
            $event,
            [
                'amount' => $amount,
                'balance' => $balance,
                'reference_code' => (int) $data->code,
                'reason' => $event,
            ],
            $message
        ));
    }

    private function dispatchDashboardSync(EventData $data): void
    {
        try {
            app(DashboardSummarySyncService::class)->dispatchForModelChange('withdraw', $data, ['withdraw', 'net']);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function touchDashboardCacheVersion(): void
    {
        try {
            Cache::forever(self::DASHBOARD_CACHE_VERSION_KEY, sprintf('%.6f', microtime(true)));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
