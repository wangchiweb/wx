<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/phpinfo',function(){
    phpinfo();
});

// Route::get('/wechat','WeachatController@wechat'); //接口测试
Route::post('/wechat','WeachatController@event'); //接受事件推送
Route::get('/getaccesstoken','WeachatController@getaccesstoken'); //获取access_token


Route::get('/test1','TestController@test1'); //测试1
Route::get('/test2','TestController@test2'); //测试2
Route::get('/test3','TestController@test3'); //测试3
Route::post('/test4','TestController@test4'); //测试4
Route::get('/guzzleget','TestController@guzzleget'); //使用guzzle发起get请求

