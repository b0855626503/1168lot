<?php

namespace Gametech\LineOA\Transformers;

use Gametech\LineOA\Contracts\LineAccount;
use League\Fractal\TransformerAbstract;

class LineAccountTransformer extends TransformerAbstract
{
    public function transform(LineAccount $model): array
    {

        return [
            'id' => $model->id,
            'name' => $model->name,
            'channel_id' => $model->channel_id,
            'status' => $model->status,
            'action' => view('admin::module.line_account.datatables_actions', ['code' => $model->id])->render(),
        ];
    }
}
