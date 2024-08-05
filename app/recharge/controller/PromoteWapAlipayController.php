<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\common\controller\BaseHomeController;
use app\common\logic\PayLogic;
use app\promote\model\PromotedepositModel;
use app\promote\model\PromotebindModel;
use think\Request;
use think\Db;
use think\weixinsdk\Weixin;

class PromoteWapAlipayController extends BaseHomeController
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
        $data = $this->request->param();
        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1, 'name' => 'zfb'])->find();
        if (empty($paytype)) {
            exit('支付宝支付通道已关闭，请选择其他支付方式');
        }
        echo '订单创建中...';
        $promote_id = $data['promote_id'];
        if (!$promote_id) {
            $this->redirect(url('channelsite/index/index'));
        }
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
//        $game_ids = array_column(session('bind_recharge_game'.$data['promote_id']),'id');
//        $user_ids = array_column(session('bind_recharge_user'.$data['promote_id']),'user_id');
//        if(in_array($data['game_id'],$game_ids)&&in_array($data['user_id'],$user_ids)){
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
//        }else{
//            exit('订单失效');
//        }
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