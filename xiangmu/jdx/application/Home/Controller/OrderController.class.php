<?php



namespace Home\Controller;



use Common\Model\OrderModel;

use Common\Model\BarberModel;

use Think\Exception;



class OrderController extends CommonController

{

    protected $orderModel;

    protected $BarberModel;



    public function __construct()

    {

        parent::__construct();

        $this->orderModel = new OrderModel();

        $this->BarberModel = new BarberModel();

    }



    /**

     *订单列表

     */

    public function index()

    {

        if (IS_AJAX && IS_POST) {

            $p = I('p');

            $pageSize = $this->pageSize;

            $status = I('nowStatus') ? (int)I('nowStatus') : 100;

            $map = array(

                'member_id' => $this->member['member_id'],

                'is_pay' => 1,

                'deleted' => 0

            );

            $tabNum = array(

                1=>0,

                2=>0,

                3=>0,

                100=>0,

            );

            $countArr = $this->orderModel

                ->field('status,COUNT(status) count')

                ->where($map)

                ->group('status')

                ->select();

            foreach ($countArr as $k=>$v) {

                if ((int)$v['status'] == 0 || (int)$v['status'] == 3) {

                    $tabNum[3] += (int)$v['count'];

                } else {

                    $tabNum[(int)$v['status']] = (int)$v['count'];

                }

                $tabNum[100] += (int)$v['count'];

            }

            if ($status != 100) {

                $map['status'] = $status;

            }

            if ($status == 3) {

                $map['status'] = array('in','0,3');

            }

            $count = M('view_order')->where($map)->count();

            $list = M('view_order')

                ->field('order_id,barber_id,status,is_comment,headimgurl,barber_name,service_name,DATE_FORMAT(service_time,"%Y-%m-%d %H:%i") service_time,barber_tel')

                ->where($map)

                ->order('update_time desc')

                ->page($p,$pageSize)

                ->select();

            $totalPages = ceil($count/$pageSize);

            $this->success(array('list'=>$list,'tabNum'=>$tabNum,'totalPages'=>$totalPages));

        }

        $this->display();

    }



    /**

     *取消订单

     */

    public function orderCancel()

    {

        $orderId = I('orderId');

        $map = array(

            'order_id' => $orderId,

            'member_id' => $this->member['member_id'],

            'deleted' => 0

        );

        $order = $this->orderModel->where($map)->find();

        if (empty($order)) {

            $this->error('订单不存在');

        }

        /*判断当前时间到预约时间的差值是否超过12小时*/

        $val = strtotime($order['service_time'])-time();

        $time = 12*60*60;

        if ($val < $time) {

            $refund = 0;

        }else{

            $refund = $order['pay_price'];

        }



        if (IS_AJAX && IS_POST) {

            if ($order['status'] != 1) {

                $this->error('订单状态已更改');

            }

            if ($order['service_lock'] == 1) {

                $this->error('正在服务中不能取消');

            }

            try {

                $this->orderModel->startTrans();

                if ($refund > 0) {

                    if ($order['pay_code'] == 'moneypay') {

                        $res = D('Member')->changeMoney(2, $order['pay_price'], "取消订单退款,订单ID：{$orderId}", $order['member_id']);

                        if ($res === false) {

                            E("退款失败，请重试");

                        }

                    }elseif($order['pay_code'] == 'wxpay'){//微信支付退款，要打折退款
                        $res = D('Member')->changeMoney(2, $order['pay_price']*0.85, "取消订单退款,订单ID：{$orderId}", $order['member_id']);

                        if ($res === false) {

                            E("退款失败，请重试");

                        }
                    }

                }
                if($order['coupon_money']>0){
                    M('Coupon')->where(array('order_id' => $orderId))->save(array('is_used'=>0,'use_time'=>'0000-00-00 00:00:00'));//取消返回优惠券
                }

                $data = array(

                    'close_time'=>date('Y-m-d H:i:s'),

                    'refund_time'=>date('Y-m-d H:i:s'),

                    'refund_price'=>$refund,

                    'status'=>0

                );

                $res = $this->orderModel->where(array('order_id' => $orderId))->save($data);

                if ($res === false) {

                    E("取消订单失败，请重试");

                }

                $this->orderModel->commit();

                $this->refresh_user_session();

                $this->orderModel->sendCancelTemplateMsg($this->member['openid'],$orderId,$refund);

                $this->success("取消订单成功");

            } catch (Exception $e) {

                $this->orderModel->rollback();

                $this->error($e->getMessage());

            }

        }

        $list = array(

            'order_id'=>$order['order_id'],

            'pay_code'=>$order['pay_code'],

            'refund'=>$refund

        );

        $this->assign('order',json_encode($list));

        $this->display();

    }



