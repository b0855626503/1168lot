<?php

namespace Gametech\Payment\Observers;

use App\Events\MemberBalanceUpdated;
use App\Events\RealtimeMemberActivityUpdated;
use App\Events\RealTimeNewMessage;
use App\Events\SumNewPayment;
use App\Helpers\TelegramBot;
use App\Services\Dashboard\DashboardSummarySyncService;
use Gametech\Auto\Jobs\CheckPayments as CheckPaymentsJob;
use Gametech\Auto\Jobs\TopupPayments as TopupPaymentsJob;
use Gametech\Core\Models\Log;
use Gametech\Payment\Models\BankAccount;
use Gametech\Payment\Models\BankPayment as EventData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Throwable;

class BankPaymentObserver
{
    private const DEFAULT_ENABLE_REALTIME_MESSAGE = true;

    private const REALTIME_MESSAGE_FLAG_ENV = 'PAYMENT_DEPOSIT_OBSERVER_REALTIME_MESSAGE';
    private const DEFAULT_ENABLE_TELEGRAM_MESSAGE = false;
    private const TELEGRAM_MESSAGE_FLAG_ENV = 'PAYMENT_DEPOSIT_OBSERVER_TELEGRAM_MESSAGE';

    private const DASHBOARD_CACHE_VERSION_KEY = 'dashboard:summary:version';
    private const PENDING_DEPOSIT_CACHE_KEY_PREFIX = 'payment:pending-deposit-count:';

    public function created(EventData $data): void
    {
        $bankShortcode = $this->handleBankJobs($data);

        DB::afterCommit(function () use ($data, $bankShortcode) {
            $this->touchDashboardCacheVersion();
            $this->broadcastCount('created', $data->code, $this->pendingDepositCountDeltaForCreate($data));
            $this->dispatchDashboardSync($data);

            if ($this->shouldSendTelegramMessage() && (int) $data->member_topup > 0) {
                $this->sendDepositTelegramMessage($data);
            }

            if ($this->shouldBroadcastRealtimeMessage()) {
                $this->broadcastRealtimeMessage($data, $bankShortcode);
            }
        });
    }

    public function updated(EventData $data): void
    {
        $this->dispatchTopupWhenReady($data);
        $this->writeAdminLog('EDIT', $data);

        $shouldSyncDashboard = $this->shouldSyncDashboardOnUpdate($data);
        $shouldRecountPending = $this->shouldRecountPendingOnUpdate($data);
        $shouldSendTelegramMessage = $data->wasChanged('member_topup') && (int) $data->member_topup > 0;

        if (! $shouldSyncDashboard && ! $shouldRecountPending) {
            return;
        }

        DB::afterCommit(function () use ($data, $shouldRecountPending, $shouldSyncDashboard, $shouldSendTelegramMessage) {
            $this->touchDashboardCacheVersion();
            if ($shouldRecountPending) {
                $this->broadcastCount('updated', $data->code, $this->pendingDepositCountDeltaForUpdate($data));
            }

            $this->broadcastMemberBalanceUpdated($data);

            if ($shouldSyncDashboard) {
                $this->dispatchDashboardSync($data);
            }

            if ($shouldSendTelegramMessage && $this->shouldSendTelegramMessage()) {
                $this->sendDepositTelegramMessage($data);
            }
        });
    }

    public function deleted(EventData $data): void
    {
        $this->writeAdminLog('DEL', $data);

        DB::afterCommit(function () use ($data) {
            $this->touchDashboardCacheVersion();
            $this->broadcastCount('deleted', $data->code, $this->pendingDepositCountDeltaForDelete($data));
            $this->dispatchDashboardSync($data);
        });
    }

    private function handleBankJobs(EventData $data): ?string
    {
        $bank = BankAccount::query()->where('enable', 'Y')->where('bank_type', 1)->where('code', $data->account_code)->first();

        if (! $bank) {
            return null;
        }

        $shouldTopup = ($bank->status_topup === 'Y' && (int) $data->member_topup > 0) || ($bank->status_topup !== 'Y' && (int) $data->member_topup > 0 && (int) $data->emp_topup > 0);

        if ($shouldTopup) {
            TopupPaymentsJob::dispatch($data->code)->delay(now()->addSeconds(2))->onQueue('topup');
        }

        $short = $bank->bank?->shortcode;
        if ($short && (int) $data->member_topup === 0 && $data->autocheck === 'N') {
            CheckPaymentsJob::dispatch(strtolower($short), $data)->onQueue('topup');
        }

        return $short;
    }

