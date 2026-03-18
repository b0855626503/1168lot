<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Storage;

class BankRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model(): string
    {
        return \Gametech\Payment\Models\Bank::class;

    }

    public function getBankInAccount()
    {
        return $this->with(['banks_account' => function ($query) {
            $query->in()->active()->topup()->show();
        }])->whereHas('banks_account', function ($query) {
            $query->in()->active()->topup()->show();
        })->findWhere(['enable' => 'Y', ['code', '<>', 0]]);
    }

    public function getBankOutAccount()
    {
        return $this->with(['bank_account' => function ($query) {
            $query->out()->active();
        }])->whereHas('bank_account', function ($query) {
            $query->out()->active();
        })->findWhere(['enable' => 'Y', ['code', '<>', 0]]);
    }

    public function getBankInAccountAll()
    {
        return $this->with(['banks_account' => function ($query) {
            $query->orderBy('sort','asc')->in()->active()->show();
        }])->whereHas('banks_account', function ($query) {
            $query->in()->active()->show();
        })->findWhere(['enable' => 'Y', ['code', '<>', 0]]);

    }

    public function getBankOutAccountAll()
    {
        return $this->with('bank_account')->whereHas('bank_account', function ($query) {
            $query->out()->active();
        })->findWhere(['enable' => 'Y', ['code', '<>', 0]]);

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

        if(!$hasfile){
            $file = strtolower($order->shortcode).'.'.$request->fileupload->getClientOriginalExtension();
            $dir = 'bank_img';

            Storage::putFileAs($dir, $request->fileupload, $file);
            $order->{$type} = $file;
            $order->save();

        }

//        if ($request->fileupload !== 'undefined') {
//            $file = $order->filepic;
//            $dir = 'bank_img';
//
//            Storage::putFileAs($dir, $request->fileupload, $file);
//            $order->{$type} = $file;
//            $order->save();
//
//        }
    }
}
