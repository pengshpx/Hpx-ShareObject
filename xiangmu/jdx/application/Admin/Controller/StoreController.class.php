<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 2016-06-27

 * Time: 15:18

 */



namespace Admin\Controller;





use Common\Controller\AdminbaseController;

use Common\Model\StoreModel;

use Think\Exception;



class StoreController extends AdminbaseController

{

    private $model;



    public function __construct()

    {

        parent::__construct();

        $this->model = new StoreModel();

    }



    public function index(){

        $list = $this->model->getList(1);

        $this->assign('stores',$list);

        $this->display();

    }

    

    public function add(){

        if(IS_POST){

            $_POST['pic_list'] = json_encode(I('photos_url'));

            if($this->model->create()){

                $res = $this->model->add();

                if($res){

                    D("Log")->addLog("新增店铺,ID：{$res}");

                    $this->success("添加成功",U("Store/index"));

                    exit();

                }else{

                    $this->error("新增失败：".$this->model->getError());

                }

            }else{

                $this->error($this->model->getError());

            }

        }else{

            $this->display('modify');

        }

    }



    public function edit($id){

        if(IS_POST){

            $_POST['pic_list'] = json_encode(I('photos_url'));

            if($this->model->create()){

                $res = $this->model->save();

                if($res>=0){

                    D("Log")->addLog("修改店铺,ID：{$id}");

                    $this->success("修改成功",U("Store/index"));

                    exit();

                }else{

                    $this->error("修改失败：".$this->model->getError());

                }

            }else{

                $this->error($this->model->getError());

            }

        }else{

            $id = (int)$id;

            $store = $this->model->find($id);

            if($store){

                if($store['pic_list']){

                    $store['pic_list'] = json_decode(htmlspecialchars_decode($store['pic_list']),true);

                }

                $this->assign('store',$store);

            }else{

                $this->error("未找到该店铺");

            }

        }

        $this->display('modify');

    }



    public function delete($id){

        $id = (int)$id;

        try{

           /* $barbers = M("ViewBarberStore")->where("store_id=%d and closed=0",$id)->group("barber_id")->select();

            if($barbers){

                E("该店铺下有未冻结的理发师,无法禁用");

            }*/

            $store = $this->model->where("id=%d and deleted=0",$id)->setField('deleted',1);

            if($store){

                D("Log")->addLog("禁用店铺,ID：{$id}");

                $this->success("禁用店铺成功");

            }else{

                $this->error("未找到该店铺");

            }

        }catch (Exception $e){

            $this->error("禁用失败:".$e->getMessage());

        }

    }



    public function enable($id){

        $id = (int)$id;

        try{

            $store = $this->model->where("id=%d and deleted=1",$id)->setField('deleted',0);

            if($store){

                D("Log")->addLog("启用店铺,ID：{$id}");

                $this->success("启用店铺成功");

            }else{

                $this->error("未找到该店铺或店铺已启用");

            }

        }catch (Exception $e){

            $this->error("启用失败:".$e->getMessage());

        }

    }



    public function schedule(){

        if(IS_POST){

            $store_id = I('store_id',0);

            $date = I('month','');

            if($store_id==0 || empty($date)){

                $this->error();

            }

            $month = date("m", strtotime(date($date."-1")));

            $start_date = date($date."-01", strtotime(date($date."-01")));

            $end_date = date('Y-m-d', strtotime("$start_date +1 month -1 day"));

            $model = M("ViewSchedule");
            $model2 = M("ViewBarberStore");
//            $barbers = $model2->field("barber_id,name as barber_name,store_barber_id")->where("store_id=%d and closed=0",$store_id)->group("barber_id")->order("barber_id")->select();//改之前
            $barbers = $model2->field("barber_id,name as barber_name,store_barber_id")->where("store_id=%d and closed=0 and status=1",$store_id)->group("barber_id")->order("barber_id")->select();//改之后


            $str='';

            foreach ($barbers as $key=>$barber) {

                $str .= "max(case barber_id when {$barber['barber_id']}  Then is_work  else 0 end) as 'barber_{$barber['barber_id']}'";

                if($key==count($barbers)-1){



                }else{

                    $str.=",";

                }

            }

            if($str!=''){
                $str=','.$str;
            }

            $list = $model->query("select work_date{$str}  from ehecd_view_schedule where store_id={$store_id} and work_date BETWEEN '{$start_date}' and '{$end_date}' group by work_date order by work_date");

            if($list){

                array_unshift($barbers,array('month'=>(int)$month));

            }

            $this->success(array('lists'=>$list,'titles'=>$barbers));

        }else{

            $this->assign('stores',$this->model->getList());

            $this->display();

        }

    }



