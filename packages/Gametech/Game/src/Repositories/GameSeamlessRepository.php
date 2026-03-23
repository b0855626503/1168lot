<?php

namespace Gametech\Game\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GameSeamlessRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Game\Models\GameSeamless::class;

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

        $type2 = 'icon';
        $request = request();

        if ($request->hasFile('fileupload') && $request->file('fileupload')->isValid()) {
            $file = Str::lower($order->id).'.'.$request->file('fileupload')->extension();
            $dir = 'game_img';

            Storage::putFileAs($dir, $request->file('fileupload'), $file);
            $order->{$type} = $file;
            $order->save();

        }

        if ($request->hasFile('fileupload2') && $request->file('fileupload2')->isValid()) {
            $file = Str::lower($order->id).'_icon.'.$request->file('fileupload2')->extension();
            $dir = 'icon_img';

            Storage::putFileAs($dir, $request->file('fileupload2'), $file);
            $order->{$type2} = $file;
            $order->save();

        }
    }

}
