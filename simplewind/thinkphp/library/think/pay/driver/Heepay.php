<?php

namespace think\pay\driver;

class Heepay extends \think\pay\Pay {
    #网页支付地址
    protected $gateway     = 'https://pay.Heepay.com/Payment/Index.aspx';
    #SDK支付地址
    protected $gateway_sdk = 'https://pay.heepay.com/Phone/SDK/PayInit.aspx';
    protected $config = array(
        'partner' => '',
        'key'=>'',
        'email'=>'',
    );

    public function check() {
        if (!$this->config['partner']) {
            E("汇付宝设置有误！");
        }
        return true;
    }

    /**
     * @param \Think\Pay\PayVo $vo
     * @return mixed|null|string
     */
    public function buildRequestForm(\Think\Pay\PayVo $vo) {
        $param = array(
            'version' => 1,
            'agent_id'        => $this->config['partner'],
            'agent_bill_id'   => $vo->getOrderNo(),
            'agent_bill_time' => date('YmdHis', time()),
            'pay_type'        => $vo->getWay(),
            'pay_amt'         => $vo->getFee(),
            'notify_url'      => $this->config['notify_url'],
            'return_url'      => "http://www.3011.cn",//$this->config['return_url']
            'goods_name'      => iconv("UTF-8","GB2312",$vo->getTitle()),
            'goods_num'       => 1,
            'goods_note'      => iconv("UTF-8","GB2312",$vo->getBody()),
            'remark'          => "3011",
        );
        $param['user_ip'] = "::1";//get_client_ip(); //$this->createSign($param);
        $param['sign'] = $this->createSign($param,$vo->getpayType());

        if($vo->getWay() == 30 && !$vo->getpayType()){
            if($vo->getpaWay == 1){
                $param['is_phone'] = 1;
                $param['is_frame'] = 0;
            }
            if($vo->getpaWay == 2){
                $param['is_phone'] = 1;
                $param['is_frame'] = 0;
            }
        }else if($vo->getWay == 20){
            $param['bank_type'] = $vo->getBank();
        }
        $url = $vo->getpayType() == 1 ? $this->gateway_sdk : $this->gateway;
        if($vo->getpayType() == 1){
            $sHtml = $this->_curlFrom($param,$url);
        }else{
            $sHtml = $this->_buildForm($param,$url);
        }
        return $sHtml;
    }

    protected function createSign($params,$getpayType=0) {
        $arg  = '';
        $arr0 = array('goods_name','goods_num','goods_note','remark','is_phone','is_frame','sign');
        $arr1 = array('return_url','goods_name','goods_num','goods_note','remark','is_phone','is_frame','sign');
        $arr  = $getpayType == 0 ? $arr0 : $arr1;
        foreach ($params as $key => $value) {
            if ($value != "" && !in_array($key,$arr)) {
                $arg .= "{$key}={$value}&";
            }
        }
        //var_dump($arg . 'key=' . $this->config['key']);
        return md5($arg . 'key=' . $this->config['key']);
    }

    protected function getSign($params) {

        $arg = '';
        foreach ($params as $key => $value) {
            if ($value != "") {
                $arg .= "{$key}={$value}&";
            }
        }
        return md5($arg . 'key=' . $this->config['key']);
    }

    protected function setInfo($notify) {
        $info = array();
        //支付状态
        $info['status'] = $notify['result'] ==1 ? true : false;
        $info['money'] = $notify['pay_amt'];
        $info['out_trade_no'] = $notify['agent_bill_id'];
        $this->info = $info;
    }

    public function verifyNotify($notify) {
        if(empty($notify['sign'])){
            $this->setInfo($notify);
            return true;
        }
        $param = array(
            'result'        => $notify['result'],
            'agent_id'      => $notify['agent_id'],
            'jnet_bill_no'  => $notify['jnet_bill_no'],
            'agent_bill_id' => $notify['agent_bill_id'],
            'pay_type'      => $notify['pay_type'],
            'pay_amt'       => $notify['pay_amt'],
            'remark'        => iconv("GB2312","UTF-8//IGNORE",$notify['remark']),
        );
        $mySign = $this->getSign($param);
        $return_sign = $notify['sign'];
        if($mySign==$return_sign){   //比较签名密钥结果是否一致，一致则保证了数据的一致性
            $this->setInfo($notify);
            return true;
        }
        else{
            return false;
        }
    }

}
// result=1&
// agent_id=2069305&
// jnet_bill_no=H1606246259359A6&
// agent_bill_id=PF_201606241158596efS&
// pay_type=30&
// pay_amt=0.50&
// remark=%e6%b8%b8%e6%88%8f%e5%85%85%e5%80&