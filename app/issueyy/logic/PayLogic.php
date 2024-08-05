<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-08
 */

namespace app\issueyy\logic;

use app\issue\model\IssueGameApplyModel;
use app\issue\model\IssueGameModel;
use app\issue\model\OpenUserModel;
use app\issue\model\PlatformModel;
use app\issue\model\SpendModel;
use app\issue\model\UserModel;
use cmf\controller\HomeBaseController;
use think\weixinsdk\Weixin;
use think\Db;
use think\Model;

class PayLogic extends  HomeBaseController
{
//    public function pay_init(array $params)
//    {
//        if((time()-$params['timestamp'])>5){
//            return ['code'=>1005,'msg'=>'验签超时'];
//        }
//        $channelExt = json_decode(simple_decode($params['channelExt']),true);
//        if(empty($channelExt)){
//            return ['code'=>1006,'msg'=>'登录透传数据丢失'];
//        }
//        //判断数据
//        if(empty($params['server_id'])||empty($params['role_id'])||empty($params['role_level'])){
//            return ['code'=>1011,'msg'=>'数据不完整或数据类型错误，请检查角色字段server_id、role_id、role_level'];
//        }
//        if(NULL==$channelExt['ff_platform']){
//            return ['code'=>1007,'msg'=>'当前非分发状态'];
//        }
//        //判断平台状态
//        $mPlatform = new  PlatformModel();
//        $platformData = $mPlatform
//            ->alias('plat')
//            ->field('plat.id,plat.open_user_id,plat.platform_config_h5,plat.service_config,plat.controller_name_h5,plat.status,user.balance,min_balance,user.settle_type,plat.order_notice_url_h5')
//            ->where('plat.id','=',$channelExt['ff_platform'])
//            ->join(['tab_issue_open_user'=>'user'],'user.id=plat.open_user_id')
//            ->find();
//        if($platformData['status']!=1){
//            return ['code'=>1014,'msg'=>'分发平台状态已关闭'];
//        }
//        //判断玩家状态
//        $params['user_id'] = substr($params['user_id'],4);
//        $usermodel = new UserModel();
//        $userData = $usermodel
//                    ->field('user_id,lock_status,account,tab_issue_user.platform_id,openid as platform_openid')
//                    ->where('tab_issue_user.id','=',$params['user_id'])
//                    ->where('tab_issue_user.platform_id','=',$channelExt['ff_platform'])
//                    ->join(['tab_issue_user_play'=>'player'],'player.user_id = tab_issue_user.id')
//                    ->find();
//        if(empty($userData)||$userData['lock_status']!=1){
//            return ['code'=>1008,'msg'=>'未找到平台该玩家或已禁用'];
//        }
//        $params['platform_openid'] = $userData['platform_openid'];
//
//        //判断充值金额
//        $params['amount'] = (float) $params['amount'];
//        if ($params['amount'] < 100) {
//            //return ['code'=>1009,'msg'=>'订单金额不能低于限额1元'];
//        }
//        //获取游戏信息
//        $mGame = new IssueGameModel();
//        $mGame->field('tab_issue_game.id as game_id,tab_issue_game.sdk_version,game_appid,tab_issue_game.game_name,tab_issue_game.status as online_status,enable_status,game_key,access_key,agent_id,platform_config,platform_id,open_user_id,apply.ratio');
//        $mGame->where('game_appid','=',$params['game_appid']);
//        $mGame->where('platform_id','=',$channelExt['ff_platform']);
//        $mGame->join(['tab_issue_game_apply'=>'apply'],'apply.game_id=tab_issue_game.id');
//        $mGameData = $mGame->find();
//
//        $ratio = $mGameData->ratio>0?$mGameData->ratio:100;
//        if(($platformData['balance']-($params['amount']/100-($params['amount']/100*$ratio/100)))<$platformData['min_balance'] && empty($platformData['settle_type'])){
//            return ['code'=>1019,'msg'=>'联运币不足，无法支付'];
//        }
//
//        //判断游戏订单是否重复
//        $mSpend = new SpendModel();
//        $mSpend->field('id');
//        $mSpend->where('game_id','=',$mGameData['game_id']);
//        $mSpend->where('extend','=',$params['trade_no']);
//        $mSpendData = $mSpend->find();
//        if(!empty($mSpendData)){
//            return ['code'=>1012,'msg'=>'游戏订单重复'];
//        }
//        if(empty($mGameData)||$mGameData['online_status']!=1||$mGameData['enable_status']!=1){
//            return ['code'=>1010,'msg'=>'游戏已下架'];
//        }
//        $data = [];
//        $data['amount'] = $params['amount'];
//        $data['props_name'] = $params['props_name'];
//        $data['trade_no'] = $params['trade_no'];
//        $data['user_id'] = 'sue_'.$params['user_id'];
//        $data['game_appid'] = $params['game_appid'];
//        $data['channelExt'] = $params['channelExt'];
//        $data['timestamp'] = $params['timestamp'];
//        $game_key = $mGameData['game_key'];
//        $data['sign'] = (new \app\sdkh5\logic\GameLogic()) -> h5SignData($data, $game_key);
//        if ($data['sign'] != $params['sign']) {
//            return ['code'=>1003,'msg'=>'充值验签失败'];
//        }
//        //创建订单
//        $res = $this->create_order($params,$mGameData);
//        if($res){
//            $orderData = $mSpend::get($res);
//            if(!empty($platformData['controller_name_h5'])){
//                if(!file_exists(APP_PATH.'issueh5/logic/pt/'.$platformData['controller_name_h5'].'Logic.php')){
//                    return json(['code'=>1004,'msg'=>'平台接口文件错误']);
//                }
//                $class = '\app\\'.request()->module().'\\logic\\pt\\'.$platformData['controller_name_h5'].'Logic';
//                $logic = new $class();
//                $pay_data = $logic->pull_pay($orderData,$platformData,$mGameData,$params);
//                if($platformData['settle_type'] > 0){
//                    if(empty($platformData['order_notice_url_h5'])){
//                        return ['code'=>1020,'msg'=>'未配置创建订单通知地址'];
//                    }
//                    $url = $platformData['order_notice_url_h5'].'?'.http_build_query($pay_data);
//                    $result = file_get_contents($url);
//                    if($result != 'success'){
//                        return ['code'=>1021,'msg'=>'创建订单失败'];
//                    }
//                }
//            }else{//平台接联运分发
//
//            }
//            return ['code'=>200,'msg'=>'分发订单创建成功','type'=>$platformData['settle_type'],'data'=>$pay_data];
//        }else{
//            return ['code'=>1013,'msg'=>'分发订单创建失败'];
//        }
//    }





