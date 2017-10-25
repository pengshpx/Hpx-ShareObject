<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 2016-06-24

 * Time: 13:59

 */



namespace Common\Model;





use Think\Exception;



class RechargeLogModel extends CommonModel

{

    public function addLog($memberId,$money,$outTradeNo){

        $data['member_id'] = $memberId;

        $data['money'] = $money;

        $data['out_trade_no'] = $outTradeNo;

        return $this->add($data);

    }



    public function generateOutTradeNo($id){

        $no = "R".mt_rand(1000,9999).date("YmdHis").$id;

        return $no;

    }



    public function notifySuccess($data){

        $log = $this->where("out_trade_no = '%s'",$data['out_trade_no'])->find();

        if(!$log){

            \Think\Log::record("未找到充值的记录","ERR");

            return false;

        }

        $this->startTrans();
        $status=false;
        try{

            $res = $this->where("out_trade_no='%s'", $data['out_trade_no'])->save(array(

                "status" => 1,

                "pay_success_time" => $data['time_end'],

                "total_fee" => $data['total_fee'],

                "transaction_id"=>$data['transaction_id'],

                "openid"=>$data["openid"]

            ));

            if(!$res){

                \Think\Log::record("更新充值记录失败","ERR");

            }

            $memberModel = new MemberModel();

            $res1 = $memberModel->changeMoney(2,$log['money'],"微信充值成功",$log['member_id']);



            if(!$res1){

                \Think\Log::record("给用户充值金额失败:".$memberModel->getError(),"ERR");

            }

            if($res && $res1){
                $status=true;
                $this->commit();



            }else{

                $this->rollback();

                return false;

            }

        }catch (Exception $e){

            \Think\Log::record("操作失败:".$e->getMessage(),"ERR");

            $this->rollback();

            return false;

        }
        if($status){
            $openid = $memberModel->field('openid')->find($log['member_id']);

            $memberModel->sendRechargeTemplateMsg($openid['openid'],$data['total_fee']/100);
        }
        return true;

        

    }

}