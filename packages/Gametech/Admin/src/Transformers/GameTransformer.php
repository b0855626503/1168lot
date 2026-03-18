<?php

namespace Gametech\Admin\Transformers;


use Gametech\Game\Contracts\Game;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class GameTransformer extends TransformerAbstract
{


    public function transform(Game $model)
    {


        $game_id = preg_replace('/\d/', '', $model->id);
        $game = ucfirst($game_id);
        if (is_file(base_path('packages/Gametech/Game/src/Repositories/Games/' . $game . 'Repository.php'))) {
            $game_status = core()->textcolor('มีระบบเกมแล้ว');
        } else {
            $game_status = core()->textcolor('ยังไม่มีระบบเกม', 'text-danger');
        }

        if ($model->batch_game == 'Y') {
            if ($model->id === 'jokerNew') {
                $model->id = 'joker';
            }
            $normal = $remain = DB::table("users_" . $model->id)->where('enable', 'Y')->where('use_account', 'N')->where('freecredit', 'N')->count();
            $free = $remain = DB::table("users_" . $model->id)->where('enable', 'Y')->where('use_account', 'N')->where('freecredit', 'Y')->count();
        } else {
            $normal = 0;
            $free = 0;
        }

        return [
            'code' => (int)$model->code,
            'name' => $model->name,
            'game_type' => $model->game_type,
//            'user_demo' => $model->user_demo,
//            'user_demofree' => $model->user_demofree,
            'demo' => 'Real : ' . $model->user_demo . '<br> Free : ' . $model->user_demofree,
            'account' => 'Real : ' . number_format($normal) . '<br> Free : ' . number_format($free),
            'status' => $game_status,
//            'auto_open' => '<button type="button" class="btn ' . ($model->auto_open == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->auto_open) . "'" . "," . "'auto_open'" . ')">' . ($model->auto_open == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'status_open' => '<button type="button" class="btn ' . ($model->status_open == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->status_open) . "'" . "," . "'status_open'" . ')">' . ($model->status_open == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'batch_game' => ($model->batch_game == 'Y' ? 'เพิ่มที่เมนู Batch User' : 'สมัครได้ทันที'),
            'enable' => '<button type="button" class="btn ' . ($model->enable == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->enable) . "'" . "," . "'enable'" . ')">' . ($model->enable == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'filepic' => '<img src="' . Storage::url('game_img/' . $model->filepic) . '" class="rounded" style="width:50px;height:50px;">',
            'action' => view('admin::module.game.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
