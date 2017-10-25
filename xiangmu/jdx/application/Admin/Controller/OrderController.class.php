<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 2016-05-23

 * Time: 14:19

 */



namespace Admin\Controller;





use Common\Controller\AdminbaseController;

use Common\Model\ExpressModel;

use Common\Model\OrderModel;

use Common\Model\StoreModel;

use Think\Exception;



class OrderController extends AdminbaseController

{



    private $order_model ;

    public function __construct()

    {

        parent::__construct();

        $this->order_model = new OrderModel();

    }



    public function index(){

        $orderView = M('ViewOrder');

        if(IS_AJAX){

            $p = (int) I("p",1);

            $where = $this->getSearchWhere();

            try{

                $data['data'] = $orderView->where($where)->page($p,$this->pageNum)->order("service_time desc")->select();

                $count =  $orderView->where($where)->count();

                $data['count'] = ceil($count/$this->pageNum);

                $this->success($data);

            }catch (Exception $e){

                //$this->error('查询发生错误:'.$e->getMessage());

                $this->error('数据库错误');

            }

        }else{

            $storeModel = new StoreModel();

            $this->assign('stores',$storeModel->getList());

            $this->display();

        }

    }



    private function getSearchWhere(){

        $orderNo = I("order_no",'');

        $nickname = I("nickname",'','');

        $tel = I("tel",'');

        $payWay = I('payway','');

        $store = I('store',0);

        $barber = I('barber','');

        $start_time = I("start_time",'');

        $end_time = I("end_time",'');

        $status = I('status',100);

        $where = "is_pay = 1 and deleted=0";

        if($orderNo!=''){

            $where .= " and order_id like '%$orderNo%'";

        }

        if(!empty($nickname)){

            $where .= " and nickname like '%". addslashes($nickname) ."%'";

        }

        if(!empty($tel)){

            $where .= " and tel like '%".addslashes($tel)."%'";

        }

        if(!empty($payWay)){

            $where .= " and pay_code = '$payWay'";

        }

        if($status=='' || $status>10){



        }else{

            $where .= " and status = $status";

        }

        if(!empty($start_time)){

            $where .= " and service_time >= '$start_time'";

        }

        if(!empty($end_time)){

            $where .= " and service_time <= '$end_time'";

        }

        if(!empty($store)){

            $where.=" and store_id = $store";

        }

        if(!empty($barber)){

            $where.=" and barber_name like '%$barber%'";

        }

        return $where;

    }

    private  function filterEmoji($str)
    {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);

        return $str;
    }

    public function exportOrder(){

        $orderView = M('ViewOrder');

        $where = $this->getSearchWhere();

        try{

            $data = $orderView->where($where)->order("service_time desc")->select();

            if($data){

                foreach ($data as $key=>$item) {
                    $data[$key]['nickname']=$this->filterEmoji($item['nickname']);

                    switch ($data[$key]['status']){

                        case 0:

                            $data[$key]['status'] = '已关闭';

                            break;

                        case 1:

                            $data[$key]['status'] = '未服务';

                            break;

                        case 2:

                            $data[$key]['status'] = '已完成';

                            break;

                        case 3:

                            $data[$key]['status'] = '已失效';

                            break;

                    }

                    $data[$key]['pay_success_time'] = date("Y-m-d H:i:s",strtotime($data[$key]['pay_success_time']));

                }

            }

            $xlsCell  = array(

                array('order_id','预约单号'),

                array('nickname','预约昵称'),

                array('tel','预约电话'),

                array('barber_name','预约理发师'),

                array('store_name','所属店铺'),

                array('pay_name','支付方式'),

                array('service_name','服务类型'),

                array('service_time','预约时间'),

                array('total_price','应付金额'),

                array('coupon_money','优惠金额'),

                array('pay_price','实际支付'),

                array('status','状态'),

                array('pay_success_time','支付时间')

            );

            exportExcel("jdx",$xlsCell,$data);

        }catch (Exception $e){

            $this->error('查询发生错误');

        }

    }



    public function detail()

    {

        $orderView = M('ViewOrder');

        $id = (int)I('id');

        $order = $orderView->where("order_id = %d",$id)->find();

        if (!$order && $order['deleted'] == 1) {

            $this->error('订单不存在或已删除');

        }

        $this->ajaxReturn(array('status'=>1,'detail'=>$order));

    }



    public function edit_status()

    {

        $orderId = I('get.orderId');

        $nowStatus = I('get.nowStatus');

        $editStatus = I('get.editStatus');

        $order = $this->order_model->find($orderId);

        if (empty($order) && $order['deleted']) {

            $this->error('订单不存在');

        }

        if (empty($nowStatus) && empty($editStatus)) {

            $this->error("请选择要更改的状态");

        }

        if($order['status']!=1){

            $this->error("订单状态无法修改，请刷新重试");

        }

        if($order['service_lock']>0){

            $this->error("服务已开始,无法修改状态");

        }

        $this->order_model->startTrans();

        try{

            if($editStatus==0){

                $data = array(

                    'close_time'=>date('Y-m-d H:i:s'),

                    'status'=>0

                );

                if ($order['pay_price'] > 0) {

                    if ($order['pay_code'] == 'moneypay') {

                        $res = D('Member')->changeMoney(2, $order['pay_price'], "取消订单退款,订单ID：{$orderId}", $order['member_id']);

                        if ($res === false) {

                            E("退款失败，请重试");

                        }else{

                            $data['refund_time'] = $data['close_time'];

                        }

                    }

                }

                $res = $this->order_model->where(array('order_id' => $orderId))->save($data);

                $statusInfo = "已关闭";

            }elseif($editStatus==2){

                $res1 = $this->order_model->startService($orderId);

                $barberId = M('ViewOrder')->where("order_id={$orderId}")->getField('barber_id');

                if($res1){

                    $res = $this->order_model->endService($orderId,$barberId);

                }

                $statusInfo = "已完成";

            }

            if($res){

                $this->order_model->commit();

                D("Log")->addLog("更改订单{$orderId}状态：$statusInfo");

                $this->success("状态更改成功");

            }else{

                $this->order_model->rollback();

                $this->error("状态更改失败");

            }

        }catch (Exception $e){

            $this->order_model->rollback();

            //$this->error("状态更改失败:".$e->getMessage());

            $this->error("状态更改失败:");

        }



    }



   /* public function getComs()

    {

        $exp = new ExpressModel();



        $coms = $exp->getComs();



        if (IS_AJAX) {



            $this->ajaxReturn(array('coms'=>$coms));

        }



        return $coms;

    }*/

}