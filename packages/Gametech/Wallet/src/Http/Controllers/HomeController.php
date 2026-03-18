<?php

namespace Gametech\Wallet\Http\Controllers;

use Gametech\API\Models\GameLogProxy;
use Gametech\Core\Repositories\SlideRepository;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameTypeRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberPromotionLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Promotion\Repositories\PromotionContentRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HomeController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $gameRepository;

    protected $memberRepository;

    protected $memberPromotionLogRepository;

    protected $gameUserRepository;

    protected $gameUserFreeRepository;

    protected $gameTypeRepository;

    protected $gameSeamlessRepository;

    protected $slideRepository;

    private $memberCreditLogRepository;

    private $promotionRepository;

    private $proContentRepository;

    /**
     * Create a new Repository instance
     */
    public function __construct(
        MemberRepository $memberRepo,
        PromotionRepository $promotionRepo,
        PromotionContentRepository $proContentRepo,
        GameRepository $gameRepo,
        GameUserRepository $gameUserRepo,
        GameUserFreeRepository $gameUserFreeRepo,
        GameTypeRepository $gameTypeRepo,
        GameSeamlessRepository $gameSeamlessRepo,
        MemberPromotionLogRepository $memberPromotionLogRepo,
        MemberCreditLogRepository $memberCreditLogRepo,
        SlideRepository $slideRepo
    ) {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->gameRepository = $gameRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->gameUserFreeRepository = $gameUserFreeRepo;

        $this->memberRepository = $memberRepo;

        $this->memberPromotionLogRepository = $memberPromotionLogRepo;

        $this->memberCreditLogRepository = $memberCreditLogRepo;

        $this->gameTypeRepository = $gameTypeRepo;

        $this->gameSeamlessRepository = $gameSeamlessRepo;

        $this->slideRepository = $slideRepo;

        $this->promotionRepository = $promotionRepo;

        $this->proContentRepository = $proContentRepo;

    }

    public function index()
    {



//        $gameTypes = app('Gametech\Game\Repositories\GameTypeRepository')->findWhere(['enable' => 'Y', 'status_open' => 'Y']);
//        $slides = $this->slideRepository->findWhere(['enable' => 'Y']);
//        $games = $this->getProviders();
//        dd($games);
//        $hots = $this->hotGame();

//        dd($hots);

        $gametypes = $this->gameTypeRepository->findWhere(['enable' => 'Y'])
            ->map(function ($g) {
                return [
                    'key' => strtolower($g->id),
                    'label' => strtolower($g->id),
                ];
            })->values()->toArray();

//        dd($gametypes);

        return view($this->_config['view'], compact('gametypes'));

    }

    public function hotGame($type = null)
    {
        $user = $this->id();
        $game = core()->getGame();

        $response = app('Gametech\Game\Repositories\GameUserRepository')
            ->providerListSingle($game->id, $user);

        if (!($response['success'] ?? false)) {
            return [];
        }

        $items = $response['provider'] ?? [];
        $result = [];

        foreach ($items as $item) {
            // ---- Override/Normalize gameType ----
            $prefix = strtoupper($item['prefix'] ?? '');
            $types  = $item['gameType'] ?? null;

            if ($prefix === 'KM') {
                $types = 'card';
            } elseif (in_array($prefix, ['KP', 'MPOKER'], true)) {
                $types = 'poker';
            }

            if ($types === 'fishing or table_game') {
                $types = 'fish';
            }

            if (empty($types)) {
                continue; // ไม่มี type ข้าม
            }

            // ถ้าระบุ type มา ให้คัดเฉพาะหมวดนั้น
            if ($type && strtolower($types) !== strtolower($type)) {
                continue;
            }

            // เอาเฉพาะที่มี rec เท่านั้น
            if (!array_key_exists('rec', $item) || is_null($item['rec'])) {
                continue;
            }

            // เก็บฟิลด์หลัก ๆ ตามตัวอย่างที่ให้มา
            $result[] = [

                'lobbyId' => $item['lobbyId'],
                'provider' => $item['lobbyId'],
                'providerTier' => 'vvip',
                'providerName' => $item['lobbyName'],
                'providerType' => $item['gameType'],
                'logoURL' => 'https://frontgame.sgp1.digitaloceanspaces.com/2022theme/provider/' . strtolower($item['prefix']) . '.jpg',
                'logoTransparentURL' => 'https://frontgame.sgp1.digitaloceanspaces.com/2022theme/provider/' . strtolower($item['prefix']) . '.jpg',
                'status' => $item['maintainance'] === false ? 'ACTIVE' : 'INACTIVE',
                'detailStatus' => 'Y',
                'gameList' => $item['gameList'],
                'maintainance' => $item['maintainance'],
                'endMaintenance' => core()->formatDate($item['endMaintenance'],'Y-m-d H:i'),
                'prefix' => $item['prefix'],
                'rec'           => is_numeric($item['rec']) ? (int)$item['rec'] : $item['rec'],
                'position'      => is_numeric($item['position'] ?? null) ? (int)$item['position'] : null,
            ];
        }

        // เรียง rec ASC; ถ้าเท่ากันเรียง position ASC; ถ้ายังเท่ากันเรียง lobbyId ASC
        usort($result, function ($a, $b) {
            $ra = is_numeric($a['rec']) ? (int)$a['rec'] : PHP_INT_MAX;
            $rb = is_numeric($b['rec']) ? (int)$b['rec'] : PHP_INT_MAX;

            if ($ra === $rb) {
                $pa = $a['position'] ?? PHP_INT_MAX;
                $pb = $b['position'] ?? PHP_INT_MAX;

                if ($pa === $pb) {
                    return ($a['lobbyId'] <=> $b['lobbyId']);
                }
                return $pa <=> $pb;
            }
            return $ra <=> $rb;
        });

        return $result; // จะได้เป็นลิสต์เดียว (ไม่แยกตาม type) ของ "hot" ทั้งหมด
    }


    public function getProviders($type = null)
    {
        $user = $this->id();
        $game = core()->getGame();
        $response = app('Gametech\Game\Repositories\GameUserRepository')
            ->providerListSingle($game->id, $user);

        if ($response['success'] === true) {
            $grouped = [];

            foreach ($response['provider'] as $item) {
                if(is_null($item['position']))continue;
                // --- บังคับ/ปรับประเภทตาม prefix และ normalize type ---
                $prefix = strtoupper($item['prefix'] ?? '');
                $types  = $item['gameType'] ?? null;

                // 1) บังคับตาม prefix
                if ($prefix === 'KM') {
                    $types = 'card';
                } elseif (in_array($prefix, ['KP', 'MPOKER'], true)) {
                    $types = 'poker';
                }

                // 2) normalize ชื่อ type เดิม
                if ($types === 'fishing or table_game') {
                    $types = 'fish';
                }

                // ตัดเคสว่างจริง ๆ หลัง override/normalize แล้ว
                if (empty($types)) {
                    continue;
                }

                // อัปเดตกลับเข้า item เพื่อให้ downstream ใช้ type เดียวกัน
                $item['gameType'] = $types;

                // จัดกลุ่ม
                $grouped[$types][] = $item;
            }

            // ระบุ $type → ส่งเฉพาะ group นั้น (หลัง sort)
            if ($type) {
                $target = $grouped[$type] ?? [];
                $sorted = $this->sortProvidersByRules($type, $target);
                $result = [
                    $type => $this->transformProviders($sorted),
                ];
            } else {
                // ไม่ระบุ type → ส่งทุก group แยก key (หลัง sort ราย group)
                $result = [];
                foreach ($grouped as $groupType => $items) {
                    $sorted = $this->sortProvidersByRules($groupType, $items);
                    $result[$groupType] = $this->transformProviders($sorted);
                }
            }

            return $result;
        }

        return null;
    }


    /**
     * กฎการจัดเรียง:
     * - ถ้า group = slot → lobbyId 31 มาก่อนเสมอ
     * - ที่เหลือเรียงตาม position (ASC), ถ้าเท่ากันผูกด้วย lobbyId (ASC)
     * - หากไม่มี position → ดันไปท้ายสุด
     */
    private function sortProvidersByRules(string $groupType, array $items): array
    {
        usort($items, function ($a, $b) use ($groupType) {
            $isSlot = strtolower($groupType) === 'slot';

            $aLobby = (int)($a['lobbyId'] ?? 0);
            $bLobby = (int)($b['lobbyId'] ?? 0);

            if ($isSlot) {
                if ($aLobby === 31 && $bLobby !== 31) return -1;
                if ($bLobby === 31 && $aLobby !== 31) return 1;
            }

            $pa = $a['position'] ?? PHP_INT_MAX;
            $pb = $b['position'] ?? PHP_INT_MAX;

            // กันกรณี position เป็น string ตัวเลข
            if (is_string($pa) && ctype_digit($pa)) $pa = (int) $pa;
            if (is_string($pb) && ctype_digit($pb)) $pb = (int) $pb;

            if ($pa === $pb) {
                return $aLobby <=> $bLobby;
            }
            return $pa <=> $pb;
        });

        return $items;
    }


    private function transformProviders(array $items): array
    {
        return array_map(function ($item) {
            return [
                'provider' => $item['lobbyId'],
                'providerTier' => 'vvip',
                'providerName' => $item['lobbyName'],
                'providerType' => $item['gameType'],
                'logoURL' => 'https://frontgame.sgp1.digitaloceanspaces.com/2022theme/provider/' . strtolower($item['prefix']) . '.jpg',
                'logoTransparentURL' => 'https://frontgame.sgp1.digitaloceanspaces.com/2022theme/provider/' . strtolower($item['prefix']) . '.jpg',
                'status' => $item['maintainance'] === false ? 'ACTIVE' : 'INACTIVE',
                'detailStatus' => 'Y',
                'gameList' => $item['gameList'],
                'maintainance' => $item['maintainance'],
                'endMaintenance' => core()->formatDate($item['endMaintenance'],'Y-m-d H:i'),
                'prefix' => $item['prefix'],
            ];
        }, $items);
    }
	
	public function loadBonus()
	{
		$member = $this->memberRepository->findOrFail($this->id());
		$result['profile'] = collect($member)->only('cashback', 'ic', 'faststart', 'bonus');
		$result['profile']['coupon'] = 0;
		
		return $this->sendResponseNew($result, 'complete');
	}

    public function getHistoryTab()
    {
        $config = core()->getConfigData();
        $banks[] = ['method' => 'deposit', 'name' => Lang::get('app.home.deposit'), 'color' => 'green', 'id' => 'deposit-tab', 'href' => '#deposit', 'select' => 'true'];
        $banks[] = ['method' => 'withdraw', 'name' => Lang::get('app.home.withdraw'), 'color' => 'red', 'id' => 'withdraw-tab', 'href' => '#withdraw', 'select' => 'false'];
        if ($config->multigame_open == 'Y') {
            $banks[] = ['method' => 'transfer', 'name' => Lang::get('app.home.transfer'), 'color' => 'red', 'id' => 'transfer-tab', 'href' => '#transfer', 'select' => 'false'];
        }
        if ($config->wheel_open == 'Y') {
            $banks[] = ['method' => 'spin', 'name' => Lang::get('app.home.wheel'), 'color' => 'green', 'id' => 'spin-tab', 'href' => '#spin', 'select' => 'false'];
        }
        if ($config->money_tran_open == 'Y') {
            $banks[] = ['method' => 'money', 'name' => 'โอนเงิน', 'color' => 'red', 'id' => 'money-tab', 'href' => '#money', 'select' => 'false'];
        }
        $banks[] = ['method' => 'cashback', 'name' => Lang::get('app.home.cashback'), 'color' => 'red', 'id' => 'cashback-tab', 'href' => '#cashback', 'select' => 'false'];
        $banks[] = ['method' => 'memberic', 'name' => Lang::get('app.home.ic'), 'color' => 'yellow', 'id' => 'memberic-tab', 'href' => '#memberic', 'select' => 'false'];

        $banks = collect($banks);

        return $banks;
    }

    public function loadSpin(): Collection
    {
        $responses = collect(app('Gametech\Core\Repositories\SpinRepository')->findWhere(['enable' => 'Y'])->toArray());
        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'fillStyle' => $item->spincolor,
                'image' => Storage::url('spin_img/'.$item->filepic),
                'text' => number_format($item->amount, 0),
                'code' => $item->code,
                'amount' => $item->amount,
                'winloss' => $item->winloss,
                'spincolor' => $item->spincolor,
                'name' => $item->name,
                'types' => $item->types,
            ];

        });

        return $responses;
    }

    public function getPromotion() {}

    public function loadGame($update = false): Collection
    {

        return collect($this->gameRepository->getGameUserById($this->id(), $update)->toArray());

    }

    public function loadProfile(): JsonResponse
    {
        $config = collect(core()->getConfigData());

        $configs = $config->map(function ($value, $key) {
            if ($key == 'point_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'diamond_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'notice') {
                return $value;
            }
        });

        $configs->only('point_open', 'diamond_open', 'notice');

        $confignew['point'] = $configs['point_open'];
        $confignew['diamond'] = $configs['diamond_open'];
        $confignew['notice'] = $configs['notice'];

        if ($config['seamless'] == 'Y') {

            $member = $this->user();

            //            $item = collect($this->gameUserRepository->findOneByField('member_code',$this->id()))->toArray();
            $game = core()->getGame('transfer');
            $games = $this->gameSeamlessRepository->findWhere(['method' => 'transfer', 'status_open' => 'Y', 'enable' => 'Y']);
            if ($games) {
                foreach ($games as $item) {
                    $gameuser = $this->gameUserFreeRepository->findOneWhere(['member_code' => $member['code'], 'game_code' => $game->code]);
                    //                    dd($gameuser);
                    if (! $gameuser) {
                        continue;
                    }
                    $response = $this->gameUserFreeRepository->checkBalanceSeamless($game->id, $gameuser->user_name, strtoupper($item->id));
                    //                   gameUserFreeRepository
                    if ($response['success'] !== true) {
                        continue;
                    }
                    //                    $res = $this->gameUserFreeRepository->checkOutStanding($game->id, $gameuser->user_name, strtoupper($item->id));
                    //                    if ($res['success'] === true) {
                    //                        if ($res['amount'] > 0) continue;
                    //                    }
                    $score = $response['score'];
                    if ($score > 0) {
                        // dd($item->id);
                        $result = $this->gameUserFreeRepository->UserWithdrawTransfer(strtoupper($item->id), $game->code, $gameuser->user_name, $score, true);
                        //                        dd($result);
                        if ($result['success'] === true) {
                            if ($result['before'] > 0) {
                                $member->balance_free += $result['before'];
                                $member->save();
                            }
                        }
                    }
                }
            }
        } else {
            if ($config['multigame_open'] == 'N') {

                $game = core()->getGame('');
                $item = collect($this->gameUserFreeRepository->getOneUser($this->id(), $game->code, true))->toArray();
                $member = $this->user();
                $member->balance_free = $item['data']['balance'];
                $member->save();

            }
        }
        // dd($confignew);
        $result['profile'] = $this->user()->only('balance', 'point_deposit', 'diamond', 'balance_free', 'user_name');
        $result['system'] = $confignew;

        return $this->sendResponseNew($result, 'complete');
    }

    public function loadProfileMin(): JsonResponse
    {
        $config = collect(core()->getConfigData());

        $configs = $config->map(function ($value, $key) {
            if ($key == 'point_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'diamond_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'notice') {
                return $value;
            }
        });

        $configs->only('point_open', 'diamond_open', 'notice');

        $confignew['point'] = $configs['point_open'];
        $confignew['diamond'] = $configs['diamond_open'];
        $confignew['notice'] = $configs['notice'];

        $result['profile'] = $this->user()->only('balance', 'point_deposit', 'diamond', 'balance_free', 'user_name');
        $result['system'] = $confignew;

        return $this->sendResponseNew($result, 'complete');
    }

    public function loadCredit_(): JsonResponse
    {
        $config = collect(core()->getConfigData());

        $configs = $config->map(function ($value, $key) {
            if ($key == 'point_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'diamond_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'multigame_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'notice') {
                return $value;
            }
        });

        $configs->only('point_open', 'diamond_open', 'notice', 'multigame_open');

        $confignew['point'] = $configs['point_open'];
        $confignew['diamond'] = $configs['diamond_open'];
        $confignew['notice'] = $configs['notice'];
        $confignew['multi'] = $configs['multigame_open'];

        if ($config['seamless'] == 'Y') {

            $member = $this->user();

            //            $item = collect($this->gameUserRepository->findOneByField('member_code',$this->id()))->toArray();
            $game = core()->getGame('transfer');
            if ($game) {
                $games = $this->gameSeamlessRepository->findWhere(['method' => 'transfer', 'enable' => 'Y']);
                if ($games) {
                    foreach ($games as $item) {
                        $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member['code'], 'game_code' => $game->code]);
                        //                    dd($gameuser);
                        if (! $gameuser) {
                            continue;
                        }
                        $response = $this->gameUserRepository->checkBalanceSeamless($game->id, $gameuser->user_name, strtoupper($item->id));
                        if ($response['success'] !== true) {
                            continue;
                        }
                        //                    $res = $this->gameUserRepository->checkOutStanding($game->id, $gameuser->user_name, strtoupper($item->id));
                        //                    if ($res['success'] === true) {
                        //                        if ($res['amount'] > 0) continue;
                        //                    }
                        //                   dd($response);
                        $score = $response['score'];
                        if ($score > 0) {
                            // dd($item->id);
                            $result = $this->gameUserRepository->UserWithdrawTransfer(strtoupper($item->id), $game->code, $gameuser->user_name, $score, true);
                            if ($result['success'] === true) {
                                //                        dd($result);
                                if ($result['before'] > 0) {
                                    if ($result['after'] == 0) {
                                        MemberProxy::where('user_name', $member->user_name)->increment('balance', $result['before']);
                                    }
                                    //                                $member->balance += $result['before'];
                                    //                                $member->save();
                                }
                            }
                        }
                    }
                }
            }

            $game = core()->getGame('seamless');

            $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member['code'], 'game_code' => $game->code]);
            //            $gameuser = $this->gameUserRepository->findOneByField('member_code', $member['code']);
            if ($gameuser) {
                $response = $this->gameUserRepository->checkBalance($game->id, $gameuser->user_name);
                if ($response['success'] === true) {
                    $gameuser->balance = $response['score'];
                    $member->balance = $response['score'];
                    $gameuser->save();
                    $member->save();
                }

                if ($gameuser->amount_balance > 0) {
                    if ($member->balance <= $config['pro_reset']) {
                        $gameuser->bill_code = 0;
                        $gameuser->pro_code = 0;
                        $gameuser->bonus = 0;
                        $gameuser->amount = 0;
                        $gameuser->turnpro = 0;
                        $gameuser->amount_balance = 0;
                        $gameuser->withdraw_limit = 0;
                        $gameuser->withdraw_limit_rate = 0;
                        $gameuser->withdraw_limit_amount = 0;
                        $gameuser->save();

                        $this->memberPromotionLogRepository->where('member_code', $member['code'])->where('complete', 'N')->update([
                            'complete' => 'Y',
                        ]);

                        $this->memberCreditLogRepository->create([
                            'ip' => request()->ip(),
                            'credit_type' => 'D',
                            'game_code' => $gameuser->game_code,
                            'gameuser_code' => $gameuser->code,
                            'amount' => 0,
                            'bonus' => 0,
                            'total' => 0,
                            'balance_before' => $member['balance'],
                            'balance_after' => $member['balance'],
                            'credit' => 0,
                            'credit_bonus' => 0,
                            'credit_total' => 0,
                            'credit_before' => $member['balance'],
                            'credit_after' => $member['balance'],
                            'member_code' => $member['code'],
                            'pro_code' => 0,
                            'refer_code' => 0,
                            'refer_table' => 'blank',
                            'auto' => 'Y',
                            'remark' => 'ได้รับอิสระ เมื่อยอดเงินมีน้อยกว่า : '.$config['pro_reset'],
                            'kind' => 'OTHER',
                            'amount_balance' => $gameuser->amount_balance,
                            'withdraw_limit' => $gameuser->withdraw_limit,
                            'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                            'user_create' => 'System Auto',
                            'user_update' => 'System Auto',
                        ]);

                    }

                }
            }

        } else {
            if ($config['multigame_open'] == 'N') {
                $game = core()->getGame('');
                $item = collect($this->gameUserRepository->getOneUser($this->id(), $game->code, true))->toArray();
                $member = $this->user();
                $member->balance = $item['data']['balance'];
                $member->save();

            }
        }
        //        $item = collect($this->gameUserRepository->getOneUser($this->id(), $game->code, true))->toArray();

        //        dd($item);

        $member = $this->memberRepository->findOrFail($this->id());
        //        $member->balance = $item['data']['balance'];
        //        $member->save();

        $result['profile'] = collect($member)->only('balance', 'point_deposit', 'diamond', 'balance_free', 'credit', 'user_name');
        $result['system'] = $confignew;

        return $this->sendResponseNew($result, 'complete');
    }

    public function create(Request $request): JsonResponse
    {
        $game = $request->input('id');
        $user = $this->gameUserRepository->findOneWhere(['game_code' => $game, 'member_code' => $this->id(), 'enable' => 'Y']);
        if (! $user) {
            $games = $this->gameRepository->findOneWhere(['code' => $game, 'enable' => 'Y']);

            if ($games->newuser == 'Y') {

                $response = $this->gameUserRepository->addGameUser($game, $this->id(), collect($this->user())->toArray());
                if ($response['success'] === true) {
                    return $this->sendResponseNew($response['data'], 'ระบบได้ทำการสร้างบัญชีเกม เรียบร้อยแล้ว');
                } else {
                    return $this->sendError($response['msg'], 200);
                }

            } else {
                return $this->sendError('ไม่สามารถสมัครไอดีเกมได้ในขณะนี้', 200);
            }
        } else {
            return $this->sendError('ไม่สามารถดำเนินการได้ คุณมีบัญชีเกมนี้ในระบบแล้ว', 200);
        }
    }

    public function loadCredit_GH(): JsonResponse
    {
        $config = collect(core()->getConfigData());

        $configs = $config->map(function ($value, $key) {
            if ($key == 'point_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'diamond_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'multigame_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'notice') {
                return $value;
            }
        });

        $configs->only('point_open', 'diamond_open', 'notice', 'multigame_open');

        $confignew['point'] = $configs['point_open'];
        $confignew['diamond'] = $configs['diamond_open'];
        $confignew['notice'] = $configs['notice'];
        $confignew['multi'] = $configs['multigame_open'];

        if ($config['seamless'] == 'Y') {

            $member = $this->user();

            //            $item = collect($this->gameUserRepository->findOneByField('member_code',$this->id()))->toArray();
            $game = core()->getGame('transfer');
            if ($game) {
                $games = $this->gameSeamlessRepository->findWhere(['method' => 'transfer', 'enable' => 'Y']);
                if ($games) {
                    foreach ($games as $item) {
                        $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member['code'], 'game_code' => $game->code]);
                        //                    dd($gameuser);
                        if (! $gameuser) {
                            continue;
                        }
                        $response = $this->gameUserRepository->checkBalanceSeamless($game->id, $gameuser->user_name, strtoupper($item->id));
                        if ($response['success'] !== true) {
                            continue;
                        }
                        //                    $res = $this->gameUserRepository->checkOutStanding($game->id, $gameuser->user_name, strtoupper($item->id));
                        //                    if ($res['success'] === true) {
                        //                        if ($res['amount'] > 0) continue;
                        //                    }
                        //                   dd($response);
                        $score = $response['score'];
                        if ($score > 0) {
                            // dd($item->id);
                            $result = $this->gameUserRepository->UserWithdrawTransfer(strtoupper($item->id), $game->code, $gameuser->user_name, $score, true);
                            if ($result['success'] === true) {
                                //                        dd($result);
                                if ($result['before'] > 0) {
                                    if ($result['after'] == 0) {
                                        MemberProxy::where('user_name', $member->user_name)->increment('balance', $result['before']);
                                    }
                                    //                                $member->balance += $result['before'];
                                    //                                $member->save();
                                }
                            }
                        }
                    }
                }
            }

            $game = core()->getGame('seamless');
            $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member['code'], 'game_code' => $game->code]);

            if ($gameuser && ($gameuser->amount_balance > 0 || $gameuser->pro_code > 0)) {
                $userId = $gameuser->user_name;
                $redis = Redis::connection('game');
                $key = "user_game_status:{$userId}";
                $sessionRaw = $redis->get($key);
                $session = $sessionRaw ? json_decode($sessionRaw, true) : null;

                // ตรวจสอบรายการ ADJUSTBALANCE ล่าสุด
                $checkAdjustQuery = GameLogProxy::where('game_user', $userId)
                    ->where('method', 'ADJUSTBALANCE')
                    ->where('response', 'in')
                    ->whereIn('con_3', ['CREDIT', 'DEBIT']);

                if ($session) {
                    $checkAdjustQuery->where('company', $session['productId']);
                }

                $checkAdjust = $checkAdjustQuery->latest('created_at')->first();

                // ถ้ามี DEBIT ล่าสุด → return ได้เลย ไม่ต้องเช็คยอด
                if ($checkAdjust && $checkAdjust->con_3 === 'DEBIT') {
                    $member = $this->memberRepository->findOrFail($this->id());

                    $result['profile'] = collect($member)->only(
                        'balance', 'point_deposit', 'diamond', 'balance_free', 'credit', 'user_name'
                    );
                    $result['system'] = $confignew;

                    return $this->sendResponseNew($result, 'complete');
                }

                // เช็ค balance เพื่อรีเซ็ตโปรฯ
                if ($member['balance'] <= $config['pro_reset']) {
                    $this->resetPromotion($gameuser, $member, $config['pro_reset']);
                }
            }

            //            $game = core()->getGame('seamless');
            //            $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member['code'], 'game_code' => $game->code]);
            //            //            $gameuser = $this->gameUserRepository->findOneByField('member_code', $member['code']);
            //            if ($gameuser) {
            //
            //                if ($gameuser->amount_balance > 0 || $gameuser->pro_code > 0) {
            //                    $userId = $gameuser->user_name;
            //                    $redis = Redis::connection('game');
            //                    $key = "user_game_status:{$userId}";
            //                    $sessionRaw = $redis->get($key);
            //
            //                    if (! $sessionRaw) {
            //
            //                        $checkAdjust = GameLogProxy::where('game_user', $userId)
            //                            ->where('response', 'in')
            //                            ->where('method', 'ADJUSTBALANCE')
            //                            ->whereIn('con_3', ['CREDIT', 'DEBIT'])
            //                            ->latest('created_at')
            //                            ->first();
            //
            //                    } else {
            //
            //                        $session = json_decode($sessionRaw, true);
            //
            //                        $checkAdjust = GameLogProxy::where('game_user', $userId)
            //                            ->where('company', $session['productId'])
            //                            ->where('response', 'in')
            //                            ->where('method', 'ADJUSTBALANCE')
            //                            ->whereIn('con_3', ['CREDIT', 'DEBIT'])
            //                            ->latest('created_at')
            //                            ->first();
            //
            //                    }
            //
            //                    if ($checkAdjust) {
            //                        if ($checkAdjust->con_3 == 'DEBIT') {
            //
            //                            $member = $this->memberRepository->findOrFail($this->id());
            //                            //        $member->balance = $item['data']['balance'];
            //                            //        $member->save();
            //
            //                            $result['profile'] = collect($member)->only('balance', 'point_deposit', 'diamond', 'balance_free', 'credit', 'user_name');
            //                            $result['system'] = $confignew;
            //
            //                            return $this->sendResponseNew($result, 'complete');
            //
            //                        }
            //                    }
            //
            //                    if ($member['balance'] <= $config['pro_reset']) {
            //                        $gameuser->bill_code = 0;
            //                        $gameuser->pro_code = 0;
            //                        $gameuser->bonus = 0;
            //                        $gameuser->amount = 0;
            //                        $gameuser->turnpro = 0;
            //                        $gameuser->amount_balance = 0;
            //                        $gameuser->withdraw_limit = 0;
            //                        $gameuser->withdraw_limit_rate = 0;
            //                        $gameuser->withdraw_limit_amount = 0;
            //                        $gameuser->save();
            //
            //                        $this->memberPromotionLogRepository->where('member_code', $member['code'])->where('complete', 'N')->update([
            //                            'complete' => 'Y',
            //                        ]);
            //
            //                        $this->memberCreditLogRepository->create([
            //                            'ip' => request()->ip(),
            //                            'credit_type' => 'D',
            //                            'game_code' => $gameuser->game_code,
            //                            'gameuser_code' => $gameuser->code,
            //                            'amount' => 0,
            //                            'bonus' => 0,
            //                            'total' => 0,
            //                            'balance_before' => $member['balance'],
            //                            'balance_after' => $member['balance'],
            //                            'credit' => 0,
            //                            'credit_bonus' => 0,
            //                            'credit_total' => 0,
            //                            'credit_before' => $member['balance'],
            //                            'credit_after' => $member['balance'],
            //                            'member_code' => $member['code'],
            //                            'pro_code' => 0,
            //                            'refer_code' => 0,
            //                            'refer_table' => 'blank',
            //                            'auto' => 'Y',
            //                            'remark' => 'ได้รับอิสระ เมื่อยอดเงินมีน้อยกว่า : '.$config['pro_reset'],
            //                            'kind' => 'OTHER',
            //                            'amount_balance' => $gameuser->amount_balance,
            //                            'withdraw_limit' => $gameuser->withdraw_limit,
            //                            'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
            //                            'user_create' => 'System Auto',
            //                            'user_update' => 'System Auto',
            //                        ]);
            //
            //                    }
            //
            //                }
            //            }

        } else {
            if ($config['multigame_open'] == 'N') {
                $game = core()->getGame('');
                $item = collect($this->gameUserRepository->getOneUser($this->id(), $game->code, true))->toArray();
                $member = $this->user();
                $member->balance = $item['data']['balance'];
                $member->save();

            }
        }
        //        $item = collect($this->gameUserRepository->getOneUser($this->id(), $game->code, true))->toArray();

        //        dd($item);

        $member = $this->memberRepository->findOrFail($this->id());
        //        $member->balance = $item['data']['balance'];
        //        $member->save();

        $result['profile'] = collect($member)->only('balance', 'point_deposit', 'diamond', 'balance_free', 'credit', 'user_name');
        $result['system'] = $confignew;

        return $this->sendResponseNew($result, 'complete');
    }

    public function loadCredit(): JsonResponse
    {
        $config = collect(core()->getConfigData());
        $member = $this->user();
        $configs = $config->map(function ($value, $key) {
            if ($key == 'point_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'diamond_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'multigame_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'wheel_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'notice') {
                return $value;
            }
        });

        $configs->only('point_open', 'diamond_open', 'notice', 'multigame_open','wheel_open');

        $confignew['point'] = $configs['point_open'];
        $confignew['diamond'] = $configs['diamond_open'];
        $confignew['notice'] = $configs['notice'];
        $confignew['multi'] = $configs['multigame_open'];
        $confignew['wheel'] = $configs['wheel_open'];

        if ($config['seamless'] == 'Y') {

            $game = core()->getGame();
            $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member['code'], 'game_code' => $game->code]);

            if ($gameuser && ($gameuser->amount_balance > 0 || $gameuser->pro_code > 0)) {
                $userId = $gameuser->user_name;
                $redis = Redis::connection('game');
                $key = "user_game_status:{$userId}";
                $sessionRaw = $redis->get($key);
                $session = $sessionRaw ? json_decode($sessionRaw, true) : null;

                // ตรวจสอบรายการ ADJUSTBALANCE ล่าสุด
                $checkAdjustQuery = GameLogProxy::where('game_user', $userId)
                    ->where('method', 'ADJUSTBALANCE')
                    ->where('response', 'in')
                    ->whereIn('con_3', ['CREDIT', 'DEBIT']);

                if ($session) {
                    $checkAdjustQuery->where('company', $session['productId']);
                }else{
                    $checkAdjustQuery->whereIn('company', ['JOKER', 'SLOTXO']);
                }

                $checkAdjust = $checkAdjustQuery->latest('created_at')->first();

                // ถ้ามี DEBIT ล่าสุด → return ได้เลย ไม่ต้องเช็คยอด
                if ($checkAdjust && $checkAdjust->con_3 === 'DEBIT') {
                    $member = $this->memberRepository->findOrFail($this->id());

                    $result['profile'] = collect($member)->only(
                        'balance', 'point_deposit', 'diamond', 'balance_free', 'credit', 'user_name'
                    );
                    $result['system'] = $confignew;

                    return $this->sendResponseNew($result, 'complete');
                }

                // เช็ค balance เพื่อรีเซ็ตโปรฯ
                if ($member['balance'] <= $config['pro_reset']) {
                    $this->resetPromotion($gameuser, $member, $config['pro_reset']);
                }
            }

