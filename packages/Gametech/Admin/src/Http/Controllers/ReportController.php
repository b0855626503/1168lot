<?php

namespace Gametech\Admin\Http\Controllers;

use App\Http\Controllers\AppBaseController;
use Gametech\Admin\DataTables\RpAllLogDataTable;
use Gametech\Admin\DataTables\RpAllLogFreeDataTable;
use Gametech\Admin\DataTables\RpBillDataTable;
use Gametech\Admin\DataTables\RpBillFreeDataTable;
use Gametech\Admin\DataTables\RpBillTurnDataTable;
use Gametech\Admin\DataTables\RpCashbackDataTable;
use Gametech\Admin\DataTables\RpCreditDataTable;
use Gametech\Admin\DataTables\RpCreditOldDataTable;
use Gametech\Admin\DataTables\RpDepositDataTable;
use Gametech\Admin\DataTables\RpFirstTimeDataTable;
use Gametech\Admin\DataTables\RpLogCashbackDataTable;
use Gametech\Admin\DataTables\RpLogDataTable;
use Gametech\Admin\DataTables\RpLogIcDataTable;
use Gametech\Admin\DataTables\RpMemberEditDataTable;
use Gametech\Admin\DataTables\RpMemberIcDataTable;
use Gametech\Admin\DataTables\RpMemberOnlineDataTable;
use Gametech\Admin\DataTables\RpMemberProDataTable;
use Gametech\Admin\DataTables\RpMemberRefDataTable;
use Gametech\Admin\DataTables\RpNoRefillDataTable;
use Gametech\Admin\DataTables\RpRecommenderDataTable;
use Gametech\Admin\DataTables\RpSetDiamondDataTable;
use Gametech\Admin\DataTables\RpSetPointDataTable;
use Gametech\Admin\DataTables\RpSmLogDataTable;
use Gametech\Admin\DataTables\RpSmPaymentDataTable;
use Gametech\Admin\DataTables\RpSmSetWalletDataTable;
use Gametech\Admin\DataTables\RpSmWithdrawDataTable;
use Gametech\Admin\DataTables\RpSmWithdrawSeamlessDataTable;
use Gametech\Admin\DataTables\RpSpinDataTable;
use Gametech\Admin\DataTables\RpSponsorDataTable;
use Gametech\Admin\DataTables\RpStaffLogDataTable;
use Gametech\Admin\DataTables\RpSumGameDataTable;
use Gametech\Admin\DataTables\RpSumPaymentDataTable;
use Gametech\Admin\DataTables\RpSumStatDataTable;
use Gametech\Admin\DataTables\RpTopPaymentDataTable;
use Gametech\Admin\DataTables\RpTopProDataTable;
use Gametech\Admin\DataTables\RpTopWithdrawDataTable;
use Gametech\Admin\DataTables\RpUserLogDataTable;
use Gametech\Admin\DataTables\RpWalletDataTable;
use Gametech\Admin\DataTables\RpWithdrawDataTable;
use Gametech\Admin\DataTables\RpWithdrawFreeDataTable;
use Gametech\Admin\DataTables\RpWithdrawSeamlessDataTable;
use Gametech\Admin\DataTables\RpWithdrawSeamlessFreeDataTable;
use Gametech\Admin\DataTables\TransactionDataTable;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Payment\Repositories\BankRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mozammil\Censor\Replacers\StarReplacer;


class ReportController extends AppBaseController
{
    protected $_config;

    protected $gameRepository;

    protected $bankRepository;

    protected $promotionRepository;

    public function __construct(
        GameRepository $gameRepo,
        PromotionRepository $promotionRepo,
        BankRepository $bankRepository
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->gameRepository = $gameRepo;
        $this->promotionRepository = $promotionRepo;
        $this->bankRepository = $bankRepository;
    }

    public function rp_log(RpLogDataTable $rpLogDataTable)
    {
        return $rpLogDataTable->render($this->_config['view']);
    }


