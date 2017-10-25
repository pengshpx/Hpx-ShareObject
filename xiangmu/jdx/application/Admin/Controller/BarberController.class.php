<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 2016-06-28

 * Time: 9:58

 */



namespace Admin\Controller;





use Common\Controller\AdminbaseController;

use Common\Model\BarberModel;

use Common\Model\ServicesModel;

use Common\Model\StoreModel;

use Think\Exception;



class BarberController extends AdminbaseController

{

    public function index(){

        $barberModel = new BarberModel();

        $listsCount = $barberModel->getCount();

        $Page = $this->Page($listsCount,$this->pageNum);

        $show = $Page->show("Admin");

        $list = $barberModel->query("SELECT name, year, tel, closed, barber_id as id,service_num,

                                        GROUP_CONCAT(store_name SEPARATOR ' ') as store_name

                                        FROM __PREFIX__view_barber_store where __PREFIX__view_barber_store.status=1

                                        group by barber_id LIMIT {$Page->firstRow },{$Page->listRows}");

        $this->assign('lists',$list);// 赋值数据集

        $this->assign('Page',$show);// 赋值分页输出

        $this->display();

    }



    public function add(){

        $storeModel = new StoreModel();

        $barberModel = new BarberModel();

        if(IS_POST){

            $barberModel->startTrans();

            try{

                $_POST['photos'] = empty($_POST['photos'])?"":json_encode($_POST['photos']);

                $pwd = I('pwd','');

                if(empty($pwd)){

                    E("密码不能为空");

                }

                $_POST['pwd'] = md5($pwd);

                if(!$barberModel->create()){

                    E($barberModel->getError());

                }

                $barberId = $barberModel->add();

                if(!$barberId){

                    E($barberModel->getError());

                }

                $belongStores = I('post.belongStores');

                if(!is_array($belongStores) || count($belongStores)==0){

                    E("请选择所属店铺");

                }else{

                    $storeBarberModel = D("StoreBarber");

                    $servicesModel = new ServicesModel();

                    foreach ($belongStores as $item) {

                        $relationshipId = $storeBarberModel->add(array('store_id'=>$item['store_id'],'barber_id'=>$barberId));

                        if(!$relationshipId){

                            E("添加失败：请检查是否有重复的所属店铺");

                        }

                        foreach ($item['services'] as $service) {

                            $serviceData = array('barber_store_id'=>$relationshipId,'name'=>$service['name'],'price'=>$service['price']);

                            if(!$servicesModel->create($serviceData)){

                                E($servicesModel->getError());

                            }

                            if(!$servicesModel->add($serviceData)){

                                E($servicesModel->getError());

                            }

                            unset($serviceData);

                        }

                    }

                }

                $barberModel->commit();

                D("Log")->addLog("新增理发师,ID：{$barberId}");

                $this->success("添加理发师成功");

                

            }catch (Exception $e){

                $barberModel->rollback();

                $this->error("新增失败:".$e->getMessage());

            }

        }else{

            $stores = $storeModel->getList();

            $this->assign('stores',$stores);

            $this->display('modify');

        }

    }



    public function edit($id){

        $barberModel = new BarberModel();

        if(IS_POST){

            $barberModel->startTrans();

            try{

                if(empty($_POST['pwd'])){

                    unset($_POST['pwd']);

                }else{

                    $_POST['pwd'] = md5($_POST['pwd']);

                }

                if(empty($_POST['photos'])){

                    $_POST['photos']="";

                }else{

                    $_POST['photos'] = json_encode($_POST['photos']);

                }



                if(!$barberModel->create()){

                    E($barberModel->getError());

                }
//                var_dump($barberModel->data());exit;
                $barberId = $barberModel->where("id=%d",$id)->save();

                if($barberId===false){

                    E($barberModel->getError());

                }

                $belongStores = I('post.belongStores');

                if(!is_array($belongStores) || count($belongStores)==0){

                    E("请选择所属店铺");

                }else{

                    $storeBarberModel = D("StoreBarber");

                    $servicesModel = new ServicesModel();

                    foreach ($belongStores as $item) {

                        if($item['store_barber_id']>0){

                            //更改理发师和店铺的所属关系，需检查该理发师在此店铺的订单情况

                            $order = M("ViewOrder")->where("barber_store_id=%d and is_pay=1 and deleted=0",$item['store_barber_id'])->find();

                            //对比店铺ID是否改变

                            if(!empty($order) && $order['store_id']!=$item['store_id']){

                                E("该理发师已在{$order['store_name']}有订单记录，请不要更改所属关系");

                            }

                            $res = $storeBarberModel->where("id=%d",$item['store_barber_id'])->save(array('store_id'=>$item['store_id']));

                            if($res===false){

                                E("编辑失败：请检查是否有重复的所属店铺");

                            }else{

                                $relationshipId = $item['store_barber_id'];

                            }

                        }else{
                            //判断是否有记录
                            if (($store_barber_row=$storeBarberModel->where(array('barber_id'=>$id,'store_id'=>$item['store_id']))->find())==false) {

                                $relationshipId = $storeBarberModel->add(array('store_id'=>$item['store_id'],'barber_id'=>$id));

                                if(!$relationshipId){

                                    E("编辑失败：请检查是否有重复的所属店铺");

                                }
                            }else{

                                $storeBarberModel->where(array('id'=>$store_barber_row['id']))->setField('status',1);

                                $relationshipId=$store_barber_row['id'];

                            }
                        }

                        foreach ($item['services'] as $service) {

                            $serviceData = array('barber_store_id'=>$relationshipId,'name'=>$service['name'],'price'=>$service['price']);

                            if(!$servicesModel->create($serviceData)){

                                E($servicesModel->getError());

                            }
                            if($service['id']>0){

                                if($servicesModel->where("id=%d",$service['id'])->save($serviceData)===false){

                                    E($servicesModel->getError());

                                }

                            }else{

                                if(!$servicesModel->add($serviceData)){

                                    E($servicesModel->getError());

                                }

                            }

                            unset($serviceData);

                        }

                    }

                }

                $barberModel->commit();

                D("Log")->addLog("编辑理发师,ID：{$id}");

                $this->success("编辑理发师成功");

                

            }catch (Exception $e){

                $barberModel->rollback();

                $this->error("编辑失败：".$e->getMessage());

            }





        }else{

            $barber = $barberModel->field("id,name,year,tel,description,photos")->where("closed=0 and id=%d",$id)->find();

            if(!$barber){

                $this->error("未找到该理发师");

            }

            $barber['photos'] = empty($barber['photos'])?array(): json_decode(htmlspecialchars_decode($barber['photos']),true);

//            $barber['belongStores'] = $barberModel->query("SELECT store_name,store_id,store_barber_id FROM __PREFIX__view_barber_store WHERE barber_id={$id}");//修改前
            $barber['belongStores'] = $barberModel->query("SELECT store_name,store_id,store_barber_id FROM __PREFIX__view_barber_store WHERE barber_id={$id} and status=1");//修改后
//            $barber['belongStores'] = $barberModel->query("SELECT store_name,store_id,store_barber_id FROM __PREFIX__view_barber_store WHERE barber_id={$id}");//修改后

            $servicesModel = new ServicesModel();

            foreach ($barber['belongStores'] as $key=>$item) {

                $barber['belongStores'][$key]['services'] = $servicesModel->where("barber_store_id=%d and deleted=0",$item['store_barber_id'])->select();

            }


            $storeModel = new StoreModel();

            $stores = $storeModel->getList(1);

            $this->assign('stores',$stores);

            $this->assign('barber',json_encode($barber));

            $this->display('modify');

        }

    }



    public function delete($id){

        $id = (int)$id;

        $barberModel = new BarberModel();

        $orders = M("ViewOrder")->where("barber_id=%d and status=1 and is_pay=1 and deleted=0",$id)->select();

        if($orders){

            $this->error("该理发师有未完成的预约，无法冻结");

        }

        try{

            $res = $barberModel->where(array('id'=>$id,'closed'=>0))->setField('closed',1);

            if($res){

                D("Log")->addLog("冻结理发师,ID：{$id}");

                $this->success("冻结理发师成功");

            }else{

                $this->error("冻结失败");

            }

        }catch (Exception $e){

            $this->error("冻结失败");

        }





    }



    public function recover($id){

        $id = (int)$id;

        $barberModel = new BarberModel();

        try{

            $res = $barberModel->where(array('id'=>$id,'closed'=>1))->setField('closed',0);

            if($res){

                D("Log")->addLog("恢复理发师,ID：{$id}");

                $this->success("恢复理发师成功");

            }else{

                $this->error("恢复失败");

            }

        }catch (Exception $e){

            $this->error("恢复失败");

        }



    }



    public function deleteBelong($belongId){

        $id= (int)$belongId;

        $res = M('StoreBarber')->where("id=%d",$id)->find();
        if($res){

            $num = M('StoreBarber')->where("barber_id=%d",$res['barber_id'])->count();

            if($num<2){

                $this->error("删除失败:请确保理发至少有一个所属的店铺");

            }

        }

        try{
            //            $res = M('StoreBarber')->where("id=%d",$id)->delete();//改之前 物理删除

            //先查询该理发师在该店是否有预约
            $service_id_list=M("services")->field('id')->where(array('barber_store_id'=>$id,'deleted'=>0))->select();

            //如果存在只有店铺 没有服务项的bug  就用这里删除店铺
            if(!$service_id_list){
                $res = M('StoreBarber')->where(array('id'=>$id))->setField('status',0);
                //删除排班表数据
                M("schedule")->where(array('store_barber_id'=>$id))->delete();

                D("Log")->addLog("解除了理发师和店铺关系,ID：{$id}");
                $this->success("删除成功");
                exit;
            }

            foreach ($service_id_list as $k=>$v){
                $service_id[]=$v['id'];
            }

            $no_service_count=M("Order")->where(array('service_id'=>array('in',$service_id),'status'=>array('eq',1),'deleted'=>0,'is_pay'=>1))->count();

            if($no_service_count){
                $this->error("删除失败：理发师在该店有未完成的订单，无法解除关系");
            }

            $res = M('StoreBarber')->where(array('id'=>$id))->setField('status',0); //改之后 逻辑删除

            //删除排班表数据
            M("schedule")->where(array('store_barber_id'=>$id))->delete();
            //删除服务类型
            M("Services")->where(array('barber_store_id'=>$id))->setField('deleted',1);

            if($res!==false){

                D("Log")->addLog("解除了理发师和店铺关系,ID：{$id}");

                $this->success("删除成功");

            }else{

                $this->error("删除失败");

            }

        }catch (Exception $e){
            $this->error($e->getMessage());
            //$this->error("删除失败：理发师已在该店有业绩订单，无法解除关系");

        }



    }



    public function deleteService($serviceId){

        $id= (int)$serviceId;

        try{

            $res = M('Services')->where("id=%d and deleted=0",$id)->setField("deleted",1);

            if($res){

                D("Log")->addLog("删除服务类型,ID：{$id}");

                $this->success("删除成功");

            }else{

                $this->error("删除失败");

            }

        }catch (Exception $e){

            $this->error("删除失败");

        }

        

    }

}