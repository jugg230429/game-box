<?php

namespace app\common\task;

use think\Controller;
use think\Db;

class HandleUserStageTask extends Controller
{
    // 将判断是否升级的动作写入数据表 
    public function saveOperation($operate='', $user_id, $pay_amount=0)
    {
        // 充值是否需要升级写入操作记录
        
        $paramTmp = ['user_id'=>$user_id];
        $d_time_int = time();
        // new \app\common\task\HandleUserStage();
        $saveData = [
            'title'=>'用户充值判断是否需要更改阶段',
            'class_name'=>'\app\common\task\HandleUserStage',
            'function_name'=>'changeUserStage1',
            'param'=>$paramTmp,
            'remark'=>'',
            'create_time'=>$d_time_int,
            'update_time'=>$d_time_int,
            'status'=>0  // 0:待执行 1:已执行
        ];
        $taskOperate = new Task();
        // $saveRes = Db::table('tab_task_trigger')->insert($saveData);
        $saveRes = $taskOperate->create($saveData);
        return $saveRes; // 成功true 失败false
        
    }
    public function changeUserStage1($tmpParamArr=[])
    {
        $doRes = $this->doChangeUserStage1($tmpParamArr);
        $code = $doRes['code'] ?? -1;
        if($code == 1){
            return true;
        }else{
            return false;
        }

    }
    // 登录或者充值时判断是否需要进阶
    public function doChangeUserStage1($tmpParamArr=[])
    {
        // $tmpParamArr = json_decode($tmpParam, true);
        if(empty($tmpParamArr)){
            return ['code'=>-1, 'msg'=>'缺少参数!', 'data'=>[]];
        }
        $userId = $tmpParamArr['user_id'] ?? '';
        // $payAmount = $tmpParamArr['pay_amount'] ?? '';
        // if(empty($userId) && empty($payAmount)){
        //     return ['code'=>-1, 'msg'=>'缺少参数!', 'data'=>[]];
        // }
        if(empty($userId)){
            return ['code'=>-1, 'msg'=>'缺少参数!', 'data'=>[]];
        }
        $userInfo = Db::table('tab_user')
            ->where(['id'=>$userId])
            ->field('id,account,cumulative,user_stage_id')
            ->find();

        $stageId = $userInfo['user_stage_id'] ?? 0;
        // 按排续查询下一个阶段
        $nextStageId = 0;
        $userStage = Db::table('tab_user_stage')->order('only_for_sort desc')->select()->toArray();

        if($stageId == 0){
            // return ['code'=>2, 'msg'=>'无需操作!', 'data'=>[]];
            foreach($userStage as $k=>$v){
                $nextStageId = $v['id'];
            }
        }else{
            foreach($userStage as $k=>$v){
                if($v['id'] == $stageId){
                    $nextStageId = $userStage[$k-1]['id'];
                }
            }
        }
        if(empty($nextStageId)){
            return ['code'=>2, 'msg'=>'已升级到最高级!', 'data'=>[]];
        }

        $stageInfo = Db::table('tab_user_stage')->where(['id'=>$nextStageId])->find();
        $otherSetting = $stageInfo['other_setting'] ?? '';
        $otherSettingArr = json_decode($otherSetting, true);
        // 进阶条件
        $total_consume = $otherSettingArr['total_consume'] ?? 0;  // 累计消费
        $consume_day = $otherSettingArr['consume_day'] ?? 0;  // 消费频次 近多少天内
        $consume_times = $otherSettingArr['consume_times'] ?? 0;  // 消费频次, 消费多少次
        $active_day = $otherSettingArr['active_day'] ?? 0;  // 活跃情况 近多少天内
        $active_times = $otherSettingArr['active_times'] ?? 0;  // 活跃情况 活跃多少次
        // 用户目前的条件
        $user_total_consume = $userInfo['cumulative'];
        if($user_total_consume < $total_consume){
            return ['code'=>2, 'msg'=>'累充达不到升级标准!', 'data'=>[]];
        }
        // $todayTimeInt = time();
        $todayTimeInt = strtotime(date('Y-m-d'));
        $inTime = $todayTimeInt - $consume_day * 86400; // 大于这个时间都算数
        $user_consume_times = Db::table('tab_spend')
            ->where(['user_id'=>$userId, 'pay_status'=>1])
            ->where(['pay_time'=>['>', $inTime]])
            ->count();
        if($user_consume_times < $consume_times){
            return ['code'=>2, 'msg'=>'充值次数达不到升级标准!', 'data'=>[]];
        }
        $inTime2 = $todayTimeInt - $active_day * 86400; // 大于这个时间都算数
        $user_active_times = Db::table('tab_user_login_record')
            ->where(['user_id'=>$userId, 'game_id'=>['>', 0]])
            ->where(['login_time'=>['>', $inTime2]])
            ->count();
        
        if($user_active_times < $active_times){
            return ['code'=>2, 'msg'=>'活跃次数达不到升级标准!', 'data'=>[]];
        }
        // 走到这里 可以升级
        $user_score = $otherSettingArr['user_score'] ?? 0.00;
        $updateRes = Db::table('tab_user')->where(['id'=>$userId])->update(['user_stage_id'=>$nextStageId, 'user_score'=>$user_score]);
        if($updateRes){
            return ['code'=>1, 'msg'=>'升级成功!', 'data'=>[]];
        }else{
            return ['code'=>-1, 'msg'=>'更新失败!', 'data'=>[]];
        }
        
    }
    // 登录时判断是否需要进阶
    public function changeUserStage2()
    {

    }
}