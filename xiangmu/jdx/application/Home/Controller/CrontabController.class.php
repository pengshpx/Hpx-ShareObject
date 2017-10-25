<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 2016-07-18

 * Time: 11:07

 */



namespace Home\Controller;





use Think\Controller;
use Think\Log;


class CrontabController extends Controller

{

    public function refreshOrder(){
        $log='';
        $content=M()->query("select order_id,member_id,service_id,service_name,service_time,service_lock,status,is_pay,deleted,pay_success_time from ehecd_order  where is_pay=0 and deleted=0 and status=1 and UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(create_time)>%d",ORDER_EXPIRE_TIME);
        foreach ($content as $k=>$item) {
            $log.="开始执行: ===================================================================当前时间:".date('Y-m-d H:i',NOW_TIME);
            $log.="\n";
            $log.="订单号:".$item['order_id']."服务时间:".$item['service_time']."服务状态:".$item['service_lock'].'status='.$item['status'].'is_pay='.$item['is_pay'].'deleted='.$item['deleted'].'微信回调时间'.$item['pay_success_time']."\n";

        }

        M()->execute("UPDATE ehecd_order SET deleted = 1 where is_pay=0 and status=1 and deleted=0 and  UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(create_time)>%d",ORDER_EXPIRE_TIME);
        $log.='执行完毕:'.date('Y-m-d H:i',NOW_TIME."\n");
        Log::write($log);
    }



    public function expOrder(){

        M()->execute("UPDATE ehecd_order SET status=3,close_time=now() where is_pay=1 and status=1 and to_days(now())-to_days(service_time) >= 1");

    }

}