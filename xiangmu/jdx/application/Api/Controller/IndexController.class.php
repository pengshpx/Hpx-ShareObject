<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 2016-07-06

 * Time: 11:48

 */



namespace Api\Controller;

use Common\Model\BarberModel;

use Common\Model\OrderModel;

use Think\Exception;



class IndexController extends ApiCommonController

{

    private $barberModel;



    public function __construct()

    {

        parent::__construct();

        $this->barberModel = new BarberModel();

    }



    /**

     *登录

     */

    public function login()

    {

        if (empty($this->data['tel']) || empty($this->data['pwd'])) {

            $this->error(2, $this->errorMsg[2]);

        }



        $data = $this->barberModel->login($this->data['tel'], $this->data['pwd']);

        if ($data) {

            if($data['closed']==1){

                $this->error(10,"对不起，你的账户已被冻结");

            }else{

                $this->success($data);

            }



        } else {

            $this->error(4, $this->errorMsg[4]);

        }

    }



    /**

     *修改密码

     */

    public function editPwd()

    {

        if (empty($this->data['oldPwd']) || empty($this->data['newPwd1']) || empty($this->data['newPwd2'])) {

            $this->error(1002, '密码不能为空');

        }

        if ($this->data['newPwd1'] != $this->data['newPwd2']) {

            $this->error(1004, '两次密码不一致');

        }

        if (strlen($this->data['newPwd1']) < 6) {

            $this->error(1005, '密码过短');

        }

        $barber = $this->barberModel->getBarberById($this->data['barber_id']);

        if ($barber['pwd'] == md5($this->data['oldPwd'])) {

            $res = $this->barberModel->where("id=%d", $this->data['barber_id'])->save(array('pwd' => md5($this->data['newPwd1'])));

            if ($res) {

                $this->success(array('info' => '更改密码成功'));

            } else {

                $this->error(1006, '更改密码失败');

            }

        } else {

            $this->error(1003, '旧密码错误');

        }

    }



    /**

     *APP首页进行中的订单

     */

    public function myServices()

    {

        try {

            $data['notice_unread'] = $this->barberModel->getNoticeNoRead($this->data['barber_id']);

            $data['unservice_orders'] = $this->barberModel->getMyUnServicesList($this->data['barber_id']);

            $this->success($data);

        } catch (Exception $e) {

            $this->error(3,$e->getMessage());

        }

    }



    /**

     *开始服务

     */

    public function startService(){

        if(empty($this->data['order_id'])){

            $this->error(6,$this->errorMsg[6]);

        }

        try{

            $orderModel = new OrderModel();

            $res = $orderModel->startService($this->data['order_id']);

            if($res){

                $this->success(array('info'=>'开始服务成功'));

            }else{

                $this->error(1202,'开始服务失败');

            }

        }catch (Exception $e){

            $this->error(1201,$e->getMessage());

        }

    }



    /**

     *完成服务

     */

    public function endService(){

        if(empty($this->data['order_id'])){

            $this->error(6,$this->errorMsg[6]);

        }

        try{

            $orderModel = new OrderModel();

            $orderModel->startTrans();

            $res = $orderModel->endService($this->data['order_id'],$this->data['barber_id']);

            if($res){

                $orderModel->commit();

                $this->success(array('info'=>'完成服务成功'));

            }else{

                $this->error(1302,'完成服务失败');

            }



        }catch (Exception $e){

            $orderModel->rollback();

            $this->error(1301,$e->getMessage());

        }

    }



    /**

     *理发师业绩统计

     */

    public function statistic(){

        $data['yesterday'] = $this->barberModel->statisticYesterday($this->data['barber_id']);

        $data['today'] = $this->barberModel->statisticToday($this->data['barber_id']);

        $data['lastMonth'] = $this->barberModel->statisticLastMonth($this->data['barber_id']);

        $data['thisMonth'] = $this->barberModel->statisticThisMonth($this->data['barber_id']);

        return $this->success($data);

    }



    /**

     *理发师个人信息

     */

    public function barberInfo()

    {

        $barber = $this->barberModel->getBarberInfoById($this->data['barber_id']);

        $setting = getOptions('mall_setting');

        $barber['callCenters'] = $setting['callCenters'];

        if ($barber) {

            $this->success($barber);

        } else {

            $this->error(1101, "未找到该理发师或已被删除，请检查ID");

        }

    }



    /**

     *理发师的预约表

     */

    public function myTime(){

        $data = $this->barberModel->myNextWeekTime($this->data['barber_id']);

        $this->success($data);

    }



    public function headImgUpload(){

        try{

            $res = $this->appUpload($this->data['imgBase64Str']);

            if($res){

                $res1 = $this->barberModel->where("id={$this->data['barber_id']}")->save(array('headimgurl'=>$res));

                if($res1){

                    $this->success(array('headimgurl'=>$res));

                }else{

                    $this->error(3,'上传文件成功，但保存数据库失败');

                }

            }else{

                $this->error(3,'上传文件失败');

            }

        }catch (Exception $e){

            $this->error('上传文件失败:'.$e->getMessage());

        }



    }



    public function appUpload($base64String)

    {

        $base64Arr = explode(',',$base64String);

        $base64Str = count($base64Arr)==2?$base64Arr[1]:$base64Arr[0];

        $name = date('Ymd', NOW_TIME);

        $savePath = "/data/upload/{$name}/";

        $dir = ".".$savePath;

        if (!is_dir($dir)) {

            mkdir($dir, 0755, true);

        }

        $savename = uniqid().'.jpg';

        $outputFile = $dir.$savename;

        $retrunPath = $savePath.$savename;

        //$base64Str = str_replace(" ","+",$base64Str);

        $res = $this->base64_to_img($base64Str,$outputFile);

        if($res > 0){

            return $retrunPath;

        }else{

            return false;

        }

    }



    private function base64_to_img($base64_string, $output_file)

    {

        $ifp = fopen($output_file, "wb");

        $res = fwrite($ifp, base64_decode($base64_string));

        fclose($ifp);

        return $res;

    }





    protected function checkDatetime($str, $format="Y-m-d H:i:s"){

        $unixTime=strtotime($str);

        $checkDate= date($format, $unixTime);

        if($checkDate==$str)

            return true;

        else

            return false;

    }

}