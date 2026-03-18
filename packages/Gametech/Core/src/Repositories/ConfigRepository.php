<?php

namespace Gametech\Core\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConfigRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Core\Models\Config::class;

    }

    public function updatenew(array $data, $id, $attribute = "id")
    {
        $order = $this->find($id);

//        dd($order);

        $order->update($data);


        $this->uploadImages($data, $order);


        return $order;
    }

    public function uploadImages( $data, $order, $type = "logo")
    {

        $request = request();
//        dd($request->fileupload);

        $hasfile = is_null($request->fileupload);
        $hasfilenew = is_null($request->fileuploadnew);

        if(!$hasfile){
            $file2 =  'logo.png';
            $file = Str::random(10) . '.' . $request->fileupload->extension();
            $dir = 'img';

            Storage::putFileAs($dir, $request->fileupload, $file);
            Storage::putFileAs($dir, $request->fileupload, $file2);
            $order->{$type} = $file;
            $order->save();

        }

        if(!$hasfilenew){
            $filenew2 =  'favicon.png';
            $filenew =  Str::random(10) . '.' . $request->fileupload->extension();
            $dirnew = 'img';

            Storage::putFileAs($dirnew, $request->fileuploadnew, $filenew);
            Storage::putFileAs($dirnew, $request->fileuploadnew, $filenew2);
            $order->favicon = $filenew;
            $order->save();

        }
    }
}
