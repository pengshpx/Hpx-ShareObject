<?php

namespace Home\Controller;

use Common\Model\StoreModel;

use Common\Model\WeChatModel;

use Think\Controller;

use Think\Exception;



class IndexController extends CommonController {

    const REQ_GET = 1;
    const REQ_POST = 2;

    public function index(){

        //$userLocation = cookie('userLocation');
        $userLocation = session('userLocation');
        if(!$userLocation){

            if(is_weixin()){

                $this->redirect('main');

            }else{

                $this->redirect('selectLocation');

            }



        }

        $storeModel = new StoreModel();
        if(!$userLocation){

            $model = new WeChatModel();

            $sign = $model->getSignPackage();

            $this->assign('sign',$sign);

            $this->assign('isLocate',0);

            $city='成都';

            $lat=0;

            $lng = 0;

        }else{

            $this->assign('userLocation',$userLocation);

            $this->assign('isLocate',1);

            $city=$userLocation['city'];

            $lat=$userLocation['lat'];

            $lng = $userLocation['lng'];

        }

        $distance = distance_sql($lng,$lat);

        $res = $storeModel->field("id,name,tel,address,pic,open_time,close_time")->where("city like '%$city%' and deleted=0")->order($distance.' asc')->select();

        $this->assign('lists',$res);

        $this->display();

    }



    public function location($lat,$lng){
        if(!$lat || !$lng){

            $data=M("Store")->field('id,lng,lat,city')->find();

            $location['city'] = $data['city'];;

            $location['lat'] = $data['lat'];

            $location['lng'] = $data['lng'];

        }else{

            $storeModel = new StoreModel();

//            $distance = distance_sql($lng,$lat);

//            $location = $storeModel->query("select city from ehecd_store order by $distance asc limit 0,1");
            $location=$this->locationByGPS($lng,$lat);

            $city=$storeModel->field('city')->where(array('city'=>$location['city']))->find();

            if($city){

                $location['city'] = str_replace('市','',$city['city']);

                $location['lat'] = $lat;

                $location['lng'] = $lng;

            }else{

                $this->error();

            }

        }

        session('userLocation',$location);
        cookie('userLocation',$location);

        $this->success();

    }

    

    public function selectLocation(){

        $city = I('city');

        if($city){

            $storeModel = new StoreModel();

            $record = $storeModel->where("city like '%$city%' and deleted=0")->count();

            if($record>0){

                $location['city'] = $city;

                $location['lat'] = 0;

                $location['lng'] = 0;

            }

            //cookie('userLocation',$location);
            session('userLocation',$location);
            $this->redirect('index');

        }

        $storeModel = new StoreModel();

        $areas = $storeModel->getArea();

        $this->assign('areas',$areas);

        $this->assign('userLocation',session('userLocation'));

        $this->display();

    }



    public function main(){
        try{

            $model = new WeChatModel();


            $sign = $model->getSignPackage();

            $this->assign('sign',$sign);

            $this->display();

        }catch (Exception $e){
            $location['city'] = '成都';

            $location['lat'] = 0;

            $location['lng'] = 0;
            session('userLocation',$location);
            $this->redirect('index');
        }

    }


    /**
     * GPS定位
     * @param $lng
     * @param $lat
     * @return array
     * @throws Exception
     */
    public function locationByGPS($lng, $lat)
    {
        $params = array(
            'coordtype' => 'wgs84ll',
            'location' => $lat . ',' . $lng,
            'ak' => 'DdydmFxbZOkEXb068RfRXVeG',
            'output' => 'json',
            'pois' => 0
        );
        $resp = $this->async('http://api.map.baidu.com/geocoder/v2/', $params, false);
        $data = json_decode($resp, true);
        if ($data['status'] != 0)
        {
            throw new Exception($data['message']);
        }
        return array(
            'address' => $data['result']['formatted_address'],
            'province' => $data['result']['addressComponent']['province'],
            'city' => $data['result']['addressComponent']['city'],
            'street' => $data['result']['addressComponent']['street'],
            'street_number' => $data['result']['addressComponent']['street_number'],
            'city_code'=>$data['result']['cityCode'],
            'lng'=>$data['result']['location']['lng'],
            'lat'=>$data['result']['location']['lat']
        );
    }

    /**
     * 执行CURL请求
     * @author: xialei<xialeistudio@gmail.com>
     * @param $url
     * @param array $params
     * @param bool $encode
     * @param int $method
     * @return mixed
     */
    private function async($url, $params = array(), $encode = true, $method = self::REQ_GET)
    {
        $ch = curl_init();
        if ($method == self::REQ_GET)
        {
            $url = $url . '?' . http_build_query($params);
            $url = $encode ? $url : urldecode($url);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        else
        {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_REFERER, '百度地图referer');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X; en-us) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($ch);
        curl_close($ch);
        return $resp;
    }
}