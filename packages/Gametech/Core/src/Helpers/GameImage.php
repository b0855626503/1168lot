<?php

namespace Gametech\Core\Helpers;

abstract class GameImage
{

    public function getImage($product): array
    {
        $images = $product ? $product->filepic : null;

        if ($images && $images->count()) {
            $image = [
                'small_image_url'    => url('cache/small/' . $images[0]->path),
                'medium_image_url'   => url('cache/medium/' . $images[0]->path),
                'large_image_url'    => url('cache/large/' . $images[0]->path),
                'original_image_url' => url('cache/original/' . $images[0]->path),
            ];
        } else {
            $image = [
                'small_image_url'    => asset('assets/images/default.png'),
                'medium_image_url'   => asset('assets/images/default.png'),
                'large_image_url'    => asset('assets/images/default.png'),
                'original_image_url' => asset('assets/images/default.png'),
            ];
        }

        return $image;
    }
}
