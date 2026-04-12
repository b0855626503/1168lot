<?php

namespace Gametech\Core\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Core\Models\Config;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConfigRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return class-string<Config>
     */
    public function model(): string
    {
        return Config::class;
    }

    public function updatenew(array $data, mixed $id, string $attribute = 'id'): Config
    {
        $order = $this->find($id);

        $order->update($data);

        $this->uploadImages($data, $order);

        return $order;
    }

    public function uploadImages(array $data, Config $order, string $type = 'logo'): void
    {
        $request = request();
        $fileUpload = $request->file('fileupload');
        $fileUploadNew = $request->file('fileuploadnew');

        if ($fileUpload instanceof UploadedFile) {
            $file2 = 'logo.png';
            $file = Str::random(10).'.'.$fileUpload->extension();
            $dir = 'img';

            Storage::putFileAs($dir, $fileUpload, $file);
            Storage::putFileAs($dir, $fileUpload, $file2);
            $order->{$type} = $file;
            $order->save();
        }

        if ($fileUploadNew instanceof UploadedFile) {
            $filenew2 = 'favicon.png';
            $filenew = Str::random(10).'.'.$fileUploadNew->extension();
            $dirnew = 'img';

            Storage::putFileAs($dirnew, $fileUploadNew, $filenew);
            Storage::putFileAs($dirnew, $fileUploadNew, $filenew2);
            $order->favicon = $filenew;
            $order->save();
        }
    }
}