    public function create_order($orderData,$gameData)
    {
        $mSpend = new SpendModel();
        $mSpend->user_id = $orderData['user_id'];
        $mSpend->platform_openid = $orderData['platform_openid'];
        $mSpend->user_account = get_table_entity((new UserModel()),['id'=>$orderData['user_id']],'account')['account'];
        $mSpend->game_id = $gameData['game_id'];
        $mSpend->game_name = $gameData['game_name'];
        $mSpend->server_id = $orderData['server_id'];
        $mSpend->server_name = $orderData['server_name']?:'';
        $mSpend->game_player_id = $orderData['role_id']?:'';
        $mSpend->game_player_name = $orderData['role_name']?:'';
        $mSpend->role_level = $orderData['role_level']?:'';
        $mSpend->platform_id = $gameData['platform_id'];
        $mSpend->platform_account = get_table_entity(new PlatformModel(),['id'=>$gameData['platform_id']],'account')['account'];
        $mSpend->open_user_id = $gameData['open_user_id'];
        $mSpend->pay_order_number = create_out_trade_no('FF_');
        $mSpend->props_name = $orderData['props_name']?:'充值';
        $mSpend->pay_amount = $orderData['money'];
        $mSpend->pay_time = time();
        $mSpend->extra_param = $orderData['channelExt']?:'';
        $mSpend->extend = $orderData['game_order']?:'';
        $mSpend->spend_ip = request()->ip();
        $mSpend->sdk_version = $gameData['sdk_version'];
        $mSpend->save();
        return $mSpend->getLastInsID();
    }

