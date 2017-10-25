<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-06-07
 * Time: 17:23
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\OptionsModel;
use Common\Model\WeChatModel;
class WechatController extends AdminbaseController
{
    private $options_model;
    private $key = 'weixin';
    public function __construct()
    {
        parent::__construct();
        $this->options_model = new OptionsModel();
    }

    public function index(){
        if(IS_POST){
            $options = I('options');
            $optionsArr = array(
                'option_name' => $this->key,
                'option_value'=> json_encode($options)
            );
            if($this->options_model->where("option_name='{$this->key}'")->find()){
                $r=$this->options_model->where("option_name='{$this->key}'")->save($optionsArr);
            }else{
                $r=$this->options_model->add($optionsArr);
            }

            if ($r!==false) {
                $this->success("保存成功！");
            } else {
                $this->error($this->options_model->getError());
            }
        }else{
            $option=$this->options_model->where("option_name='{$this->key}'")->find();
            if($option){
                $optionArr = (array)json_decode($option['option_value']);
                $optionArr['url'] = "http://".$_SERVER['HTTP_HOST'].U('Api/WeChat/index');
                $this->assign($optionArr);
                $this->assign("option_id",$option['option_id']);
            }
            $this->display();
        }
    }
    public function menu(){
        $model = new WeChatModel();
        $wx = $model->getWeChatAuthWithAccessToken();
        if(IS_AJAX && IS_POST){
            $data = I("menu");
            $res = $wx->menuCreate($data);
            if($res['errcode']==0){
                $this->success("菜单设置成功");
            }else{
                $this->error($res['errmsg']);
            }
        }else{
            $menu = $wx->menuGet();
            if($menu && !isset($menu['errcode'])){
                $this->assign("menu",json_encode($menu['menu']));
            }else{
                $this->assign("menu","{button:[]}");
            }
            $this->display();
        }

    }

    public function keywords(){
        if(IS_AJAX && IS_GET){
            $model = M("WxKeywords");
            $search = I('get.');
            $where = " 1=1 ";
            if($search['keyword']!=''){
                $where .= " and keyword like'%{$search['keyword']}%'";
            }
            $count = $model->where($where)->count();
            if($count>0){
                $list['data'] = $model->where($where)->page($search['p'],$this->pageNum)->order('update_time desc')->select();
            }else{
                $list['data'] = array();
            }
            $list['count'] = ceil($count/$this->pageNum);
            $this->success($list);
        }
        $this->display();
    }
    
    public function add_keyword(){
        $data = I("post.");
        $model = M("WxKeywords");
        if($data['id'] == ''){
            $res = $model->add($data);
        }elseif ($data['id']>0){
            $res = $model->where("id=%d",$data['id'])->save($data);
        }

        if($res){
            $this->success("操作成功");
        }else{
            $this->error("操作失败：".$model->getError());
        }
    }

    public function delete_keyword($id){
        $model = M("WxKeywords");
        $res = $model->where("id=%d",$id)->delete();
        if($res){
            $this->success("操作成功");
        }else{
            $this->error("操作失败：".$model->getError());
        }
    }
}