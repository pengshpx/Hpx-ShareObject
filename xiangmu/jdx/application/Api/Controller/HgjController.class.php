<?php

namespace Api\Controller;

class HgjController extends ApiCommonController
{
    private $noticeModel;
    private $barberModel;
    private $viewOrderModer;

    public function __construct()
    {
        parent::__construct();
        $this->noticeModel = D('Notice');
        $this->barberModel = D('Barber');
        $this->viewOrderModer = D('view_order');
    }

    /*公告列表*/
    public function noticeList()
    {
        $p = (int)$this->data['p'];
        $listPages = (int)$this->data['pageSize'];
        $listPages = empty($listPages) ? 10 : $listPages;
        $count = $this->noticeModel->count();
        $data = $this->noticeModel->alias("n")
            ->field("n.id,n.title,n.content,DATE_FORMAT(n.update_time,'%Y-%m-%d') time,(select count(m1.notice_id) from ehecd_notice_log as m1 where n.id=m1.notice_id and m1.barber_id={$this->data['barber_id']}) as isread ")
            ->order('sort desc,update_time desc')
            ->page($p,$listPages)
            ->select();
        if ($data) {
            $totalPages = ceil($count/$listPages);
            $this->success($data,$totalPages);
        } else {
            $this->success(array());
        }
    }

    /*公告详细信息*/
    public function noticeInfo()
    {
        if (empty($this->data['notice_id'])) {
            $this->error(2011,$this->errorMsg[2]);
        }
        if (!$data = $this->noticeModel->find($this->data['notice_id'])) {
            $this->error(2012,"公告不存在或已删除");
        }
        $dataArr = array(
            'notice_id' => $this->data['notice_id'],
            'barber_id' => $this->data['barber_id'],
        );
        if (!M('notice_log')->where($dataArr)->find()) {
            $dataArr['is_read'] = 1;
            $res = M('notice_log')->add($dataArr);
            if ($res == false) {
                $this->error(2013,$this->errorMsg[3]);
            }
        }
        $data['content'] = "<html><head><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
<style>body,html{margin: 0;padding: 0;background-color: #222222;color: #FFF}img{max-width: 100%}</style>
</head><body>{$data['content']}</body></html>";
        $this->success($data);
    }

    /*个人简历*/
    public function resume()
    {
        $year = (int)$this->data['year'];
        $description = $this->data['description'];
        if (!isset($year)) {
            $this->error(2021,"请填写从业年数");
        }
        if (empty($description)) {
            $this->error(2022,"请填写个人简介");
        }
        $data = array(
            'id' => $this->data['barber_id'],
            'year' => $year,
            'description' => $description
        );
        $res = $this->barberModel->save($data);
        if ($res !== false) {
            $this->success("保存成功");
        } else {
            $this->error(2023,"保存失败");
        }
    }

    /*我的预约列表*/
    public function orderList()
    {
        $status = (int)$this->data['status'];
        $p = (int)$this->data['p'];
        $listPages = (int)$this->data['pageSize'];
        $map = array(
            'is_pay'=>1,
            'deleted'=>0,
            'barber_id'=>$this->data['barber_id']
        );
        $tabNum = array(
            1=>0,
            2=>0,
            3=>0,
            100=>0,
        );
        $countArr = $this->viewOrderModer
            ->field('status,COUNT(status) count')
            ->where($map)
            ->group('status')
            ->select();
        foreach ($countArr as $k=>$v) {
            if ((int)$v['status'] == 0 || (int)$v['status'] == 3) {
                $tabNum[3] += (int)$v['count'];
            } else {
                $tabNum[(int)$v['status']] = (int)$v['count'];
            }
            $tabNum[100] += (int)$v['count'];
        }
        if ($status != 100) {
            $map['status'] = $status;
        }
        if ($status == 3) {
            $map['status'] = array('in','0,3');
        }
        $count = $this->viewOrderModer->where($map)->count();
        if ($listPages) {
            $data = $this->viewOrderModer
                ->field("order_id,status,service_time,service_name,ifnull(service_start_time,'') as service_start_time,ifnull(service_end_time,'') as service_end_time,total_price,create_time")
                ->where($map)
                ->order('service_time desc')
                ->page($p,$listPages)
                ->select();
        }else{
            $data = $this->viewOrderModer
                ->field("order_id,status,service_time,service_name,ifnull(service_start_time,'') as service_start_time,ifnull(service_end_time,'') as service_end_time,total_price,create_time")
                ->where($map)
                ->order('service_time desc')
                ->select();
        }
        $totalPages = ceil($count/$listPages);
        $list = array(
            'data'=>$data,
            'tabNum'=>$tabNum
        );
        $this->success($list,$totalPages);
    }

    /*预约订单详情*/
    public function orderInfo()
    {
        if (empty($this->data['order_id'])) {
            $this->error(2041,$this->errorMsg[2]);
        }
        $map = array(
            'barber_id'=>$this->data['barber_id'],
            'order_id'=>$this->data['order_id'],
            'deleted'=>0
        );
        $data = $this->viewOrderModer
            ->field("order_id,status,service_time,service_name,refund_price,ifnull(service_start_time,'') as service_start_time,ifnull(service_end_time,'') as service_end_time,total_price,pay_price,create_time,nickname,tel,userheadimgurl")
            ->where($map)
            ->find();
        if ($data) {
            $this->success($data);
        } else {
            $this->error(2042,"订单不存在或已删除");
        }
    }

}