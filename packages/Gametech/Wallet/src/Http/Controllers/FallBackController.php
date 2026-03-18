<?php

namespace Gametech\Wallet\Http\Controllers;



use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;


class FallBackController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;


    public function __construct
    (

    )
    {

        $this->_config = request('_config');
    }

    public function index(Request $request)
    {
        $slugOrPath = trim($request->getPathInfo(), '/');

        if (preg_match('/^([a-z0-9-]+\/?)+$/', $slugOrPath)) {

            if ($category = $this->categoryRepository->findByPath($slugOrPath)) {

                return view($this->_config['category_view'], compact('category'));
            }

            if ($product = $this->productRepository->findBySlug($slugOrPath)) {

                $customer = auth()->guard('customer')->user();

                return view($this->_config['product_view'], compact('product', 'customer'));
            }

            abort(404);
        }

        $sliderRepository = app('Webkul\Core\Repositories\SliderRepository');

        $sliderData = $sliderRepository
            ->where('channel_id', core()->getCurrentChannel()->id)
            ->where('locale', core()->getCurrentLocale()->code)
            ->get()
            ->toArray();

        return view('shop::home.index', compact('sliderData'));
    }




}
