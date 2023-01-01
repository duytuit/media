<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router->get('/uploads/mini/{src:.*}', [
    'as' => 'image.mini', 'uses' => '\App\StechMedia\AppMedia@to_image_mini'
]);
$router->get('/uploads/small/{src:.*}', [
    'as' => 'image.small', 'uses' => '\App\StechMedia\AppMedia@to_image_small'
]);
$router->get('/uploads/medium/{src:.*}', [
    'as' => 'image.medium', 'uses' => '\App\StechMedia\AppMedia@to_image_medium'
]);
$router->get('/uploads/big/{src:.*}', [
    'as' => 'image.big', 'uses' => '\App\StechMedia\AppMedia@to_image_big'
]);

$router->get('/uploads/thumb/{width}x{height}/{src:.*}', [
    'as' => 'image.thumb', 'uses' => '\App\StechMedia\AppMedia@to_image_thumb'
]);

$router->get('/uploads/webp/thumb/{width}x{height}/{src:.*}', [
    'as' => 'image.webp.thumb', 'uses' => '\App\StechMedia\AppMedia@to_image_thumb_webp'
]);

$router->get('/uploads/crop/{width}x{height}/{src:.*}', [
    'as' => 'image.crop', 'uses' => '\App\StechMedia\AppMedia@to_image_crop'
]);

$router->get('/resize/image/{src:.*}', [
    'as' => 'image.crop', 'uses' => '\App\StechMedia\AppMedia@resize_image'
]);




$router->post('/do-upload', '\App\StechMedia\AppMedia@do_upload');

$router->post('/remove', '\App\StechMedia\AppMedia@move_file');


$router->get('/', function () use ($router) {
    return "!_@";
});
$router->get('/__i', function () use ($router) {
    return phpinfo();
});

$router->get('/test', '\App\StechMedia\AppMedia@test_function');