    public function rp_log_cashback(RpLogCashbackDataTable $rpLogCashbackDataTable)
    {
        return $rpLogCashbackDataTable->render($this->_config['view']);
    }

    public function rp_log_ic(RpLogIcDataTable $rpLogIcDataTable)
    {
        return $rpLogIcDataTable->render($this->_config['view']);
    }

    public function rp_wallet(RpWalletDataTable $rpWalletDataTable)
    {
        return $rpWalletDataTable->render($this->_config['view']);
    }

    public function rp_setpoint(RpSetPointDataTable $rpSetPointDataTable)
    {
        return $rpSetPointDataTable->render($this->_config['view']);
    }

    public function rp_setdiamond(RpSetDiamondDataTable $rpSetDiamondDataTable)
    {
        return $rpSetDiamondDataTable->render($this->_config['view']);
    }

    public function rp_credit(RpCreditDataTable $rpCreditDataTable)
    {
        return $rpCreditDataTable->render($this->_config['view']);
    }

    public function rp_credit_old(RpCreditOldDataTable $rpCreditOldDataTable)
    {
        return $rpCreditOldDataTable->render($this->_config['view']);
    }

    public function rp_member_ref(RpMemberRefDataTable $rpMemberRefDataTable)
    {
        return $rpMemberRefDataTable->render($this->_config['view']);
    }

    public function rp_bill(RpBillDataTable $rpBillDataTable)
    {
        $games = $this->gameRepository->findWhere(['enable' => 'Y'])->pluck('name', 'code');
        $pros = $this->promotionRepository->findWhere(['use_wallet' => 'Y'])->whereIn('id', ['pro_newuser', 'pro_firstday', 'pro_allbonus'])->pluck('name_th', 'code');
        return $rpBillDataTable->render($this->_config['view'], ['games' => $games, 'pros' => $pros]);
    }

    public function rp_top_promotion(RpTopProDataTable $rpTopProDataTable)
    {
        $pros = $this->promotionRepository->findWhere(['enable' => 'Y'])->whereNotIn('id', ['pro_cashback', 'pro_ic','pro_spin'])->pluck('name_th', 'code');
        return $rpTopProDataTable->render($this->_config['view'], ['pros' => $pros]);
    }

    public function rp_online_behavior(RpMemberOnlineDataTable $rpMemberOnlineDataTable)
    {
        return $rpMemberOnlineDataTable->render($this->_config['view']);
    }

    public function rp_user_log(RpUserLogDataTable $rpUserLogDataTable)
    {
        return $rpUserLogDataTable->render($this->_config['view']);
    }

    public function rp_staff_log(RpStaffLogDataTable $rpStaffLogDataTable)
    {
        return $rpStaffLogDataTable->render($this->_config['view']);
    }

    public function rp_alllog(RpAllLogDataTable $rpAllLogDataTable)
    {
        $games = $this->gameRepository->findWhere(['enable' => 'Y'])->pluck('name', 'code');

        return $rpAllLogDataTable->render($this->_config['view'], ['games' => $games]);
    }

    public function rp_alllog_free(RpAllLogFreeDataTable $rpAllLogFreeDataTable)
    {
        $games = $this->gameRepository->findWhere(['enable' => 'Y'])->pluck('name', 'code');

        return $rpAllLogFreeDataTable->render($this->_config['view'], ['games' => $games]);
    }

    public function rp_sum_game(RpSumGameDataTable $rpSumGameDataTable)
    {
        return $rpSumGameDataTable->render($this->_config['view']);
    }

    public function rp_sum_stat(RpSumStatDataTable $rpSumStatDataTable)
    {
        return $rpSumStatDataTable->render($this->_config['view']);
    }

    public function rp_sum_payment(RpSumPaymentDataTable $rpSumPaymentDataTable)
    {
        return $rpSumPaymentDataTable->render($this->_config['view']);
    }

