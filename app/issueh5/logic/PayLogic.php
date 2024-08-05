<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-08
 */

namespace app\issueh5\logic;

use app\issue\model\IssueGameApplyModel;
use app\issue\model\IssueGameModel;
use app\issue\model\OpenUserModel;
use app\issue\model\PlatformModel;
use app\issue\model\SpendModel;
use app\issueh5\model\GameApplyModel;
use app\issue\model\UserModel;
use cmf\controller\HomeBaseController;
use think\weixinsdk\Weixin;
use think\Db;
use think\Model;

class PayLogic extends  HomeBaseController
{
    public function pay_init(array $params)
    {
        if((time()-$params['timestamp'])>5){
            return ['code'=>1005,'msg'=>'验签超时'];
        }
        $channelExt = json_decode(simple_decode($params['channelExt']),true);
        if(empty($channelExt)){
            return ['code'=>1006,'msg'=>'登录透传数据丢失'];
        }
        //判断数据
        if(empty($params['server_id'])||empty($params['role_id'])||empty($params['role_level'])){
            return ['code'=>1011,'msg'=>'数据不完整或数据类型错误，请检查角色字段server_id、role_id、role_level'];
        }
        if(NULL==$channelExt['ff_platform']){
            return ['code'=>1007,'msg'=>'当前非分发状态'];
        }
        //判断平台状态
        $mPlatform = new  PlatformModel();
        $platformData = $mPlatform
            ->alias('plat')
            ->field('plat.id,plat.open_user_id,plat.platform_config_h5,plat.service_config,plat.controller_name_h5,plat.status,user.balance,min_balance,user.settle_type,plat.order_notice_url_h5')
            ->where('plat.id','=',$channelExt['ff_platform'])
            ->join(['tab_issue_open_user'=>'user'],'user.id=plat.open_user_id')
            ->find();
        if($platformData['status']!=1){
            return ['code'=>1014,'msg'=>'分发平台状态已关闭'];
        }
        //判断玩家状态
        $params['user_id'] = substr($params['user_id'],4);
        $usermodel = new UserModel();
        $userData = $usermodel
                    ->field('user_id,lock_status,account,tab_issue_user.platform_id,openid as platform_openid')
                    ->where('tab_issue_user.id','=',$params['user_id'])
                    ->where('tab_issue_user.platform_id','=',$channelExt['ff_platform'])
                    ->join(['tab_issue_user_play'=>'player'],'player.user_id = tab_issue_user.id')
                    ->find();
        if(empty($userData)||$userData['lock_status']!=1){
            return ['code'=>1008,'msg'=>'未找到平台该玩家或已禁用'];
        }
        $params['platform_openid'] = $userData['platform_openid'];

        //判断充值金额
        $params['amount'] = (float) $params['amount'];
        if ($params['amount'] < 100) {
            //return ['code'=>1009,'msg'=>'订单金额不能低于限额1元'];
        }
        //获取游戏信息
        $mGame = new IssueGameModel();
        $mGame->field('tab_issue_game.id as game_id,tab_issue_game.sdk_version,game_appid,tab_issue_game.game_name,tab_issue_game.status as online_status,enable_status,game_key,access_key,agent_id,platform_config,platform_id,open_user_id,apply.ratio');
        $mGame->where('game_appid','=',$params['game_appid']);
        $mGame->where('platform_id','=',$channelExt['ff_platform']);
        $mGame->join(['tab_issue_game_apply'=>'apply'],'apply.game_id=tab_issue_game.id');
        $mGameData = $mGame->find();

        $ratio = $mGameData->ratio>0?$mGameData->ratio:100;
        if(($platformData['balance']-($params['amount']/100-($params['amount']/100*$ratio/100)))<$platformData['min_balance'] && empty($platformData['settle_type'])){
            return ['code'=>1019,'msg'=>'联运币不足，无法支付'];
        }

        //判断游戏订单是否重复
        $mSpend = new SpendModel();
        $mSpend->field('id');
        $mSpend->where('game_id','=',$mGameData['game_id']);
        $mSpend->where('extend','=',$params['trade_no']);
        $mSpendData = $mSpend->find();
        if(!empty($mSpendData)){
            return ['code'=>1012,'msg'=>'游戏订单重复'];
        }
        if(empty($mGameData)||$mGameData['online_status']!=1||$mGameData['enable_status']!=1){
            return ['code'=>1010,'msg'=>'游戏已下架'];
        }
        $data = [];
        $data['amount'] = $params['amount'];
        $data['props_name'] = $params['props_name'];
        $data['trade_no'] = $params['trade_no'];
        $data['user_id'] = 'sue_'.$params['user_id'];
        $data['game_appid'] = $params['game_appid'];
        $data['channelExt'] = $params['channelExt'];
        $data['timestamp'] = $params['timestamp'];
        $game_key = $mGameData['game_key'];
        $data['sign'] = (new \app\sdkh5\logic\GameLogic()) -> h5SignData($data, $game_key);
        if ($data['sign'] != $params['sign']) {
            return ['code'=>1003,'msg'=>'充值验签失败'];
        }
        //创建订单
        $res = $this->create_order($params,$mGameData);
        if($res){
            $orderData = $mSpend::get($res);
            if(!empty($platformData['controller_name_h5'])){
                if(!file_exists(APP_PATH.'issueh5/logic/pt/'.$platformData['controller_name_h5'].'Logic.php')){
                    return json(['code'=>1004,'msg'=>'平台接口文件错误']);
                }
                $class = '\app\\'.request()->module().'\\logic\\pt\\'.$platformData['controller_name_h5'].'Logic';
                $logic = new $class();
                $pay_data = $logic->pull_pay($orderData,$platformData,$mGameData,$params);
                if($platformData['settle_type'] > 0){
                    if(empty($platformData['order_notice_url_h5'])){
                        return ['code'=>1020,'msg'=>'未配置创建订单通知地址'];
                    }
                    $url = $platformData['order_notice_url_h5'].'?'.http_build_query($pay_data);
                    $result = file_get_contents($url);
                    if($result != 'success'){
                        return ['code'=>1021,'msg'=>'创建订单失败'];
                    }
                }
            }else{//平台接联运分发

            }
            return ['code'=>200,'msg'=>'分发订单创建成功','type'=>$platformData['settle_type'],'data'=>$pay_data];
        }else{
            return ['code'=>1013,'msg'=>'分发订单创建失败'];
        }
    }


