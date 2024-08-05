<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace app\issuesy\logic\sdk;

use app\issue\logic\GameLogic;
use app\issue\model\IssueGameApplyModel;
use app\issue\model\IssueGameModel;
use app\issue\model\OpenUserModel;
use app\issue\model\PlatformModel;
use app\issue\model\SpendModel;
use app\issue\model\UserModel;
use app\issue\model\UserPlayRoleModel;
use app\issuesy\api\LoginApi;
use think\Db;

/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class PlatformLogic extends BaseLogic
{
    public function channel_pack_status($user,$game_data,$platformData)
    {
        $result['login'] = 1;
        $result['pay'] = 1;
        if ($game_data['game_status'] != 1 || $game_data['apply_status'] !=1  || $game_data['apply_enable_status'] !=1) {
            $result['login'] = 0;
            $result['pay'] = 0;
        }
        if($platformData['plat_status']!=1||$platformData['user_status']!=1){
            $result['login'] = 0;
            $result['pay'] = 0;
        }
        if($platformData['balance']<$platformData['min_balance']){
            $result['pay'] = 0;
        }
        $channel_data = [];
        if(!file_exists(APP_PATH.'issuesy/logic/sdk/Sdk_'.$platformData['controller_name_sy'].'Logic.php')){
            return ['code'=>1004,'msg'=>'平台接口文件错误'];
        }
        $class = '\app\\'.request()->module().'\\logic\\sdk\\Sdk_'.$platformData['controller_name_sy'].'Logic';
        $logic = new $class();
        if(method_exists($logic,'initialization')){
            $channel_data = $logic->initialization($game_data,$platformData);
        }
        $msg = array(
            "code" => 200,
            "login" => $result['login'],
            "pay" => $result['pay'],
            "channel_data" => $channel_data,
        );
        return $msg;
    }
    public function user_login($user,$game_data,$plat_data)
    {
        //不同渠道不同登录操作
        $cslogin = new LoginApi();
        $cslogindata = $cslogin->channel_login_api($user, $game_data,$plat_data);
        if($cslogindata['code']!=200){
            return ['code'=>0,'msg'=>$cslogindata['msg']];
        }
        $user = $cslogindata['data'];
        //渠道方uid
        if ($user['channel_uid'] == '') {
            return ['code'=>0,'msg'=>'渠道用户唯一id不能为空'];
        }
        //登录验证
        $login_verify_param = $user;
        $login_verify_param['user_id'] = $user['channel_uid'];
        $login_verify_res = $cslogin->login_verify($login_verify_param,$game_data,$plat_data);
        if ($login_verify_res['code'] != 200) {
            return ['code'=>0,'msg'=>$login_verify_res['msg']];
        }else{
            $user['login_verify_data'] = $login_verify_res['data']??[];
            $channel_data = $login_verify_res['data'];
        }
        #实例化用户接口
        $result = $cslogin->user_login($user,$game_data,$plat_data);
        if($result['lock_status']!='1'){
            return ['code'=>0,'msg'=>'用户已被锁定'];
        }
        $result['id'] = 'sue_'.$result['id'];
        return ['code'=>200,'msg'=>'登录成功','user_id'=>$result['id'],'channel_data'=>$channel_data,'token'=>$result['token']];
    }
    public function user_role_record($user,$game_data,$plat_data)
    {
        #判断数据是否为空
        if (empty($user['server_id'])||empty($user['role_id'])||empty($user['level'])) {
            return ['code'=>0,'msg'=>'角色数据不能为空'];
        }
        //渠道方uid
        if ($user['channel_uid'] == '') {
            return ['code'=>0,'msg'=>'渠道用户唯一id不能为空'];
        }
        $mUserPlayRole = new UserPlayRoleModel();
        $param['server_id'] = $user['server_id'];
        $param['server_name'] = $user['server_name'];
        $param['role_id'] = $user['role_id'];
        $param['role_name'] = $user['role_name'];
        $param['role_level'] = $user['level'];
        $param['combat_number'] = $user['combat_number']?:'';
        $param['platform_openid'] = $user['channel_uid'];
        $usermodel = new UserModel();
        $userData = $usermodel
            ->field('user_id,lock_status,account,tab_issue_user.platform_id,openid as platform_openid')
            ->where('tab_issue_user.openid','=',$user['channel_uid'])
            ->where('tab_issue_user.platform_id','=',$game_data['platform_id'])
            ->join(['tab_issue_user_play'=>'player'],'player.user_id = tab_issue_user.id')
            ->find();
        if(empty($userData)){
            return ['code'=>0,'msg'=>'未找到平台该玩家'];
        }
        $res = $mUserPlayRole->saveRole($param,$userData,$game_data);
        if($res!==false){
            return ['code'=>200,'msg'=>'角色记录成功'];
        }else{
            return ['code'=>0,'msg'=>'角色记录成功'];
        }
    }

    public function pay_create($user,$game_data,$plat_data){
        if ($user['channel_uid'] == '') {
            return ['code'=>0,'msg'=>'渠道唯一id不能为空'];
        }
        if(empty($user['server_id'])||empty($user['role_id'])||empty($user['level'])){
            return ['code'=>0,'msg'=>'数据不完整或数据类型错误，请检查角色字段server_id、role_id、level'];
        }

        //判断玩家状态
        $usermodel = new UserModel();
        $userData = $usermodel
            ->field('user_id,lock_status,account,tab_issue_user.platform_id,openid as platform_openid,login_extend')
            ->where('tab_issue_user.openid','=',$user['channel_uid'])
            ->where('tab_issue_user.platform_id','=',$game_data['platform_id'])
            ->join(['tab_issue_user_play'=>'player'],'player.user_id = tab_issue_user.id')
            ->find();
        if(empty($userData)||$userData['lock_status']!=1){
            return ['code'=>0,'msg'=>'未找到平台该玩家或已禁用'];
        }
        $user['pay_amount'] = (float)$user['pay_amount'];
        if ($user['pay_amount'] < 0.01) {
            return ['code'=>0,'msg'=>'订单金额不能低于0.01元'];
        }
        //验证预存金余额
        $ratio = $game_data->ratio>0?$game_data->ratio:100;
        if(($plat_data['balance']-($user['pay_amount']-($user['pay_amount']*$ratio/100)))<$plat_data['min_balance'] && $plat_data['settle_type'] == 0){
            return ['code'=>0,'msg'=>'联运币不足，无法支付'];
        }
        //判断游戏订单是否重复
        $mSpend = new SpendModel();
        $mSpend->field('id');
        $mSpend->where('game_id','=',$game_data['game_id']);
        $mSpend->where('extend','=',$user['extend']);
        $mSpendData = $mSpend->find();
        if(!empty($mSpendData)){
            return ['code'=>0,'msg'=>'游戏订单重复'];
        }
        $user['user_id'] = $userData['user_id'];
        $user['platform_openid'] = $user['channel_uid'];
        if(!file_exists(APP_PATH.'issuesy/logic/sdk/Sdk_'.$plat_data['controller_name_sy'].'Logic.php')){
            return ['code'=>1004,'msg'=>'平台接口文件错误'];
        }
        $class = '\app\\'.request()->module().'\\logic\\sdk\\Sdk_'.$plat_data['controller_name_sy'].'Logic';
        $logic = new $class();
        $res = $this->create_order($user,$game_data);
        if($res){
            if($plat_data['settle_type'] > 0){//支付到发行
                if(empty($plat_data['order_notice_url_sy'])){
                    return ['code'=>1020,'msg'=>'未配置创建订单通知地址'];
                }
                return $this->pay_ff($logic,$game_data,$plat_data,$user,$res);
            }
            $get_callback_param['apply_id'] = $user['game_channel_id'];
            $get_callback_param['platform_id'] = $game_data['platform_id'];
            $get_callback_param['open_user_id'] = $game_data['open_user_id'];
            $get_callback_param['settle_type'] = $plat_data['settle_type'];
            $lGame = new GameLogic();
            $get_callback_data = $lGame -> getGameUrl($get_callback_param);
            $callbalc_url = $get_callback_data['data']['callback_url']??'';
            if(!$callbalc_url){
                return ['code'=>0,'msg'=>'支付通知回调地址错误'];
            }

            if(method_exists($logic,'get_ff_order_number')){//渠道个性化
                $ff_order_number_res = $logic->get_ff_order_number($user,$game_data,$plat_data,$res);
                if($ff_order_number_res['code']!=200){
                    return ['code'=>0,'msg'=>'分发订单创建失败'];
                }
                $ff_order_number = $ff_order_number_res['data'];
            }else{
                $ff_order_number = simple_encode(json_encode(['pay_order_number'=>$res['pay_order_number'],'user_id'=>$res['user_id'],'ff_platform'=>$game_data['platform_id']]));
            }
            $channel_data = [];
            if(method_exists($logic,'create_order_extra')){
                $extra_data['ff_order_number'] = $ff_order_number;
                $extra_data['callbalc_url'] = $callbalc_url;
                $channel_data_res = $logic->create_order_extra($user,$game_data,$plat_data,$res,$extra_data);
                if($channel_data_res['code']!=200){
                    return ['code'=>0,'msg'=>$channel_data_res['msg']];
                }
                $channel_data = $channel_data_res['data'];
            }
            return ['code'=>200,'msg'=>'分发订单创建成功','data'=>['channel_data'=>$channel_data,'ff_order'=>$ff_order_number,
                'callbalc_url'=>$callbalc_url,'login_extend'=>$userData['login_extend'],'type'=>0]];
        }else{
            return ['code'=>0,'msg'=>'分发订单创建失败'];
        }
    }

    private function pay_ff($logic,$game_data,$plat_data,$user,$res)
    {
        $game_data = json_decode($game_data['platform_config'],true);
        $game_key = $logic->get_game_key($game_data);
        $pay_data['amount'] = $user['pay_amount'];
        $pay_data['props_name'] =$user['props_name'];
        $pay_data['trade_no'] = $res->pay_order_number;
        $pay_data['user_id'] = $user['platform_openid'];
        $pay_data['game_appid'] = $game_data['gameappid'];
        $pay_data['role_id'] = $user['role_id'];
        $pay_data['role_name'] = $user['role_name'];
        $pay_data['server_id'] = $user['server_id'];
        $pay_data['server_name'] = $user['server_name'];
        $pay_data['level'] = $user['level'];
        $pay_data['sign'] = MD5(implode($pay_data).$game_key);
        //$result = file_get_contents($plat_data['order_notice_url_sy'].'?'.http_build_query($pay_data));
        $result = $this->post($pay_data,$plat_data['order_notice_url_sy']);
        if($result != 'success'){
            return ['code'=>0,'msg'=>'分发订单失败'];
        }else{
            $scheme = $this->request->param('scheme');
            $url = url('issuesy/sdk/pay_way',['order_no'=>$res->pay_order_number,'scheme'=>$scheme],true,true);
            return ['code'=>200,'msg'=>'分发订单创建成功','data'=>['ff_order'=>$url,'login_extend'=>[],'type'=>1]];
        }
    }

    private function create_order($orderData,$gameData,$auto_commit=1)
    {
        $mSpend = new SpendModel();
        $mSpend->startTrans();
        $mSpend->user_id = $orderData['user_id'];
        $mSpend->platform_openid = $orderData['platform_openid'];
        $mSpend->user_account = get_table_entity((new UserModel()),['id'=>$orderData['user_id']],'account')['account'];
        $mSpend->game_id = $gameData['game_id'];
        $mSpend->game_name = $gameData['game_name'];
        $mSpend->server_id = $orderData['server_id'];
        $mSpend->server_name = $orderData['server_name'];
        $mSpend->game_player_id = $orderData['role_id'];
        $mSpend->game_player_name = $orderData['role_name'];
        $mSpend->role_level = $orderData['level'];
        $mSpend->platform_id = $gameData['platform_id'];
        $mSpend->platform_account = get_table_entity(new PlatformModel(),['id'=>$gameData['platform_id']],'account')['account'];
        $mSpend->open_user_id = $gameData['open_user_id'];
        $mSpend->pay_order_number = create_out_trade_no('FF_');
        $mSpend->props_name = $orderData['props_name'];
        $mSpend->pay_amount = $orderData['pay_amount'];
        $mSpend->pay_time = time();
        $mSpend->extra_param = '';
        $mSpend->pay_code = $orderData['pay_code']??'';
        $mSpend->extend = $orderData['extend'];
        $mSpend->spend_ip = request()->ip();
        $mSpend->sdk_version = $gameData['sdk_version'];
        $mSpend->save();
        if($auto_commit){
            $mSpend->commit();
        }
        return $mSpend;
    }

    public function pay_callback($url_data,$callback_data,$request)
    {
        if(empty($request['channel_code'])){
            return (['code'=>0,'msg'=>'缺少分发平台参数']);
        }
        if(empty($request['game_id'])){
            return (['code'=>0,'msg'=>'缺少游戏数据']);
        }
        //获取游戏信息
        $mGame = new IssueGameModel();
        $mGame->field('tab_issue_game.id as game_id,tab_issue_game.sdk_version,game_appid,tab_issue_game.game_name,tab_issue_game.status as online_status,enable_status,game_key,access_key,agent_id,platform_config,service_config,platform_id,open_user_id,login_notify_url,pay_notify_url,apply.ratio');
        $mGame->where('tab_issue_game.id','=',$request['game_id']);
        $mGame->where('platform_id','=',$request['channel_code']);
        $mGame->join(['tab_issue_game_apply'=>'apply'],'apply.game_id=tab_issue_game.id');
        $mGameData = $mGame->find();
        if(empty($mGameData)){//不存在该分发
            return (['code'=>0,'msg'=>'平台未申请该游戏']);
        }
        $mPlatform = new  PlatformModel();
        $platformData = $mPlatform
            ->alias('plat')
            ->field('plat.id,plat.open_user_id,plat.platform_config_h5,plat.controller_name_sy,plat.service_config,plat.controller_name_h5,plat.status,user.balance,min_balance')
            ->where('plat.id','=',$request['channel_code'])
            ->join(['tab_issue_open_user'=>'user'],'user.id=plat.open_user_id')
            ->find();
        if(empty($platformData['controller_name_sy'])){
            return ['code'=>0,'msg'=>'平台接口文件未配置'];
        }
        $class_name = 'Sdk_'.strtolower($platformData['controller_name_sy']);
        $class = '\app\issuesy\logic\sdk\\'.$class_name.'Logic';
        if(class_exists($class)){
            $logic = new $class();
            $res = $logic->pay_callback($request,$mGameData,$callback_data);
        }else{
            return ['code'=>0,'msg'=>'平台接口文件不存在'];
        }
        if($res['code']==200){
            return $this->callback_game($request,$mGameData,$res['spend_data'],$platformData);
        }else{
            return $res;
        }
    }

    /**
     * @descript
     * @param $request
     * @param $applydata
     * @param $mSpendData 分发订单数据
     * @param $platformData
     *
     * @return array|int[]
     * @since    2021/8/12 10:10
     * @author  yyh <1010707499@qq.com>
     */
    private function callback_game($request,$applydata,$mSpendData,$platformData)
    {
        //扣除预付款 第一次修改支付状态时扣除，再次通知不扣除
        if($mSpendData['pay_status']!=1){
            $deduction_res = $this->deduction($mSpendData,$applydata,$platformData,$request);
            if($deduction_res['code']!=200){
                return ($deduction_res);
            }
        }
        $gameInfo = explode('_xgsdk_',$mSpendData['extend']);

        //判断是否绑定渠道,获取绑定渠道id
        $mUser = new UserModel();
        $userData = $mUser->field('id,parent_id')->where(['id'=>$mSpendData->user_id])->find();
        if(!empty($userData['parent_id'])){
            $mSpendData->user_id = $userData->parent_id;
        }

        //通知cp
        $data = array(
            "game_order" => $gameInfo[0],//游戏订单
            "out_trade_no" => $mSpendData['pay_order_number'],
            'pay_extra' => request()->host(),
            "pay_status" => 1,
            "price" => $mSpendData['pay_amount'],
            "user_id" => 'sue_'.$mSpendData['user_id'],
        );
        $datasign = implode($data);
        $md5_sign = md5($datasign.$applydata['game_key']);
        $data['sign'] = $md5_sign;
        if(!empty($gameInfo[1])){
            $data['game_extend'] = $gameInfo[1];
        }
        $result = $this->post($data, $applydata['pay_notify_url']);
        $class_name = 'Sdk_'.strtolower($platformData['controller_name_sy']);
        $class = '\app\issuesy\logic\sdk\\'.$class_name.'Logic';
        $logic = new $class();
        if ($result == "success"||json_decode($result,true)['status'] == "success") {
            $res = $this->update_game_pay_status($mSpendData);
            if($res==true){
                if(method_exists($logic,'callback_result')){
                    return $logic->callback_result(1);
                }else{
                    echo '{"status":"success"}';exit;
                }
            }else{
                if(method_exists($logic,'callback_result')){
                    return $logic->callback_result(0);
                }else{
                    return (['code'=>0,'msg'=>'游戏发货失败']);
                }
            }
        } else {
            \think\Log::record("游戏分发支付通知信息：" . $result . ";游戏通知地址：" . $applydata['pay_notify_url']);
            if(method_exists($logic,'callback_result')){
                return $logic->callback_result(0);
            }
            return (['code'=>0,'msg'=>'游戏发货失败']);
        }
    }
    //修改支付状态、扣除预付款 余额判断等
    private function deduction($mSpendData,$applydata,$platdata,$request)
    {
        //余额判断
        $ratio = $applydata->ratio>0?$applydata->ratio:100;
        if(($platdata['balance']-($mSpendData['pay_amount']-($mSpendData['pay_amount']*$ratio/100)))<$platdata['min_balance']){
            return ['code'=>0,'msg'=>'联运币不足，无法支付'];
        }
        Db::startTrans();
        $mSpendData->pay_status = 1;
        $mSpendData->save();
        (new OpenUserModel())->where('id','=',$applydata['open_user_id'])->setDec('balance',$mSpendData['pay_amount']-($mSpendData['pay_amount']*$ratio/100));
        (new PlatformModel())->where('id','=',$platdata['id'])->setInc('total_pay',$mSpendData['pay_amount']);
        $save['dec_balance'] = $mSpendData['pay_amount']-($mSpendData['pay_amount']*$ratio/100);
        $save['ratio'] = $ratio;
        $save['is_check'] = 1;
        (new SpendModel())->where('id','=',$mSpendData['id'])->update($save);
        (new IssueGameApplyModel())->where('game_id','=',$applydata['game_id'])->where('platform_id','=',$platdata['id'])->setInc('total_pay',$mSpendData['pay_amount']);
        (new IssueGameModel())->where('id','=',$applydata['game_id'])->setInc('total_pay',$mSpendData['pay_amount']);
        (new UserModel())->where('id','=',$mSpendData->user_id)->setInc('cumulative',$mSpendData['pay_amount']);
        Db::commit();
        return ['code'=>200];
    }
    /**
     *修改游戏支付状态
     */
    private function update_game_pay_status($mSpendData = "")
    {
        $mSpendData->pay_game_status = 1;
        $mSpendData->update_time = time();
        $mSpendData->callback_ip = request()->ip();
        $mSpendData->save();
        return true;
    }

    /**
     *post提交数据
     */
    protected function post($param, $url)
    {
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
}
