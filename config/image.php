<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    |
    | Supported: "gd", "imagick"
    |
    */

    'driver'            => 'gd',
    'max'    => 4096,//width
    'big'    => 1200,//width
    'medium' => 768,
    'small'  => 480,
    'mini'  => 120,
    'url'               => 'https://cdn.dxmb.vn/',


];