    public function rp_top_payment(RpTopPaymentDataTable $rpTopPaymentDataTable, RpTopWithdrawDataTable $rpTopWithdrawDataTable)
    {
        $wdDataTable = $rpTopWithdrawDataTable->html();
        return $rpTopPaymentDataTable->render($this->_config['view'],compact('wdDataTable'));
    }

    public function rp_sm_payment(RpSmPaymentDataTable $rpSmPaymentDataTable, RpSmWithdrawDataTable $rpSmWithdrawDataTable,RpSmWithdrawSeamlessDataTable $rpSmWithdrawSeamlessDataTable, RpSmSetWalletDataTable $rpSmSetWalletDataTable, RpSmLogDataTable $rpSmLogDataTable)
    {
        $config = core()->getConfigData();

        if($config->seamless == 'Y'){
            $wdDataTable = $rpSmWithdrawSeamlessDataTable->html();
        }else{
            $wdDataTable = $rpSmWithdrawDataTable->html();
        }
        $stDataTable = $rpSmSetWalletDataTable->html();
        $lgDataTable = $rpSmLogDataTable->html();

        return $rpSmPaymentDataTable->render($this->_config['view'],compact('wdDataTable','stDataTable','lgDataTable'));
    }

    public function rp_sm_withdraw(RpSmWithdrawDataTable $rpSmWithdrawDataTable)
    {
        return $rpSmWithdrawDataTable->render($this->_config['view']);
    }

    public function rp_sm_withdraw_seamless(RpSmWithdrawSeamlessDataTable $rpSmWithdrawSeamlessDataTable)
    {
        return $rpSmWithdrawSeamlessDataTable->render($this->_config['view']);
    }

    public function rp_sm_setwallet(RpSmSetWalletDataTable $rpSmSetWalletDataTable)
    {
        return $rpSmSetWalletDataTable->render($this->_config['view']);
    }

    public function rp_no_refill(RpNoRefillDataTable $rpNoRefillDataTable)
    {
        return $rpNoRefillDataTable->render($this->_config['view']);
    }

    public function rp_first_time(RpFirstTimeDataTable $rpFirstTimeDataTable)
    {
        return $rpFirstTimeDataTable->render($this->_config['view']);
    }

    public function rp_sm_log(RpSmLogDataTable $rpSmLogDataTable)
    {
        return $rpSmLogDataTable->render($this->_config['view']);
    }

    public function rp_top_withdraw(RpTopWithdrawDataTable $rpTopWithdrawDataTable)
    {

        $wdDataTable = $rpTopWithdrawDataTable->html();
        return $rpTopWithdrawDataTable->render($this->_config['view'],compact('wdDataTable'));
    }

    public function rp_bill_free(RpBillFreeDataTable $rpBillFreeDataTable)
    {
        $games = $this->gameRepository->findWhere(['enable' => 'Y'])->pluck('name', 'code');
        $pros = $this->promotionRepository->findWhere(['use_wallet' => 'Y'])->pluck('name_th', 'code');
        return $rpBillFreeDataTable->render($this->_config['view'], ['games' => $games, 'pros' => $pros]);
    }

    public function rp_withdraw(RpWithdrawDataTable $rpWithdrawDataTable)
    {
        $banks = $this->bankRepository->findWhere(['enable' => 'Y'])->pluck('name_th', 'code');
        $responses = app('Gametech\Payment\Repositories\BankAccountRepository')->out()->active()->with('bank')->orderBy('code')->get()->toArray();

        $banks_out = collect($responses)->map(function ($items) {
            $item = (object)$items;
//            dd($item);
            return [
                'code' => $item->code,
                'name' => $item->bank['name_th'] . ' [' . $item->acc_no . '] '.($item->enable == 'Y' ? 'ใช้งาน' : 'ปิด'),
            ];

        })->pluck('name', 'code');
        $banks = $this->bankRepository->findWhere(['enable' => 'Y'])->pluck('name_th', 'code');
        return $rpWithdrawDataTable->render($this->_config['view'],  ['banks' => $banks,'banks_out' => $banks_out]);
    }

