<?php
/**
 * app 用户平台币提现功能
 * by:byh 2021/06/24
 */

namespace app\app\controller;

use think\Db;
use think\Cache;
use think\weixinsdk\Weixin;

class CashOutController extends BaseController
{

    /**
     * 获取提现状态和手续费
     */
    public function get_cash_out_set()
    {
        //查询提现设置
        $data = cmf_get_option('ptb_cash_out_set');
//        if(empty($data)){
//            $this->set_message(1031, '配置信息错误');//无配置,按照空的配置走
//        }
        //增加登录用户是否绑定过微信的判断
        $user_id = $request = $this->request->param('user_id',0);
        if(empty($user_id)) $this->set_message(171,'请先登录');
        //查询用户
        $user = get_user_entity($user_id,false,'openid');
        //只有在微信提现开启,且用户有绑定微信的openid时,才返回微信状态为开启
        if($data['weixin_show'] == '1' && !empty($user['openid'])){
            $data['weixin_show'] = '1';
        }else{
            $data['weixin_show'] = '0';
        }
        //如果支付宝和微信都是关闭状态,则提现状态也是关闭
        if($data['alipay_show'] != '1' && $data['weixin_show'] != '1'){
            $data['tx_status'] = '0';
        }

        $new_data = [
            'limit_money'=>$data['limit_money']??'0',
            'payment_fee'=>$data['payment_fee']??'0',
            'tx_status'=>$data['tx_status']??'0',
            'alipay_show'=>$data['alipay_show']??'0',
            'weixin_show'=>$data['weixin_show']??'0',
        ];
        //增加APP内账户充值的状态-按钮-平台币-绑币-----start
        $ptb_recharge_status = get_ptb_bind_recharge_status('ptb_pay');//平台币配置状态
        $bind__recharge_status = get_ptb_bind_recharge_status('bind_pay');//绑币配置状态
        //如平台币和绑币都为关闭状态,则充值入口为关闭
        if($ptb_recharge_status == '0' && $bind__recharge_status == '0'){
            $new_data['app_recharge_switch'] = '0';
        }else{
            $new_data['app_recharge_switch'] = '1';
        }
        //增加APP内账户充值的状态-按钮-平台币-绑币-----end


        $this->set_message(200,'获取成功',$new_data);
    }

