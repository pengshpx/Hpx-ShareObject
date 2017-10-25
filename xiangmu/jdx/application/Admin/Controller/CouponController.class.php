<?php



namespace Admin\Controller;



use Common\Controller\AdminbaseController;



class CouponController extends AdminbaseController

{



    protected $Coupon_model;



    public function _initialize() {

        parent::_initialize();

        $this->Coupon_model = D('Coupon');

    }



    //展示优惠券列表页面

    public function index()

    {

        if (IS_AJAX && IS_POST) {

            $p = I('p');

            $listPages = $this->pageNum;

            $map = array('cp.is_invoke'=>1);

            $code = I('code');

            $nickname = I('nickname','','');

            $get_st_time = I('get_st_time');

            $get_end_time = I('get_end_time');

            $use_st_time = I('use_st_time');

            $use_end_time = I('use_end_time');

            if (!empty($code)) {

                $map['cp.code'] = $code;

            }

            if (!empty($nickname)) {

                $map['m.nickname'] = $nickname;

            }

            if (!empty($get_st_time)) {

                $map["DATE_FORMAT(cp.get_time,'%Y-%m-%d')"] = array('egt',$get_st_time);

            }

            if (!empty($get_end_time)) {

                $map["DATE_FORMAT(cp.get_time,'%Y-%m-%d')"] = array('elt',$get_end_time);

            }

            if (!empty($get_st_time) && !empty($get_end_time)) {

                $map["DATE_FORMAT(cp.get_time,'%Y-%m-%d')"] = array('between',"{$get_st_time},{$get_end_time}");

            }

            if (!empty($use_st_time)) {

                $map["DATE_FORMAT(cp.use_time,'%Y-%m-%d')"] = array('egt',$use_st_time);

            }

            if (!empty($use_end_time)) {

                $map["DATE_FORMAT(cp.use_time,'%Y-%m-%d')"] = array('elt',$use_end_time);

            }

            if (!empty($use_st_time) && !empty($use_end_time)) {

                $map["DATE_FORMAT(cp.use_time,'%Y-%m-%d')"] = array('between',"{$use_st_time},{$use_end_time}");

            }

            $count = $this->Coupon_model->alias('cp')

                ->join('left join ehecd_member m on cp.member_id=m.member_id')

                ->where($map)

                ->count();

            $list = $this->Coupon_model->alias('cp')

                ->join('left join ehecd_member m on cp.member_id=m.member_id')

                ->where($map)

                ->order('cp.create_time desc')

                ->page($p,$listPages)

                ->select();

            $totalPage = ceil($count/$listPages);

            $this->ajaxReturn(array('list'=>$list,'totalPage'=>$totalPage,'status'=>1));

        }

        $this->display();

    }



    //通过会员ID获取优惠券信息

    public function getCouponByMemberId()

    {

        $memberId = I('member_id');

        $listPages = 10;

        $map = array('is_invoke'=>1,'member_id'=>$memberId);

        $count = $this->Coupon_model

            ->where($map)

            ->count();

        $Page = $this->page($count,$listPages);

        $list = $this->Coupon_model

            ->where($map)

            ->limit($Page->firstRow,$Page->listRows)

            ->select();

        $totalPage = ceil($count/$listPages);

        $this->ajaxReturn(array('list'=>$list,'totalPage'=>$totalPage,'status'=>1));

    }
    public function coupon_business(){
        if(IS_AJAX){
            $p = I('p');

            $listPages = $this->pageNum;

            //$map['deleted']=0;
            $map=array();
            $count = M('CouponBusiness')

                ->where($map)

                ->count();

            $list = M('CouponBusiness')

                ->where($map)

                ->order('create_time desc')

                ->page($p,$listPages)

                ->select();

            $totalPage = ceil($count/$listPages);

            $this->ajaxReturn(array('list'=>$list,'totalPage'=>$totalPage,'status'=>1));
        }else{
            $this->display();
        }

    }
    public function add_business(){
        if(IS_POST){
            $name=I('name','');
            $money=I('money',0,'floatval');
            $rules  = array(
                array('name', 'require', '商家名称不能为空！'),
                array('name', '2,30', '请输入长度为2-30的商家名称！', 1, 'length'),
                array('money', 'require', '优惠码金额不能为空！'),

            );
            if($money<=0){
                $this->error('请输入正确的优惠码金额！');
            }
            $data = array(
                'name'=>$name,
                'money'=>$money,
                'code'=>$this->randomCode(),
                'create_time'=>date('Y-m-d H:i:s')

            );
            if(M('CouponBusiness')->validate($rules)->create()){
                M('CouponBusiness')->add($data);
               $this->success('添加成功！');
            }else{
                $this->error(M('CouponBusiness')->getError());
            }
        }else{
            $this->display();
        }
    }
    public function randomCode()
    {
        $date = (int) date("ymd")-160714;
        $randomCode = str_pad($date,4,"0").mt_rand(100000,999999);
        if (M('CouponBusiness')->where(array('code'=>$randomCode))->find()) {
            $this->randomCode();
        }
        return $randomCode;
    }

