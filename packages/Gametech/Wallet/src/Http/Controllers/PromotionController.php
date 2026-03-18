<?php

namespace Gametech\Wallet\Http\Controllers;

use App\Support\Concerns\LogsMemberEvent;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;
use Gametech\Member\Models\MemberSelectPro;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BillRepository;
use Gametech\Promotion\Repositories\PromotionContentRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

class PromotionController extends AppBaseController
{
    use ActivityLoggerUser;

    use LogsMemberEvent;
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    private $promotionRepository;

    private $proContentRepository;

    private $memberRepository;

    private $billRepository;

    private $bankPaymentRepository;

    private $gameUserRepository;

    /**
     * Create a new Repository instance.
     */
    public function __construct(
        PromotionRepository $promotionRepo,
        MemberRepository $memberRepo,
        PromotionContentRepository $proContentRepo,
        BillRepository $billRepo,
        BankPaymentRepository $bankPaymentRepo,
        GameUserRepository $gameUserRepo
    ) {
        $this->middleware('customer')->except(['show']);

        $this->_config = request('_config');

        $this->promotionRepository = $promotionRepo;

        $this->memberRepository = $memberRepo;

        $this->proContentRepository = $proContentRepo;

        $this->billRepository = $billRepo;

        $this->bankPaymentRepository = $bankPaymentRepo;

        $this->gameUserRepository = $gameUserRepo;
    }

