<?php

namespace Home\Controller;


use Think\Controller;





class CouponController extends CommonController {


    public function business(){

        $code=I('bcode','');
        if(!empty($code)){
            $info=M('CouponBusiness')->where(array('code'=>$code))->find();
            if($info){
                if($info['deleted']==0){
                        $this->assign('info',$info);
                }else{
                    //已结束
                    $this->error('活动已结束');
                }
            }else{
                //没找到
                $this->error('没有找到该活动');
            }
        }else{
            //跳转
            $this->error('活动链接错误');
        }
        $this->display();

    }

    public function get_coupon(){
        $bid=I('bid',0,'intval');
        $mobile=I('mobile','');
        if(empty($mobile)){
            $this->error('请输入手机号');
        }
        if(!isMobile($mobile)){
            $this->error('请输入正确的手机号');
        }
        if($bid){
            $info=M('CouponBusiness')->find($bid);
            if($info){
                if($info['deleted']==0){
                    $oldmobile=M('Member')->where(array('member_id'=>$this->member['member_id']))->getField('mobile');
                    if($oldmobile){
                        $this->error('抱歉，该优惠仅适用于新用户。');
                    }
                    //$where['bid']=$bid;
                    $count=M('Member')->where(array('member_id'=>array('neq',$this->member['member_id']),'mobile'=>$mobile))->count();
                    if($count>0){
                        $this->error('抱歉，该优惠仅适用于新用户。');
                    }
                    $where['mid']=$this->member['member_id'];
                    $where['phone']=$mobile;
                    $where['_logic'] = 'or';
                    $count=M('CouponBusinessLog')->where($where)->count();
                    //echo M('CouponBusinessLog')->getLastSql();
                    if($count>0){
                        $this->error('你已经领过了!');
                    }else{
                        $coupon_model=D('Coupon');
                        $id=$coupon_model->send($this->member['member_id'],1,0,$info['money']);
                        if($id){
                            $data=array(
                                'bid'=>$bid ,
                                'mid'=>$this->member['member_id'],
                                'phone'=>$mobile,
                                'money'=>$info['money'],
                                'create_time' => date('Y-m-d H:i:s'),
                            );
                            M('CouponBusinessLog')->add($data);//记录领取日志
                            M('CouponBusiness')->where(array('id'=>$bid))->setInc('send_count');//更新领取数量
                            M('Member')->where(array('member_id'=>$this->member['member_id'],'mobile'=>''))->setField('mobile',$mobile);//更新会员手机号
                            $this->success('领取成功');
                        }else{
                            $this->error('领取失败');
                        }
                    }
                }else{
                    $this->error('活动已结束!');
                }
            }else{
                $this->error('没有找到该活动!');
            }
        }else{
            $this->error('参数错误!');
        }

    }

    public function do_success(){
        $mobile=I('mobile','');
        $money=I('money',0,'floatval');
        $this->assign('mobile',$mobile);
        $this->assign('money',$money);
        $this->display();
    }
}