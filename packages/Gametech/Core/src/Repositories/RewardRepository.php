<?php

namespace Gametech\Core\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class RewardRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Core\Models\Reward::class;

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

    public function uploadImages( $data, $order, $type = "filepic")
    {

        $request = request();
//        dd(is_null($request->fileupload));

        $hasfile = is_null($request->fileupload);

        if(!$hasfile){
            $file = $order->code.'.'.$request->fileupload->getClientOriginalExtension();
            $dir = 'reward_img';

            Storage::putFileAs($dir, $request->fileupload, $file);
            $order->{$type} = $file;
            $order->save();

        }
    }

    public function loadReward()
    {
        return $this->withCount(['exchange' => function (Builder $query) {
            $query->where('enable', 'Y');
        }])->active()->enable()->get();
    }

    public function loadRewardID($id)
    {
        return $this->withCount(['exchange' => function (Builder $query) {
            $query->where('enable', 'Y');
        }])->active()->enable()->where('code',$id)->first();
    }
}