    public function index()
    {
        $pro = false;
        $pro_limit = 0;
        $promotions = [];

        $promotions = $this->promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]]);
        $promotions = collect($promotions)->map(function ($items, $key) {

            $items['filepic'] = Storage::url('promotion_img/'.$items['filepic']);

            return $items;
        });
        

        $pro_contents = $this->proContentRepository->orderBy('sort')->findWhere(['enable' => 'Y', ['code', '<>', 0]]);
        $pro_contents = collect($pro_contents)->map(function ($items, $key) {

            $items['filepic'] = Storage::url('procontent_img/'.$items['filepic']);

            return $items;
        });
       
       
        $profile = $this->user();

        $type = app('Gametech\Game\Repositories\GameContentRepository')->findOneByField('id', 'PROMOTION');

        return view($this->_config['view'], compact('promotions', 'pro_contents', 'pro_limit', 'profile', 'type'));

    }

    public function loadPromotion()
    {

        $pro = false;
        $pro_limit = 0;
        $config = core()->getConfigData();
        if ($config->seamless === 'Y') {
            if (($config->pro_onoff == 'Y')) {
                if ($config->pro_wallet == 'Y') {
                    $pro = true;
                }
            }

            if ($this->user()->promotion == 'N') {
                $pro = false;
            }

            if ($pro) {

                $pro_limit = $this->memberRepository->getPro($this->id());
                if ($pro_limit > 0) {
                    $promotions = $this->promotionRepository->loadPromotion($this->id())->toArray();
                } else {
                    $promotions = $this->promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]])->toArray();
                }
            } else {
                $promotions = $this->promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]])->toArray();

            }

        } else {
            $promotions = $this->promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]])->toArray();

        }

        $pro_contents = $this->proContentRepository->orderBy('sort')->findWhere(['enable' => 'Y', ['code', '<>', 0]])->toArray();

        //        $pros = collect($pro_contents)->prepend($promotions);
        $pro_contents = collect($pro_contents)->map(function ($items, $key) {

            $items['filepic'] = Storage::url('procontent_img/'.$items['filepic']);

            return $items;
        });

        $promotions = collect($promotions)->map(function ($items, $key) {

            $items['filepic'] = Storage::url('promotion_img/'.$items['filepic']);

            return $items;
        });

        $pros = collect($pro_contents)->merge($promotions);

        $result['promotions'] = $pros;
        $result['getpro'] = (($pro_limit > 0) ? true : false);

        return $this->sendResponse($result, 'Complete');
    }

    public function show()
    {
        $pro = false;
        $pro_limit = 0;
        $promotions = [];

        if(auth('customer')->check()){
            return redirect()->route('customer.promotion.index');
        }

        $type = app('Gametech\Game\Repositories\GameTypeRepository')->findOneByField('id', 'PROMOTION');

        $promotions = $this->promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]]);

        $pro_contents = $this->proContentRepository->orderBy('sort')->findWhere(['enable' => 'Y', ['code', '<>', 0]]);

        return view($this->_config['view'], compact('promotions', 'pro_contents', 'pro_limit', 'type'));

    }

    public function indextest()
    {
        $pro = false;
        $pro_limit = 0;
        $promotions = [];

        $config = core()->getConfigData();
        if ($config->seamless == 'Y') {
            if (($config->pro_onoff == 'Y')) {
                if ($config->pro_wallet == 'Y') {
                    $pro = true;
                }
            }

            if ($this->user()->promotion == 'N') {
                $pro = false;
            }

            if ($pro) {

                $pro_limit = $this->memberRepository->getPro($this->id());
                if ($pro_limit > 0) {
                    $promotions = $this->promotionRepository->loadPromotiontest($this->id());
                } else {
                    $promotions = $this->promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]]);
                }
            } else {
                $promotions = $this->promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]]);

            }

        } else {
            $promotions = $this->promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]]);

        }

        $pro_contents = $this->proContentRepository->orderBy('sort')->findWhere(['enable' => 'Y', ['code', '<>', 0]]);

        return view($this->_config['view'], compact('promotions', 'pro_contents', 'pro_limit'));

    }

    public function store(Request $request)
    {

        $promotion = [];
        $datenow = now();
        $user = $this->user();
        $promotion_id = $request->input('promotion');
        $amount = $this->memberRepository->getPro($user->code);
        if ($amount) {

            $promotion = $this->promotionRepository->checkSelectPro($promotion_id, $user->code, $amount, $datenow);

            if ($promotion['bonus'] == 0) {
                session()->flash('error', 'ขออภัยค่ะ คุณไม่สามารถรับโปรโมชั่นนี้ได้ หรือ เงื่อนไขในการรับไม่ถูกต้อง ตรวจสอบยอดโบนัสที่ได้รับ เป็น 0');
                ActivityLoggerUser::activity('Get Pro ID : '.$promotion_id, 'ขออภัยค่ะ คุณไม่สามารถรับโปรโมชั่นนี้ได้ หรือ เงื่อนไขในการรับไม่ถูกต้อง คำนวนได้โบนัส 0 บาท', $user->code);

                return redirect()->route('customer.promotion.index');
            }

            $promotion['amount'] = $amount;
            $promotion['member_code'] = $user->code;
            $response = $this->billRepository->getPro($promotion);
            if ($response['success'] === true) {
                $bills = $response['data'];
                session()->flash('success', 'ได้รับโบนัสจำนวน '.$bills->credit_bonus);
                ActivityLoggerUser::activity('Get Pro ID : '.$promotion_id, 'ได้รับโบนัสจำนวน '.$bills->credit_bonus, $user->code);
            } else {
                ActivityLoggerUser::activity('Get Pro ID : '.$promotion_id, $response['msg'], $user->code);
                session()->flash('error', $response['msg']);
            }

            return redirect()->route('customer.promotion.index');
        } else {
            ActivityLoggerUser::activity('Get Pro ID : '.$promotion_id, 'ขออภัยค่ะ คุณไม่สามารถรับโปรโมชั่นนี้ได้ เนื่องจากไม่พบ ยอดฝาก', $user->code);
            session()->flash('error', 'ขออภัยค่ะ คุณไม่สามารถรับโปรโมชั่นนี้ได้');

            return redirect()->route('customer.promotion.index');
        }

    }

    public function store_api(Request $request)
    {

        $promotion = [];
        $datenow = now();
        $user = $this->user();
        $promotion_id = $request->input('promotion');
        $amount = $this->memberRepository->getPro($user->code);
        if ($amount) {

            $promotion = $this->promotionRepository->checkSelectPro($promotion_id, $user->code, $amount, $datenow);

            if ($promotion['bonus'] == 0) {

                return $this->sendError(Lang::get('app.promotion.cannot'), 200);
            }

            $promotion['amount'] = $amount;
            $promotion['member_code'] = $user->code;
            $response = $this->billRepository->getPro($promotion);
            if ($response['success'] === true) {
                $bills = $response['data'];

                return $this->sendSuccess(Lang::get('app.promotion.cannot').$bills->credit_bonus);

            } else {
                return $this->sendError($response['msg'], 200);

            }

        } else {

            return $this->sendError('ขออภัยค่ะ คุณไม่สามารถรับโปรโมชั่นนี้ได้', 200);
        }

    }

    public function cancel()
    {
        $user = $this->user();

        $amount = $this->memberRepository->getPro($user->code);
        if ($amount > 0) {
            $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $user->code]);
            if (! $gameuser) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 200);
            }

            //            $gameuser->withdraw_limit_amount += ($gameuser->withdraw_limit_rate * $amount);
            //            $gameuser->amount_balance += ($amount * $gameuser->turnpro);
            //            $gameuser->save();

            $this->bankPaymentRepository->where('member_topup', $user->code)->where('pro_check', 'N')->update([
                'pro_check' => 'Y',
                'user_update' => $user->name,
            ]);



            app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
                'ip' => request()->ip(),
                'credit_type' => 'D',
                'balance_before' => $user->balance,
                'balance_after' => $user->balance,
                'credit' => 0,
                'total' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $user->balance,
                'credit_after' => $user->balance,
                'pro_code' => 0,
                'bank_code' => 0,
                'auto' => 'N',
                'enable' => 'Y',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
                'refer_code' => 0,
                'refer_table' => 'blank',
                'remark' => 'กดปุ่ม ไม่รับโปร บนหน้าต่าง POPUP แจ้งเตือนการได้รับสิทธิ์ (ยอดเติม '.$amount.')',
                'kind' => 'OTHER',
                'amount' => 0,
                'amount_balance' => $gameuser->amount_balance,
                'withdraw_limit' => $gameuser->withdraw_limit,
                'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                'method' => 'D',
                'member_code' => $user->code,
            ]);

        }

        ActivityLoggerUser::activity('Not Get Pro', 'กดปุ่ม ไม่รับโปร บนหน้าต่าง POPUP แจ้งเตือนการได้รับสิทธิ์ (ยอดเติม '.$amount.')', $user->code);

        return $this->sendSuccess('ดำเนินการสำเร็จ');

    }

    public function selectPromotion_(Request $request)
    {
        $config = core()->getConfigData();
        $pass = false;
        $user = $this->user();
        $promotion_id = $request->input('promotion');
        $promotion = $this->promotionRepository->findOneWhere(['code' => $promotion_id]);
        $this->logMemberEvent($user, 'กดรับโปรก่อนเติมเงิน โปร '.$promotion->name_th);
        switch ($promotion->id) {
            case 'pro_newuser':
                if ($user->status_pro != 1) {
                    $pass = true;
                }
                break;
            case 'pro_firstday':
                $count = $this->promotionRepository->checkProFirstDay($user->code);
                if (! $count) {
                    $pass = true;
                }
                break;
            case 'pro_allbonus':
                $pass = true;
                break;
            case 'pro_oneonly_day':
                $count = $this->promotionRepository->checkProOneOnlyDay($user->code,$promotion->id);
                if (! $count) {
                    $pass = true;
                }
                break;
            case 'pro_oneonly_time':
                $count = $this->promotionRepository->checkProOneOnlyTime($user->code,$promotion->id);
                $this->logMemberEvent($user, 'pro_oneonly_time '.$count);
                if (! $count) {
                    $pass = true;
                }
                break;

        }

        $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $user->code]);
        if($user->balance >= $config->pro_reset){
            $this->logMemberEvent($user, 'ไม่ผ่านเงื่อนไข กดรับโปร '.$promotion->name_th .' แต่ ยอดเงินมากกว่า ยอดโปรรีเซต อดรับ');

            session()->flash('error', Lang::get('app.promotion.over_balance').$config->pro_reset);

            return redirect()->route('customer.promotion.index');
        }

        if ($pass) {

            MemberSelectPro::updateOrCreate(
                ['member_code' => $user->code],
                ['pro_code' => $promotion->code, 'pro_name' => $promotion->name_th, 'pro_id' => $promotion->id]
            );

            $this->logMemberEvent($user, 'ผ่านเงื่อนไข กดรับโปร '.$promotion->name_th .' รอเติมเงิน');


            session()->flash('success', Lang::get('app.promotion.pass'));

            return redirect()->route('customer.promotion.index');

        } else {
            MemberSelectPro::where('member_code', $user->code)->delete();

            $this->logMemberEvent($user, 'ไม่ผ่านเงื่อนไข รับโปร');

            session()->flash('error', Lang::get('app.promotion.cannot'));

            return redirect()->route('customer.promotion.index');
        }

    }

    public function selectPromotion(Request $request)
    {
        $config = core()->getConfigData();
        $user   = $this->user();
        $promotion_id = $request->input('promotion');

        if (!$promotion_id) {
            return $this->sendError('ไม่พบรหัสโปรโมชัน', 422);
        }

        $promotion = $this->promotionRepository->findOneWhere(['code' => $promotion_id]);
        if (!$promotion) {
            return $this->sendError('ไม่พบโปรโมชันนี้', 404);
        }

        // log เหตุการณ์
        $this->logMemberEvent($user, 'กดรับโปรก่อนเติมเงิน โปร '.$promotion->name_th);
        $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $user->code , 'enable' => 'Y']);

        // ตรวจ balance เกิน pro_reset ไหม
