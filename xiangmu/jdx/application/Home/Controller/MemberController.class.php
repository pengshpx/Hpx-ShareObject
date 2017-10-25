<?php



namespace Home\Controller;

use Common\Model\WeChatModel;

class MemberController extends CommonController

{

    public function index()

    {

        $this->refresh_user_session();

        $this->assign('member_id',$this->member['member_id']);

        $this->assign('headimgurl',$this->member['headimgurl']);

        $this->assign('nickname',$this->member['nickname']);

        $this->assign('mobile',$this->member['mobile']);

        $this->assign('money',$this->member['money']);

        $this->assign('call',getOptions('mall_setting'));

        $map = array(

            'member_id'=>$this->member['member_id'],

            'is_used'=>0,

            'is_invoke'=>1

        );

        $count = M('coupon')->where($map)->count();
        $this->assign('count',$count);
        $this->display();

    }



    public function balance()

    {

        $this->refresh_user_session();

        //$record = M('RechargeLog')->where("member_id=%d",$this->member['member_id'])->count();

        if($this->member['money']>0){

            $this->assign('money',$this->member['money'].'元');

        }else{

            $this->assign('money','尚未开通超剪卡');

        }

        $this->display();

    }



    public function balanceLog()

    {

        if (IS_AJAX && IS_POST) {

            $p = I('p');

            $pageSize = $this->pageSize;

            $map = array('member_id'=>$this->member['member_id']);

            $count = M('money_log')->where($map)->count();

            $list = M('money_log')

                ->field('change,now,type,DATE_FORMAT(create_time,"%Y-%m-%d %H:%i") time')

                ->where($map)

                ->order('create_time desc')

                ->page($p,$pageSize)

                ->select();

            $totalPages = ceil($count/$pageSize);

            $this->success(array('list'=>$list,'totalPages'=>$totalPages));

        }



        $this->display();

    }



    public function recharge()

    {

        $this->refresh_user_session();

        $this->assign('member_id',$this->member['member_id']);

        $this->assign('nickname',$this->member['nickname']);

        //$record = M('RechargeLog')->where("member_id=%d",$this->member['member_id'])->count();

        if($this->member['money']>0){

            $this->assign('money',$this->member['money']);

        }else{

            $this->assign('money','尚未开通超剪卡');

        }

        $list = M('member_card')->order('price asc')->select();

        $this->assign('lists',$list);

        $this->display();

    }



    public function coupon()

    {

        if (IS_AJAX && IS_POST) {

            $p = I('p');

            $pageSize = $this->pageSize;

            $map = array(

                'member_id'=>$this->member['member_id'],

                'is_used'=>0,

                'is_invoke'=>1

            );

            $count = M('coupon')->where($map)->count();

            $list = M('coupon')->where($map)->page($p,$pageSize)->select();

            $totalPages = ceil($count/$pageSize);

            $this->success(array('list'=>$list,'totalPages'=>$totalPages));

        }



        $this->display();

    }



    public function saveMobile()

    {

        $mobile = I('mobile');

        if (!isMobile($mobile)) {

            $this->error("手机号格式不正确");

        }

        $map = array('member_id'=>$this->member['member_id']);

        $res = M('member')->where($map)->setField('mobile',$mobile);

        if ($res !== false) {

            $this->refresh_user_session();

            $this->success();

        } else {

            $this->error("保存失败，请重试");

        }

    }



    public function share()

    {

        $model = new WeChatModel();

        $sign = $model->getSignPackage();

        if(!$sign){

            $this->redirect('share');

        }

        $this->assign('sign',$sign);

        $this->assign('myId',$this->member['member_id']);

        $this->assign('headimgurl',$this->member['headimgurl']);

        $this->display();

    }

}