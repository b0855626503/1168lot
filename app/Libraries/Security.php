<?php


namespace App\Libraries;


class Security
{
    public static function encrypt($input, $key) {
        $cipher = "aes-128-ecb";
        return openssl_encrypt($input,$cipher,$key,OPENSSL_PKCS1_PADDING);
    }

    private static function pkcs5_pad ($text, $blocksize): string
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
}
