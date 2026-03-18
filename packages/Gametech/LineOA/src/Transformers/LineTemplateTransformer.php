<?php

namespace Gametech\LineOA\Transformers;

use Gametech\LineOA\Contracts\LineTemplate;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class LineTemplateTransformer extends TransformerAbstract
{
    public function transform(LineTemplate $model): array
    {

        return [
            'id' => $model->id,
            'category' => $model->category,
            'key' => $model->key,
            'message' => Str::limit($model->message, 50),
            'description' => $model->description,
            'action' => view('admin::module.line_template.datatables_actions', ['code' => $model->id])->render(),
        ];
    }
}
