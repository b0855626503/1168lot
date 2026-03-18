<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\AutoTransfer;
use Gametech\Payment\Models\Bank;
use Gametech\Payment\Models\BankAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBalanceAutoTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 0;
    public $maxExceptions = 5;
    public $retryAfter = 0;

    public function __construct()
    {
    }

    public function handle(): int
    {
        try {
            $api = new AutoTransfer();
            $response = $api->getAccountBalances();

            if (!is_array($response) || !($response['success'] ?? false)) {
                Log::channel('autotransfer_api')->warning(
                    'UpdateBalanceAutoTransfer: API request failed',
                    ['response' => $response]
                );
                return 0;
            }

            $payload = $response['data'] ?? [];

            $statusCode = (string) data_get($payload, 'status.code');
            $statusType = (string) data_get($payload, 'status.type');

            if (!str_starts_with($statusCode, '200') || $statusType !== 'success') {
                Log::channel('autotransfer_api')->warning(
                    'UpdateBalanceAutoTransfer: API status not success',
                    ['status' => data_get($payload, 'status')]
                );
                return 0;
            }

            $items = collect(data_get($payload, 'data', []))
                ->filter(fn ($row) => data_get($row, 'is_disabled') === false);

            if ($items->isEmpty()) {
                return 0;
            }

            /**
             * preload bank mapping
             * ใช้ banks.shortcode -> banks.code
             * เช่น SCB / KBANK / BBL
             */
            $bankMap = Bank::query()
                ->get(['code', 'shortcode'])
                ->keyBy(fn ($b) => strtoupper($b->shortcode));

            foreach ($items as $row) {
                $bankShortcode = strtoupper((string) data_get($row, 'bank'));
                $accountNo     = (string) data_get($row, 'account_no');
                $balance       = (float) data_get($row, 'balance', 0);
                $updatedAt     = data_get($row, 'updated_at');

                if ($bankShortcode === '' || $accountNo === '') {
                    continue;
                }

                $bank = $bankMap->get($bankShortcode);
                if (!$bank) {
                    Log::channel('autotransfer_api')->notice(
                        'Unknown bank shortcode from AutoTransfer',
                        ['bank' => $bankShortcode]
                    );
                    continue;
                }

                $query = BankAccount::query()
                    ->where('bank_code', $bank->code)
                    ->where('acc_no', $accountNo)
                    ->where('enable', 'Y');

                if (!$query->exists()) {
                    Log::channel('autotransfer_api')->notice(
                        'Bank account not found or disabled',
                        [
                            'bank_code' => $bank->code,
                            'acc_no'    => $accountNo,
                        ]
                    );
                    continue;
                }

                $query->update([
                    'balance'     => $balance,
                    'api_refresh' => 'AutoTransfer sync ' .
                        ($updatedAt
                            ? Carbon::parse($updatedAt)->toDateTimeString()
                            : now()->toDateTimeString()
                        ),
                ]);
            }

            return 0;
        } catch (\Throwable $e) {
            Log::channel('autotransfer_api')->error(
                'UpdateBalanceAutoTransfer: fatal error',
                ['error' => $e->getMessage()]
            );
            return 0;
        }
    }
}
