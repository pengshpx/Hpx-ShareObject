<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-06-08
 * Time: 16:11
 */

namespace Common\Model;


class CartModel extends CommonModel
{
    protected $_validate = array(
        array('goods_id','require','请选择商品'),
        array('member_id','require','请确认购买用户')
    );

    /**
     * 获取用户购物车的列表
     * @param $memberId
     * @return mixed
     */
    public function getCartsByMemberId($memberId){
        $list = $this->field("c.id,c.goods_id,c.buy_num,g.price,g.price_other,g.title,g.pic_url,c.spec_group_id,s.name,s.price as s_price,s.stock as s_stock")
            ->alias("c")
            ->join("LEFT JOIN __GOODS__ g ON c.goods_id = g.goods_id")
            ->join("LEFT JOIN __SPEC_GROUP__ s ON c.spec_group_id = s.id")
            ->where("c.member_id = {$memberId}")->select();

        return $list;
    }

    public function getGoodsByCarts($cartIds){
        $list = $this->field("c.id,c.goods_id,c.buy_num,g.price,g.price_other,g.title,g.pic_url,c.spec_group_id,s.name,s.price as s_price,s.stock as s_stock")
            ->alias("c")
            ->join("LEFT JOIN __GOODS__ g ON c.goods_id = g.goods_id")
            ->join("LEFT JOIN __SPEC_GROUP__ s ON c.spec_group_id = s.id")
            ->where("c.id in ({$cartIds})")->select();
        foreach ($list as $key=>$item) {
            if($item['s_price']>0 && isset($item['name']) && $item['spec_group_id']>0){
                $list[$key]['price'] = $item['s_price'];
            }
        }
        return $list;
    }
}