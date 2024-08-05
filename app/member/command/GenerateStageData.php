<?php

namespace app\member\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class GenerateStageData extends Command
{

    protected function configure()
    {
        $this -> setName('GenerateStageData')
                -> setDescription('生成玩家阶段统计信息');
    }


    
    protected function execute(Input $input, Output $output)
    {
        // $tmp_user_ids = Db::table('tab_user')
        // ->where(['lock_status'=>1])
        // ->column('id');
        // var_dump($tmp_user_ids);exit;
        // $userStageInfo = Db::table('tab_user_stage')->select()->toArray();
        // foreach ($userStageInfo as $k1=>$v1) {
        //     $remindInfo = json_decode($v1['follow_remind'],true);
        //     var_dump($remindInfo);
        // }

        // exit;
        $todayTimeInt = strtotime(date('Y-m-d'));
        $userStageInfo = Db::table('tab_user_stage')->select()->toArray();
        $i = 0;
        foreach($userStageInfo as $k1=>$v1){
            $remindInfo = json_decode($v1['follow_remind'], true);
            $follow_remind_switch = $remindInfo['follow_remind_switch'] ?? 0;
            if($follow_remind_switch == 1){
                $remindMsg = '';
                $not_login_num = 0;
                $not_recharge_num = 0;

                $stage_id = $v1['id'];
                // 统计需要提醒的数据
                $not_login_days = $remindInfo['not_login_days'] ?? 0;
                if($not_login_days > 0){
                    $not_login_num = Db::table('tab_user')
                        ->where(['user_stage_id'=>$stage_id, 'lock_status'=>1, 'login_time'=>['<', $todayTimeInt-$not_login_days*86400]])
                        ->count();
                }
                $not_recharge_days = $remindInfo['not_recharge_days'] ?? 0;
                if($not_recharge_days > 0){
                    $tmp_user_ids = Db::table('tab_user')
                        ->where(['user_stage_id'=>$stage_id, 'lock_status'=>1])
                        ->column('id');
                    foreach($tmp_user_ids as $k2=>$v2){
                        $spend_info = Db::table('tab_spend')
                            ->where(['user_id'=>$v2])
                            ->order('pay_time desc')
                            ->field('id,user_id,pay_time')
                            ->find();
                        $pay_time = $spend_info['pay_time'] ?? 0;
                        if($pay_time > $todayTimeInt-$not_recharge_days*86400){
                            unset($tmp_user_ids[$k2]);
                        }
                    }
                    $not_recharge_num = count($tmp_user_ids);
                }
                if($not_login_num !=0 || $not_recharge_num !=0){
                    // $remindInfo['remind_time'] = empty($remindInfo['remind_time']) ? '9:00' : $remindInfo['remind_time'];
                    $remindInfo['remind_time'] = '9:00';
                    // var_dump($remindInfo['remind_time']);exit;
                    $insertData = [
                        'user_stage_id'=>$stage_id,
                        'remindtime'=>$remindInfo['remind_time'],
                        'admin_ids'=>json_encode($remindInfo['remind_admin_ids']),
                        'remind_msg'=>"【溪谷软件】".$v1['name']."目前有".$not_login_num."个用户超过".$not_login_days."天未登录, 有".$not_recharge_num."个用户超过".$not_recharge_days."天未付费, 如有异常请及时处理。",
                        'not_login_num'=>$not_login_num,
                        'not_login_days'=>$not_login_days,
                        'not_recharge_num'=>$not_recharge_num,
                        'not_recharge_days'=>$not_recharge_days,
                        'remind_status'=>0,
                        'remind_fail_admin_ids'=>'',
                        'remind_type'=>$remindInfo['remind_type'],
                        'create_time'=>time(),
                    ];
                    $b = Db::table('tab_admin_remind_msg')->insert($insertData);
                    
                    $i ++;
                }
            }
        }
        echo '处理了'.$i.'条数据';
        exit;
    }


}
