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
class Sdk_9gameLogic
{
    public function login_verify($request='',$game_data,$plat_data){
        if(empty($request['sid'])){
            $result['code']  = 0;
            $result['msg'] = 'sid不能为空';
            return $result;
        }
        $serverUrl  		 = "http://sdk.9game.cn/cp/account.verifySession";
        $service_config = json_decode($game_data['service_config'],true);
        $post_request['id'] = time().sp_random_string(6);
        $post_request['data'] = [
            'sid' =>$request['sid'],
        ];
        $post_request['game'] = [
            'gameId' =>$service_config['GameID'],
        ];
        $post_request['sign'] = md5($request['sid'].$service_config['ApiKey']);
        $response = send_request($serverUrl, $post_request, 'POST', []);
        $res 		= 	json_decode($response['content'],true);			//结果也是一个json格式字符串
        if(isset($res['state']['code'])&&$res['state']['code']==1&&isset($res['data']['accountId'])
        &&$res['data']['accountId']!=''){
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
        if(!is_array($sign_data)){
            return ['code'=>0,'msg'=>'缺少加密参数'];
        }
        if(!$sign_data['accountId']){
            return ['code'=>0,'msg'=>'缺少accountId参数'];
        }
        if(!$sign_data['amount']){
            return ['code'=>0,'msg'=>'缺少amount参数'];
        }
        $sign_data['callbackInfo'] = $extra_data['ff_order_number'];
        $sign_data['notifyUrl'] = $extra_data['callbalc_url'];
        $sign_data['cpOrderId'] = md5($extra_data['ff_order_number']);
        $request['sign_data'] = $sign_data;
        $data = $this->arrSort($sign_data);
        $signature_string = http_build_query($data);
        $signature = strtolower(md5($signature_string.'&'.$service_config['ApiKey']));
        $result['code']  = 200;
        $result['channel']  = '9game';
        $result['msg'] = '加密字符串';
        $result['data'] = [
            '9gameSignature'=>$signature
        ];
        return $result;
    }

    public function pay_callback($request,$applydata,$callback_data)
    {
        $service_config = json_decode($applydata['service_config'],true);
        $serverUrl  		 = "http://bbs.9game.cn/thread-16364522-1-1.html";
        $sign_data = $this->arrSort($callback_data['data']);
        $sign_string = http_build_query($sign_data);
        $sign = md5($sign_string.'&'.$service_config['ApiKey']);
        if ($sign != $callback_data['sign']) {
            return ['code'=>0,'msg'=>'签名失败'];
        }
        if($callback_data['data']['orderStatus']!='S'){
            return ['code'=>0,'msg'=>$callback_data['data']['failedDesc']];//支付失败
        }
        $md5_callbackInfo = md5($callback_data['data']['callbackInfo']);
        if($md5_callbackInfo!=$callback_data['data']['cpOrderId']){
            return ['code'=>0,'msg'=>'订单不存在'];
        }
        $mSpend = new SpendModel();
        $ff_order_arr  = explode('|',simple_decode($request['callbackInfo']));//分发订单加密后传给渠道的数据
        $ff_order = $ff_order_arr[0];
        $platform_id = $ff_order_arr[1];
        if(empty($ff_order)||$platform_id!=$request['channel_code']){
            return ['code'=>0,'msg'=>'订单不合法'];
        }
        $mSpend->where('pay_order_number','=',$ff_order);
        $mSpendData = $mSpend->find();
        if(empty($mSpendData)||$mSpendData['platform_id']!=$platform_id){
            return ['code'=>0,'msg'=>'订单不存在'];
        }
        if($mSpendData['pay_status']==1&&$mSpendData['pay_game_status']==1){
            return $this->callback_result(1);
        }
        if($callback_data['data']['amount']*100!=$mSpendData['pay_amount']*100){//渠道单位元
            return ['code'=>0,'msg'=>'订单金额不匹配'];
        }
        $mSpendData->order_number = $request['orderId'];//渠道订单号
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
