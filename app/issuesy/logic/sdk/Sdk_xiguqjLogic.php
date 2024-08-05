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
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class Sdk_xiguqjLogic
{
    //安卓溪谷sdk
    //以sdkverson 判断SDK版本
    public function login_verify($request='',$game_data,$plat_data){
        $url               = 'http://qj.vlcms.com/sdk/login_notify/login_verify';
        $params['user_id'] = $request['user_id'];
        $params['token']   = $request['token'];
        /**
         *post提交数据
         */
        $res = json_decode($this->post($params, $url), true);
        if ($res['code'] == 200){
            $result['code']  = 200;
            $result['msg'] = 'token验证成功';
        } else{
            $result['code']  = 0;
            $result['msg'] = $res['msg'];
        }
        return ($result);
    }
    public function user_login(array $request,Model $apply_data)
    {
        $data['user_id'] = $request['channel_uid'];
        $data['birthday'] = $request['birthday'];
        $data['platform_id'] = $apply_data['platform_id'];
        $data['game_id'] = $apply_data['game_id'];
        $data['login_extend'] = $request['login_extend']??'';
        $data['equipment_num'] = $request['equipment_num'] ?? '';
        $data['open_user_id'] = $apply_data['open_user_id'] ?? '';
        $user = (new UserModel())->user_login($data);
        $token = ['user_id'=>$user->id,'ip'=>request()->ip(),'open_user_id'=>$apply_data['open_user_id'],'ff_platform'=>$apply_data['platform_id'],'host'=>request()->host(),'time'=>time()];
        $user->token = simple_encode(json_encode($token));
        return $user;
    }

    public function pay_callback($request,$applydata,$callback_data)
    {
        $data = array(
            "game_order" => $request['game_order'],
            "out_trade_no" => $request['out_trade_no'],
            'pay_extra' => $request['pay_extra'],
            "pay_status" => $request['pay_status'],
            "price" => $request['price'],
            "user_id" => $request['user_id'],
        );
        $datasign = implode($data);
        $game_key = json_decode($applydata['service_config'],true)['game_key'];
        $md5_sign = md5($datasign.$game_key);
        if($md5_sign!=$request['sign']){
            return ['code'=>0,'msg'=>'订单验签失败'];
        }
        $mSpend = new SpendModel();
        $ff_order  = json_decode(simple_decode($request['game_order']),true);//分发订单加密后传给渠道的数据
        if(empty($ff_order)||$ff_order['ff_platform']!=$request['channel_code']){
            return ['code'=>0,'msg'=>'订单不合法'];
        }
        $mSpend->where('pay_order_number','=',$ff_order['pay_order_number']);
        $mSpendData = $mSpend->find();
        if(empty($mSpendData)||$mSpendData['platform_id']!=$ff_order['ff_platform']){
            return ['code'=>0,'msg'=>'订单不存在'];
        }
        if($mSpendData['pay_status']==1&&$mSpendData['pay_game_status']==1){
            echo '{"status":"success"}';exit;
        }
        if($request['price']!=$mSpendData['pay_amount']){
            return ['code'=>0,'msg'=>'订单金额不匹配'];
        }
        $mSpendData->order_number = $request['out_trade_no'];//渠道订单号
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
