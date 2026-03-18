<?php

namespace Gametech\Wallet\Http\Controllers;



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Laravel\Facades\Image;

class ImageController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */


    public function resize(Request $request)
    {
        $url = $request->query('url'); // ดึง URL จากพารามิเตอร์
        $width = $request->query('width', 156); // ค่าปรับขนาดเริ่มต้น 300px
        $height = $request->query('height', 156); // ความสูง (อาจให้ Laravel คำนวณอัตโนมัติ)

        // ดึงรูปจาก URL
        $response = Http::get($url);
        if (!$response->successful()) {
            return response()->json(['error' => 'Unable to fetch image'], 404);
        }

        // ใช้ Intervention Image ปรับขนาด
        $image = Image::read($response->body())
            ->scale($width, $height);

        // ส่งรูปภาพกลับโดยไม่บันทึกไฟล์
        return response()->image($image, Format::WEBP, quality: 65);

    }




}
