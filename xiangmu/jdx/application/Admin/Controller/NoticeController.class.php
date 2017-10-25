<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class NoticeController extends AdminbaseController
{
    protected $noticeModel;

    public function _initialize() {
        parent::_initialize();
        $this->noticeModel = D('notice');
    }

    //展示公告列表页面
    public function index()
    {
        if (IS_AJAX && IS_POST) {
            $p = I('p');
            $listPages = $this->pageNum;
            $count = $this->noticeModel->count();
            $list = $this->noticeModel
                ->order('sort desc,update_time desc')
                ->page($p,$listPages)
                ->select();
            $totalPage = ceil($count/$listPages);
            $this->ajaxReturn(array('list'=>$list,'totalPage'=>$totalPage,'status'=>1));
        }
        $this->display();
    }

    //展示添加公告页面
    public function add()
    {
        $this->display();
    }

    //添加公告处理
    public function add_post()
    {
        if(IS_POST){
            $data = $this->checkPost();
            $res = $this->noticeModel->add($data);
            if ($res !== false) {
                D('Log')->addLog("添加公告,ID：{$res}");
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        }
    }

    public function edit($id)
    {
        $id = (int)$id;
        $info = $this->noticeCheck($id);
        $this->assign('info',json_encode($info));
        $this->display();
    }

    public function edit_post()
    {
        if(IS_POST){
            $id = (int)I('id');
            $data = $this->checkPost();
            $data['id'] = $id;
            $res = $this->noticeModel->save($data);
            if ($res !== false) {
                D('Log')->addLog("编辑公告,ID：{$id}");
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        }
    }

    public function del($id)
    {
        $id = (int)$id;
        $this->noticeCheck($id);
        $res = $this->noticeModel->delete($id);
        if ($res !== false) {
            D('Log')->addLog("删除公告,ID：{$id}");
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function content($id)
    {
        $id = (int)$id;
        $data = $this->noticeCheck($id);
        $this->assign('notice',$data);
        $this->display();
    }

    public function checkPost()
    {
        $title = I('title');
        $content = I('content','','');
        $sort = (int)I('sort');
        if (empty($title)) {
            $this->error('请填写标题');
        }
        if (empty($content)) {
            $this->error('请填写内容');
        }
        $data = array(
            'title'=>$title,
            'content'=>$content,
            'sort'=>$sort
        );
        return $data;
    }

    public function noticeCheck($id)
    {
        $id = (int)$id;
        if (empty($id)) {
            $this->error('参数有误');
        }
        if (!$data = $this->noticeModel->find($id)) {
            $this->error('没有找到相关的公告');
        }
        return $data;
    }
}