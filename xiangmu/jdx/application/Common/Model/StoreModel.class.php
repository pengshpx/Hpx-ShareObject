<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-06-27
 * Time: 16:55
 */

namespace Common\Model;


class StoreModel extends CommonModel
{
    protected $_validate = array(
        array('name','require','店铺名称不能为空！'),
        array('name','2,30','请输入长度为2-30的店铺名称！',1,'length'),
        array('tel','require','门店电话不能为空！'),
        array('address','require','详细地址不能为空！'),
        array('city','require','所属城市不能为空！'),
        array('lng','require','经度不能为空！'),
        array('lat','require','纬度不能为空！'),
        array('open_time','require','开店时间不能为空！'),
        array('close_time','require','关门时间不能为空！'),
        array('open_time','checkTime','开店时间必须小于关门时间！',1,'callback'),
        array('pic','require','请上传店铺实景图！'),
    );

    public function checkTime($data){
        $open_time = strtotime(I('open_time'));
        $close_time = strtotime(I('close_time'));
        if($open_time<=$close_time){
            return true;
        }else{
            return false;
        }
    }
    
    public function getList($deleted=0){
        $where['deleted'] = array('elt',$deleted);
        return $this->where($where)->select();
    }

    public function getArea(){
        $data = $this->field('city')->where("deleted=0")->group('city')->select();
        foreach ($data as $key=>$item) {
            $data[$key]['city'] = str_replace('市','',$data[$key]['city']);
        }
        return $data;
    }
}