    public function callback($request)
    {
        if(empty($request['channel_code'])){
            return (['code'=>1001,'msg'=>'缺少分发平台参数']);
        }
        if(empty($request['game_id'])){
            return (['code'=>1002,'msg'=>'缺少游戏数据']);
        }
        //获取游戏信息
        $mGame = new IssueGameModel();
        $mGame->field('tab_issue_game.id as game_id,tab_issue_game.sdk_version,game_appid,tab_issue_game.game_name,tab_issue_game.status as online_status,enable_status,cp_game_id,interface_id,platform_config,platform_id,open_user_id,login_notify_url,pay_notify_url,apply.ratio');
        $mGame->where('tab_issue_game.id','=',$request['game_id']);
        $mGame->where('platform_id','=',$request['channel_code']);
        $mGame->join(['tab_issue_game_apply'=>'apply'],'apply.game_id=tab_issue_game.id');
        $mGameData = $mGame->find();
        if(empty($mGameData)){//不存在该分发
            return (['code'=>1015,'msg'=>'平台未申请该游戏']);
        }
        $mPlatform = new  PlatformModel();
        $platformData = $mPlatform
            ->alias('plat')
            ->field('plat.id,plat.open_user_id,plat.platform_config_yy,plat.service_config,plat.controller_name_yy,plat.status,user.balance,min_balance')
            ->where('plat.id','=',$request['channel_code'])
            ->join(['tab_issue_open_user'=>'user'],'user.id=plat.open_user_id')
            ->find();
        if(empty($platformData['controller_name_yy'])) {//平台接联运分发
            return (['code'=>1000,'msg'=>'系统错误']);
        }
        if (!file_exists(APP_PATH . 'issueyy/logic/pt/' . $platformData['controller_name_yy'] . 'Logic.php')) {
            return (['code' => 1004, 'msg' => '平台接口文件错误']);
        }
        $class = '\app\\' . request()->module() . '\\logic\\pt\\' . $platformData['controller_name_yy'] . 'Logic';
        $logic = new $class();
        $res = $logic->callback($request,$mGameData);
        if($res['code'] != 200){
            return $res;
        }
        $usermodel = new UserModel();
        $userData = $usermodel
                ->field('user_id,lock_status,account,tab_issue_user.platform_id,openid as platform_openid')
                ->where('tab_issue_user.openid','=',$request['user_id'])
                ->where('tab_issue_user.platform_id','=',$request['channel_code'])
                ->join(['tab_issue_user_play'=>'player'],'player.user_id = tab_issue_user.id')
                ->find();
        $request['platform_openid'] = $userData['platform_openid'];

        $mSpend = new SpendModel();
        $mSpend->where('extend',$request['game_order']);
        $mSpendData = $mSpend->find();
        if(empty($mSpendData)){
            $request['user_id'] = $userData['user_id'];
            $request['server_name'] = get_server_name($request['server_id']);
            $res = $this->create_order($request,$mGameData);
            $mSpendData = $mSpend::get($res);
        }else{
            if($mSpendData['pay_status']==1&&$mSpendData['pay_game_status']==1){
                return 1;
            }
        }
        $request['user_id'] = $userData['user_id'];
        return $this->callback_game($request,$mGameData,$mSpendData,$platformData);
    }

