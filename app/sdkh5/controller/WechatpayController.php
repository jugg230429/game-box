<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */

namespace app\sdkh5\controller;

use app\promote\logic\CustompayLogic;
use app\sdkh5\BaseController;
use think\Db;
use think\weixinsdk\Weixin;

class WechatpayController extends BaseController
{
    /**
     * @支付宝支付
     *
     * @author: zsl
     * @since: 2020/6/17 15:59
     */
    public function alipay()
    {
        $data = $this -> request -> param();
        if(cmf_is_wechat()){
            return $this->fetch();
        }
        //验证支付
        $sign = check_h5pay_auth($data);
        if(!$sign){
           exit('验签失败');
        }
        //获取用户信息
        $user = get_user_entity($data['user_id'], false, 'id,account,promote_id,promote_account');
        if (empty($user)) {
            exit('用户不存在');
        }
        if ($data['pay_amount'] < 1) {
            exit('金额不能低于1元');
        }

        if (pay_type_status('zfb') != 1) {
            exit('支付宝支付未开启');
        }
        $data['cost'] = $data['pay_amount'];

        if (empty($data['game_appid'])) {
            exit('请选择游戏');
        }
        $where = [];
        $where['user_id'] = $data['user_id'];
        $where['game_appid'] = $data['game_appid'];
        $userplay = Db ::table('tab_user_play') -> field('user_id,user_account,game_id,game_name,game_appid,sdk_version')
                -> where($where)-> find();
        if (!$userplay) {
            exit('游戏角色不存在');
        }
        $lPay = new \app\common\logic\PayLogic();
        //去除代金券金额
        if($data['coupon_id']){
            $coupon_money = $lPay->get_use_coupon($data['user_id'],$data['pay_amount'],$data['coupon_id']);
            if($coupon_money){
                $price = $data['pay_amount'] - $coupon_money;
            }else{
                exit('优惠券使用失败');
            }
        }else{
            $price = $data['pay_amount'];
        }
        //获取折扣
        $discount = $lPay -> get_discount($userplay['game_id'], $user['promote_id'], $user['id']);
        $data['sdk_version'] = $userplay['sdk_version'];
        $data['discount'] = $discount['discount'];
        $data['discount_type'] = $discount['discount_type'];
        $data['pay_amount'] = round($discount['discount']*$price,2);
        $data['user_id'] = $user['id'];
        $data['user_account'] = $user['account'];
        $data['promote_id'] = $user['promote_id'];
        $data['promote_account'] = $user['promote_account'];
        $data['game_id'] = $userplay['game_id'];
        $data['game_name'] = $userplay['game_name'];
        $data['game_player_id'] = $data['role_id'];
        $data['game_player_name'] = $data['role_name'];
        $data['pay_order_number'] = create_out_trade_no("SP_");
        $data['body'] = "游戏充值充值";
        $data['title'] = $data['props_name']?:'充值游戏';
        $data['extend'] = $data['trade_no'];
        $data['pay_way'] = 3;
        //代金券完全消费
        if(cmf_is_mobile()){
            /* wap支付 */
            $data['pay_type'] = 'alipay';
            $data['service'] = "alipay.wap.create.direct.pay.by.user";
            $data['signtype'] = "MD5";
            $data['method'] = "wap";
            $data['pay_way'] = 3;
            $data['body'] = "游戏充值充值";
            $data['title'] = $data['props_name']?:'充值游戏';
            //获取渠道自定义支付参数
            $lCustomPay = new CustompayLogic();
            $customConfig = $lCustomPay -> getPayConfig($data['promote_id'], $data['game_id'], 'zfb', $data['pay_amount']);
            if (false === $customConfig) {
                $config = get_pay_type_set('zfb');
                $payPromoteId = 0;
            } else {
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
                    ->setCouponRecordId($data['coupon_id'])
                    ->setPayPromoteId($payPromoteId);
            $url = $pay->buildRequestForm($vo);
            return redirect($url);
        }else{
           die('请用移动端浏览器打开');
        }
    }

    /**
     * @函数或方法说明
     * @分发支付宝支付
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/10/31 11:21
     */
    public function alipay_ff()
    {
        if(!cmf_is_wechat()){
            $url = $this->request->param('url');
            $this->assign('url',base64_decode($url));
        }else{
            $this->assign('url','');
        }
        return $this->fetch();
    }

    public function weixinpay()
    {
        $param = $this -> request -> param();
        if (cmf_is_wechat()) {
            return $this -> fetch();
        }
        if (!cmf_is_mobile()) {
            die('请用移动端浏览器打开');
        }
        //H5支付
        $weixn = new Weixin();
        $is_pay = json_decode($weixn -> weixin_pay($param['title'], $param['pay_order_number'], $param['pay_amount'], 'MWEB',
                                                   1, 0, 0, $param['pay_promote_id'], $param['game_id']), true);
        if ($is_pay['status'] == 1) {
        } else {
            return $this -> error('支付失败');
        }
        if (!empty($is_pay['mweb_url'])) {
            $pay_url = $this -> get_weixin_url($is_pay['mweb_url']);
            if (HPID > 0) {
                $url = '//' . cmf_get_option('admin_set')['web_site'] . '/mobile/pay/wechatJumpPage' . "?jump_url=" . urlencode($pay_url);
            } else {
                $url = $pay_url;
            }
            return $this->redirect($url);
        } else {
            return $this -> error('支付发生错误,请重试');
        }

    }

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

}