    private function dispatchTopupWhenReady(EventData $data): void
    {
        $memberNow = (int) $data->member_topup;
        $memberJustSet = $data->wasChanged('member_topup') && (int) $data->getOriginal('member_topup') === 0 && $memberNow > 0;

        $empJustSet = $data->wasChanged('emp_topup') && (int) $data->getOriginal('emp_topup') === 0 && (int) $data->emp_topup > 0;

        if ($memberNow > 0 && ($memberJustSet || $empJustSet) && $data->autocheck === 'W') {
            TopupPaymentsJob::dispatch($data->code)->delay(now()->addSeconds(2))->onQueue('topup');
        }
    }

    private function shouldRecountPendingOnUpdate(EventData $data): bool
    {
        return $data->wasChanged('status') || $data->wasChanged('enable');
    }

    private function shouldSyncDashboardOnUpdate(EventData $data): bool
    {
        foreach (['status', 'enable', 'member_topup', 'date_approve', 'emp_topup'] as $field) {
            if ($data->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    private function writeAdminLog(string $mode, EventData $data): void
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            return;
        }

        $log = new Log;
        $log->emp_code = $admin->code;
        $log->mode = $mode;
        $log->menu = 'bank_payment';
        $log->record = $data->code;
        $log->item_before = json_encode($data->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item = json_encode($data->getChanges(), JSON_UNESCAPED_UNICODE);
        $log->ip = Request::ip();
        $log->user_create = $admin->user_name;
        $log->save();
    }

    private function broadcastCount(string $action, string $code, ?int $delta = null): void
    {
        $count = $this->resolvePendingDepositCount($delta);

        broadcast(new SumNewPayment($count, $action, $code));
    }

    private function resolvePendingDepositCount(?int $delta = null): int
    {
        $today = today();
        $cacheKey = $this->pendingDepositCountCacheKey($today);
        $cachedCount = Cache::get($cacheKey);

        if ($delta !== null && is_numeric($cachedCount)) {
            $nextCount = max(0, (int) $cachedCount + $delta);
            Cache::put($cacheKey, $nextCount, now()->addMinutes(5));

            return $nextCount;
        }

        $count = $this->queryPendingDepositCount($today);
        Cache::put($cacheKey, $count, now()->addMinutes(5));

        return $count;
    }

    private function queryPendingDepositCount(Carbon $date): int
    {
        [$rangeStart, $rangeEndExclusive] = $this->resolveDayRange($date);

        return (int) app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->where('status', 0)
            ->where('enable', 'Y')
            ->where('date_create', '>=', $rangeStart)
            ->where('date_create', '<', $rangeEndExclusive)
            ->count();
    }

    private function pendingDepositCountDeltaForCreate(EventData $data): int
    {
        return $this->matchesPendingDepositSnapshot(
            (int) ($data->status ?? 0),
            (string) ($data->enable ?? ''),
            $data->date_create
        ) ? 1 : 0;
    }

    private function pendingDepositCountDeltaForDelete(EventData $data): int
    {
        return $this->matchesPendingDepositSnapshot(
            (int) ($data->status ?? 0),
            (string) ($data->enable ?? ''),
            $data->date_create
        ) ? -1 : 0;
    }

    private function pendingDepositCountDeltaForUpdate(EventData $data): int
    {
        $wasPending = $this->matchesPendingDepositSnapshot(
            (int) ($data->getOriginal('status') ?? 0),
            (string) ($data->getOriginal('enable') ?? ''),
            $data->getOriginal('date_create')
        );

        $isPending = $this->matchesPendingDepositSnapshot(
            (int) ($data->status ?? 0),
            (string) ($data->enable ?? ''),
            $data->date_create
        );

        return ((int) $isPending) - ((int) $wasPending);
    }

    private function matchesPendingDepositSnapshot(int $status, string $enable, mixed $dateCreate): bool
    {
        if ($status !== 0 || $enable !== 'Y' || empty($dateCreate)) {
            return false;
        }

        try {
            $date = $dateCreate instanceof Carbon ? $dateCreate : Carbon::parse((string) $dateCreate);
        } catch (Throwable) {
            return false;
        }

        [$rangeStart, $rangeEndExclusive] = $this->resolveDayRange(today());

        return $date->greaterThanOrEqualTo($rangeStart) && $date->lessThan($rangeEndExclusive);
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    private function resolveDayRange(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $endExclusive = $start->copy()->addDay();

        return [$start, $endExclusive];
    }

    private function pendingDepositCountCacheKey(Carbon $date): string
    {
        return self::PENDING_DEPOSIT_CACHE_KEY_PREFIX.$date->toDateString();
    }

    private function broadcastMemberBalanceUpdated(EventData $data): void
    {
        $memberCode = (int) ($data->member_topup ?? 0);
        if ($memberCode <= 0) {
            return;
        }

        if (! $data->wasChanged('status') || (int) $data->status !== 1 || (string) $data->enable !== 'Y') {
            return;
        }

        try {
            $member = app('Gametech\Member\Repositories\MemberRepository')->find($memberCode);
            if (! $member) {
                return;
            }

            $amount = (float) ($data->value ?? 0);
            $balance = (float) ($member->balance ?? 0);
            $message = 'เติมเงินสำเร็จ +'.number_format($amount, 2, '.', ',').' บาท';

            broadcast(new MemberBalanceUpdated(
                $memberCode,
                $balance,
                $amount,
                'deposit_approved',
                (int) $data->code,
                $message
            ));

            broadcast(new RealtimeMemberActivityUpdated(
                $memberCode,
                'deposit',
                'wallet.deposit_approved',
                [
                    'amount' => $amount,
                    'balance' => $balance,
                    'reference_code' => (int) $data->code,
                    'reason' => 'deposit_approved',
                ],
                $message
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function shouldBroadcastRealtimeMessage(): bool
    {
        return (bool) filter_var((string) env(self::REALTIME_MESSAGE_FLAG_ENV, self::DEFAULT_ENABLE_REALTIME_MESSAGE), FILTER_VALIDATE_BOOLEAN);
    }

    private function shouldSendTelegramMessage(): bool
    {
        return (bool) filter_var((string) env(self::TELEGRAM_MESSAGE_FLAG_ENV, self::DEFAULT_ENABLE_TELEGRAM_MESSAGE), FILTER_VALIDATE_BOOLEAN);
    }

    private function sendDepositTelegramMessage(EventData $data): void
    {
        try {
            $username = $this->escapeTelegram(optional($data->member)->user_name ?? '-');
            $amount = $this->escapeTelegram($data->value);
            $bankTime = $this->escapeTelegram($data->bank_time);
            $detail = $this->escapeTelegram($data->detail);
            $createBy = $this->escapeTelegram($data->create_by);
            $bank = $this->escapeTelegram($data->bank);
            $channel = (string) ($data->channel ?? '');
            $ref = $bank.' - '.($channel === 'MANUAL' ? $this->escapeTelegram($channel) : '');

            $message = <<<HTML
<b>แจ้งเตือนการทำรายการ</b>
———————
<b>ประเภท:</b> ฝากเงิน<br>
<b>ชื่อผู้ใช้:</b> <code>{$username}</code><br>
<b>จำนวนเงิน:</b> {$amount}<br>
<b>เวลา:</b> {$bankTime} (GMT+7)<br>
<b>อ้างอิง:</b> {$ref}<br>
<b>รายละเอียดเพิ่มเติม:</b> {$detail} โดย {$createBy}
HTML;

            TelegramBot::Send('notify/send', $message, $this->telegramNotifyPayload('/withdraw'));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function telegramNotifyPayload(string $path = '/bank_in'): array
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

    private function escapeTelegram($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function broadcastRealtimeMessage(EventData $data, ?string $bankShortcode): void
    {
        $bankLabel = $bankShortcode ? strtoupper($bankShortcode) : 'BANK';

        broadcast(new RealTimeNewMessage($bankLabel.' มีรายการฝากเข้ามาใหม่แล้วนะ '.$data->value.' บาท', ['ui' => 'toast', 'as' => 'RealTime.Message.All', 'toast' => ['className' => 'gt-toast gt-toast-deposit', 'duration' => 30000, 'gravity' => 'top', 'position' => 'right', 'avatar' => '/assets/admin/icons/deposit.webp?v=1']]));
    }

    private function dispatchDashboardSync(EventData $data): void
    {
        try {
            app(DashboardSummarySyncService::class)->dispatchForModelChange('deposit', $data, ['deposit', 'net', 'conversion', 'funnel']);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function touchDashboardCacheVersion(): void
    {
        try {
            Cache::forever(self::DASHBOARD_CACHE_VERSION_KEY, sprintf('%.6f', microtime(true)));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
