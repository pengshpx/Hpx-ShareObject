<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-07-05
 * Time: 10:43
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\OrderModel;
use Common\Model\StoreModel;

class StatisticController extends AdminbaseController
{
    private $orderViewModel;
    private $orderModel;
    public function __construct()
    {
        parent::__construct();
        $this->orderViewModel = M("ViewOrder");
        $this->orderModel = new OrderModel();
    }

    public function index(){
        if(IS_AJAX){
            $amountSql = "select ";
            $sumPriceSql = " ";
            foreach ($this->orderModel->orderStatus as $key => $value) {
                $amountSql.="IFNULL(sum(case status when $key then 1 else 0 end),0)'amount_$key'";
                $sumPriceSql.="IFNULL(sum(case status when $key then total_price else 0 end),0.00) 'sumprice_$key'";
                $amountSql.=",";
                if($key!=count($this->orderModel->orderStatus)-1){

                    $sumPriceSql.=",";
                }
            }
            $where = " from __PREFIX__view_order where deleted=0 and is_pay=1 ";
            $storeId = I("store",0);
            $barber = I("barber",'');
            $start_time = I("start_time",'');
            $end_time = I("end_time",'');
            if(!empty($storeId)){
                $where.="and store_id=$storeId ";
            }
            if(!empty($barber)){
                $where.="and barber_name like '%$barber%' ";
            }
            if(!empty($start_time)){
                $where .= " and service_time >= '$start_time'";
            }
            if(!empty($end_time)){
                $where .= " and service_time <= '$end_time'";
            }
            $sql =$amountSql.$sumPriceSql.$where;
            $data = M()->query($sql);
            $this->success($data[0]);
        }else if(IS_GET){
            $storeModel = new StoreModel();
            $this->assign('stores',$storeModel->getList());
            $this->display();
        }

    }
}