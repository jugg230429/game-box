<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */

namespace app\sdkh5\logic;

use app\common\logic\InvitationLogic;
use app\game\model\GameModel;
use app\member\model\UserPlayModel;
use app\promote\logic\CustompayLogic;
use app\recharge\logic\CheckAgeLogic;
use app\recharge\model\CouponRecordModel;
use app\recharge\model\SpendModel;
use app\member\model\UserModel;
use app\api\GameApi;
use think\weixinsdk\Weixin;
use think\Db;

class PayLogic extends BaseLogic
{
    public function pay_init(array $params)
    {
        //获取用户信息
        $user = get_user_entity(get_user_puid($params['user_id']), false, 'id,account,promote_id,promote_account,age_status,balance');
        if (empty($user)) {
            return $this -> failResult('用户不存在');
        }
        //获取用户绑币余额
        $mUserPlay = new UserPlayModel();
        $where = [];
        $where['user_id'] = get_user_puid($params['user_id']);
        $where['game_appid'] = $params['game_appid'];
        $user['bind_balance'] = $mUserPlay -> where($where) -> value('bind_balance');
        $this -> assign('user', $user);
        //判断实名充值验证
        if (get_user_config_info('age')['real_pay_status'] == '1' && $user['age_status'] != 2 && $user['age_status'] != 3) {
            return $this -> failResult(-100,get_user_config_info('age')['real_pay_msg']?:"根据国家关于《网络游戏管理暂行办法》要求，平台所有玩家必须完成实名认证后才可以进行游戏充值！");
        }
        //判断充值金额
        $params['amount'] = (int) $params['amount'];
        if ($params['amount'] < 100) {
            return $this -> failResult('金额不能低于1元');
        }
        //判断订单
        $spend = Db::table('tab_spend')->where('extend',$params['trade_no'])->field('id')->find();
        if($spend){
            return $this -> failResult('游戏订单已存在，请重新下单');
        }
        //获取游戏信息
        $mGame = new GameModel();
        $field = 'id,game_name,game_appid,pay_status';
        $where = [];
        $where['game_appid'] = $params['game_appid'];
        $gameInfo = $mGame -> field($field) -> where($where) -> find();
        if (empty($gameInfo)) {
            return $this -> failResult('游戏不存在');
        }
        //判断游戏是否开启充值
        if ($gameInfo['pay_status'] != '1') {
            return $this -> failResult('游戏未开启充值');
        }
        //检查用户是否属于自定义支付渠道
        $isCustom = check_user_is_custom_pay_channel($user['id']);
        if ($isCustom) {
            $this -> assign('is_custom', 1);
            //获取微端APP支付开启状态
            $params['weiduan_pay_status'] = 0;
        } else {
            //获取微端APP支付开启状态
            $params['weiduan_pay_status'] = get_game_entity($gameInfo['id'], 'weiduan_pay_status')['weiduan_pay_status'];
            $this -> assign('is_custom', 0);
        }
        //获取游戏扩展信息
        $gameSetInfo = Db ::table('tab_game_set') -> field('game_id,game_key') -> where(['game_id' => $gameInfo['id']]) -> find();
        //参数验签
        $lGame = new GameLogic();
        $data = [];
        $data['amount'] = $params['amount'];
        $data['props_name'] = $params['props_name'];
        $data['trade_no'] = $params['trade_no'];
        $data['user_id'] = $params['user_id'];
        $data['game_appid'] = $params['game_appid'];
        $data['channelExt'] = $params['channelExt'];
        $data['timestamp'] = $params['timestamp'];
        $data['sign'] = $lGame -> h5SignData($data, $gameSetInfo['game_key']);
        if ($data['sign'] != $params['sign']) {
            //file_put_contents(dirname(__FILE__).'/sign.txt',json_encode($data));
            return $this -> failResult('充值验签失败');
        }
        $this -> assign('props_name', $data['props_name']);
        $amount = sprintf("%.2f", $data['amount'] / 100);//原价
        $this -> assign('amount', $amount);
        //获取折扣
        $lPay = new \app\common\logic\PayLogic();
        $discount = $lPay -> get_discount($gameInfo['id'], $user['promote_id'], $user['id']);
        $this -> assign('discount', $discount);
        //获取可用代金券
        $mCouponRecord = new CouponRecordModel();
        $coupon = $mCouponRecord -> get_my_coupon($user['id'], 1, $gameInfo['id'],$amount);
        $this -> assign('coupon', $coupon);
        //实际支付金额
        $realAmount = sprintf("%.2f", $amount * $discount['discount']);
        $this -> assign('realAmount', $realAmount);

        $this->assign('data',$params);



        //获取模板
        $paysdkHtml = $this -> fetch('sdkh5@/paysdk');
        $returnData = [];
        $returnData['paysdk'] = $paysdkHtml;
        //初始化成功
        return $this -> successResult($returnData, '支付初始化成功');
    }