    /**
     * 提交提现申请
     */
    public function apply_cash_out()
    {
        $data = $request = $this->request->param();
        //暂时关闭微信提现
//        if($data['type']==4){
//            $this->set_message(1031, '抱歉!微信提现暂时不可用,请选择支付宝');
//        }

        //判断信息
        if(empty($data['user_id']) || empty($data['apply_amount']) || !is_numeric($data['apply_amount'])){
            $this->set_message(1071,'信息或金额错误');
        }
        //获取提现配置及处理判断
        $config = cmf_get_option('ptb_cash_out_set');
//        if($config['tx_status'] != 1) $this->set_message(1038,'提现功能已关闭');//此按钮删除
        if($config['limit_money']>=$data['apply_amount']){
            $this->set_message(1071,'提现金额必须大于最低手续费'.$config['limit_money'].'元');
//            $this->set_message(1071,'账户余额不足');
        }

        if($config['limit_cash'] && $data['apply_amount'] > $config['limit_cash']){
            $this->set_message(1031, '单笔最高提现金额为'.$config['limit_cash'].'元');
        }
        if($config['limit_count']){
            $map1['create_time'] = total(1,1);
            $map1['user_id'] = $data['user_id'];
            $count = Db::table('tab_user_cash_out')->where($map1)->count();
            if($count >= $config['limit_count']){
                $this->set_message(1031, '今日剩余提现次数为0');
            }
        }
        //查询账户平台币
        $user = get_user_entity($data['user_id'],false,'id,account,balance,openid,pay_password');

        //判断提现类型-微信/支付宝
        switch ($data['type']){
            case '4'://微信
                if(empty($user['openid'])){
                    $this->set_message(1071,'账号未绑定微信,无法使用微信体现!');
                }
                break;
            case '3'://支付宝
                if(empty($data['ali_account'])){
                    $this->set_message(1071,'支付宝账号错误');
                }
                if(empty($data['account_name'])){
                    $this->set_message(1071,'账号姓名必须填写');
                }
                break;
            default:
                $this->set_message(1071,'提现类型错误');
        }
        if($data['apply_amount']>$user['balance'])$this->set_message(1071,'提现金额不得超过账户余额');
//        //查询判断已提交申请提现但未审核处理的金额
//        $where = [];
//        $where['user_id'] = $data['user_id'];
//        $where['status'] = 0;
//        $total_apply_amount = Db::table('tab_user_cash_out')->where($where)->sum('apply_amount');
//        if($data['apply_amount']>($user['balance']-$total_apply_amount)){
//            $this->set_message(1071,'扣除未审核的提现金额后,剩余余额不足');
//        }
        //扣除手续费后实际到账金额
        if($config['payment_fee']>0){
            $payment_money = (($data['apply_amount']*$config['payment_fee'])/100);
            if($payment_money<$config['limit_money']){
                $payment_money = $config['limit_money'];
            }
            $real_amount = $data['apply_amount']-$payment_money;
            $real_amount = sprintf("%.2f", $real_amount);
        }else{
            $real_amount = sprintf("%.2f", $data['apply_amount']-$config['limit_money']);
        }

        //处理提交数据

        $save = [
            'user_id'=>$data['user_id'],
            'user_account'=>get_user_name($data['user_id']),
            'promote_id'=>$data['promote_id']??0,
            'apply_amount'=>$data['apply_amount'],
            'commission'=>$config['payment_fee']??0,
            'real_amount'=>$real_amount,
            'fee_amount'=>$data['apply_amount']-$real_amount,
            'order_no'=>$this->get_cash_out_order_no($data['user_id']),
            'create_time'=>time(),
            'create_ip'=>get_client_ip(),
            'type'=>$data['type'],
        ];
        if($data['type'] == 4){//微信数据
            $save['wx_openid'] = $user['openid'];
        }elseif ($data['type'] == 3){
            $save['ali_account'] = $data['ali_account'];
            $save['account_name'] = $data['account_name'];
        }
        Db::startTrans();
        //写入提现记录
        $res = Db::table('tab_user_cash_out')->insertGetId($save);
        //扣除平台币
        $res2 = Db ::table('tab_user')->where('id', $data['user_id']) -> setDec('balance', $data['apply_amount']);
        if($res>0 && $res2){
            Db::commit();
            if($config['pay_type'] != 1){//手动-自动关闭
//                $this->set_message(200,'提现申请成功，请等待打款(手续费:'.$config['payment_fee'].'%)');
                $this->set_message(200,'申请提现成功，请等待打款');
            }else{//自动打款开启
                if($data['type'] == 3){
                    $result = $this->alipay_cash_out($save['order_no'],$save);
                }else{
                    $result = $this->weixin_cash_out($save,$user['openid']);
                }
                return json($result);
            }
        }
        Db::rollback();
        $this->set_message(1083,'提现申请失败');

    }

    /**
     * 获取提现订单编号
     */
    public function get_cash_out_order_no($user_id=0)
    {
        $order_no = 'TX' . date('Ymd') . date('His').sprintf('%08s',$user_id) . sp_random_string(4);
        //查询此订单号是否在数据表中出现
        $res = Db::table('tab_user_cash_out')->where('order_no',$order_no)->count();
        if($res>0){
            $this->get_cash_out_order_no($user_id);
        }
        return $order_no;

    }



