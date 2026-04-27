<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGeneratorV3;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class PaymentProviderGenerator
{
    public function generate(PaymentProviderName $provider, array $analysis, array $plan, array $decisions, string $mode): array
    {
        $files = [];
        $filesMap = (array) $plan['files_map'];

        $files[$filesMap['library']] = $this->renderLibrary($provider, $analysis);
        $files[$filesMap['controller']] = $this->renderController($provider, $analysis, $decisions);
        $files[$filesMap['config']] = $this->renderConfig($provider);

        if (isset($filesMap['withdraw_job'])) {
            $files[$filesMap['withdraw_job']] = $this->renderWithdrawJob($provider, $analysis, $decisions);
        }

        if (isset($filesMap['balance_job'])) {
            $files[$filesMap['balance_job']] = $this->renderBalanceJob($provider, $analysis, $decisions);
        }

        $manifest = [
            'provider' => $provider->key,
            'studly' => $provider->studly,
            'mode' => $mode,
            'created_at' => now()->toIso8601String(),
            'capabilities' => $analysis['capabilities'] ?? [],
            'auth' => $analysis['auth'] ?? [],
            'decisions' => $decisions,
            'files' => [
                'created' => array_keys($files),
                'modified' => [],
                'suggested_patches' => $plan['suggested_manual_patches'] ?? [],
            ],
            'status_mapping' => config('payment_provider_generator.status_mapping', []),
            'terminal_statuses' => config('payment_provider_generator.terminal_statuses', []),
        ];

        if ($mode === 'write_files') {
            foreach ($files as $relative => $content) {
                $this->assertWritablePath($relative);

                $absolute = base_path($relative);
                if (File::exists($absolute)) {
                    throw new RuntimeException('Refuse to overwrite existing file: ' . $relative);
                }

                File::ensureDirectoryExists(dirname($absolute));
                File::put($absolute, $content);
            }
        } else {
            $previewDir = storage_path('app/mcp/payment-providers/' . $provider->key . '/dry-run');
            File::ensureDirectoryExists($previewDir);

            foreach ($files as $relative => $content) {
                $target = $previewDir . '/' . str_replace(['/', '\\'], '__', $relative);
                File::put($target, $content);
            }
        }

        return [
            'manifest' => $manifest,
            'files' => $files,
        ];
    }

    private function assertWritablePath(string $relative): void
    {
        foreach ((array) config('payment_provider_generator.blocked_paths', []) as $blocked) {
            if ($relative === $blocked || str_starts_with($relative, rtrim($blocked, '/') . '/')) {
                throw new RuntimeException('Blocked path: ' . $relative);
            }
        }

        foreach ((array) config('payment_provider_generator.whitelist_write_paths', []) as $allowed) {
            if ($relative === $allowed || str_starts_with($relative, rtrim($allowed, '/') . '/')) {
                return;
            }
        }

        throw new RuntimeException('Path is not whitelisted: ' . $relative);
    }

    private function renderConfig(PaymentProviderName $p): string
    {
        return <<<PHP
<?php

return [
    'api_url' => env('{$p->upperSnake}_API_URL', ''),
    'api_key' => env('{$p->upperSnake}_API_KEY', ''),
    'secret_key' => env('{$p->upperSnake}_SECRET_KEY', ''),
    'merchant_id' => env('{$p->upperSnake}_MERCHANT_ID', ''),
    'currency' => env('{$p->upperSnake}_CURRENCY', 'THB'),

    'system_bank_code' => (int) env('{$p->upperSnake}_SYSTEM_BANK_CODE', 310),
    'min_deposit' => (float) env('{$p->upperSnake}_MIN_DEPOSIT', 100),

    'debug_log' => (bool) env('{$p->upperSnake}_DEBUG_LOG', true),
    'log_channel' => env('{$p->upperSnake}_LOG_CHANNEL', '{$p->key}_api'),

    'callback' => [
        'verify_signature' => (bool) env('{$p->upperSnake}_CALLBACK_VERIFY_SIGNATURE', true),
        'allowed_ips' => array_filter(array_map('trim', explode(',', env('{$p->upperSnake}_CALLBACK_ALLOWED_IPS', '')))),
    ],
];

PHP;
    }

    private function renderLibrary(PaymentProviderName $p, array $analysis): string
    {
        $auth = $analysis['auth'] ?? [];
        $usesHmac = !empty($auth['hmac']);

        $signatureCode = $usesHmac
            ? "\$signature = hash_hmac('sha256', \$timestamp . '.' . \$method . '.' . \$path . '.' . \$jsonBody, \$secretKey);"
            : "\$signature = ''; // TODO: fill signature strategy from provider doc if required";

        return <<<PHP
<?php

declare(strict_types=1);

namespace Gametech\\Payment\\Libraries;

use Illuminate\\Support\\Facades\\Log;

class {$p->studly}
{
    public function request(string \$method, string \$path, ?array \$body = null, array \$query = []): array
    {
        \$baseUrl = rtrim((string) config('{$p->key}.api_url'), '/');
        \$path = '/' . ltrim(\$path, '/');

        \$url = \$baseUrl . \$path;
        if (!empty(\$query)) {
            \$url .= (str_contains(\$url, '?') ? '&' : '?') . http_build_query(\$query);
        }

        \$apiKey = (string) config('{$p->key}.api_key');
        \$secretKey = (string) config('{$p->key}.secret_key');
        \$timestamp = (string) time();

        \$jsonBody = \$body !== null
            ? (json_encode(\$body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
            : '';

        \$method = strtoupper(trim(\$method));
        {$signatureCode}

        \$headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Api-Key: ' . \$apiKey,
            'X-Timestamp: ' . \$timestamp,
        ];

        if (\$signature !== '') {
            \$headers[] = 'X-Signature: ' . \$signature;
        }

        \$this->apiLog('info', '[{$p->studly}] HTTP Request', [
            'method' => \$method,
            'path' => \$path,
            'url' => \$url,
            'has_body' => \$body !== null,
        ]);

        \$ch = curl_init();
        curl_setopt_array(\$ch, [
            CURLOPT_URL => \$url,
            CURLOPT_CUSTOMREQUEST => \$method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => \$headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        if (\$body !== null) {
            curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$jsonBody);
        }

        \$raw = curl_exec(\$ch);
        \$err = curl_error(\$ch);
        \$httpCode = (int) curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
        curl_close(\$ch);

        if (\$raw === false) {
            \$this->apiLog('error', '[{$p->studly}] Curl error', [
                'error' => \$err,
                'http_code' => \$httpCode,
            ]);

            return [
                'success' => false,
                'code' => \$httpCode ?: 500,
                'msg' => \$err ?: 'curl error',
                'data' => null,
                'raw' => null,
            ];
        }

        \$json = json_decode((string) \$raw, true);
        \$ok = \$httpCode >= 200 && \$httpCode < 300;

        \$this->apiLog(\$ok ? 'info' : 'error', '[{$p->studly}] HTTP Response', [
            'http_code' => \$httpCode,
            'success' => \$ok,
            'raw' => strlen((string) \$raw) > 12000 ? substr((string) \$raw, 0, 12000) . '...(truncated)' : \$raw,
        ]);

        return [
            'success' => \$ok,
            'code' => \$httpCode,
            'msg' => \$ok ? 'success' : (is_array(\$json) ? (string) (data_get(\$json, 'message') ?? data_get(\$json, 'msg') ?? 'error') : 'error'),
            'data' => \$json,
            'raw' => \$raw,
        ];
    }

    public function createPayin(array \$payload): array
    {
        return \$this->request('POST', '/v1/payins', \$payload);
    }

    public function createPayout(array \$payload): array
    {
        return \$this->request('POST', '/v1/payouts', \$payload);
    }

    public function getBalance(): array
    {
        return \$this->request('GET', '/v1/balance');
    }

    public function getTransaction(string \$transactionId): array
    {
        return \$this->request('GET', '/v1/transactions/' . urlencode(\$transactionId));
    }

    public function verifyCallback(array \$payload, array \$headers = []): bool
    {
        if (!(bool) config('{$p->key}.callback.verify_signature', true)) {
            return true;
        }

        // TODO: adjust callback signature verification from provider document.
        // Keep safe default: reject when verification is required but strategy is unknown.
        return false;
    }

    private function apiLog(string \$level, string \$message, array \$context = []): void
    {
        if (!(bool) config('{$p->key}.debug_log', true)) {
            return;
        }

        \$channel = (string) config('{$p->key}.log_channel', '{$p->key}_api');

        try {
            Log::channel(\$channel)->{\$level}(\$message, \$context);
        } catch (\\Throwable \$e) {
            Log::{\$level}(\$message, \$context);
        }
    }
}

PHP;
    }

    private function renderController(PaymentProviderName $p, array $analysis, array $decisions): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Gametech\\Payment\\Http\\Controllers;

use Carbon\\Carbon;
use Gametech\\Auto\\Jobs\\UpdateBalance{$p->studly};
use Gametech\\Core\\Repositories\\CheckCaseRepository;
use Gametech\\Member\\Repositories\\MemberRepository;
use Gametech\\Payment\\Libraries\\{$p->studly};
use Gametech\\Payment\\Repositories\\BankAccountRepository;
use Gametech\\Payment\\Repositories\\BankPaymentRepository;
use Gametech\\Payment\\Repositories\\BankRepository;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Log;

class {$p->studly}Controller extends AppBaseController
{
    public function __construct(
        protected CheckCaseRepository \$repository,
        protected MemberRepository \$memberRepository,
        protected BankRepository \$bankRepository,
        protected BankPaymentRepository \$bankPaymentRepository,
        protected BankAccountRepository \$bankAccountRepository,
    ) {
    }

    public function index(string \$id)
    {
        \$data = \$this->repository->findOneWhere(['detail' => \$id]);

        if (!\$data) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรายการฝากเงิน',
            ], 404);
        }

        \$authMember = auth()->guard('customer')->user();
        if (\$authMember && (string) \$data->username !== (string) \$authMember->user_name) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึงรายการนี้',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'request_id' => \$id,
                'txid' => (string) (\$data->txid ?? ''),
                'status' => (string) (\$data->status ?? ''),
                'amount' => (float) (\$data->amount ?? 0),
                'payamount' => (float) (\$data->payamount ?? 0),
                'qrcode' => \$data->qrcode ?? null,
                'qr_string' => \$data->url ?? null,
                'expired_date' => !empty(\$data->expired_date)
                    ? Carbon::parse(\$data->expired_date)->toDateTimeString()
                    : null,
            ],
        ]);
    }

    public function deposit(Request \$request)
    {
        \$request->validate([
            'amount' => 'required|numeric',
        ]);

        \$member = auth()->guard('customer')->user();
        if (!\$member) {
            return response()->json([
                'success' => false,
                'msg' => 'unauthenticated',
            ], 401);
        }

        \$amount = number_format((float) \$request->input('amount'), 2, '.', '');
        \$min = (float) config('{$p->key}.min_deposit', 100);

        if ((float) \$amount < \$min) {
            return response()->json([
                'success' => false,
                'msg' => __('app.topup.min_deposit', ['amount' => \$min]),
            ]);
        }

        \$systemBankCode = (int) config('{$p->key}.system_bank_code', 310);

        \$bankAccount = \$this->bankAccountRepository->findOneWhere([
            'banks' => \$systemBankCode,
            'bank_type' => 1,
            'enable' => 'Y',
            'status_auto' => 'Y',
        ]);

        if (!\$bankAccount) {
            return response()->json([
                'success' => false,
                'msg' => __('app.topup.fail'),
            ]);
        }

        \$txid = '{$p->studly}-DEP-' . str_pad((string) \$member->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');

        \$payload = [
            'amount' => \$amount,
            'currency' => (string) config('{$p->key}.currency', 'THB'),
            'merchant_ref_id' => \$txid,
            'customer' => [
                'id' => (string) \$member->code,
                'username' => (string) \$member->user_name,
                'name' => (string) \$member->name,
            ],
            'callback_url' => route('api.{$p->key}.deposit_callback'),
        ];

        \$api = new {$p->studly}();
        \$resp = \$api->createPayin(\$payload);

        if (!data_get(\$resp, 'success')) {
            Log::channel('{$p->key}_deposit_create')->error('[{$p->studly}] create payin failed', [
                'txid' => \$txid,
                'resp' => \$resp,
            ]);

            return response()->json([
                'success' => false,
                'msg' => (string) (data_get(\$resp, 'msg') ?: 'create payin failed'),
            ]);
        }

        \$provider = (array) data_get(\$resp, 'data.data', data_get(\$resp, 'data', []));

        \$requestId = (string) (
            data_get(\$provider, 'id')
            ?? data_get(\$provider, 'transaction_id')
            ?? data_get(\$provider, 'payment_id')
            ?? \$txid
        );

        \$qrString = (string) (
            data_get(\$provider, 'qr_string')
            ?? data_get(\$provider, 'qr')
            ?? data_get(\$provider, 'payment_url')
            ?? ''
        );

        \$qrImage = (string) (
            data_get(\$provider, 'qr_image')
            ?? data_get(\$provider, 'qrcode')
            ?? data_get(\$provider, 'qr_base64')
            ?? ''
        );

        \$payAmount = (string) (
            data_get(\$provider, 'pay_amount')
            ?? data_get(\$provider, 'transfer_amount')
            ?? \$amount
        );

        try {
            if (class_exists(UpdateBalance{$p->studly}::class)) {
                UpdateBalance{$p->studly}::dispatch()->delay(5)->onQueue('topup');
            }

            \$this->repository->create([
                'bank_code' => \$bankAccount->banks,
                'method' => 1,
                'txid' => \$txid,
                'detail' => \$requestId,
                'amount' => \$amount,
                'payamount' => \$payAmount,
                'username' => trim((string) \$member->user_name),
                'name' => (string) \$member->name,
                'url' => \$qrString !== '' ? \$qrString : null,
                'qrcode' => \$qrImage !== '' ? \$qrImage : null,
                'status' => 'pending',
                'expired_date' => null,
                'user_create' => (string) \$member->name,
                'user_update' => (string) \$member->name,
            ]);
        } catch (\\Throwable \$e) {
            Log::channel('{$p->key}_deposit_create')->error('[{$p->studly}] create check_case failed', [
                'txid' => \$txid,
                'error' => \$e->getMessage(),
                'provider' => \$provider,
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'create check_case failed: ' . \$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'msg' => __('app.topup.create'),
            'url' => route('api.{$p->key}.index', ['id' => \$requestId]),
        ]);
    }

    public function deposit_callback(Request \$request)
    {
        \$payload = \$request->all();

        Log::channel('{$p->key}_deposit_callback')->info('[{$p->studly}] Deposit callback', \$payload);

        \$api = new {$p->studly}();
        if (!\$api->verifyCallback(\$payload, \$request->headers->all())) {
            return response()->json(['success' => false, 'msg' => 'invalid signature'], 401);
        }

        \$merchantRef = data_get(\$payload, 'merchant_ref_id')
            ?? data_get(\$payload, 'reference')
            ?? data_get(\$payload, 'data.merchant_ref_id')
            ?? data_get(\$payload, 'data.reference');

        if (!\$merchantRef) {
            return response()->json(['success' => true]);
        }

        \$case = \$this->repository->findOneWhere(['txid' => \$merchantRef]);
        if (!\$case) {
            return response()->json(['success' => true]);
        }

        \$incoming = \$this->normalizeStatus(
            (string) (data_get(\$payload, 'status') ?? data_get(\$payload, 'data.status') ?? 'pending')
        );

        \$current = strtolower((string) \$case->status);

        if (in_array(\$current, ['completed', 'failed', 'rejected', 'refunded'], true)) {
            return response()->json(['success' => true]);
        }

        if (\$current === 'expired') {
            if (\$incoming === 'completed') {
                \$this->repository->update(['status' => 'completed'], \$case->code);
            }
        } elseif (\$incoming !== 'pending') {
            \$this->repository->update(['status' => \$incoming], \$case->code);
        }

        if (\$incoming === 'completed') {
            \$this->createBankPaymentFromCompletedDeposit(\$case, \$merchantRef, \$payload);
        }

        return response()->json(['success' => true]);
    }

    public function withdraw_callback(Request \$request)
    {
        // Pattern เดียวกับ SmkPay: normalize status แล้วให้ PaymentOut/withdraw repository ปิดหรือ rollback
        // TODO: bind to actual withdraw repository fields from provider callback payload.
        Log::channel('{$p->key}_withdraw_callback')->info('[{$p->studly}] Withdraw callback', \$request->all());

        return response()->json(['code' => 0, 'msg' => 'success']);
    }

    private function normalizeStatus(string \$status): string
    {
        \$status = strtolower(trim(\$status));
        \$map = config('payment_provider_generator.status_mapping', []);

        return (string) (\$map[\$status] ?? 'pending');
    }

    private function createBankPaymentFromCompletedDeposit(\$case, string \$merchantRef, array \$payload): void
    {
        \$amount = \$case->amount;
        \$member = \$this->memberRepository->findOneWhere(['user_name' => \$case->username]);

        if (!\$member) {
            return;
        }

        \$systemBankCode = (int) config('{$p->key}.system_bank_code', 310);

        \$bankAccount = \$this->bankAccountRepository->findOneWhere([
            'banks' => \$systemBankCode,
            'bank_type' => 1,
            'enable' => 'Y',
            'status_auto' => 'Y',
        ]);

        if (!\$bankAccount) {
            return;
        }

        \$bank = \$this->bankRepository->find(\$bankAccount->banks);
        \$refId = (string) (data_get(\$payload, 'id') ?? data_get(\$payload, 'data.id') ?? '-');
        \$detail = ' REF ID : ' . \$refId;
        \$hash = md5(\$bankAccount->code . \$amount . \$detail);

        \$existing = \$this->bankPaymentRepository->findOneWhere(['txid' => \$merchantRef]);
        if (\$existing) {
            return;
        }

        \$this->bankPaymentRepository->create([
            'bank' => strtolower(\$bank->shortcode . '_' . \$bankAccount->acc_no),
            'detail' => \$detail . ' จำนวน ' . \$amount,
            'account_code' => \$bankAccount->code,
            'autocheck' => 'W',
            'bankstatus' => 1,
            'bank_name' => \$bank->shortcode,
            'bank_time' => now()->toDateTimeString(),
            'channel' => 'QR',
            'value' => \$amount,
            'tx_hash' => \$hash,
            'txid' => \$merchantRef,
            'status' => 0,
            'ip_admin' => request()->ip(),
            'member_topup' => \$member->code,
            'remark_admin' => '',
            'emp_topup' => 0,
            'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย {$p->studly}',
            'create_by' => 'SYSAUTO',
        ]);
    }
}

PHP;
    }

    private function renderWithdrawJob(PaymentProviderName $p, array $analysis, array $decisions): string
    {
        $isStub = ($decisions['missing_withdraw'] ?? null) === 'stub';

        $body = $isStub
            ? "throw new \\RuntimeException('{$p->studly} withdraw is not supported by provider document.');"
            : "\$api = new {$p->studly}();\n        // TODO: map withdraw model fields to provider payout payload from API document.\n        \$api->createPayout([]);";

        return <<<PHP
<?php

declare(strict_types=1);

namespace Gametech\\Auto\\Jobs;

use Gametech\\Payment\\Libraries\\{$p->studly};
use Illuminate\\Bus\\Queueable;
use Illuminate\\Contracts\\Queue\\ShouldQueue;
use Illuminate\\Foundation\\Bus\\Dispatchable;
use Illuminate\\Queue\\InteractsWithQueue;
use Illuminate\\Queue\\SerializesModels;

class PaymentOut{$p->studly} implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ?int \$withdrawCode = null)
    {
        \$this->onQueue('withdraw');
    }

    public function handle(): void
    {
        {$body}
    }
}

PHP;
    }

    private function renderBalanceJob(PaymentProviderName $p, array $analysis, array $decisions): string
    {
        $isStub = ($decisions['missing_balance'] ?? null) === 'stub';

        $body = $isStub
            ? "return;"
            : "\$api = new {$p->studly}();\n        \$api->getBalance();";

        return <<<PHP
<?php

declare(strict_types=1);

namespace Gametech\\Auto\\Jobs;

use Gametech\\Payment\\Libraries\\{$p->studly};
use Illuminate\\Bus\\Queueable;
use Illuminate\\Contracts\\Queue\\ShouldQueue;
use Illuminate\\Foundation\\Bus\\Dispatchable;
use Illuminate\\Queue\\InteractsWithQueue;
use Illuminate\\Queue\\SerializesModels;

class UpdateBalance{$p->studly} implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct()
    {
        \$this->onQueue('topup');
    }

    public function handle(): void
    {
        {$body}
    }
}

PHP;
    }
}
