<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Model\WxUser;
use App\Model\WxMation;

class WeachatController extends Controller{
    protected $xml_obj;
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
        //验签
        if($this->checkSignature()==false){   //验签不通过
            die;
        }
        //接受数据
        $xml_str=file_get_contents("php://input");

        //记录日志
        file_put_contents('wx_event.log',$xml_str,FILE_APPEND);

        //把xml文本转换为PHP的对象
        $data=simplexml_load_string($xml_str);
        // dd($data);
        $this->xml_obj=$data;

        $msg_type=$data->MsgType;   //推送事件的消息类型
        switch($msg_type){
            case 'event' :
                $EventKey=$this->xml_obj->EventKey;
                if($EventKey=='weather'){
                    echo $this->subscribe();
                    die;
                }
                if($data->Event=='subscribe'){   // subscribe 扫码关注
                    echo $this->subscribe();
                    die;  
                }elseif($data->Event=='unsubscribe'){   // unsubscribe 取消关注
                    echo "";
                    die;
                }
                break;

            case 'text' :           //处理文本信息
                $result=$this->text();
                return $result;
                break;

            case 'image' :          // 处理图片信息
                $this->image();
                break;

            case 'voice' :          // 语音
                $this->voice();
                break;

            case 'video' :          // 视频
                $this->video();
                break;

            default:
                echo 'default';
        }  
    }
    /**处理文本 */
    public function text(){
        echo '<pre>';print_r($this->xml_obj);echo '</pre>';
        $data=[
            "FromUserName"=>$this->xml_obj->FromUserName,
            "CreateTime"=>$this->xml_obj->CreateTime,
            "MsgType"=>$this->xml_obj->MsgType,
            "Content"=>$this->xml_obj->Content,
        ];
        //入库
        WxMation::insert($data);
    }
    /**处理图片 */
    public function image(){
        $access_token=$this->getaccesstoken();
        $media_id=$this->xml_obj->MediaId;
        $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$access_token.'&media_id='.$media_id;
        $img=file_get_contents($url);
        $media_path='image/wx.jpg';
        $res=file_put_contents($media_path,$img);
        if($res){
           // TODO 成功
        }else{
            // TODO 失败
        }

        $data=[
            "MediaId"=>$media_id,
            "FromUserName"=>$this->xml_obj->FromUserName,
            "CreateTime"=>$this->xml_obj->CreateTime,
            "MsgType"=>$this->xml_obj->MsgType,
            "PicUrl" =>$this->xml_obj->PicUrl,
            'media_path'=>$this->media_path
        ];
        //入库
        WxMation::insert($data);
    }
    /**处理语音 */
    public function voice(){
        $access_token=$this->getaccesstoken();
        $media_id=$this->xml_obj->MediaId;
        $url='https://api.weixin.qq.com/cgi-bin/media/get/jssdk?access_token='.$access_token.'&media_id='.$media_id;
        $img = file_get_contents($url);
        $media_path='voice/wx.mp3';
        $res = file_put_contents($media_path,$img);
        if($res){
            // TODO 成功
        }else{
            // TODO 失败
        }
        $data=[
            "MediaId"=>$media_id,
            "FromUserName"=>$this->xml_obj->FromUserName,
            "CreateTime"=>$this->xml_obj->CreateTime,
            "MsgType"=>$this->xml_obj->MsgType,
            "Format" => $this->xml_obj->Format,
            "ThumbMediaId" =>$this->xml_obj->ThumbMediaId,
            'media_path'=>$this->media_path
        ];
        //入库
        WxMation::insert($data);
    }
    /**处理视频 */
    public function video(){
        $access_token=$this->getaccesstoken();
        $media_id=$this->xml_obj->MediaId;
        $url='https://api.weixin.qq.com/cgi-bin/media/get/jssdk?access_token='.$access_token.'&media_id='.$media_id;
        $img = file_get_contents($url);
        $media_path='video/wx.mp4';
        $res = file_put_contents($media_path,$img);
        if($res){
            // TODO 成功
        }else{
            // TODO 失败
        }
        $data=[
            "MediaId"=>$media_id,
            "FromUserName"=>$this->xml_obj->FromUserName,
            "CreateTime"=>$this->xml_obj->CreateTime,
            "MsgType"=>$this->xml_obj->MsgType,
            "ThumbMediaId" =>$this->xml_obj->ThumbMediaId,
            'media_path'=>$this->media_path
        ];
        //入库
        WxMation::insert($data);
    }
    /**回复扫码关注 */
    public function subscribe(){
        $ToUserName=$this->xml_obj->FromUserName;   //openid
        $FromUserName=$this->xml_obj->ToUserName;    
        $CreateTime=time();
        $MsgType="text";
        
        //查询此用户是否存在        
        $res=WxUser::where(['openid'=>$ToUserName])->first();         
        if($res){   //如果存在 
            $content="欢迎回来";        
        }else{   //如果不存在，则添加此用户入库 
            //获取用户信息
            $userinfo=$this->getwxuser();    
            $data=[
                'openid'=>$userinfo['openid'],
                'nickname'=>$userinfo['nickname'],
                'sex'=>$userinfo['sex'],
                'city'=>$userinfo['city'],
                'province'=>$userinfo['province'],
                'country'=>$userinfo['country'],
                'language'=>$userinfo['language'],
                'headimgurl'=>$userinfo['headimgurl'],
                'subscribe_time'=>$userinfo['subscribe_time']
            ];
            WxUser::insert($data);
            $content="欢迎关注";
        }

        //点击天气，回复此时的天气信息
        $content=$this->weather();

        $xml="<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[%s]]></MsgType>
                <Content><![CDATA[%s]]></Content>
            </xml>";     
        $info=sprintf($xml,$ToUserName,$FromUserName,$CreateTime,$MsgType,$content);
        return $info;
    }
    /**获取access_token */
    public function getaccesstoken(){
        $key='wx:access_token';
        //检查Redis中是否有access_token
        $token=Redis::get($key);
        if($token){
            // echo '有缓存'.'<br>';
        }else{
            // echo '无缓存'.'<br>';
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
        //获取access_token
        $access_token=$this->getaccesstoken();
        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $menu = [
            'button'    => [
                [
                    'type'  => 'view',
                    'name'  => '商城',
                    'url'   => 'http://2004wch.comcto.com/'
                ],
                [
                    'name'  => '二级菜单',
                    "sub_button"    => [
                        [
                            "type"  =>  "click",
                            "name"  =>  "签到",
                            "url"   =>  "checking"
                        ],
                        [
                            'type'  => 'pic_photo_or_album',
                            'name'  => '图片',
                            'key'   => 'uploadimg'
                        ],
                        [
                            'type'  => 'click',
                            'name'  => '天气',
                            'key'   => 'weather'
                        ]
                    ]
                ],
                [
                    'type'  => 'view',
                    'name'  => '百度',
                    'url'   => 'https://www.baidu.com'
                ]
            ]
        ];
        
        //使用guzzle发起POST请求
        $client=new Client();   //实例化 客户端
        $response=$client->request('POST',$url,[
            'verify'=>false,      
            'body'=>json_encode($menu,JSON_UNESCAPED_UNICODE)
        ]);   //发起请求并接收响应
        
        $json_data=$response->getBody();   //服务器的响应数据
        //判断接口返回
        $info=json_decode($json_data,true);
        if($info['errcode']==0){   //判断错误码
            echo '<pre>';print_r($info);echo '</pre>';
        }else{
            echo date("Y-m-d H:i:s").  "创建菜单成功";
        }

    }
    /**获取用户信息 */
    public function getwxuser(){
        $access_token=$this->getaccesstoken();   //获取access_token
        $openid=$this->xml_obj->FromUserName;   //获取openid
        $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        
        //使用guzzle发起POST请求(请求接口)
        $client=new Client();   //实例化 客户端
        $response=$client->request('GET',$url,[
            'verify'=>false, 
        ]);   //发起请求并接收响应
        
        $json_data=$response->getBody();   //服务器的响应数据
        $info=json_decode($json_data,true);
        return $info;
    }
    /**获取天气信息 */
    public function weather(){
        $url = "http://api.k780.com:88/?app=weather.future&weaid=beijing&&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=json";
        $weather = file_get_contents($url);
        $weather = json_decode($weather,true);
        if($weather["success"]){
            $content = "";
            foreach ($weather["result"] as $v) {
                $content .= "\n"."地区:" . $v['citynm'] .","."日期:" . $v['days'] . $v['week'] .","."温度:" . $v['temperature'] .","."风速:" . $v['winp'] .","."天气:" . $v['weather'];
            }
        }
        return $content;
    }
}
