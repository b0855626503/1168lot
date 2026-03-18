<?php

namespace Gametech\Admin\Transformers;


use Gametech\Core\Contracts\Spin;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class SpinTransformer extends TransformerAbstract
{


    public function transform(Spin $model)
    {

        $types = ['WALLET' => 'Wallet (balance)', 'CREDIT' => 'CREDIT (balance_free)', 'DIAMOND' => 'Diamond', 'REAL' => 'รางวัลที่จับต้องได้'];

        return [
            'code' => (int)$model->code,
            'types' => $types[$model->types],
            'name' => $model->name,
            'amount' => $model->amount,
            'winloss' => $model->winloss,
            'filepic' => '<img src="' . Storage::url('spin_img/' . $model->filepic) . '" class="rounded" style="width:50px;height:50px;">',
            'action' => view('admin::module.spin.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
