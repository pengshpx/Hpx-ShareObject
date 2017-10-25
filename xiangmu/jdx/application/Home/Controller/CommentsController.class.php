<?php

namespace Home\Controller;

use Common\Model\CommentsModel;
use Think\Exception;

class CommentsController extends CommonController
{
    protected $CommentsModel;

    public function __construct() {
        parent::__construct();
        $this->CommentsModel = new CommentsModel();
    }

    public function index()
    {
        $id = (int)I('orderId');
        $map = array(
            'order_id'=>$id
        );
        $order = M('view_order')->where($map)->find();
        if (empty($order) || $order['deleted'] == 1) {
            $this->error("订单不存在，请刷新");
        }
        if ($order['is_comment'] == 1) {
            $this->error("订单已评论，请刷新");
        }
        if (IS_AJAX && IS_POST) {
            $map = array(
                'order_id' => $id,
                'deleted' => 0
            );
            $info = M('view_order')
                ->field('order_id,barber_id,barber_name,headimgurl,barber_tel,service_name,service_time')
                ->where($map)
                ->find();
            $this->success(array('info'=>$info));
        }
        $info = array(
            'order_id'=>$id,
            'barber_id'=>$order['barber_id'],
            'barber_name'=>$order['barber_name'],
            'headimgurl'=>$order['headimgurl'],
            'service_name'=>$order['service_name'],
            'service_time'=>date('Y-m-d H:i',strtotime($order['service_time'])),
            'barber_tel'=>$order['barber_tel']
        );
        $this->assign('order',json_encode($info));
        $this->display();
    }

    public function lookComment()
    {
        $id = (int)I('orderId');
        $order = M('order')->find($id);
        if (empty($order) || $order['deleted'] == 1) {
            $this->error("订单不存在，请刷新");
        }
        $map = array(
            'order_id' => $id
        );
        $info = M('view_order')
            ->field('order_id,barber_id,barber_name,headimgurl,service_name,
            DATE_FORMAT(service_time,"%Y-%m-%d %H:%i") service_time')
            ->where($map)
            ->find();
        $comment = $this->CommentsModel
            ->field('content,grade')
            ->where(array('order_id' => $id))
            ->find();
        $this->assign('info',$info);
        $this->assign('comment',$comment);
        $this->display();
    }

    public function comment()
    {
        if (IS_AJAX && IS_POST) {
            $data = $this->commentCheck();
            try {
                $this->CommentsModel->startTrans();
                $res = $this->CommentsModel->add($data);
                if ($res == false) {
                    E("评论失败请重试");
                }
                $res = M('order')->where(array('order_id'=>$data['order_id']))->setField('is_comment',1);
                if ($res == false) {
                    E("评论失败请重试");
                }
                $stars = M('comments')
                    ->field('ifnull(SUM(grade),0)/ifnull(COUNT(id),0) stars')
                    ->where(array('barber_id'=>$data['barber_id']))
                    ->find();
                $res = M('barber')->where(array('id'=>$data['barber_id']))->setField('stars',ceil($stars['stars']));
                if ($res === false) {
                    E("评论失败请重试");
                }
                $this->CommentsModel->commit();
                $this->success("评论成功");
            } catch (Exception $e) {
                $this->CommentsModel->rollback();
                $this->error($e->getMessage());
            }
        }
    }

    public function commentCheck()
    {
        $order_id = (int)I('orderId');
        $barber_id = (int)I('barberId');
        $member_id = (int)$this->member['member_id'];
        $grade = (int)I('grade');
        $content = str_replace(array("\r\n","\r","\n"," "),"",I('content'));
        if (empty($order_id) || empty($barber_id) || empty($member_id)) {
            $this->error("数据错误，请重试");
        }
        $order = M('order')->find($order_id);
        if (empty($order) || $order['deleted'] == 1) {
            $this->error("订单不存在，请刷新");
        }
        if ($order['status'] != 2) {
            $this->error("订单状态已更改，请刷新");
        }
        if ($order['is_comment'] == 1) {
            $this->error("订单已评论，请刷新");
        }
        if (empty($grade)) {
            $this->error("请评星");
        }
        if ($grade < 1 || $grade > 5) {
            $this->error("评星等级为1-5星");
        }
        if (empty($content)) {
            $this->error("评价内容不能为空");
        }
        if (strlen($content) > 200) {
            $this->error("评价内容最多为200个字符");
        }
        $data = array(
            'order_id' => $order_id,
            'barber_id' => $barber_id,
            'member_id' => $member_id,
            'grade' => $grade,
            'content' => $content
        );
        return $data;
    }
}