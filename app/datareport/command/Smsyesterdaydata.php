<?php

namespace app\datareport\command;

use app\datareport\event\DatabaseController;
use app\datareport\event\PromoteController as Promote;
use app\extend\model\MsgModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\xigusdk\Xigu;

class Smsyesterdaydata extends Command
{

    protected function configure()
    {
        $this
                -> setName('smsyesterdaydata')
                -> setDescription('生成昨日统计数据');
    }

    protected function execute(Input $input, Output $output)
    {
        //查询短信通知总开关和渠道开关以及渠道手机号码
        $zong_switch = cmf_get_option('admin_set')['promote_sms_notice_switch']??0;
        if($zong_switch != 1) {
            $output -> newLine();
            $output -> writeln('总开关关闭状态,无需操作');
            exit();
        }

        //查询所有渠道id
        $promote_arr = Db::table('tab_promote')->field('id,mobile_phone,account')->where(['status'=>1,'sms_notice_switch'=>1])->select();
        if(empty($promote_arr)){
            $output -> newLine();
            $output -> writeln('暂无需要发送短信通知的渠道');
            exit();
        }
        //日期
        $promoteevent = new Promote();
        $yesterday = date("Y-m-d",strtotime("-1 day"));

        //短信配置
        $msg = new MsgModel();
        $data = $msg::get(1);
        if (empty($data) || $data['status'] != 1) {
            $output -> newLine();
            $output -> writeln('没有配置短信发送,无法发送');
            exit();
        }
        $data = $data->toArray();
        $xigu = new Xigu($data);

        foreach ($promote_arr as $k => $promote){
            //昨日数据
            $_data = $promoteevent->promote_base($yesterday, $yesterday, $promote['id']);
            $new_data = [];
            foreach ($_data as $k => $v){
                $new_data = $v;
            }
            //新增付费率 新增角色
            $new_data['new_rate'] = empty($new_data['new_register_user']) ? '0.00%' : (empty($new_data['new_pay_user']) ? '0.00%' : null_to_0(count(explode(',', $new_data['new_pay_user'])) / count(explode(',', $new_data['new_register_user'])) * 100) . '%');
            $start_time = strtotime($yesterday);
            $end_time   = strtotime(date('Y-m-d'))-1;
            $map['create_time'] = ['between',[$start_time,$end_time]];
            $new_data['new_register_role'] = Db::table('tab_user_play_info')->where($map)->count();
            //处理短信内容
            //您前一日的业绩数据如下：实付充值${pay_amount}，新增用户${new_user_num}，活跃用户${active_user_num}，
            //付费用户${pay_user_num}，付费率${pay_rate}%，新增付费率${new_pay_rate}%，新增角色${new_role_num}。
            $sms_data = [
                'pay_amount'=>$new_data['total_pay'],
                'new_user_num'=>$new_data['count_new_register_user'],
                'active_user_num'=>$new_data['count_active_user'],
                'pay_user_num'=>$new_data['count_pay_user'],
                'pay_rate'=>rtrim($new_data['rate'],'%'),
                'new_pay_rate'=>rtrim($new_data['new_rate'],'%'),
                'new_role_num'=>$new_data['new_register_role'],
            ];

            $result = json_decode($xigu->sendSM( $promote['mobile_phone'], $data['everyday_notice_tid'], $sms_data), true);
            //日志记录一下
            #TODO 短信验证数据
            if ($result['send_status'] != '000000') {
                $output -> newLine();
                $output -> writeln("渠道:".$promote['account']."短信发送发送失败!");
            }
        }


        $output -> newLine();
        $output -> writeln('昨日统计数据发送成功');
    }


}
