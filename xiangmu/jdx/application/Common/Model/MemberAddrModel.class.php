<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 *      会员地址
 * Date: 2016/6/6 0006
 * Time: 上午 11:44
 */

namespace Common\Model;

class MemberAddrModel extends CommonModel
{


    /**
     * @param $map
     * @param bool $is_default
     * @return mixed
     */
    public function getAddress($map, $is_default=true)
    {
        $map['deleted'] = 0;
        if($is_default){
            $map['is_default'] = 1;
            $address = $this->where($map)->find();
        }else{
            $address = $this->where($map)->order("is_default desc")->select();
        }
        return $address;
    }
    
    
}