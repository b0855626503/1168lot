<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use App\Support\Concerns\LogsMemberEvent;
use Gametech\Member\Models\MemberSelectPro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

class PromotionController extends BaseController
{
    use LogsMemberEvent;

    public function list(Request $request)
    {
        try {
            $member = $request->user() ?: $request->user('customer');
            if (! $member) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $config = core()->getConfigData();
            $memberRepository = app('Gametech\Member\Repositories\MemberRepository');
            $promotionRepository = app('Gametech\Promotion\Repositories\PromotionRepository');
            $proContentRepository = app('Gametech\Promotion\Repositories\PromotionContentRepository');

            $pro = false;
            $proLimit = 0;

            if (($config->seamless ?? 'N') === 'Y') {
                if (($config->pro_onoff ?? 'N') === 'Y' && ($config->pro_wallet ?? 'N') === 'Y') {
                    $pro = true;
                }

                if (($member->promotion ?? 'N') === 'N') {
                    $pro = false;
                }

                if ($pro) {
                    $proLimit = $memberRepository->getPro($member->code);
                    $promotions = $proLimit > 0
                        ? $promotionRepository->loadPromotion($member->code)->toArray()
                        : $promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]])->toArray();
                } else {
                    $promotions = $promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]])->toArray();
                }
            } else {
                $promotions = $promotionRepository->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]])->toArray();
            }

            $proContents = $proContentRepository->orderBy('sort')->findWhere(['enable' => 'Y', ['code', '<>', 0]])->toArray();

            $proContents = collect($proContents)->map(function ($items) {
                $items['filepic'] = Storage::url('procontent_img/'.$items['filepic']);

                return $items;
            });

            $promotions = collect($promotions)->map(function ($items) {
                $items['filepic'] = Storage::url('promotion_img/'.$items['filepic']);

                return $items;
            });

            return $this->sendResponse([
                'promotions' => $proContents->merge($promotions)->values()->all(),
                'getpro' => $proLimit > 0,
            ], 'Complete');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการโปรโมชันได้ในขณะนี้', 422);
        }
    }

    public function select(Request $request)
    {
        try {
            $member = $request->user() ?: $request->user('customer');
            if (! $member) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $request->validate([
                'promotion' => 'required',
            ]);

            $config = core()->getConfigData();
            $promotionRepository = app('Gametech\Promotion\Repositories\PromotionRepository');

            $promotionId = $request->input('promotion');
            $promotion = $promotionRepository->findOneWhere(['code' => $promotionId]);

            if (! $promotion) {
                return $this->sendError('ไม่พบโปรโมชันนี้', 404);
            }

            // log เหตุการณ์
            $this->logMemberEvent($member, 'กดรับโปรก่อนเติมเงิน โปร '.$promotion->name_th);

            if ((float) ($member->balance ?? 0) >= (float) ($config->pro_reset ?? 0)) {
                $this->logMemberEvent($member, 'ไม่ผ่านเงื่อนไข กดรับโปร '.$promotion->name_th.' แต่ ยอดเงินมากกว่า ยอดโปรรีเซต อดรับ');

                return $this->sendError(Lang::get('app.promotion.over_balance').($config->pro_reset ?? 0), 200);
            }

            $pass = false;
            switch ((string) $promotion->id) {
                case 'pro_newuser':
                    $pass = (int) ($member->status_pro ?? 0) !== 1;
                    break;
                case 'pro_firstday':
                    $pass = ! $promotionRepository->checkProFirstDay($member->code);
                    break;
                case 'pro_allbonus':
                    $pass = true;
                    break;
                case 'pro_oneonly_day':
                    $pass = ! $promotionRepository->checkProOneOnlyDay($member->code, $promotion->code);
                    break;
                case 'pro_oneonly_time':
                    $pass = ! $promotionRepository->checkProOneOnlyTime($member->code, $promotion->code);
                    break;
            }

            if (! $pass) {
                MemberSelectPro::where('member_code', $member->code)->delete();

                $this->logMemberEvent($member, 'ไม่ผ่านเงื่อนไข รับโปร');

                return $this->sendError(Lang::get('app.promotion.cannot'), 200);
            }

            MemberSelectPro::updateOrCreate(
                ['member_code' => $member->code],
                ['pro_code' => $promotion->code, 'pro_name' => $promotion->name_th, 'pro_id' => $promotion->id]
            );

            $this->logMemberEvent($member, 'ผ่านเงื่อนไข กดรับโปร '.$promotion->name_th.' รอเติมเงิน');

            return $this->sendResponse([
                'promotion' => $promotion->code,
            ], Lang::get('app.promotion.pass'));
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถเลือกโปรโมชันได้ในขณะนี้', 422);
        }
    }

    public function deselect(Request $request)
    {
        try {
            $member = $request->user() ?: $request->user('customer');
            if (! $member) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $this->logMemberEvent($member, 'กดยกเลิกโปรที่รับ แล้ว');
            MemberSelectPro::where('member_code', $member->code)->delete();

            return $this->sendSuccess(Lang::get('app.promotion.deselect'));
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถยกเลิกโปรโมชันได้ในขณะนี้', 422);
        }
    }
}