    /**

     *预约理发师

     */

    public function appointment()

    {

        $this->checkMemberStatus();

        $barber_id = (int)I('barber_id');

        $store_barber_id = (int)I('store_barber_id');

        $barber = $this->BarberModel->find($barber_id);

        if (empty($barber) || $barber['closed'] == 1) {

            $this->error("理发师不存在或已删除，请刷新");

        }

        $store_barber = M('store_barber')->find($store_barber_id);

        if (empty($store_barber)) {

            $this->error("门店或理发师不存在，请刷新");

        }

        $map = array('barber_store_id' => $store_barber['id'],'deleted'=>0);

        $services = M('services')->where($map)->select();

        $nextWeek = $this->BarberModel->NextWeekWorkTime($barber['id'],$store_barber['store_id']);

        $this->assign('barber',$barber);

        $this->assign('services',json_encode($services));

        $this->assign('nextWeek',json_encode($nextWeek));

        $this->assign('mobile',$this->member['mobile']);

        $this->display();

    }



    /**

     *确认预约信息

     */

    public function orderConfirm()

    {

        $this->checkMemberStatus();

        $barber_id = I('barber_id');

        $service_id = I('service_id');

        $service_time = I('service_time','','urldecode');

        $return = $this->checkBarberServices($barber_id,$service_time);

        if ($return['status'] == false && $return['state'] == 1) {

            $this->error($return['msg']);

        }

        $services = M('services')->find($service_id);

        $map = array(

            'store_barber_id'=>$services['barber_store_id'],

            'work_date'=>date('Y-m-d',strtotime($service_time))

        );

        $schedule = M('schedule')->where($map)->find();

        if (empty($schedule) or $schedule['is_work'] != 1) {

            $this->error("该理发师今天未排班，请刷新");

        }

        $store_barber = M('view_barber_store')

            ->field('barber_id,name,stars,service_num,headimgurl,store_name,address')

            ->where(array('store_barber_id'=>$services['barber_store_id']))

            ->find();

        $this->assign('services',$services);

        $this->assign('store_barber',$store_barber);

        $this->assign('service_time',$service_time);

        $this->assign('mobile',$this->member['mobile']);

        $this->display();

    }



    /**

     *下单操作

     */

    public function orderDown()

    {

        $this->checkMemberStatus();

        $barber_id = I('barber_id');

        $service_id = I('service_id');

        $service_time = I('service_time','','urldecode');

        $mobile = I('mobile');

        $return = $this->checkBarberServices($barber_id,$service_time);

        if ($return['status'] == false) {

            $this->error(array('msg'=>$return['msg'],'url'=>$return['url']));

        }

        $services = M('services')->find($service_id);

        $data = array(

            'member_id'=>$this->member['member_id'],

            'service_id'=>$services['id'],

            'service_name'=>$services['name'],

            'service_time'=>$service_time,

            'total_price'=>$services['price'],

            'pay_price'=>$services['price'],

            'status'=>1,

            'tel'=>$mobile

        );

        try {

            $this->orderModel->startTrans();

            $res = $this->orderModel->lock(true)->add($data);

            if (!$res) {

                E("下单失败，请重试");

            }

            $this->orderModel->commit();

            $this->success($res);

        } catch (Exception $e) {

            $this->orderModel->rollback();

            $this->error($e->getMessage());

        }

    }