//        if (($config->seamless ?? 'N') === 'Y') {
            if ($gameuser->balance >= ($config->pro_reset ?? 0)) {
                $this->logMemberEvent($user, 'ไม่ผ่านเงื่อนไข กดรับโปร '.$promotion->name_th .' แต่ ยอดเงินมากกว่า ยอดโปรรีเซต อดรับ');
                return $this->sendError(Lang::get('app.promotion.over_balance').($config->pro_reset ?? 0), 200);
            }
//        }

        // เงื่อนไขเฉพาะโปร (ยกมาจาก selectPromotion เดิม)
        $pass = false;
        switch ($promotion->id) {
            case 'pro_newuser':
                if ($user->status_pro != 1) $pass = true;
                break;

            case 'pro_firstday':
                $count = $this->promotionRepository->checkProFirstDay($user->code);
                if (!$count) $pass = true;
                break;

            case 'pro_allbonus':
                $pass = true;
                break;

            case 'pro_oneonly_day':
                $count = $this->promotionRepository->checkProOneOnlyDay($user->code, $promotion->code);
                if (!$count) $pass = true;
                break;

            case 'pro_oneonly_time':
                $count = $this->promotionRepository->checkProOneOnlyTime($user->code, $promotion->code);
                if (!$count) $pass = true;
                break;
        }

        if ($pass) {
            \Gametech\Member\Models\MemberSelectPro::updateOrCreate(
                ['member_code' => $user->code],
                ['pro_code' => $promotion->code, 'pro_name' => $promotion->name_th, 'pro_id' => $promotion->id]
            );

            $this->logMemberEvent($user, 'ผ่านเงื่อนไข กดรับโปร '.$promotion->name_th .' รอเติมเงิน');

            return $this->sendResponse([
                'promotion' => $promotion->code,
            ], Lang::get('app.promotion.pass'));
        } else {
            \Gametech\Member\Models\MemberSelectPro::where('member_code', $user->code)->delete();

            $this->logMemberEvent($user, 'ไม่ผ่านเงื่อนไข รับโปร');

            return $this->sendError(Lang::get('app.promotion.cannot'), 200);
        }
    }

    public function deselectPromotion(Request $request)
    {
        $pass = false;
        $user = $this->user();

        $this->logMemberEvent($user, 'กดยกเลิกโปรที่รับ แล้ว');
        MemberSelectPro::where('member_code', $user->code)->delete();
        return $this->sendSuccess(Lang::get('app.promotion.deselect'));

    }
}
