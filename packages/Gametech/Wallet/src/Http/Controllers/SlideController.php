<?php

namespace Gametech\Wallet\Http\Controllers;

use Gametech\Core\Repositories\SlideRepository;
use Illuminate\Support\Facades\Storage;

class SlideController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $slideRepo;

    /**
     * Create a new Repository instance.
     */
    public function __construct(

        SlideRepository $slideRepo
    ) {
        $this->middleware('guest');

        $this->_config = request('_config');

        $this->slideRepository = $slideRepo;
    }

    public function loadSlide()
    {
        $response = $this->slideRepository->orderBy('sort')->findWhere(['enable' => 'Y']);
        if (count($response) > 0) {

            $response->map(function ($item) {
                $item->image = Storage::url('slide_img/'.$item->filepic);

                return $item;
            });

            return response()->json(['success' => true, 'data' => $response]);
        } else {
            return response()->json(['success' => false]);
        }

    }
}