    /**

     * 去支付

     */

    public function goPay()

    {

        $this->checkMemberStatus();

        $order = $this->checkOrder();

        $order = array(

            'order_id'=>$order['order_id'],

            'total_price'=>$order['total_price']

        );

        if ($order['is_pay']) {

            //$this->error("订单已支付，请到我的预约查看",U('index'));

            $this->redirect("Member/index");

        }

        $map = array(

            'member_id'=>$this->member['member_id'],

            'is_invoke'=>1,

            'is_used'=>0

        );

        $coupon = M('coupon')->field('id,code,money')->where($map)->select();

        foreach ($coupon as $k =>$v) {

            $coupon[$k]['isDisabled'] = false;

            $coupon[$k]['isChecked'] = false;

        }

        $this->assign('order',json_encode($order));

        $this->assign('coupon',json_encode($coupon));

        $this->assign('money',$this->member['money']);

        $this->display();

    }



    public function detail()

    {

        $this->checkOrder();

        $orderId = I('orderId');

        $map = array(

            'order_id' => $orderId,

            'deleted' => 0

        );

        $order = M('view_order')

            ->field('order_id,barber_name,headimgurl,tel,status,

            barber_tel,service_name,total_price,store_name,refund_price,

            address,coupon_money,pay_name,pay_price,is_comment,

            DATE_FORMAT(close_time,"%Y-%m-%d %H:%i") close_time,

            DATE_FORMAT(pay_success_time,"%Y-%m-%d %H:%i") pay_success_time,

            DATE_FORMAT(service_start_time,"%Y-%m-%d %H:%i") service_start_time,

            DATE_FORMAT(service_end_time,"%Y-%m-%d %H:%i") service_end_time,

            DATE_FORMAT(service_time,"%Y-%m-%d %H:%i") service_time')

            ->where($map)

            ->find();

        $statusShow = array(

            0=>'已关闭',

            1=>'未服务',

            2=>'已完成',

            3=>'已失效',

            100=>'全部'

        );

        $this->assign('order',$order);

        $this->assign('statusShow',$statusShow);

        $this->display();

    }



    /**

     * 验证订单是否存在

     * @return mixed    //若订单存在返回订单信息，否则抛出错误提示

     */

    public function checkOrder()

    {

        $orderId = I('orderId');

        if (empty($orderId)) {

            $this->error("订单号不存在");

        }

        if (!$order = $this->orderModel->find($orderId)) {

            $this->error("订单不存在");

        }

        return $order;

    }



    /**

     * 检测理发师是否已被预约

     * @param $barber_id        //理发师ID

     * @param $service_time     //服务时间

     */

    public function checkBarberServices($barber_id, $service_time)

    {

        $map = array(

            'barber_id'=>$barber_id,

            'service_time'=>$service_time,

            'status'=>1,

            'deleted'=>0

        );

        if (strtotime($service_time) <= time()) {

            $this->error("服务时间不能小于当前时间，请刷新页面");

        }

        $res = M('view_order')->where($map)->find();

        if ($res) {

            $backUrl = U("Order/appointment",array('barber_id'=>$res['barber_id'],'store_barber_id'=>$res['barber_store_id']));

            if ($res['member_id'] == $this->member['member_id']) {

                $this->orderModel->deleteOrder($res['order_id'],$this->member['member_id']);

                return array('status'=>false,'state'=>2,'msg'=>'订单已失效，请重新下单','url'=>$backUrl);

            } else {

                return array('status'=>false,'state'=>1,'msg'=>'该时间已被预约，请刷新重新选择','url'=>$backUrl);

            }

        } else {

            return array('status'=>true);

        }

    }



    public function deleteOrder($oid){

        $this->orderModel->deleteOrder($oid,$this->member['member_id']);

    }



    protected function checkMemberStatus(){

        $this->refresh_user_session();

        if($this->member['status']!=1){

            $this->error('用户已被禁用');

        }

    }

}