    /**
     * @函数或方法说明
     * @分发平台通知
     * @author: 郭家屯
     * @since: 2020/10/23 11:24
     */
    public function pay_ff_notice($spend=[])
    {
        //获取游戏设置信息
        $platform = new \app\issue\model\PlatformModel();
        $platform->where('id',$spend['platform_id']);
        $platform->field('pay_notice_url_yy,controller_name_yy');
        $platformdata = $platform->find();
        if (empty($platformdata)) {
            return false;
        }
        if (empty($platformdata->pay_notice_url_yy)) {
            return false;
        }
        //获取游戏设置信息
        $platformapplymodel = new IssueGameApplyModel();
        $platformapplymodel->field('platform_config');
        $platformapplymodel->where('game_id',$spend['game_id']);
        $platformapplymodel->where('platform_id',$spend['platform_id']);
        $platformapplymodel->where('open_user_id',$spend['open_user_id']);
        $game_data = $platformapplymodel->find();
        $game_data = json_decode($game_data->platform_config,true);
        if(!file_exists(APP_PATH.'issueyy/logic/pt/'.$platformdata['controller_name_yy'].'Logic.php')){
            return false;
        }
        $class = '\app\\issueyy\\logic\\pt\\'.$platformdata['controller_name_yy'].'Logic';
        $logic = new $class();
        $game_data['game_key'] = $logic->get_pay_key($game_data);
        $data = array(
                "out_trade_no" => $spend['pay_order_number'],
                'pay_extra' => request()->host(),
                "pay_status" => 1,
                "pay_game_status" => $spend['pay_game_status'],
                "price" => $spend['pay_amount'],
                "user_id" => $spend['platform_openid'],
                "pay_way" => $spend['pay_way']
        );
        $data['sign'] = md5($data['out_trade_no'].$data['pay_extra'].$data['pay_status'].$data['pay_game_status'].$data['price'].$data['user_id'].$data['pay_way'].$game_data['game_key']);
        //file_put_contents(dirname(__FILE__).'/result.txt',$platformdata['pay_notice_url_yy']."?".http_build_query($data));
        //$result = file_get_contents($platformdata['pay_notice_url_h5']."?".http_build_query($data));
        $result = $this->post($data, $platformdata['pay_notice_url_yy']);
        if ($result == "success"||json_decode($result,true)['status'] == "success") {
            return true;
        } else {
            return false;
        }
    }

    private function callback_game($request,$applydata,$mSpendData,$platformData)
    {
        //扣除预付款 第一次修改支付状态时扣除，再次通知不扣除
        if($mSpendData['pay_status']!=1){
            $deduction_res = $this->deduction($mSpendData,$applydata,$platformData);
            if($deduction_res['status']!=200){
                return ($deduction_res);
            }
        }
        //通知cp
        $interface = Db::table('tab_game_interface')->field('tag')->where('id',$applydata['interface_id'])->find();
        if(empty($interface)){
            return ['status'=>-100,'msg'=>'CP接口错误'];
        }
        $server = Db::table('tab_game_server')->field('server_num')->where('id',$request['server_id'])->find();
        if(empty($server)){
            return ['status'=>-101,'msg'=>'区服不存在'];
        }
        $controller_game_name = "\app\sdkyy\api\\".$interface['tag'];
        $gamecontroller = new $controller_game_name;
        $res = $gamecontroller->pay($mSpendData['pay_order_number'],$mSpendData['pay_amount'],$applydata['cp_game_id'],$server['server_num'],'sue_'.$request['user_id']);
        if($res['status'] == 1){
            $this->update_game_pay_status($mSpendData);
            return 1;
        }
        return $res;
    }

