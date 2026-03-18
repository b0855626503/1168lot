<?php

namespace Gametech\Admin\Transformers;


use Carbon\Carbon;
use Gametech\Member\Contracts\MemberCreditLog;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class SeamlessTransformer extends TransformerAbstract
{

    protected $no;

    public function __construct($no = 1)
    {
        $this->no = $no;

    }

    public function transform(array $model)
    {

//        dd($model);
        return [
            'code' => ++$this->no,
            'username' => $model['username'],
            'betTime' => Carbon::createFromTimestampMs($model['betTime'], 'Asia/Bangkok')->format('Y-m-d H:i:s'),
            'settleTime' => Carbon::createFromTimestampMs($model['settleTime'], 'Asia/Bangkok')->format('Y-m-d H:i:s'),
            'gameCompany' => $model['gameCompany'],
            'gameRef' => $model['gameRef'],
            'betAmount' => $model['betAmount'],
            'settleAmount' => $model['settleAmount'],
            'result' => $model['result'],

        ];
    }


}
