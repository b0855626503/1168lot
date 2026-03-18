<?php

namespace Gametech\Payment\Observers;

use App\Events\RealTimeNewMessage;
use App\Events\SumNewWithdraw;
use App\Services\Dashboard\DashboardSummarySyncService;
use Gametech\Core\Models\Log;
use Gametech\Payment\Models\Withdraw as EventData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class WithdrawObserver
{
    private const DEFAULT_ENABLE_REALTIME_MESSAGE = true;
    private const REALTIME_MESSAGE_FLAG_ENV = 'PAYMENT_WITHDRAW_OBSERVER_REALTIME_MESSAGE';

    public function created(EventData $data): void
    {
        $this->writeLog('customer', 'ADD', $data, true);

        DB::afterCommit(function () use ($data) {
            $this->broadcastCount('created', $data->code);
            $this->dispatchDashboardSync($data);

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

        if (!$shouldBroadcastCount && !$shouldSyncDashboard) {
            return;
        }

        DB::afterCommit(function () use ($data, $shouldBroadcastCount, $shouldSyncDashboard) {
            if ($shouldBroadcastCount) {
                $this->broadcastCount('updated', $data->code);
            }

            if ($shouldSyncDashboard) {
                $this->dispatchDashboardSync($data);
            }
        });
    }

    public function deleted(EventData $data): void
    {
        $this->writeLog('admin', 'DEL', $data, false);

        DB::afterCommit(function () use ($data) {
            $this->broadcastCount('deleted', $data->code);
            $this->dispatchDashboardSync($data);
        });
    }

    private function shouldBroadcastCountOnUpdate(EventData $data): bool
    {
        return $data->wasChanged('status') || $data->wasChanged('enable');
    }

    private function shouldSyncDashboardOnUpdate(EventData $data): bool
    {
        foreach (['status', 'enable', 'amount', 'member_code', 'date_create', 'date_approve'] as $field) {
            if ($data->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    private function writeLog(string $guard, string $mode, EventData $data, bool $created = false): void
    {
        $user = Auth::guard($guard)->user();
        if (!$user) {
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

    private function broadcastRealtimeMessage(EventData $data): void
    {
        broadcast(new RealTimeNewMessage(
            'มีรายการแจ้งถอนเข้ามาใหม่ จาก ID ' . $data->member_user . ' โปรดตรวจสอบ',
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

    private function dispatchDashboardSync(EventData $data): void
    {
        try {
            app(DashboardSummarySyncService::class)->dispatchForModelChange('withdraw', $data, ['withdraw', 'net']);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
