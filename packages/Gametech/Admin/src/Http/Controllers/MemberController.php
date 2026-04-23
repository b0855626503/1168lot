<?php

namespace Gametech\Admin\Http\Controllers;

use App\Events\MemberBalanceUpdated;
use App\Events\RealtimeMemberActivityUpdated;
use Carbon\Carbon;
use Gametech\Admin\DataTables\MemberDataTable;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberDiamondLogRepository;
use Gametech\Member\Repositories\MemberPointLogRepository;
use Gametech\Member\Repositories\MemberRemarkRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PragmaRX\Google2FA\Google2FA;

class MemberController extends AppBaseController
{
    private $_config;

    private $gameRepository;

    private $bankPaymentRepository;

    private $gameUserRepository;

    private $memberRepository;

    private $memberCreditLogRepository;

    private $memberPointLogRepository;

    private $memberDiamondLogRepository;

    private $memberRemarkRepository;

    public $method;

    /**
     * MemberController constructor.
     */
    public function __construct(
        GameUserRepository $gameUserRepo,
        GameRepository $gameRepo,
        MemberRepository $memberRepo,
        MemberCreditLogRepository $memberCreditLogRepo,
        MemberPointLogRepository $memberPointLogRepo,
        MemberDiamondLogRepository $memberDiamondLogRepo,
        BankPaymentRepository $bankPaymentRepo,
        MemberRemarkRepository $memberRemarkRepo
    ) {

        $this->_config = request('_config');

        //        $this->middleware('admin');
        $this->middleware(['auth', 'admin']);

        $this->gameUserRepository = $gameUserRepo;

        $this->gameRepository = $gameRepo;

        $this->memberRepository = $memberRepo;

        $this->memberCreditLogRepository = $memberCreditLogRepo;

        $this->memberPointLogRepository = $memberPointLogRepo;

        $this->bankPaymentRepository = $bankPaymentRepo;

        $this->memberDiamondLogRepository = $memberDiamondLogRepo;

        $this->memberRemarkRepository = $memberRemarkRepo;

        $this->method = ['TOPUP' => Lang::get('app.status.refill'), 'WITHDRAW' => Lang::get('app.status.withdraw'), 'ROLLBACK' => Lang::get('app.status.rollback'), 'SETWALLET' => Lang::get('app.status.setwallet'), 'BONUS' => Lang::get('app.status.bonus')];
    }

    public function index(MemberDataTable $memberDataTable)
    {
        //        request()->session()->invalidate();
        //
        //        request()->session()->regenerateToken();

        return $memberDataTable->render($this->_config['view']);
    }

