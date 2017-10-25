<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 2016-07-14

 * Time: 10:27

 */



namespace Home\Controller;



use Common\Model\CouponModel;

use Common\Model\MemberModel;

use Common\Model\OrderModel;

use Common\Model\RechargeLogModel;

use Think\Exception;



import("Common.payment.wxpay.WxPay#JsApiPay", "", ".php");



class PayController extends CommonController

{





    private $payWays = array('wxpay'=>'微信支付','moneypay'=>'超剪卡支付');



    public function pay()

    {

        $orderId = I('oid', '');//订单ID

        $couponIds = I('cids', '');//优惠券ID

        $payWay = I('way');//支付方式

        //检查订单是否合法

        if (empty($orderId)) {

            $this->error('支付订单ID错误');

        }

        $orderModel = new OrderModel();

        $order = $orderModel->find($orderId);

        if ($order) {

            if ($order['deleted'] == 1) {

                $this->error('订单已失效,请重新下单',U("Index/index"));

            } elseif ($order['is_pay'] == 1) {

                $this->redirect('Member/index');

            }

        } else {

            $this->error('订单的订单不存在');

        }

        $money = $order['total_price'];

        $orderModel->startTrans();

        $couponMoney = 0;

        try {

            //处理优惠码

            if (!empty($couponIds)) {

                $couponModel = M("Coupon");

                $coupons = $couponModel->where("id in ($couponIds) and is_used=0 and is_invoke=1")->select();

                if (!$coupons) {

                    E('选择的优惠码不存在');

                }

                //若存在优惠码，更新优惠码使用的订单号

                if (!$couponModel->where("id in ($couponIds)")->save(array('order_id' => $orderId))) {

                    E('更新优惠码关联订单失败');

                }

                //计算优惠价格

                foreach ($coupons as $coupon) {

                    $couponMoney += $coupon['money'];

                }

            }

            $order['coupon_money'] = $couponMoney;

            switch ($payWay) {

                case 'wxpay':

                    $order['pay_price'] = $money-$couponMoney;

                    $this->assign('jsParameters',$this->wxPay($order));

                    break;

                case 'moneypay':

                    $order['pay_price'] =  sprintf("%.2f", $money*0.85-$couponMoney);

                    $this->moneyPay($order);

                    $orderModel->sendSuccessTemplateMsg($this->member['openid'],$orderId);

                    break;

                default:

                    E('错误的支付方式');

                    break;

            }

            //更新订单优惠价格和实际支付价格

            if ($orderModel->where(array('order_id' => $orderId))->save(array('pay_price' => $order['pay_price'], 'coupon_money' => $order['coupon_money'],'pay_code'=>$payWay,'pay_name'=>$this->payWays[$payWay]))===false) {

                E('更新订单信息失败');

            }

            $orderModel->commit();

            $this->assign('way',$payWay);

            $this->assign('orderId',$orderId);

            $barber = M("ViewOrder")->field("barber_store_id,barber_id")->where("order_id=%d",$orderId)->find();

            $this->assign('barberId',$barber['barber_id']);

            $this->assign('StoreBarberId',$barber['barber_store_id']);

            $this->display();

        } catch (Exception $e) {

            $orderModel->rollback();

            $this->assign('payResult',0);

            \Think\Log::record($e->getMessage(),"ERR");

            $this->error($e->getMessage());

        }

    }



    protected function wxPay(&$order)

    {

        if($order['pay_price'] <= 0){

            E('在线支付金额不能为0');

        }

        $openId = $this->member['openid'];

        $tools = new \JsApiPay();

        $input = new \WxPayUnifiedOrder();

        $input->SetBody("剪刀侠预约服务支付：".sprintf("%.2f",$order['pay_price'])."元");

        $input->SetOut_trade_no($order['order_id']);

        $input->SetTotal_fee(intval(strval($order['pay_price']*100)));

        $input->SetTime_start(date("YmdHis"));

        $input->SetTime_expire(date("YmdHis",time()+ORDER_EXPIRE_TIME-2));

        $input->SetNotify_url("http://" . $_SERVER['HTTP_HOST'] . U('Home/Notify/wxpay'));

        $input->SetTrade_type("JSAPI");

        $input->SetOpenid($openId);


        $result = \WxPayApi::unifiedOrder($input);

        if ($result['return_code'] == 'FAIL') {

            E($result['return_msg']);

        }

        if($result['result_code'] == 'FAIL'){

            E($result['err_code_des']);

        }

        $jsApiParameters = $tools->GetJsApiParameters($result);

        return $jsApiParameters;

    }



    protected function moneyPay(&$order){

        if($order['pay_price'] <= 0){

            $order['pay_price'] = 0;

        }

        $this->refresh_user_session();

        if($this->member['money']<$order['pay_price']){

            E("超剪卡余额不足");

        }

        $memberModel = new MemberModel();

        if(!$memberModel->changeMoney(1,$order['pay_price'],'支付订单'.$order['order_id'],$this->member['member_id'])){

            E("超剪卡扣款失败");

        }

        //更改订单状态

        $orderModel = new OrderModel();

        if(!$orderModel->where("order_id=%d",$order['order_id'])->save(array('is_pay'=>1,'pay_success_time'=>date("Y-m-d H:i:s")))){

            E("更改订单状态出错");

        }

        //处理优惠码为已使用

        $couponModel = new CouponModel();

        $couponModel->useCoupon($order['order_id']);

        $this->assign('payResult',1);

    }



    public function recharge_wxpay($id = 0)

    {

        if ($id <= 0) {

            $this->error("请选择充值超剪卡类型");

        }

        $card = M("MemberCard")->find($id);

        if (!$card) {

            $this->error("未找到充值的超剪卡类型");

        }

        $money = $card['price'] + $card['donate'];

        try {

            $model = new RechargeLogModel();

            $outTradeNo = $model->generateOutTradeNo($this->member['member_id']);

            $res = $model->addLog($this->member['member_id'], $money, $outTradeNo);

            if (!$res) {

                $this->error("生成充值记录失败");

            }

            $openId = $this->member['openid'];

            $tools = new \JsApiPay();

            $input = new \WxPayUnifiedOrder();

            $input->SetBody("充值金额：{$card['price']}元,赠送{$card['donate']}元");

            $input->SetOut_trade_no($outTradeNo);

            $input->SetTotal_fee($card['price'] * 100);
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis",time()+ORDER_EXPIRE_TIME-2));

            $input->SetNotify_url("http://" . $_SERVER['HTTP_HOST'] . U('Home/Notify/recharge_wxpay'));

            $input->SetTrade_type("JSAPI");

            $input->SetOpenid($openId);


            $order = \WxPayApi::unifiedOrder($input);


            if ($order['return_code'] == 'FAIL') {

                $this->error($order['return_msg']);

            }

            $jsApiParameters = $tools->GetJsApiParameters($order);

        } catch (Exception $e) {

            $this->error("提交充值失败：" . $e->getMessage());

        }

        //return $jsApiParameters;
        //var_dump(json_decode($jsApiParameters));
        //exit;
        $this->success(json_decode($jsApiParameters,true));

    }

}