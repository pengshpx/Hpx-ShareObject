<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 2016-05-23

 * Time: 15:43

 */



namespace Common\Model;





use Think\Exception;
use Think\Log;


class OrderModel extends CommonModel

{

    public $payWays = array(

        'wxpay' => '微信支付',

        'balance' => '余额支付',

        'unionpay' => '银联支付'

    );



    public $orderStatus = array(

        0=>'已关闭',

        1=>'未服务',

        2=>'已完成',

        3=>'已失效'

    );



    public function notifySuccess($data)

    {

        $order = $this->find($data['out_trade_no']);

        if(!$order){

            \Think\Log::record("未找到订单的记录","ERR");

            return false;

        }

        $couponModel = new CouponModel();

        $couponModel->useCoupon($order['order_id']);

        $res =  $this->where("order_id=%d", $order['order_id'])->save(array('deleted'=>0,"is_pay"=>1, "pay_success_time" => date("Y-m-d H:i:s",strtotime($data['time_end'])), "notify_pay_price" => $data['total_fee']));

        if($res){

            $openid = $this->getOpenIdByOrder($order);

            $this->sendSuccessTemplateMsg($openid,$order['order_id']);

        }

        return $res;

    }



    public function startService($orderId){

        $order = $this->find($orderId);

        if($order['status']!=1 || $order['service_lock']!=0){

            E("订单的状态错误，无法开始服务");

        }

        return $this->where("order_id=$orderId")->save(array('service_lock'=>1,'service_start_time'=>date("Y-m-d H:i:s")));

    }



    public function endService($orderId,$barberId){

        $order = $this->find($orderId);

        if($order['status']!=1 || $order['service_lock']!=1){

            E("订单的状态错误，无法完成服务");

        }

        //理发师增加服务单数

        if(!M('Barber')->where("id=%d",$barberId)->setInc("service_num")){

            E("更新理发师服务单数失败");

        }

        $data = array('service_lock'=>2,'service_end_time'=>date("Y-m-d H:i:s"),'status'=>2);

        $res = $this->where("order_id=$orderId")->save($data);

        if($res){

            D("Coupon")->invokeByOrder($orderId);

            $openid = $this->getOpenIdByOrder($order);

            $this->sendEndServiceTemplateMsg($openid,$orderId);

        }

        return $res;

    }



    public function deleteOrder($orderId,$memberId){
        Log::write('当前时间:'.date('Y-m-d H:i',NOW_TIME)." 删除订单:$orderId\n");
        return $this

            ->where("order_id=%d and member_id=%d and is_pay=0 and status=1 and service_lock=0 and UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(create_time)<%d",$orderId,$memberId,ORDER_EXPIRE_TIME)

            ->setField("deleted",1);

}



    /**

     * 发送预约成功模版消息

     * @param $openid

     * @param $orderId

     * @return bool

     * @throws Exception

     */

    public function sendSuccessTemplateMsg($openid, $orderId){

        $templateId = "NsfC9x_IFGSkOpM0LaT3-VPJeESkGYMwUxFwsYBrg3I";

        $order = M("ViewOrder")->where("order_id={$orderId}")->find();

        if(!$order){

            return false;

        }

        $wx = new WeChatModel();

        try{

            $data = array(

                "first"=>array("value"=>"您好！您已成功预约剪刀侠男士理发。","color"=>"#173177"),

                "keyword1"=>array("value"=>$order['nickname'],"color"=>"#173177"),

                "keyword2"=>array("value"=>$order['service_time'],"color"=>"#173177"),

                "keyword3"=>array("value"=>$order['store_name'].':'.$order['address'],"color"=>"#173177"),

                "keyword4"=>array("value"=>$order['service_name'].'/'.$order['total_price'].'元',"color"=>"#173177"),

                "keyword5"=>array("value"=>$order['barber_name'],"color"=>"#173177"),

                "remark"=>array("value"=>"温馨提示：离预约开始时间12小时内，不支持退款。请按预约时间前往，若迟到会影响您的理发体验；若迟到30分钟以上，为不影响下一位客人的理发体验，理发师有权拒绝为您服务，感谢您的理解。","color"=>"#173177"),

            );

            $wx->templateMsg($openid,$templateId,URL_ROOT.U('Order/detail',array('orderId'=>$order['order_id'])),$data);

        }catch (Exception $e){

            \Think\Log::record($e->getMessage(),"ERR");

        }



    }



    /**

     * 取消预约成功模版消息

     * @param $openid

     * @param $orderId

     * @return bool

     * @throws Exception

     */

    public function sendCancelTemplateMsg($openid, $orderId,$refund=-1){

        $templateId = "TjjWrAEMBoSf-hx0n6cxCGGybfUGTJjQiT-CxsTJQ4M";

        $order = M("ViewOrder")->where("order_id={$orderId}")->find();

        if(!$order){

            return false;

        }

        if ($order['pay_code'] == 'wxpay') {
            if($refund==0) {
                $tips = '服务取消成功，12小时内取消订单不退款。';
            }else{
                $tips = '服务取消成功，退款申请已提交，工作人员会尽快与您联系办理退款。';
            }

        }

        if ($order['pay_code'] == 'moneypay') {
            if($refund==0){
                $tips = '服务取消成功，12小时内取消订单不退款。';
            }else{
                $tips = '服务取消成功，订单金额已经通过充值的方式退回到您的余额中。';
            }


        }

        $wx = new WeChatModel();

        try{

            $data = array(

                "first"=>array("value"=>$tips,"color"=>"#173177"),

                "keyword1"=>array("value"=>$order['service_name'].'['.$order['store_name'].']',"color"=>"#173177"),

                "keyword2"=>array("value"=>$order['service_time'],"color"=>"#173177"),

                "remark"=>array("value"=>"如有任何疑问，请联系客服，客服电话028-89895818。","color"=>"#173177"),

            );

            $wx->templateMsg($openid,$templateId,URL_ROOT.U('Order/detail',array('orderId'=>$order['order_id'])),$data);

        }catch (Exception $e){

            \Think\Log::record($e->getMessage(),"ERR");

        }



    }



    /**

     * 发送完成服务的模版消息

     * @param $openid

     * @param $orderId

     * @return bool

     */

    public function sendEndServiceTemplateMsg($openid, $orderId){

        $templateId = "B9g5XVMu0JzPVsRvpqccdNWaWfXtfthh5CT-KmNo5As";

        $order = M("ViewOrder")->where("order_id={$orderId}")->find();

        if(!$order){

            return false;

        }

        //$tel = getOptions("mall_setting");

        //$tel = $tel['callCenters'];

        $wx = new WeChatModel();

        try{

            $data = array(

                "first"=>array("value"=>"您好，本次服务已完成，可点击订单详情评价理发师。","color"=>"#173177"),

                "keyword1"=>array("value"=>$order['store_name'].':'.$order['address'],"color"=>"#173177"),

                "keyword2"=>array("value"=>$order['service_time'],"color"=>"#173177"),

                "remark"=>array("value"=>"如有任何疑问，可联系客服，客服电话：028-89895818","color"=>"#173177"),

            );

            $url = URL_ROOT.'/Order/detail/orderId/'.$orderId;
            //U("Order/detail",array("orderId"=>$orderId));
            $wx->templateMsg($openid,$templateId,$url,$data);

        }catch (Exception $e){

            \Think\Log::record($e->getMessage(),"ERR");

        }

    }





    private function getOpenIdByOrder($order){

        return M("Member")->where("member_id=%d",$order['member_id'])->getField("openid");

    }

    

}