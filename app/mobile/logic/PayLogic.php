<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\mobile\logic;

use app\member\model\UserTransactionModel;
use app\member\model\UserTransactionOrderModel;
use app\common\logic\PayLogic as commonlogic;
use app\recharge\logic\CheckAgeLogic;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class PayLogic{

    /**
     * @函数或方法说明
     * @平台币充值
     * @author: 郭家屯
     * @since: 2020/7/11 9:23
     */
    public function get_ptb_pay($data=[])
    {
        $user = get_user_entity($data['account'], true,'id,account,promote_id,promote_account');
        if (empty($user)) {
            return ['err'=>1,'msg'=>'用户不存在'];
        }
        $data['money'] = (int)$data['money'];
        if ($data['money'] < 1) {
            return ['err'=>1,'msg'=>'金额不能低于1元'];
        }
        if (pay_type_status('wxscan') != 1) {
            return ['err'=>1,'msg'=>'微信支付未开启'];
        }
        $data['cost'] = $data['money'];
        if($data['coin_type'] == 1){
            if(empty($data['game_id'])){
                return ['err'=>1,'msg'=>'请选择游戏'];
            }
            $userplay = Db::table('tab_user_play')->field('user_id,user_account,game_id,game_name')->where('user_account',$data['account'])->where('game_id',$data['game_id'])->find();
            $data['game_name'] = $userplay['game_name'];
            if(!$userplay){
                return ['err'=>1,'msg'=>'游戏角色不存在'];
            }
            //获取折扣
//            $discount = get_game_entity($data['game_id'],'bind_recharge_discount')['bind_recharge_discount'];
//            $data['discount'] = $discount;
            $title = "绑币充值";
            $data['pay_order_number'] = create_out_trade_no("PB_");
            //获取折扣-绑币充值折扣新增-byh-20210825-start
            $lPay = new \app\common\logic\PayLogic();
            $discount_info = $lPay -> get_bind_discount($data['game_id'], $user['promote_id'], $user['id']);
            $data['discount'] = $discount_info['discount']*10;
            $data['discount_type'] = $discount_info['discount_type'];
            $data['money'] = round($data['money']*$discount_info['discount'],2);
            //获取折扣-绑币充值折扣新增-byh-20210825-end
//            $data['money'] = round($data['money']*$discount/10,2);
        }else{
            $title = "平台币充值";
            $data['pay_order_number'] = create_out_trade_no("PF_");

        }
        $data['title'] = $title;
        $data['order_no'] = $data['pay_order_number'];
        $data['pay_amount'] = $data['money'];
        $data['pay_status'] = 0;
        $data['pay_way'] = 4;
        $data['user_id'] = $user['id'];
        $data['spend_ip'] = get_client_ip();
        //微信内部支付
        if($data['coin_type'] == 1){
            add_bind($data,$user);
        }else{
            add_deposit($data,$user);
        }
        return $data;

    }

    /**
     * @函数或方法说明
     * @小号交易公众号支付
     * @author: 郭家屯
     * @since: 2020/7/11 9:43
     */
    public function get_trade_pay($request=[])
    {
        $model = new UserTransactionModel();
        $transaction = $model->where('id',$request['transaction_id'])->where('status','in',[-1,1])->find();
        if(!$transaction){
            return ['err'=>1,'msg'=>'商品已出售或者已下架'];
        }
        //锁定交易
        if($transaction['status'] == 1){
            $save['status'] = -1;
            $save['lock_time'] = time();
            $model->where('id',$request['transaction_id'])->update($save);
        }else{
            $ordermodel = new UserTransactionOrderModel();
            $order = $ordermodel->where('transaction_id',$request['transaction_id'])->field('id,user_id')->order('id desc')->find();
            if($order['user_id'] != session('member_auth.user_id')){
                return ['err'=>1,'msg'=>'当前商品已被锁定，可购买其他商品'];
            }else{
                $model->where('id',$request['transaction_id'])->setField('lock_time',time());
                $ordermodel->where('id',$order['id'])->setField('pay_status',2);
            }
        }
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,balance');
        if (pay_type_status('wxscan') != 1) {
            return ['err'=>1,'msg'=>'微信支付未开启'];
        }
        $data['pay_order_number'] = create_out_trade_no("TO_");
        $data['pay_amount'] = $transaction['money'];
        $data['pay_status'] = 0;
        $data['pay_way'] = 4;
        $data['user_id'] = $user['id'];
        $data['title'] = "交易订单支付";
        $fee = cmf_get_option('transaction_set')['fee'];
        $min_dfee = cmf_get_option('transaction_set')['min_dfee'];
        $fee_money = 0;
        if($fee){
            $fee_money = $transaction['money'] * $fee/100;
        }
        if($min_dfee){
            if($min_dfee > $fee_money ){
                $fee_money = $min_dfee;
            }
        }
        $data['fee'] = $fee_money;
        if($request['is_balance']){
            $data['pay_amount'] = $transaction['money'] - $user['balance'];
            $data['balance_money'] = $user['balance'];
        }
        $data['time'] = time();
        $data['time_expire'] = date('YmdHis',$data['time'])+301;

        $logic = new commonlogic();
        //微信内部支付
        $logic -> add_transaction($user, $transaction, $data);
        return $data;
    }

    /**
     * @函数或方法说明
     * @重新调起小号支付
     * @author: 郭家屯
     * @since: 2020/7/11 9:57
     */
    public function get_continue_trade_pay($request=[])
    {
        $id = $request['id'];
        $model = new UserTransactionOrderModel();
        $order = $model->where('id',$id)->where('pay_status',0)->find();
        if(!$order || (time()-$order['pay_time'])>300){
            return ['err'=>1,'msg'=>'订单已失效'];
        }else{
            //更新锁定时间
            $model = new UserTransactionModel();
            $model->where('id',$order['transaction_id'])->setField('lock_time',time());
        }
        if (pay_type_status('wxscan') != 1) {
            return ['err'=>1,'msg'=>'微信支付未开启'];
        }
        $data['pay_amount'] = $order['pay_amount']-$order['balance_money'];
        $data['time_expire'] = date('YmdHis',$order['create_time'])+301;
        $data['pay_order_number'] = $order['pay_order_number'];
        $data['title'] = "交易订单支付";
        return $data;
    }

    /**
     * @函数或方法说明
     * @尊享卡支付
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/8/17 17:00
     */
    public function get_member_pay($data=[])
    {
        $mcard_set = cmf_get_option('mcard_set');
        if($mcard_set['status'] == 0){
            return ['err'=>1,'msg'=>'尊享卡未开启'];
        }
        if($mcard_set['config1']['card_name'] == $data['card_name']){
            $card = $mcard_set['config1'];
        }elseif($mcard_set['config2']['card_name'] == $data['card_name']){
            $card = $mcard_set['config2'];
        }elseif($mcard_set['config3']['card_name'] == $data['card_name']){
            $card = $mcard_set['config3'];
        }else{
            return ['err'=>1,'msg'=>'会员卡类型不存在'];
        }
        if(empty($card['price']) || empty($card['days'])){
            return ['err'=>1,'msg'=>'尊享卡配置错误，请联系客服'];
        }
        if ($card['price'] < 0.01) {
            return ['err'=>1,'msg'=>'金额不能为空'];
        }
        $data['pay_amount'] = $card['price'];
        $data['days'] = $card['days'];
        $data['free_days'] = $card['free_days'];
        $data['member_name'] = $card['card_name'];
        $data['pay_way'] = 4;
        $data['title'] = "尊享卡会员充值";
        $data['pay_order_number'] = create_out_trade_no("MC_");
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,promote_id,promote_account');
        if (empty($user)) {
            return ['err'=>1,'msg'=>'用户不存在'];
        }
        $result = add_member($data,$user);
        if($result){
            return $data;
        }else{
            return ['err'=>1,'msg'=>'写入订单失败'];
        }
    }

    /**
     * @函数或方法说明
     * @页游扫码支付
     * @author: 郭家屯
     * @since: 2020/9/25 14:52
     */
    public function get_yygame_pay($data=[])
    {
        $user = get_user_entity($data['user_id'], false,'id,account,promote_id,promote_account');
        if (empty($user)) {
            return ['err'=>1,'msg'=>'用户不存在'];
        }
        $data['money'] = (int)$data['money'];
        if ($data['money'] < 1) {
            return ['err'=>1,'msg'=>'金额不能低于1元'];
        }
        if (pay_type_status('wxscan') != 1) {
            return ['err'=>1,'msg'=>'微信支付未开启'];
        }
        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($user['id'], $data['money']);
            if (false === $checkAgeRes) {
                return ['err' => 1, 'msg' => $lCheckAge -> getErrorMsg()];
            }
        }
        $data['cost'] = $data['money'];
        $data['title'] = $title = "游戏充值";
        $userplay = Db::table('tab_user_play_record')
                ->field('id')
                ->where('user_id',$user['id'])
                ->where('game_id',$data['game_id'])
                ->where('server_id',$data['server_id'])
                ->find();
        if(!$userplay){
            return ['err'=>1,'msg'=>'游戏角色不存在'];
        }
        $game = get_game_entity($data['game_id'],'game_name,sdk_version');
        $data['game_name'] = $game['game_name'];
        $data['sdk_version'] = $game['sdk_version'];
        $data['server_name'] = get_server_name($data['server_id']);
        $lPay = new \app\common\logic\PayLogic();
        $discount_info = $lPay -> get_discount($data['game_id'], $user['promote_id'], $user['id']);
        $data['discount'] = $discount_info['discount'];
        $data['discount_type'] = $discount_info['discount_type'];
        $data['money'] = round($data['money']*$discount_info['discount'],2);
        $data['extra_param'] = cmf_get_domain();
        $data['pay_amount'] = $data['money'];
        $data['pay_order_number'] = create_out_trade_no('SP_');
        $data['pay_status'] = 0;
        $data['pay_way'] = 4;
        $data['user_id'] = $user['id'];
        $data['spend_ip'] = get_client_ip();
        $result =  add_spend($data,$user);
        if($result){
            return $data;
        }else{
            return ['err'=>1,'msg'=>'写入订单失败'];
        }
    }
}
