<?php

namespace Gametech\Payment\Observers;

use App\Events\RealTimeNewMessage;
use App\Events\SumNewPayment;
use App\Services\Dashboard\DashboardSummarySyncService;
use Gametech\Auto\Jobs\CheckPayments as CheckPaymentsJob;
use Gametech\Auto\Jobs\TopupPayments as TopupPaymentsJob;
use Gametech\Core\Models\Log;
use Gametech\Payment\Models\BankAccount;
use Gametech\Payment\Models\BankPayment as EventData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class BankPaymentObserver
{
    private const DEFAULT_ENABLE_REALTIME_MESSAGE = true;
    private const REALTIME_MESSAGE_FLAG_ENV = 'PAYMENT_DEPOSIT_OBSERVER_REALTIME_MESSAGE';

    public function created(EventData $data): void
    {
        $bankShortcode = $this->handleBankJobs($data);

        DB::afterCommit(function () use ($data, $bankShortcode) {
            $this->broadcastCount('created', $data->code);
            $this->dispatchDashboardSync($data);

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

        if (!$shouldSyncDashboard && !$shouldRecountPending) {
            return;
        }

        DB::afterCommit(function () use ($data, $shouldRecountPending, $shouldSyncDashboard) {
            if ($shouldRecountPending) {
                $this->broadcastCount('updated', $data->code);
            }

            if ($shouldSyncDashboard) {
                $this->dispatchDashboardSync($data);
            }
        });
    }


    public function deleted(EventData $data): void
    {
        $this->writeAdminLog('DEL', $data);

        DB::afterCommit(function () use ($data) {
            $this->broadcastCount('deleted', $data->code);
            $this->dispatchDashboardSync($data);
        });
    }

    private function handleBankJobs(EventData $data): ?string
    {
        $bank = BankAccount::query()
            ->where('enable', 'Y')
            ->where('bank_type', 1)
            ->where('code', $data->account_code)
            ->first();

        if (!$bank) {
            return null;
        }

        $shouldTopup =
            ($bank->status_topup === 'Y' && (int) $data->member_topup > 0)
            || ($bank->status_topup !== 'Y' && (int) $data->member_topup > 0 && (int) $data->emp_topup > 0);

        if ($shouldTopup) {
            TopupPaymentsJob::dispatch($data->code)
                ->delay(now()->addSeconds(2))
                ->onQueue('topup');
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
        $memberJustSet = $data->wasChanged('member_topup')
            && (int) $data->getOriginal('member_topup') === 0
            && $memberNow > 0;

        $empJustSet = $data->wasChanged('emp_topup')
            && (int) $data->getOriginal('emp_topup') === 0
            && (int) $data->emp_topup > 0;

        if ($memberNow > 0 && ($memberJustSet || $empJustSet) && $data->autocheck === 'W') {
            TopupPaymentsJob::dispatch($data->code)
                ->delay(now()->addSeconds(2))
                ->onQueue('topup');
        }
    }

    private function shouldRecountPendingOnUpdate(EventData $data): bool
    {
        return $data->wasChanged('status')
            || $data->wasChanged('enable')
            || $data->wasChanged('date_create');
    }

    private function shouldSyncDashboardOnUpdate(EventData $data): bool
    {
        foreach (['status', 'enable', 'value', 'member_topup', 'date_create'] as $field) {
            if ($data->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    private function writeAdminLog(string $mode, EventData $data): void
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
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

    private function broadcastCount(string $action, string $code): void
    {
        $count = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->where('status', 0)
            ->where('enable', 'Y')
            ->whereDate('date_create', today())
            ->count();

        broadcast(new SumNewPayment($count, $action, $code));
    }

    private function shouldBroadcastRealtimeMessage(): bool
    {
        return (bool) filter_var(
            (string) env(self::REALTIME_MESSAGE_FLAG_ENV, self::DEFAULT_ENABLE_REALTIME_MESSAGE),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    private function broadcastRealtimeMessage(EventData $data, ?string $bankShortcode): void
    {
        $bankLabel = $bankShortcode ? strtoupper($bankShortcode) : 'BANK';

        broadcast(new RealTimeNewMessage(
            $bankLabel . ' มีรายการฝากเข้ามาใหม่แล้วนะ ' . $data->value . ' บาท',
            [
                'ui' => 'toast',
                'as' => 'RealTime.Message.All',
                'toast' => [
                    'className' => 'gt-toast gt-toast-deposit',
                    'duration' => 30000,
                    'gravity' => 'top',
                    'position' => 'right',
                    'avatar' => '/assets/admin/icons/deposit.webp?v=1',
                ],
            ]
        ));
    }

    private function dispatchDashboardSync(EventData $data): void
    {
        try {
            app(DashboardSummarySyncService::class)->dispatchForModelChange('deposit', $data, ['deposit', 'net', 'conversion', 'funnel']);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
