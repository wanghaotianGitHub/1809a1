<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ComController extends Controller
{
    //获取accessToken
    public function accessToken(){
        $key = 'wx_access_token';
        $accessToken = Redis::get($key);
        if($accessToken){
            return $accessToken;
        }else{
            //https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
            $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_SECRET').'';
            $response = file_get_contents($url);
            $arr = json_decode($response,true);
            $access = $arr['access_token'];
//            print_r($arr);die;
            Redis::set($key,$access);
            Redis::expire($key,3600);
            $accessToken = $arr['access_token'];
            return $accessToken;
        }
    }
    public function shop(){
//        echo __METHOD__;die;
        $content = file_get_contents("php://input");
        $time = date('Y-m-d H:i:s');
        $str = $time . $content . "\n";
        file_put_contents("logs/wx_event.log",$str,FILE_APPEND);

        $objxml = simplexml_load_string($content);
        $ToUserName = $objxml->ToUserName;
        $FromUserName = $objxml->FromUserName;
        $CreateTime = $objxml->CreateTime;
        $MsgType = $objxml->MsgType;
        $Event = $objxml->Event;
        $EventKey = $objxml->EventKey;
        $Content = $objxml->Content;
        $MediaId = $objxml->MediaId;

        $openid = $FromUserName;
        $accessToken = $this->accessToken();
        $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=$accessToken&openid=$openid&lang=zh_CN";
        $response = file_get_contents($url);
        $arr = json_decode($response,true);
        $name = $arr['nickname'];
        $openid = $arr['openid'];
        $date = DB::table('user')->where('openid',$openid)->count();
        if($MsgType=='image'){
            $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$MediaId";
            $response = file_get_contents($url);
            $file_name = rtrim(substr("QAZWSXEDCRFVTGBYHNUJMIKMOLqwertyuiopasdfghjklzxcvbnmP", -10), '"').".jpg";//取文件名后10位
            $img_name =  substr(md5(time() . mt_rand()), 10, 8) . '_' . $file_name;//最后的文件名;
            file_put_contents("/tmp/$img_name",$response,FILE_APPEND);
            $data = [
                'openid'=>$openid,
                'image_url'=>"/tmp/".$img_name
            ];
            $array = DB::table('sucai')->insert($data);
        }else  if($Content==""){
                $good = DB::table('shop_goods')->where('goods_up',1)->orderBy('create_time','desc')->first();
                $good_name = $good->goods_name;
                $title = "";
                $picurl = "http://1809wanghaotian.comcto.com/goodsimg/$good->goods_img";
                $url = "http://1809wanghaotian.comcto.com/goodDetail";
                $str = "<xml>
                          <ToUserName><![CDATA[$FromUserName]]></ToUserName>
                          <FromUserName><![CDATA[$ToUserName]]></FromUserName>
                          <CreateTime>$CreateTime</CreateTime>
                          <MsgType><![CDATA[news]]></MsgType>
                          <ArticleCount>1</ArticleCount>
                          <Articles>
                            <item>
                              <Title><![CDATA[$title]]></Title>
                              <Description><![CDATA[$good_name]]></Description>
                              <PicUrl><![CDATA[$picurl]]></PicUrl>
                              <Url><![CDATA[$url]]></Url>
                            </item>
                          </Articles>
                        </xml>";
                echo $str;
            }else{
                $data = [
                    'openid'=>$openid,
                    'content'=>$Content
                ];
                $array = DB::table('sucai')->insert($data);
            }
        }else if($MsgType=='voice'){
            $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$MediaId";
            $response = file_get_contents($url);
            $file_name = rtrim(substr("QAZWSXEDCRFVTGBYHNUJMIKMOLqwertyuiopasdfghjklzxcvbnmP", -10), '"').".mp3";//取文件名后10位
            $voice_name =  substr(md5(time() . mt_rand()), 10, 8) . '_' . $file_name;//最后的文件名;
            file_put_contents("/tmp/$voice_name",$response,FILE_APPEND);
            $data = [
                'openid'=>$openid,
                'voice_url'=>"/tmp/".$voice_name
            ];
            $array = DB::table('sucai')->insert($data);
        }

    }
}
