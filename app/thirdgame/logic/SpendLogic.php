<?php

namespace app\thirdgame\logic;

use app\common\logic\InvitationLogic;
use app\common\logic\PayLogic;
use app\member\model\UserModel;
use app\thirdgame\model\GameModel;
use app\thirdgame\model\SpendModel;
use app\promote\logic\CustompayLogic;
use app\promote\model\PromoteDeductRecordModel;
use app\common\task\HandleUserStageTask;

class SpendLogic
{
    /**
     * 订单初始化
     *
     * @param array $param
     * @return array
     * @author: Juncl
     * @time: 2021/08/24 21:48
     */
    public function pay_init($param=[])
    {
        //查询当前用户
        $userInfo = get_user_entity($param['user_id'],false,'id,promote_id');
        if(empty($userInfo)){
            return ['status'=>0,'msg'=>'用户不存在'];
        }
        //获取平台ID
        $platformInfo = get_platform_entity_by_map(['platform_url'=>$param['platform']],'id');
        $param['platform_id'] = $platformInfo['id'];
        //查询当前平台游戏ID
        $gameInfo = get_third_game_entity($param['platform_id'],$param['game_id'],'id');
        $gameId = $gameInfo['id'];
        if(!$gameId){
            return ['status'=>0,'msg'=>'游戏不存在'];
        }
        $PayLogic = new PayLogic();
        $discount = $PayLogic->get_discount($gameId,$userInfo['promote_id'],$userInfo['id']);
        $data = [];
        $data['discount'] = $discount['discount'];
        $data['discount_type'] = $discount['discount_type'];
        return ['status'=>1,'data'=>$data];
    }

    /**
     * 创建订单
     *
     * @param array $param
     * @return array
     * @author: Juncl
     * @time: 2021/08/25 20:23
     */
    public function add_spend($param=[])
    {
        //查询当前用户
        $userInfo = get_user_entity($param['user_id'],false,'id,account,promote_id,promote_account');
        if(empty($userInfo)){
            return ['status'=>0,'msg'=>'用户不存在'];
        }
        //获取平台ID
        $platformInfo = get_platform_entity_by_map(['platform_url'=>$param['platform']],'id');
        $param['platform_id'] = $platformInfo['id'];
        //查询当前平台游戏ID
        $gameInfo = get_third_game_entity($param['platform_id'],$param['game_id'],'id,game_name,sdk_version');
        if(!$gameInfo){
            return ['status'=>0,'msg'=>'游戏不存在'];
        }
        $pay_order_number = 'SP_' . cmf_get_order_sn();
        $param['game_id'] = $gameInfo['id'];
        $param['game_name'] = $gameInfo['game_name'];
        $param['sdk_version'] = $gameInfo['sdk_version'];
        $param['pay_order_number'] = $pay_order_number;
        $SpendModel = new SpendModel();
        $result = $SpendModel->add_spend($param,$userInfo);
        if($result>0){
            return ['status'=>1,'data'=>$pay_order_number];
        }else{
            return ['status'=>0,'msg'=>'存入订单失败'];
        }
    }

    /**
     * 修改支付状态
     *
     * @param array $param
     * @return array
     * @author: Juncl
     * @time: 2021/08/25 20:19
     */
    public function set_pay_status($param=[])
    {
        $SpendModel = new SpendModel();
        $d = $SpendModel->where('pay_order_number',$param['pay_order_number'])->find();
        if (empty($d)) {
            return ['status'=>0,'msg'=>'订单不存在'];
        }
        $d = $d->toArray();
        if($d['pay_status'] == 1){
            return ['status'=>1,'msg'=>'订单已修改成功'];
        }
        $data_save['pay_status'] = 1;
        $data_save['order_number'] = $param['order_number']?:'';
        if ($d['promote_id']) {
            $promote = get_promote_entity($d['promote_id'],'pattern');
            if ($promote['pattern'] == 0) {
                $data_save['is_check'] = 1;
            }
        }
        Db::startTrans();
        try {
            $user = get_user_entity($d['user_id'], false, 'id,account,promote_id,promote_account,parent_name,parent_id,cumulative,invitation_id');
            $UserModel = new UserModel();
            if (user_is_paied($d['user_id']) == 0) {
                $UserModel->task_complete($d['user_id'], 'first_pay', $d['pay_amount']);//首冲
            }
            $UserModel->auto_task_complete($d['user_id'], 'pay_award', $d['pay_amount']);//充值送积分
            $UserModel->first_pay_every_day($d['user_id'], $d['pay_amount']);//每日首充积分奖励
            // 设置邀请人代金券
            if ($user['invitation_id'] > 0) {
                $money = $d['pay_amount'] + $user['cumulative'];
                $InvitationLogic = new InvitationLogic();
                $InvitationLogic->send_spend_coupon($user['invitation_id'], $user['id'], $money);
            }
            // 更新VIP等级和充值总计
            set_vip_level($d['user_id'], $d['pay_amount'], $user['cumulative']);
            // 修改订单状态
            $r = $SpendModel->where('pay_order_number', $param['pay_order_number'])->update($data_save);
            // 扣除推广员预付款(自定义支付商户)
            $lCustomPay = new CustompayLogic();
            if($d['pay_way'] == 3){
                $pay_type = 'zfb';
            }else{
                $pay_type = 'wxscan';
            }
            $customConfig = $lCustomPay->getPayConfig($d['pay_promote_id'], $d['game_id'], $pay_type, $d['pay_amount']);
            if (false !== $customConfig) {
                //写入扣款记录并扣款
                $mPromoteDeductRecord = new PromoteDeductRecordModel();
                $mPromoteDeductRecord->addRecord($d);
            }
            Db::commit();
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>'订单修改失败'];
        }
        if ($r) {
            //结算分成 (渠道订单  并且 支付走官方 产生结算订单)
            if ($d['promote_id'] && empty($d['pay_promote_id']) ) {
                if (isset($promote) && $promote['pattern'] == 0) {
                    set_promote_radio($d,$user);
                }
            }
            //返利
            $paylogic = new PayLogic();
            $paylogic->set_ratio($d,$user);
            // 用户阶段更改
            try{
                $systemSet = cmf_get_option('admin_set');
                if (empty($systemSet['task_mode'])) {
                    (new HandleUserStageTask()) -> changeUserStage1(['user_id' => $d['user_id']]);
                } else {
                    (new HandleUserStageTask()) -> saveOperation('', $d['user_id'], 0);
                }
            }catch(\Exception $e){}
            return ['status'=>1,'msg'=>'订单修改成功'];
        } else {
            return ['status'=>0,'msg'=>'订单修改失败'];
        }
    }

    /**
     * 修改游戏通知状态
     *
     * @param array $param
     * @author: Juncl
     * @time: 2021/08/25 20:23
     */
    public function set_game_status($param=[])
    {
        $SpendModel = new SpendModel();
        $d = $SpendModel->where('pay_order_number',$param['pay_order_number'])->find();
        if (empty($d)) {
            return ['status'=>0,'msg'=>'订单不存在'];
        }
        $d = $d->toArray();
        if($d['pay_game_status'] == 1){
            return ['status'=>1,'msg'=>'订单已修改成功'];
        }
        $saveData['pay_game_status'] = 1;
        $r = $SpendModel->where('pay_order_number',$param['pay_order_number'])->update($saveData);
        if($r){
            return ['status'=>1,'msg'=>'订单修改成功'];
        }else{
            return ['status'=>0,'msg'=>'订单修改失败'];
        }
    }
}