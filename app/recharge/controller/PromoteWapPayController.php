<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\channelwap\controller\BaseController;
use app\common\logic\PayLogic;
use app\promote\model\PromotedepositModel;
use app\promote\model\PromotebindModel;
use think\Request;
use think\Db;
use think\weixinsdk\Weixin;

class PromoteWapPayController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_PAY != 1) {
            $this->error('请购买充值权限');
        }
        if (AUTH_PROMOTE != 1) {
            $this->error('请购买渠道权限');
        }

    }

    /**
     * [支付宝支付]
     * @param array $param
     * @return \SimpleXMLElement|string
     * @author yyh[gjt]
     */
    private function pay($param = array())
    {
        $table = empty($param['table'])?"promote_deposit":$param['table'];
        $out_trade_no = $param['pay_order_number'];
        $config = get_pay_type_set('zfb');
        $pay = new \think\Pay($param['apitype'], $config['config']);
        $discount = $param['discount']?:10;
        $vo = new \think\pay\PayVo();
        $vo->setBody($param['body'])
            ->setFee($param['price'])//支付金额
            ->setCost($param['cost'])//实际金额
            ->setTitle($param['title'])
            ->setOrderNo($out_trade_no)
            ->setService($param['server'])
            ->setSignType($param['signtype'])
            ->setTable($table)
            ->setPayWay($param['payway'])
            ->setUserId($param['to_id'])
            ->setAccount($param['to_account'])
            ->setGameId($param['game_id'])
            ->setPromoteId($param['promote_id'])
            ->setPayMethod("wap")
            ->setOther($param['type'])
            ->setPromoteName($param['promote_account'])
            ->setDiscount($discount);
        $this->redirect($pay->buildRequestForm($vo));
    }
    //渠道支付宝充值
    //yyh
    public function alipay()
    {
        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1, 'name' => 'zfb'])->find();
        if (empty($paytype)) {
            exit('支付宝支付通道已关闭，请选择其他支付方式');
        }
        echo '订单创建中...';
        $promote_id = PID;
        if (!$promote_id) {
            $this->redirect(url('channelsite/index/index'));
        }
        $data = $this->request->param();
        if($data['is_bind_pay']==1){
            return $this->alipay_bind($data);
        }
        $order_no = $data['order_no'] ?: "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
        $order_is_ex = Db::table('tab_promote_deposit')->field('id')->where(['pay_order_number' => $order_no])->find();
        if (!empty($order_is_ex)) {
            $this->error('订单已存在，请重新下单');
        }
        $to_id = get_promote_list(['account' => $data['account']], 'id')[0]['id'];
        if (!$to_id) {
            $this->error('渠道账号不存在');
        }
        if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
            $this->error('金额错误');
        }
        if(cmf_is_wechat()){
            $data['promote_id'] = PID;
            $url = url('@recharge/Promote_wap_alipay/wechat_alipay',$data,true,true);
            $this->redirect($url);
        }
        $request['apitype'] = 'alipay';
        $request['config'] = "alipay";
        $request['service'] = "alipay.wap.create.direct.pay.by.user";
        $request['signtype'] = "MD5";
        $request['price'] = $data['amount'];
        $request['payway'] = 3;
        $request['type'] = $data['type'];
        $request['title'] = '平台币充值';
        $request['body'] = '渠道平台币充值';
        $request['pay_order_number'] = $order_no;
        $request['promote_id'] = $promote_id;
        $request['to_id'] = $to_id;
        $request['to_account'] = $data['account'];
        $pay_url = $this->pay($request);


    }
    private function alipay_bind($data)
    {
        $data['is_alipay'] = 1;
        $request = $this->bindpay($data);
        $request['apitype'] = 'alipay';
        $request['config'] = "alipay";
        $request['service'] = "alipay.wap.create.direct.pay.by.user";
        $request['signtype'] = "MD5";
        $request['payway'] = 3;
        $pay_url = $this->pay($request);
    }
    //绑币充值参数
    private function bindpay($data)
    {
        $order_no = $data['order_no'] ?: "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
        $order_is_ex = Db::table('tab_promote_bind')->field('id')->where(['pay_order_number' => $order_no])->find();
        if (!empty($order_is_ex)) {
            $this->error('订单已存在，请重新下单');
        }
        $game_ids = array_column(session('bind_recharge_game'.PID),'id');
        $user_ids = array_column(session('bind_recharge_user'.PID),'user_id');
        if(in_array($data['game_id'],$game_ids)&&in_array($data['user_id'],$user_ids)){
            if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
                $this->error('金额错误');
            }
            $user = get_user_entity($data['user_id'],false,'promote_id');
            if(empty($user)){
                $this->error('用户不存在');
            }
            $game = get_game_entity($data['game_id'],'id');
            if(empty($game)){
                $this->error('游戏不存在');
            }
            if(cmf_is_wechat() && $data['is_alipay'] == 1){
                $data['promote_id'] = PID;
                $url = url('@recharge/Promote_wap_alipay/wechat_alipay',$data);
                $this->redirect($url);
            }
            $request['price'] = $data['amount'];
            $request['title'] = '绑定平台币代充';
            $request['body'] = '渠道绑定平台币代充';
            $request['pay_order_number'] = $order_no;
            $request['promote_id'] = $user['promote_id'];
            $request['to_id'] = $data['user_id'];
            $request['game_id'] = $data['game_id'];
            $_discount = get_promote_dc_discount($user['promote_id'],$data['game_id'],$data['user_id']);
            $request['discount'] = $_discount['discount']==0?10:$_discount['discount'];
            $request['price'] = round($request['price']*$request['discount']/10,2);
            $request['cost'] = $data['amount'];
            $request['table'] = 'promote_bind';
            return $request;
        }else{
            exit('订单失效');
        }
    }
    //渠道微信充值
    //yyh
    public function weixinpay()
    {
        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1, 'name' => 'wxscan'])->find();
        if (empty($paytype)) {
            exit('微信支付通道已关闭，请选择其他支付方式');
        }
        $promote_id = PID;
        if (!$promote_id) {
            $this->redirect(url('channelsite/index/index'));
        }
        $data = $this->request->param();
        if($data['is_bind_pay']==1){
           return $this->weixinpay_bind($data);
        }
        $order_no = $data['order_no'] ?: "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
        $order_is_ex = Db::table('tab_promote_deposit')->field('id')->where(['pay_order_number' => $order_no])->find();
        if (!empty($order_is_ex)) {
            $order_no = "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
        }
        $to_id = get_promote_list(['account' => $data['account']], 'id')[0]['id'];
        if (!$to_id) {
            $this->error('渠道账号不存在');
        }
        if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
            $this->error('金额错误');
        }
        $model = new PromotedepositModel();
        $param['pay_order_number'] = $order_no;
        $param['promote_id'] = $promote_id;
        $param['to_id'] = $to_id;
        $param['pay_amount'] = $data['amount'];
        $param['pay_way'] = 4;
        $param['type'] = $data['type'];
        $res = $model->add_promote_deposit($param);
        if($res == false){
            exit('订单创建失败');
        }
        if(cmf_is_wechat()){
            $param['title'] = "渠道平台币充值";
            return $this->get_wx_code($param);
        }else{
            $weixn = new Weixin();
            $is_pay = json_decode($weixn->weixin_pay("渠道平台币充值", $order_no, $data['amount'], 'MWEB'), true);
            if ($is_pay['status'] != 1) {
                $this->error('支付失败');
            }
            if (!empty($is_pay['mweb_url'])) {
                $url = '//' . cmf_get_option('admin_set')['web_site'] . '/channelwap/promote/wechatJumpPage' . "?jump_url=" . urlencode($is_pay['mweb_url'] . "&redirect_url=" . urlencode(url('channelwap/promote/balance', [], true, true)));
                $this->redirect($url);
            } else {
                $this->error('支付发生错误,请重试');
            }
        }
    }
    /**
     * [微信内公众号支付]
     * @author 郭家屯[gjt]
     */
    public function get_wx_code($param = '')
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
        $this->assign('jsApiParameters', $is_pay);
        $this->assign('hostdomain', $_SERVER['HTTP_HOST']);
        return $this->fetch('channelwap@promote/get_wx_code');
    }

    private function weixinpay_bind($data)
    {
        $param = $this->bindpay($data);
        $param['pay_way'] = 4;
        $model = new PromotebindModel();
        $res = $model->add_promote_bind($param);
        if ($res == false) {
            exit('订单创建失败');
        }
        if(cmf_is_wechat()){
            $param['title'] = "绑定平台币充值";
            $param['amount'] = $param['price'];
            return $this->get_wx_code($param);
        }else {
            $weixn = new Weixin();
            $is_pay = json_decode($weixn -> weixin_pay("绑定平台币充值", $param['pay_order_number'], $param['price'], 'MWEB'), true);
            if ($is_pay['status'] === 1) {
                $url = '//' . cmf_get_option('admin_set')['web_site'] . '/channelwap/promote/wechatJumpPage' . "?jump_url=" . urlencode($is_pay['mweb_url'] . "&redirect_url=" . urlencode(url('channelwap/promote/bind_balance', [], true, true)));
                $this -> redirect($url);
            } else {
                exit('微信订单创建失败');
            }
        }
    }
    public function ptb_bind_pay(){
        if($this->request->isAjax()){
            $data = $this->request->param();
            $request = $this->bindpay($data);
            if(empty($data['second_pwd'])){
                $this->error('请输入二级密码');
            }else{
                $promote = get_promote_entity(PID,'second_pwd,balance_coin');
                if(empty($promote['second_pwd'])){
                    $this->error('请先设置二级密码');
                }
                if (!xigu_compare_password($data['second_pwd'], $promote['second_pwd'])) {
                    $this->error('二级密码错误');
                }
            }
            if($request['price']<=0){
                $this->error('实付金额错误');
            }
            if($promote['balance_coin']<$request['price']){
                $this->error('平台币余额不足');
            }
            $request['pay_way'] = 2;
            $model = new PromotebindModel();
            $res = $model->add_promote_bind($request);
            if ($res != false) {
                $pay = Db::table('tab_promote')->where(['id'=>PID])->setDec('balance_coin',$request['price']);
                if($pay!=false){
                    Db::table('tab_user_play')->where(['user_id'=>$data['user_id'],'game_id'=>$data['game_id']])->setInc('bind_balance',$request['cost']);
                    $status = $model->where(['id'=>$res])->update(['pay_status'=>1]);
                    $this->success('充值成功');
                }else{
                    $this->error('扣款失败，请重试');
                }
            } else {
                $this->error('订单创建失败');
            }
        }
    }
    //获取订单号
    public function get_order()
    {
        $order_no = create_out_trade_no('TD_');
        echo json_encode(['order_no' => $order_no]);
    }
    //绑币充值订单号
    public function get_bind_order()
    {
        if($this->request->isAjax()){
            $order_no = create_out_trade_no('BR_');
            echo json_encode(['order_no' => $order_no]);
        }
    }

    public function wechat_alipay()
    {
        if(cmf_is_wechat()){
            return $this->fetch();
        }
        $data = $this->request->param();
        $url = url('@recharge/Promote_wap_alipay/alipay',$data);
        $this->redirect($url);
    }
}