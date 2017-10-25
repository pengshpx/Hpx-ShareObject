<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-06-28
 * Time: 14:46
 */

namespace Common\Model;


class ServicesModel extends CommonModel
{
    protected $_validate = array(
        array('barber_store_id','require','关联ID不能为空！'),
        array('name','require','服务名称不能为空！'),
        array('price','require','价格不能为空！')
    );
}