<?php

namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use Common\Model\LogModel;

class LogController extends AdminbaseController
{
    private $LogModel;

    public function __construct()
    {
        parent::__construct();
        $this->LogModel = new LogModel();
    }

    public function index()
    {
        if (IS_AJAX && IS_POST) {
            $p = I('p');
            $listPages = $this->pageNum;
            $user_login = I('user_login','','');
            $st_time = I('st_time');
            $end_time = I('end_time');
            $con = " 1=1 ";
            if (!empty($user_login)) {
                $con .= "and u.user_login like '%".addslashes($user_login)."%' ";
            }
            if (!empty($st_time)) {
                $con .= " and log.create_time >= '{$st_time}' ";
            }
            if (!empty($end_time)) {
                $con .= " and log.create_time <= '{$end_time}' ";
            }
            $count = $this->LogModel
                ->alias('log')
                ->join('left join ehecd_users u on log.admin=u.id')
                ->where($con)
                ->count();
            $list = $this->LogModel
                ->alias('log')
                ->field('log.*,u.user_login')
                ->join('left join ehecd_users u on log.admin=u.id')
                ->where($con)
                ->order('log.create_time desc')
                ->page($p,$listPages)
                ->select();
            $totalPage = ceil($count/$listPages);
            $this->ajaxReturn(array('list'=>$list,'totalPage'=>$totalPage,'status'=>1));
        }

        $this->display();
    }
}