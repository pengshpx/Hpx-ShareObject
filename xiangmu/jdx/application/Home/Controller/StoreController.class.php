<?php



namespace Home\Controller;



use Common\Model\StoreModel;

use Common\Model\BarberModel;

use Common\Model\WeChatModel;



class StoreController extends CommonController

{

    protected $StoreModel;

    protected $BarberModel;



    public function __construct() {



        parent::__construct();

        $this->StoreModel = new StoreModel();

        $this->BarberModel = new BarberModel();

    }



    public function index()

    {



        $store = $this->storeCheck();

        $barbers = M('store_barber')->alias('sb')

            ->field('sb.id store_barber_id,b.id barber_id,b.name barber_name,b.stars,b.headimgurl,b.service_num,b.year,s.name service_name,s.price service_price')

            ->join('left join ehecd_barber b on sb.barber_id=b.id')

            ->join('left join ehecd_services s on sb.id=s.barber_store_id')
            //修改后 增加了sb.status
            ->where(array('sb.store_id'=>$store['id'],'sb.status'=>1,'b.closed'=>0,'s.deleted'=>0))

            ->group('sb.barber_id')

            ->select();

        $this->assign('store',$store);

        $this->assign('barbers',$barbers);

        $model = new WeChatModel();

        $sign = $model->getSignPackage();

        $this->assign('sign',$sign);

        $this->display();

    }



    public function barber()

    {

        $barber = $this->barberCheck();

        $barber['photos'] = json_decode(htmlspecialchars_decode($barber['photos']),true);

        $barber['description'] = str_replace(" ","&nbsp;",$barber['description']);

        $map = array('barber_store_id' => $barber['store_barber']['id'],'deleted'=>0);

        $services = M('services')->where($map)->select();

        $this->assign('barber',$barber);

        $this->assign('services',$services);

        $model = new WeChatModel();

        $sign = $model->getSignPackage();

        $this->assign('sign',$sign);

        $this->display();

    }



    public function barberComments()

    {

        $barber_id = (int)I('barber_id');

        $p = I('p');

        $firstSize = 3;

        $pageSize = $p == 1 ? $firstSize : 10;

        $start = $p == 1 ? 0 : ($p-2)*$pageSize+$firstSize;

        $map = array('c.barber_id'=>$barber_id,'c.deleted'=>0);

        $count = M('comments')->alias('c')

            ->join("left join ehecd_member m on c.member_id=m.member_id")

            ->where($map)

            ->count();

        $list = M('comments')->alias('c')

            ->field('c.content,c.reply,DATE_FORMAT(c.create_time,"%Y-%m-%d %H:%i") create_time,m.headimgurl,m.nickname')

            ->join("left join ehecd_member m on c.member_id=m.member_id")

            ->where($map)

            ->order('c.create_time desc')

            ->limit($start,$pageSize)

            ->select();

        $totalPages =ceil(($count-$firstSize<0?0:$count-$firstSize)/$pageSize)+1;

        $this->success(array('list'=>$list,'totalPages'=>$totalPages));

    }



    public function info()

    {

        $info = $this->storeCheck();

        $info['pic_list'] = json_decode(htmlspecialchars_decode($info['pic_list']),true);

        $info['details'] = htmlspecialchars_decode($info['details']);

        $this->assign('info',$info);

        $this->display();

    }



    public function barberCheck()

    {

        $barber_id = (int)I('barber_id');

        $store_barber_id = (int)I('store_barber_id');

        $barber = $this->BarberModel->find($barber_id);

        if (empty($barber) || $barber['closed'] == 1) {

            $this->error("理发师不存在或已删除，请刷新");

        }

        $store_barber = M('store_barber')->find($store_barber_id);

        if (empty($store_barber)) {

            $this->error("门店或理发师不存在，请刷新");

        }

        $barber['store_barber'] = $store_barber;

        return $barber;

    }



    public function storeCheck()

    {

        $id = (int)I('id');

        $map = array(

            'id'=>$id,

            'deleted'=>0

        );

        $store = $this->StoreModel->where($map)->find();

        if (empty($store)) {

            $this->error("店铺不存在或已关闭，请刷新","Index/index");

        }

        return $store;

    }

}