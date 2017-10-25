<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-07-06
 * Time: 9:34
 */

namespace Api\Controller;


use Think\Controller;

class ApiCommonController extends Controller
{

    protected $data ;
    protected $errorMsg = array(
        0 => '请求成功',
        1 => '验证签名失败',
        2 => '缺少必要参数',
        3 => '发生未知错误',
        4 => '手机号或密码错误',
        5 => '缺少理发师ID参数',
        6=>'缺少订单号参数'
    );
    public function __construct()
    {
        parent::__construct();
        //$input = json_decode(htmlspecialchars_decode(I("post")),true);
        //\Think\Log::record(json_encode($_POST),"INFO");
        $data = I('data','');
        $timestamp = I('timestamp','');
        $sign = I('sign','');
        if(empty($sign) || empty($timestamp) || empty($data)){
            $this->error(2,$this->errorMsg[2]);
        }
        $temp = htmlspecialchars_decode($data);
        $this->data = json_decode($temp,true);
        if(!$this->checkSign($temp,$timestamp,$sign)){
            $this->error(1,$this->errorMsg[1]);
        }else{

        }
        
        if(ACTION_NAME != 'login'){
            if(empty($this->data['barber_id'])){
                $this->error(5,$this->errorMsg[5]);
            }else{
                $barber = M("Barber")->find($this->data['barber_id']);
                if(!empty($barber) && $barber['closed']==1){
                    $this->error(10,"对不起，你的账户已被冻结");
                }
            }
        }
    }

    /**
     * 验证签名是否正确
     * @param $data 本次请求的参数
     * @param $timestamp 时间戳
     * @param $sign 签名
     * @return bool 验签是否成功
     */
    protected function checkSign($data, $timestamp, $sign){
        $str = "{'timestamp':$timestamp,'data':$data}";
        $str = str_replace(array("{","}","\"",":","[","]","\\","/","'",",","(",")","?","#","@","!","$","&","*","+",";","=","<",">","%","|","^","~","`"," "),"",$str);
        $str = strtoupper($str);
        $str = urlencode($str);
        $str = strtoupper($str);
        $arr = str_split($str);
        rsort($arr);
        $str = implode($arr);
        $mySign = strtoupper(md5($str.SECRETKEY));
        //\Think\Log::record("sign:".$mySign."\n str:".$str,"INFO");
        if($mySign===$sign){
            return true;
        }else{
            return false;
        }
    }

    protected function appReturn($data,$maxCount=0,$code=0,$msg='请求成功',$status=true,$err=''){
        $arr = array(
            "Code"=>$code,
            "Msg"=>$msg,
            "Data"=>$data,
            "IsSucceed"=>$status,
            "MaxCount"=>$maxCount,
            "Err"=>$err,
        );
        //\Think\Log::record(json_encode($arr),"INFO");
        $this->ajaxReturn($arr);
    }

    protected function error($code=0,$err=''){
        $this->appReturn('',0,$code,'请求失败',false,$err);
    }

    protected function success($data,$maxCount=0){
        $this->appReturn($data,$maxCount);
    }
}