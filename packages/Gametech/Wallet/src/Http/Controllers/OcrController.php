<?php

namespace Gametech\Wallet\Http\Controllers;


use Gametech\Game\Repositories\GameRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\AcledaRepository;
use Illuminate\Support\Facades\Storage;
use Alimranahmed\LaraOCR\Services\OcrAbstract;
use Illuminate\Support\Str;
use OCR;

class OcrController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    private $gameRepository;

    private $memberRepository;

    protected $ocr;

    protected $acledaRepository;

    /**
     * Create a new Repository instance.
     *
     * @param GameRepository $gameRepo
     * @param MemberRepository $memberRepo
     */
    public function __construct
    (
        GameRepository $gameRepo,
        MemberRepository $memberRepo,
        AcledaRepository $acledaRepository
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->gameRepository = $gameRepo;

        $this->memberRepository = $memberRepo;

        $this->acledaRepository = $acledaRepository;
    }

    public function readImage(){
        $image = request('image');
        if(isset($image) && $image->getPathName()){
            $ocr = app()->make(OcrAbstract::class);
            $parsedText = $ocr->scan($image->getPathName());

//            dd($parsedText);
            if(preg_match('/\b\d{11}\b/', $parsedText, $matches)) {

                $refid = $matches[0];

            } else if (preg_match('/External Txn Ref\s*:\s*([\dA-Za-z]+)\s+([\dA-Za-z]+)/', $parsedText, $matches)) {

                $refid = $matches[1] . $matches[2];

            }else if (preg_match('/External Txn Ref\s*:\s*([^\.\n]+)/', $parsedText, $matches)) {

                $refid = trim($matches[1]);

            }  else {
                return $this->sendError('Cannot Get Ref Number',200);
            }


//            if(strlen($refid) != 11){
//                return $this->sendError('Cannot Get Ref Number'.$refid,200);
//            }
//            dd($refid);
            if(!trim($refid)){
                return $this->sendError('Cannot Read Image',200);
            }else{
                $user = $this->user();
                $chk = $this->acledaRepository->findOneWhere(['refid' => trim($refid)]);
//                dd($chk);
                if(is_null($chk)){
                    $this->acledaRepository->create([ 'user_code' => $user->code , 'user_name' => $user->user_name , 'refid' => $refid , 'method' => 'ACLEDA']);
                }
                return $this->sendSuccess('Upload Complete');
            }

        }

        return $this->sendError('Please add Image',200);
    }

    public function readImageAba(){
        $image = request('image');
        if(isset($image) && $image->getPathName()){
            $ocr = app()->make(OcrAbstract::class);
            $parsedText = $ocr->scan($image->getPathName());
            preg_match('/\b\d{15}\b/', $parsedText, $matches);

            if (!empty($matches)) {
                $refid = $matches[0];
            } else {
                return $this->sendError('Cannot Get Ref Number',200);
            }


//            if(strlen($refid) != 11){
//                return $this->sendError('Cannot Get Ref Number'.$refid,200);
//            }
//            dd($refid);
            if(!trim($refid)){
                return $this->sendError('Cannot Read Image',200);
            }else{
                $user = $this->user();
                $chk = $this->acledaRepository->findOneWhere(['refid' => trim($refid)]);
//                dd($chk);
                if(is_null($chk)){
                    $this->acledaRepository->create([ 'user_code' => $user->code , 'user_name' => $user->user_name , 'refid' => $refid , 'method' => 'ABA']);
                }
                return $this->sendSuccess('Upload Complete');
            }

        }

        return $this->sendError('Please add Image',200);
    }

}
