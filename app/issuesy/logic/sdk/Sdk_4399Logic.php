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
class Sdk_4399Logic
{
    public function login_verify($request='',$game_data,$plat_data){
        if(empty($request['token'])){
            $result['code']  = 0;
            $result['msg'] = 'token不能为空';
            return $result;
        }
        if(empty($request['uid'])){
            $result['code']  = 0;
            $result['msg'] = 'uid不能为空';
            return $result;
        }
        $serverUrl  		 = "https://m.4399api.com/openapi/oauth-check.html";
        $params['state'] = $request['token'];
        $params['uid'] = $request['uid'];
        $response = send_request($serverUrl, $params, 'POST', []);
        $res 		= 	json_decode($response['content'],true);			//结果也是一个json格式字符串
        if($res['code']==100||$res['result']['uid']!=$request['uid']){
            $result['code']  = 200;
            $result['msg'] = 'token验证成功';
            $result['data'] = $res;
        }else{
            $result['code']  = 0;
            $result['msg'] = $res['message'];
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
        $orderid = $callback_data['orderid'];
        $uid = $callback_data['uid'];
        $money = $callback_data['money'];
        $gamemoney = $callback_data['gamemoney'];
        $serverid = $callback_data['serverid'];
        $mark = $callback_data['mark'];
        $time = $callback_data['time'];
        $coupon_mark = $callback_data['coupon_mark'];
        $coupon_money = $callback_data['coupon_money'];

        $sign = md5($orderid . $uid .$money . $gamemoney . $serverid . $service_config['secret'].$mark . $time .
            $coupon_mark . $coupon_money);

        if ($sign!=$callback_data['sign']) {
            return ['code'=>0,'msg'=>'订单验签失败'];
        }
        $mSpend = new SpendModel();
        $ff_order_arr  = explode('|',$request['mark']);//分发订单加密后传给渠道的数据
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
        if($money*100!=$mSpendData['pay_amount']*100){//渠道单位元
            return ['code'=>0,'msg'=>'订单金额不匹配'];
        }
        $mSpendData->order_number = $request['orderid'];//渠道订单号
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

    public function get_ff_order_number($user,$game_data,$plat_data,$res)
    {
        $ff_order_number = $res['pay_order_number'].'|'.$game_data['platform_id'];

        return ['code'=>200,'data'=>$ff_order_number];
    }

}
