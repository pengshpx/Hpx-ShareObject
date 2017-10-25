<?php

namespace Home\Controller;

use Common\Controller\MobilebaseController;
use Com\WechatAuth;
use Common\Model\CouponModel;
use Think\Exception;
use Common\Model\MemberModel;

class CommonController extends MobilebaseController
{
    protected $weChatOptions;
    protected $weChatAuth;
    protected $member;
    protected $pageSize = 10;
    public function __construct() {

        parent::__construct();

        if(!is_weixin()){
            $this->error("请在微信浏览器中打开");
        }
        $this->weChatOptions = getOptions('weixin');

        $this->weChatAuth = new WechatAuth($this->weChatOptions['appId'],$this->weChatOptions['appSecret']);

        /*为测试用指定固定用户*/
        //session('user',M("Member")->find(7));

        /*未登录的情况下，跳转微信获取个人信息 */
        if(!$this->check_login() && (ACTION_NAME != 'wechat_login')){
            $state = md5(time().mt_rand(1,1000));
            session('state', $state);

            //记录是否带有父级ID
            $superior = I('superior');
            if($superior>0){
                session('superior',$superior);
            }

            if (!empty($_SERVER['REQUEST_URI'])) {
                $backUrl = $_SERVER['REQUEST_URI'];
            } else {
                $backUrl = U('index/index');
            }
            session('backurl', $backUrl);
            $loginUrl = $this->weChatAuth->getRequestCodeURL("http://".$_SERVER['HTTP_HOST'].U('Home/Common/wechat_login'),$state);

            header("location:$loginUrl");
            //echo $login_url;
            die;
        }else{
            $this->member = session('user');
        }
        if(!IS_AJAX){
            //当前模块名
            $this->assign('mod',MODULE_NAME);
            //当前控制器名
            $this->assign('con',CONTROLLER_NAME);
            //当前操作名
            $this->assign('act',ACTION_NAME);
        }
        /*if((ACTION_NAME != 'wechat_login') && !empty($this->member) && $this->member['status']!=1 ){
            $this->refresh_user_session();
            if($this->member['status']!=1){
                $this->error('对不起，此帐号已被禁用');
            }
        }*/
    }

    public function wechat_login(){
        $state = I("get.state");
        $myState = session('state');
        $code = I("get.code");

        if($myState  && ($state == $myState) && $code){
            $memberModel = new MemberModel();
            try{
                $token = $this->weChatAuth->getAccessToken('code',$code);
                $userInfo = $this->weChatAuth->getUserInfo($token['openid']);
                echo json_encode($userInfo);
                $memberModel->startTrans();

                $superior = session('superior');
                $member = $memberModel->where("openid = '{$userInfo['openid']}'")->find();
                if($member){
                    $memberModel->where("openid = '{$userInfo['openid']}'")->save($userInfo);
                }else{
                    if(isset($superior)){
                        //记录来源的分享ID，后续做优惠券发放
                        $userInfo['superior'] = $superior;
                    }
                    $memberId = $memberModel->add($userInfo);
                    if(!$memberId){
                        throw new Exception("注册新帐号失败");
                    }
                    $member = $memberModel->find($memberId);
                    if(isset($superior)){
                        //优惠券发放
                        $couponModel = new CouponModel();
                        $couponId = $couponModel->send($member['member_id'],1);
                        $sOpenid = $memberModel->field('openid')->find($superior);
                        $memberModel->sendShareTemplateMsg($sOpenid['openid'],$memberId);
                        $couponModel->send($superior,0,$couponId);
                    }
                }

                $memberModel->commit();
                session('user',$member);
                $returnUrl = session('backurl');
                if($returnUrl){
                    redirect($returnUrl);
                }else{
                    redirect(U('Index/index'));
                }
            }catch (Exception $e){
                $memberModel->rollback();
                $this->error($e->getMessage());
            }
        }else{
            $this->error("非法请求");
        }
    }


    public function refresh_user_session(){
        $member = session('user');
        if($member){
            $this->member = M("Member")->find($member['member_id']);
            session('user', $this->member);
            return true;
        }else{
            return false;
        }
    }
}