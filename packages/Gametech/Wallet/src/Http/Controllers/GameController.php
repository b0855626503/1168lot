<?php

namespace Gametech\Wallet\Http\Controllers;

use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameTypeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GameController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $gameTypeRepository;

    protected $gameSeamlessRepository;

    public function __construct(
        GameUserRepository $repository,
        GameTypeRepository $gameTypeRepository,
        GameSeamlessRepository $gameSeamlessRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('customer');

        $this->repository = $repository;

        $this->gameTypeRepository = $gameTypeRepository;

        $this->gameSeamlessRepository = $gameSeamlessRepository;
    }

    public function getGames_($type, $provider)
    {

        $game = core()->getGame();
        $game_name = $this->gameSeamlessRepository->findOneByField('id', $provider);
        $games = $this->repository->getGameList($provider, $game_name->method);

        $gamelist = $games['games'];

        $method = $game_name->method;
        $transformedList = array_map(function ($item) use ($method) {
            return [
                "id" => $item["code"],
                "provider" => $item['product'], // ปรับจาก $item["product"] ถ้าอยาก map อัตโนมัติ
                "providerLogo" => [
                    "logoURL" => "",
                    "logoMobileURL" => "",
                    "logoTransparentURL" => ""
                ],
                "gameName" => $item["name"],
                "gameCategory" => $method,
                "gameType" => [$item["type"]],
                "image" => [
                    "vertical" => $item["img"],
                    "horizontal" => $item["img"],
                    "banner" => ""
                ],
                "status" => $item["enable"] ? "ACTIVE" : "INACTIVE",
                "rtp" => round(mt_rand(96000, 98000) / 1000, 8), // mock RTP
                "online" => rand(50, 100) // mock online player count
            ];
        }, $gamelist);

//        dd($transformedList);

        return response()->json($transformedList);
    }


    public function getGames__($type,$provider)
    {

        $game = core()->getGame();

        $games = $this->repository->gameListSingle($game->id,$type,$provider);
        $gamelist = $games['games'];
        $transformedList = array_map(function ($item) use ($type,$provider) {
            return [
                "id" => $item["gameCode"],
                "provider" => $provider, // ปรับจาก $item["product"] ถ้าอยาก map อัตโนมัติ
                "providerLogo" => [
                    "logoURL" => "",
                    "logoMobileURL" => "",
                    "logoTransparentURL" => ""
                ],
                "gameName" => $item["gameName"],
                "gameCategory" => 'kickoff',
                "gameType" => $type,
                "image" => [
                    "vertical" => $item["gameImage"],
                    "horizontal" => $item["gameImage1"],
                    "banner" => ""
                ],
                "status" => 'ACTIVE',
                "rtp" => round(mt_rand(96000, 98000) / 1000, 8), // mock RTP
                "online" => rand(50, 100) // mock online player count
            ];
        }, $gamelist);

        return response()->json($transformedList);
    }

    public function getGames($type, $provider)
    {

        $game = core()->getGame();

        if ($provider === 25) {
            $oldtype = 'fish';
        } elseif (in_array($provider, [53, 107], true)) {
            $oldtype = 'casino';
        }else{
            $oldtype = $type;
        }

        $response = app('Gametech\Game\Repositories\GameUserRepository')->gameListSingle($game->id, $oldtype, $provider);
        $gamelist = Arr::get($response, 'games', Arr::get($response, 'result', []));

// กันเคส API เพี้ยน type ไม่ใช่ array
        if (!is_array($gamelist)) {
            $gamelist = [];
        }

//        dd($gamelist);

//        $gamelist = $games['games'];
        $transformedList = array_map(function ($item) use ($type, $provider) {
            return [
                "id" => $item["gameCode"],
                "provider" => $provider, // ปรับจาก $item["product"] ถ้าอยาก map อัตโนมัติ
                "providerLogo" => [
                    "logoURL" => "",
                    "logoMobileURL" => "",
                    "logoTransparentURL" => ""
                ],
                "gameName" => $item["gameName"],
                "gameCategory" => 'kickoff',
                "gameType" => $type,
                "image" => [
                    "vertical" => $item["gameImage"],
                    "horizontal" => $item["gameImage1"],
                    "banner" => ""
                ],
                "status" => 'ACTIVE',
                "rtp" => round(mt_rand(96000, 98000) / 1000, 8), // mock RTP
                "online" => rand(50, 100) // mock online player count
            ];
        }, $gamelist);

        return response()->json($transformedList);
    }

    public function getProviders__($type)
    {
        $user = $this->id();
        $game = core()->getGame();
        $response = $this->repository->providerListSingle($game->id,$user);

        if($response['success'] === true){
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

            $game = $grouped[$type];
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
//            dd($grouped[$type]);


            return response()->json(collect($result[$type]));
        }

        return response()->json([]);
    }

    public function getProviders($type = null)
    {
        $user = $this->id();
        $game = core()->getGame();

        $response = app('Gametech\Game\Repositories\GameUserRepository')
            ->providerListSingle($game->id, $user);

        if ($response['success'] === true) {

            /**
             * ดึง id จาก GameSingleRepository
             * - id ตรงกับ lobbyId ของ provider
             * - ใช้เป็น blacklist: ถ้าเจอ id นี้ใน provider ให้ตัดออก
             */
            $singleIds = app('Gametech\Game\Repositories\GameSingleRepository')
                ->all()
                ->pluck('id')
                ->filter()        // กัน null / 0 แปลก ๆ
                ->unique()
                ->values()
                ->all();          // กลายเป็น array ของตัวเลข

            // ทำเป็น lookup table เพื่อเช็คเร็วด้วย isset()
            $singleIdMap = array_fill_keys($singleIds, true);

            $grouped = [];

            foreach ($response['provider'] as $item) {
                // ถ้าไม่มี lobbyId เลย ก็ขอข้าม
                $lobbyId = $item['lobbyId'] ?? null;
                if ($lobbyId === null) {
                    continue;
                }

                /**
                 * ถ้า lobbyId นี้อยู่ใน GameSingle → ไม่ต้องแสดงในรายการนี้
                 * (ตัดออกตั้งแต่ต้น)
                 */
                if (isset($singleIdMap[$lobbyId])) {
                    continue;
                }

                if (is_null($item['position'])) {
                    continue;
                }

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

                // frontend เดิมคง expect แค่ group เดียว
                return response()->json(collect($result[$type] ?? []));
            }

            // ถ้าไม่ได้ส่ง type มาเลย ยังไงก็ให้ array ว่างกลับไป
            return response()->json([]);
        }

        return response()->json([]);
    }


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


    public function getProviders_($type)
    {

        $games = [];
        $gameTypes = $this->gameTypeRepository->findWhere(['enable' => 'Y', 'status_open' => 'Y']);
        foreach ($gameTypes as $types) {
            $gameseamless = $this->gameSeamlessRepository->orderBy('sort')->findWhere(['game_type' => $types->id, 'status_open' => 'Y', 'enable' => 'Y']);
            $gameseamless = collect($gameseamless)->map(function ($items) {
                $items['filepic'] = Storage::url('game_img/'.strtolower($items->filepic).'?v='.date('ymd'));

                return (object) $items;

            });
            $games[strtolower($types->id)] = $gameseamless->toArray();
        }

        $game = $games[$type];
        $transformed = array_map(function ($item) {
            return [
                'provider' => $item['id'], // ใช้ id เป็นรหัส provider
                'providerTier' => 'vvip', // สมมุติค่า หรือดึงจาก logic อื่น
                'providerName' => $item['name'],
                'providerType' => $item['game_type'], // เช่น "COCK"
                'logoURL' => url($item['filepic']),
                'logoTransparentURL' => url($item['filepic']),
                'status' => $item['enable'] === 'Y' ? 'ACTIVE' : 'INACTIVE',
                'detailStatus' => $item['status_open'] === 'Y'
            ];
        }, $game);

        return response()->json($transformed);

    }

    public function gameLogin($type, $provider, $id)
    {

        $url = '';
        if (Auth::guard('customer')->check()) {
            $user = Auth::guard('customer')->user();
        } else {
            dd('cannot get auth');

            return view('wallet::customer.game.cannot');
        }

        dd($user->code);
        $game = core()->getGame();
        $result = $this->repository->autoLoginSingle($member_code, $type, $id, $provider);
        if ($result['success'] === true) {
            $url = $result['url'];
        }
        if ($url == '') {
            return view('wallet::customer.game.cannot');
        }

        return view($this->_config['view'], compact('url'));
    }
}
