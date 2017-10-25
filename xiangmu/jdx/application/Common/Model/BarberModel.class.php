<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 2016-06-28

 * Time: 9:59

 */



namespace Common\Model;





class BarberModel extends CommonModel

{

    protected $_validate = array(

        array('name', 'require', '店铺名称不能为空！'),

        array('name', '2,10', '请输入长度为2-10的店铺名称！', 1, 'length'),

        array('tel', '11', '请输入11位的手机号！', 1, 'length'),

        array('tel', '', '该手机号已存在！', 0, 'unique'),

        array('year', '0,99', '从业年数为0-99范围！', 1, 'between')

    );



    protected $week = array(

        "周日", "周一", "周二", "周三", "周四", "周五", "周六"

    );



    public function getList($where = array())

    {

        $where['closed'] = 0;

        return $this->where($where)->select();

    }



    public function getCount($where = array())

    {

        //$where['closed'] = 0;

        return $this->where($where)->count();

    }



    public function getBarberById($id)

    {

        return $this->where("id=$id")->find();

    }



    public function login($tel, $pwd)

    {

        $barber = $this->field("id,name,tel,headimgurl,service_num,closed")->where("tel='%s' and pwd='%s' ", $tel, md5($pwd))->find();

        if ($barber) {

//            $barber['photos'] = empty($barber['photos'])?array(): json_decode(htmlspecialchars_decode($barber['photos']),true);

            return $barber;

        } else {

            return false;

        }

    }



    public function getBarberInfoById($id)

    {

        $data = $this->query("select barber_id,name as barber_name,headimgurl,year,description,stars,tel,service_num,store_name,address,city from ehecd_view_barber_store where barber_id=%d", $id);

        if ($data) {

            $barber = array(

                'barber_id' => $data[0]['barber_id'],

                'barber_name' => $data[0]['barber_name'],

                'stars' => $data[0]['stars'],

                'tel' => $data[0]['tel'],

                'service_num' => $data[0]['service_num'],

                'headimgurl' => $data[0]['headimgurl'],

                'year' => $data[0]['year'],

                'description' => $data[0]['description']

            );

            foreach ($data as $item) {

                $barber['stores'][] = array(

                    'store_name' => $item['store_name'],

                    'address' => $item['address'],

                    'city' => $item['city'],

                );

            }

            return $barber;

        } else {

            return false;

        }

    }



    public function getNoticeNoRead($id)

    {

        $data = $this->query("SELECT IFNULL((select count(*) from ehecd_notice),0)-IFNULL((select count(*) from ehecd_notice_log where barber_id=%d),0) as noread", $id);

        return $data[0]['noread'];

    }



    public function getMyUnServicesList($id)

    {

        $data = $this->query("select order_id,userheadimgurl,nickname,tel,service_name,total_price,service_time,service_start_time,create_time,pay_price,status,service_lock from __PREFIX__view_order where barber_id=$id and is_pay=1 and status=1 order by service_time");

        if ($data) {

            $orderModel = new OrderModel();

            foreach ($data as $key => $item) {

                $data[$key]['status'] = $orderModel->orderStatus[$item['status']];

            }

        }

        return $data;

    }



    public function statisticYesterday($id)

    {

        $data = $this->query("select ifnull(sum(total_price),0) as totalprice,count(*) as amount from __PREFIX__view_order where to_days(now())-to_days(service_time) = 1 and status=2 and barber_id=$id");

        return $data[0];

    }



    public function statisticToday($id)

    {

        $data = $this->query("select ifnull(sum(total_price),0) as totalprice,count(*) as amount from __PREFIX__view_order where to_days(now())=to_days(service_time) and status=2 and barber_id=$id");

        return $data[0];

    }



    public function statisticLastMonth($id)

    {

        $data = $this->query("select ifnull(sum(total_price),0) as totalprice,count(*) as amount from __PREFIX__view_order where  period_diff(date_format(now(),'%Y%m') , date_format(`service_time`,'%Y%m')) =1 and status=2 and barber_id=$id");

        return $data[0];

    }



    public function statisticThisMonth($id)

    {

        $data = $this->query("select ifnull(sum(total_price),0) as totalprice,count(*) as amount from __PREFIX__view_order where  date_format(`service_time`, '%Y%m') = date_format(curdate() , '%Y%m') and status=2 and barber_id=$id");

        return $data[0];

    }



    public function myNextWeekTime($barberId, $startTime = 10, $endTime = 21)

