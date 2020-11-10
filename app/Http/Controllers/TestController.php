<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\User;
use GuzzleHttp\Client;

class TestController extends Controller{
    public function test1(){
        $res=User::get()->toArray();
        dd($res);
    }
    public function test2(){
        echo phpinfo();
    }
    public function test3(){
        echo '<pre>';
        print_r($_GET); 
        echo '<pre>';
    }
    public function test4(){
        echo '<pre>';
        print_r($_POST); 
        echo '<pre>';
    }
    // /**使用guzzle发起get请求 */
    // public function guzzleget(){
    //     $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');

    //     //使用guzzle发起get请求
    //     $client = new Client();         //实例化 客户端
    //     $response = $client->request('GET',$url,['verify'=>false]);       //发起请求并接收响应

    //     $json_str = $response->getBody();       //服务器的响应数据
    //     echo $json_str;

    // }
}
