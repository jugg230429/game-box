<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace app\issuesy\logic\sdk;

use app\issue\model\SpendModel;
use app\issue\model\UserModel;
use think\Model;

/**
 * @Copyright (c) 2021  XIGU Inc. All rights reserved.
 * @Link https://www.vlsdk.com
 * @License江苏溪谷网络科技有限公司版权所有
 * @Author  yyh
 */
class Sdk_360Logic
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    private $_connectTimeout = 60;
    private $_timeout = 60;

    public function login_verify($request='',$game_data,$plat_data){
        if(empty($request['access_token'])){
            $result['code']  = 0;
            $result['msg'] = 'access_token不能为空';
            return $result;
        }
        $service_config = json_decode($game_data['service_config'],true);
        $serverUrl  		 = "https://openapi.360.cn/user/me.json";
        $params = [
            'access_token'=>$request['access_token']
        ];
        $response = $this->get($serverUrl, $params);
        $response = json_decode($response,true);			//结果也是一个json格式字符串
        if (isset($response['id']) && $response['id']!=''){
            $result['code']  = 200;
            $result['msg'] = '用户验证成功';
            $result['data'] = $response;
        } else{
            $result['code']  = 0;
            $result['msg'] = '用户验证失败';
        }
        return ($result);
    }
    public function user_login(array $request,Model $apply_data)
    {
        $data['user_id'] = $request['channel_uid'];
        $data['birthday'] = $request['birthday'];
        $data['platform_id'] = $apply_data['platform_id'];
        $data['game_id'] = $apply_data['game_id'];
        $data['login_extend'] = $request['login_extend'];
        $data['equipment_num'] = $request['equipment_num'] ?? '';
        $data['open_user_id'] = $apply_data['open_user_id'] ?? '';
        $user = (new UserModel())->user_login($data);
        $token = ['user_id'=>$user->id,'ip'=>request()->ip(),'open_user_id'=>$apply_data['open_user_id'],'ff_platform'=>$apply_data['platform_id'],'host'=>request()->host(),'time'=>time()];
        $user->token = simple_encode(json_encode($token));
        return $user;
    }

    public function pay_callback($request,$applydata,$callback_data)
    {
        if($callback_data['app_order_id']!=md5($callback_data['app_ext1'])){//长度问题 app_order_id = MD5后的 app_ext1
            return ['code'=>0,'msg'=>'订单号信息不一致'];
        }
        $service_config = json_decode($applydata['service_config'],true);
        $params = array();
        $sign_key = [
            'app_key',
            'product_id',
            'amount',
            'app_uid',
            'app_ext1',
            'app_ext2',
            'user_id',
            'order_id',
            'gateway_flag',
            'sign_type',
            'app_order_id',
        ];
        foreach ($callback_data as $key => $value) {
            if(!in_array($key,$sign_key)||($value==='')){
                continue;
            }
            $params[$key] = $this->urlDecode($value);
        }
        $params = $this->arrSort($params);
        $sign_string = implode('#',$params);
        $signature = md5($sign_string.'#'.$service_config['appsecret']);
        if($signature!=$callback_data['sign']){
            return ['code'=>0,'msg'=>'订单验签失败'];
        }
        $mSpend = new SpendModel();
        $ff_order_arr  = explode('|',simple_decode($request['app_ext1']));//分发订单加密后传给渠道的数据
        $ff_order['pay_order_number'] = $ff_order_arr[0];
        $ff_order['ff_platform'] = $ff_order_arr[1];
        if(empty($ff_order)||$ff_order['ff_platform']!=$request['channel_code']){
            return ['code'=>0,'msg'=>'订单不合法'];
        }
        $mSpend->where('pay_order_number','=',$ff_order['pay_order_number']);
        $mSpendData = $mSpend->find();
        if(empty($mSpendData)||$mSpendData['platform_id']!=$ff_order['ff_platform']){
            return ['code'=>0,'msg'=>'订单不存在'];
        }
        if($mSpendData['pay_status']==1&&$mSpendData['pay_game_status']==1){
            return $this->callback_result(1);
        }
        if($request['amount']!=$mSpendData['pay_amount']*100){//渠道单位分
            return ['code'=>0,'msg'=>'订单金额不匹配'];
        }
        $mSpendData->order_number = $request['order_id'];//渠道订单号
        return ['code'=>200,'spend_data'=>$mSpendData];
    }
    public function callback_result($status)
    {
        if($status){
            echo '{"errcode":200}';exit;
        }else{
            echo '{"errcode":0}';exit;
        }
    }

    private function arrSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * @函数或方法说明
     * @获取game_key的值
     * @author: 郭家屯
     * @since: 2020/10/27 17:42
     */
    public function get_game_key($param){
        return $param['game_key'];
    }

    /**
     * Http post请求
     * @param type $url  http url address
     * @param type $data post params name => value
     */
    public function post($url, $data = array()){

        $queryString = $this->buildHttpQueryString($data,  self::METHOD_POST);

        $response = $this->makeHttpRequest($url, self::METHOD_POST,$queryString);
        return $response;
    }

    /**
     * http get 请求
     * @param type $url  http url address
     * @param type $data get params name => value
     */
    public function get($url,$data = array()) {
        if(!empty($data)){
            $url .= "?" . $this->buildHttpQueryString($data,  self::METHOD_GET);
        }
        $response = $this->makeHttpRequest($url,  self::METHOD_GET);
        return $response;
    }

    /**
     * 构造并发送一个http请求
     * @param type $url
     * @param type $method
     * @param type $postFields
     * @return type
     */
    public function makeHttpRequest($url,$method,$postFields = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(self::METHOD_POST == $method){
            curl_setopt($ch, CURLOPT_POST, 1);
            if(!empty($postFields)){
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            }
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 构造http请求的查询字符串
     * @param array $params
     * @param type $method
     * @return string
     */
    public function buildHttpQueryString(array $params, $method = self::METHOD_GET) {
        if(empty($params)){
            return '';
        }
        if(self::METHOD_GET == $method){
            $keys = array_keys($params);
            $values = $this->urlEncode(array_values($params));
            $params = array_combine($keys, $values);
        }

        $fields = array();

        foreach ($params as $key => $value) {
            $fields[] = $key . '=' . $value;
        }
        return implode('&',$fields);
    }

    /**
     * url encode 函数
     * @param type $item  数组或者字符串类型
     * @return type
     */
    public function urlEncode($item) {
        if(is_array($item)){
            return array_map(array(&$this,'urlEncode'), $item);
        }
        return rawurlencode($item);
    }

    /**
     * url decode 函数
     * @param type $item 数组或者字符串类型
     * @return type
     */
    public function urlDecode($item){
        if(is_array($item)){
            return array_map(array(&$this,'urlDecode'), $item);
        }
        return rawurldecode($item);
    }
    /**
     * 计算hmac-sha1签名
     * @param array $params
     * @param type $secretKey
     * @return type
     */
    public function sign(array $params, $secretKey){
        $sortString = $this->buildSortString($params);
        $signature = hash_hmac('sha1', $sortString, $secretKey,FALSE);

        return $signature;
    }

    /**
     * 验证签名
     * @param array $params
     * @param type $signature
     * @param type $secretKey
     * @return type
     */
    public function verifySignature(array $params, $signature, $secretKey) {
        $tmpSign = $this->sign($params, $secretKey);
        return $signature == $tmpSign ? TRUE : FALSE;
    }

    /**
     * 构造排序字符串
     * @param array $params
     * @return string
     */
    public function buildSortString(array $params) {
        if(empty($params)){
            return '';
        }

        ksort($params);

        $fields = array();

        foreach ($params as $key => $value) {
            $fields[] = $key . '=' . $value;
        }

        return implode('&',$fields);
    }

    public function get_ff_order_number($user,$game_data,$plat_data,$res)
    {
        $ff_order_number = simple_encode($res['pay_order_number'].'|'.$game_data['platform_id']);

        return ['code'=>200,'data'=>$ff_order_number];
    }
}
