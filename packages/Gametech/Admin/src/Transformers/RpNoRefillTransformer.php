<?php

namespace Gametech\Admin\Transformers;


use Gametech\Member\Contracts\Member;
use League\Fractal\TransformerAbstract;


class RpNoRefillTransformer extends TransformerAbstract
{



    public function transform(Member $model)
    {


//        dd($model->toJson(JSON_PRETTY_PRINT));

//        $config = $this->config;
//        $admin = $this->admin;

//        if (bouncer()->hasPermission('wallet.member.tel')) {
//            $tel = $model->tel;
//        } else {
//            $tel = '*****';
////            $tel = StarReplacer::replace($model->tel);
//        }

//        if (bouncer()->hasPermission('wallet.member.password')) {
//            $pass = $model->user_pass;
//        } else {
//            $pass = '*****';
////            $tel = StarReplacer::replace($model->tel);
//        }

//        if ($config->seamless === 'Y') {
//
//            $pro = '<button class="btn btn-xs icon-only ' . ($model->promotion == 'Y' ? 'btn-success' : 'btn-danger') . '" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->promotion) . "'" . "," . "'promotion'" . ')">' . ($model->promotion == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>';
////            $pro = '<button class="btn btn-xs icon-only ' . ($model->status_pro == 1 ? 'btn-success' : 'btn-danger') . '" onclick="editdata(' . $model->code . "," . "'" . core()->flipnum($model->status_pro) . "'" . "," . "'status_pro'" . ')">' . ($model->status_pro == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>';
//            $newuser = '<button class="btn btn-xs icon-only ' . ($model->status_pro == 1 ? 'btn-success' : 'btn-danger') . '" onclick="editdata(' . $model->code . "," . "'" . core()->flipnum($model->status_pro) . "'" . "," . "'status_pro'" . ')">' . ($model->status_pro == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>';
//
//
//        } else {
//
//
//            if ($config->multigame_open === 'Y') {
//                $pro = '';
////            $pro = '<button class="btn btn-xs icon-only ' . ($model->promotion == 'Y' ? 'btn-success' : 'btn-danger') . '" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->promotion) . "'" . "," . "'promotion'" . ')">' . ($model->promotion == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>';
//                $newuser = '<button class="btn btn-xs icon-only ' . ($model->status_pro == 1 ? 'btn-success' : 'btn-danger') . '" onclick="editdata(' . $model->code . "," . "'" . core()->flipnum($model->status_pro) . "'" . "," . "'status_pro'" . ')">' . ($model->status_pro == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>';
//
//            } else {
//
//                $user = core()->getGameUser($model->code);
//                $model->balance = $user['balance'];
//
//                $pro = '<button class="btn btn-xs icon-only ' . ($model->promotion == 'Y' ? 'btn-success' : 'btn-danger') . '" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->promotion) . "'" . "," . "'promotion'" . ')">' . ($model->promotion == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>';
////            $pro = '<button class="btn btn-xs icon-only ' . ($model->status_pro == 1 ? 'btn-success' : 'btn-danger') . '" onclick="editdata(' . $model->code . "," . "'" . core()->flipnum($model->status_pro) . "'" . "," . "'status_pro'" . ')">' . ($model->status_pro == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>';
//                $newuser = '<button class="btn btn-xs icon-only ' . ($model->status_pro == 1 ? 'btn-success' : 'btn-danger') . '" onclick="editdata(' . $model->code . "," . "'" . core()->flipnum($model->status_pro) . "'" . "," . "'status_pro'" . ')">' . ($model->status_pro == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>';
//
//            }
//        }

        return [
            'code' => (int)$model->code,
            'date_regis' => $model->date_create->format('d/m/Y H:i:s'),
            'name' => $model->name,
            'bank' => (is_null($model->bank) ? '' : core()->displayBank($model->bank->shortcode, $model->bank->filepic)),
            'acc_no' => $model->acc_no,
            'user_name' => $model->user_name,
            'tel' => $model->tel,
            'lineid' => $model->lineid,
            'enable' => '<button class="btn btn-xs icon-only disable' . ($model->enable == 'Y' ? 'btn-success' : 'btn-danger') . '" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->enable) . "'" . "," . "'enable'" . ')">' . ($model->enable == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
        ];
    }


}
