<?php

namespace Gametech\Admin\Transformers;


use Gametech\Payment\Contracts\BankAccount;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class BankAccountOutTransformer extends TransformerAbstract
{

    protected $permiss;

    public function __construct($permiss)
    {

        $this->permiss = $permiss;
    }

    public function transform(BankAccount $model)
    {
        $permiss = $this->permiss;
        if($permiss){
            $user = $model->user_name;
            $pass = $model->user_pass;
        }else{
            $user = '***';
            $pass = '***';
        }

        if(auth()->guard('admin')->user()->superadmin == 'Y'){
            $auto = core()->displayBtn($model->code, $model->status_auto, 'status_auto');
        }else{
            $auto = ($model->status_auto == 'Y'?'ทำงาน':'ไม่ทำงาน');
        }
        return [
            'code' => (int)$model->code,
            'bank' => core()->displayBank($model->bank->name_th, $model->bank->filepic),
            'name' => '<span class="text-long" data-toggle="tooltip" title="' . $model->acc_name . '">' . Str::limit($model->acc_name, 30) . '</span>',
            'acc_no' => $model->acc_no,
            'username' => $user,
            'password' => $pass,
            'balance' => core()->textcolor(core()->currency($model->balance)),
            'sort' => $model->sort,
            'auto' => $auto,
            'user_update' => $model->user_update.'<br> [ '.$model->date_update->format('d/m/Y H:i:s').' ]',
            'topup' => core()->displayBtn($model->code, $model->status_topup, 'status_topup'),
            'display' => core()->displayBtn($model->code, $model->display_wallet, 'display_wallet'),
            'enable' => core()->displayBtn($model->code, $model->enable, 'enable'),
            'action' => view('admin::module.bank_account_in.datatables_actions', ['code' => $model->code])->render(),
        ];
    }


}
