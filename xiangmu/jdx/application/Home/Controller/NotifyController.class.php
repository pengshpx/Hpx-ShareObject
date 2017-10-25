<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-07-14
 * Time: 10:29
 */

namespace Home\Controller;

import("Common.payment.wxpay.lib.WxPay#Data","",".php");
use Think\Controller;
use Common\Model\OrderModel;
use Common\Model\RechargeLogModel;
class NotifyController extends Controller
{
    private $payLogPath ="";
    public function __construct()
    {
        parent::__construct();
        $this->payLogPath =  SITE_PATH."data/paylog/".date('y_m_d').'.log';
    }

    public function wxpay(){
        $model = new OrderModel();
        $this->notify($model);
    }
    public function recharge_wxpay(){
        $model = new RechargeLogModel();
        $this->notify($model);
    }

    private function notify($model){
        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $reply = new \WxPayNotifyReply();
        //如果返回成功则验证签名
        try {
            $result = \WxPayResults::Init($xml);
            if($result['result_code']=='SUCCESS'){
                $res = $model->notifySuccess($result);
                if($res){
                    \Think\Log::write($xml."\n微信支付回调成功，更改状态成功\n",'INFO',false,$this->payLogPath);
                }else{
                    \Think\Log::write($xml."\n微信支付回调成功，更改状态失败\n",'ERR',false,$this->payLogPath);
                }
            }
            $reply->SetReturn_code("SUCCESS");
            $reply->SetReturn_msg("OK");
            exit($reply->ToXml());
        } catch (\WxPayException $e){
            $msg = $e->errorMessage();
            \Think\Log::write($msg,'ERR',false,$this->payLogPath);
            $reply->SetReturn_code("FAIL");
            $reply->SetReturn_msg($msg);
            exit($reply->ToXml());
        }
    }
}