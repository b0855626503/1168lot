<?php

namespace Gametech\Wallet\Http\Controllers;


use App\Notifications\GetOtp;
use Gametech\Member\Repositories\MemberOtpRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;


class VerifyController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;


    protected $memberRepository;

    protected $memberOtpRepository;


    /**
     * Create a new Repository instance.
     *
     * @param MemberRepository $memberRepo
     */
    public function __construct
    (

        MemberRepository    $memberRepo,
        MemberOtpRepository $memberOtpRepo
    )
    {
//        $this->middleware('customer');

        $this->_config = request('_config');


        $this->memberRepository = $memberRepo;

        $this->memberOtpRepository = $memberOtpRepo;
    }

    public function index()
    {
        return view($this->_config['view']);
    }

    public function update(Request $request)
    {
        $otp = $request->input('otp');

        $user = $this->user();

        if ($user->otp == $otp && $user->confirm == 'N') {
            $user->confirm = 'Y';
            $user->save();
            session()->flash('success', 'ทำรายการสำเร็จ ขอบคุณสำหรับการยืนยันตน');
            return redirect()->route('customer.home.index');
        } else {
            session()->flash('error', 'รหัส OTP ไม่ถูกต้อง โปรดตรวจสอบ');
            return redirect()->back();
        }

    }

    public function requestOtp(Request $request)
    {
        $data['confirm'] = 'N';
        $mobile = $request->input('mobile');
        $mobile = Str::of($mobile)->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
        $check_dup = $this->memberRepository->findOneByField('tel', $mobile);


        if (!is_null($check_dup)) {
            return $this->sendError('เบอร์ดังกล่าว มีในระบบแล้ว', 200);
        }

//        if($check_dup)

//        $check = $this->memberOtpRepository->findOneByField('mobile', $mobile);
        $check = $this->memberOtpRepository->getlatest($mobile);
//        dd($check->exists());
//        if (!is_null($check)) {
        if ($check->exists()) {
//            dd('here');
            $data['confirm'] = $check->confirm;
            $data['minute'] = $check->isDiff();
            if($check->isExpired()){
                $response = $this->memberOtpRepository->getOtp($mobile);
                if ($response->code) {
                    $data['minute'] = $response->isDiff();
                    Notification::send($mobile,new GetOtp('Wallet OTP : '.$response->otp));
                    return $this->sendResponse($data, 'ระบบส่ง SMS แล้ว โปรดตรวจสอบ OTP เพื่อยืนยัน ภายใน 3 นาที');
                } else {
                    return $this->sendError('ไม่สามารถ ทำรายการได้ โปรดลองใหม่อีกครั้ง', 200);
                }
            }else{
                return $this->sendResponse($data, 'ระบบได้เคยส่ง SMS ไปแล้ว โปรดตรวจสอบ OTP เพื่อยืนยัน');

            }
//            if ($check->confirm == 'N') {
//                $response = $this->memberOtpRepository->getOtp($mobile);
//                if ($response->code) {
//                    Notification::send($mobile,new GetOtp('Wallet OTP : '.$response->otp));
//                    return $this->sendResponse($data, 'ระบบส่ง SMS แล้ว โปรดตรวจสอบ OTP เพื่อยืนยัน');
//                } else {
//                    return $this->sendError('ไม่สามารถ ทำรายการได้ โปรดลองใหม่อีกครั้ง', 200);
//                }
//            } else {
//
//                return $this->sendResponse($data, 'ระบบได้เคยส่ง SMS ไปแล้ว โปรดตรวจสอบ OTP เพื่อยืนยัน');
//            }
        } else {

            $response = $this->memberOtpRepository->getOtp($mobile);
            if ($response->code) {
                $data['minute'] = $response->isDiff();
                Notification::send($mobile,new GetOtp('Wallet OTP : '.$response->otp));
                return $this->sendResponse($data, 'ระบบส่ง SMS แล้ว โปรดตรวจสอบ OTP เพื่อยืนยัน ภายใน 3 นาที');

            } else {
                return $this->sendError('ไม่สามารถ ทำรายการได้ โปรดลองใหม่อีกครั้ง', 200);
            }

        }
    }

    public function checkMobile(Request $request)
    {
        $mobile = $request->input('mobile');
        $otp = $request->input('otp');

        $mobile = Str::of($mobile)->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
        $otp = Str::of($otp)->replaceMatches('/[^0-9]++/', '')->trim()->__toString();

        $check = $this->memberOtpRepository->orderBy('date_create','desc')->findOneWhere(['mobile' => $mobile]);
        if (!is_null($check)) {
            if($check->isExpired()){
                return $this->sendError('OTP หมดอายุ โปรดทำรายการยืนยัน ใหม่อีกครั้ง', 200);
            }
            if($check->otp === $otp){
                $check->confirm = 'Y';
                $check->save();
                return $this->sendSuccess('complete');
            }else{
                return $this->sendError('OTP ไม่ถูกต้อง โปรดตรวจสอบ', 200);
            }

        } else {
            return $this->sendError('OTP ไม่ถูกต้อง โปรดตรวจสอบ', 200);
        }
    }


}
