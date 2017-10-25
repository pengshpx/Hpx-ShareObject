<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-06-23
 * Time: 15:39
 */

namespace Api\Controller;


use Think\Controller;
use Com\Wechat;
use Think\Exception;
use Think\Log;

class WeChatController extends Controller
{
    /**
     * 微信消息接口入口
     * 所有发送到微信的消息都会推送到该操作
     * 所以，微信公众平台后台填写的api地址则为该操作的访问地址
     */
    public function index($id = ''){
        //调试
        try{
            $weChatOptions = getOptions('weixin');
            $appid = $weChatOptions['appId']; //AppID(应用ID)
            $token = $weChatOptions['token']; //微信后台填写的TOKEN
            //echo json_encode($weChatOptions);
            //$crypt = 'q6FPCUoCQWaOiR3UUe5RfQu8A7hlJcMW4BnNyH9z2il'; //消息加密KEY（EncodingAESKey）
            /* 加载微信SDK */
            $wechat = new Wechat($token, $appid);

            /* 获取请求信息 */
            $data = $wechat->request();

            if($data && is_array($data)){
                /**
                 * 你可以在这里分析数据，决定要返回给用户什么样的信息
                 * 接受到的信息类型有10种，分别使用下面10个常量标识
                 * Wechat::MSG_TYPE_TEXT       //文本消息
                 * Wechat::MSG_TYPE_IMAGE      //图片消息
                 * Wechat::MSG_TYPE_VOICE      //音频消息
                 * Wechat::MSG_TYPE_VIDEO      //视频消息
                 * Wechat::MSG_TYPE_SHORTVIDEO //视频消息
                 * Wechat::MSG_TYPE_MUSIC      //音乐消息
                 * Wechat::MSG_TYPE_NEWS       //图文消息（推送过来的应该不存在这种类型，但是可以给用户回复该类型消息）
                 * Wechat::MSG_TYPE_LOCATION   //位置消息
                 * Wechat::MSG_TYPE_LINK       //连接消息
                 * Wechat::MSG_TYPE_EVENT      //事件消息
                 *
                 * 事件消息又分为下面五种
                 * Wechat::MSG_EVENT_SUBSCRIBE    //订阅
                 * Wechat::MSG_EVENT_UNSUBSCRIBE  //取消订阅
                 * Wechat::MSG_EVENT_SCAN         //二维码扫描
                 * Wechat::MSG_EVENT_LOCATION     //报告位置
                 * Wechat::MSG_EVENT_CLICK        //菜单点击
                 */

                //记录微信推送过来的数据
                file_put_contents('./data.json', json_encode($data));

                /* 响应当前请求(自动回复) */
                //$wechat->response($content, $type);

                /**
                 * 响应当前请求还有以下方法可以使用
                 * 具体参数格式说明请参考文档
                 *
                 * $wechat->replyText($text); //回复文本消息
                 * $wechat->replyImage($media_id); //回复图片消息
                 * $wechat->replyVoice($media_id); //回复音频消息
                 * $wechat->replyVideo($media_id, $title, $discription); //回复视频消息
                 * $wechat->replyMusic($title, $discription, $musicurl, $hqmusicurl, $thumb_media_id); //回复音乐消息
                 * $wechat->replyNews($news, $news1, $news2, $news3); //回复多条图文消息
                 * $wechat->replyNewsOnce($title, $discription, $url, $picurl); //回复单条图文消息
                 *
                 */

                //执行Demo
                $this->demo($wechat, $data);
            }
        } catch(\Exception $e){
            Log::record(json_encode($e->getMessage()),"ERR");
            exit('');
        }

    }

    /**
     * DEMO
     * @param  Object $wechat Wechat对象
     * @param  array  $data   接受到微信推送的消息
     */
    private function demo($wechat, $data){
        switch ($data['MsgType']) {
            case Wechat::MSG_TYPE_EVENT:
                /*switch ($data['Event']) {
                    case Wechat::MSG_EVENT_SUBSCRIBE:
                        $wechat->replyText('欢迎您关注麦当苗儿公众平台！回复“文本”，“图片”，“语音”，“视频”，“音乐”，“图文”，“多图文”查看相应的信息！');
                        break;

                    case Wechat::MSG_EVENT_UNSUBSCRIBE:
                        //取消关注，记录日志
                        break;

                    default:
                        $wechat->replyText("欢迎访问麦当苗儿公众平台！您的事件类型：{$data['Event']}，EventKey：{$data['EventKey']}");
                        break;
                }*/
                break;

            case Wechat::MSG_TYPE_TEXT:
                $model = M("WxKeywords");
                $key = $model->where("keyword = '%s'",$data['Content'])->find();
                Log::record(json_encode($key),"INFO");
                if($key){
                    switch($key['type']){
                        case '1':
                            $wechat->replyText($key['content']);
                            break;
                        case '2':
                            $wechat->replyNewsOnce(
                                $key['title'],
                                "",
                                $key['content'],
                                "http://".$_SERVER['HTTP_HOST'].$key['pic_url']
                            ); //回复单条图文消息
                            break;
                        default:
                            Log::record('未知的处理类型',"ERR");
                    }
                }
            default:
                Log::record('未找到对应的处理',"ERR");
                exit('');
                break;
        }
    }

}