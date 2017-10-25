<?php



namespace Common\Model;





class CouponModel extends CommonModel

{

    /**

     * 发放优惠码

     * @param $mid 会员ID

     * @param int $is_invoke 是否已激活

     * @param int $fromCid 关联的赠送来源优惠码id
     * 
     * @param float $money 优惠券金额

     * @return mixed

     */

    public function send($mid, $is_invoke=1, $fromCid=0, $money=20)

    {

        $data = array(

            'member_id'=>(int)$mid,

            'from_cid'=>$fromCid,

            'code'=>$this->randomCode(),

            'money'=>$money,

            'is_invoke'=>$is_invoke,

            'get_time'=>date('Y-m-d H:i:s')

        );

        return $this->add($data);

    }



    /**

     * 激活优惠码

     * @param $cid

     * @return bool

     */

    public function invoke($cid)

    {

        $data = array(

            'id'=>$cid,

            'is_invoke'=>1,

            'get_time'=>date('Y-m-d H:i:s')

        );

        return $this->save($data);

    }



    /**

     * 生成优惠码

     * @return string

     */

    public function randomCode()

    {

        $date = (int) date("ymd")-160714;

        $randomCode = str_pad($date,4,"0").mt_rand(100000,999999);

        //$randomCode = strtoupper(sp_random_string(5).$mid.substr(md5(substr(microtime(),2,6)),-5));

        if ($this->where(array('code'=>$randomCode))->find()) {

            $this->randomCode();

        }

        return $randomCode;

    }



    public function useCoupon($orderId){

        $this->where("order_id=%d",$orderId)->save(array('is_used'=>1,'use_time'=>date("Y-m-d H:i:s")));

    }



    public function invokeByOrder($orderId){

        $coupons = $this->where("order_id=%d",$orderId)->select();

        if($coupons){

            //激活关联的优惠码

            foreach ($coupons as $coupon) {

                $this->where("from_cid=%d",$coupon['id'])->save(array('is_invoke'=>1,'get_time'=>date('Y-m-d H:i:s')));

            }

        }

    }

}