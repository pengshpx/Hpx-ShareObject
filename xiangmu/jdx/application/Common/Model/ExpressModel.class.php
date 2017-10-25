<?php

namespace Common\Model;

class ExpressModel
{

    private $queryUrl = 'http://api.open.baidu.com/pae/channel/data/asyncqury';

    public function query($appId,$com,$nu)
    {
        $url = $this->queryUrl."?appid={$appId}&com={$com}&nu={$nu}";

        $cookie = " BDUSS=1J0NERwY3dqcWhud3E0VWNHNml-YVVZU1o0V3YtbWRwbXVpM0NlN354Uk1PSTFVQVFBQUFBJCQAAAAAAAAAAAEAAAB9CV0McG9vcm1hbnRpaQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEyrZVRMq2VUN; BAIDUPSID=327A86B61EB05F254C4738AD5330686C; ATS_PASS=0; BAIDUID=88484640F8AEDD01FF692B1117702C0D:FG=1";

        $content = $this->curl_request($url,'',$cookie);

        return $this->_returnArray($content);
    }

    /**
     * 将JSON内容转为数据，并返回
     * @param string $content [内容]
     * @return array
     */
    public function _returnArray($content)
    {
        return json_decode($content, true);
    }


    /**
     * 请求接口返回内容
     * @param $url          [请求的URL地址]
     * @param string $post  [post数据(不填则为GET请求)]
     * @return mixed|string
     */
    function curl_request($url, $post='',$cookie){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        return $data;
    }

    /**
     * 返回支持的快递公司公司列表
     * @return array
     */
    public function getComs()
    {
        return array(
            'yunda'=>'韵达快运',
            'yuantong'=>'圆通速递',
            'shentong'=>'申通速递',
            'shunfeng'=>'顺丰速递',
            'zhongtong'=>'中通快递',
            'quanfengkuaidi'=>'全峰快递',
            'zhaijisong'=>'宅急送',
            'tiantian'=>'天天快递',
            'youzhengguonei'=>'邮政包裹',
            'huitongkuaidi'=>'百世快递',
            'suer'=>'速尔物流',
            'youshuwuliu'=>'优速快递',
            'ems'=>'EMS',
            /*'quanfengkuaidi'=>'全峰快递',
            'chuanxiwuliu'=>'传喜物流',
            'datianwuliu'=>'大田物流',
            'debangwuliu'=>'德邦物流',
            'dsukuaidi'=>'D速快递',
            'disifang'=>'递四方',
            'feikangda'=>'飞康达物流',
            'feikuaida'=>'飞快达',
            'rufengda'=>'凡客如风达',
            'fengxingtianxia'=>'风行天下',
            'feibaokuaidi'=>'飞豹快递',
            'ganzhongnengda'=>'港中能达',
            'guotongkuaidi'=>'国通快递',
            'guangdongyouzhengwuliu'=>'广东邮政',
            'gongsuda'=>'共速达',
            'huitongkuaidi'=>'汇通快运',
            'huiqiangkuaidi'=>'汇强快递',
            'tiandihuayu'=>'华宇物流',
            'hengluwuliu'=>'恒路物流',
            'huaxialongwuliu'=>'华夏龙',
            'tiantian'=>'海航天天',
            'haimengsudi'=>'海盟速递',
            'huaqikuaiyun'=>'华企快运',
            'haihongwangsong'=>'山东海红',
            'jiajiwuliu'=>'佳吉物流',
            'jiayiwuliu'=>'佳怡物流',
            'jiayunmeiwuliu'=>'加运美',
            'jinguangsudikuaijian'=>'京广速递',
            'jixianda'=>'急先达',
            'jinyuekuaidi'=>'晋越快递',
            'jietekuaidi'=>'捷特快递',
            'jindawuliu'=>'金大物流',
            'jialidatong'=>'嘉里大通',
            'kuaijiesudi'=>'快捷速递',
            'kangliwuliu'=>'康力物流',
            'kuayue'=>'跨越物流',*/
        );
    }
}
