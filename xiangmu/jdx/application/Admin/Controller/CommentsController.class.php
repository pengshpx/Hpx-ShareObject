<?php

namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class CommentsController extends AdminbaseController
{
    protected $Comments_model;

    public function __construct()
    {
        parent::__construct();
        $this->Comments_model = D('Comments');
    }

    //展示评论列表页面
    public function index()
    {

        if (IS_AJAX && IS_POST) {
            $p = I('p');
            $listPages = $this->pageNum;
            $post = I('post.','','');
            if($post['orderId'] != ''){
                $map['o.order_id'] = $post['orderId'];
            }
            if(!empty($post['barberName'])){
                $map['b.name'] = array('like',"%".addslashes($post['barberName'])."%");
            }
            if(!empty($post['memberName'])){
                $map['m.nickname'] = array('like',"%".addslashes($post['memberName'])."%");
            }
            if(!empty($post['grade'])){
                $map['cmt.grade'] = $post['grade'];
            }
            $map['cmt.deleted'] = 0;
            $count = $this->Comments_model
                ->alias('cmt')
                ->join('left join ehecd_order o on cmt.order_id=o.order_id')
                ->join('left join ehecd_barber b on cmt.barber_id=b.id')
                ->join('left join ehecd_member m on cmt.member_id=m.member_id')
                ->where($map)
                ->count();
            $list = $this->Comments_model
                ->alias('cmt')
                ->field('cmt.*,m.nickname,b.name')
                ->join('left join ehecd_order o on cmt.order_id=o.order_id')
                ->join('left join ehecd_barber b on cmt.barber_id=b.id')
                ->join('left join ehecd_member m on cmt.member_id=m.member_id')
                ->where($map)
                ->order('cmt.create_time desc')
                ->page($p,$listPages)
                ->select();
            $totalPage = ceil($count/$listPages);
            $this->ajaxReturn(array('list'=>$list,'totalPage'=>$totalPage,'status'=>1));
        }
        $this->display();
    }

    //评论详情页面展示
    public function detail()
    {
        if (IS_AJAX && IS_POST) {
            $this->commentsCheck();
            $info = $this->Comments_model
                ->alias('cmt')
                ->field('cmt.*,gs.title')
                ->join('left join ehecd_goods gs on cmt.goods_id=gs.goods_id')
                ->where(array('cmt.c_id'=>I('get.c_id')))
                ->order('cmt.create_time desc')
                ->find();
            $wxinfo = M('wxinfo')->field('nickname')->where(array('member_id'=>$info['member_id']))->find();
            $info['nickname'] = $wxinfo['nickname'];
            $this->ajaxReturn(array('info'=>$info,'status'=>1));
        }
        $this->display();
    }

    //评论回复处理
    public function reply_post()
    {
        if(IS_AJAX && IS_POST){
            $id = (int)I('id');
            $reply = I('reply');
            if (empty($reply)) {
                $this->error('回复内容不能为空');
            }
            $return = $this->commentsCheck();
            if (!is_null($return['reply'])) {
                $this->error('评论已回复，请刷新');
            }
            $data = array(
                'reply'=>$reply,
                'reply_time'=>date('Y-m-d H:i:s'),
                'id'=>$id
            );
            $res = $this->Comments_model->save($data);
            if($res !== false){
                D('Log')->addLog("回复评论成功,ID：{$res}");
                $this->success('回复成功');
            }else{
                $this->error('回复失败');
            }
        }
    }

    //评论删除
    public function del()
    {
        $return = $this->commentsCheck();
        if ($return['deleted'] == 1) {
            $this->error('评论已被删除，请刷新');
        }
        $data = array(
            'deleted'=>1,
            'id'=>(int)I('id')
        );
        $res = $this->Comments_model->save($data);
        if($res !== false){
            D('Log')->addLog("删除评论成功,ID：{$res}");
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    public function commentsCheck()
    {
        if(!$id = (int)I('id')){
            $this->error('请选择一个评论');
        }
        if(!$data = $this->Comments_model->find($id)){
            $this->error('没有找到这个评论');
        }
        return $data;
    }
}