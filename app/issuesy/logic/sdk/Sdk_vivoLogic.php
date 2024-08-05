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
class Sdk_vivoLogic
{
    public function login_verify($request='',$game_data,$plat_data){
        if(empty($request['authtoken'])){
            $result['code']  = 0;
            $result['msg'] = 'authtoken不能为空';
            return $result;
        }
        if(empty($request['openId'])){
            $result['code']  = 0;
            $result['msg'] = 'openId不能为空';
            return $result;
        }
        $serverUrl  		 = "https://joint-account.vivo.com.cn/cp/user/auth";
        $service_config = json_decode($game_data['service_config'],true);
        $headers['Content-Type'] = "application/x-www-form-urlencoded";
        $post_request['opentoken'] = $request['authtoken'];
        $response = send_request($serverUrl, http_build_query($post_request), 'POST', $headers);
        $res 		= 	json_decode($response['content'],true);			//结果也是一个json格式字符串
        if(isset($res['retcode'])&&!$res['retcode']&&isset($res['data']['openid'])&&$res['data']['openid']==$request['openId']){
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

    public function pay_callback($request,$applydata,$callback_data)
    {
        $service_config = json_decode($applydata['service_config'],true);
        $data = [];
        $sign_key = [
            'respCode',
            'respMsg',
            'tradeType',
            'tradeStatus',
            'cpId',
            'appId',
            'uid',
            'cpOrderNumber',
            'orderNumber',
            'orderAmount',
            'extInfo',
            'payTime',
        ];
        foreach ($callback_data as $key=>$value){
            if(!in_array($key,$sign_key)){
                continue;
            }
            $data[$key] = $this->get_param($callback_data,$key);
        }
        $data = $this->arrSort($data);
        $signature_string = $this->_assemblyParameters($data);
        $signature = md5($signature_string.md5($service_config['APP_KEY_PAY']));
        if($signature!=$request['signature']){
            return ['code'=>0,'msg'=>'订单验签失败'];
        }
        $mSpend = new SpendModel();
        $ff_order_arr  = explode('|',simple_decode($request['cpOrderNumber']));//分发订单加密后传给渠道的数据
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
        if($request['orderAmount']!=$mSpendData['pay_amount']*100){//渠道单位分
            return ['code'=>0,'msg'=>'订单金额不匹配'];
        }
        $mSpendData->order_number = $request['orderNumber'];//渠道订单号
        return ['code'=>200,'spend_data'=>$mSpendData];
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
        $sign_data['cpOrderNumber'] = $extra_data['ff_order_number'];
        $sign_data['notifyUrl'] = $extra_data['callbalc_url'];
        $request['sign_data'] = $sign_data;
        foreach ($request['sign_data'] as $key=>$value){
            $data[$key] = $this->get_param($request['sign_data'],$key);
        }
        $data = $this->arrSort($data);
        $signature_string = $this->_assemblyParameters($data);
        $signature = strtolower(md5($signature_string.md5($service_config['APP_KEY_PAY'])));
        $result['code']  = 200;
        $result['channel']  = 'vivo';
        $result['msg'] = '加密字符串';
        $result['data'] = [
            'vivoSignature'=>$signature
        ];
        return $result;
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
    /**
     * 请求的参数串组合
     */
    public function _assemblyParameters($dataParams){
        $requestString 				= "";
        foreach($dataParams as $key=>$value){
            if(empty($value)){
                continue;
            }
            $requestString = $requestString . $key . "=" . $value . "&";
        }
        return $requestString;
    }

    public function get_param($data,$param_name)
    {
        $param_value = "";
        if(isset($data[$param_name])){
            $param_value = ($data[$param_name]);
        }
        return $param_value;
    }
    public function get_ff_order_number($user,$game_data,$plat_data,$res)
    {
        $ff_order_number = simple_encode($res['pay_order_number'].'|'.$game_data['platform_id']);

        return ['code'=>200,'data'=>$ff_order_number];
    }
}
