<?php

use League\Fractal\Serializer\DataArraySerializer;

return [
    /*
     * Request key name to parse includes on fractal.
     */
    'includes' => 'include',

    /*
     * Default fractal serializer.
     */
    'serializer' => DataArraySerializer::class,
];
