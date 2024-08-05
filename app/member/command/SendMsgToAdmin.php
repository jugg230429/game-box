<?php

namespace app\member\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use app\extend\model\MsgModel;
use think\xigusdk\Xigu;

class SendMsgToAdmin extends Command
{

    protected function configure()
    {
        $this -> setName('SendMsgToAdmin')
                -> setDescription('给管理员发送玩家阶段信息');
    }

    protected function execute(Input $input, Output $output)
    {
       $resMsg = $this->sendMsgToAdmin();
       exit($resMsg);
    }

    // 计划任务 发送短信或者邮件给管理员
    protected function sendMsgToAdmin()
    {
        $todayTimeInt = strtotime(date('Y-m-d'));
        $d_time_int = time();
        $needRemind = Db::table('tab_admin_remind_msg')->where(['remind_status'=>0, 'create_time'=>['<', $d_time_int]])->select()->toArray();
        if(empty($needRemind)){
            return '没有要处理的信息!';
        }
        foreach($needRemind as $k=>$v){
            $adminPhoneNums = '';
            $adminEmails = '';
            $admin_ids_arr = json_decode($v['admin_ids'], true);

            if(!empty($admin_ids_arr)){
                // 给管理员发送短信 / 邮件
                foreach($admin_ids_arr as $k2=>$v2){
                    $adminInfoTmp = Db::table('sys_user')->where(['id'=>$v2])->field('id,mobile,user_email')->find();
                    $adminPhone = $adminInfoTmp['mobile'] ?? '';
                    if(!empty($adminPhone)){
                        $adminPhoneNums .= $adminPhone.',';
                    }
                    $adminEmailTmp = $adminInfoTmp['user_email'] ?? '';
                    if(!empty($adminEmailTmp)){
                        $adminEmails .= $adminEmailTmp.',';
                    }
                }
                $adminPhoneNums = rtrim($adminPhoneNums, ",");
                $adminEmails = rtrim($adminEmails, ",");
            }
            // $msgVariate = [
            //     'stage_name'=>$v['stage_name'],
            //     'not_login_num'=>$v['not_login_num'],
            //     'not_login_days'=>$v['not_login_days'],
            //     'not_recharge_num'=>$v['not_recharge_num'],
            //     'not_recharge_days'=>$v['not_recharge_days'],
            // ];
            $msgVariate = [
                'stage_name'=>$v['stage_name'],
                'not_login_num'=>$v['not_login_num'],
                'not_login_days'=>$v['not_login_days'],
                'not_recharge_num'=>$v['not_recharge_num'],
                'not_recharge_days'=>$v['not_recharge_days'],
            ];

            if($v['not_login_num'] > 0 ||  $v['not_recharge_num'] > 0){
                // 发送短信
                if($v['remind_type'] == 1 && !empty($adminPhoneNums)){
                    $msgRes = $this->sendMsg($adminPhoneNums, $msgVariate);
                    if($msgRes['send_status'] == "000000"){
                        Db::table('tab_admin_remind_msg')->where(['id'=>$v['id']])->update(['remind_status'=>1, 'update_time'=>time()]);
                    }else{
                        // Db::table('tab_admin_remind_msg')->where(['id'=>$v['id']])->update(['remind_status'=>2, 'update_time'=>time()]);
                    }
                }
                // 发用邮件
                if($v['remind_type'] == 2 && !empty($adminEmails)){
                    $emailsArr = explode(',', $adminEmails);
                    $content = $v['remind_msg'];
                    $emailRes = $this->sendEmail($emailsArr, $content);
                    if($emailRes['error'] == 0){
                        Db::table('tab_admin_remind_msg')->where(['id'=>$v['id']])->update(['remind_status'=>1, 'update_time'=>time()]);
                    }else{
                        // Db::table('tab_admin_remind_msg')->where(['id'=>$v['id']])->update(['remind_status'=>2, 'update_time'=>time()]);
                    }
                }
            }
            
        }
        return '处理成功!';

    }

    // 发送短信
    private function sendMsg($phoneNums, $param)
    {
        try {
            $msg = new MsgModel();
            $data = $msg ::get(1);
            if (empty($data)) {
                return false;
            }
            $xigu = new Xigu($data);
            $result = json_decode($xigu -> sendSM($phoneNums, $data['user_stage_tid'], $param), true);
            return $result;
        } catch (\Exception $e) {
            return false;
        }

    }
    // 发送邮件
    private function sendEmail($mailArr, $content)
    {
        try {
            $result = cmf_send_batch_email($mailArr, '玩家阶段提醒', $content);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }


}
