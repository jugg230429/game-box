<?php

namespace app\callback\controller;

use app\promote\logic\CustompayLogic;
use think\Db;

/**
 * 支付回调控制器
 */
class NotifyController extends BaseController
{

    /**
     * [支付宝通知方法]
     * @author 郭家屯[gjt]
     */
    public function alipay_callback()
    {
        //获取回调参数
        $methodtype = $this->request->param('methodtype');
        $module = $this->request->param('module');
        if ($this->request->isPost()) {
            $notify = $this->request->post();
            $notify['fund_bill_list'] = htmlspecialchars_decode($notify['fund_bill_list']);//去除转义
        } elseif ($this->request->isGet()) {
            $notify = $this->request->get();
            unset($notify['methodtype']);
            unset($notify['module']);
        } else {
            $notify = file_get_contents("php://input");
            if (empty($notify)) {
                $this->record_logs("Access Denied");
                exit('Access Denied');
            }
        }
        $public_url = ROOT_PATH . "/app/sdk/secretKey/alipay/alipay2_public_key.txt";
        if (!file_exists($public_url)) {
            $public_url = ROOT_PATH . "/app/callback/secretKey/alipay/alipay2_public_key.txt";
        }
        //获取当前推广员支付宝公钥
        $promoteOrder = $this -> get_order_promote_id($notify['out_trade_no']);
        $lCustomPay = new CustompayLogic();
        $customConfig = $lCustomPay -> getPayConfig($promoteOrder['pay_promote_id'], $promoteOrder['game_id'], 'zfb', $promoteOrder['pay_amount']);
        if (false === $customConfig) {
            $rsaPublicKey = file_get_contents($public_url);
        } else {
            $rsaPublicKey = $customConfig['public_key'];
        }

        Vendor('alipay.AopSdk');
        $aop = new \AopClient();
        $aop->alipayrsaPublicKey = $rsaPublicKey;
        $result = $aop->rsaCheckV1($notify, '', 'RSA2');
        if ($result) {
            //获取回调订单信息
            if ($methodtype == "notify") {
                $order_info = $notify;
                if ($order_info['trade_status'] == 'TRADE_SUCCESS') {
                    $order_info['real_amount'] = $order_info['total_amount'];
                    $pay_where = substr($order_info['out_trade_no'], 0, 2);
                    switch ($pay_where) {
                        case 'SP':
                            if (!$this->recharge_is_exist($order_info['out_trade_no'])) {
                                //支付方式
                                $order_info['pay_type'] = 'zfb';
                                $r = $this->set_spend($order_info);
                            }
                            break;
                        case 'PF':
                            if (!$this->deposit_is_exist($order_info["out_trade_no"])) {
                                $r = $this->set_deposit($order_info);
                            }
                            break;
                        case 'TD'://渠道平台币充值
                            if (!$this->promote_deposit_is_exist($order_info["out_trade_no"])) {
                                $r = $this->set_promote_deposit($order_info);
                            }
                            break;
                        case 'PP'://渠道充值预付款
                            if (!$this->promote_prepayment_is_exist($order_info["out_trade_no"])) {
                                $r = $this->set_promote_prepayment($order_info);
                            }
                            break;
                        case 'PB'://绑币充值
                            if (!$this->bind_is_exist($order_info["out_trade_no"])) {
                                $r = $this->set_bind($order_info);
                            }
                            break;
                        case 'BR'://绑币代充
                            if (!$this->bind_is_exist($order_info["out_trade_no"])) {
                                $r = $this->set_promote_bind($order_info);
                            }
                            break;
                        case 'TO'://小号交易
                            if (!$this->transaction_is_exist($order_info["out_trade_no"])) {
                                $r = $this->set_transaction($order_info);
                            }
                            break;
                        case 'LY'://联运币
                            if (!$this->lyb_is_exist($order_info["out_trade_no"])) {
                                $r = $this->set_lyb_balance($order_info);
                            }
                            break;
                        case 'MC'://尊享卡
                            if (!$this->member_is_exist($order_info["out_trade_no"])) {
                                $r = $this->set_member($order_info);
                            }
                            break;
                        case 'FF'://分发支付
                            if (!$this->sue_spend_is_exist($order_info["out_trade_no"])) {
                                $order_info['pay_way'] = 3;
                                $r = $this->set_sue_spend($order_info);
                            }
                            break;
                        case 'SS'://超级签支付
                            if (!$this->supersign_order_is_exist($order_info["out_trade_no"])) {
                                $r = $this->set_supersign_order($order_info);
                            }
                            break;
                        case 'PG'://超级签游戏支付支付 pay game
                            if (!$this->pay_game_order_is_exist($order_info["out_trade_no"])) {
                                $r = $this->set_pay_game_order($order_info);
                            }
                            break;
                        default:
                            exit('accident order data');
                            break;
                    }
                    if ($r) {
                        echo "success";
                    }
                } else {
                    $this->record_logs("支付失败！");
                    echo "fail";
                }
            } elseif ($methodtype == "return") {
                $pay_where = substr($notify['out_trade_no'], 0, 2);
                switch ($pay_where) {
                    case 'SP':
                        $map['pay_order_number'] = $notify['out_trade_no'];
                        $spend = Db::table("tab_spend")->field("sdk_version")->where($map)->find();
                        if($spend['sdk_version'] == 3){
                            return redirect(cmf_get_domain() . '/sdkh5/pay/pay_success/out_trade_no/' . $notify['out_trade_no']);
                        }else{
                            return redirect(cmf_get_domain() . '/sdk/pay/pay_success/out_trade_no/' . $notify['out_trade_no']);
                        }
                        break;
                    case 'PF':
                        if ($module == 'media') {
                            return redirect(cmf_get_domain() . '/media/pay/payresult/order_no/' . $notify['out_trade_no']);
                        }elseif($module == 'app'){
                            return redirect(cmf_get_domain() . '/app/pay/pay_success/out_trade_no/' . $notify['out_trade_no']);
                        } else {
                            return redirect(cmf_get_domain() . '/sdk/pay/pay_success/out_trade_no/' . $notify['out_trade_no']);
                        }
                        break;
                    case 'PB':
                        if ($module == 'media') {
                            return redirect(cmf_get_domain() . '/media/pay/payresult/order_no/' . $notify['out_trade_no']);
                        }elseif($module == 'app'){
                            return redirect(cmf_get_domain() . '/app/pay/pay_success/out_trade_no/' . $notify['out_trade_no']);
                        } else {
                            return redirect(cmf_get_domain() . '/sdk/pay/pay_success/out_trade_no/' . $notify['out_trade_no']);
                        }
                        break;
                    case 'TD'://渠道平台币充值
                        if(cmf_is_mobile()){
                            return redirect(cmf_get_domain() . '/channelwap/promote/record');
                        }
                        return redirect(cmf_get_domain() . '/channelsite/promote/check_order_status/order_no/' . $notify['out_trade_no']);
                        break;
                    case 'PP': //渠道充值保证金
                        if(cmf_is_mobile()){
                            return redirect(cmf_get_domain() . '/channelwap/promote/record');
                        }
                        return redirect(cmf_get_domain() . '/channelsite/promote/check_pp_order_status/order_no/' . $notify['out_trade_no']);
                        break;
                    case 'BR'://渠道绑定平台币代充
                        if(cmf_is_mobile()){
                            return redirect(cmf_get_domain() . '/channelwap/promote/bind_record');
                        }
                        return redirect(cmf_get_domain() . '/channelsite/promote/check_bind_order_status/order_no/' . $notify['out_trade_no']);
                        break;
                    case 'TO'://小号交易
                        return redirect(cmf_get_domain() . '/mobile/trade/record/tmp_flag/1');
                        break;
                    case 'LY'://联运币
                        return redirect(cmf_get_domain() . '/issue/currency/check_order_status/order_no/' . $notify['out_trade_no']);
                        break;
                    case 'MC'://尊享卡
                        if($module == 'app'){
                            return redirect(cmf_get_domain() . '/app/pay/pay_success/out_trade_no/' . $notify['out_trade_no']);
                        }else{
                            return redirect(cmf_get_domain() . '/mobile/user/index');
                        }
                        break;
                    case 'FF'://分发支付订单
                        return redirect(cmf_get_domain() . '/issuesy/sdk/pay_success/out_trade_no/' . $notify['out_trade_no']);
                        break;
                    case 'SS'://超级签订单
                        return redirect(cmf_get_domain() . '/mobile/downfile/download_app');
                        break;
                    case 'PG'://超级签游戏订单 pay game
                        return redirect(cmf_get_domain() . '/mobile/game/detail/out_trade_no/'.$notify['out_trade_no']);
                        break;
                    default:
                        exit('accident return order data');
                        break;
                }
            }
        } else {
            $this->record_logs("支付验证失败");
            return redirect(cmf_get_domain() . '/media', 3, '支付验证失败');
        }
    }

