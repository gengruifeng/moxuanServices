<?php

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});


$router->post('uploadimg', [
    'as' => 'uploadimg', 'uses' => 'DayRecord@uploadImg'
]);

$router->post('publish', [
    'as' => 'publish', 'uses' => 'DayRecord@publish'
]);

$router->get('getdayrecordlist', [
    'as' => 'getdayrecordlist', 'uses' => 'DayRecord@getDayRecordList'
]);