<?php

namespace app\thirdgame\command;

use app\common\logic\InvitationLogic;
use app\common\logic\PayLogic;
use app\member\model\UserModel;

use app\common\task\HandleUserStageTask;
use app\thirdgame\logic\PlatformLogic;
use app\thirdgame\logic\ThirdGameApiLogic;
use app\thirdgame\model\SpendModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Spend extends Command
{

    protected function configure()
    {
        $this
            ->setName('spend')
            ->setDescription('拉取第三方平台订单');
    }

    protected function execute(Input $input, Output $output)
    {
        // 获取所有第三方平台
        $PlatformLogic = new PlatformLogic();
        $pmap['status'] = 1;
        $platform_list = $PlatformLogic->get_platform($pmap);
        $platform_ids = array_column($platform_list,'id');
        $spend_ids = [];
        foreach ($platform_ids as $key => $val)
        {
            $GameLogic = new ThirdGameApiLogic($val);
            $spends = $GameLogic->importOrder($val);
            $spend_ids = array_merge($spend_ids,$spends);
        }
        if(empty($spend_ids)){
           echo '没有需要导入的订单';exit;
        }
        $SpendModel = new SpendModel();
        foreach ($spend_ids as $key => $val) {
            $d = $SpendModel->where('id',$val)->find();
            if(empty($d)){
                echo '订单不存在:'.$val;
                continue;
            }
            if($d['user_id'] == 0 || $d['game_id'] == 0){
                echo '订单用户不存在：'. $val;
                continue;
            }
            try{
                $user = get_user_entity($d['user_id'], false, 'id,account,promote_id,promote_account,parent_name,parent_id,cumulative,invitation_id');
                $UserModel = new UserModel();
                if (user_is_paied($d['user_id']) == 0) {
                    // 首冲奖励
                    $UserModel->task_complete($d['user_id'], 'first_pay', $d['pay_amount']);
                }
                // 充值送积分
                $UserModel->auto_task_complete($d['user_id'], 'pay_award', $d['pay_amount']);
                // 每日首充积分奖励
                $UserModel->first_pay_every_day($d['user_id'], $d['pay_amount']);
                // 设置邀请人代金券
                if ($user['invitation_id'] > 0) {
                    $money = $d['pay_amount'] + $user['cumulative'];
                    $InvitationLogic = new InvitationLogic();
                    $InvitationLogic->send_spend_coupon($user['invitation_id'], $user['id'], $money);
                }
                // 更新VIP等级和充值总计
                set_vip_level($d['user_id'], $d['pay_amount'], $user['cumulative']);
                // 生成渠道结算订单
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
            }catch(\Exception $e){
                echo '订单设置异常：'. $val;
            }
            echo '订单设置成功：'.$val;
        }
    }
}