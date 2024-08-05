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
class Sdk_oppoLogic
{
    private $_publicKey;

    public function login_verify($request='',$game_data,$plat_data){
        if(empty($request['token'])){
            $result['code']  = 0;
            $result['msg'] = 'token不能为空';
            return $result;
        }
        if(empty($request['ssoid'])){
            $result['code']  = 0;
            $result['msg'] = 'ssoid不能为空';
            return $result;
        }
        $serverUrl  		 = "https://iopen.game.oppomobile.com/sdkopen/user/fileIdInfo";
        $request_serverUrl   = $serverUrl."?fileId=".$request['ssoid']."&token=".($request['token']);
        $time                = microtime(true);
        $service_config = json_decode($game_data['service_config'],true);
        $dataParams['oauthConsumerKey'] 	= $service_config['appkey']??'';
        $dataParams['oauthToken'] 			= $request['token'];
        $dataParams['oauthSignatureMethod'] = "HMAC-SHA1";
        $dataParams['oauthTimestamp'] 		= intval($time*1000);
        $dataParams['oauthNonce'] 			= intval($time) + rand(0,9);
        $dataParams['oauthVersion'] 		= "1.0";
        $requestString 						= $this->_assemblyParameters($dataParams);
        $oauthSignature = $service_config['appsecret']."&";
        $sign 			= $this->_signatureNew($oauthSignature,$requestString);
        $res 		= $this->OauthPostExecuteNew($sign,$requestString,$request_serverUrl);
        $res 		= 	json_decode($res,true);			//结果也是一个json格式字符串
        if ($res['resultCode'] == 200){
            $result['code']  = 200;
            $result['msg'] = 'token验证成功';
            $result['data'] = $res;
        } else{
            $result['code']  = 0;
            $result['msg'] = $res['resultMsg'];
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
        $this->_publicKey = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCmreYIkPwVovKR8rLHWlFVw7YDfm9uQOJKL89Smt6ypXGVdrAKKl0wNYc3/jecAoPi2ylChfa2iRu5gunJyNmpWZzlCNRIau55fxGW0XEu553IiprOZcaw5OuYGlf60ga8QT6qToP0/dpiL/ZbmNUO9kUhosIjEu22uFgR+5cYyQIDAQAB";
        $data['notifyId'] = $this->get_param($request,'notifyId');
        $data['partnerOrder'] = $this->get_param($request,'partnerOrder');
        $data['productName'] = $this->get_param($request,'productName');
        $data['productDesc'] = $this->get_param($request,'productDesc');
        $data['price'] = $this->get_param($request,'price');
        $data['count'] = $this->get_param($request,'count');
        $data['attach'] = $this->get_param($request,'attach');
        $data['sign'] = $this->get_param($request,'sign');
        $result = $this->rsa_verify($data);
        if(!$result){
            return ['code'=>0,'msg'=>'订单验签失败'];
        }
        $ff_order  = json_decode(simple_decode($data['partnerOrder']),true);//分发订单加密后传给渠道的数据
        $mSpend = new SpendModel();
        if(empty($ff_order)){
            return ['code'=>0,'msg'=>'订单不合法'];
        }
        $mSpend->where('pay_order_number','=',$ff_order['pay_order_number']);
        $mSpendData = $mSpend->find();
        if(empty($mSpendData)){
            return ['code'=>0,'msg'=>'订单不存在'];
        }
        if($mSpendData['pay_status']==1&&$mSpendData['pay_game_status']==1){
            return $this->callback_result(1);
        }
        if($data['price']!=$mSpendData['pay_amount']*100){//渠道单位分
            return ['code'=>0,'msg'=>'订单金额不匹配'];
        }
        $mSpendData->order_number = $data['notifyId'];//渠道订单号
        return ['code'=>200,'spend_data'=>$mSpendData];
    }
    private function post($param,$url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function callback_result($status)
    {
        if($status){
            echo 'result=OK&resultMsg=成功';exit;
        }else{
            echo 'result=FAIL&resultMsg=游戏发货失败';exit;
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
     * 请求的参数串组合
     */
    public function _assemblyParameters($dataParams){
        $requestString 				= "";
        foreach($dataParams as $key=>$value){
            $requestString = $requestString . $key . "=" . $value . "&";
        }
        return $requestString;
    }


    /**
     * 使用HMAC-SHA1算法生成签名
     */
    public function _signatureNew($oauthSignature,$requestString){
        return urlencode(base64_encode( hash_hmac( 'sha1', $requestString,$oauthSignature,true) ));
    }


    /**
     * Oauth身份认证请求
     * @param string $Authorization 请求头值
     * @param string $serverUrl     请求url
     */

    public function OauthPostExecuteNew($sign,$requestString,$request_serverUrl){
        $opt = array(
            "http"=>array(
                "method"=>"GET",
                'header'=>array("param:".$requestString, "oauthsignature:".$sign),
            )
        );
        $res=file_get_contents($request_serverUrl,null,stream_context_create($opt));
        return $res;
    }
    public function rsa_verify($contents) {
        $str_contents = "notifyId={$contents['notifyId']}&partnerOrder={$contents['partnerOrder']}&productName={$contents['productName']}&productDesc={$contents['productDesc']}&price={$contents['price']}&count={$contents['count']}&attach={$contents['attach']}";
        $publickey= $this->_publicKey;
        $pem = chunk_split($publickey,64,"\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n".$pem."-----END PUBLIC KEY-----\n";
        $public_key_id = openssl_pkey_get_public($pem);
        $signature =base64_decode($contents['sign']);
        return openssl_verify($str_contents, $signature, $public_key_id );//成功返回1,0失败，-1错误,其他看手册
    }

    public function get_param($data,$param_name)//兼容post/get
    {
        $param_value = "";
        if(isset($data[$param_name])){
            $param_value = trim($data[$param_name]);
        }
        return $param_value;
    }
    public function get_ff_order_number($user,$game_data,$plat_data,$res)
    {
        $ff_order_number = simple_encode(json_encode(['pay_order_number'=>$res['pay_order_number']]));

        return ['code'=>200,'data'=>$ff_order_number];
    }
}
