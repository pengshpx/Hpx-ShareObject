<?php

namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MemberCardController extends AdminbaseController
{
    public function index()
    {
        if (IS_AJAX && IS_POST) {
            $p = I('p');
            $listPages = $this->pageNum;
            $McardModel = M('member_card');
            $count = $McardModel->count();
            $list = $McardModel->page($p,$listPages)->select();
            $totalPage = ceil($count/$listPages);
            $this->ajaxReturn(array('list'=>$list,'totalPage'=>$totalPage,'status'=>1));
        }
        $this->display();
    }

    public function add()
    {
        $this->display();
    }

    public function add_post()
    {
        if (IS_POST) {
            $data = $this->checkPost();
            $res = M('member_card')->add($data);
            if ($res !== false) {
                D('Log')->addLog("添加会员卡,ID：{$res}");
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        }
    }

    public function edit($id)
    {
        $info = $this->memberCardCheck($id);
        $this->assign('info',json_encode($info));
        $this->display();
    }

    public function edit_post()
    {
        if (IS_POST) {
            $id = (int)I('id');
            $data = $this->checkPost();
            $data['id'] = $id;
            $res = M('member_card')->save($data);
            if ($res !== false) {
                D('Log')->addLog("编辑会员卡,ID：{$id}");
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        }
    }

    public function card_del($id)
    {
        $id = (int)$id;
        $this->memberCardCheck($id);
        $res = M('member_card')->delete($id);

        if ($res !== false) {
            D('Log')->addLog("删除会员卡,ID：{$id}");
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function checkPost()
    {
        $price = (int)I('price');
        $donate = (int)I('donate');
        if (!isset($price)) {
            $this->error('会员卡金额不能为空');
        }
        if ($price <= 0) {
            $this->error('会员卡金额必须大于0');
        }
        if (!isset($donate)) {
            $this->error('赠送金额不能为空');
        }
        if ($donate < 0) {
            $this->error('赠送金额必须大于等于0');
        }
        $data = array(
            'price'=>$price,
            'donate'=>$donate
        );
        return $data;
    }

    public function memberCardCheck($id)
    {
        $id = (int)$id;
        if (empty($id)) {
            $this->error('参数有误');
        }
        if (!$data = M('member_card')->find($id)) {
            $this->error('没有找到相关的会员卡');
        }
        return $data;
    }

    //会员卡详细描述展示
    public function description()
    {
        $option = D("Options")->where("option_name='member_card_description'")->find();
        $this->assign('option_id',$option['option_id']);
        $this->assign('card',json_decode($option['option_value'],true));
        $this->display();
    }

    //会员卡详细描述提交
    function description_post()
    {
        if(IS_POST){
            if(!empty($_POST['option_id'])){
                $data['option_id']=intval($_POST['option_id']);
            }
            $data['option_name']="member_card_description";
            $data['option_value'] = json_encode($_POST['card']);
            $res = D("Options")->save($data);
            if($res !== false){
                D('Log')->addLog('编辑会员卡详细描述');
                $this->success("编辑成功！");
            }else{
                $this->error("编辑失败！");
            }
        }
    }
}