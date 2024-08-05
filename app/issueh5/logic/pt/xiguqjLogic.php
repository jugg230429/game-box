<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-08
 */

namespace app\issueh5\logic\pt;

use app\api\GameApi;
use app\issue\model\IssueGameModel;
use app\issue\model\PlatformModel;
use app\issue\model\SpendModel;
use app\issue\model\UserModel;
use cmf\controller\HomeBaseController;
use think\Model;

class xiguqjLogic extends  HomeBaseController
{
    public function check_sign(array $request,Model $apply_data)
    {
        $data['channelExt'] = $request['channelExt'];
        $data['agent_id'] = $request['agent_id'];
        $data['game_appid'] = $request['game_appid'];
        $data['timestamp'] = $request['timestamp'];
        $data['loginplatform2cp'] = $request['loginplatform2cp'];
        $data['user_id'] = $request['user_id'];
        $data['birthday'] = $request['birthday'];
        $game_key = json_decode($apply_data['platform_config'],true)['game_key'];
        $sign = $this->h5SignData($data,$game_key);
        if((time()-$data['timestamp']>5)){
            return ['code'=>1005,'msg'=>'验签超时'];
        }
        if($request['sign']!=$sign){
            return false;
        }else{
            return true;
        }
    }

    public function user_login(array $request,Model $apply_data)
    {
        $data['user_id'] = $request['user_id'];
        $data['birthday'] = $request['birthday'];
        $data['platform_id'] = $request['channel_code'];
        $data['game_id'] = $request['game_id'];
        $user = (new UserModel())->user_login($data);
        return $user;
    }

    public function pull_pay($orderData,$platformData,$gameData,$request)
    {
        $game_key = json_decode($gameData['platform_config'],true)['game_key'];
        $data = [];
        $data['amount'] = (int)($orderData['pay_amount']*100);
        $data['props_name'] = $orderData['props_name'];
        $data['trade_no'] = $orderData['pay_order_number'];
        $data['user_id'] = $orderData['platform_openid'];
        $data['game_appid'] = $request['login_game_appid'];
        $data['channelExt'] = $request['login_extend'];
        $data['timestamp'] = time();
        $sign = $this->h5SignData($data,$game_key);
        $data['server_id'] = $orderData['server_id'];
        $data['server_name'] = $orderData['server_name'];
        $data['role_id'] = $orderData['game_player_id'];
        $data['role_name'] = $orderData['game_player_name'];
        $data['role_level'] = $orderData['role_level'];
        $data['sign'] = $sign;
        return $data;
    }

    public function pull_role($roleData,$platformData,$gameData,$request)
    {
        $signdata = [];
        $signdata['user_id'] = $roleData['platform_openid'];
        $signdata['game_appid'] = $request['login_game_appid'];
        $signdata['server_id'] = $roleData['server_id'];
        $signdata['server_name'] = $roleData['server_name'];
        $signdata['role_id'] = $roleData['role_id'];
        $signdata['role_name'] = $roleData['role_name'];
        $signdata['role_level'] = $roleData['role_level'];
        $signdata['combat_number'] = $roleData['combat_number'];
        $signdata['channelExt'] = $request['channelExt'];
        $signdata['timestamp'] = time();
        $game_key = json_decode($gameData['platform_config'],true)['game_key'];
        $sign = $this->h5SignData($signdata,$game_key);
        $signdata['sign'] = $sign;
        return $signdata;
    }

    public function callback($request,$applydata)
    {
        $data = array(
            "game_order" => $request['game_order'],
            "out_trade_no" => $request['out_trade_no'],
            'pay_extra' => $request['pay_extra'],
            "pay_status" => $request['pay_status'],
            "price" => $request['price'],
            "user_id" => $request['user_id'],
        );
        $game_key = json_decode($applydata['platform_config'],true)['game_key'];
        $md5_sign = $this->h5SignData($data,$game_key);
        if($md5_sign!=$request['sign']){
            return ['code'=>1003,'msg'=>'订单验签失败'];
        }
        $mSpend = new SpendModel();
        $mSpend->where('pay_order_number','=',$request['game_order']);
        $mSpendData = $mSpend->find();
        if(empty($mSpendData)){
            return ['code'=>1016,'msg'=>'订单不存在'];
        }
        if($mSpendData['pay_status']==1&&$mSpendData['pay_game_status']==1){
            echo '{"status":"success"}';exit;
        }
        if($request['price']!=$mSpendData['pay_amount']){
            return ['code'=>1017,'msg'=>'订单金额不匹配'];
        }
        $mSpendData->order_number = $request['out_trade_no'];
        return ['code'=>200,'spend_data'=>$mSpendData];
    }

    /**
     * @H5游戏签名加密方法
     *
     * @author: zsl
     * @since: 2020/6/12 17:49
     */
    private function h5SignData($data, $game_key)
    {
        ksort($data);
        foreach ($data as $k => $v) {
            $tmp[] = $k . '=' . urlencode($v);
        }
        $str = implode('&', $tmp) . $game_key;
        return md5($str);
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