    /**
     * 提现记录
     */
    public function cash_out_record()
    {
        $request = $request = $this->request->param();
        //参数判断
        if(empty($request['user_id']) ){
            $this->set_message(1071,'参数错误');
        }
        $map = [];
        $map['user_id'] = $request['user_id'];
        $map['status'] = 1;//只返回打款成功的数据

        $limit = $request['limit']??10;
        $page = $request['page']??1;
        //查询数据
        $list = Db::table('tab_user_cash_out')
        ->field('apply_amount,create_time,type,status')
        ->where($map)
        ->page($page,$limit)
        ->order('id desc')
        ->select();
        //处理数据-
        $new_list = [];
        foreach ($list as $k => $v){
            $new_list[$k]['apply_amount'] = $v['apply_amount'];
            $new_list[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            if($v['type']==4){
                $new_list[$k]['type'] = '微信';
            }elseif($v['type']==3){
                $new_list[$k]['type'] = '支付宝';
            }
            if($v['status']== 1 ){
                $new_list[$k]['status'] = '提现成功';
            }elseif($v['status']== -1 ){
                $new_list[$k]['status'] = '提现失败';
            }elseif($v['status']== 9 ){
                $new_list[$k]['status'] = '已驳回';
            }else{
                $new_list[$k]['status'] = '审核中';
            }
        }
        //计算总提现金额-计算提现成功的,
        $map['status'] = 1;//提现成功的
        $total_cash_out = Db::table('tab_user_cash_out')->where($map)->sum('apply_amount');
        $data = [
            'total_success'=>$total_cash_out,
            'list'=>$new_list,
        ];
        $this->set_message(200,'获取成功',$data);

    }


    /**
     * @函数或方法说明
     * @支付宝提现
     * @author: 郭家屯
     * @since: 2020/9/27 15:21
     */
    protected function alipay_cash_out($orderNo,$order)
    {
        //支付宝提现配置
        $config = get_pay_type_set('zfb_tx');
        $pay = new \think\Pay('alipay', $config['config']);
        $vo   = new \think\pay\PayVo();
        $vo->setOrderNo($orderNo)
            ->setTable('cash_out')
            ->setPayMethod("transfer_cash_out")
            ->setDetailData('平台币提现');
        $res =  $pay->buildRequestForm($vo);
        if($res==10000){
            return ['code'=>1,'msg'=>'打款成功'];
        }else{//打款失败-退还扣除的平台币
            $result = Db ::table('tab_user')->where('id', $order['user_id']) -> setInc('balance', $order['apply_amount']);
            if(!$result){
                file_put_contents("tx_cash_order.txt","打款失败,提现金额退还账户失败!记录时间:".date('Y-m-d H:i:s',time()).".订单信息:".json_encode($order).PHP_EOL,FILE_APPEND | LOCK_EX);
                return ['code'=>0,'msg'=>'打款失败,提现金额退还账户失败!已记录文档'];
            }
            return ['code'=>0,'msg'=>$res];
        }
    }

    /**
     * @函数或方法说明
     * @微信自动打款
     * @author: 郭家屯
     * @since: 2020/9/27 16:53
     */
    protected function weixin_cash_out($data=[],$openid='')
    {
        $weixin = new Weixin();
        $result = $weixin->weixin_transfers('平台币提现',$data['order_no'],$data['real_amount'],$openid);
        //修改状态
        if($result['code'] == 1){
            $save['status'] = 1;
        }else{
            $save['status'] = -1;
        }
        $save['remark'] = $result['msg'];
        if($result['code'] == 1){
        }else{//打款失败-退还扣除的平台币
            $result2 = Db ::table('tab_user')->where('id', $data['user_id']) -> setInc('balance', $data['apply_amount']);
            if(!$result2){
                file_put_contents("tx_cash_order.txt","打款失败,提现金额退还账户失败!记录时间:".date('Y-m-d H:i:s',time()).".订单信息:".json_encode($data).PHP_EOL,FILE_APPEND | LOCK_EX);
                return ['code'=>0,'msg'=>'打款失败,提现金额退还账户失败!已记录文档'];
            }
        }
        Db::table('tab_user_cash_out')->where('order_no',$data['order_no'])->update($save);
        return $result;
    }






}
