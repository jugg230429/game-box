<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\issue\controller\ManagementBaseController;
use app\common\logic\PayLogic;
use app\issue\model\BalanceModel;
use think\Db;
use think\weixinsdk\Weixin;

class IssuePayController extends ManagementBaseController
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
            ->setTable($param['table'])
            ->setPayWay($param['payway'])
            ->setPayMethod("direct")
            ->setOpenUserId($param['open_user_id'])
            ->setDiscount($discount);
        echo $pay->buildRequestForm($vo);
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
        if (!is_numeric(OID)) {
            $this->redirect(url('issue/index/index'));
        }
        $data = $this->request->param();
        $order_no = $data['order_no'] ?: "LY_" . date('Ymd') . date('His') . sp_random_string(4);
        $order_is_ex = Db::table('tab_issue_open_user_balance')->field('id')->where(['pay_order_number' => $order_no])->find();
        if (!empty($order_is_ex)) {
            $this->error('订单已存在，请重新下单');
        }
        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $data['amount'])) {
            $this->error('金额错误');
        }
        $request['apitype'] = "alipay";
        $request['config'] = "alipay";
        $request['signtype'] = "MD5";
        $request['server'] = "create_direct_pay_by_user";
        $request['price'] = $data['amount'];
        $request['payway'] = 3;
        $request['title'] = '联运币充值';
        $request['body'] = '联运币充值'.$data['amount'].'元';
        $request['pay_order_number'] = $order_no;
        $request['open_user_id'] = OID;
        $request['table'] = 'issue_open_user_balance';
        $pay_url = $this->pay($request);


    }
    //渠道微信充值
    //yyh
    public function weixinpay()
    {
        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1, 'name' => 'wxscan'])->find();
        if (empty($paytype)) {
            exit('微信支付通道已关闭，请选择其他支付方式');
        }
        if (!is_numeric(OID)) {
            $this->redirect(url('issue/index/index'));
        }
        $data = $this->request->param();
        $order_no = $data['order_no'] ?: "LY_" . date('Ymd') . date('His') . sp_random_string(4);
        $order_is_ex = Db::table('tab_issue_open_user_balance')->field('id')->where(['pay_order_number' => $order_no])->find();
        if (!empty($order_is_ex)) {
            $this->error('订单已存在，请重新下单');
        }
        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $data['amount'])) {
            $this->error('金额错误');
        }
        $model = new BalanceModel();
        $param['user_id'] = OID;
        $param['pay_order_number'] = $order_no;
        $param['pay_amount'] = $data['amount'];
        $param['props_name'] = $data['amount'].'元';
        $param['pay_way'] = 4;
        $param['pay_time'] = time();
        $param['pay_ip'] = $this->request->ip();
        $res = $model->add_issue_open_user_balance($param);
        if ($res != false) {
            $weixn = new Weixin();
            $is_pay = json_decode($weixn->weixin_pay("联运币充值", $order_no, $data['amount'], 'NATIVE'), true);
            if ($is_pay['status'] === 1) {
                $this->assign('order_data', ['pay_order_number' => $order_no, 'pay_amount' => $data['amount'], 'wx_url' => $is_pay['url']]);
                return $this->fetch('issue@currency/wechat_pay');
            } else {
                exit('微信订单创建失败');
            }
        } else {
            exit('订单创建失败');
        }
    }
}