    public function rp_withdraw_seamless(RpWithdrawSeamlessDataTable $rpWithdrawSeamlessDataTable)
    {
        $banks = $this->bankRepository->findWhere(['enable' => 'Y'])->pluck('name_th', 'code');
        $responses = app('Gametech\Payment\Repositories\BankAccountRepository')->out()->active()->with('bank')->orderBy('code')->get()->toArray();

        $banks_out = collect($responses)->map(function ($items) {
            $item = (object)$items;
//            dd($item);
            return [
                'code' => $item->code,
                'name' => $item->bank['name_th'] . ' [' . $item->acc_no . '] '.($item->enable == 'Y' ? 'ใช้งาน' : 'ปิด'),
            ];

        })->pluck('name', 'code');

        return $rpWithdrawSeamlessDataTable->render($this->_config['view'], ['banks' => $banks,'banks_out' => $banks_out]);
    }

    public function rp_withdraw_seamless_free(RpWithdrawSeamlessFreeDataTable $rpWithdrawSeamlessFreeDataTable)
    {
        $banks = $this->bankRepository->findWhere(['enable' => 'Y'])->pluck('name_th', 'code');
        return $rpWithdrawSeamlessFreeDataTable->render($this->_config['view'], ['banks' => $banks]);
    }

    public function rp_billturn(RpBillTurnDataTable $rpBillTurnDataTable)
    {
        $games = $this->gameRepository->findWhere(['enable' => 'Y'])->pluck('name', 'code');
        return $rpBillTurnDataTable->render($this->_config['view'], ['games' => $games]);
    }

    public function rp_cashback(RpCashbackDataTable $rpCashbackDataTable)
    {
        if((now()->toTimeString() >= '00:00:00') && (now()->toTimeString() <= '01:00:00')){
            return 'ระบบกำลังมอบออโต้ ห้ามกดเวลา 00.00 - 01.00 โปรดเข้ามากดหลังจากนี้ถ้าระบบ มอบไม่ครบ';
        }


        return $rpCashbackDataTable->render($this->_config['view']);
    }

    public function rp_member_ic(RpMemberIcDataTable $rpMemberIcDataTable)
    {
        if((now()->toTimeString() >= '00:00:00') && (now()->toTimeString() <= '01:00:00')){
            return 'ระบบกำลังมอบออโต้ ห้ามกดเวลา 00.00 - 01.00 โปรดเข้ามากดหลังจากนี้ถ้าระบบ มอบไม่ครบ';
        }
        return $rpMemberIcDataTable->render($this->_config['view']);
    }

    public function rp_member_pro(RpMemberProDataTable $rpMemberProDataTable)
    {

        return $rpMemberProDataTable->render($this->_config['view']);
    }

    public function rp_member_edit(RpMemberEditDataTable $rpMemberEditDataTable)
    {

        return $rpMemberEditDataTable->render($this->_config['view']);
    }

    public function rp_withdraw_free(RpWithdrawFreeDataTable $rpWithdrawFreeDataTable)
    {
        $banks = $this->bankRepository->findWhere(['enable' => 'Y'])->pluck('name_th', 'code');

        return $rpWithdrawFreeDataTable->render($this->_config['view'], ['banks' => $banks]);
    }

    public function rp_sponsor(RpSponsorDataTable $rpSponsorDataTable)
    {
        return $rpSponsorDataTable->render($this->_config['view']);
    }

    public function rp_recommender(RpRecommenderDataTable $rpRecommenderDataTable)
    {
        return $rpRecommenderDataTable->render($this->_config['view']);
    }

    public function transaction(TransactionDataTable $transactionDataTable)
    {
        return $transactionDataTable->render($this->_config['view']);
    }

