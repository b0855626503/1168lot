<?php


namespace App\Libraries;

use Illuminate\Support\Str;



class Kbank
{



    public function clean($text)
    {
        return Str::of($text)->replace('&nbsp;', '')->trim()->__toString();

    }


}