    public function edit(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');

        $data[$method] = $status;

        $member = $this->memberRepository->find($id);
        if (! $member) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $member = $this->memberRepository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function editsub(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');

        $data[$method] = $status;

        $member = $this->gameUserRepository->find($id);
        if (! $member) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $member = $this->gameUserRepository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->memberRepository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->memberRepository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function refill(Request $request)
    {
        $user = Auth::guard('admin')->user();
        $google2fa = new Google2FA;

        $return['success'] = false;

        $datenow = now()->toDateTimeString(); // server time (ไว้ใช้เป็น fallback/ทำ hash)
        $date = now()->toDateString();
        $fulluser = $user->name.' '.$user->surname;
        $ip = $request->ip();

        $config = $this->getCoreConfig();

        $request->validate([
            'id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
            'account_code' => ['required', 'integer'],
            'remark_admin' => ['nullable', 'string'],

            // ✅ เพิ่ม: bank_time จาก input type="datetime-local" => YYYY-MM-DDTHH:mm
            // ถ้าคุณอยาก "บังคับกรอก" ให้ใช้ required
            'bank_time' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);

        $id = (int) $request->input('id');
        $amount = (float) $request->input('amount');

        $account = (int) $request->input('account_code');
        $remark = $request->input('remark_admin', '');

        // ✅ แปลง bank_time เป็น format ที่เหมาะกับ DB: Y-m-d H:i:s
        // - datetime-local ไม่มี timezone; เราจะตีความเป็นเวลา "ตามเครื่อง/ระบบ" (Asia/Bangkok) ตามการใช้งานในไทย
        // - ถ้าเกิดกรณี frontend ยังไม่ส่งมา (กันพัง) ให้ fallback เป็น $datenow
        $rawBankTime = $request->input('bank_time');
        $bankTime = $datenow;
        if ($rawBankTime) {
            try {
                $bankTime = Carbon::createFromFormat('Y-m-d\TH:i', $rawBankTime, 'Asia/Bangkok')
                    ->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // ถ้า parse ไม่ผ่าน ให้ใช้ now() และปล่อยผ่านเพื่อไม่ทำรายการล่ม
                // แต่ถ้าคุณอยาก strict: ให้ return error ตรงนี้แทน
                $bankTime = $datenow;
            }
        }

        //        $secret = $request->input('one_time_password');
        //
        //        if($user->superadmin == 'N') {
        //
        //            $valid = $google2fa->verifyKey($user->google2fa_secret, $secret);
        //            if (!$valid) {
        //                return $this->sendError('รหัสยืนยันไม่ถูกต้อง', 200);
        //            }
        //        }

        $member = $this->memberRepository->find($id);

        $bank_account = app('Gametech\Payment\Repositories\BankAccountRepository')->find($account);

        $bank = app('Gametech\Payment\Repositories\BankRepository')->find($bank_account->banks);

        if ($amount < 1) {
            return $this->sendError('ยอดเงินไม่ถูกต้อง', 200);
        } elseif ($amount > $config['maxsetcredit']) {
            return $this->sendError('ไม่สามารถทำรายการเกินครั้งละ '.core()->currency($config['maxsetcredit']), 200);
        }

        $detail = 'เพิ่มรายการฝากเงินโดย Staff : '.$fulluser;

        // หมายเหตุ: hash เดิมอิง $datenow (เวลาสร้างรายการ) ไม่ใช่ bank_time ที่ user เลือก
        // เพื่อคง behavior เดิมและลดผลกระทบ
        $hash = md5($account.$datenow.$amount.$detail);

        $data = [
            'bank' => strtolower($bank->shortcode.'_'.$bank_account->acc_no),
            'detail' => $detail,
            'account_code' => $account,
            'autocheck' => 'W',
            'bankstatus' => 1,
            'bankname' => $bank->shortcode,

            // ✅ ใช้ bank_time ที่ทีมงานระบุ
            'bank_time' => $bankTime,

            'channel' => 'MANUAL',
            'value' => $amount,
            'tx_hash' => $hash,
            'status' => 0,
            'ip_admin' => $ip,
            'member_topup' => $id,
            'remark_admin' => $remark,
            'emp_topup' => $this->id(),
            'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย Staff : '.$fulluser,
            'create_by' => 'SYSAUTO',
        ];

        $response = $this->bankPaymentRepository->create($data);
        if ($response->code) {
            return $this->sendSuccess('ดำเนินการ ทำรายการฝากเงิน เรียบร้อยแล้ว');
        } else {
            return $this->sendError('ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง', 200);
        }
    }

    public function setWallet(Request $request)
    {
        $user = Auth::guard('admin')->user();
        $google2fa = new Google2FA;

        $return['success'] = false;

        $request->validate([
            'id' => 'required',
            'amount' => 'required|numeric',
            'type' => 'required|string',
            'remark' => 'required|string',
        ]);

        $id = $request->input('id');
        $amount = $request->input('amount');
        $remark = $request->input('remark');
        $method = $request->input('type');
        //        $secret = $request->input('one_time_password');
        //
        //        if($user->superadmin == 'N' && $user->google2fa_enable) {
        //
        //            $valid = $google2fa->verifyKey($user->google2fa_secret, $secret);
        //            if (!$valid) {
        //                return $this->sendError('รหัสยืนยันไม่ถูกต้อง', 200);
        //            }
        //        }

        $types = ['D' => 'เพิ่ม ยอดเงินคงเหลือ', 'W' => 'ลด ยอดเงินคงเหลือ'];

        $config = $this->getCoreConfig();

        $member = $this->memberRepository->find($id);

        if ($amount < 0) {
            return $this->sendError('ยอดเงินไม่ถูกต้อง', 200);
        } elseif ($amount > $config['maxsetcredit']) {
            return $this->sendError('ไม่สามารถทำรายการเกินครั้งละ '.core()->currency($config['maxsetcredit']), 200);
        } elseif ($method == 'W' && ($member->balance - $amount) < 0) {
            return $this->sendError('ยอดเงินหลังทำรายการ ไม่สามารถติดลบได้', 200);
        }

        $data = [
            'refer_code' => $id,
            'refer_table' => 'members',
            'kind' => 'SETWALLET',
            'remark' => $remark,
            'amount' => $amount,
            'method' => $method,
            'member_code' => $id,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name.' '.$this->user()->surname,
        ];

        if ($config->seamless == 'Y') {

            $response = $this->memberCreditLogRepository->setWalletSeamless($data);

        } else {

            if ($config->multigame_open == 'Y') {
                $response = $this->memberCreditLogRepository->setWallet($data);
            } else {
                $response = $this->memberCreditLogRepository->setWalletSingle($data);
            }

        }
        if ($response) {
            $this->broadcastManualWalletAdjust($id, (float) $amount, (string) $method, (string) $remark);

            return $this->sendSuccess('ดำเนินการ '.$types[$method].' เรียบร้อยแล้ว');
        } else {
            return $this->sendError('ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง', 200);
        }

    }

    protected function broadcastManualWalletAdjust(int $memberCode, float $amount, string $method, string $remark): void
    {
        $member = $this->memberRepository->find($memberCode);
        if (! $member) {
            return;
        }

        $normalizedMethod = strtoupper(trim($method));
        if (! in_array($normalizedMethod, ['D', 'W'], true)) {
            return;
        }

        $direction = $normalizedMethod === 'D' ? 'credit' : 'debit';
        $reason = $normalizedMethod === 'D' ? 'admin_adjust_credit' : 'admin_adjust_debit';
        $message = $this->manualWalletAdjustMessage($normalizedMethod, $amount, $remark);

        broadcast(new MemberBalanceUpdated(
            $memberCode,
            (float) ($member->balance ?? 0),
            round($amount, 2),
            $reason,
            0,
            $message
        ));

        broadcast(new RealtimeMemberActivityUpdated(
            $memberCode,
            'adjust',
            'wallet.admin_adjusted',
            [
                'amount' => round($amount, 2),
                'balance' => (float) ($member->balance ?? 0),
                'reference_code' => 0,
                'reason' => $reason,
                'direction' => $direction,
                'remark' => trim($remark),
                'adjusted_by' => trim((string) ($this->user()?->name.' '.$this->user()?->surname)),
            ],
            $message
        ));
    }

    protected function manualWalletAdjustMessage(string $method, float $amount, string $remark): string
    {
        $action = strtoupper(trim($method)) === 'D' ? 'เพิ่มเครดิต' : 'ลดเครดิต';
        $message = 'ทีมงาน'.$action.' '.number_format(round($amount, 2), 2, '.', ',').' บาท';

        $normalizedRemark = trim($remark);
        if ($normalizedRemark !== '') {
            $message .= ' หมายเหตุ: '.$normalizedRemark;
        }

        return $message;
    }

    public function setPoint(Request $request)
    {
        $return['success'] = false;

        $request->validate([
            'id' => 'required',
            'amount' => 'required|numeric',
            'type' => 'required|string',
            'remark' => 'required|string',
        ]);

        $id = $request->input('id');
        $amount = $request->input('amount');
        $remark = $request->input('remark');
        $method = $request->input('type');

        $types = ['D' => 'เพิ่ม Point', 'W' => 'ลด Point'];

        $config = $this->getCoreConfig();

        $member = $this->memberRepository->find($id);

        if ($amount < 1) {
            return $this->sendError('ยอดเงินไม่ถูกต้อง', 200);
        } elseif ($method == 'W' && ($member->point_deposit - $amount) < 0) {
            return $this->sendError('ยอดเงินหลังทำรายการ ไม่สามารถติดลบได้', 200);
        }

        $data = [
            'remark' => $remark,
            'amount' => $amount,
            'method' => $method,
            'member_code' => $id,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name.' '.$this->user()->surname,
        ];

        $response = $this->memberPointLogRepository->setPoint($data);
        if ($response) {
            return $this->sendSuccess('ดำเนินการ '.$types[$method].' เรียบร้อยแล้ว');
        } else {
            return $this->sendError('ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง', 200);
        }

    }

    public function setDiamond(Request $request)
    {
        $return['success'] = false;

        $request->validate([
            'id' => 'required',
            'amount' => 'required|numeric',
            'type' => 'required|string',
            'remark' => 'required|string',
        ]);

        $id = $request->input('id');
        $amount = $request->input('amount');
        $remark = $request->input('remark');
        $method = $request->input('type');

        $types = ['D' => 'เพิ่ม Diamond', 'W' => 'ลด Diamond'];

        $config = $this->getCoreConfig();

        $member = $this->memberRepository->find($id);

        if ($amount < 1) {
            return $this->sendError('ยอด Diamond ไม่ถูกต้อง', 200);
        } elseif ($method == 'W' && ($member->diamond - $amount) < 0) {
            return $this->sendError('ยอด Diamond หลังทำรายการ ไม่สามารถติดลบได้', 200);
        }

        $data = [
            'remark' => $remark,
            'amount' => $amount,
            'method' => $method,
            'member_code' => $id,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name.' '.$this->user()->surname,
        ];

        $response = $this->memberDiamondLogRepository->setDiamond($data);
        if ($response) {
            return $this->sendSuccess('ดำเนินการ '.$types[$method].' เรียบร้อยแล้ว');
        } else {
            return $this->sendError('ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง', 200);
        }

    }

    public function gameLog(Request $request)
    {
        $id = $request->input('id');
        $method = $request->input('method');
        $header = '';
        $member = $this->memberRepository->find($id);
        $responses = [];

        switch ($method) {
            case 'gameuser':
                $header = 'ข้อมูลบัญชีเกม';
                $responses = $this->gameuser($id);
                break;

            case 'transfer':
                $header = 'ข้อมูลการโยก 50 รายการล่าสุด';
                $responses = $this->gametransfer($id);
                break;

            case 'deposit':
                $header = 'ข้อมูลการฝากเงิน 50 รายการล่าสุด';
                $responses = $this->gamedeposit($id);
                break;

            case 'withdraw':
                $header = 'ข้อมูลการถอน 50 รายการล่าสุด';
                $responses = $this->gamewithdraw($id);
                break;

            case 'setwallet':
                $header = 'ข้อมูลการ Set Wallet 50 รายการล่าสุด';
                $responses = $this->gamesetwallet($id);
                break;

            case 'setpoint':
                $header = 'ข้อมูลการ Set Point 50 รายการล่าสุด';
                $responses = $this->gamesetpoint($id);
                break;

            case 'setdiamond':
                $header = 'ข้อมูลการ Set Diamond 50 รายการล่าสุด';
                $responses = $this->gamesetdiamond($id);
                break;
        }

        $result['name'] = $member->firstname.' '.$member->lastname.'('.$header.')';
        $result['list'] = $responses;

        return $this->sendResponseNew($result, 'complete');
    }

    public function gameuser($id)
    {

        $games = collect($this->gameRepository->getGameUserById($id, false)->toArray())->whereNotNull('game_user');

        $games = $games->map(function ($items) {
            $item = (object) $items;

            return [
                'status' => '<span class="text-danger">db</span>',
                'game_code' => $item->code,
                'game' => $item->name,
                'member_code' => $item->game_user['member_code'],
                'user_name' => $item->game_user['user_name'],
                'user_pass' => $item->game_user['user_pass'],
                'balance' => $item->game_user['balance'],
                'promotion' => (! is_null($item->game_user['promotion']) ? $item->game_user['promotion']['name_th'] : '-'),
                'turn' => ($item->game_user['pro_code'] > 0 ? $item->game_user['turnpro'] : '-'),
                'amount_balance' => ($item->game_user['pro_code'] > 0 ? $item->game_user['amount_balance'] : '-'),
                'withdraw_limit' => ($item->game_user['pro_code'] > 0 ? ($item->game_user['withdraw_limit'] > 0 ? $item->game_user['withdraw_limit'] : '-') : '-'),
                'action' => '<button class="btn btn-xs icon-only '.($item->game_user['enable'] == 'Y' ? 'btn-warning' : 'btn-danger').'" onclick="editdatasub('.$item->game_user['code'].','."'".core()->flip($item->game_user['enable'])."'".','."'enable'".')">'.($item->game_user['enable'] == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-trash"></i>').'</button>',
                'changepass' => '<button class="btn btn-xs icon-only btn-info" onclick="changegamepass('.$item->game_user['code'].')"><i class="fa fa-edit"></i></button>',
            ];

        });

        return $games->values()->all();
    }

    public function gamesetwallet($id)
    {

        $responses = collect($this->memberCreditLogRepository->orderBy('date_create', 'desc')->findWhere(['member_code' => $id, 'kind' => 'SETWALLET', 'enable' => 'Y'])->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                'credit_amount' => $item->total,
                'credit_before' => $item->balance_before,
                'credit_balance' => $item->balance_after,
                'remark' => $item->remark,
                'credit_type' => $item->credit_type == 'D' ? '<span class="text-success">เพิ่ม Wallet</span>' : '<span class="text-danger">ลด Wallet</span>',

            ];

        });

        return $responses->take(50)->values()->all();
    }

    public function gamesetpoint($id)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberPointLogRepository')->orderBy('date_create', 'desc')->findWhere(['member_code' => $id, 'enable' => 'Y'])->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                'credit_amount' => $item->point_amount,
                'credit_before' => $item->point_before,
                'credit_balance' => $item->point_balance,
                'remark' => $item->remark,
                'credit_type' => $item->point_type == 'D' ? '<span class="text-success">เพิ่ม Point</span>' : '<span class="text-danger">ลด Point</span>',

            ];

        });

        return $responses->take(50)->values()->all();
    }

    public function gamesetdiamond($id)
    {

        $responses = collect(app('Gametech\Member\Repositories\MemberDiamondLogRepository')->orderBy('date_create', 'desc')->findWhere(['member_code' => $id, 'enable' => 'Y'])->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                'credit_amount' => $item->diamond_amount,
                'credit_before' => $item->diamond_before,
                'credit_balance' => $item->diamond_balance,
                'remark' => $item->remark,
                'credit_type' => $item->diamond_type == 'D' ? '<span class="text-success">เพิ่ม Diamond</span>' : '<span class="text-danger">ลด Diamond</span>',

            ];

        });

        return $responses->take(50)->values()->all();
    }

    public function gametransfer($id)
    {

        $responses = collect($this->memberRepository->loadBill($id, '', '')->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'id' => '#BL'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                'amount' => $item->amount,
                'game_name' => $item->game['name'],
                'transfer' => $item->transfer_type == 1 ? '<span class="text-success">โยกเข้าเกม</span>' : '<span class="text-danger">โยกออกเกม</span>',
                'status' => $item->enable == 'Y' ? '<span class="text-danger">สำเร็จ</span>' : '<span class="text-warning">ไม่สำเร็จ</span>',
            ];

        });

        return $responses->take(50)->values()->all();
    }

    public function gamedeposit_($id)
    {

        $responses = collect($this->memberRepository->loadDeposit($id, '', '')->toArray());
        if (! $responses) {
            return [];
        }
        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'id' => '#DP'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                'amount' => $item->value,
                'credit_bonus' => $item->pro_amount,
                'credit_before' => $item->before_credit,
                'credit_after' => $item->after_credit,
            ];

        });

        return $responses->take(50)->values()->all();
    }

    public function gamewithdraw_($id)
    {
        $config = $this->getCoreConfig();
        if ($config->seamless == 'Y') {
            $responses = collect($this->memberRepository->loadWithdrawSeamless($id, '', '')->toArray());

        } else {
            $responses = collect($this->memberRepository->loadWithdraw($id, '', '')->toArray());

        }
        //        $responses = collect($this->memberRepository->loadWithdraw($id, '', '')->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $status = ['0' => 'รอโอนเงิน', '1' => 'โอนเงินสำเร็จ', '2' => 'ยกเลิก'];

            return [
                'id' => '#WD'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                'amount' => $item->amount,
                'credit_before' => $item->oldcredit,
                'credit_after' => $item->aftercredit,
                'status_display' => $status[$item->status],
            ];

        });

        return $responses->take(50)->values()->all();
    }

    public function gamedeposit($id)
    {

        $responses = collect($this->memberRepository->loadBillType($id, 'TOPUP')->toArray());
        if (! $responses) {
            return [];
        }
        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel')];
            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];

            return [
                'id' => '#DP'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                'amount' => $item->amount,
                'amount_request' => $item->amount_request,
                'pro_name' => $item->pro_name,
                'credit_bonus' => $item->credit_bonus,
                'credit_before' => $item->credit_before,
                'credit_after' => $item->credit_after,
                'status' => $item->complete,
                'image' => $image[$item->complete],
                'transfer_type' => ($item->transfer_type == 1 ? '+' : '-'),
                'method' => $this->method[$item->method],
                'status_color' => $color[$item->complete],
                'status_display' => $status[$item->complete],
            ];

        });

        return $responses->values()->all();
    }

    public function gamewithdraw($id)
    {
        $responses = collect($this->memberRepository->loadBillType($id, 'WITHDRAW')->toArray());
        //        $responses = collect($this->memberRepository->loadWithdraw($id, '', '')->toArray());
        if (! $responses) {
            return [];
        }
        $responses = $responses->map(function ($items) {
            $item = (object) $items;
            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel')];
            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];

            return [
                'id' => '#WD'.Str::of($item->code)->padLeft(8, 0),
                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                'amount' => $item->amount,
                'amount_request' => $item->amount_request,
                'pro_name' => $item->pro_name,
                'credit_bonus' => $item->credit_bonus,
                'credit_before' => $item->credit_before,
                'credit_after' => $item->credit_after,
                'status' => $item->complete,
                'image' => $image[$item->complete],
                'transfer_type' => ($item->transfer_type == 1 ? '+' : '-'),
                'method' => $this->method[$item->method],
                'status_color' => $color[$item->complete],
                'status_display' => $status[$item->complete],
            ];

        });

        return $responses->values()->all();
    }

    public function loadBank()
    {
        $banks = [
            'value' => '',
            'text' => 'ธนาคาร',
        ];

        $responses = collect(app('Gametech\Payment\Repositories\BankRepository')->all()->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'value' => $item->code,
                'text' => $item->name_th,
            ];

        })->prepend($banks);

        $result['banks'] = $responses;

        return $this->sendResponseNew($result, 'complete');
    }

    public function loadRefer()
    {
        $banks = [
            'value' => '0',
            'text' => 'โปรดเลือก',
        ];

        $responses = collect(app('Gametech\Core\Repositories\ReferRepository')->where('enable', 'Y')->get()->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'value' => $item->code,
                'text' => $item->name,
            ];

        })->prepend($banks);

        $result['refers'] = $responses;

        return $this->sendResponseNew($result, 'complete');
    }

    public function loadAf(Request $request)
    {
        $code = $request->input('af');
        //        dd($af);
        $member = app('Gametech\Member\Repositories\MemberRepository')->where('user_name', $code)->where('enable', 'Y')->first();
        if (! $member) {
            $data['name'] = '';
            $data['code'] = 0;

            return $this->sendError('ไม่พบข้อมูล ผู้แนะนำ', 200);
        }

        $data['name'] = $member['name'];
        $data['code'] = $member['code'];

        return $this->sendResponse($data, 'complete');
    }

    public function loadBankAccount()
    {
        $banks = [
            'value' => '',
            'text' => 'เลือกช่องทางที่ฝาก',
        ];

        //        $responses = app('Gametech\Payment\Repositories\BankRepository')->getBankInAccountAll()->toArray();
        $responses = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountInAllNew()->toArray();

        //        dd($responses);

        $responses = collect($responses)->map(function ($items) {
            $item = (object) $items;

            //            dd($item);
            return [
                'value' => $item->code,
                'text' => $item->bank['name_th'].' ['.$item->acc_no.']',
            ];

        })->prepend($banks);

        // dd($responses);

        $result['banks'] = $responses;

        return $this->sendResponseNew($result, 'complete');
    }

    public function create(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $data = json_decode($request['data'], true);
        $chk = $this->memberRepository->findOneWhere(['user_name' => $data['user_name']]);
        if ($chk) {
            return $this->sendError('ข้อมูลมีในระบบแล้ว', 200);
        }

        $agent = $data['agent'];
        unset($data['agent']);

        $bank_code = $data['bank_code'];
        if ($bank_code != 18) {
            $acc_no = Str::of($data['acc_no'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
            $data['acc_no'] = $acc_no;
        }

        if ($bank_code == 18) {
            $acc_check = '';
        } elseif ($bank_code == 4) {
            $acc_check = substr($acc_no, -4);
        } else {
            $acc_check = substr($acc_no, -6);
        }

        if ($bank_code == 18) {
            $acc_bay = '';
        } else {
            $acc_bay = substr($acc_no, -7);
        }

        $data['confirm'] = 'Y';
        $data['date_regis'] = now()->toDateString();
        $data['acc_check'] = $acc_check;
        $data['acc_bay'] = $acc_bay;

        if (isset($data['wallet_id'])) {
            $data['wallet_id'] = $data['tel'];
        }

        $data['name'] = $data['firstname'].' '.$data['lastname'];
        $data['password'] = Hash::make($data['user_pass']);

        $data['user_create'] = $user;
        $data['user_update'] = $user;

        $wallet_id = trim($data['wallet_id']);

        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,14',
                Rule::unique('members', 'acc_no')->where(function ($query) use ($bank_code) {
                    return $query->where('bank_code', $bank_code);
                }),
            ],
            'wallet_id' => [
                'required',
                Rule::unique('members', 'wallet_id')->where(function ($query) use ($wallet_id) {
                    return $query->where('wallet_id', $wallet_id);
                }),
            ],
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'tel' => 'required|numeric|unique:members,tel',
            'user_name' => 'required|string|different:tel|unique:members,user_name|max:20|regex:/^[a-z][a-z0-9]*$/',
            'bank_code' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            $message = implode(', ', $errors->all());

            return $this->sendError($message, 200);

        }

        $response = $this->memberRepository->create($data);
        if (! $response->code) {
            return $this->sendError('ไม่สามารถบันทุึกข้อมูลได้', 200);
        }

        if ($agent == 'Y') {
            $member = $this->memberRepository->find($response->code);
            $res = $this->gameUserRepository->addGameUser(1, $member->code, $member, false);
            if ($res['success'] !== true) {
                $this->memberRepository->delete($response->code);

                return $this->sendError('ไม่สามารถเพิ่มข้อมูลที่ Agent ได้', 200);
            }

        }

        $datasub['member_code'] = $response->code;
        $datasub['game_code'] = 1;
        $datasub['user_name'] = $data['user_name'];
        $datasub['user_pass'] = $data['user_pass'];

        $this->gameUserRepository->create($datasub);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update_($id, Request $request)
    {

        $google2fa = new Google2FA;

        $acc_no = '';
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'], true);

        //        $secret = $data['one_time_password'];

        if ($this->user()->superadmin == 'N') {

            //            $valid = $google2fa->verifyKey($this->user()->google2fa_secret, $secret);
            //            if (!$valid) {
            //                return $this->sendError('รหัสยืนยันไม่ถูกต้อง', 200);
            //            }
        }

        //        unset($data['one_time_password']);

        $bank_code = $data['bank_code'];
        if ($bank_code != 18) {
            $acc_no = Str::of($data['acc_no'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
            $data['acc_no'] = $acc_no;
        }

        if ($bank_code == 18) {
            $acc_check = '';
        } elseif ($bank_code == 4) {
            $acc_check = substr($acc_no, -4);
        } else {
            $acc_check = substr($acc_no, -6);
        }

        if ($bank_code == 18) {
            $acc_bay = '';
        } else {
            $acc_bay = substr($acc_no, -7);
        }

        $data['acc_check'] = $acc_check;
        $data['acc_bay'] = $acc_bay;

        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,15',
                Rule::unique('members', 'acc_no')->ignore($id, 'code')->where(function ($query) use ($bank_code) {
                    return $query->where('bank_code', $bank_code);
                }),
            ],
            'tel' => [
                'required',
                'digits:10',
                Rule::unique('members', 'tel')->ignore($id, 'code'),
            ],
            'user_name' => [
                'required',
                'digits:10',
                Rule::unique('members', 'user_name')->ignore($id, 'code'),
            ],

        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();

            return $this->sendError($errors->messages(), 200);
        }

        $chk = $this->memberRepository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ($data['wallet_id'] == '') {
            $data['wallet_id'] = $chk->tel;
        }

        if ($data['user_pass']) {
            $data['password'] = Hash::make($data['user_pass']);
        } else {
            unset($data['user_pass']);
        }
        $data['firstname'] = strip_tags($data['firstname']);
        $data['lastname'] = strip_tags($data['lastname']);
        $data['tel'] = strip_tags($data['tel']);
        $data['wallet_id'] = strip_tags($data['wallet_id']);

        $data['name'] = $data['firstname'].' '.$data['lastname'];
        $data['user_update'] = $user;
        $this->memberRepository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update($id, Request $request)
    {
        $google2fa = new Google2FA;

        $acc_no = '';
        $user = $this->user()->name.' '.$this->user()->surname;

        // รองรับทั้งรูปแบบเดิม (ส่ง data เป็น JSON string)
        // และรูปแบบใหม่ (ส่ง JSON body ตรง ๆ)
        $data = [];
        $rawData = $request->input('data');
        if (is_string($rawData) && $rawData !== '') {
            $decoded = json_decode($rawData, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        } elseif (is_array($rawData)) {
            $data = $rawData;
        }

        if ($data === []) {
            $data = $request->except(['data']);
        }

        if ($data === []) {
            $rawPayload = json_decode((string) $request->getContent(), true);
            if (is_array($rawPayload)) {
                $data = $rawPayload;
            }
        }

        // ===== 2FA (เปิดใช้เมื่อพร้อม) =====
        // if ($this->user()->superadmin == 'N') {
        //     $secret = $data['one_time_password'] ?? null;
        //     $valid  = $google2fa->verifyKey($this->user()->google2fa_secret, $secret);
        //     if (!$valid) {
        //         return $this->sendError('รหัสยืนยันไม่ถูกต้อง', 200);
        //     }
        // }
        // unset($data['one_time_password']);

        // ---- Normalize เบื้องต้นให้ชัดก่อน validate ----
        $bank_code = (int) ($data['bank_code'] ?? 0);

        // acc_no: เหลือเลขล้วน ถ้าไม่ใช่ bank_code 18
        if ($bank_code !== 18) {
            $acc_no = Str::of($data['acc_no'] ?? '')
                ->replaceMatches('/[^0-9]++/', '')
                ->trim()
                ->__toString();
            $data['acc_no'] = $acc_no;
        } else {
            $acc_no = (string) ($data['acc_no'] ?? '');
        }

        // tel: บังคับเป็นเลข 10 หลัก (กันเคสมีขีด/เว้นวรรค/ตัวอักษรพิเศษ)
        $data['tel'] = Str::of($data['tel'] ?? '')
            ->replaceMatches('/[^0-9]++/', '')
            ->trim()
            ->__toString();

        // user_name: คงค่าตามที่ส่งมา (เช่น boatjunior) ไม่แปลงเป็นเลข
        $data['user_name'] = Str::of($data['user_name'] ?? '')
            ->trim()
            ->__toString();

        // acc_check / acc_bay ตามกติกาเดิม
        if ($bank_code === 18) {
            $acc_check = '';
        } elseif ($bank_code === 4) {
            $acc_check = substr($acc_no, -4);
        } else {
            $acc_check = substr($acc_no, -6);
        }
        $acc_bay = ($bank_code === 18) ? '' : substr($acc_no, -7);

        $data['acc_check'] = $acc_check ?: '';
        $data['acc_bay'] = $acc_bay ?: '';

        // ===== Rules (ไม่ยุ่งกับ deleted_at เพราะไม่มีคอลัมน์นี้) =====
        $accNoRules = ['required', 'digits_between:1,15'];
        $accNoRules[] = Rule::unique('members', 'acc_no')
            ->ignore($id, 'code')
            ->where(function ($q) use ($bank_code) {
                return $q->where('bank_code', $bank_code);
            });

        $telRules = [
            'required',
            'digits:10',
            Rule::unique('members', 'tel')
                ->ignore($id, 'code'),
        ];

        $userNameRules = [
            'required',
            'string',
            'max:20',
            Rule::unique('members', 'user_name')
                ->ignore($id, 'code'),
        ];

        $validator = Validator::make($data, [
            'acc_no' => $accNoRules,
            'tel' => $telRules,
            'user_name' => $userNameRules,
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->messages(), 200);
        }

        // ===== หา record ปัจจุบันด้วย code =====
        $chk = $this->memberRepository->find($id); // โมเดลควร $primaryKey = 'code'
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        // ===== ค่าเริ่มต้น & sanitize =====
        $data['firstname'] = strip_tags($data['firstname'] ?? '');
        $data['lastname'] = strip_tags($data['lastname'] ?? '');
        $data['firstname_addon'] = strip_tags($data['firstname_addon'] ?? '');
        $data['lastname_addon'] = strip_tags($data['lastname_addon'] ?? '');
        $data['tel'] = trim(strip_tags($data['tel'] ?? ''));
        $data['wallet_id'] = trim(strip_tags($data['wallet_id'] ?? ''));

        if ($data['wallet_id'] === '') {
            $data['wallet_id'] = $chk->tel; // fallback
        }

        if (! empty($data['user_pass'])) {
            $data['password'] = Hash::make($data['user_pass']);
        } else {
            unset($data['user_pass']);
        }

        $data['name'] = trim(($data['firstname'] ?? '').' '.($data['lastname'] ?? ''));
        $data['name_addon'] = trim(($data['firstname_addon'] ?? '').' '.($data['lastname_addon'] ?? ''));
        $data['user_update'] = $user;

        // ===== อัปเดต =====
        $this->memberRepository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->memberRepository->find($id);
        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

    }

    public function remark(Request $request)
    {
        $id = $id = $request->input('id');
        $responses = collect($this->memberRemarkRepository->loadRemark($id));

        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i:s'),
                'remark' => $item->remark,
                'emp_code' => (is_null($item->emp) ? '' : $item->emp->user_name),
                'action' => '<button type="button" class="btn btn-warning btn-xs icon-only" onclick="delSub('.$item->code.')"><i class="fa fa-times"></i></button>',

            ];

        });

        $result['list'] = $responses;

        return $this->sendResponseNew($result, 'complete');
    }

    public function createsub(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $data = $request->input('data');

        $data['member_code'] = $id;
        $data['emp_code'] = $this->id();
        $data['user_create'] = $user;
        $data['user_update'] = $user;

        $this->memberRemarkRepository->create($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function destroysub(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->memberRemarkRepository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->memberRemarkRepository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function balance(Request $request)
    {
        $id = $request->input('game_code');
        $member_code = $request->input('member_code');

        $item_list = $this->gameUserRepository->getOneUser($member_code, $id, true);
        if ($item_list['success'] != true) {

        }

        $item_list = $item_list['data'];
        $item = $item_list;

        $game = [
            'status' => '<span class="text-success">game</span>',
            'game_id' => $item['game']['id'],
            'game' => $item['game']['name'],
            'member_code' => $item['member_code'],
            'user_name' => $item['user_name'],
            'user_pass' => $item['user_pass'],
            'balance' => $item['balance'],
            'promotion' => (! is_null($item['promotion']) ? $item['promotion']['name_th'] : '-'),
            'turn' => ($item['pro_code'] > 0 ? $item['turnpro'] : '-'),
            'amount_balance' => ($item['pro_code'] > 0 ? $item['amount_balance'] : '-'),
            'withdraw_limit' => ($item['pro_code'] > 0 ? ($item['withdraw_limit'] > 0 ? $item['withdraw_limit'] : '-') : '-'),
            'action' => '<button class="btn btn-xs icon-only '.($item['enable'] == 'Y' ? 'btn-warning' : 'btn-danger').'" onclick="editdatasub('.$item['code'].','."'".core()->flip($item['enable'])."'".','."'enable'".')">'.($item['enable'] == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-trash"></i>').'</button>',
            'changepass' => '<button class="btn btn-xs icon-only btn-info" onclick="changegamepass('.$item['code'].')"><i class="fa fa-edit"></i></button>',

        ];

        $result['list'] = $game;

        return $this->sendResponseNew($result, 'ดำเนินการเสร็จสิ้น');

    }

    public function changegamepass(Request $request)
    {
        $id = $request->input('id');
        $password = $request->input('password');
        $game_user = $this->gameUserRepository->find($id);
        if (! $game_user) {
            $result['success'] = false;
            $result['reload'] = false;
            $result['msg'] = 'ไม่พบข้อมูลรหัสเกมของ สมาชิก';

            return $this->sendResponseNew($result, 'ไม่พบข้อมูลรหัสเกมของ สมาชิก');

        }

        $game = $this->gameRepository->find($game_user->game_code);
        if (! $game) {
            $result['success'] = false;
            $result['reload'] = false;
            $result['msg'] = 'ไม่พบข้อมูลเกม';

            return $this->sendResponseNew($result, 'ไม่พบข้อมูลเกม');

        }

        $member = $this->memberRepository->find($game_user->member_code);
        if (! $member) {
            $result['success'] = false;
            $result['reload'] = false;
            $result['msg'] = 'ไม่พบข้อมูลสมาชิก';

            return $this->sendResponseNew($result, 'ไม่พบข้อมูลสมาชิก');

        }

        $user = collect($member)->toArray();
        $user_pass = $password;

        $result = $this->gameUserRepository->changeGamePass($game->code, $game_user['code'], [
            'user_pass' => $user_pass,
            'user_name' => $game_user['user_name'],
            'name' => $user['name'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'gender' => $user['gender'],
            'birth_day' => $user['birth_day'],
            'date_regis' => $user['date_regis'],
        ]);

        if ($result['success'] == true) {

            $result['reload'] = true;

        } else {
            $result['reload'] = false;
        }

        return $this->sendResponseNew($result, $result['msg']);

    }

    public function export(MemberDataTable $dataTable)
    {
        return $dataTable->myexport();
    }

    public function loadEvents(Request $request)
    {
        $data = app('Gametech\Promotion\Repositories\PromotionRepository')->whereIn('id', ['pro_cashback', 'pro_spin', 'pro_faststart'])->where('enable', 'Y')->get();
        //        dd($data);

        //        dd($cacheP, $cached_provider);

        //        $data = collect($provider)->pluck('lobbyName', 'lobbyId');
        $dropdown['events'] = collect($data)
            ->pluck('name_th', 'code')
            ->map(fn ($name, $id) => ['value' => $id, 'text' => $name])
            ->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)
            ->prepend(['value' => '', 'text' => '== เลือกโปร =='])
            ->values()
            ->toArray();
        //        if (!$data) {
        //            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        //        }

        return $this->sendResponseNew($dropdown, 'ดำเนินการเสร็จสิ้น');
    }

    public function addEvent(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
            'event_code' => ['required', 'integer'],
            'remark_admin' => ['nullable', 'string'],
        ]);

        $id = $request->input('id');
        $amount = $request->input('amount');
        $remark = $request->input('remark_admin');
        $event = $request->input('event_code');

        $member = $this->memberRepository->find($id);

        if ($amount < 1) {
            return $this->sendError('ยอดเงินไม่ถูกต้อง', 200);
        }

        $data = [
            'refer_code' => $id,
            'refer_table' => 'members',
            'remark' => $remark,
            'amount' => $amount,
            'event' => $event,
            'member_code' => $id,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name.' '.$this->user()->surname,
        ];
        $response = $this->memberCreditLogRepository->setEvent($data);

        if ($response) {
            return $this->sendSuccess('ดำเนินการ  เรียบร้อยแล้ว');
        } else {
            return $this->sendError('ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง', 200);
        }

    }
}