//            $game = core()->getGame();
//            $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member['code'], 'game_code' => $game->code]);
//            //            $gameuser = $this->gameUserRepository->findOneByField('member_code', $member['code']);
//            if ($gameuser) {
//
//                if ($gameuser->amount_balance > 0 && $gameuser->pro_code != 0) {
//                    if ($member['balance'] <= $config['pro_reset']) {
//                        $gameuser->bill_code = 0;
//                        $gameuser->pro_code = 0;
//                        $gameuser->bonus = 0;
//                        $gameuser->amount = 0;
//                        $gameuser->turnpro = 0;
//                        $gameuser->amount_balance = 0;
//                        $gameuser->withdraw_limit = 0;
//                        $gameuser->withdraw_limit_rate = 0;
//                        $gameuser->withdraw_limit_amount = 0;
//                        $gameuser->save();
//
//                        $this->memberCreditLogRepository->create([
//                            'ip' => request()->ip(),
//                            'credit_type' => 'D',
//                            'game_code' => $gameuser->game_code,
//                            'gameuser_code' => $gameuser->code,
//                            'amount' => 0,
//                            'bonus' => 0,
//                            'total' => 0,
//                            'balance_before' => $member['balance'],
//                            'balance_after' => $member['balance'],
//                            'credit' => 0,
//                            'credit_bonus' => 0,
//                            'credit_total' => 0,
//                            'credit_before' => $member['balance'],
//                            'credit_after' => $member['balance'],
//                            'member_code' => $member['code'],
//                            'pro_code' => 0,
//                            'refer_code' => 0,
//                            'refer_table' => 'blank',
//                            'auto' => 'Y',
//                            'remark' => 'ได้รับอิสระ เมื่อยอดเงินมีน้อยกว่า : '.$config['pro_reset'],
//                            'kind' => 'OTHER',
//                            'amount_balance' => $gameuser->amount_balance,
//                            'withdraw_limit' => $gameuser->withdraw_limit,
//                            'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
//                            'user_create' => 'System Auto',
//                            'user_update' => 'System Auto',
//                        ]);
//
//                    }
//
//                }
//            }

        } else {
            if ($config['multigame_open'] == 'N') {
                $game = core()->getGame();
                $item = collect($this->gameUserRepository->getOneUser($member->code, $game->code, true))->toArray();
                $member = $this->user();
                $member->balance = $item['data']['balance'];
                $member->save();

                $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member['code'], 'game_code' => $game->code]);
                //            $gameuser = $this->gameUserRepository->findOneByField('member_code', $member['code']);
                if ($gameuser) {

                    if ($gameuser->amount_balance > 0 || $gameuser->pro_code != 0) {
                        if ($gameuser['balance'] <= $config['pro_reset']) {
                            $gameuser->bill_code = 0;
                            $gameuser->pro_code = 0;
                            $gameuser->bonus = 0;
                            $gameuser->amount = 0;
                            $gameuser->turnpro = 0;
                            $gameuser->amount_balance = 0;
                            $gameuser->withdraw_limit = 0;
                            $gameuser->withdraw_limit_rate = 0;
                            $gameuser->withdraw_limit_amount = 0;
                            $gameuser->save();

                            $this->memberPromotionLogRepository->where('member_code', $member['code'])->where('complete', 'N')->update([
                                'complete' => 'Y',
                            ]);

                            $this->memberCreditLogRepository->create([
                                'ip' => request()->ip(),
                                'credit_type' => 'D',
                                'game_code' => $gameuser->game_code,
                                'gameuser_code' => $gameuser->code,
                                'amount' => 0,
                                'bonus' => 0,
                                'total' => 0,
                                'balance_before' => $member['balance'],
                                'balance_after' => $member['balance'],
                                'credit' => 0,
                                'credit_bonus' => 0,
                                'credit_total' => 0,
                                'credit_before' => $member['balance'],
                                'credit_after' => $member['balance'],
                                'member_code' => $member['code'],
                                'pro_code' => 0,
                                'refer_code' => 0,
                                'refer_table' => 'blank',
                                'auto' => 'Y',
                                'remark' => 'ได้รับอิสระ เมื่อยอดเงินมีน้อยกว่า : '.$config['pro_reset'],
                                'kind' => 'OTHER',
                                'amount_balance' => $gameuser->amount_balance,
                                'withdraw_limit' => $gameuser->withdraw_limit,
                                'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                                'user_create' => 'System Auto',
                                'user_update' => 'System Auto',
                            ]);

                        }

                    }
                }

            }
        }
        //        $item = collect($this->gameUserRepository->getOneUser($this->id(), $game->code, true))->toArray();

        //        dd($item);

        $member = $this->memberRepository->findOrFail($member->code);
        //        $member->balance = $item['data']['balance'];
        //        $member->save();

        $result['profile'] = collect($member)->only('balance', 'point_deposit', 'diamond', 'balance_free', 'credit', 'user_name', 'bonus', 'cashback', 'ic', 'faststart','pic_id');
        if ($config['wallet_withdraw_all'] == 'Y') {
            $result['profile']['getpro'] = ($gameuser->pro_code > 0 ? true : ($gameuser->amount_balance > 0 ? true : false));
            $result['profile']['pro'] = true;

        } else {
            $result['profile']['getpro'] = ($gameuser->pro_code > 0 ? true : ($gameuser->amount_balance > 0 ? true : false));
            $result['profile']['pro'] = ($gameuser->pro_code > 0 ? true : ($gameuser->amount_balance > 0 ? true : false));

        }

        $today = now()->toDateString();
        if ($config['seamless'] == 'Y') {
            $withdraw_today = $this->memberRepository->sumWithdrawSeamless($member->code, $today)->withdraw_seamless_amount_sum;
        } else {
            $withdraw_today = $this->memberRepository->sumWithdraw($member->code, $today)->withdraw_amount_sum;
        }

        $withdraw = (is_null($withdraw_today) ? 0 : $withdraw_today);

        if ($member->maxwithdraw_day == 0) {
            $maxwithdraw = $config['maxwithdraw_day'];
        } else {
            $maxwithdraw = $member->maxwithdraw_day;
        }

        $today_wd = ($maxwithdraw - $withdraw);

        //        dd($gameuser);
        // จะดึงความสัมพันธ์เฉพาะตอนที่ pro_code มีค่าเท่านั้น
        $result['profile']['pro_name'] = $gameuser->pro_code
            ? ($gameuser->promotion?->name_th ?? '')
            : '';



        $result['profile']['bank_code'] = $member->bank_code;
        $result['profile']['pic_id'] = $member->pic_id ? asset('storage/' . $member->pic_id) : '';
        $result['profile']['balance'] = ($member->balance);
        $result['profile']['diamond'] = intval($member->diamond);
        $result['profile']['amount_balance'] = ($gameuser->amount_balance);
        $result['profile']['withdraw_limit_amount'] = ($gameuser->withdraw_limit_amount);
        $result['profile']['winlost'] = 0;
        $result['profile']['downline'] = $member->load('down')->down->count();
        $result['profile']['maxwithdraw_day'] = $maxwithdraw;
        $result['profile']['withdraw_min'] = ($config['minwithdraw']);
        $result['profile']['withdraw_max'] = ($today_wd);
        $result['profile']['withdraw_sum_today'] = ($withdraw);
        $result['profile']['withdraw_remain_today'] = ($today_wd);
        $result['profile']['lastupdate'] = now()->format('d/m/Y H:i:s');

        $pro = core()->getSelectPro();
        if (! empty($pro)) {
            $result['promotion']['select'] = true;
            $result['promotion']['name'] = $pro['name_th'];
            $result['promotion']['min'] = $pro['amount_min'];
        } else {
            $result['promotion']['select'] = false;
            $result['promotion']['name'] = '';
            $result['promotion']['min'] = '';
        }


        $result['withdraw'] = $config['withdraw_status'] === 'Y' ? true : false;


        $deposit = core()->getBankTopupCountsNew();