    /**
     * @支付宝支付
     *
     * @author: zsl
     * @since: 2020/6/17 16:01
     */
    public function alipay($param = [])
    {
        //验证支付
        $sign = check_h5pay_auth($param);
        if(!$sign){
            return $this -> failResult('验签失败');
        }
        //获取用户信息
        $user = get_user_entity(get_user_puid($param['user_id']), false, 'id,account,promote_id,promote_account');
        if (empty($user)) {
            return $this -> failResult('用户不存在');
        }
        $wechat_data = $param;
        if ($param['pay_amount'] < 1) {
            return $this -> failResult('金额不能低于1元');
        }
        if (pay_type_status('zfb') != 1) {
            return $this -> failResult('支付宝支付未开启');
        }
        $param['cost'] = $param['pay_amount'];
        if (empty($param['game_appid'])) {
            return $this -> failResult('请选择游戏');
        }
        $where = [];
        $where['user_id'] = get_user_puid($param['user_id']);
        $where['game_appid'] = $param['game_appid'];
        $userplay = Db ::table('tab_user_play') -> field('user_id,user_account,game_id,game_name,game_appid,sdk_version')
                -> where($where)-> find();
        if (!$userplay) {
            return $this -> failResult('游戏角色不存在');
        }
        $lPay = new \app\common\logic\PayLogic();
        //去除代金券金额
        if($param['coupon_id']){
            $coupon_money = $lPay->get_use_coupon(get_user_puid($param['user_id']),$param['pay_amount'],$param['coupon_id']);
            if($coupon_money){
                $price = $param['pay_amount'] - $coupon_money;
            }else{
                return $this -> failResult('优惠券使用失败');
            }
        }else{
            $price = $param['pay_amount'];
        }
        //获取折扣
        $discount = $lPay -> get_discount($userplay['game_id'], $user['promote_id'], $user['id']);
        $param['sdk_version'] = $userplay['sdk_version'];
        $param['discount'] = $discount['discount'];
        $param['discount_type'] = $discount['discount_type'];
        $param['pay_amount'] = round($discount['discount']*$price,2);
        $param['user_id'] = $user['id'];
        $param['user_account'] = $user['account'];
        $param['promote_id'] = $user['promote_id'];
        $param['promote_account'] = $user['promote_account'];
        $param['game_id'] = $userplay['game_id'];
        $param['game_name'] = $userplay['game_name'];
        $param['game_player_id'] = $param['role_id'];
        $param['game_player_name'] = $param['role_name'];
        $param['pay_order_number'] = create_out_trade_no("SP_");
        $param['body'] = "游戏充值充值";
        $param['title'] = $param['props_name']?:'充值游戏';
        $param['extend'] = $param['trade_no'];
        $param['pay_way'] = 3;

        if ($param['user_id'] != SMALL_UID) {
            $smallUser = get_user_entity(SMALL_UID, false, 'id,account,nickname,cumulative');
            $param['small_id'] = SMALL_UID;
            $param['small_nickname'] = $smallUser['nickname'];
        }
        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run(get_user_puid($param['user_id']), $param['pay_amount']);
            if (false === $checkAgeRes) {
                return $this -> failResult($lCheckAge -> getErrorMsg());
            }
        }

