<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;

class WeachatController extends Controller{
    /**微信接口配置 */
    public function checkSignature(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    /**微信接口测试 */
    public function wechat(){
        $token = request()->get('echostr','');
        if(!empty($token) && $this->checkSignature()){
            echo $token;
        }
    }
    /**处理推送事件 */
    public function event(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){   //验证通过
            //接受数据
            $xml_str=file_get_contents("php://input");

            //记录日志
            file_put_contents('wx_event.log',$xml_str,FILE_APPEND);

            //把xml文本转换为PHP的对象
            $data=simplexml_load_string($xml_str);
            // dd($data);

            if($data->MsgType=="event"){
                if($data->Event=="subscribe"){
                    echo $this->news($data);
                    die;
                }
            }
            
        }else{
            echo "";
        }
    }
    /**消息 */
    public function news($data){
        $ToUserName=$data->FromUserName;
        $FromUserName=$data->ToUserName;
        $content="欢迎关注";
        $xml="<xml>
                <ToUserName><![CDATA[".$ToUserName."]]></ToUserName>
                <FromUserName><![CDATA[".$FromUserName."]]></FromUserName>
                <CreateTime>time()</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[".$content."]]></Content>
                <MsgId>%s</MsgId>
            </xml>";     
        return $xml;
    }
    /**获取access_token */
    public function getaccesstoken(){
        $key='wx:access_token';
        //检查Redis中是否有access_token
        $token=Redis::get($key);
        if($token){
            echo '有缓存'.'<br>';
        }else{
            echo '无缓存'.'<br>';
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
            $response=file_get_contents($url);
            // dd($response);
            $data=json_decode($response,true);
            // dd($token);
            $token=$data['access_token'];
            // echo $token;
            //保存到Redis中，时间为3600s
            Redis::set($key,$token);
            Redis::expire($key,3600);
        }
        return $token;
    }
    /**创建自定义菜单 */
    public function createmenu(){
        $access_token=$this->getaccesstoken();
        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $menu = [
            'button'    => [
                [
                    'type'  => 'click',
                    'name'  => '微信',
                    'key'   => 'wechat'
                ],
                [
                    'type'  => 'view',
                    'name'  => 'BAIDU',
                    'url'   => 'https://www.baidu.com'
                ],

            ]
        ];
        //使用guzzle发起POST请求
        $client=new Client();   //实例化 客户端
        $response=$client->request('POST',$url,[
            'verify'=>false,
            'body'=>json_encode($menu)
        ]);   //发起请求并接收响应
        dd($response);
        $json_data=$response->getBody();   //服务器的响应数据
        echo $json_data;die;
        //判断接口返回
        $info=json_decode($json_data,true);
        if($info['errcode']>0){   //判断错误码

        }

    }
}
