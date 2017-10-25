<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 *      会员类
 * Date: 2016/6/5 0005
 * Time: 上午 10:19
 */

namespace Common\Model;

class MemberModel extends CommonModel
{
    /**
     * @param $map              查询条件
     * @param $field            要查询的字段
     * @param bool $keyById     是否用ID作为主键
     * @return array            返回查询内容
     */
    public function memberList($map, $field, $keyById=false){
        $member = $this
            ->alias('m')
            ->field($field)
            ->join('left join ehecd_wxinfo wx on m.wx_openid=wx.openid')
            ->where($map)
            ->order("m.member_id desc")
            ->select();
        if($keyById){
            $member = $this->getKeyByVal($member,'member_id');
        }
        return $member;
    }

    //通过会员ID获取该会员的下级
    /*public function getJunior($memberId,$field='m.*,wx.*')
    {
        $map = array(
            'm.superior'=>$memberId
        );
        $junior = $this->alias('m')->field($field)
            ->join('left join ehecd_wxinfo wx on m.wx_openid=wx.openid')
            ->where($map)->select();
        return $junior;
    }*/

    /**
     * @param int $type 1：消费，2：充值
     * @param $change 变动的金额
     * @param $remark 备注
     * @param $memberId 用户ID
     * @return false|int
     */
    public function changeMoney($type = 1, $change, $remark, $memberId)
    {
        if($change<=0){
            E("变动金额不能为负");
        }
        $change = $type == 1 ? -$change : $change;

        $moneyLogModel = new MoneyLogModel();
        $res = $moneyLogModel->insertLog($type, $change, $remark, $memberId);
        if ($res) {
            if ($type == 1) {
                $res = $this->execute("update __PREFIX__member set money = money + {$change} where member_id={$memberId} and  money>={$change}");
            } else if ($type == 2) {
                $res = $this->execute("update __PREFIX__member set money = money + {$change} where member_id={$memberId}");
            }
        }else{
            E("写入余额变动记录失败:".$moneyLogModel->getError());
        }
        return $res;
    }

    public function getMemberInfo($id)
    {
        $member = M('member')->find($id);
        return $member;
    }

    /**
     * 发送充值成功模版消息
     * @param $openid
     * @param $money
     * @return bool
     * @throws Exception
     */
    public function sendRechargeTemplateMsg($openid,$money){
        $templateId = "pJRBi34AqCz3jBncNwM0U_bzFOBSQh9rHOERS8_dlKI";
        $wx = new WeChatModel();
        try{
            $data = array(
                "first"=>array("value"=>'您好，您已成功进行会员卡充值。',"color"=>"#173177"),
                "accountType"=>array("value"=>'卡种类型',"color"=>"#173177"),
                "account"=>array("value"=>'超剪卡',"color"=>"#173177"),
                "amount"=>array("value"=>$money.'元',"color"=>"#173177"),
                "result"=>array("value"=>'充值成功',"color"=>"#173177"),
                "remark"=>array("value"=>"如有任何疑问，请联系客服，客服电话028-89895818。","color"=>"#173177"),
            );
            $url = URL_ROOT.U('Member/balanceLog');
            $wx->templateMsg($openid,$templateId,$url,$data);
        }catch (Exception $e){
            \Think\Log::record($e->getMessage(),"ERR");
        }
    }

    /**
     * 发送邀请成功模版消息
     * @param $openid
     * @param $memberId
     * @return bool
     * @throws Exception
     */
    public function sendShareTemplateMsg($openid,$memberId){
        $templateId = "EWiydwXJD4WEz-z1LNfO8WRZxypOJDbEV2klb2U_SIE";
        $member = M('member')->find($memberId);
        $wx = new WeChatModel();
        try{
            $data = array(
                "first"=>array("value"=>"{$member['nickname']} 已成功注册，并领取了20元代金券！","color"=>"#173177"),
                "keyword1"=>array("value"=>$member['member_id'],"color"=>"#173177"),
                "keyword2"=>array("value"=>$member['create_time'],"color"=>"#173177"),
                "remark"=>array("value"=>"当 {$member['nickname']} 成功预约并完成服务后，你也将获得20元代金券，放在“个人中心/优惠码”里。支付时代金券可以叠加使用，但超出订单金额部分不予找补。","color"=>"#173177"),
            );
            $url = URL_ROOT.U('Member/index');
            $wx->templateMsg($openid,$templateId,$url,$data);
        }catch (Exception $e){
            \Think\Log::record($e->getMessage(),"ERR");
        }
    }
}