<?php

namespace Gametech\Game\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GameContentRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Game\Models\GameContent::class;

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
            $file = Str::lower($order->id).'.'.$request->fileupload->extension();;
            $dir = 'gamecontent_img';

            Storage::putFileAs($dir, $request->fileupload, $file);
            $order->{$type} = $file;
            $order->save();

        }

    }

}
