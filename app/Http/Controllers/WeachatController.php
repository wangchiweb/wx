<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

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
            file_put_contents('wx_event.log',$xml_str);

            //把xml文本转换为PHP的对象或数组
            $data=simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);
            // dd($data);

            if($data['MsgType']=="event"){
                if($data['Event']=="subscribe"){
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
        $ToUserName=$data['FromUserName'];
        $FromUserName=$data['ToUserName'];
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
        echo 'access_token:'.$token;
    }
}
