<?php

namespace Gametech\Wallet\Http\Controllers;


use Gametech\Core\Repositories\FaqRepository;
use Gametech\Member\Repositories\MemberRepository;


class ManualController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    private $faqRepository;

    private $memberRepository;


    /**
     * Create a new Repository instance.
     *
     * @param FaqRepository $faqRepo
     * @param MemberRepository $memberRepo
     */
    public function __construct
    (
        FaqRepository $faqRepo,
        MemberRepository $memberRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->faqRepository = $faqRepo;

        $this->memberRepository = $memberRepo;
    }

    public function index()
    {
        $manuals = $this->faqRepository->findWhere(['enable' => 'Y']);

        return view($this->_config['view'], compact('manuals'));
    }


}