    /**
     * [微信回调]
     * @return \think\response\Redirect|void
     * @author 郭家屯[gjt]
     */
    public function wxpay_callback()
    {
        Vendor("wxPayPubHelper.WxPayPubHelper");
        $request = file_get_contents("php://input");
        $reqdata = xmlToArray($request);

        file_put_contents('1.txt',json_encode($reqdata));

        if ($reqdata['return_code'] != 'SUCCESS') {
            $this->record_logs("return_code返回数据错误");
            exit();
        } else {

            $lCustomPay = new CustompayLogic();
            $promoteOrderInfo = $this->get_order_promote_id($reqdata['out_trade_no']);

            $method = $this->request->param('method');
            if ($method == "notify2") {//sdk

                $customConfig = $lCustomPay -> getPayConfig($promoteOrderInfo['pay_promote_id'],$promoteOrderInfo['game_id'], 'wxscan', $promoteOrderInfo['pay_amount']);
                if(false===$customConfig){
                    $pay_set = get_pay_type_set('wxscan');
                }else{
                    $pay_set['config'] = $customConfig;
                }

                $Common_util_pub = new \Common_util_pub($pay_set['config']['appid'], $pay_set['config']['pid'], $pay_set['config']['key']);
            } elseif ($method == "notify3") { //app

                $customConfig = $lCustomPay -> getPayConfig($promoteOrderInfo['pay_promote_id'],$promoteOrderInfo['game_id'], 'wxapp', $promoteOrderInfo['pay_amount']);

                if(false===$customConfig){
                    $spend = Db::table('tab_spend')->field('id,is_weiduan,game_id')->where('pay_order_number',$reqdata['out_trade_no'])->find();
                    if($spend['is_weiduan']){
                        $param = Db::table('tab_spend_wxparam')->field('appid,partner,key')->where('game_id',$spend['game_id'])->find();
                        $Common_util_pub = new \Common_util_pub($param['appid'],$param['partner'],$param['key']);
                    }else{
                        $pay_set = get_pay_type_set('wxapp');
                        $Common_util_pub = new \Common_util_pub($pay_set['config']['appid'], $pay_set['config']['pid'], $pay_set['config']['key']);
                    }
                }else{
                    $pay_set['config'] = $customConfig;
                    $Common_util_pub = new \Common_util_pub($pay_set['config']['appid'], $pay_set['config']['pid'], $pay_set['config']['key']);
                }


            } elseif ($method == "notify") {//扫码
                $customConfig = $lCustomPay -> getPayConfig($promoteOrderInfo['pay_promote_id'],$promoteOrderInfo['game_id'], 'wxscan', $promoteOrderInfo['pay_amount']);
                if(false===$customConfig){
                    $pay_set = get_pay_type_set('wxscan');
                }else{
                    $pay_set['config'] = $customConfig;
                }
                $Common_util_pub = new \Common_util_pub($pay_set['config']['appid'], $pay_set['config']['pid'], $pay_set['config']['key']);
            }
            if ($Common_util_pub->getSign($reqdata) == $reqdata['sign']) {
                $pay_where = substr($reqdata['out_trade_no'], 0, 2);
                $data['trade_no'] = $reqdata['transaction_id'];
                $data['out_trade_no'] = $reqdata['out_trade_no'];
                $data['real_amount'] = $reqdata['total_fee'] / 100;
                switch ($pay_where) {
                    case 'SP'://充值游戏
                        if ($this->recharge_is_exist($reqdata['out_trade_no'])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        //支付方式
                        if ($method == 'notify' || $method == 'notify2') {
                            $data['pay_type'] = 'wxscan';
                        } else {
                            $data['pay_type'] = 'wxapp';
                        }
                        $result = $this->set_spend($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'PF'://充值平台币
                        if ($this->deposit_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $result = $this->set_deposit($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'PB'://充值绑币
                        if ($this->bind_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $result = $this->set_bind($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'TD'://渠道平台币充值
                        if ($this->promote_deposit_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $result = $this->set_promote_deposit($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'PP'://渠道预付款充值
                        if ($this->promote_prepayment_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $result = $this->set_promote_prepayment($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'BR'://渠道代充绑定平台币
                        if ($this->promote_bind_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $result = $this->set_promote_bind($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'TO':
                        if ($this->transaction_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $result = $this->set_transaction($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'LY':
                        if ($this->lyb_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $result = $this->set_lyb_balance($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'MC':
                        if ($this->member_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $result = $this->set_member($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'FF':
                        if ($this->sue_spend_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $data['pay_way'] = 4;
                        $result = $this->set_sue_spend($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'SS':
                        if ($this->supersign_order_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $result = $this->set_supersign_order($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    case 'PG'://超级签游戏支付支付 pay game
                        if ($this->pay_game_order_is_exist($reqdata["out_trade_no"])) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                            exit();
                        }
                        $result = $this->set_pay_game_order($data);
                        if ($result) {
                            echo " <xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        } else {
                            echo " <xml> <return_code><![CDATA[FAILURE]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>";
                        }
                        break;
                    default:
                        $this->record_logs("订单号错误！！");
                        break;
                }
            } else {
                $this->record_logs("支付验证失败");
                return redirect(cmf_get_domain() . '/sdk/Pay/notice/msg/' . urlencode('支付验证失败'));
            }
        }
    }

    /**
     *判断平台币充值是否存在
     */
    protected function deposit_is_exist($out_trade_no)
    {
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_spend_balance')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *判断绑币充值是否存在
     */
    protected function bind_is_exist($out_trade_no)
    {
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_spend_bind')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    //判断充值是否存在

    protected function recharge_is_exist($out_trade_no = '')
    {
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_spend')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *判断平台币充值是否存在
     */
    protected function promote_deposit_is_exist($out_trade_no)
    {
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_promote_deposit')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    // 判断渠道预付款充值是否存在
     protected function promote_prepayment_is_exist($out_trade_no)
    {
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_promote_prepayment_recharge')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }
    /**
     *判断平台币充值是否存在
     */
    protected function promote_bind_is_exist($out_trade_no)
    {
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_promote_bind')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 判断交易订单是否存在
     */
    protected function transaction_is_exist($out_trade_no)
    {
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_user_transaction_order')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    protected function lyb_is_exist($out_trade_no)
    {
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_issue_open_user_balance')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    protected function member_is_exist($out_trade_no){
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_user_member')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    protected function sue_spend_is_exist($out_trade_no){
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_issue_spend')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    protected function supersign_order_is_exist($out_trade_no)
    {
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_app_supersign_order')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    protected function pay_game_order_is_exist($out_trade_no)
    {
        $map['pay_status'] = 1;
        $map['pay_order_number'] = $out_trade_no;
        $res = Db::table('tab_game_ios_pay_to_download_order')->field('id')->where($map)->find();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }


}