        //代金券完全消费
        if($param['coupon_id'] > 0 && $price<=0){
            $data = $this->coupon_pay($param,$user);
        }elseif(!empty($param['is_scan'])){
            //扫码支付
            $data = $this->pc_alipay($param);
        }elseif(cmf_is_wechat()){
            $url = url('wechatpay/alipay',$wechat_data);
            $data = ['status'=>2,'url'=>$url];
        }elseif(cmf_is_mobile()||session('app_user_login')==1){
            $config = get_pay_type_set('zfb');
            if(get_devices_type() == 2 && session('app_user_login')==1 && $config['config']['type'] == 1){
                /* app支付 */
                $data = $this->app_alipay($param);
            }elseif (get_devices_type() == 2 && $param['is_weiduan'] == 1 && $config['config']['type'] == 1 ){
               /* 微端支付 */
                $data = $this->app_alipay($param);
            }else{
                /* wap支付 */
                $data = $this->mobile_alipay($param);
            }
        }else{
           $data = $this->pc_alipay($param);
        }
        return $data;
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
        //验证支付
        $sign = check_h5pay_auth($param);
        if(!$sign){
            return $this -> failResult('验签失败');
        }
        //获取用户信息
        $user = get_user_entity(get_user_puid($param['user_id']), false, 'id,account,promote_id,promote_account');
        if (empty($user)) {
            return $this -> failResult('用户不存在');
        }
        if ($param['pay_amount'] < 1) {
            return $this -> failResult('金额不能低于1元');
        }
        if (pay_type_status('wxscan') != 1 && pay_type_status('wxapp') != 1) {
            return $this -> failResult('微信支付未开启');
        }
        $param['cost'] = $param['pay_amount'];
        if (empty($param['game_appid'])) {
            return $this -> failResult('请选择游戏');
        }
        $where = [];
        $where['user_id'] = get_user_puid($param['user_id']);
        $where['game_appid'] = $param['game_appid'];
        $userplay = Db ::table('tab_user_play') -> field('user_id,user_account,game_id,game_name,game_appid,sdk_version')
                -> where($where)-> find();
        if (!$userplay) {
            return $this -> failResult('游戏角色不存在');
        }
        $lPay = new \app\common\logic\PayLogic();
        //去除代金券金额
        if($param['coupon_id']){
            $coupon_money = $lPay->get_use_coupon(get_user_puid($param['user_id']),$param['pay_amount'],$param['coupon_id']);
            if($coupon_money){
                $price = $param['pay_amount'] - $coupon_money;
            }else{
                return $this -> failResult('优惠券使用失败');
            }
        }else{
            $price = $param['pay_amount'];
        }
        //获取折扣
        $discount = $lPay -> get_discount($userplay['game_id'], $user['promote_id'], $user['id']);
        $param['sdk_version'] = $userplay['sdk_version'];
        $param['discount'] = $discount['discount'];
        $param['discount_type'] = $discount['discount_type'];
        $param['pay_amount'] = round($discount['discount']*$price,2);
        $param['user_id'] = $user['id'];
        $param['user_account'] = $user['account'];
        $param['promote_id'] = $user['promote_id'];
        $param['promote_account'] = $user['promote_account'];
        $param['game_id'] = $userplay['game_id'];
        $param['game_name'] = $userplay['game_name'];
        $param['game_player_id'] = $param['role_id'];
        $param['game_player_name'] = $param['role_name'];
        $param['pay_order_number'] = create_out_trade_no("SP_");
        $param['body'] = "游戏充值充值";
        $param['title'] = $param['props_name']?:'充值游戏';
        $param['extend'] = $param['trade_no'];
        $param['pay_way'] = 4;

