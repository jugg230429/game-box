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
class Sdk_huaweiLogic
{
    public function login_verify($request='',$game_data,$plat_data){
        if(empty($request['open_id'])){
            $result['code']  = 0;
            $result['msg'] = 'open_id不能为空';
            return $result;
        }
        if(empty($request['access_token'])){
            $result['code']  = 0;
            $result['msg'] = 'access_token不能为空';
            return $result;
        }
        $serverUrl  		 = "https://api.cloud.huawei.com/rest.php?nsp_fmt=JSON&nsp_svc=huawei.oauth2.user.getTokenInfo";
        $ch = curl_init();
        $header[] = "Content-Type: application/x-www-form-urlencoded";
        $param = [
            'open_id'=>'OPENID',
            'access_token'=>$request['access_token'],
        ];
        $content = http_build_query($param, "", "&");
        $header[] = "Content-Length: ".strlen($content);
        curl_setopt($ch, CURLOPT_HEADER, true);	 //setting output include header.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //setting the transferred content in the header.
        curl_setopt($ch, CURLOPT_URL, $serverUrl);
        curl_setopt($ch, CURLOPT_POST, count($param));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // check the source of the certificate or not.
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // check the source of the certificate or not.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // setting not output all content if faild automatically.
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $result = substr($response, $header_size);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $res 		= 	json_decode($result,true);			//结果也是一个json格式字符串
        $service_config = json_decode($game_data['service_config'],true);
        $result = [];
        if ($res['open_id'] == $request['open_id']&&$res['client_id']==$service_config['APP_ID']){
            $result['code']  = 200;
            $result['msg'] = '用户验证成功';
            $result['data'] = $res;
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
        if(empty($request['inAppPurchaseData'])){
            return ['code'=>0,'msg'=>'缺少inAppPurchaseData'];
        }

        if(empty($request['inAppPurchaseDataSignature'])){
            return ['code'=>0,'msg'=>'缺少inAppPurchaseDataSignature'];
        }
        $request['inAppPurchaseData'] = htmlspecialchars_decode($request['inAppPurchaseData']);
        $InAppPurchaseData = json_decode($request['inAppPurchaseData'],true);
        $service_config = json_decode($applydata['service_config'],true);
        require_once dirname(__FILE__).'/huawei_extra/rsa_sha256.php';
        $rsa = new \RSA();
        $ok=$rsa::doChecK($request['inAppPurchaseData'],$request['inAppPurchaseDataSignature'],$service_config['publicKey']);
        if(!isset($InAppPurchaseData['purchaseState'])||!empty($InAppPurchaseData['purchaseState'])){
            return ['code'=>0,'msg'=>'订单付款状态未支付'];
        }
        if(!$ok) {
            return ['code'=>0,'msg'=>'订单数据验签失败'];
        }
        if(isset($InAppPurchaseData['purchaseType'])){//正式购买不会返回该参数。
            return ['code'=>0,'msg'=>'正式环境才能发货'];
        }
        //TODO 服务端验证
        $response = $this->checkToken($service_config,$InAppPurchaseData);
        if(!(!empty($response) && isset($response['responseCode']) && $response['responseCode'] == 0)){
            return ['code'=>0,'msg'=>'验证购买Token失败'];
        }
        $purchaseTokenData = json_decode($response['purchaseTokenData'],true);
        $mSpend = new SpendModel();
        $ff_order  = json_decode(simple_decode($purchaseTokenData['developerPayload']),true);//分发订单加密后传给渠道的数据
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
        if($purchaseTokenData['price']!=$mSpendData['pay_amount']*100){//渠道单位分
            return ['code'=>0,'msg'=>'订单金额不匹配'];
        }
        $mSpendData->order_number = $purchaseTokenData['orderId'];//渠道订单号
        //todo 发货
        $commit_res = $this->notice_after_delivery($service_config,$InAppPurchaseData);
        if($commit_res['responseCode']!=0&&$commit_res['responseCode']!=9){
            return ['code'=>0,'msg'=>'通知华为发货失败'];
        }
        return ['code'=>200,'spend_data'=>$mSpendData];
    }

    private function checkToken($service_config,$InAppPurchaseData)
    {
        require_once dirname(__FILE__).'/huawei_extra/at_demo.php';
        $AtDemo = new \AtDemo($service_config['APP_ID'],$service_config['SecretKey']);
        // fetch the App Level AccessToken
        $appAT = $AtDemo::getAppAT();
        if ($appAT == null) {
            return ['code'=>0,'msg'=>'获取accessToken失败'];
        }
        // construct the Authorization in Header
        $headers = $AtDemo::buildAuthorization($appAT);
        // pack the request body
        $body = ["purchaseToken" => $InAppPurchaseData['purchaseToken'], "productId" => $InAppPurchaseData['productId']];
        $msgBody = json_encode($body);
        $response = $AtDemo::httpPost('https://orders-drcn.iap.hicloud.com'.'/applications/purchases/tokens/verify', $msgBody, 5, 5, $headers);
        $response = json_decode($response,true);
        return $response;
    }

    /**
     * * 发货完成后通知
     * @param string $request
     * @return bool|mixed
     * @throws Exception
     */
    private function notice_after_delivery($service_config,$InAppPurchaseData)
    {
        require_once dirname(__FILE__).'/huawei_extra/at_demo.php';
        $AtDemo = new \AtDemo($service_config['APP_ID'],$service_config['SecretKey']);
        // fetch the App Level AccessToken
        $appAT = $AtDemo::getAppAT();
        if ($appAT == null) {
            return ['code'=>0,'msg'=>'获取accessToken失败'];
        }
        // construct the Authorization in Header
        $headers = $AtDemo::buildAuthorization($appAT);
        // pack the request body
        $body = ["purchaseToken" => $InAppPurchaseData['purchaseToken'], "productId" => $InAppPurchaseData['productId']];
        $msgBody = json_encode($body);
        $response = $AtDemo::httpPost('https://orders-drcn.iap.hicloud.com'.'/applications/v2/purchases/confirm', $msgBody, 5, 5, $headers);
        $response = json_decode($response,true);
        return $response;
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

    public function get_param($data,$param_name)//兼容post/get
    {
        $param_value = "";
        if(isset($data[$param_name])){
            $param_value = trim($data[$param_name]);
        }
        return $param_value;
    }
}