    public function edit_business(){
        if(IS_POST){
            $name=I('name','');
            $id=I('id',0,'intval');
            $money=I('money',0,'floatval');
            $rules  = array(
                array('name', 'require', '商家名称不能为空！'),
                array('name', '2,30', '请输入长度为2-30的商家名称！', 1, 'length'),
                array('money', 'require', '优惠码金额不能为空！'),

            );
            if($money<=0){
                $this->error('请输入正确的优惠码金额！');
            }
            if($id<=0){
                $this->error('请选择要编辑的信息！');
            }
            $data = array(
                'id'=>$id,
                'name'=>$name,
                'money'=>$money,
                'create_time'=>date('Y-m-d H:i:s')
            );
            if(M('CouponBusiness')->validate($rules)->create()){
                M('CouponBusiness')->save($data);
                $this->success('编辑成功！');
            }else{
                $this->error(M('CouponBusiness')->getError());
            }
        }else{
            $id=I('id',0,'intval');
            if($id){
                $info=M('CouponBusiness')->find($id);
                $this->assign('info',json_encode($info));
            }
            $this->display('add_business');
        }
    }
    public function delete_business(){
        $id=I('id',0,'intval');
        if($id) {
            $deleted=M('CouponBusiness')->where(array('id'=>$id))->getField('deleted');
            if($deleted==1){
                M('CouponBusiness')->where(array('id'=>$id))->setField('deleted',0);
            }else{
                M('CouponBusiness')->where(array('id'=>$id))->setField('deleted',1);
            }
            $this->success('状态切换成功');
        }else{
            $this->error('请选择要处理的信息！');
        }
    }
    public function detail_business(){
        $id=I('id',0,'intval');
        if(IS_POST){

            $info=M('CouponBusiness')->field('name,code')->find($id);
            $p = I('p');
            $listPages = $this->pageNum;
            $map['cbl.bid']=$id;
            $count = M('CouponBusinessLog')->alias('cbl')
                ->join(' left join __MEMBER__ as m on cbl.mid=m.member_id')
                ->where($map)

                ->count();

            $list = M('CouponBusinessLog')->field('cbl.*,m.nickname,m.headimgurl')
                ->alias('cbl')
                ->join(' left join __MEMBER__ as m on cbl.mid=m.member_id')
                ->where($map)
                ->order('cbl.create_time desc')

                ->page($p,$listPages)

                ->select();

            $totalPage = ceil($count/$listPages);

            $this->ajaxReturn(array('list'=>$list,'totalPage'=>$totalPage,'status'=>1,'info'=>$info));
        }else{
            $this->assign('id',$id);
            $this->display();
        }
    }

    public function exportDetail(){
        try{
            $id=I('id',0,'intval');
            $map['cbl.bid']=$id;
            $data = M('CouponBusinessLog')->field('cbl.*,m.nickname,m.headimgurl')
                ->alias('cbl')
                ->join(' left join __MEMBER__ as m on cbl.mid=m.member_id')
                ->where($map)
                ->select();
            foreach($data as $key=>$vo){
                $data[$key]['phone']=$vo['phone'].' ';
            }
            $xlsCell  = array(
                array('id','序号'),
                array('nickname','会员昵称'),
                array('phone','手机号'),
                array('money','金额'),
                array('create_time','领取时间')

            );
            exportExcel("jdx",$xlsCell,$data);
        }catch (Exception $e){

            $this->error('查询发生错误');

        }

    }
}