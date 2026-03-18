<?php

namespace Gametech\Core\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpinRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Core\Models\Spin::class;

    }

    public function updatenew(array $data, $id, $attribute = "id")
    {
        $order = $this->find($id);

        $order->update($data);

        $this->uploadImages($data, $order);



//        Event::dispatch('checkout.order.save.after', $order);
//        Event::dispatch('catalog.category.update.after', $id);

        return $order;
    }

    public function uploadImages( $data, $order, $type = "filepic")
    {

        $request = request();

        $hasfile = is_null($request->fileupload);

        if(!$hasfile){

            $file =  Str::random(10).'.'.$request->fileupload->extension();
            $dir = 'spin_img';

            Storage::delete($dir.'/'.$order->{$type});
            Storage::putFileAs($dir, $request->fileupload, $file);
            $order->{$type} = $file;
            $order->save();

        }
    }


}
