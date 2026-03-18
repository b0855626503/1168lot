<?php

namespace Gametech\Promotion\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PromotionContentRepository extends Repository
{

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model(): string
    {
        return \Gametech\Promotion\Models\PromotionContent::class;

    }

    public function createnew(array $data)
    {
        $reward = $this->create($data);

        $order = $this->findOrFail($reward->code);


        $this->uploadImages($data, $order);


        return $order;
    }

    public function updatenew(array $data, $id, $attribute = "id")
    {
        $order = $this->findOrFail($id);

        $order->update($data);


        $this->uploadImages($data, $order);


        return $order;
    }

    public function uploadImages( $data, $order, $type = "filepic")
    {

        $request = request();

        $hasfile = is_null($request->fileupload);

        if(!$hasfile){
            $file = Str::random(10).'.'.$request->fileupload->extension();
            $dir = 'procontent_img';

            Storage::putFileAs($dir, $request->fileupload, $file);
            $order->{$type} = $file;
            $order->save();

        }
    }
}
