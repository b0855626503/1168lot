<?php

namespace Gametech\Wallet\Http\Controllers;

use Gametech\Core\Repositories\SpinRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BonusSpinRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SpinController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $spinRepository;

    protected $memberRepository;

    protected $bonusSpinRepository;

    /**
     * Create a new Repository instance.
     *
     * @param SpinRepository $spinRepo
     * @param MemberRepository $memberRepo
     * @param BonusSpinRepository $bonusSpinRepo
     */
    public function __construct(
        SpinRepository      $spinRepo,
        MemberRepository    $memberRepo,
        BonusSpinRepository $bonusSpinRepo
    ) {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->spinRepository = $spinRepo;
        $this->memberRepository = $memberRepo;
        $this->bonusSpinRepository = $bonusSpinRepo;
    }

    public function index()
    {
        $config = core()->getConfigData();
        if ($config->wheel_open == 'N') {
            return redirect()->back();
        }

        $spins = $this->loadSpin();
        $profile = $this->user();

        return view($this->_config['view'], compact('spins', 'profile'));
    }

    public function loadSpin(): Collection
    {
        $responses = collect($this->spinRepository->findWhere(['enable' => 'Y'])->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'fillStyle'  => $item->spincolor,
                'image'      => Storage::url('spin_img/' . $item->filepic),
                'text'       => number_format($item->amount, 0),
                'code'       => $item->code,
                'amount'     => $item->amount,
                'winloss'    => $item->winloss,
                'spincolor'  => $item->spincolor,
                'name'       => $item->name,
                'types'      => $item->types,
            ];
        });

        return $responses;
    }

    /**
     * สุ่มแบบถ่วงน้ำหนัก
     * - คืน key ที่ถูกเลือก หรือ null ถ้าข้อมูลไม่พร้อม
     */
    private function pickWeighted(array $weightedValues): ?string
    {
        if (empty($weightedValues)) {
            return null;
        }

        $sum = (int) array_sum($weightedValues);
        if ($sum <= 0) {
            // น้ำหนักรวมเป็น 0 => fallback สุ่มแบบเท่า ๆ กัน
            $keys = array_keys($weightedValues);
            return $keys[array_rand($keys)];
        }

        $rand = mt_rand(1, $sum);

        foreach ($weightedValues as $key => $value) {
            $rand -= (int) $value;
            if ($rand <= 0) {
                return (string) $key;
            }
        }

        // fallback (กัน edge case)
        $keys = array_keys($weightedValues);
        return (string) end($keys);
    }

    public function store(Request $request): JsonResponse
    {
        $ip = $request->ip();
        $config = core()->getConfigData();

        $maxbonus = (float) ($config->maxspin ?? 0);

        // รวมยอดรางวัลวันนี้
        $bonustoday_sum = $this->bonusSpinRepository->scopeQuery(function ($query) {
            return $query->where('enable', 'Y')->whereDate('date_create', now()->toDateString());
        });
        $bonustoday = (float) $bonustoday_sum->sum('amount');

        $result = [
            'success' => 'COMPLETE',
        ];

        // โหลดรายการวงล้อ
        $spins = $this->loadSpin();
        if ($spins->count() <= 0) {
            return $this->sendError(Lang::get('app.spin.fail'), 200);
        }

        $range = (360 / $spins->count());

        $change = [];
        $no_change = [];

        $wheel = [];
        $random = [];

        foreach ($spins as $i => $item) {
            // ถ่วงน้ำหนักตาม winloss
            $change[$item['code']] = $item['winloss'];

            // ถ้า amount = 0 ให้เป็นชุด no_change (กันแจกเกินเพดาน)
            if ((float) $item['amount'] == 0) {
                $no_change[$item['code']] = $item['winloss'];
            }

            $start = (($i * $range) + 1);
            $stop = (($i + 1) * $range);

            $wheel[] = [
                'text'  => $item['amount'],
                'image' => $item['image'],
            ];

            $random[$item['code']] = [
                'name'   => $item['name'],
                'amount' => $item['amount'],
                'start'  => $start,
                'stop'   => $stop,
                'types'  => $item['types'],
            ];
        }

        // เลือกชุดสุ่ม: ถ้าแตะเพดานแล้ว ให้เลือกจากชุดที่ amount=0 เป็นหลัก
        $useNoChange = ($maxbonus > 0 && $bonustoday >= $maxbonus);

        $spinid = $this->pickWeighted($useNoChange ? $no_change : $change);

        // กันกรณีที่ no_change ว่าง/สุ่มไม่ได้ => fallback ไป change
        if ($spinid === null || ! isset($random[$spinid])) {
            $spinid = $this->pickWeighted($change);
        }
        if ($spinid === null || ! isset($random[$spinid])) {
            return $this->sendError(Lang::get('app.spin.fail'), 200);
        }

        $point = rand((int) $random[$spinid]['start'], (int) $random[$spinid]['stop']);

        $name_stop = (string) $random[$spinid]['name'];
        $amount_stop = (float) $random[$spinid]['amount'];
        $reward_type = (string) $random[$spinid]['types'];

        // ===== ทำธุรกรรมให้ครบชุด: เช็คเพชร -> หักเพชร -> สร้างประวัติ -> ให้รางวัล =====
        try {
            $payload = DB::transaction(function () use (
                $ip,
                $config,
                $name_stop,
                $amount_stop,
                $reward_type
            ) {
                // lock member row กันกดซ้อน/หลายแท็บ
                $member = $this->user();
                $memberTable = method_exists($member, 'getTable') ? $member->getTable() : 'members';
                $memberId = $this->id();

                $currentDiamond = (int) DB::table($memberTable)
                    ->where('code', $memberId)
                    ->lockForUpdate()
                    ->value('diamond');

                $diamondAfter = $currentDiamond - 1;
                if ($diamondAfter < 0) {
                    return [
                        'ok' => false,
                        'error' => Lang::get('app.spin.usediamond'),
                    ];
                }

                // 1) หักเพชร 1 เม็ด
                $setDiamondData = [
                    'remark'      => 'ร่วมสนุกการหมุนวงล้อ',
                    'amount'      => 1,
                    'method'      => 'W',
                    'member_code' => $memberId,
                    'emp_code'    => 0,
                    'emp_name'    => $this->user()->name,
                ];

                $diamondResult = app('Gametech\Member\Repositories\MemberDiamondLogRepository')->setDiamond($setDiamondData);
                if (! $diamondResult) {
                    return [
                        'ok' => false,
                        'error' => Lang::get('app.spin.fail'),
                    ];
                }

                // reload diamond หลังหักจริง
                $diamondAfterDb = (int) DB::table($memberTable)
                    ->where('code', $memberId)
                    ->value('diamond');

                // 2) สร้างรายการหมุนวงล้อ
                $spinParam = [
                    'member_code'     => $memberId,
                    'bonus_name'      => $name_stop,
                    'reward_type'     => $reward_type,
                    'amount'          => $amount_stop,
                    'credit_before'   => 0,
                    'credit_after'    => 0,
                    'diamond_balance' => $diamondAfterDb,
                    'ip'              => $ip,
                    'user_create'     => $this->user()->name,
                    'user_update'     => $this->user()->name,
                ];

                $spinRecord = $this->bonusSpinRepository->create($spinParam);
                if (! $spinRecord) {
                    return [
                        'ok' => false,
                        'error' => Lang::get('app.spin.fail'),
                    ];
                }

                // 3) ให้รางวัล (ถ้า amount_stop > 0)
                if ($amount_stop > 0) {
                    $grantOk = true;

                    if ($reward_type === 'WALLET') {
                        $setdata = [
                            'kind'        => 'SPIN',
                            'remark'      => 'ได้รับรางวัลจากการหมุนวงล้อ',
                            'amount'      => $amount_stop,
                            'method'      => 'D',
                            'member_code' => $memberId,
                            'refer_code'  => $spinRecord->code,
                            'refer_table' => 'bonus_spin',
                            'emp_code'    => 0,
                            'emp_name'    => $this->user()->name,
                        ];

                        if ($config->seamless == 'Y') {
                            $grantOk = (bool) app('Gametech\Member\Repositories\MemberCreditLogRepository')->setBonus($setdata);
                        } else {
                            if ($config->multigame_open == 'Y') {
                                $grantOk = (bool) app('Gametech\Member\Repositories\MemberCreditLogRepository')->setWallet($setdata);
                            } else {
                                if ($config->freecredit_open == 'Y') {
                                    $grantOk = (bool) app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->setBonus($setdata);
                                } else {
                                    $grantOk = (bool) app('Gametech\Member\Repositories\MemberCreditLogRepository')->setBonus($setdata);
                                }
                            }
                        }
                    } elseif ($reward_type === 'CREDIT') {
                        $setdata = [
                            'kind'        => 'SPIN',
                            'remark'      => 'ได้รับรางวัลจากการหมุนวงล้อ',
                            'amount'      => $amount_stop,
                            'method'      => 'D',
                            'refer_code'  => $spinRecord->code,
                            'refer_table' => 'bonus_spin',
                            'member_code' => $memberId,
                            'emp_code'    => 0,
                            'emp_name'    => $this->user()->name,
                        ];

                        $grantOk = (bool) app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->setWallet($setdata);
                    } elseif ($reward_type === 'DIAMOND') {
                        $setdata = [
                            'remark'      => 'ได้รับรางวัลจากการหมุนวงล้อ',
                            'amount'      => $amount_stop,
                            'method'      => 'D',
                            'member_code' => $memberId,
                            'emp_code'    => 0,
                            'emp_name'    => $this->user()->name,
                        ];

                        $grantOk = (bool) app('Gametech\Member\Repositories\MemberDiamondLogRepository')->setDiamond($setdata);
                    } elseif ($reward_type === 'POINT') {
                        $setdata = [
                            'remark'      => 'ได้รับรางวัลจากการหมุนวงล้อ',
                            'amount'      => $amount_stop,
                            'method'      => 'D',
                            'member_code' => $memberId,
                            'emp_code'    => 0,
                            'emp_name'    => $this->user()->name,
                        ];

                        $grantOk = (bool) app('Gametech\Member\Repositories\MemberPointLogRepository')->setPoint($setdata);
                    } elseif ($reward_type === 'BONUS') {
                        // ✅ แก้บั๊ก: BONUS เดิมไม่มีทางเข้า
                        $setdata = [
                            'kind'        => 'SPIN',
                            'remark'      => 'ได้รับรางวัลจากการหมุนวงล้อ',
                            'amount'      => $amount_stop,
                            'method'      => 'D',
                            'member_code' => $memberId,
                            'refer_code'  => $spinRecord->code,
                            'refer_table' => 'bonus_spin',
                            'emp_code'    => 0,
                            'emp_name'    => $this->user()->name,
                        ];

                        $grantOk = (bool) app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->setBonus($setdata);
                    } else {
                        // reward_type ไม่รู้จัก => ไม่ให้รางวัล แต่ถือว่า fail เพื่อกันข้อมูลเพี้ยน
                        $grantOk = false;
                    }

                    if (! $grantOk) {
                        return [
                            'ok' => false,
                            'error' => Lang::get('app.spin.fail'),
                        ];
                    }
                }

                return [
                    'ok' => true,
                    'diamond' => $diamondAfterDb,
                    'spinRecordCode' => $spinRecord->code,
                ];
            });

            if (empty($payload['ok'])) {
                return $this->sendError($payload['error'] ?? Lang::get('app.spin.fail'), 200);
            }

            $diamond = (int) ($payload['diamond'] ?? 0);

        } catch (Throwable $e) {
            report($e);
            return $this->sendError(Lang::get('app.spin.fail'), 200);
        }

        // ===== สร้าง payload แสดงผล (แยกจาก transaction) =====
        if ($amount_stop > 0) {
            $win = [
                'title'   => Lang::get('app.spin.win') . $name_stop,
                'msg'     => Lang::get('app.spin.win_msg'),
                'img'     => Storage::url('spin_img/spin-win.png'),
                'point'   => $point,
                'diamond' => $diamond,
            ];
        } else {
            $win = [
                'title'   => Lang::get('app.spin.lost'),
                'msg'     => Lang::get('app.spin.lost_msg'),
                'img'     => Storage::url('spin_img/spin-loss.png'),
                'point'   => $point,
                'diamond' => $diamond,
            ];
        }

        $result['diamond'] = $diamond;
        $result['format'] = $win;
        $result['spin'] = $wheel;

        return $this->sendResponseNew($result, 'complete');
    }

    public function history()
    {
        $histories = $this->loadHistory();

        return view($this->_config['view'], compact('histories'));
    }

    public function loadHistory(): array
    {
        $responses = [];
        $result = [];

        $results = $this->user()->bonus_spin()
            ->select(
                'bonus_name',
                'reward_type',
                'amount',
                DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') as date"),
                DB::raw("DATE_FORMAT(date_create,'%H:%i') as time")
            )
            ->orderBy('code', 'desc')
            ->withCasts([
                'date' => 'date:d/m/Y',
            ])
            ->get()
            ->toArray();

        foreach ($results as $item) {
            if ($item['amount'] > 0) {
                $credit = 'ได้รับรางวัล ' . $item['bonus_name'] . ' จำนวน ' . $item['amount'];
            } else {
                $credit = 'ไม่ได้รับรางวัล';
            }

            $responses[$item['date']]['date'] = $item['date'];
            $responses[$item['date']]['data'][] = ['credit' => $credit, 'time' => $item['time']];
        }

        foreach ($responses as $value) {
            $result[] = $value;
        }

        return $result;
    }
}