    public function scheduleEdit(){

        $post = I('post.');

        $model = M('Schedule');

        $viewModel = M("ViewSchedule");

        $model->startTrans();

        try{

            foreach ($post['lists'] as $dateItem) {

                $i=1;

                foreach ($dateItem as $key=>$item) {
                    if($key=='work_date'){

                        continue;

                    }

                    $temp['work_date'] = $dateItem['work_date'];

                    $temp['store_barber_id'] = $post['titles'][$i]['store_barber_id'];

                    $temp['is_work'] = $item;



                    $res = $viewModel->where("work_date='%s' and store_barber_id=%d",$temp['work_date'],$temp['store_barber_id'])->find();

                    if ($res){

                      /*  if( $res['is_work']!=$temp['is_work'] && strtotime($temp['work_date'])<=strtotime(date("Y-m-d 23:59:59")) ){

                            E("不能编辑{$temp['work_date']}更早的排班表");

                        }*/

                        if($temp['is_work']==1 && $res['is_work']!=$temp['is_work']){

                            $res2 = $viewModel->where("work_date='%s' and barber_id=%d and is_work=1",$temp['work_date'],$post['titles'][$i]['barber_id'])->find();

                            if($res2){

                                E("理发师{$res2['barber_name']}，在{$res2['work_date']}于{$res2['store_name']}已有排班，请检查");

                            }

                        }

                        $model->where("id=%d",$res['id'])->setField("is_work",$temp['is_work']==1?1:0);

                    }else{

                        /*if( strtotime($temp['work_date'])<=strtotime(date("Y-m-d 23:59:59")) ){

                            E("不能新增更早的排班表");

                        }*/

                        //检查一下是否在其他店有上班的情况

                        $res2 = $viewModel->where("work_date='%s' and barber_id=%d and is_work=1",$temp['work_date'],$post['titles'][$i]['barber_id'])->find();

                        if($res2 && $temp['is_work']==1){

                            E("理发师:{$res2['barber_name']}，在{$res2['work_date']}于{$res2['store_name']}已有排班，请检查");

                        }else{

                            $model->add($temp);

                        }

                    }

                    $i++;

                }

            }
            $model->commit();

            D("Log")->addLog("更新排班表");

            $this->success("保存成功");

        }catch (Exception $e){

            $model->rollback();

            $this->error($e->getMessage());

        }



    }



    public function scheduleCreate(){

        if(IS_POST){

            $store_id = I('store_id',0);

            $date = I('month','');

            if($store_id==0 || empty($date)){

                $this->error();

            }

            //$model = M("ViewSchedule");

            $model2 = M("ViewBarberStore");

            $barbers = $model2->field("barber_id,name as barber_name,store_barber_id")->where(array("store_id=%d"=>$store_id,'closed'=>0))->group("barber_id")->order("barber_id")->select();


            //$year = $month = date("y", strtotime(date($date."-01")));

            $month = date("m", strtotime(date($date."-01")));

            //$start_date = date($date."-01", strtotime(date($date."-01")));

            //$end_date = date('Y-m-d', strtotime("$start_date +1 month -1 day"));

            $length = date("t",strtotime($date));

            $list = array();

            for($i=1;$i<=$length;$i++){

                $data['work_date'] = date("Y-m-d",strtotime($date."-".$i));

                foreach ($barbers as $barber) {

                    $data['barber_'.$barber['barber_id']] = 1;

                }

                $list[] = $data;

                unset($data);

            }

            array_unshift($barbers,array('month'=>(int)$month));

            $this->success(array('lists'=>$list,'titles'=>$barbers));

        }

    }



    public function exportSchedule(){

        $store_id = I('store_id',0);

        $date = I('month','');

        if($store_id==0 || empty($date)){

            $this->error();

        }

        $month = date("m", strtotime(date($date."-1")));

        $start_date = date($date."-01", strtotime(date($date."-01")));

        $end_date = date('Y-m-d', strtotime("$start_date +1 month -1 day"));

        $model = M("ViewSchedule");

        $model2 = M("ViewBarberStore");

        $barbers = $model2->field("barber_id,name as barber_name,store_barber_id")->where("store_id=%d",$store_id)->group("barber_id")->order("barber_id")->select();

        $str='';

        foreach ($barbers as $key=>$barber) {

            $str .= "max(case barber_id when {$barber['barber_id']}  Then if(is_work,'√','×')  else '×' end) as 'barber_{$barber['barber_id']}'";

            if($key==count($barbers)-1){



            }else{

                $str.=",";

            }

        }

        $list = $model->query("select work_date,{$str}  from ehecd_view_schedule where store_id={$store_id} and work_date BETWEEN '{$start_date}' and '{$end_date}' group by work_date order by work_date");

        if($list){

            foreach ($list as $key =>$item) {

                $list[$key]['work_date'] = $list[$key]['work_date']."(".dateFormatWeekDay($list[$key]['work_date']).")";

            }

            $xlsCell = array();

            $xlsCell[] = array('work_date',(int)$month.'月');

            foreach ($barbers as $barber) {

                $xlsCell[] = array('barber_'.$barber['barber_id'],$barber['barber_name']);

            }

            exportExcel('排班表',$xlsCell,$list);

        }





    }

}