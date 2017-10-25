<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-06-22
 * Time: 17:58
 */

namespace Common\Model;

use Com\WechatAuth;
use Think\Exception;

class WeChatModel
{
    public function getWeChatAuthWithAccessToken()
    {
        $accessTokenArr = F("access_token_arr");
        if (empty($accessTokenArr) || $accessTokenArr['expires'] <= time()) {
            $weChatOptions = getOptions('weixin');
            $wx = new WechatAuth($weChatOptions['appId'], $weChatOptions['appSecret']);
            $accessToken = $wx->getAccessToken();
            $accessToken['expires'] = time() + $accessToken['expires_in'];
            $accessToken['appId'] = $weChatOptions['appId'];
            $accessToken['appSecret'] = $weChatOptions['appSecret'];
            F("access_token_arr", $accessToken);
            return $wx;
        } else {
            $wx = new WechatAuth($accessTokenArr['appId'], $accessTokenArr['appSecret'], $accessTokenArr['access_token']);
            return $wx;
        }
    }

    public function templateMsg($openid,$templateId,$url,$data){

        $wx = $this->getWeChatAuthWithAccessToken();
        $res = $wx->sendTemplate($openid,$templateId,$url,$data);
        if($res['errcode']>0){
            E($res['errmsg']);
            return false;
        }else{
            return $res['msgid'];
        }
    }

    protected function getJsApiTicket()
    {
        $ticketArr = F('jsapi_ticket');

        if (empty($ticket) || $ticketArr['expires'] <= time()) {
            $weChat = $this->getWeChatAuthWithAccessToken();
            try {
                $ticketArr = $weChat->jsTicket();
            } catch (Exception $e) {
                F('access_token_arr', null);
                F('jsapi_ticket', null);
                return false;
            }
            $ticketArr['expires'] = time() + $ticketArr['expires_in'];
            F('jsapi_ticket', $ticketArr);
        }

        return $ticketArr['ticket'];
    }

    public function getSignPackage()
    {


        $jsapiTicket = $this->getJsApiTicket();
        if (!$jsapiTicket) {
            return false;
        }
        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);
        $weChatOptions = getOptions('weixin');
        $signPackage = array(
            "appId" => $weChatOptions['appId'],
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}