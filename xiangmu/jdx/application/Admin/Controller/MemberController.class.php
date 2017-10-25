<?php
/**
 * 会员管理处理器
 * Date: 2016/6/5 0005
 * Time: 上午 10:26
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Think\Exception;

class MemberController extends AdminbaseController{

    protected $member_model;
    protected $listPages;

    function _initialize() {
        parent::_initialize();
        $this->member_model = D("Member");
        $this->listPages = 20;
    }

    public function index()
    {
        if (IS_AJAX && IS_POST) {
            $p = I('p',1);
            $map = $this->getSearchWhere();
            $listPages = $this->listPages;
            $count = $this->member_model->where($map)->count();
            $list = $this->member_model
                ->where($map)
                ->order('member_id asc')
                ->page($p,$listPages)
                ->select();
            $totalPage = ceil($count/$listPages);
            $this->ajaxReturn(array('list'=>$list,'totalPage'=>$totalPage,'status'=>1));
        }
        $this->display();
    }

    public function open_user()
    {
        $member = $this->checkMember();
        if ($member['status'] == 1) {
            $this->error('会员已处于正常状态');
        }
        $res = $this->member_model->where(array('member_id'=>$member['member_id']))->setField('status',1);
        if ($res !== false) {
            D('Log')->addLog("启用用户,ID：{$member['member_id']}");
            $this->success('启用用户成功');
        } else {
            $this->error('启用用户失败');
        }
    }

    public function disable_user()
    {
        $member = $this->checkMember();
        if ($member['status'] == 0) {
            $this->error('会员已处于禁用状态');
        }
        $res = $this->member_model->where(array('member_id'=>$member['member_id']))->setField('status',0);
        if ($res !== false) {
            D('Log')->addLog("禁用用户,ID：{$member['member_id']}");
            $this->success('禁用用户成功');
        } else {
            $this->error('禁用用户失败');
        }
    }

    public function checkMember()
    {
        $memberId = I('member_id');
        if (empty($memberId)) {
            $this->error('缺少参数');
        }
        $member = $this->member_model->find($memberId);
        if (empty($member)) {
            $this->error('会员不存在');
        }
        return $member;
    }

    //获取会员列表，显示在多选框中
    public function member_select(){
        $map = array();
        $field = 'wx.nickname,m.member_id';
        $keyById = true;
        if($mobile = I('mobile')){
            $map['m.mobile'] = array('like',"%{$mobile}%");;
        }
        $map['m.status'] = 1;
        $member = $this->member_model->memberList($map,$field,$keyById);
        if(IS_AJAX){
            $this->ajaxReturn(array('list'=>$member,'status'=>1));
        }
        return $member;
    }

    public function balance()
    {
        $member = $this->checkMember();
        if (!empty($member)) {
            if (IS_AJAX && IS_POST) {
                $p = I('p');
                $listPages = $this->pageNum;
                $memberId = I('member_id');
                $map = array('member_id'=>$memberId);
                $count = M('MoneyLog')->where($map)->count();
                $info = M('MoneyLog')
                    ->where($map)
                    ->order('create_time desc')
                    ->page($p,$listPages)
                    ->select();
                $totalPage = ceil($count/$listPages);
                $this->ajaxReturn(array('list'=>$info,'totalPage'=>$totalPage,'status'=>1));
            }
            $info = array(
                'member_id'=>$member['member_id'],
                'nickname'=>$member['nickname']
            );
            $this->assign('member',json_encode($info));
            $this->display();
        }
    }

    public function recharge()
    {
        $member_id = (int)I('member_id');
        $money = (int)I('money');
        try {
            $this->member_model->startTrans();
            $res = $this->member_model->changeMoney(2,$money,"后台充值,操作管理员：".session('name'),$member_id);
            if (!$res) {
                $this->error('充值失败，请重试');
            }
            $this->member_model->commit();
            D('Log')->addLog("后台充值,会员ID：{$member_id}");
            $this->success('充值成功');
        } catch (Exception $e) {
            $this->member_model->rollback();
            $this->error($e->getMessage());
        }
    }

    public function getSearchWhere()
    {
        $post = I('post.','','');
        $nickname = $post['nickname'];
        $status = $post['status'];
        $mobile = $post['mobile'];
        $map = array('1=1');
        if(!empty($nickname)){
            $map['nickname'] = array('like',"%{$nickname}%");
        }
        if($status != ''){
            $map['status'] = $status;
        }
        if(!empty($mobile)){
            $map['mobile'] = array('like',"%{$mobile}%");;
        }
        return $map;
    }

    public function exportMember()
    {
        $map = $this->getSearchWhere();
        $list = $this->member_model
            ->field('nickname,create_time,mobile,money,if(status=1,"正常","禁用") status')
            ->where($map)
            ->order('member_id asc')
            ->select();
        $xlsCell = array(
            array('nickname','昵称'),
            array('create_time','注册时间'),
            array('mobile','手机号'),
            array('money','余额'),
            array('status','状态')
        );
        exportExcel("jdx",$xlsCell,$list);
    }
}