<?php

namespace Gametech\Admin\Transformers;


use Carbon\Carbon;
use Gametech\Payment\Contracts\Bank;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class BankTransformer extends TransformerAbstract
{


    public function transform(Bank $model): array
    {
        $version = (int) round(microtime(true) * 1000);
        if (!empty($model->date_update)) {
            try {
                $version = Carbon::parse((string) $model->date_update)->getTimestampMs();
            } catch (\Throwable $e) {
                // keep fallback version
            }
        }

        return [
            'code' => (int)$model->code,
            'name_th' => $model->name_th,
            'name_en' => $model->name_en,
            'shortcode' => $model->shortcode,
            'enable' => '<button type="button" class="btn ' . ($model->enable == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->enable) . "'" . "," . "'enable'" . ')">' . ($model->enable == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'show' => '<button type="button" class="btn ' . ($model->show_regis == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs icon-only" onclick="editdata(' . $model->code . "," . "'" . core()->flip($model->show_regis) . "'" . "," . "'show_regis'" . ')">' . ($model->show_regis == 'Y' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . '</button>',
            'filepic' => '<img src="' . Storage::url('bank_img/' . $model->filepic) . '?v=' . $version . '" class="rounded" style="width:50px;height:50px;">',
            'action' => view('admin::module.bank.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
