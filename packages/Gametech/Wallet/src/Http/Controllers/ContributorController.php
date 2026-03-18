<?php

namespace Gametech\Wallet\Http\Controllers;

use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

class ContributorController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $memberRepository;

    /**
     * Create a new Repository instance.
     * @param MemberRepository $memberRepo
     */
    public function __construct
    (
        MemberRepository $memberRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->memberRepository = $memberRepo;

    }

    public function index()
    {
        $faststart = 0;
        $profile = $this->memberRepository->getAff($this->id());


        $promotion = app('Gametech\Promotion\Repositories\PromotionRepository')->findOneWhere(['id' => 'pro_faststart']);
        if($promotion){
            if($promotion->length_type === 'PERCENT'){
                $faststart = $promotion->bonus_percent.' %';
            }else{
                $faststart = $promotion->bonus_price;
            }

        }
        $banks[] = ['method' => 'contributor', 'name' => Lang::get('app.con.list'), 'color' => 'green', 'id' => 'contributor-tab', 'href' => '#contributor', 'select' => 'true'];
        $banks[] = ['method' => 'contributor_income', 'name' => Lang::get('app.con.income'), 'color' => 'red', 'id' => 'contributor_income-tab', 'href' => '#contributor_income', 'select' => 'false'];

//        dd($profile);
//        $banks[] = ['method' => 'contributor', 'name' => 'รายชื่อเพื่อนที่แนะนำมา'];
//        $banks[] = ['method' => 'contributor_income', 'name' => 'รายได้จากเพื่อนที่แนะนำมา'];

        $banks = collect($banks);



        return view($this->_config['view'], compact('profile', 'banks','faststart'));
    }

    public function indextest()
    {
        $profile = $this->memberRepository->getAff($this->id());

        $banks[] = ['method' => 'contributor2', 'name' => Lang::get('app.con.list'), 'color' => 'green', 'id' => 'contributor-tab', 'href' => '#contributor', 'select' => 'true'];
        $banks[] = ['method' => 'contributor_income', 'name' => Lang::get('app.con.income'), 'color' => 'red', 'id' => 'contributor_income-tab', 'href' => '#contributor_income', 'select' => 'false'];


//        $banks[] = ['method' => 'contributor', 'name' => 'รายชื่อเพื่อนที่แนะนำมา'];
//        $banks[] = ['method' => 'contributor_income', 'name' => 'รายได้จากเพื่อนที่แนะนำมา'];

        $banks = collect($banks);

        return view($this->_config['view'], compact('profile', 'banks'));
    }

    public function indextest_()
    {
        $profile = $this->memberRepository->getAffTest($this->id());
        dd($profile);

        return view($this->_config['view'], compact('profile'));
    }


    public function store()
    {
        $result['success'] = true;
        $date_start = request()->input('date_start');
        $date_stop = request()->input('date_stop');
        $id = request()->input('id');

        switch ($id) {
            case 'contributor':
                $result['data'] = $this->loadDownlines($date_start, $date_stop);
                break;
            case 'contributor2':
                $result['data'] = $this->loadDownline2($date_start, $date_stop);
                break;
            case 'contributor_income':
                $result['data'] = $this->loadDownlineIncomes($date_start, $date_stop);
                break;
            default:
                $result['data'] = '';
        }

        return json_encode($result);

    }

    public function loadDownline($date_start = null, $date_stop = null): Collection
    {

        $responses = $this->memberRepository->loadDownline($this->id(), $date_start, $date_stop);

        if (empty($responses)) {
            return collect([]);
        }

        $downline = $responses->down;

        return collect($downline)->map(function ($items) {
            $item = (object)$items;

            return [
                'code' => $item['code'],
                'date_regis' => core()->Date($item['date_regis'], 'd/m/Y'),
                'name' => $item['name']
            ];
        });


    }

    public function loadDownline2($date_start = null, $date_stop = null): Collection
    {

        $responses = $this->memberRepository->loadDownline2($this->id(), $date_start, $date_stop);
//        dd($responses);

        if (empty($responses)) {
            return collect([]);
        }

        $downline = $responses->down;

        return collect($downline)->map(function ($items) {
            $item = (object)$items;

            return [
                'code' => $item['code'],
                'date_regis' => core()->Date($item['date_regis'], 'd/m/Y'),
                'name' => $item['name']
            ];
        });


    }

    public function loadDownlineIncome($date_start = null, $date_stop = null): Collection
    {

        $responses = collect($this->memberRepository->loadDownlineIncome($this->id(), $date_start, $date_stop))->toArray();
        if (empty($responses)) {
            return collect([]);
        }

        return collect($responses)->map(function ($items) {
            $item = (object)$items;
            $sub = (object)$item->down;

            return [
                'code' => $sub->code,
                'date_regis' => core()->Date($sub->date_regis, 'd/m/Y'),
                'amount' => $item->amount,
                'bonus' => $item->credit_bonus,
                'name' => $sub->name,
                'date_topup' => core()->formatDate($item->date_create, 'd/m/Y H:i:s')

            ];

        });

    }

    public function loadDownlines($date_start = null, $date_stop = null): Collection
    {

        $responses = collect($this->memberRepository->loadDownline($this->id(), $date_start, $date_stop))->toArray();

        if (empty($responses)) {
            return collect([]);
        }

        $downline = $responses['down'];

        return collect($downline)->map(function ($items) {
            $item = (object)$items;

            return [
                'id' => $item->name,
                'date_create' => core()->Date($item->date_regis, 'd/m/Y'),
                'amount' => ''
            ];
        });


    }

    public function loadDownlineIncomes($date_start = null, $date_stop = null): Collection
    {

        $responses = collect($this->memberRepository->loadDownlineIncome($this->id(), $date_start, $date_stop))->toArray();
        if (empty($responses)) {
            return collect([]);
        }

        return collect($responses)->map(function ($items) {
            $item = (object)$items;
            $sub = (object)$item->down;

            return [
                'id' => $sub->name,
                'date_regis' => core()->Date($sub->date_regis, 'd/m/Y'),
                'amount' => $item->credit_bonus,
                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i:s')

            ];

        });

    }

    public function contributor()
    {
        $profile = $this->memberRepository->getAff($this->id());

        $pro_percent = app('Gametech\Promotion\Repositories\PromotionRepository')->findOneWhere(['id' => 'pro_faststart'],['bonus_percent']);
        $profile = collect($profile)->prepend($pro_percent->bonus_percent.' %','percent');
        return $this->sendResponse($profile,'Complete');
    }
}
