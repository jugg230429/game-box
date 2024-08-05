<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\channelsite\controller\BaseController;
use app\common\logic\PayLogic;
use app\promote\model\PromotedepositModel;
use app\promote\model\PromotebindModel;
use think\Request;
use think\Db;
use think\weixinsdk\Weixin;

class PromotePayController extends BaseController
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

        // 渠道充值预付款 $table 设为 prepayment----------------START
        $tmp_prepayment_head = substr($out_trade_no,0,3);
        if($tmp_prepayment_head == 'PP_'){
            $table = 'prepayment';
            $discount = 10; // 无折扣
        }

        // 渠道充值预付款 $table 设为 prepayment----------------END

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
            ->setPayMethod("direct")
            ->setOther($param['type'])
            ->setPromoteName($param['promote_account'])
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
        $promote_id = PID;
        if (!$promote_id) {
            $this->redirect(url('channelsite/index/index'));
        }
        $data = $this->request->param();
        $is_prepayment = $data['prepayment'] ?? 0; // 是否是渠道充值预付款
        if($data['is_bind_pay']==1){
            return $this->alipay_bind($data);
        }

        if($is_prepayment == 1){
            // 渠道充值预付款 再次判断订单是否存在
            $order_no = $data['order_no'] ?: "PP_" . date('Ymd') . date('His') . sp_random_string(4);//渠道预付款充值
            $order_is_ex = Db::table('tab_promote_prepayment_recharge')->field('id')->where(['pay_order_number' => $order_no])->find();
            if (!empty($order_is_ex)) {
                $this->error('订单已存在，请重新下单');
            }
        }else{
            $order_no = $data['order_no'] ?: "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
            $order_is_ex = Db::table('tab_promote_deposit')->field('id')->where(['pay_order_number' => $order_no])->find();
            if (!empty($order_is_ex)) {
                $this->error('订单已存在，请重新下单');
            }
        }
        
        $to_id = get_promote_list(['account' => $data['account']], 'id')[0]['id'];
        if (!$to_id) {
            $this->error('渠道账号不存在');
        }
        if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
            $this->error('金额错误');
        }
        $request['apitype'] = "alipay";
        $request['config'] = "alipay";
        $request['signtype'] = "MD5";
        $request['server'] = "create_direct_pay_by_user";
        $request['price'] = $data['amount'];
        $request['payway'] = 3;
        $request['type'] = $data['type'];
        $request['title'] = '平台币充值';
        $request['body'] = '渠道平台币充值';
        $request['pay_order_number'] = $order_no;
        $request['promote_id'] = $promote_id;
        $request['to_id'] = $to_id;
        $request['to_account'] = $data['account'];
        if($is_prepayment == 1){
            $request['title'] = '渠道充值预付款';
            $request['body'] = '渠道预付款充值';
        }
        $pay_url = $this->pay($request);


    }
    private function alipay_bind($data)
    {
        $request = $this->bindpay($data);
        $request['apitype'] = "alipay";
        $request['config'] = "alipay";
        $request['signtype'] = "MD5";
        $request['server'] = "create_direct_pay_by_user";
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
            $request['price'] = $data['amount'];
            $request['title'] = '绑定平台币代充';
            $request['body'] = '渠道绑定平台币代充';
            $request['pay_order_number'] = $order_no;
            $request['promote_id'] = $user['promote_id'];
            $request['to_id'] = $data['user_id'];
            $request['game_id'] = $data['game_id'];
            //调整查询折扣信息-20210429-byh
            $_discount = get_promote_dc_discount($user['promote_id'],$data['game_id'],$data['user_id']);
            $request['discount'] = $_discount['discount']==0?10:$_discount['discount'];
//            $request['discount'] = $game['discount']==0?10:$game['discount'];
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
        $is_prepayment = $data['prepayment'] ?? 0; // 是否是渠道充值预付款
        if($data['is_bind_pay']==1){
           return $this->weixinpay_bind($data);
        }

        if($is_prepayment == 1){
            // 渠道充值预付款 再次判断订单是否存在
            $order_no = $data['order_no'] ?: "PP_" . date('Ymd') . date('His') . sp_random_string(4);// 渠道充值预付款
            $order_is_ex = Db::table('tab_promote_prepayment_recharge')->field('id')->where(['pay_order_number' => $order_no])->find();
            if (!empty($order_is_ex)) {
                // 重新生成订单
                $order_no = create_out_trade_no('PP_');
                if(empty($order_no)){
                    $this->error('订单状态有误,请重新下单');
                }
            }
        }else{
            $order_no = $data['order_no'] ?: "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
            $order_is_ex = Db::table('tab_promote_deposit')->field('id')->where(['pay_order_number' => $order_no])->find();
            if (!empty($order_is_ex)) {
                $order_no = "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
            }
        }

        $to_id = get_promote_list(['account' => $data['account']], 'id')[0]['id'];
        if (!$to_id) {
            $this->error('渠道账号不存在');
        }
        if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
            $this->error('金额错误');
        }
        $order_no = $data['order_no'];
        $model = new PromotedepositModel();
        $param['pay_order_number'] = $order_no;
        $param['promote_id'] = $promote_id;
        $param['to_id'] = $to_id;
        $param['pay_amount'] = $data['amount'];
        $param['pay_way'] = 4;
        $param['type'] = $data['type'];
         // 渠道充值预付款----------------
        $tmp_prepayment_head = substr($order_no,0,3);
        if($tmp_prepayment_head == 'PP_'){
            // 渠道充值预付款
            // $res = $model->add_promote_deposit($param);
            $prepayment_data['order_number'] = "";
            $prepayment_data['pay_order_number'] = $param['pay_order_number'];
            $prepayment_data['promote_id'] = $param['promote_id'];
            $prepayment_data['promote_account'] = get_promote_name($param['promote_id']);;
            $prepayment_data['pay_amount'] = $param['pay_amount'];
            $prepayment_data['pay_status'] = 0;
            $prepayment_data['pay_way'] = $param['pay_way'];
            // $prepayment_data['type'] = $param['type'];
            $prepayment_data['create_time'] = time();
            $prepayment_data['pay_ip'] = get_client_ip();
            $insert_res = Db::table('tab_promote_prepayment_recharge')->insert($prepayment_data);

            if ($insert_res != false) {
                $weixn = new Weixin();
                $is_pay = json_decode($weixn->weixin_pay("渠道充值预付款", $order_no, $data['amount'], 'NATIVE'), true);
                if ($is_pay['status'] === 1) {
                    $this->assign('order_data', ['pay_order_number' => $order_no, 'pay_amount' => $data['amount'], 'wx_url' => $is_pay['url']]);
                    return $this->fetch('channelsite@promote/wechat_pay2');
                } else {
                    exit('微信订单创建失败');
                }
            } else {
                exit('订单创建失败');
            }
        }else{
            // 原本的充值
            $res = $model->add_promote_deposit($param);
            if ($res != false) {
                $weixn = new Weixin();
                $is_pay = json_decode($weixn->weixin_pay("渠道平台币充值", $order_no, $data['amount'], 'NATIVE'), true);
                if ($is_pay['status'] === 1) {
                    $this->assign('order_data', ['pay_order_number' => $order_no, 'pay_amount' => $data['amount'], 'wx_url' => $is_pay['url']]);
                    return $this->fetch('channelsite@promote/wechat_pay');
                } else {
                    exit('微信订单创建失败');
                }
            } else {
                exit('订单创建失败');
            }
        }  
        
    }
    private function weixinpay_bind($data)
    {
        $param = $this->bindpay($data);
        $param['pay_way'] = 4;
        $model = new PromotebindModel();
        $res = $model->add_promote_bind($param);
        if ($res != false) {
            $weixn = new Weixin();
            $is_pay = json_decode($weixn->weixin_pay("绑定平台币充值", $param['pay_order_number'], $param['price'], 'NATIVE'), true);
            if ($is_pay['status'] === 1) {
                $this->assign('order_data', ['pay_order_number' => $param['pay_order_number'], 'pay_amount' => $param['price'], 'is_bind_pay' => 1, 'wx_url' => $is_pay['url']]);
                return $this->fetch('channelsite@promote/wechat_pay');
            } else {
                exit('微信订单创建失败');
            }
        } else {
            exit('订单创建失败');
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

    //获取 预付款 订单号
    public function get_prepayment_order()
    {
        $order_no = create_out_trade_no('PP_');
        echo json_encode(['order_no' => $order_no]);
    }
}