    public function rp_spin(RpSpinDataTable $rpSpinDataTable)
    {
        $bonus = collect(app('Gametech\Payment\Repositories\BonusSpinRepository')->orderBy('bonus_name')->select('bonus_name')->distinct()->get()->toArray())->map(function ($items) {
            $item = (object)$items;
            return [
                'code' => $item->bonus_name,
                'name' => $item->bonus_name
            ];

        })->pluck('name', 'code');
        return $rpSpinDataTable->render($this->_config['view'], ['bonus' => $bonus]);
    }

    public function rp_deposit(RpDepositDataTable $rpDepositDataTable)
    {


        $responses = app('Gametech\Payment\Repositories\BankAccountRepository')->in()->active()->with('bank')->orderBy('code')->get()->toArray();

        $banks = collect($responses)->map(function ($items) {
            $item = (object)$items;
//            dd($item);
            return [
                'code' => $item->code,
                'name' => $item->bank['name_th'] . ' [ ' . $item->acc_no .' - '.$item->acc_name. ' ] '.($item->enable == 'Y' ? 'ใช้งาน' : 'ปิด'),
            ];

        })->pluck('name', 'code');;

//        dd($banks->toArray());

//        dd($banks);
//        $pros = $this->promotionRepository->findWhere(['use_wallet' => 'Y'])->pluck('name_th', 'code');
        return $rpDepositDataTable->render($this->_config['view'], ['banks' => $banks]);
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');
        $method = $request->input('method');
        $topic = '';
        $responses = [];

        $stat = app('Gametech\Core\Repositories\DailyStatRepository')->find($id);

        switch ($method){
            case 'new':
                $topic = 'รายชื่อสมาชิกใหม่';
                $member_code = json_decode($stat->member_new_list);
                $responses = app('Gametech\Member\Repositories\MemberRepository')->findWhereIn('code',$member_code);
                break;

            case 'newrefill':
                $topic = 'รายชื่อสมาชิกใหม่ที่เติมเงิน';
                $member_code = json_decode($stat->member_new_refill_list);
                $responses = app('Gametech\Member\Repositories\MemberRepository')->findWhereIn('code',$member_code);
                break;
        }


        $permiss = bouncer()->hasPermission('wallet.member.tel');


        $responses = $responses->map(function ($items,$key) use ($permiss) {
            $item = (object)$items;

//            $censor = new StarReplacer();

            if ($permiss) {
                $tel = $item->tel;
            } else {
                $tel = Str::mask($item->tel,'*');
            }

            return [
                'no' => ++$key,
                'date_regis' => core()->formatDate($item->date_create,'H:i:s'),
                'firstname' => $item->firstname,
                'lastname' => $item->lastname,
                'user_name' => $item->user_name,
                'tel' => $tel
            ];

        });

        $result['caption'] = $topic;
        $result['list'] = $responses;

        return $this->sendResponseNew($result,'ดำเนินการเสร็จสิ้น');

    }

    public function loadDataRef(Request $request)
    {
        $id = $request->input('id');
        $start = $request->input('start');
        $end = $request->input('end');
        $topic = 'รายชื่อสมาชิก';
        $responses = [];

        $responses = app('Gametech\Member\Repositories\MemberRepository')->scopeQuery(function($query) use ($id,$start,$end){
            return $query->where('refer_code',$id)->whereBetween('date_regis', array($start, $end));
        })->all();


        $permiss = bouncer()->hasPermission('wallet.member.tel');


        $responses = $responses->map(function ($items,$key) use ($permiss) {
            $item = (object)$items;

//            $censor = new StarReplacer();

            if ($permiss) {
                $tel = $item->tel;
            } else {
                $tel = Str::mask($item->tel,'*');
            }

            return [
                'no' => ++$key,
                'firstname' => $item->firstname,
                'lastname' => $item->lastname,
                'user_name' => $item->user_name,
                'tel' => $tel
            ];

        });

        $result['caption'] = $topic;
        $result['list'] = $responses;

        return $this->sendResponseNew($result,'ดำเนินการเสร็จสิ้น');

    }

}
