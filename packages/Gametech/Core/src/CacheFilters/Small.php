<?php

namespace Gametech\Core\CacheFilters;

use Intervention\Image\Image;
use Intervention\Image\Filters\FilterInterface;

class Small implements FilterInterface
{
    /**
     * @param Image $image
     * @return Image
     */
    public function applyFilter(Image $image)
    {
        $width = $height = 120;

        $image->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });

        return $image->resizeCanvas($width, $height, 'center', false, '#fff');
    }
}
