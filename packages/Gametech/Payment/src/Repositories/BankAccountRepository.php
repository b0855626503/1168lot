<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class BankAccountRepository extends Repository
{
    public function getAccount($bank)
    {

        return $this
            ->active()
            ->where('status_auto', 'Y')

            ->where('bank_type', 1)
            ->with('bank')
            ->whereHas('bank', function ($model) use ($bank) {
                $model->where('shortcode', strtoupper($bank));
            })->get();

    }

    public function getAccountOut($bank)
    {

        return $this
            ->active()
            ->where('status_auto', 'Y')
            ->where('bank_type', 2)
            ->with('bank')
            ->whereHas('bank', function ($model) use ($bank) {
                $model->where('shortcode', strtoupper($bank));
            })->get();

    }

    public function getAccountInWeb()
    {

        return $this->in()->active()->topup()->show()->with('bank')->whereHas('bank')->get();

    }

    public function getAccountInAll()
    {

        return $this->in()->active()->where('status_auto', 'Y')->with('bank')->whereHas('bank')->get();

    }

    public function getAccountInAllNew()
    {

        return $this->in()->active()->with('bank')->whereHas('bank')->get();

    }

    public function getAccountInAlls()
    {

        return $this->in()->with('bank')->whereHas('bank')->get();

    }

    public function getAccountOutAll()
    {
        return $this->out()->active()->with('bank')->whereHas('bank')->get();

    }

    public function getAccountOutAllWithApi()
    {
        return $this->out()->active()->where('status_auto','Y')->with('bank')->whereHas('bank')->get();

    }

    public function getAccountOutAlls()
    {
        return $this->out()->with('bank')->whereHas('bank')->get();

    }

    public function getAccountOne($bank, $account)
    {

        return $this
            ->active()
            ->where('bank_type', 1)
            ->where('status_auto', 'Y')
            ->where('local', 'Y')
            ->where('acc_no', $account)
            ->with('bank')
            ->whereHas('bank', function ($model) use ($bank) {
                $model->where('shortcode', strtoupper($bank));
            })->first();

    }

    public function getAccountOneNew($bank, $account)
    {

        return $this
            ->active()
            ->where('bank_type', 1)
            ->where('status_auto', 'Y')
            ->where('acc_no', $account)
            ->with('bank')
            ->whereHas('bank', function ($model) use ($bank) {
                $model->where('shortcode', strtoupper($bank));
            })->first();

    }

    public function getAccountOutOne($id)
    {

        return $this
            ->active()
            ->where('bank_type', 2)
            ->where('status_auto', 'Y')
            ->where('enable', 'Y')
            ->where('code', $id)
            ->with('bank')
            ->first();

    }

    public function getAccountInOutOne($id)
    {

        return $this
            ->active()
            ->where('enable', 'Y')
            ->where('code', $id)
            ->with('bank')
            ->first();

    }


    public function getAccountOutOneNew($id)
    {

        return $this
            ->active()
            ->where('bank_type', 2)
            ->where('status_auto', 'Y')
            ->where('enable', 'Y')
            ->where('acc_no', $id)
            ->with('bank')
            ->first();

    }

    public function getAccountInOne($id)
    {

        return $this
            ->active()
            ->where('bank_type', 1)
            ->where('status_auto', 'Y')
            ->where('enable', 'Y')
            ->where('code', $id)
            ->with('bank')
            ->first();

    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Payment\Models\BankAccount::class;

    }


    public function createnew(array $data)
    {
        $reward = $this->create($data);

        $order = $this->find($reward->code);


        $this->uploadImages($data, $order);


        return $order;
    }

    public function updatenew(array $data, $id, $attribute = "id")
    {
        $order = $this->find($id);

        $order->update($data);


        $this->uploadImages($data, $order);


        return $order;
    }

    public function uploadImages($data, $order, $type = "filepic")
    {

        $request = request();

        $hasfile = is_null($request->fileupload);

        if (!$hasfile) {
            $file = Str::random(10) . '.' . $request->fileupload->extension();
            $dir = 'bank_qr';

            Storage::putFileAs($dir, $request->fileupload, $file);
            $order->{$type} = $file;
            $order->save();

        }
    }
}