        if ($param['user_id'] != SMALL_UID) {
            $smallUser = get_user_entity(SMALL_UID, false, 'id,account,nickname,cumulative');
            $param['small_id'] = SMALL_UID;
            $param['small_nickname'] = $smallUser['nickname'];
        }


        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run(get_user_puid($param['user_id']), $param['pay_amount']);
            if (false === $checkAgeRes) {
                return $this -> failResult($lCheckAge -> getErrorMsg());
            }
        }

        if(!empty($param['is_scan'])){
            //扫码支付

            //查询是否开启自定义支付
            $lCustomPay = new CustompayLogic();
            $customConfig = $lCustomPay -> getPayConfig($param['promote_id'], $param['game_id'], 'wxscan', $param['pay_amount']);

            if (false !== $customConfig) {
                $param['pay_promote_id'] = $param['promote_id'];
            }

            $weixn = new Weixin();
            $is_pay = json_decode($weixn -> weixin_pay($param['title'], $param['pay_order_number'], $param['pay_amount'], 'NATIVE',
                                                       1, 0, 0, $param['promote_id'], $param['game_id']), true);
            if ($is_pay['status'] == 1) {
                add_spend($param,$user);
            } else {
                return $this -> failResult('支付失败');
            }
            return array("status" => 1,"amount" => $param['pay_amount'], "out_trade_no" => $param["pay_order_number"], "qrcode_url" =>  url('sdkh5/pay/qrcode', array('level' => 3, 'size' => 4, 'url' => base64_encode(base64_encode($is_pay['url']))),true,true));


        }else if($param['coupon_id'] > 0 && $price<=0){
            //代金券完全消费
            $data = $this->coupon_pay($param,$user);
            return $data;
        }elseif (cmf_is_wechat() && !cmf_is_company_wechat()) {

            //查询是否开启自定义支付
            $lCustomPay = new CustompayLogic();
            $customConfig = $lCustomPay -> getPayConfig($param['promote_id'], $param['game_id'], 'wxapp', $param['pay_amount']);
            if (false !== $customConfig) {
                $param['pay_promote_id'] = $param['promote_id'];
                add_spend($param,$user);
                $url = url('wechatpay/weixinpay', $param);
                $data = ['status' => 2, 'url' => $url];
                return $data;
            } else {
                add_spend($param,$user);
                $result = $this -> get_wx_code($param);
            }

            return $result;
        } elseif(cmf_is_mobile()||session('app_user_login')==1) {
            if(get_devices_type() == 2 && session('app_user_login')==1 && pay_type_status('wxapp') == 1){
                //查询是否开启自定义支付
                $lCustomPay = new CustompayLogic();
                $customConfig = $lCustomPay -> getPayConfig($param['promote_id'], $param['game_id'], 'wxapp', $param['pay_amount']);
                if (false !== $customConfig) {
                    $param['pay_promote_id'] = $param['promote_id'];
                }
                //APP支付
                $weixn = new Weixin();
                $is_pay = json_decode($weixn -> weixin_pay($param['body'], $param['pay_order_number'], $param['pay_amount'],
                        'APP', 3,0,0,$param['promote_id'],$param['game_id']), true);
                if ($is_pay['status'] === 1) {
                    add_spend($param,$user);
                    $json_data['appid'] = $is_pay['appid'];
                    $json_data['partnerid'] = $is_pay['mch_id'];
                    $json_data['out_trade_no'] = $is_pay['prepay_id'];
                    $json_data['prepayid'] = $is_pay['prepay_id'];
                    $json_data['noncestr'] = $is_pay['noncestr'];
                    $json_data['timestamp'] = $is_pay['time'];
                    $json_data['package'] = "Sign=WXPay";
                    $json_data['sign'] = $is_pay['sign'];
                    $json_data['status'] = 4;
                    $json_data['return_msg'] = "下单成功";
                    $json_data['wxtype'] = "wx";
                    return $json_data;
                } else {
                    return $this -> failResult($is_pay['return_msg']);
                }
            }else if(get_devices_type() == 2 && $param['is_weiduan'] == 1 && pay_type_status('wxapp') == 1){

                //查询是否开启自定义支付
                $lCustomPay = new CustompayLogic();
                $customConfig = $lCustomPay -> getPayConfig($param['promote_id'], $param['game_id'], 'wxapp', $param['pay_amount']);
                if (false !== $customConfig) {
                    $param['pay_promote_id'] = $param['promote_id'];
                }

                //微端支付
                $weixn = new Weixin();
                $param['is_weiduan'] = 1;
                $map1['game_id'] = $param['game_id'];
                $map1['status'] = 1;
                $param1 = Db::table('tab_spend_wxparam') -> where($map1) -> find();
                if (!empty($param)) {
                    $is_pay = json_decode($weixn -> weixin_pay($param['body'], $param['pay_order_number'], $param['pay_amount'],
                    'APP', 3, $param1['id'], 0, $param['promote_id'], $param['game_id']), true);
                } else {
                    return $this -> failResult('游戏未启用微信充值');
                }
                if ($is_pay['status'] === 1) {
                    add_spend($param,$user);
                    $json_data['appid'] = $is_pay['appid'];
                    $json_data['partnerid'] = $is_pay['mch_id'];
                    $json_data['out_trade_no'] = $is_pay['prepay_id'];
                    $json_data['prepayid'] = $is_pay['prepay_id'];
                    $json_data['noncestr'] = $is_pay['noncestr'];
                    $json_data['timestamp'] = $is_pay['time'];
                    $json_data['package'] = "Sign=WXPay";
                    $json_data['sign'] = $is_pay['sign'];
                    $json_data['status'] = 4;
                    $json_data['return_msg'] = "下单成功";
                    $json_data['wxtype'] = "wx";
                    return $json_data;
                } else {
                    return $this -> failResult($is_pay['return_msg']);
                }
            }else{


                //查询是否开启自定义支付
                $lCustomPay = new CustompayLogic();
                $customConfig = $lCustomPay -> getPayConfig($param['promote_id'], $param['game_id'], 'wxscan', $param['pay_amount']);
                if (false !== $customConfig) {
                    $param['pay_promote_id'] = $param['promote_id'];
                }
                //H5支付
                $weixn = new Weixin();
                $is_pay = json_decode($weixn -> weixin_pay($param['title'], $param['pay_order_number'], $param['pay_amount'], 'MWEB',
               1, 0, 0, $param['promote_id'], $param['game_id']), true);
                if ($is_pay['status'] == 1) {
                    add_spend($param,$user);
                } else {
                    return $this -> failResult('支付失败');
                }
                if (!empty($is_pay['mweb_url'])) {
                    $pay_url = $this -> get_weixin_url($is_pay['mweb_url']);
                    if(HPID > 0){
                        $url = '//' . cmf_get_option('admin_set')['web_site'] . '/mobile/pay/wechatJumpPage' . "?jump_url=" . urlencode($pay_url);
                    }else{
                        $url= $pay_url;
                    }
                    return (['status'=>2,'url'=>$url]);
                } else {
                    return $this -> failResult('支付发生错误,请重试');
                }
            }
        }else{

            //查询是否开启自定义支付
            $lCustomPay = new CustompayLogic();
            $customConfig = $lCustomPay -> getPayConfig($param['promote_id'], $param['game_id'], 'wxscan', $param['pay_amount']);

            if (false !== $customConfig) {
                $param['pay_promote_id'] = $param['promote_id'];
            }

            $weixn = new Weixin();
            $is_pay = json_decode($weixn -> weixin_pay($param['title'], $param['pay_order_number'], $param['pay_amount'], 'NATIVE',
            1, 0, 0, $param['promote_id'], $param['game_id']), true);
            if ($is_pay['status'] == 1) {
                add_spend($param,$user);
            } else {
                return $this -> failResult('支付失败');
            }
            return array("status" => 1,"amount" => $param['pay_amount'], "out_trade_no" => $param["pay_order_number"], "qrcode_url" =>  url('sdkh5/pay/qrcode', array('level' => 3, 'size' => 4, 'url' => base64_encode(base64_encode($is_pay['url']))),true,true));
        }

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
        $is_pay = $weixn->weixin_jsapi($param['title'], $out_trade_no, $amount, $jsApi, $openid);
        $return['jsApiParameters'] = $is_pay;
        $return['status'] = 3;
        return $return;
    }

    /**
     * @函数或方法说明
     * @平台币支付
     * @author: 郭家屯
     * @since: 2020/7/7 19:49
     */
    public function platformcoinpay(array $param)
    {
        //验证支付
        $sign = check_h5pay_auth($param);
        if(!$sign){
            return $this -> failResult('验签失败');
        }
        //获取用户信息
        $user = get_user_entity(get_user_puid($param['user_id']), false, 'id,account,nickname,promote_id,promote_account,balance,cumulative,parent_id,parent_name,age_status,invitation_id');
        if (empty($user)) {
            return $this -> failResult('用户不存在');
        }
        if ($param['pay_amount'] < 1) {
            return $this -> failResult('金额不能低于1元');
        }
        $param['cost'] = $param['pay_amount'];
        if (empty($param['game_appid'])) {
            return $this -> failResult('请选择游戏');
        }
        $cpSpend = Db::table('tab_spend')->where('extend', $param['extend'])->where('pay_status',1)->find();
        if($cpSpend){
            return $this -> failResult('订单重复，请关闭页面重新支付');
        }
        $where = [];
        $where['user_id'] = get_user_puid($param['user_id']);
        $where['game_appid'] = $param['game_appid'];
        $userplay = Db ::table('tab_user_play') -> field('user_id,user_account,game_id,game_name,game_appid,sdk_version')
                -> where($where)-> find();
        if (!$userplay) {
            return $this -> failResult('游戏角色不存在');
        }
        $lPay = new \app\common\logic\PayLogic();
        if($param['code'] == 1){
            //去除代金券金额
            if($param['coupon_id']){
                $coupon_money = $lPay->get_use_coupon(get_user_puid($param['user_id']),$param['pay_amount'],$param['coupon_id']);
                if($coupon_money){
                    $price = $param['pay_amount'] - $coupon_money;
                }else{
                    return $this -> failResult('优惠券使用失败');
                }
            }else{
                $price = $param['pay_amount'];
            }
            //获取折扣
            $discount = $lPay -> get_discount($userplay['game_id'], $user['promote_id'], $user['id']);
        }else{
            $discount['discount'] = 1;
            $discount['discount_type'] = 0;
            $price = $param['pay_amount'];
            unset($param['coupon_id']);
        }
        $param['sdk_version'] = $userplay['sdk_version'];
        $param['discount'] = $discount['discount'];
        $param['discount_type'] = $discount['discount_type'];
        $param['pay_amount'] = round($discount['discount']*$price,2);
        $param['user_id'] = $user['id'];
        $param['user_account'] = $user['account'];
        $param['promote_id'] = $user['promote_id'];
        $param['promote_account'] = $user['promote_account'];
        $param['game_id'] = $userplay['game_id'];
        $param['game_name'] = $userplay['game_name'];
        $param['game_player_id'] = $param['role_id'];
        $param['game_player_name'] = $param['role_name'];
        $param['pay_order_number'] = create_out_trade_no("SP_");
        $param['body'] = "游戏充值充值";
        $param['title'] = $param['props_name']?:'充值游戏';
        $param['extend'] = $param['trade_no'];
        $param['pay_status'] = 1;

        if ($param['user_id'] != SMALL_UID) {
            $smallUser = get_user_entity(SMALL_UID, false, 'id,account,nickname,cumulative');
            $param['small_id'] = SMALL_UID;
            $param['small_nickname'] = $smallUser['nickname'];
        }

        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run(get_user_puid($param['user_id']), $param['pay_amount']);
            if (false === $checkAgeRes) {
                return $this -> failResult($lCheckAge -> getErrorMsg());
            }
        }

        $usermodel = new UserModel();
        switch ($param['code']) {
            case 1:#平台币
                $param['pay_way'] = 2;
                //代金券完全消费
                if($param['coupon_id'] > 0 && $price <=0){
                    $data = $this->coupon_pay($param,$user);
                    return $data;
                }
                if ($user['balance'] < $param['pay_amount']) {
                    return $this -> failResult('余额不足');
                }
                Db::startTrans();
                try {
                    #扣除平台币
                    $usermodel->where("id", $user["id"])->setDec("balance", $param['pay_amount']);
                    //任务
                    if(user_is_paied($user["id"])==0){
                        $usermodel->task_complete($user["id"],'first_pay',$param['pay_amount']);//首冲
                    }
                    $usermodel->auto_task_complete($user["id"],'pay_award',$param['pay_amount']);//充值送积分
                    $usermodel->first_pay_every_day($user["id"],$param['pay_amount']);//每日首充积分奖励
                    //发放邀请人充值代金券
                    if($user['invitation_id'] >0){
                        $money = $param['pay_amount']+$user['cumulative'];
                        $logic = new InvitationLogic();
                        $logic->send_spend_coupon($user['invitation_id'],$user['id'],$money);
                    }
                    //更新VIP等级和充值总金额
                    set_vip_level($user["id"], $param['pay_amount'],$user['cumulative']);
                    if ($param['user_id'] != SMALL_UID) {
                        //更新小号累计充值金额
                        set_vip_level(SMALL_UID, $param['pay_amount'], $smallUser['cumulative']);
                    }

                    #TODO 添加绑定平台币消费记录
                    if ($user['promote_id']) {
                        $promote = get_promote_entity($user['promote_id'],'pattern');
                        if ($promote['pattern'] == 0) {
                            $param['is_check'] = 1;
                        }
                    }
                    $spendid = add_spend($param,$user);
                    $spend = Db::table('tab_spend')->where('id', $spendid)->find();
                    if ($user['promote_id']) {
                        if ($promote['pattern'] == 0) {
                            set_promote_radio($spend,$user);
                        }
                    }
                    //代金券使用
                    if($param['coupon_id']){
                        $coupon_data['status'] = 1;
                        $coupon_data['cost'] = $param['cost'];
                        $coupon_data['update_time'] = time();
                        $coupon_data['pay_amount'] = $param['pay_amount'];
                        Db::table('tab_coupon_record')->where('id',$param['coupon_id'])->update($coupon_data);
                    }
                    //异常预警
                    if($param['cost'] >= 2000){
                        $warning = [
                                'type'=>3,
                                'user_id'=>$param['user_id'],
                                'user_account'=>$param['user_account'],
                                'pay_order_number'=>$param['pay_order_number'],
                                'target'=>3,
                                'record_id'=>$spendid,
                                'pay_amount'=>$param['cost'],
                                'create_time'=>time()
                        ];
                        Db::table('tab_warning')->insert($warning);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return $this -> failResult('支付失败');
                }
                $type = 0;

                // 用户阶段更改
                try{

                    $systemSet = cmf_get_option('admin_set');
                    if (empty($systemSet['task_mode'])) {
                        (new \app\common\task\HandleUserStageTask()) -> changeUserStage1(['user_id' => $param['user_id']]);
                    } else {
                        (new \app\common\task\HandleUserStageTask()) -> saveOperation('', $param['user_id'], 0);
                    }

                }catch(\Exception $e){

                }
                break;
            case 2:#绑币
                $user_play = get_user_play($param['user_id'],$param['game_id']);
                if ($user_play['bind_balance'] < $param['pay_amount']) {
                    return $this -> failResult('余额不足');
                }
                $param['pay_way'] = 1;
                Db::startTrans();
                try {
                    #扣除绑币
                    $userplaymodel = new UserPlayModel();
                    $userplaymodel->where("id", $user_play["id"])->setDec("bind_balance",  $param['pay_amount']);
                    //任务
                    if(user_is_paied($param['user_id'])==0){
                        $usermodel->task_complete($param['user_id'],'first_pay', $param['pay_amount']);//首冲
                    }
                    $usermodel->auto_task_complete($param['user_id'],'pay_award', $param['pay_amount']);//充值送积分
                    $usermodel->first_pay_every_day($param["user_id"],$param['pay_amount']);//每日首充积分奖励
                    //发放邀请人充值代金券
                    if($user['invitation_id'] >0){
                        $money =  $param['pay_amount']+$user['cumulative'];
                        $logic = new InvitationLogic();
                        $logic->send_spend_coupon($user['invitation_id'],$user['id'],$money);
                    }
                    //更新VIP等级和充值总金额
                    set_vip_level($param["user_id"],  $param['pay_amount'],$user['cumulative']);
                    if ($param['user_id'] != SMALL_UID) {
                        //更新小号累计充值金额
                        set_vip_level(SMALL_UID, $param['pay_amount'], $smallUser['cumulative']);
                    }
                    #TODO 添加绑定平台币消费记录
                    if ($user['promote_id']) {
                        $promote = get_promote_entity($user['promote_id'],'pattern');
                        if ($promote['pattern'] == 0) {
                            $param['is_check'] = 1;
                        }
                    }
                    $spendid = add_spend($param,$user);
                    $spend = Db::table('tab_spend')->where('id', $spendid)->find();
                    if ($user['promote_id']) {
                        if ($promote['pattern'] == 0) {
                            set_promote_radio($spend,$user);
                        }
                    }
                    //异常预警
                    if($param['cost'] >= 2000){
                        $warning = [
                                'type'=>3,
                                'user_id'=>$param['user_id'],
                                'user_account'=>$param['user_account'],
                                'pay_order_number'=>$param['pay_order_number'],
                                'target'=>3,
                                'record_id'=>$spendid,
                                'pay_amount'=>$param['cost'],
                                'create_time'=>time()
                        ];
                        Db::table('tab_warning')->insert($warning);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return $this -> failResult('支付失败');
                }
                $type = 1;

                // 用户阶段更改
                try{

                    $systemSet = cmf_get_option('admin_set');
                    if (empty($systemSet['task_mode'])) {
                        (new \app\common\task\HandleUserStageTask()) -> changeUserStage1(['user_id' => $param['user_id']]);
                    } else {
                        (new \app\common\task\HandleUserStageTask()) -> saveOperation('', $param['user_id'], 0);
                    }

                }catch(\Exception $e){

                }
                break;
            default:
                return $this -> failResult('支付方式不明确');
                break;
        }
        $param['out_trade_no'] = $param['pay_order_number'];
        $game = new GameApi();
        $game->game_pay_notify($param);
        $lPay->set_ratio($spend,$user,$type);
        return ['status'=>1,"out_trade_no" => $param['pay_order_number']];
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
        return array("status" => 2,"amount" => $url['fee'], "out_trade_no" => $url["order_no"], "url" => $url);
    }

    /**
     * @函数或方法说明
     * @APP支付
     * @param array $data
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/7/9 13:51
     */
    protected function app_alipay($data=[])
    {
        $data['pay_type'] = "alipay";
        $data['signtype'] = "MD5";
        $data['method'] = "mobile";
        $data['service'] = "mobile.securitypay.pay";
        $return = $this->model_alipay($data);
        $pay_data['status'] = 3;
        $pay_data['md5_sign'] = $this->encrypt_md5($return['arg'], 'mengchuang');
        $pay_data['orderInfo'] = $return['arg'];
        $pay_data['out_trade_no'] = $return['out_trade_no'];
        $pay_data['order_sign'] = $return['sign'];
        return $pay_data;
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
        return array("status" => 1,"amount" => $url['fee'], "out_trade_no" => $url["order_no"], "qrcode_url" =>  url('sdkh5/pay/qrcode', array('level' => 3, 'size' => 4, 'url' => base64_encode(base64_encode($url['payurl']))),true,true));
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
        $data['pay_way'] = 3;
        $data['body'] = "游戏充值充值";
        $data['title'] = $data['props_name']?:'充值游戏';

        //获取渠道自定义支付参数
        $lCustomPay = new CustompayLogic();
        $customConfig = $lCustomPay -> getPayConfig($data['promote_id'], $data['game_id'], 'zfb', $data['pay_amount']);
        if(false===$customConfig){
            $config = get_pay_type_set('zfb');
            $payPromoteId = 0;
        }else{
            $config['config'] = $customConfig;
            $payPromoteId = $data['promote_id'];
        }
        $pay = new \think\Pay($data['pay_type'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody($data['body'])
                ->setFee($data['pay_amount'])//支付金额
                ->setTitle($data['title'])
                ->setOrderNo($data['pay_order_number'])
                ->setSignType($data['signtype'])
                ->setPayMethod($data['method'])
                ->setTable("spend")
                ->setPayWay($data['pay_way'])
                ->setGameId($data['game_id'])
                ->setGameName($data['game_name'])
                ->setGameAppid($data['game_appid'])
                ->setRoleLevel($data['role_level'])
                ->setServerId($data['server_id'])
                ->setServerName($data['server_name'])
                ->setGameplayerId($data['game_player_id'])
                ->setGameplayerName($data['game_player_name'])
                ->setUserId($data['user_id'])
                ->setAccount($data['user_account'])
                ->setPromoteId($data['promote_id'])
                ->setPromoteName($data['promote_account'])
                ->setExtend($data['trade_no'])
                ->setCost($data['cost'])
                ->setDiscount($data['discount']*10)
                ->setSdkVersion($data['sdk_version'])
                ->setDiscount_type($data['discount_type'])
                ->setPayPromoteId($payPromoteId)
                ->setCouponRecordId($data['coupon_id'])
                ->setSmallId($data['small_id'])
                ->setSmallNickname($data['small_nickname']);
        $url = $pay->buildRequestForm($vo);
        return $url;
    }

    /**
     * @函数或方法说明
     * @代金券金额超出
     * @param array $param
     * @param array $user
     *
     * @author: 郭家屯
     * @since: 2020/9/15 15:59
     */
    protected function coupon_pay($param = [], $user= [])
    {
        $param['pay_amount'] = 0;
        $param['pay_status'] = 1;
        $data = add_spend($param,$user);
        if($data){
            //任务
            $usermodel = new UserModel();
            if(user_is_paied($user["id"])==0){
                $usermodel->task_complete($user["id"],'first_pay',$param['cost']);//首冲
            }
            //代金券使用
            if($param['coupon_id']){
                $coupon_data['status'] = 1;
                $coupon_data['cost'] = $param['cost'];
                $coupon_data['update_time'] = time();
                $coupon_data['pay_amount'] = $param['pay_amount'];
                Db::table('tab_coupon_record')->where('id',$param['coupon_id'])->update($coupon_data);
            }
            $param['out_trade_no'] = $param['pay_order_number'];
            $game = new GameApi();
            $game->game_pay_notify($param);
            $data = ['status'=>4,'message'=>'支付成功'];
        }else{
            $data = ['status'=>0,'message'=>'创建订单失败'];
        }
        return $data;
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
     *MD5验签加密
     */
    protected function encrypt_md5($param = "", $key = "")
    {
        $md5 = md5($param . $key);
        return $param ? $md5 : '';
    }


}
