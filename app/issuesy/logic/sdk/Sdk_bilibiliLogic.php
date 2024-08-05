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
class Sdk_bilibiliLogic
{
    public function login_verify($request='',$game_data,$plat_data){
        if(empty($request['access_key'])){
            $result['code']  = 0;
            $result['msg'] = 'access_key不能为空';
            return $result;
        }
        if(empty($request['uid'])){
            $result['code']  = 0;
            $result['msg'] = 'uid不能为空';
            return $result;
        }
        require_once dirname(__FILE__).'/bilibili_extra/BiliApiHttpClient.class.php';
        require_once dirname(__FILE__).'/bilibili_extra/SignHelper.class.php';
        $serverUrl  		 = "http://pnew.biligame.net/api/server/";
        $service_config = json_decode($game_data['service_config'],true);
        $biliApiHttpClient = new \BiliApiHttpClient();
        $params['access_key'] = $request['access_key'];
        $biliConfig['game_id'] = $service_config['app_id'];
        $biliConfig['server_id'] = $service_config['server_id'];
        $biliConfig['merchant_id'] = $service_config['merchant_id'];
        $biliConfig['secret_key'] = $service_config['secret_key'];
        $biliConfig['user_agent'] = 'Mozilla/5.0 GameServer';
        $response = $biliApiHttpClient->post($serverUrl . 'session.verify', $params, $biliConfig);
        $res 		= 	json_decode($response,true);			//结果也是一个json格式字符串
        if(isset($res['code'])&&!$res['code']&&isset($res['open_id'])
        &&$res['open_id']==$request['uid']){
            $result['code']  = 200;
            $result['msg'] = 'token验证成功';
            $result['data'] = $res;
        }else{
            $result['code']  = 0;
            $result['msg'] = '验证失败';
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

    //创建额外参数 加密字符串
    public function create_order_extra($request,$game_data,$plat_data,$spend_data,$extra_data)
    {
        $service_config = json_decode($game_data['service_config'],true);
        $sign_data = '';
        parse_str($request['sign_data']??'',$sign_data);
        if(empty($sign_data['game_money'])){
            return ['code'=>0,'msg'=>'缺少加密参数game_money'];
        }
        if(empty($sign_data['money'])){
            return ['code'=>0,'msg'=>'缺少加密参数money'];
        }
        $sign_data['out_trade_no'] = md5($extra_data['ff_order_number']);
        $sign_data['notify_url'] = $extra_data['callbalc_url'];
        $signature_string = $sign_data['game_money'].$sign_data['money'].$sign_data['notify_url'].$sign_data['out_trade_no'].$service_config['secret_key'];
        $signature = md5($signature_string);
        $result['code']  = 200;
        $result['channel']  = 'bilibili';
        $result['msg'] = '加密字符串';
        $result['data'] = [
            'bilibiliSignature'=>$signature
        ];
        return $result;
    }

    public function pay_callback($request,$applydata,$callback_data)
    {
        $service_config = json_decode($applydata['service_config'],true);
        $serverUrl  		 = "http://pnew.biligame.net/api/server/";
        require_once dirname(__FILE__).'/bilibili_extra/BiliApiHttpClient.class.php';
        require_once dirname(__FILE__).'/bilibili_extra/SignHelper.class.php';
        $biliApiHttpClient = new \BiliApiHttpClient();
        $SignHelper = new \SignHelper();
        $result = 'failure';
        //order_status 为1 表示支付成功
        $paid_success = 1;
        $callback_data['data'] = htmlspecialchars_decode($callback_data['data']);
        $callback_data['data'] = html_entity_decode($callback_data['data']);
        $notifyParams = json_decode($callback_data['data'], true);
        if (!$SignHelper::checkSign($notifyParams, $service_config['secret_key'], $notifyParams['sign'])) {
            return ['code'=>0,'msg'=>'订单验签失败'];
        }
        if($notifyParams['out_trade_no']!=md5($notifyParams['extension_info'])){//长度问题 out_trade_no = MD5后的 extension_info
            return ['code'=>0,'msg'=>'订单号信息不一致'];
        }
        // 根据BILI订单号查询订单数据，并完成验证
        $params['order_no'] = $notifyParams['order_no'];
        $biliConfig['game_id'] = $service_config['app_id'];
        $biliConfig['server_id'] = $service_config['server_id'];
        $biliConfig['merchant_id'] = $service_config['merchant_id'];
        $biliConfig['secret_key'] = $service_config['secret_key'];
        $biliConfig['user_agent'] = 'Mozilla/5.0 GameServer';
        $validateResp = $biliApiHttpClient->post($serverUrl . 'query.pay.order', $params, $biliConfig);
        $validateRespArr = json_decode($validateResp, true);

        if ($paid_success !== $validateRespArr['order_status']) {
            return ['code'=>0,'msg'=>'订单查询失败'];
        }
        $mSpend = new SpendModel();
        $ff_order  = json_decode(simple_decode($notifyParams['extension_info']),true);
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
        if($notifyParams['pay_money']!=$mSpendData['pay_amount']*100){//渠道单位分
            return ['code'=>0,'msg'=>'订单金额不匹配'];
        }
        $mSpendData->order_number = $request['order_no'];//渠道订单号
        return ['code'=>200,'spend_data'=>$mSpendData];
    }

    /**
     *对数据进行排序
     */
    private function arrSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    public function callback_result($status)
    {
        if($status){
            echo 'success';exit;
        }else{
            echo 'fail';exit;
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

}