    /**
     * @支付宝支付
     *
     * @author: zsl
     * @since: 2020/6/17 16:01
     */
    public function alipay($param = [])
    {
       if(cmf_is_mobile()){
           /* wap支付 */
           $data = $this->mobile_alipay($param);
        }else{
            $data = $this->pc_alipay($param);
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @PC扫码支付
     * @param array $data
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/7/7 17:31
     */
    protected function pc_alipay($data=[]){
        $data['pay_type'] = 'alipay';
        $data['service'] = "create_direct_pay_by_user";
        $data['signtype'] = "RSA2";
        $data['method'] = 'f2fscan';//面对面30min
        $url = $this->model_alipay($data);
        return array("status" => 1,"amount" => $url['fee'], "out_trade_no" => $url["order_no"], "qrcode_url" =>  url('issueh5/pay/qrcode', array('level' => 3, 'size' => 4, 'url' => base64_encode(base64_encode($url['payurl']))),true,true));
    }

    /**
     * @函数或方法说明
     * @手机端支付宝支付
     * @param array $data
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/7/7 17:32
     */
    protected function mobile_alipay($data=[]){
        $data['pay_type'] = 'alipay';
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $data['signtype'] = "MD5";
        $data['method'] = "wap";
        $url = $this->model_alipay($data);
        if(cmf_is_wechat()){
            $url = base64_encode($url);
        }
        return array("status" => 2,"amount" => $url['fee'], "out_trade_no" => $url["order_no"], "url" => $url);
    }


    /**
     * @函数或方法说明
     * @支付宝支付调起
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/7/3 11:39
     */
    protected function model_alipay($data=[])
    {
        $mSpend = new SpendModel();
        $mSpend->field('id,pay_amount,props_name,pay_order_number');
        $mSpend->where('pay_order_number','=',$data['trade_no']);
        $mSpendData = $mSpend->find();
        if(empty($mSpendData)){
            return ['status'=>0,'msg'=>'订单不存在'];
        }
        $data['body'] = $mSpendData['props_name']?:"游戏充值充值";
        $data['title'] = $mSpendData['props_name']?:'充值游戏';
        $config = get_pay_type_set('zfb');
        $pay = new \think\Pay($data['pay_type'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody($data['body'])
                ->setFee($mSpendData['pay_amount'])//支付金额
                ->setTitle($data['title'])
                ->setOrderNo($mSpendData['pay_order_number'])
                ->setSignType($data['signtype'])
                ->setPayMethod($data['method'])
                ->setService($data['server'])
                ->setModule('issueh5')
                ->setTable("sue_spend");
        $url = $pay->buildRequestForm($vo);
        return $url;
    }

    /**
     * @函数或方法说明
     * @微信支付
     * @param array $data
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/7/7 17:52
     */
    public function weixinpay(array $param)
    {
        $mSpend = new SpendModel();
        $mSpend->field('id,pay_amount,props_name,game_id,pay_order_number');
        $mSpend->where('pay_order_number','=',$param['trade_no']);
        $mSpendData = $mSpend->find();
        if(empty($mSpendData)){
            return ['status'=>0,'msg'=>'订单不存在'];
        }
        if (cmf_is_wechat() && !cmf_is_company_wechat()) {
            $result = $this -> get_wx_code($mSpendData);
            return $result;
        } elseif(cmf_is_mobile()) {
            //H5支付
            $weixn = new Weixin();
            $is_pay = json_decode($weixn -> weixin_pay($mSpendData['props_name'], $mSpendData['pay_order_number'], $mSpendData['pay_amount'], 'MWEB'), true);
            if ($is_pay['status'] != 1) {
                return ['status'=>0,'msg'=>'支付失败'];
            }
            if (!empty($is_pay['mweb_url'])) {
                $pay_url = $this -> get_weixin_url($is_pay['mweb_url']);
                return (['status'=>2,'url'=>$pay_url]);
            } else {
                return ['status'=>0,'msg'=>'支付发生错误,请重试'];
            }
        }else{
            $weixn = new Weixin();
            $is_pay = json_decode($weixn->weixin_pay($mSpendData['props_name'], $mSpendData['pay_order_number'], $mSpendData['pay_amount']), true);
            if ($is_pay['status'] != 1) {
                return ['status'=>0,'msg'=>'支付失败'];
            }
            return array("status" => 1,"amount" => $mSpendData['pay_amount'], "out_trade_no" => $mSpendData["pay_order_number"], "qrcode_url" =>  url('issueh5/pay/qrcode', array('level' => 3, 'size' => 4, 'url' => base64_encode(base64_encode($is_pay['url']))),true,true));
        }

    }

    /**
     * @函数或方法说明
     * @支付链接转化
     * @param string $weixn_url
     *
     * @return string
     *
     * @author: 郭家屯
     * @since: 2020/7/8 15:15
     */
    private function get_weixin_url($weixn_url = '')
    {
        $_ch = curl_init();
        curl_setopt($_ch, CURLOPT_URL, $weixn_url);
        if (strpos($weixn_url, 'https') === 0) {
            curl_setopt($_ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($_ch, CURLOPT_SSL_VERIFYHOST, 2);
        }
        $header['CLIENT-IP'] = $_SERVER['REMOTE_ADDR'];
        $header['X-FORWARDED-FOR'] = $_SERVER['REMOTE_ADDR'];
        $header = array();
        foreach ($header as $n => $v) {
            $header[] = $n . ':' . $v;
        }
        curl_setopt($_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($_ch, CURLOPT_HTTPHEADER, $header);  //构造IP
        curl_setopt($_ch, CURLOPT_CONNECTTIMEOUT, 5); // 连接超时（秒）
        curl_setopt($_ch, CURLOPT_REFERER, cmf_get_option('admin_set')['web_site']);
        curl_setopt($_ch, CURLOPT_TIMEOUT, 5); // 执行超时（秒）
        $data = curl_exec($_ch);
        if ($data === false) {
            echo curl_error($_ch);
            die;
        }
        curl_close($_ch);
        //匹配出支付链接
        preg_match('/weixin(.*)"/', $data, $_match);
        if (!isset($_match[1])) {
            return $weixn_url;
        }
        $pay_url = 'weixin' . $_match[1];
        return $pay_url;

    }

    /**
     * [微信内公众号支付]
     * @author 郭家屯[gjt]
     */
    private function get_wx_code($param = '')
    {
        Vendor("wxPayPubHelper.WxPayPubHelper");
        // 使用jsapi接口
        $pay_set = get_pay_type_set('wxscan');
        $wx_config = get_user_config_info('wechat');
        $jsApi = new \JsApi_pub($wx_config['appsecret'], $pay_set['config']['appid'], $pay_set['config']['key']);
        //获取code码，以获取openid
        $openid = session("wechat_token.openid");
        $weixn = new Weixin();
        $amount = $param['pay_amount'];
        $out_trade_no = $param['pay_order_number'];
        $is_pay = $weixn->weixin_jsapi($param['props_name']?:'游戏充值', $out_trade_no, $amount, $jsApi, $openid);
        $return['jsApiParameters'] = $is_pay;
        $return['status'] = 3;
        return $return;
    }


    public function create_order($orderData,$gameData)
    {
        $mSpend = new SpendModel();
        $mSpend->user_id = $orderData['user_id'];
        $mSpend->platform_openid = $orderData['platform_openid'];
        $mSpend->user_account = get_table_entity((new UserModel()),['id'=>$orderData['user_id']],'account')['account'];
        $mSpend->game_id = $gameData['game_id'];
        $mSpend->game_name = $gameData['game_name'];
        $mSpend->server_id = $orderData['server_id'];
        $mSpend->server_name = $orderData['server_name'];
        $mSpend->game_player_id = $orderData['role_id'];
        $mSpend->game_player_name = $orderData['role_name'];
        $mSpend->role_level = $orderData['role_level'];
        $mSpend->platform_id = $gameData['platform_id'];
        $mSpend->platform_account = get_table_entity(new PlatformModel(),['id'=>$gameData['platform_id']],'account')['account'];
        $mSpend->open_user_id = $gameData['open_user_id'];
        $mSpend->pay_order_number = create_out_trade_no('FF_');
        $mSpend->props_name = $orderData['props_name'];
        $mSpend->pay_amount = $orderData['amount']/100;
        $mSpend->pay_time = time();
        $mSpend->extra_param = $orderData['channelExt'];
        $mSpend->extend = $orderData['trade_no'];
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
        $mGame->field('tab_issue_game.id as game_id,tab_issue_game.sdk_version,game_appid,tab_issue_game.game_name,tab_issue_game.status as online_status,enable_status,game_key,access_key,agent_id,platform_config,platform_id,open_user_id,login_notify_url,pay_notify_url,apply.ratio');
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
            ->field('plat.id,plat.open_user_id,plat.platform_config_h5,plat.service_config,plat.controller_name_h5,plat.status,user.balance,min_balance')
            ->where('plat.id','=',$request['channel_code'])
            ->join(['tab_issue_open_user'=>'user'],'user.id=plat.open_user_id')
            ->find();
        if(!empty($platformData['controller_name_h5'])) {
            if (!file_exists(APP_PATH . 'issueh5/logic/pt/' . $platformData['controller_name_h5'] . 'Logic.php')) {
                return (['code' => 1004, 'msg' => '平台接口文件错误']);
            }
            $class = '\app\\' . request()->module() . '\\logic\\pt\\' . $platformData['controller_name_h5'] . 'Logic';
            $logic = new $class();
            $res = $logic->callback($request,$mGameData);
        }else{//平台接联运分发
            return (['code'=>1000,'msg'=>'系统错误']);
        }
        if($res['code']==200){
            return $this->callback_game($request,$mGameData,$res['spend_data'],$platformData);
        }else{
            return $res;
        }
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
        $platform->field('pay_notice_url_h5,controller_name_h5');
        $platformdata = $platform->find();
        if (empty($platformdata)) {
            return false;
        }
        if (empty($platformdata->pay_notice_url_h5)) {
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
        if(!file_exists(APP_PATH.'issueh5/logic/pt/'.$platformdata['controller_name_h5'].'Logic.php')){
            return false;
        }
        $class = '\app\\issueh5\\logic\\pt\\'.$platformdata['controller_name_h5'].'Logic';
        $logic = new $class();
        $game_data['game_key'] = $logic->get_game_key($game_data);
        $data = array(
                "out_trade_no" => $spend['pay_order_number'],
                'pay_extra' => request()->host(),
                "pay_status" => 1,
                "pay_game_status" => $spend['pay_game_status'],
                "price" => $spend['pay_amount'],
                "user_id" => $spend['platform_openid'],
                "pay_way" => $spend['pay_way']
        );
        $data['sign'] = $this->h5SignData($data,$game_data['game_key']);
        //$result = file_get_contents($platformdata['pay_notice_url_h5']."?".http_build_query($data));
        //return $platformdata['pay_notice_url_h5']."?".http_build_query($data);
        $result = $this->post($data, $platformdata['pay_notice_url_h5']);
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
            if($deduction_res['code']!=200){
                return ($deduction_res);
            }
        }
        //通知cp
        $data = array(
            "game_order" => $mSpendData['extend'],
            "out_trade_no" => $mSpendData['pay_order_number'],
            'pay_extra' => request()->host(),
            "pay_status" => 1,
            "price" => $mSpendData['pay_amount'],
            "user_id" => 'sue_'.$mSpendData['user_id'],
        );
        if($applydata['sdk_version'] == '3'){
            $data['sign'] = $this->h5SignData($data,$applydata['game_key']);
        }else {
            $datasign = implode($data);
            $md5_sign = md5($datasign . $applydata['game_key']);
            $data['sign'] = $md5_sign;
        }
        $result = $this->post($data, $applydata['pay_notify_url']);
        if ($result == "success"||json_decode($result,true)['status'] == "success") {
            $res = $this->update_game_pay_status($mSpendData);
            if($res==true){
                echo '{"status":"success"}';exit;
            }else{
                return (['code'=>1018,'msg'=>'游戏发货失败']);
            }
        } else {
            \think\Log::record("游戏分发支付通知信息：" . $result . ";游戏通知地址：" . $applydata['pay_notify_url']);
            return (['code'=>1018,'msg'=>'游戏发货失败']);
        }
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
            return ['code'=>1019,'msg'=>'联运币不足，无法支付'];
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