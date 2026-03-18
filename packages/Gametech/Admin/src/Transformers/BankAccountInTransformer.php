<?php

namespace Gametech\Admin\Transformers;

use Gametech\Payment\Contracts\BankAccount;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class BankAccountInTransformer extends TransformerAbstract
{
    protected $permiss;
    protected $admin;

    public function __construct($permiss)
    {
        $this->admin = auth()->guard('admin')->user();
        $this->permiss = $permiss;
    }

    public function displayBtn($code, $method, $methodtxt)
    {
        return '<button type="button" class="btn ' . ($method == 'Y' ? 'btn-success' : 'btn-danger') . ' btn-xs" onclick="editdata(' . $code . ',' . "'" . $this->flip($method) . "'" . ',' . "'$methodtxt'" . ')">' . ($method == 'Y' ? '<i class="fa fa-comment-sms"></i> Line Webhook' : '<i class="fab fa-line"></i> Line API') . '</button>';
    }


    public function flip($data)
    {
        return $data === 'Y' ? 'N' : 'Y';
    }

    public function displayLineConnectBtn(int $code, string $bankShortcode, string $accNo, string $baseApi): string
    {
        $bankShortcode = e($bankShortcode);
        $accNo = e($accNo);
        $baseApi = e($baseApi);

        return '<button type="button" class="btn btn-info btn-xs icon-only" '
            . 'onclick="lineConnentModal(' . $code . ')" '
            . 'data-bank="' . $bankShortcode . '" '
            . 'data-acc="' . $accNo . '" '
            . 'data-baseapi="' . $baseApi . '" '
            . 'title="LINE Connect">'
            . '<i class="fab fa-line"></i>'
            . '</button>';
    }

    protected function buildBaseApi(): string
    {
        $apiRoute = config('gametech.api_url') ?? config('app.admin_url');

        $apiRoute = "$apiRoute." . (
            is_null(config('app.admin_domain_url'))
                ? config('app.domain_url')
                : config('app.admin_domain_url')
            );

        $apiRoute = trim((string) $apiRoute);

        if ($apiRoute === '') {
            return 'https://api';
        }

        // บังคับ https เสมอ
        if (Str::startsWith($apiRoute, ['https://', 'http://'])) {
            $apiRoute = preg_replace('#^http://#i', 'https://', $apiRoute);
        } else {
            $apiRoute = 'https://' . ltrim($apiRoute, '/');
        }

        // ตัด / ท้าย
        $apiRoute = rtrim($apiRoute, '/');

        // ถ้ายังไม่มี /api ท้าย ให้เติม
        if (!Str::endsWith($apiRoute, '/api')) {
            $apiRoute .= '/api';
        }

        return $apiRoute;
    }

    public function transform(BankAccount $model)
    {
        $permiss = $this->permiss;
        if ($permiss) {
            $user = $model->user_name;
            $pass = $model->user_pass;
        } else {
            $user = '***';
            $pass = '***';
        }



        if ($this->admin->superadmin === 'Y' || $this->admin->role_id == 1) {
            $payment = core()->displayBtn($model->code, $model->payment, 'payment');
        } else {
            $payment = '';
        }

        // ===== LINE Connect (เฉพาะ SCB เท่านั้น) =====
        $bankShortcode = (string) data_get($model, 'bank.shortcode', '');
        $accNo = (string) ($model->acc_no ?? '');
        $baseApi = $this->buildBaseApi();

        $choose = '';
        if ($bankShortcode === 'SCB' || $bankShortcode === 'GSB') {
            $choose = $this->displayBtn($model->code, $model->webhook, 'webhook');
        }

        $lineconnect = '';
        if ($bankShortcode === 'SCB' || $bankShortcode === 'GSB') {
            $lineconnect = $this->displayLineConnectBtn(
                (int) $model->code,
                $bankShortcode,
                $accNo,
                $baseApi
            );
        }

        return [
            'code' => (int) $model->code,
            'bank' => core()->displayBank($model->bank->name_th, $model->bank->filepic),
            'name' => '<span class="text-long" data-toggle="tooltip" title="' . $model->acc_name . '">' . Str::limit($model->acc_name, 30) . '</span>',
            'acc_no' => $model->acc_no,
            'username' => $user,
            'password' => $pass,
            'user_update' => $model->user_update . '<br> [ ' . $model->date_update->format('d/m/Y H:i:s') . ' ]',
            'balance' => core()->textcolor(core()->currency($model->balance)),
            'sort' => $model->sort,
            'auto' => core()->displayBtn($model->code, $model->status_auto, 'status_auto'),
            'topup' => core()->displayBtn($model->code, $model->status_topup, 'status_topup'),
            'display' => core()->displayBtn($model->code, $model->display_wallet, 'display_wallet'),
            'payment' => $payment,
            'slip' => core()->displayBtn($model->code, $model->slip, 'slip'),
            'enable' => core()->displayBtn($model->code, $model->enable, 'enable'),
            'choose' => $choose,
            'lineconnect' => $lineconnect,
            'action' => view('admin::module.bank_account_in.datatables_actions', ['code' => $model->code])->render(),
        ];
    }
}
