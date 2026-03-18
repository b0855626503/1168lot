<?php

namespace Gametech\Admin\Transformers;


use Gametech\Payment\Contracts\BankRule;
use League\Fractal\TransformerAbstract;

class BankRuleTransformer extends TransformerAbstract
{

    protected $banks;

    public function __construct($banks)
    {
        $this->banks = $banks;

    }

    public function transform(BankRule $model)
    {

        $banks = $this->banks;
//        dd($banks);

//                dd($model->toJson(JSON_PRETTY_PRINT));

        $replace = '';
        $bank_num = explode(',', $model->bank_number);

        if (count($bank_num) > 1) {

            $replaced = [];
            foreach ($bank_num as $i) {
                $replaced[] = $banks[$i];
            }

            $replace = implode(',', $replaced);
//            dd($replace);

        } else {
            $replace = $banks[$model->bank_number];
        }

//        dd($replaced);


        $types = ['IF' => 'ถ้าเป็น', 'IFNOT' => 'ถ้าไม่เป็น'];
        $method = ['CAN' => 'จะสามารถเห็น', 'CANNOT' => 'จะไม่สามารถเห็น'];

        return [
            'code' => (int)$model->code,
            'types' => $types[$model->types],
            'bank' => $types[$model->types] . (!is_null($model->bank) ? $model->bank->name_th : ''),
            'method' => $method[$model->method],
            'other' => $replace,
            'action' => view('admin::module.bank_rule.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