    {

        $today = date("Y-m-d $startTime:00:00");

        $endDay = date("Y-m-d $endTime:00:00", strtotime("+6 days"));

        $orders = $this->query("select date_format(`service_time`, '%Y-%m-%d %H:00') service_time,nickname,order_id,status from ehecd_view_order where is_pay=1 and deleted=0 and service_time BETWEEN '$today' and '$endDay' and status>=1 and status<=2 and barber_id=$barberId");



        $orderArr = array();

        foreach ($orders as $item) {

            $orderArr[$item['service_time']] = $item;

        }

        $nextWeekDays = array();

        for ($i = 0; $i < 7; $i++) {

            $tempDate =  date("Y-m-d", strtotime("+$i days"));

            $nextWeekDays[$i]['fullDate'] = date("Y-m-d", strtotime("+$i days"));

            $nextWeekDays[$i]['date'] = date("m-d", strtotime("+$i days"));

            $nextWeekDays[$i]['w'] = $this->formatDateW($tempDate);

            $nextWeekDays[$i]['times'] = array();

            for ($j = $startTime; $j <= $endTime; $j++) {

                $serverTime = $tempDate . " $j:00";

                if (isset($orderArr[$serverTime])) {

                    $nextWeekDays[$i]['times'][] = array(

                        'time' => "$j:00",

                        'nickname' => $orderArr[$serverTime]['nickname'],

                        'is_busy' => 1,

                        'order_id'=>$orderArr[$serverTime]['order_id'],

                        'status'=>$orderArr[$serverTime]['status']

                    );

                } else {

                    $nextWeekDays[$i]['times'][] = array(

                        'time' => "$j:00",

                        'nickname' => '',

                        'is_busy' => 0

                    );

                }

            }

        }

        return $nextWeekDays;

    }

    public function NextWeekWorkTime($barberId,$storeId, $startTime = 10, $endTime = 21)

    {

        $today = date("Y-m-d $startTime:00:00");

        $endDay = date("Y-m-d $endTime:00:00", strtotime("+6 days"));

        $todayDate = date("Y-m-d");

        $endDayDate = date("Y-m-d", strtotime("+6 days"));

        $orders = $this->query("select date_format(`service_time`, '%Y-%m-%d %H:00') service_time,nickname,order_id,status 

              from ehecd_view_order 

              where service_time BETWEEN '$today' and '$endDay' 

              and barber_id=$barberId and status>=1 and status<=2

              and deleted = 0");

        $works = $this->query("select id,work_date,is_work,barber_id from ehecd_view_schedule where is_work=1 and  barber_id=%d and store_id=%d and work_date BETWEEN '%s' and '%s'",$barberId,$storeId,$todayDate,$endDayDate);

        $workArr = array();

        $orderArr = array();

        foreach ($orders as $item) {

            $orderArr[$item['service_time']] = $item;

        }

        foreach ($works as $work) {

            $workArr[$work['work_date']] = $work;

        }

        $nextWeekDays = array();

        for ($i = 0; $i < 7; $i++) {

            $tempDate =  date("Y-m-d", strtotime("+$i days"));

            $nextWeekDays[$i]['fullDate'] = date("Y-m-d", strtotime("+$i days"));

            $nextWeekDays[$i]['date'] = date("m-d", strtotime("+$i days"));

            $nextWeekDays[$i]['w'] = $this->formatDateW($tempDate);

            $nextWeekDays[$i]['times'] = array();

            for ($j = $startTime; $j <= $endTime; $j++) {

                $serverTime = $tempDate . " $j:00";

                if (isset($orderArr[$serverTime])) {  //如果该时间占用

                    $nextWeekDays[$i]['times'][] = array(

                        'time' => "$j:00",

                        'nickname' => $orderArr[$serverTime]['nickname'],

                        'is_busy' => 1,

                        'order_id'=>$orderArr[$serverTime]['order_id'],

                        'status'=>$orderArr[$serverTime]['status']

                    );

                } else {

                    //判断服务时间是否小于当前时间

                    if(strtotime($serverTime)<time()){

                        $nextWeekDays[$i]['times'][] = array(

                            'time' => "$j:00",

                            'nickname' => '',

                            'is_busy' => 1

                        );

                    }else{
                        if(isset($workArr[$tempDate]) && $workArr[$tempDate]['is_work']==1){

                            //无订单占用，且当天上班

                            $nextWeekDays[$i]['times'][] = array(

                                'time' => "$j:00",

                                'nickname' => '',

                                'is_busy' => 0

                            );

                        }else{

                            //无订单占用，但当天不上班

                            $nextWeekDays[$i]['times'][] = array(

                                'time' => "$j:00",

                                'nickname' => '',

                                'is_busy' => 1

                            );

                        }

                    }





                }

            }

        }

        return $nextWeekDays;

    }



    private function formatDateW($dateStr)

    {

        $w = date("w", strtotime($dateStr));

        $thisW = date("w");

        if ($w == $thisW) {

            return "今天";

        } elseif ($w == $thisW + 1) {

            return "明天";

        } else {

            return $this->week[$w];

        }



    }

}