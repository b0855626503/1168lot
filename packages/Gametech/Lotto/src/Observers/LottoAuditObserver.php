<?php

namespace Gametech\Lotto\Observers;

use Gametech\Core\Models\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class LottoAuditObserver
{
    public function created(Model $model): void
    {
        $this->writeLog('ADD', $model, $model->toArray());
    }

    public function updated(Model $model): void
    {
        $changes = $this->filterNoiseColumns((array) $model->getChanges());
        if ($changes === []) {
            return;
        }

        $this->writeLog('EDIT', $model, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->writeLog('DEL', $model, []);
    }

    /**
     * @param array<string, mixed> $itemPayload
     */
    private function writeLog(string $mode, Model $model, array $itemPayload): void
    {
        [$empCode, $userName] = $this->resolveActor();

        $log = new Log();
        $log->emp_code = $empCode;
        $log->mode = $mode;
        $log->menu = (string) $model->getTable();
        $log->record = (string) $model->getKey();
        $log->item_before = json_encode($model->getOriginal(), JSON_UNESCAPED_UNICODE);
        $log->item = json_encode($itemPayload, JSON_UNESCAPED_UNICODE);
        $log->ip = Request::ip();
        $log->user_create = $userName;
        $log->save();
    }

    /**
     * @return array{0:int,1:string}
     */
    private function resolveActor(): array
    {
        $admin = Auth::guard('admin')->user();
        if ($admin) {
            return [(int) ($admin->code ?? $admin->id ?? 0), (string) ($admin->user_name ?? 'SYSTEM')];
        }

        $customer = Auth::guard('customer')->user();
        if ($customer) {
            return [(int) ($customer->code ?? $customer->id ?? 0), (string) ($customer->user_name ?? 'SYSTEM')];
        }

        $user = Auth::user();
        if ($user) {
            return [(int) ($user->code ?? $user->id ?? 0), (string) ($user->user_name ?? 'SYSTEM')];
        }

        return [0, 'SYSTEM'];
    }

    /**
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    private function filterNoiseColumns(array $changes): array
    {
        unset($changes['updated_at'], $changes['date_update']);

        return $changes;
    }
}

