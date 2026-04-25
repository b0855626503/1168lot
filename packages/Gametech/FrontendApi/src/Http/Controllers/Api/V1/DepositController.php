<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DepositController extends BaseController
{
    public function channels(Request $request)
    {
        try {
            $deposit = core()->getBankTopupCountsNew();

            return $this->sendResponse([
                'deposit' => [
                    'bank' => ((int) ($deposit['bank'] ?? 0)) > 0 ? 1 : 0,
                    'payment' => (int) ($deposit['payment'] ?? 0),
                    'tw' => (int) ($deposit['tw'] ?? 0),
                    'slip' => (int) ($deposit['slip'] ?? 0),
                    'sort' => [
                        'payment' => $deposit['payment_min_sort'] ?? null,
                        'tw' => $deposit['tw_min_sort'] ?? null,
                        'slip' => $deposit['slip_min_sort'] ?? null,
                        'bank' => $deposit['bank_min_sort'] ?? null,
                    ],
                ],
            ], 'ดึงช่องทางเติมเงินสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงช่องทางเติมเงินได้ในขณะนี้', 422);
        }
    }

    public function loadBank(Request $request)
    {
        try {
            $validated = validator($request->all(), [
                'method' => ['required', 'in:bank,payment,tw,slip'],
            ])->validate();

            return $this->normalizedJsonResponse($this->loadBankPayload($request, (string) $validated['method']));
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงข้อมูลบัญชีเติมเงินได้ในขณะนี้', 422);
        }
    }

    public function loadRandomBank(Request $request)
    {
        try {
            $validated = validator($request->all(), [
                'method' => ['required', 'in:bank,tw,slip'],
            ])->validate();

            $payload = $this->loadBankPayload($request, (string) $validated['method']);
            $account = $this->randomAccountFromPayload($payload['bank'] ?? []);

            return $this->normalizedJsonResponse([
                'success' => $account !== null,
                'bank' => $account ?? '',
            ]);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงข้อมูลบัญชีเติมเงินได้ในขณะนี้', 422);
        }
    }

    /**
     * @return array{success:bool,bank:mixed,message?:string}
     */
    private function loadBankPayload(Request $request, string $method): array
    {
        $response = [
            'success' => false,
            'bank' => '',
        ];

        $member = $request->user() ?: $request->user('customer');
        $memberCode = (int) ($member->code ?? 0);
        $isLegacy = $this->isLegacyMember($memberCode);
        $result = [];

        $query = app('Gametech\Payment\Repositories\BankAccountRepository')
            ->with('bank')
            ->where('bank_type', 1)
            ->where('enable', 'Y')
            ->where('display_wallet', 'Y');

        switch ($method) {
            case 'bank':
                $datas = $this->applyVisibilityScope(
                    (clone $query)->where('slip', 'N')->where('payment', 'N'),
                    $isLegacy
                )->get();

                foreach ($datas as $i => $data) {
                    $shortcode = (string) optional($data->bank)->shortcode;
                    if ($shortcode === 'TW') {
                        continue;
                    }

                    $result[$i] = [
                        'acc_no' => $data->acc_no,
                        'acc_name' => $data->acc_name,
                        'bank_name' => (string) optional($data->bank)->name_th,
                        'bank_pic' => Storage::url('bank_img/'.(string) optional($data->bank)->filepic),
                        'qr_pic' => Storage::url('bank_qr/'.$data->filepic),
                        'qrcode' => $data->qrcode === 'Y',
                        'code' => $data->code,
                        'deposit_min' => $data->deposit_min,
                        'remark' => $data->remark,
                    ];
                }

                break;

            case 'tw':
                $datas = $this->applyVisibilityScope(
                    (clone $query)->where('slip', 'N')->where('payment', 'N'),
                    $isLegacy
                )->get();

                foreach ($datas as $i => $data) {
                    $shortcode = (string) optional($data->bank)->shortcode;
                    if ($shortcode !== 'TW') {
                        continue;
                    }

                    $result[$i] = [
                        'acc_no' => $data->acc_no,
                        'acc_name' => $data->acc_name,
                        'bank_name' => (string) optional($data->bank)->name_th,
                        'bank_pic' => Storage::url('bank_img/'.(string) optional($data->bank)->filepic),
                        'qr_pic' => Storage::url('bank_qr/'.$data->filepic),
                        'qrcode' => $data->qrcode === 'Y',
                        'deposit_min' => $data->deposit_min,
                        'remark' => $data->remark,
                    ];
                }

                if (! empty($result)) {
                    $response['message'] = 'ใช้วอลเล็ททำรายการฝากเท่านั้น';
                }

                break;

            case 'slip':
                $data = $this->applyVisibilityScope(
                    (clone $query)->where('slip', 'Y'),
                    $isLegacy
                )->inRandomOrder()->first();

                if ($data) {
                    $result = [
                        'acc_no' => $data->acc_no,
                        'acc_name' => $data->acc_name,
                        'bank_name' => (string) optional($data->bank)->name_th,
                        'bank_pic' => Storage::url('bank_img/'.(string) optional($data->bank)->filepic),
                        'qr_pic' => Storage::url('bank_qr/'.$data->filepic),
                        'qrcode' => $data->qrcode === 'Y',
                        'slip_bank' => $this->getBankCode((string) optional($data->bank)->shortcode),
                        'code' => $data->code,
                    ];
                }

                break;

            case 'payment':
                $datas = $this->applyVisibilityScope(
                    (clone $query)->where('slip', 'N')->where('payment', 'Y')->orderBy('sort'),
                    $isLegacy
                )->get();

                foreach ($datas as $data) {
                    $key = strtolower((string) optional($data->bank)->shortcode);
                    if ($key === '') {
                        continue;
                    }

                    $result[$key] = [
                        'id' => $key,
                        'min_deposit' => ($data->deposit_min > 0 ? $data->deposit_min : config("$key.min_deposit")),
                        'deposit_range' => config("$key.deposit_range"),
                        'payment_url' => route("api.$key.deposit"),
                        'name' => ucfirst($key),
                        'remark' => $data->remark,
                    ];
                }

                break;
        }

        if (! empty($result)) {
            $response['success'] = true;
            $response['bank'] = $result;
        }

        return $response;
    }

    private function isLegacyMember(int $memberCode): bool
    {
        if ($memberCode <= 0 || ! DB::getSchemaBuilder()->hasTable('member_deposit_stats')) {
            return false;
        }

        return DB::table('member_deposit_stats')
            ->where('member_code', $memberCode)
            ->whereNotNull('legacy_at')
            ->exists();
    }

    private function applyVisibilityScope($query, bool $isLegacy)
    {
        return $query->where(function ($q) use ($isLegacy): void {
            $q->whereNull('visibility_scope')
                ->orWhere('visibility_scope', 'all')
                ->orWhere('visibility_scope', $isLegacy ? 'legacy' : 'new');
        });
    }

    private function randomAccountFromPayload($bankPayload): ?array
    {
        if (! is_array($bankPayload) || empty($bankPayload)) {
            return null;
        }

        if (Arr::isAssoc($bankPayload) && array_key_exists('acc_no', $bankPayload)) {
            return $bankPayload;
        }

        $accounts = collect($bankPayload)
            ->filter(static fn ($item): bool => is_array($item) && array_key_exists('acc_no', $item))
            ->values();

        if ($accounts->isEmpty()) {
            return null;
        }

        return $accounts->random();
    }

    private function getBankCode(string $bank): ?string
    {
        return match ($bank) {
            'BBL' => '01002',
            'KBANK' => '01004',
            'KTB' => '01006',
            'SCB' => '01014',
            'GHBANK' => '01033',
            'CIMB' => '01022',
            'IBANK' => '01066',
            'TISCO' => '01067',
            'TTB' => '01011',
            'BAAC' => '01034',
            'TW' => '04000',
            default => null,
        };
    }
}
