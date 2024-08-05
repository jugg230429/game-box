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
class Sdk_qqyybLogic
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    private $_connectTimeout = 60;
    private $_timeout = 60;

    public function login_verify($request='',$game_data,$plat_data){
        if(empty($request['openid'])){
            $result['code']  = 0;
            $result['msg'] = 'openid不能为空';
            return $result;
        }
        if(empty($request['access_token'])){
            $result['code']  = 0;
            $result['msg'] = 'access_token不能为空';
            return $result;
        }
        if(!in_array($request['login_type'],['qq','wx'])){
            $result['code']  = 0;
            $result['msg'] = 'login_type格式错误';
            return $result;
        }
        $service_config = json_decode($game_data['service_config'],true);
        if($request['login_type']=='qq'){
            $app_key = $service_config['APP_KEY'];
            $serverUrl = "https://ysdktest.qq.com/auth/qq_check_token";
            //【正式环境】https://ysdk.qq.com/auth/qq_check_token
            //【测试环境】https://ysdktest.qq.com/auth/qq_check_token
        }else{
            $app_key = $service_config['WX_AppSecret'];
            $serverUrl = "https://ysdktest.qq.com/auth/wx_check_token";
            //TODO
            //【正式环境】https://ysdk.qq.com/auth/wx_check_token
            //【测试环境】https://ysdktest.qq.com/auth/wx_check_token
        }
        $params = [
            "appid"=>$service_config['APP_ID'],
            "openid"=>$request['openid'],
            "openkey"=>$request['access_token'],
            "userip"=>$request['userip']??get_client_ip(),
            "timestamp"=>time(),
        ];
        $sig = $this->get_sign($app_key,$params['timestamp']);
        $params['sig'] = $sig;
        $response = $this->get($serverUrl, $params);
        $res = json_decode($response,true);
        if(isset($res['ret'])&&!$res['ret']){
            $result['code']  = 200;
            $result['msg'] = '用户验证成功';
            $result['data'] = $res;
        }else{
            $result['code']  = 0;
            $result['msg'] = $res['msg'];
        }
        return ($result);
    }

    private function get_sign($appkey,$timestamp)
    {
        return md5($appkey.$timestamp);
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

    public function create_order_extra($request,$game_data,$plat_data,$spend_data,$extra_data)
    {
        $verify_data = '';
        parse_str($request['verify_data']??'',$verify_data);
        if(empty($verify_data['openid'])){
            $result['code']  = 0;
            $result['msg'] = 'openid不能为空';
            return $result;
        }
        if(empty($verify_data['access_token'])){
            $result['code']  = 0;
            $result['msg'] = 'access_token不能为空';
            return $result;
        }
        if(!in_array($verify_data['login_type'],['qq','wx'])){
            $result['code']  = 0;
            $result['msg'] = '游客暂不支持支付';
            return $result;
        }
        $cookie["session_id"] = $verify_data['login_type']=='qq'?"openid":'hy_gameid';
        $cookie["session_type"] = $verify_data['login_type']=='qq'?"kp_actoken":'wc_actoken';
        $cookie["org_loc"] = "/v3/r/mpay/buy_goods_m";
        $openkey = $verify_data['login_type']=='qq'?$verify_data['openkey']:$verify_data['access_token'];
        $pf = $verify_data['pf'];
        // 支付接口票据, 从客户端YSDK登录返回的LoginRet获取
        $pfkey = $verify_data['pfkey'];
        $ts = time();
        $service_config = json_decode($game_data['service_config'],true);
        $params = array(
            'openid' => $verify_data['openid'],
            'openkey' => $openkey,
            'appid' => $service_config['APP_ID'],
            'ts' => $ts,
            'pf' => $pf,
            'pfkey' => $pfkey,
            'payitem'=>($verify_data['productid']??'1').'*'.($spend_data['pay_amount']*10).'*1',
            'goodsmeta'=>($spend_data['props_name']).'*'.($spend_data['props_name']),
            'appmode'=>1,
            'goodsurl'=>'',
            'zoneid' => $service_config['zoneid']??1,
        );
        require_once dirname(__FILE__).'/qqyyb_extra/SnsSigCheck.php';
        require_once dirname(__FILE__).'/qqyyb_extra/SnsNetwork.php';
        $SnsSigCheck = new \SnsSigCheck();
        $SnsNetwork = new \SnsNetwork();
        $method = 'get';
        $params['sig'] = $SnsSigCheck::makeSig($method,'/v3/r/mpay/buy_goods_m',$params,$service_config['APP_KEY'].'&');
        $servel_url = 'https://ysdktest.qq.com/mpay/buy_goods_m';
        //TODO
//        现⽹：https://ysdk.qq.com/mpay/buy_goods_m
//        沙箱：https://ysdktest.qq.com/mpay/buy_goods_m
        $res = $SnsNetwork::makeRequest($servel_url, $params, $cookie, $method, 'https');
        $res = json_decode($res['msg'],true);
        if(isset($res['ret'])&&!$res['ret']){
            $spend_data->order_number = $res['token'];
            $spend_res = $spend_data->save();
            $result['code']  = 200;
            $result['channel']  = 'yyb';
            $result['msg'] = '服务端下单成功';
            $result['data'] = $res;
        }else{
            $result['code']  = 0;
            $result['channel']  = 'yyb';
            $result['msg'] = $res['msg'];
        }
        return $result;
    }
    ////todo 游戏币
//    public function create_order_extra($request,$game_data,$plat_data,$spend_data,$extra_data)
//    {
//        try{
//            $verify_data = $this->parse_str($request['verify_data']);
//            if(empty($verify_data['openid'])){
//                $result['code']  = 0;
//                $result['msg'] = 'openid不能为空';
//                return $result;
//            }
//            if(empty($verify_data['access_token'])){
//                $result['code']  = 0;
//                $result['msg'] = 'access_token不能为空';
//                return $result;
//            }
//            if(!in_array($verify_data['login_type'],['qq','wx'])){
//                $result['code']  = 0;
//                $result['msg'] = '游客暂不支持支付';
//                return $result;
//            }
//            $cookie["session_id"] = ($verify_data['login_type']=='qq'?"openid":'hy_gameid');
//            $cookie["session_type"] = ($verify_data['login_type']=='qq'?"kp_actoken":'wc_actoken');
//            $cookie["org_loc"] = ("/v3/r/mpay/get_balance_m");
//            $openkey = $verify_data['login_type']=='qq'?$verify_data['openkey']:$verify_data['access_token'];
//            $pf = $verify_data['pf'];
//            // 支付接口票据, 从客户端YSDK登录返回的LoginRet获取
//            $pfkey = $verify_data['pfkey'];
//            $ts = time();
//            $service_config = json_decode($game_data['service_config'],true);
//            $params = array(
//                'openid' => $verify_data['openid'],
//                'openkey' => $openkey,
//                'appid' => $service_config['APP_ID'],
//                'ts' => $ts,
//                'pf' => $pf,
//                'pfkey' => $pfkey,
//                'zoneid' => $service_config['zoneid']??1,
//            );
//
//            require_once dirname(__FILE__) . '/qqyyb_extra/SnsSigCheck.php';
//            require_once dirname(__FILE__) . '/qqyyb_extra/SnsNetwork.php';
//            $SnsSigCheck = new \SnsSigCheck();
//            $SnsNetwork = new \SnsNetwork();
//            $method = 'get';
//            $params['sig'] = $SnsSigCheck::makeSig($method,'/v3/r/mpay/get_balance_m',$params,$service_config['APP_KEY'].'&');
//            $servel_url = 'https://ysdktest.qq.com/mpay/get_balance_m';
//            //TODO
//    //        现⽹：https://ysdk.qq.com/mpay/get_balance_m
//    //        沙箱：https://ysdktest.qq.com/mpay/get_balance_m
//            $res = $SnsNetwork::makeRequest($servel_url, $params, $cookie, $method, 'https');
//            $res = json_decode($res['msg'],true);
//            if(isset($res['ret'])&&!$res['ret']){
//                $callback_data['balance'] = $res['balance'];
//                $callback_data['time'] = intval(microtime(true)*1000);
//                $callback_data['sign'] = md5($extra_data['ff_order_number'].$callback_data['time'].$service_config['APP_KEY']);
//                $result['code']  = 200;
//                $result['channel']  = 'yyb';
//                $result['msg'] = '查询游戏币成功';
//                $result['data'] = $callback_data;
//            }else{
//                $result['code']  = 0;
//                $result['channel']  = 'yyb';
//                $result['msg'] = $res['msg'];
//            }
//        }catch (\Exception $exception){
//            $result['code']  = 0;
//            $result['channel']  = 'yyb';
//            $result['msg'] = '查询游戏币接口异常';
//        }
//        return $result;
//    }
////todo 游戏币
//    public function pay_callback($request,$applydata,$callback_data)
//    {
//        $service_config = json_decode($applydata['service_config'],true);
//        $callback_data['ff_order'] = 'R6kOZrf3MGjIAmylM5TaA64vMPjJcSxCMxTaA3MDFkRjZKfDEO0O0O';
//        $callback_data['time'] = 1630033621808;
//        $callback_data['sign'] = "c3869ed0be85a2c6a4d85f1593e280dd";
//        $sign = md5($callback_data['ff_order'].$callback_data['time'].$service_config['APP_KEY']);
//
//        if($sign!=$callback_data['sign']){
//            return ['code'=>0,'msg'=>'订单验签失败'];
//        }
//        $mSpend = new SpendModel();
//        $ff_order_arr  = explode('|',simple_decode($callback_data['ff_order']));//分发订单加密后传给渠道的数据
//        $ff_order['pay_order_number'] = $ff_order_arr[0];
//        $ff_order['ff_platform'] = $ff_order_arr[1];
//        if(empty($ff_order)||$ff_order['ff_platform']!=$request['channel_code']){
//            return ['code'=>0,'msg'=>'订单不合法'];
//        }
//        $mSpend->where('pay_order_number','=',$ff_order['pay_order_number']);
//        $mSpendData = $mSpend->find();
//        if(empty($mSpendData)||$mSpendData['platform_id']!=$ff_order['ff_platform']){
//            return ['code'=>0,'msg'=>'订单不存在'];
//        }
////        if($mSpendData['pay_status']==1&&$mSpendData['pay_game_status']==1){
////            return $this->callback_result(1);
////        }
//        //查询余额
//        $balance_res = $this->create_order_extra($request,$applydata,$applydata,$mSpendData,
//            ['ff_order_number'=>$request['ff_order']]);
//        if($balance_res['code']!=200){
//            return ['code'=>0,'msg'=>$balance_res['msg']];
//        }
//
//        if($balance_res['data']['balance']<$mSpendData['pay_amount']*100){//渠道单位分
//            return ['code'=>0,'msg'=>'游戏币不足'];
//        }
//        $verify_data = $this->parse_str($request['verify_data']??'');
//
//        //扣款操作
//        $cookie["session_id"] = ($verify_data['login_type']=='qq'?"openid":'hy_gameid');
//        $cookie["session_type"] = ($verify_data['login_type']=='qq'?"kp_actoken":'wc_actoken');
//        $cookie["org_loc"] = ("/v3/r/mpay/pay_m");
//        $openkey = $verify_data['login_type']=='qq'?$verify_data['openkey']:$verify_data['access_token'];
//        $pf = $verify_data['pf'];
//        // 支付接口票据, 从客户端YSDK登录返回的LoginRet获取
//        $pfkey = $verify_data['pfkey'];
//        $ts = time();
//        $params = array(
//            'openid' => $verify_data['openid'],
//            'openkey' => $openkey,
//            'appid' => $service_config['APP_ID'],
//            'ts' => $ts,
//            'pf' => $pf,
//            'pfkey' => $pfkey,
//            'zoneid' => $service_config['zoneid']??1,
//            'billno' => $callback_data['ff_order'],
//            'amt' => $mSpendData['pay_amount']*100,//渠道单位分
//        );
//        require_once dirname(__FILE__) . '/qqyyb_extra/SnsSigCheck.php';
//        require_once dirname(__FILE__) . '/qqyyb_extra/SnsNetwork.php';
//        $SnsSigCheck = new \SnsSigCheck();
//        $SnsNetwork = new \SnsNetwork();
//        $method = 'get';
//        $params['sig'] = $SnsSigCheck::makeSig($method,'/v3/r/mpay/pay_m',$params,$service_config['APP_KEY'].'&');
//        $servel_url = 'https://ysdktest.qq.com/mpay/pay_m';
//        //TODO
//        //        现⽹：https://ysdk.qq.com/mpay/pay_m
//        //        沙箱：https://ysdktest.qq.com/mpay/pay_m
//        $res = $SnsNetwork::makeRequest($servel_url, $params, $cookie, $method, 'https');
//        $res = json_decode($res['msg'],true);
//        //扣款结果
//        if(isset($res['ret'])&&!$res['ret']){
//            $mSpendData->order_number = $ff_order['pay_order_number'];//用发行的代替
//            return ['code'=>200,'spend_data'=>$mSpendData];
//        }else{
//            return ['code'=>0,'msg'=>'扣款失败'];
//        }
//    }

    public function pay_callback($request,$applydata,$callback_data)
    {
        $service_config = json_decode($applydata['service_config'],true);

        require_once dirname(__FILE__).'/qqyyb_extra/SnsSigCheck.php';
        require_once dirname(__FILE__).'/qqyyb_extra/SnsNetwork.php';
        $SnsSigCheck = new \SnsSigCheck();
        $SnsNetwork = new \SnsNetwork();
        $method = 'get';
        $callback_url = $service_config['callback_url']?:request()->url();
        $check_sign = $SnsSigCheck::verifySig($method,$callback_url,$callback_data,$service_config['APP_KEY'].'&',
            $callback_data['sig']);
        if(!$check_sign){
            return ['code'=>0,'msg'=>'订单验签失败'];
        }
        $mSpend = new SpendModel();
        $mSpend->where('order_number','=',$callback_data['token']);
        $mSpendData = $mSpend->find();
        if(empty($mSpendData)||$mSpendData['platform_openid']!=$callback_data['openid']){
            return ['code'=>0,'msg'=>'订单不存在'];
        }
        if($mSpendData['pay_status']==1&&$mSpendData['pay_game_status']==1){
            return $this->callback_result(1);
        }
        return ['code'=>200,'spend_data'=>$mSpendData];
    }
    public function callback_result($status)
    {
        if($status){
            echo '{"ret":0,"msg":"OK"}';exit;
        }else{
            echo '{"ret":"fail","msg":"发货失败"}';exit;
        }
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

    public function parse_str($string)
    {
        $arr = explode('&',htmlspecialchars_decode($string));
        $new_arr = [];
        foreach ($arr as $value){
            $v_arr = explode('=',$value);
            $new_arr[$v_arr[0]] = $v_arr[1]??'';
        }
        return $new_arr;
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
    //todo 初始化返回数据
    public function initialization($game_data,$platformData)
    {
        $service_config = json_decode($game_data['service_config'],true);
        $login_type = in_array($service_config['login_type'],['qq','wx'])?$service_config['login_type']:"qq";
        $login_type = is_array($login_type)?$login_type:[$login_type];
        return ['login_type'=>$login_type];
    }
}