    //H5加密方式
    protected function h5SignData($data, $game_key)
    {
        ksort($data);
        foreach ($data as $k => $v) {
            $tmp[] = $k . '=' .urlencode($v);
        }
        $str = implode('&', $tmp) . $game_key;
        //file_put_contents(dirname(__FILE__)."/check.txt",$str);
        return md5($str);
    }
    //修改支付状态、扣除预付款 余额判断等
    private function deduction($mSpendData,$applydata,$platdata)
    {
        //余额判断
        $ratio = $applydata->ratio>0?$applydata->ratio:100;
        if(($platdata['balance']-($mSpendData['pay_amount']-($mSpendData['pay_amount']*$ratio/100)))<$platdata['min_balance']){
            return ['status'=>1019,'msg'=>'联运币不足，无法支付'];
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
        return ['status'=>200];
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

    /**
     * @函数或方法说明
     * @分发分成模式微信支付创建订单
     * @author: 郭家屯
     * @since: 2020/11/11 16:14
     */
    public function get_ff_yygame_pay($data=[])
    {
        //平台状态
        $mPlatform = new  PlatformModel();
        $platformData = $mPlatform->field('id,account,open_user_id,platform_config_yy,service_config,controller_name_yy,order_notice_url_yy,pay_notice_url_yy,status')->where('id','=',$data['channel_code'])->find();
        $data['platform_account'] = $platformData['account'];
        if($platformData['status']!=1){
            return ['err'=>1,'msg'=>'分发平台状态已关闭'];
        }
        if(empty($platformData['order_notice_url_yy']) || empty($platformData['pay_notice_url_yy'])){
            return ['err'=>1,'msg'=>'平台配置支付地址设置错误'];
        }
        //用户状态
        $mOpenUser = new OpenUserModel();
        $openUserData = $mOpenUser -> field('id,account,auth_status,status,settle_type') -> where('id', '=', $platformData['open_user_id']) -> find();
        if ($openUserData['status'] != 1 || $openUserData['auth_status'] != 1) {
            return ['err'=>1,'msg'=>'平台用户已锁定'];
        }
        $usermodel = new UserModel();
        $userData = $usermodel
                ->field('user_id,lock_status,account,tab_issue_user.platform_id,openid as platform_openid')
                ->where('tab_issue_user.id','=',$data['user_id'])
                ->join(['tab_issue_user_play'=>'player'],'player.user_id = tab_issue_user.id')
                ->find();
        $data['platform_openid'] = $userData['platform_openid'];
        $data['account'] = $userData['account'];
        if(empty($userData)){
            return ['err'=>1,'msg'=>'用户不存在，请刷新游戏后扫码'];
        }
        $data['money'] = (float)$data['money'];
        if ($data['money'] < 0.01) {
            return ['err'=>1,'msg'=>'金额不能低于0.01元'];
        }
        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $data['pay_way'] = 3;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        $data['cost'] = $data['money'];
        $body = "游戏充值";
        $title = "游戏币";
        //获取游戏信息
        $mGame = new IssueGameModel();
        $mGame->field('tab_issue_game.id as game_id,tab_issue_game.sdk_version,game_appid,tab_issue_game.game_name,tab_issue_game.status,enable_status,cp_game_id,interface_id,platform_config,platform_id,open_user_id,login_notify_url,pay_notify_url,apply.ratio');
        $mGame->where('tab_issue_game.id','=',$data['game_id']);
        $mGame->where('platform_id','=',$data['channel_code']);
        $mGame->join(['tab_issue_game_apply'=>'apply'],'apply.game_id=tab_issue_game.id');
        $mGameData = $mGame->find();
        if(empty($mGameData)){
            return ['err'=>1,'msg'=>'游戏不存在，请刷新游戏后扫码'];
        }
        if($mGameData['status']!=1){
            return ['err'=>1,'msg'=>'游戏已下架'];
        }
        $data['game_name'] = $mGameData['game_name'];
        $data['server_name'] = get_server_name($data['server_id']);
        $spend_order = $this->create_order($data,$mGameData);
        if(!$spend_order){
            return ['err'=>1,'msg'=>'创建订单失败'];
        }
        $spendmodel = new SpendModel();
        $spendDate = $spendmodel::get($spend_order);
        $config = json_decode($mGameData['platform_config'],true);
        $url = $platformData['order_notice_url_yy'];
        $param['trade_no'] = $spendDate['pay_order_number'];
        $param['gid'] = $data['game_id'];
        $param['uid'] = $data['platform_openid'];
        $param['sid'] = $data['server_id'];
        $param['amount'] = $data['money'];
        $param['game_appid'] = $config['game_appid'];
        $param['time'] = time();
        $controller_name = $platformData['controller_name_yy'];
        $class = '\app\\issueyy\\logic\\pt\\'.$controller_name.'Logic';
        $logic = new $class();
        $pay_key = $logic->get_pay_key($config);
        $param['sign'] = md5($param['trade_no'].$param['gid'].$param['uid'].$param['sid'].$param['amount'].$param['game_appid'].$param['time'].$pay_key );
        $result = $this->post($param,$url);
        if($result != 'success'){
            return ['err'=>1,'msg'=>'创建订单失败'];
        }
        $spendDate['title'] = $title;
        $spendDate['err'] = 0;
        return $spendDate;
    }
}