//        $stat = $member->depositStat;
//
//        $isLegacy = $stat && $stat->isLegacy();
//
//        if($isLegacy){
//            $result['deposit']['bank'] = $deposit['bank'];
//            $result['deposit']['payment'] = $deposit['payment'];
//        }else{
//            $result['deposit']['bank'] = 0;
//            $result['deposit']['payment'] = $deposit['payment'];
//        }

        $result['deposit']['bank'] = $deposit['bank'];
        $result['deposit']['payment'] = $deposit['payment'];
        $result['deposit']['tw'] = $deposit['tw'];
        $result['deposit']['slip'] = $deposit['slip'];

        $result['deposit']['sort']['payment'] = $deposit['payment_min_sort'];
        $result['deposit']['sort']['tw'] = $deposit['tw_min_sort'];
        $result['deposit']['sort']['slip'] = $deposit['slip_min_sort'];
        $result['deposit']['sort']['bank'] = $deposit['bank_min_sort'];

        $result['spin'] =  $this->loadSpin();

        $result['system'] = $confignew;

        return $this->sendResponseNew($result, 'complete');
    }

    private function resetPromotion($gameuser, $member, $threshold)
    {
        $gameuser->bill_code = 0;
        $gameuser->pro_code = 0;
        $gameuser->bonus = 0;
        $gameuser->amount = 0;
        $gameuser->turnpro = 0;
        $gameuser->amount_balance = 0;
        $gameuser->withdraw_limit = 0;
        $gameuser->withdraw_limit_rate = 0;
        $gameuser->withdraw_limit_amount = 0;
        $gameuser->save();

        $this->memberPromotionLogRepository->where('member_code', $member['code'])
            ->where('complete', 'N')
            ->update(['complete' => 'Y']);

        $this->memberCreditLogRepository->create([
            'ip' => request()->ip(),
            'credit_type' => 'D',
            'game_code' => $gameuser->game_code,
            'gameuser_code' => $gameuser->code,
            'amount' => 0,
            'bonus' => 0,
            'total' => 0,
            'balance_before' => $member['balance'],
            'balance_after' => $member['balance'],
            'credit' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => $member['balance'],
            'credit_after' => $member['balance'],
            'member_code' => $member['code'],
            'pro_code' => 0,
            'refer_code' => 0,
            'refer_table' => 'blank',
            'auto' => 'Y',
            'remark' => 'ได้รับอิสระ เมื่อยอดเงินมีน้อยกว่า : '.$threshold,
            'kind' => 'OTHER',
            'amount_balance' => $gameuser->amount_balance,
            'withdraw_limit' => $gameuser->withdraw_limit,
            'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
            'user_create' => 'System Auto',
            'user_update' => 'System Auto',
        ]);
    }

    public function loadCreditMin(): JsonResponse
    {
        $config = collect(core()->getConfigData());

        $configs = $config->map(function ($value, $key) {
            if ($key == 'point_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'diamond_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'multigame_open') {
                return $value === 'Y' ? true : false;
            }
            if ($key == 'notice') {
                return $value;
            }
        });

        $configs->only('point_open', 'diamond_open', 'notice', 'multigame_open');

        $confignew['point'] = $configs['point_open'];
        $confignew['diamond'] = $configs['diamond_open'];
        $confignew['notice'] = $configs['notice'];
        $confignew['multi'] = $configs['multigame_open'];

        if ($config['seamless'] == 'Y') {

        } else {

            if ($config['multigame_open'] == 'N') {
                $game = core()->getGame('');
                $item = collect($this->gameUserRepository->getOneUser($this->id(), $game->code, true))->toArray();
                $member = $this->user();
                $member->balance = $item['data']['balance'];
                $member->save();

            }
        }
        //        $item = collect($this->gameUserRepository->getOneUser($this->id(), $game->code, true))->toArray();

        //        dd($item);

        $member = $this->memberRepository->findOrFail($this->id());
        //        $member->balance = $item['data']['balance'];
        //        $member->save();

        $result['profile'] = collect($member)->only('balance', 'point_deposit', 'diamond', 'balance_free', 'credit', 'user_name');
        $result['system'] = $confignew;

        return $this->sendResponseNew($result, 'complete');
    }

    public function loadGameID($game)
    {

        $item = collect($this->gameUserRepository->getOneUser($this->id(), $game, true))->toArray();

        if ($item['new'] === true) {
            $games = $this->gameRepository->find($game);

            $response['id'] = $games->id;
            $response['connect'] = true;
            $response['user_code'] = 0;
            $response['code'] = $game;
            $response['name'] = $games->name;
            $response['balance'] = 0;
            $response['image'] = Storage::url('game_img/'.$games->filepic);

        } else {

            $response['connect'] = $item['connect'];
            if ($item['success'] === false) {
                $response['connect'] = false;
            }

            $response['user_code'] = $item['data']['code'];
            $response['id'] = $item['data']['game']['id'];
            $response['code'] = $item['data']['game']['code'];
            $response['name'] = $item['data']['game']['name'];
            $response['balance'] = $item['data']['balance'];
            $response['image'] = Storage::url('game_img/'.$item['data']['game']['filepic']);
        }

        return $this->sendResponseNew($response, 'complete');
    }

    public function loadGameFreeID($game)
    {

        $item = collect($this->gameUserFreeRepository->getOneUser($this->id(), $game, true))->toArray();

        if ($item['new'] === true) {
            $games = $this->gameRepository->find($game);

            $response['connect'] = true;
            $response['user_code'] = 0;
            $response['code'] = $game;
            $response['name'] = $games->name;
            $response['balance'] = 0;
            $response['image'] = Storage::url('game_img/'.$games->filepic);

        } else {

            $response['connect'] = $item['connect'];
            if ($item['success'] === false) {
                $response['connect'] = false;
            }

            $response['user_code'] = $item['data']['code'];
            $response['code'] = $item['data']['game']['code'];
            $response['name'] = $item['data']['game']['name'];
            $response['balance'] = $item['data']['balance'];
            $response['image'] = Storage::url('game_img/'.$item['data']['game']['filepic']);
        }

        return $this->sendResponseNew($response, 'complete');
    }

    public function createfree(Request $request): JsonResponse
    {
        $game = $request->input('id');
        $user = $this->gameUserFreeRepository->findOneWhere(['game_code' => $game, 'member_code' => $this->id(), 'enable' => 'Y']);
        if (! $user) {
            $games = $this->gameRepository->findOneWhere(['code' => $game, 'enable' => 'Y']);
            if ($games->newuser == 'Y') {
                $response = $this->gameUserFreeRepository->addGameUser($game, $this->id(), collect($this->user())->toArray());
                if ($response['success'] === true) {
                    return $this->sendResponseNew($response['data'], 'ระบบได้ทำการสร้างบัญชีเกม เรียบร้อยแล้ว');
                } else {
                    return $this->sendError($response['msg'], 200);
                }
            } else {
                return $this->sendError('ไม่สามารถสมัครไอดีเกมได้ในขณะนี้', 200);
            }
        } else {
            return $this->sendError('ไม่สามารถดำเนินการได้ คุณมีบัญชีเกมนี้ในระบบแล้ว', 200);
        }
    }
}
