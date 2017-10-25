<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-06-16
 * Time: 17:28
 */

namespace Common\Model;


class MoneyLogModel extends CommonModel
{

    /**
     * 记录余额变动
     * @param $type 类型 1：消费，2：充值
     * @param $change 变动的金额数
     * @param $remark 备注
     * @param $memberId 用户ID
     * @return bool
     */
    public function insertLog($type=1, $change, $remark,$memberId){
        $data['type'] = $type;
        $data['change'] = $change;
        $data['remarks'] = $remark;
        $data['member_id'] = $memberId;
        $before = M("Member")->where(array('member_id'=>$memberId))->getField("money");
        $data['now'] = $before + $change;
        return $this->add($data);
    }
}