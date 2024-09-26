<?php

namespace app\callback\controller;

use app\common\logic\InvitationLogic;
use app\common\logic\PayLogic;
use app\issue\model\BalanceModel;
use app\issue\model\OpenUserModel;
use app\member\model\UserMemberModel;
use app\member\model\UserModel;
use app\member\model\UserTransactionOrderModel;
use app\promote\logic\CustompayLogic;
use app\promote\model\PromoteDeductRecordModel;
use app\site\model\AppSupersignOrderModel;
use cmf\controller\HomeBaseController;
use app\recharge\model\SpendModel;
use app\recharge\model\SpendBalanceModel;
use app\recharge\model\SpendBindModel;
use app\promote\model\PromotebindModel;
use app\promote\model\PromoteModel;
use app\promote\model\PromotedepositModel;
use app\member\model\UserPlayModel;
use app\api\GameApi;
use app\common\task\HandleUserStageTask;
use think\Db;
use think\Log;

/**
 * 支付回调控制器
 * @author 郭家屯
 */
class BaseController extends HomeBaseController
{
    /**
     *充值到游戏成功后修改充值状态和设置游戏币
     */
    public function set_spend($data = [])
    {
        $spend = new SpendModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $spend->where($map)->find();
        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        $d = $d->toArray();
        if ($data['real_amount'] < $d['pay_amount']) {
            $warn = Db::table('tab_warning')->field('id')->where('pay_order_number',$d['pay_order_number'])->find();
            if($warn){
                return false;
            }
            //异常预警提醒
            $warning = [
                    'type'=>1,
                    'user_id'=>$d['user_id'],
                    'user_account'=>$d['user_account'],
                    'pay_order_number'=>$d['pay_order_number'],
                    'target'=>3,
                    'record_id'=>$d['id'],
                    'pay_amount'=>$d['pay_amount'],
                    'unusual_money' => $data['real_amount'],
                    'create_time'=>time()
            ];
            Db::table('tab_warning')->insert($warning);
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }
        //判断代金券是否已使用
        if($d['coupon_record_id']){
            $coupon = Db::table('tab_coupon_record')->field('status')->where('id',$d['coupon_record_id'])->find();
            if($coupon['status'] == 1){
                $this->record_logs('订单' . $data['out_trade_no'] . '代金券已使用，支付失败');
                return false;
            }
        }
        if ($d['pay_status'] == 0) {
            $data_save['pay_status'] = 1;
            $data_save['order_number'] = $data['trade_no'];
            if ($d['promote_id']) {
                $promote = get_promote_entity($d['promote_id'],'pattern');
                if ($promote['pattern'] == 0) {
                    $data_save['is_check'] = 1;
                }
            }
            //用户信息
            $user = get_user_entity($d['user_id'],false,'id,account,promote_id,promote_account,parent_name,parent_id,cumulative,invitation_id');
            Db::startTrans();
            try {
                //异常预警提醒
                if($d['cost'] >= 2000){
                    $warning = [
                            'type'=>3,
                            'user_id'=>$d['user_id'],
                            'user_account'=>$d['user_account'],
                            'pay_order_number'=>$d['pay_order_number'],
                            'target'=>3,
                            'record_id'=>$d['id'],
                            'pay_amount'=>$d['pay_amount'],
                            'create_time'=>time()
                    ];
                    Db::table('tab_warning')->insert($warning);
                }
                $modelUserModel = new UserModel();
                if(user_is_paied($d['user_id'])==0){
                    $modelUserModel->task_complete($d['user_id'],'first_pay',$d['pay_amount']);//首冲
                }
                $modelUserModel->auto_task_complete($d['user_id'],'pay_award',$d['pay_amount']);//充值送积分
                $modelUserModel->first_pay_every_day($d['user_id'],$d['pay_amount']);//每日首充积分奖励
                //发放邀请人充值代金券
                if($user['invitation_id'] >0){
                    $money = $d['pay_amount']+$user['cumulative'];
                    $logic = new InvitationLogic();
                    $logic->send_spend_coupon($user['invitation_id'],$user['id'],$money);
                }
                //更新VIP等级和充值总计
                set_vip_level($d['user_id'], $d['pay_amount'],$user['cumulative']);
                if($d['user_id']!=$d['small_id']&&$d['small_id']!=''){//更新小号累计充值
                    $small_entity = get_user_entity($d['small_id'],false,'id,account,nickname,promote_id,promote_account,balance,cumulative,parent_id,parent_name');
                    set_vip_level($d["small_id"], $d['pay_amount'],$small_entity['cumulative']);
                }
                $map_s['pay_order_number'] = $data['out_trade_no'];
                $r = $spend->where($map_s)->update($data_save);
                //代金券使用
                if($d['coupon_record_id']){
                    $coupon_data['status'] = 1;
                    $coupon_data['cost'] = $d['cost'];
                    $coupon_data['update_time'] = time();
                    $coupon_data['pay_amount'] = $d['pay_amount'];
                    Db::table('tab_coupon_record')->where('id',$d['coupon_record_id'])->update($coupon_data);
                }
                //扣除推广员预付款(自定义支付商户)
                $lCustomPay = new CustompayLogic();
                $customConfig = $lCustomPay -> getPayConfig($d['pay_promote_id'], $d['game_id'], $data['pay_type'], $d['pay_amount']);
                if (false !== $customConfig) {
                    //写入扣款记录并扣款
                    $mPromoteDeductRecord = new PromoteDeductRecordModel();
                    $mPromoteDeductRecord -> addRecord($d);
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->record_logs('订单' . $data['out_trade_no'] . '修改支付成功状态失败');
                return false;
            }
            if ($r) {
                //结算分成 (渠道订单  并且 支付走官方 产生结算订单)
                if ($d['promote_id'] && empty($d['pay_promote_id']) ) {
                    if (isset($promote) && $promote['pattern'] == 0) {
                        set_promote_radio($d,$user);
                    }
                }
                $game = new GameApi();
                $game->game_pay_notify($data);
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

                }catch(\Exception $e){

                }
                return true;
            } else {
                $this->record_logs("修改数据失败");
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * @函数或方法说明
     * @分发支付回调处理
     * @param array $data
     * @author: 郭家屯
     * @since: 2020/10/26 17:18
     */
    protected function set_spend_ff($data = [])
    {
        $spend = new SpendModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $spend->where($map)->find();
        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        $d = $d->toArray();
        if ($data['real_amount'] < $d['pay_amount']) {
            $warn = Db::table('tab_warning')->field('id')->where('pay_order_number',$d['pay_order_number'])->find();
            if($warn){
                return false;
            }
            //异常预警提醒
            $warning = [
                    'type'=>1,
                    'user_id'=>$d['user_id'],
                    'user_account'=>$d['user_account'],
                    'pay_order_number'=>$d['pay_order_number'],
                    'target'=>3,
                    'record_id'=>$d['id'],
                    'pay_amount'=>$d['pay_amount'],
                    'unusual_money' => $data['real_amount'],
                    'create_time'=>time()
            ];
            Db::table('tab_warning')->insert($warning);
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }

        if ($d['pay_status'] == 0) {
            $data_save['pay_status'] = 1;
            $data_save['order_number'] = $data['trade_no'];
            $data_save['pay_way'] = $data['pay_way'];
            $data_save['pay_game_status'] = $data['pay_game_status'];
            if ($d['promote_id']) {
                $promote = get_promote_entity($d['promote_id'],'pattern');
                if ($promote['pattern'] == 0) {
                    $data_save['is_check'] = 1;
                }
            }
            //用户信息
            $user = get_user_entity($d['user_id'],false,'id,account,promote_id,promote_account,parent_name,parent_id,cumulative,invitation_id');
            Db::startTrans();
            try {
                //异常预警提醒
                if($d['cost'] >= 2000){
                    $warning = [
                            'type'=>3,
                            'user_id'=>$d['user_id'],
                            'user_account'=>$d['user_account'],
                            'pay_order_number'=>$d['pay_order_number'],
                            'target'=>3,
                            'record_id'=>$d['id'],
                            'pay_amount'=>$d['pay_amount'],
                            'create_time'=>time()
                    ];
                    Db::table('tab_warning')->insert($warning);
                }
                $modelUserModel = new UserModel();
                if(user_is_paied($d['user_id'])==0){
                    $modelUserModel->task_complete($d['user_id'],'first_pay',$d['pay_amount']);//首冲
                }
                $modelUserModel->auto_task_complete($d['user_id'],'pay_award',$d['pay_amount']);//充值送积分
                $modelUserModel->first_pay_every_day($d['user_id'],$d['pay_amount']);//每日首充积分奖励
                //发放邀请人充值代金券
                if($user['invitation_id'] >0){
                    $money = $d['pay_amount']+$user['cumulative'];
                    $logic = new InvitationLogic();
                    $logic->send_spend_coupon($user['invitation_id'],$user['id'],$money);
                }
                //更新VIP等级和充值总计
                set_vip_level($d['user_id'], $d['pay_amount'],$user['cumulative']);
                if($d['user_id']!=$d['small_id']&&$d['small_id']!=''){//更新小号累计充值
                    $small_entity = get_user_entity($d['small_id'],false,'id,account,nickname,promote_id,promote_account,balance,cumulative,parent_id,parent_name');
                    set_vip_level($d["small_id"], $d['pay_amount'],$small_entity['cumulative']);
                }
                $map_s['pay_order_number'] = $data['out_trade_no'];
                $r = $spend->where($map_s)->update($data_save);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->record_logs('订单' . $data['out_trade_no'] . '修改支付成功状态失败');
                return false;
            }
            if ($r) {
                //结算分成
                if ($d['promote_id']) {
                    if (isset($promote) && $promote['pattern'] == 0) {
                        set_promote_radio($d,$user);
                    }
                }
                // 用户阶段更改
                try{

                    $systemSet = cmf_get_option('admin_set');
                    if (empty($systemSet['task_mode'])) {
                        (new HandleUserStageTask()) -> changeUserStage1(['user_id' => $d['user_id']]);
                    } else {
                        (new HandleUserStageTask()) -> saveOperation('', $d['user_id'], 0);
                    }

                }catch(\Exception $e){

                }
                //返利
                $paylogic = new PayLogic();
                $paylogic->set_ratio($d,$user);
                return true;
            } else {
                $this->record_logs("修改数据失败");
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * @函数或方法说明
     * @分发支付成功回调
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/10/22 19:23
     */
    protected function set_sue_spend($data=[])
    {
        $spend = new \app\issue\model\SpendModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $spend->where($map)->find();
        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        $gamemodel = new \app\issue\model\IssueGameApplyModel();
        $gamemodel->field('id,ratio');
        $gamemodel->where('game_id',$d->game_id);
        $gamemodel->where('platform_id',$d->platform_id);
        $gamemodel->where('open_user_id',$d->open_user_id);
        $gameapply = $gamemodel->find();
        if ($data['real_amount'] < $d['pay_amount']) {
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }
        if ($d->pay_status == 0) {
            //更新支付状态
            $d->ratio = $gameapply->ratio;
            $d->ratio_money = round($d->pay_amount*$gameapply->ratio/100,2);
            $d->pay_status = 1;
            $d->pay_way = $data['pay_way'];
            $d->order_number = $data['trade_no'];
            $d->save();
            //通知游戏
            $game = new GameApi();
            $result = $game->game_ff_pay_notify($data);
            if($result){
                $d->pay_game_status = 1;
            }
            //通知分发
            if($d->sdk_version == 3){
                $logic = new \app\issueh5\logic\PayLogic();
            }elseif($d->sdk_version == 4){
                $logic = new \app\issueyy\logic\PayLogic();
            }else{
                $logic = new \app\issuesy\logic\PayLogic();
            }
            $notice_status = $logic->pay_ff_notice($d->toArray());
            if($notice_status){
                $d->pay_ff_status = 1;
            }
            $d->save();
        }else{
            return true;
        }

    }

    /**
     *充值平台币成功后的设置
     */
    public function set_deposit($data)
    {
        $deposit = new SpendBalanceModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $deposit->field('id,pay_amount,pay_status,user_id,pay_order_number')->where($map)->find();
        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        $d = $d->toArray();
        if ($data['real_amount'] < $d['pay_amount']) {
            $warn = Db::table('tab_warning')->field('id')->where('pay_order_number',$d['pay_order_number'])->find();
            if($warn){
                return false;
            }
            //异常预警提醒
            $warning = [
                    'type'=>1,
                    'user_id'=>$d['user_id'],
                    'user_account'=>get_user_entity($d['user_id'],false,'account')['account'],
                    'pay_order_number'=>$d['pay_order_number'],
                    'target'=>2,
                    'record_id'=>$d['id'],
                    'pay_amount'=>$d['pay_amount'],
                    'unusual_money' => $data['real_amount'],
                    'create_time'=>time()
            ];
            Db::table('tab_warning')->insert($warning);
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }
        if ($d['pay_status'] == 0) {
            //异常预警提醒
            if($d['pay_amount'] >= 2000){
                $warning = [
                        'type'=>3,
                        'user_id'=>$d['user_id'],
                        'user_account'=>get_user_entity($d['user_id'],false,'account')['account'],
                        'pay_order_number'=>$d['pay_order_number'],
                        'target'=>2,
                        'record_id'=>$d['id'],
                        'pay_amount'=>$d['pay_amount'],
                        'create_time'=>time()
                ];
                Db::table('tab_warning')->insert($warning);
            }
            $data_save['pay_status'] = 1;
            $data_save['order_number'] = $data['trade_no'];
            $map_s['pay_order_number'] = $data['out_trade_no'];
            $r = $deposit->where($map_s)->update($data_save);
            if ($r) {
                $user = new UserModel();
                $result = $user->where("id", $d['user_id'])->setInc("balance", $d['pay_amount']);
                if (empty($result)) {
                    $this->record_logs("充值平台币失败");
                    return false;
                }
            } else {
                $this->record_logs("修改数据失败");
                return false;
            }
            return true;
        } else {
            return true;
        }
    }

    /**
     *充值绑币成功后的设置
     */
    protected function set_bind($data=[])
    {
        $bind = new SpendBindModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $bind->field('id,pay_amount,pay_status,user_id,user_account,game_id,cost,pay_order_number')->where($map)->find();
        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        $d = $d->toArray();
        if ($data['real_amount'] < $d['pay_amount']) {
            $warn = Db::table('tab_warning')->field('id')->where('pay_order_number',$d['pay_order_number'])->find();
            if($warn){
                return false;
            }
            //异常预警提醒
            $warning = [
                    'type'=>1,
                    'user_id'=>$d['user_id'],
                    'user_account'=>$d['user_account'],
                    'pay_order_number'=>$d['pay_order_number'],
                    'target'=>1,
                    'record_id'=>$d['id'],
                    'pay_amount'=>$d['pay_amount'],
                    'unusual_money' => $data['real_amount'],
                    'create_time'=>time()
            ];
            Db::table('tab_warning')->insert($warning);
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }
        if ($d['pay_status'] == 0) {
            //异常预警提醒
            if($d['cost'] >= 2000){
                $warning = [
                        'type'=>3,
                        'user_id'=>$d['user_id'],
                        'user_account'=>$d['user_account'],
                        'pay_order_number'=>$d['pay_order_number'],
                        'target'=>1,
                        'record_id'=>$d['id'],
                        'pay_amount'=>$d['cost'],
                        'create_time'=>time()
                ];
                Db::table('tab_warning')->insert($warning);
            }
            $data_save['pay_status'] = 1;
            $data_save['order_number'] = $data['trade_no'];
            $map_s['pay_order_number'] = $data['out_trade_no'];
            $r = $bind->where($map_s)->update($data_save);
            if ($r) {
                $userplay = new UserPlayModel();
                $result = $userplay->where("user_id", $d['user_id'])->where('game_id',$d['game_id'])->setInc("bind_balance", $d['cost']);
                if (empty($result)) {
                    $this->record_logs("充值绑币失败");
                    return false;
                }
            } else {
                $this->record_logs("修改数据失败");
                return false;
            }
            return true;
        } else {
            return true;
        }
    }
    /**
     *推广员代充绑币成功后的设置
     */
    protected function set_promote_bind($data=[])
    {
        $bind = new PromotebindModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $bind->field('id,pay_amount,pay_status,user_id,user_account,game_id,cost,pay_order_number')->where($map)->find();
        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        $d = $d->toArray();
        if ($data['real_amount'] < $d['pay_amount']) {
            $warn = Db::table('tab_warning')->field('id')->where('pay_order_number',$d['pay_order_number'])->find();
            if($warn){
                return false;
            }
            //异常预警提醒
            $warning = [
                    'type'=>1,
                    'user_id'=>$d['user_id'],
                    'user_account'=>$d['user_account'],
                    'pay_order_number'=>$d['pay_order_number'],
                    'target'=>4,
                    'record_id'=>$d['id'],
                    'pay_amount'=>$d['pay_amount'],
                    'unusual_money' => $data['real_amount'],
                    'create_time'=>time()
            ];
            Db::table('tab_warning')->insert($warning);
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }
        if ($d['pay_status'] == 0) {
            //异常预警提醒
            if($d['cost'] >= 2000){
                $warning = [
                        'type'=>3,
                        'user_id'=>$d['user_id'],
                        'user_account'=>$d['user_account'],
                        'pay_order_number'=>$d['pay_order_number'],
                        'target'=>4,
                        'record_id'=>$d['id'],
                        'pay_amount'=>$d['cost'],
                        'create_time'=>time()
                ];
                Db::table('tab_warning')->insert($warning);
            }
            $data_save['pay_status'] = 1;
            $data_save['order_number'] = $data['trade_no'];
            $map_s['pay_order_number'] = $data['out_trade_no'];
            $r = $bind->where($map_s)->update($data_save);
            if ($r) {
                $userplay = new UserPlayModel();
                $result = $userplay->where("user_id", $d['user_id'])->where('game_id',$d['game_id'])->setInc("bind_balance", $d['cost']);
                if (empty($result)) {
                    $this->record_logs("充值绑币失败");
                    return false;
                }
            } else {
                $this->record_logs("修改数据失败");
                return false;
            }
            return true;
        } else {
            return true;
        }
    }

    /**
     *渠道充值平台币成功后的设置
     */
    protected function set_promote_deposit($data)
    {
        $deposit = new PromotedepositModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $deposit->field('pay_amount,pay_status,to_id')->where($map)->find();
        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        $d = $d->toArray();
        if ($data['real_amount'] < $d['pay_amount']) {
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }
        if ($d['pay_status'] == 0) {
            $data_save['pay_status'] = 1;
            $data_save['order_number'] = $data['trade_no'];
            $map_s['pay_order_number'] = $data['out_trade_no'];
            $r = $deposit->where($map_s)->update($data_save);
            if ($r) {
                $promotemodel = new PromoteModel();
                $result = $promotemodel->where("id", $d['to_id'])->setInc("balance_coin", $d['pay_amount']);
                if (empty($result)) {
                    $this->record_logs("充值平台币失败");
                    return false;
                }
            } else {
                $this->record_logs("修改数据失败");
                return false;
            }
            return true;
        } else {
            return true;
        }
    }
    // 渠道预付款充值
    protected function set_promote_prepayment($data)
    {
        // $deposit = new PromotedepositModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        // $d = $deposit->field('pay_amount,pay_status,to_id')->where($map)->find();
        $d = Db::table('tab_promote_prepayment_recharge')->where($map)->field('pay_amount,pay_status,promote_id,promote_account')->find();
        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        if ($data['real_amount'] < $d['pay_amount']) {
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }
        if ($d['pay_status'] == 0) {
            $data_save['pay_status'] = 1;
            $data_save['order_number'] = $data['trade_no'];
            $map_s['pay_order_number'] = $data['out_trade_no'];
            $r = Db::table('tab_promote_prepayment_recharge')->where($map_s)->update($data_save);

            if ($r) {
                $promotemodel = new PromoteModel();
                $result = $promotemodel->where("id", $d['promote_id'])->setInc("prepayment", $d['pay_amount']);
                if (empty($result)) {
                    $this->record_logs("充值预付款失败");
                    return false;
                }
            } else {
                $this->record_logs("修改数据失败");
                return false;
            }
            // 充值成功
            return true;
        } else {
            return true;
        }
    }

    /**
     * @函数或方法说明
     * @小号交易回调
     * @param $data
     *
     * @author: 郭家屯
     * @since: 2020/3/11 11:09
     */
    protected function set_transaction($data=[])
    {
        $transactionmodel = new UserTransactionOrderModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $transactionmodel->field('id,game_name,game_id,sell_account,pay_amount,balance_money,user_id,transaction_id,sell_id,fee,small_id')->where($map)->find();
        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        $d = $d->toArray();
        if ($data['real_amount'] < (string)($d['pay_amount']-$d['balance_money'])) {
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }
        if ($d['pay_status'] == 0) {
            Db ::startTrans();
            try {
                //扣除平台币
                if ($d['balance_money'] > 0) {
                    $user = get_user_entity($d['user_id'], false, 'id,balance');
                    if ($user['balance'] < $d['balance_money']) {
                        $this -> record_logs("平台币金额不足，支付失败");
                        return false;
                    }
                    $result = Db ::table('tab_user')->where('id', $d['user_id']) -> setDec('balance', $d['balance_money']);
                }
                $result1 = Db::table('tab_user_transaction_order')->where('id',$d['id'])->setField('pay_status',1);
                //修改交易状态
                $result2 = Db ::table('tab_user_transaction') -> where('id', $d['transaction_id']) -> setField('status', 3);
                //增加金额
                $result3 = Db ::table('tab_user') -> where('id', $d['sell_id']) -> setInc('balance', $d['pay_amount'] - $d['fee']);
                //修改小号归属
                $result4 = Db ::table('tab_user') -> where('id', $d['small_id']) -> setField('puid', $d['user_id']);
                $result5 = Db ::table('tab_user_play_info')->where('user_id',$d['small_id'])->setField('puid', $d['user_id']);
                //增加收益记录
                $save['user_id'] = $d['sell_id'];
                $save['user_account'] = $d['sell_account'];
                $save['game_id'] = $d['game_id'];
                $save['game_name'] = $d['game_name'];
                $save['amount'] = $d['pay_amount'] - $d['fee'];
                $save['small_id'] = $d['small_id'];
                $save['small_account'] = get_user_entity($d['small_id'], false, 'nickname')['nickname'];
                $save['create_time'] = time();
                $result6 = Db ::table('tab_user_transaction_profit') -> insert($save);
                //更改交易状态
                Db ::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db ::rollback();
                return false;
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * @充值联运币回调
     *
     * @author: zsl
     * @since: 2020/7/27 9:57
     */
    protected function set_lyb_balance($data = [])
    {

        $balance = new BalanceModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $balance -> field('pay_amount,pay_status,user_id') -> where($map) -> find();
        if (empty($d)) {
            $this -> record_logs("数据异常");
            return false;
        }
        $d = $d -> toArray();
        if ($data['real_amount'] < $d['pay_amount']) {
            $this -> record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }
        if ($d['pay_status'] == 0) {
            $data_save['pay_status'] = 1;
            $data_save['order_number'] = $data['trade_no'];
            $map_s['pay_order_number'] = $data['out_trade_no'];
            $r = $balance -> where($map_s) -> update($data_save);
            if ($r) {
                $openUser = new OpenUserModel();
                $result = $openUser -> where("id", $d['user_id']) -> setInc("balance", $d['pay_amount']);
                if (empty($result)) {
                    $this -> record_logs("联运币充值成功");
                    return false;
                }
            } else {
                $this -> record_logs("联运币修改数据失败");
                return false;
            }
            return true;
        } else {
            return true;
        }


    }

    /**
     * @函数或方法说明
     * @尊享卡会员
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/8/17 14:57
     */
    public function set_member($data = [])
    {
        $model = new UserMemberModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $model -> field('pay_amount,pay_status,user_id,days,free_days') -> where($map) -> find();
        if (empty($d)) {
            $this -> record_logs("数据异常");
            return false;
        }
        $d = $d -> toArray();
        if ($data['real_amount'] < $d['pay_amount']) {
            $this -> record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }
        if ($d['pay_status'] == 0) {
            Db ::startTrans();
            try {
                $user = get_user_entity($d['user_id'],false,'id,account,member_days,end_time');
                $data_save['pay_status'] = 1;
                $data_save['order_number'] = $data['trade_no'];
                if($user['end_time'] >= time()){
                    $days = $d['days'] + $d['free_days'];
                    $data_save['end_time'] = strtotime("+{$days} days",$user['end_time']);
                    $data_save['start_time'] = $user['end_time'] + 1;
                }else{
                    $days = $d['days'] + $d['free_days'] - 1;
                    $data_save['end_time'] = strtotime("+{$days} days",strtotime(date('Y-m-d'))+86399);
                    $data_save['start_time'] = strtotime(date('Y-m-d'));
                    //发放代金券
                    $mcard_set = cmf_get_option('mcard_set');
                    $coupon_id = $mcard_set['coupon_id'];
                    $coupon = get_coupon_info($coupon_id);
                    $coupon_data = $this->get_coupon_data($coupon,$user);
                    Db::table('tab_coupon_record')->insert($coupon_data);
                }
                $map_s['pay_order_number'] = $data['out_trade_no'];
                $model -> where($map_s) -> update($data_save);//订单信息修改
                //会员信息修改
                $save['member_days'] = $user['member_days'] + $d['days'] + $d['free_days'];
                $save['end_time'] = $data_save['end_time'];
                Db::table('tab_user')->where('id',$d['user_id'])->update($save);
                Db ::commit();
            } catch (\Exception $e) {
            // 回滚事务
            Db ::rollback();
            return false;
        }
            return true;
        } else {
            return true;
        }
    }


    /**
     * @设置超级签支付订单
     *
     * @author: zsl
     * @since: 2021/7/12 21:35
     */
    protected function set_supersign_order($data = [])
    {
        $mAppSuperSignOrder = new AppSupersignOrderModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = $mAppSuperSignOrder->field('pay_amount,pay_status')->where($map)->find();
        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        $d = $d->toArray();
        if ($data['real_amount'] < $d['pay_amount']) {
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }

        if ($d['pay_status'] == 0) {

            $data_save['pay_status'] = 1;
            $data_save['order_number'] = $data['trade_no'];
            $data_save['pay_time'] = time();
            $map_s['pay_order_number'] = $data['out_trade_no'];
            $r = $mAppSuperSignOrder->where($map_s)->update($data_save);
            if (!$r) {
                $this->record_logs("修改数据失败");
                return false;
            }
            return true;
        } else {
            return true;
        }
    }

    protected function set_pay_game_order($data = [])
    {
        // $mAppSuperSignOrder = new AppSupersignOrderModel();
        $map['pay_order_number'] = $data['out_trade_no'];
        $d = Db::table('tab_game_ios_pay_to_download_order')->field('game_id,user_agent,user_agent_md5,pay_price,pay_status')->where($map)->find();

        if (empty($d)) {
            $this->record_logs("数据异常");
            return false;
        }
        // $d = $d->toArray();
        if ($data['real_amount'] < $d['pay_price']) {
            $this->record_logs('订单' . $data['out_trade_no'] . '实际支付金额低于订单金额');
            return false;
        }

        if ($d['pay_status'] == 0) {
            $data_save['pay_status'] = 1;
            $data_save['order_number'] = $data['trade_no'];
            $data_save['pay_time'] = time();
            $map_s['pay_order_number'] = $data['out_trade_no'];
    
            // 启动事务 
            Db::startTrans(); 
            try{
                $d_time = time();
                $res1 = Db::table('tab_game_ios_pay_to_download_order')->where($map_s)->update($data_save);

                $res2 = Db::table('tab_game_ios_pay_to_download_record')->insert([
                    'game_id'=>$d['game_id'],
                    'user_agent'=>$d['user_agent'],
                    'user_agent_md5'=>$d['user_agent_md5'],
                    'pay_price'=>$d['pay_price'],
                    'pay_status'=>1,
                    'create_time'=>$d_time,
                    'update_time'=>$d_time,
                ]);
        
                // 提交事务 
                Db::commit();
                $flag = 1;
            } catch (\Exception $e) { 
                // 回滚事务
                // echo $e->getMessage();
                $flag = 0;
                Db::rollback(); 
            }
            if ($flag == 0) {
                $this->record_logs("修改数据失败");
                return false;
            }
            return true;
        } else {
            return true;
        }
    }


    /**
     *日志记录
     */
    protected function record_logs($msg = "")
    {
        Log::record($msg);
    }

    /**
     * @函数或方法说明
     * @代金券数据
     * @author: 郭家屯
     * @since: 2020/8/13 13:50
     */
    protected function get_coupon_data($coupon=[],$user=[])
    {
        $add['user_id'] = $user['id'];
        $add['user_account'] = $user['account'];
        $add['coupon_id'] = $coupon['id'];
        $add['coupon_name'] = cmf_get_option('mcard_set')['coupon_name']?:$coupon['coupon_name'];
        $add['game_id'] = $coupon['game_id'];
        $add['game_name'] = $coupon['game_name'];
        $add['mold'] = $coupon['mold'];
        $add['money'] = $coupon['money'];
        $add['limit_money'] = $coupon['limit_money'];
        $add['create_time'] = time();
        $add['start_time'] = strtotime(date('Y-m-d'));
        $add['end_time'] = strtotime(date('Y-m-d'))+86399;
        $add['limit'] = $coupon['limit'];
        $add['get_way'] = 2;//尊享卡发放
        return $add;
    }


    /**
     * @获取订单信息
     *
     * @since: 2021/1/27 11:52
     * @author: zsl
     */
    protected function get_order_promote_id($pay_order_number)
    {
        $pay_where = substr($pay_order_number, 0, 2);
        switch ($pay_where) {
            case 'SP':
                $promoteOrder = Db ::table('tab_spend') -> field('id,pay_order_number,game_id,pay_promote_id,pay_amount') -> where(['pay_order_number' => $pay_order_number]) -> find();
                return $promoteOrder;
                break;
            default:
                return false;
        }
    }

}
