<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\channelwap\logic;

use app\promote\model\PromotedepositModel;
use app\promote\model\PromotebindModel;
use app\common\logic\PayLogic as commonlogic;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class PayLogic{

    /**
     * @函数或方法说明
     * @公众号支付
     * @author: 郭家屯
     * @since: 2020/7/11 11:31
     */
    public function get_promote_balance($data=[])
    {
        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1, 'name' => 'wxscan'])->find();
        if (empty($paytype)) {
            return ['err'=>1,'msg'=>'微信支付通道已关闭，请选择其他支付方式'];
        }
        $promote_id = $data['promote_id'];
        if (!$promote_id) {
            return ['err'=>1,'msg'=>'登录已失效，请重新登录'];
        }
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
            return ['err'=>1,'msg'=>'渠道账号不存在'];
        }
        if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
            return ['err'=>1,'msg'=>'金额错误'];
        }
        $model = new PromotedepositModel();
        $param['pay_order_number'] = $order_no;
        $param['promote_id'] = $promote_id;
        $param['to_id'] = $to_id;
        $param['pay_amount'] = $data['amount'];
        $param['pay_way'] = 4;
        $param['type'] = $data['type'];
        $param['title'] = "渠道平台币充值";
        $res = $model->add_promote_deposit($param);
        if($res == false){
            return ['err'=>1,'msg'=>'订单创建失败'];
        }
        return $param;
    }
    /**
     * @函数或方法说明
     * @代充
     * @param $data
     *
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/7/11 10:34
     */
    protected function weixinpay_bind($data=[])
    {
        $param = $this->bindpay($data);
        $param['pay_way'] = 4;
        $model = new PromotebindModel();
        $res = $model->add_promote_bind($param);
        if ($res == false) {
            return ['err'=>1,'msg'=>'订单创建失败'];
        }
        return $param;
    }

    //绑币充值参数
    private function bindpay($data=[])
    {
        $order_no = $data['order_no'] ?: "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
        $order_is_ex = Db::table('tab_promote_bind')->field('id')->where(['pay_order_number' => $order_no])->find();
        if (!empty($order_is_ex)) {
            return ['err'=>1,'msg'=>'订单已存在，请重新下单'];
        }
        $game_ids = array_column(session('bind_recharge_game'.$data['promote_id']),'id');
        $user_ids = array_column(session('bind_recharge_user'.$data['promote_id']),'user_id');
        if(in_array($data['game_id'],$game_ids)&&in_array($data['user_id'],$user_ids)){
            if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
                return ['err'=>1,'msg'=>'金额错误'];
            }
            $user = get_user_entity($data['user_id'],false,'promote_id');
            if(empty($user)){
                return ['err'=>1,'msg'=>'用户不存在'];
            }
            $game = get_game_entity($data['game_id'],'id');
            if(empty($game)){
                return ['err'=>1,'msg'=>'游戏不存在'];
            }
            $request['price'] = $data['amount'];
            $request['pay_amount'] = $data['amount'];
            $request['title'] = '绑定平台币代充';
            $request['body'] = '渠道绑定平台币代充';
            $request['pay_order_number'] = $order_no;
            $request['promote_id'] = $user['promote_id'];
            $request['to_id'] = $data['user_id'];
            $request['game_id'] = $data['game_id'];
            //调整查询折扣信息-20210429-byh
            $_discount = get_promote_dc_discount($user['promote_id'],$data['game_id'],$data['user_id']);
            $request['discount'] = $_discount['discount']==0?10:$_discount['discount'];
            $request['price'] = round($request['price']*$request['discount']/10,2);
            $request['cost'] = $data['amount'];
            $request['table'] = 'promote_bind';
            return $request;
        }else{
            return ['err'=>1,'msg'=>'订单失效'];
        }
    }
}
