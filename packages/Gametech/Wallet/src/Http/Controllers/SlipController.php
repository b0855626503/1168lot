<?php

namespace Gametech\Wallet\Http\Controllers;

use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SlipController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $ocr;

    protected $bankAccountRepository;

    protected $memberRepository;

    protected $bankPaymentRepository;

    /**
     * Create a new Repository instance.
     */
    public function __construct(

        BankAccountRepository $bankAccountRepository,
        MemberRepository $memberRepository,
        BankPaymentRepository $bankPaymentRepository
    ) {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->memberRepository = $memberRepository;

        $this->bankAccountRepository = $bankAccountRepository;

        $this->bankPaymentRepository = $bankPaymentRepository;
    }

    protected function isLegacyMember(?int $memberCode): bool
    {
        if (! $memberCode || $memberCode <= 0) {
            return false;
        }

        return DB::table('member_deposit_stats')
            ->where('member_code', $memberCode)
            ->whereNotNull('legacy_at')
            ->exists();
    }

    /**
     * Apply visibility filter ให้ bank_accounts (กันพังถ้ายังไม่มีคอลัมน์)
     * - visibility_scope: NULL|all|legacy|new
     */
    protected function applyVisibilityScope($query, bool $isLegacy)
    {
        // กันพัง: ถ้ายังไม่ได้ migrate field นี้ → ข้ามไป
//        if (! Schema::hasColumn('bank_accounts', 'visibility_scope')) {
//            return $query;
//        }

        return $query->where(function ($q) use ($isLegacy) {
            $q->whereNull('visibility_scope')
                ->orWhere('visibility_scope', 'all')
                ->orWhere('visibility_scope', $isLegacy ? 'legacy' : 'new');
        });
    }

    public function loadBank(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'bank' => '',
        ];

        $result = [];
        $method = (string) $request->input('method', '');

        // member context (หน้า user ปกติมีอยู่แล้ว)
        $member = Auth::guard('customer')->user();
        $memberCode = $member ? (int) ($member->code ?? 0) : 0;
        $isLegacy = $this->isLegacyMember($memberCode);

        switch ($method) {

            case 'bank':

                // เดิม: ดึง accounts ทั้งหมด แล้ว loop skip TW
                // ปรับ: eager load bank + apply visibility + เงื่อนไขเดิม
                $query = $this->bankAccountRepository
                    ->with('bank')
                    ->where('bank_type', 1)
                    ->where('enable', 'Y')
                    ->where('display_wallet', 'Y')
                    ->where('slip', 'N')
                    ->where('payment', 'N');

                $query = $this->applyVisibilityScope($query, $isLegacy);

                $datas = $query->get();

                if ($datas->count() > 0) {
                    foreach ($datas as $i => $data) {
                        // safety: bank relation อาจ null ได้ในบางเคส
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

                    $response['success'] = true;
                    $response['bank'] = $result;

                    return response()->json($response);
                }
                break;

            case 'tw':

                $query = $this->bankAccountRepository
                    ->with('bank')
                    ->where('bank_type', 1)
                    ->where('enable', 'Y')
                    ->where('display_wallet', 'Y')
                    ->where('slip', 'N')
                    ->where('payment', 'N');

                $query = $this->applyVisibilityScope($query, $isLegacy);

                $datas = $query->get();

                if ($datas->count() > 0) {
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

                    $response['message'] = 'ใช้วอลเล็ททำรายการฝากเท่านั้น';
                    $response['success'] = true;
                    $response['bank'] = $result;

                    return response()->json($response);
                }
                break;

            case 'slip':

                $query = $this->bankAccountRepository
                    ->with('bank')
                    ->where('bank_type', 1)
                    ->where('enable', 'Y')
                    ->where('display_wallet', 'Y')
                    ->where('slip', 'Y');

                $query = $this->applyVisibilityScope($query, $isLegacy);

                $data = $query->inRandomOrder()->first();

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

                    $response['success'] = true;
                    $response['bank'] = $result;

                    return response()->json($response);
                }
                break;

            case 'payment':

                $query = $this->bankAccountRepository
                    ->with('bank')
                    ->where('bank_type', 1)
                    ->where('enable', 'Y')
                    ->where('display_wallet', 'Y')
                    ->where('slip', 'N')
                    ->where('payment', 'Y')
                    ->orderBy('sort');

                $query = $this->applyVisibilityScope($query, $isLegacy);

                $datas = $query->get();

                if ($datas->count() > 0) {
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

                    $response['success'] = true;
                    $response['bank'] = $result;

                    return response()->json($response);
                }
                break;

        }

        return response()->json($response);
    }

    public function getBankCode($bank)
    {
        switch ($bank) {
            case 'BBL':
                return '01002';
            case 'KBANK':
                return '01004';
            case 'KTB':
                return '01006';
            case 'SCB':
                return '01014';
            case 'GHBANK':
                return '01033';
            case 'CIMB':
                return '01022';
            case 'IBANK':
                return '01066';
            case 'TISCO':
                return '01067';
            case 'TTB':
                return '01011';
            case 'BAAC':
                return '01034';
            case 'TW':
                return '04000';

        }
    }

    public function getBank($bank)
    {
        switch ($bank) {
            case '01002':
                return '1';
            case '01004':
                return '2';
            case '01006':
                return '3';
            case '01014':
                return '4';
            case '01033':
                return '5';
            case '01022':
                return '7';
            case '01066':
                return '13';
            case '01067':
                return '9';
            case '01069':
                return '19';
            case '01034':
                return '17';
            case '04000':
                return '18';

        }
    }

    public function verifySlip(Request $request)
    {
        // ตรวจสอบว่าไฟล์กับ payload ถูกส่งมาหรือไม่
        $secretKey = config('slip2go.secret_key');

        if (! $request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'ข้อมูลไม่ครบ'], 422);
        }
        //        if (! $request->hasFile('file') || ! $request->has('payload')) {
        //            return response()->json(['success' => false, 'message' => 'ข้อมูลไม่ครบ'], 422);
        //        }

        $file = $request->file('file');

        $payload = json_decode($request->input('payload'), true);

        // ใช้ Guzzle ส่ง multipart
        $response = Http::withHeaders([
            'Authorization' => "Bearer $secretKey",
        ])->attach(
            'file', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
        )->post('https://connect.slip2go.com/api/verify-slip/qr-image/info', [
            ['name' => 'payload', 'contents' => json_encode($payload)],
        ]);

        $data = $response->json();
        if ($data['code'] === '200200') {
            $datenow = now()->toDateTimeString();
            $info = json_decode($request->input('info'), true);

            $member = $this->user();
            $account = $info['code'];
            $amount = $data['data']['amount'];
            $detail = 'รายการฝากเงิน ผ่านการอัพสลิป transRef : '.$data['data']['transRef'].' referenceId '.$data['data']['referenceId'];
            $bank_account = app('Gametech\Payment\Repositories\BankAccountRepository')->find($account);

            $bank = app('Gametech\Payment\Repositories\BankRepository')->find($bank_account->banks);

            $hash = md5($account.$datenow.$amount.$detail);

            $data = [
                'bank' => strtolower($bank->shortcode.'_'.$bank_account->acc_no),
                'txid' => $data['data']['transRef'],
                'report_id' => $data['data']['referenceId'],
                'detail' => $detail,
                'account_code' => $account,
                'autocheck' => 'W',
                'bankstatus' => 1,
                'bank_name' => $bank->shortcode,
                'bank_time' => $datenow,
                'channel' => 'SLIP',
                'value' => $amount,
                'tx_hash' => $hash,
                'status' => 0,
                'ip_admin' => $request->ip(),
                'member_topup' => $member->code,
                'remark_admin' => '',
                'emp_topup' => 0,
                'user_create' => 'SYSAUTO',
                'create_by' => 'SYSAUTO',
            ];

            $this->bankPaymentRepository->create($data);

        }

        return response()->json($response->json(), $response->status());
    }

    public function uploadQr(Request $request)
    {
        if (! $request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'ข้อมูลไม่ครบ'], 200);
        }

        $user = auth()->guard('customer')->user();
        $filename = $user->user_name.'.'.$request->file('file')->getClientOriginalExtension();
        $path = $request->file('file')->storeAs('qr', $filename, 'public');
        if ($path) {
            $user->pic_id = $path;
            $user->save();
        } else {
            return response()->json(['success' => false, 'message' => 'อัพรูปไม่สำเร็จ'], 200);
        }

        return response()->json(['success' => true, 'message' => 'สำเร็จ', 'img_url' => asset('storage/'.$path)], 200);
    }
}
