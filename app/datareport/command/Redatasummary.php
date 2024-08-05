<?php

namespace app\datareport\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatasummaryController as Summary;

class Redatasummary extends Command
{

    protected function configure()
    {
        $this
            ->setName('re_datasummary')
            ->setDescription('重新生成数据报表');
    }

    protected function execute(Input $input, Output $output)
    {
        echo '暂停统计!';
        exit;

        set_time_limit(0);
        // 处理时间
        $start_date = '';
        $end_date = '';
        $datareport_help_info = Db::table('tab_datareport_help')->where(['status'=>0])->order('id asc')->limit(1)->select()->toArray();
        if(empty($datareport_help_info)){
            // 去除锁
            Db::table('tab_datareport_help')->where(['id'=>1])->setField('lock_switch',0);
            echo json_encode(['code'=>1,'msg'=>'已执行完毕!','data'=>[]], JSON_UNESCAPED_UNICODE);
            exit;
        }else{
            $start_date = $datareport_help_info[0]['time_string'];
            foreach($datareport_help_info as $tmp_val){
                $end_date = $tmp_val['time_string'];
            }
        }

        $event = new Summary();
        //时间范围
        $end_date = empty($end_date) ? $start_date : $end_date;
        $todaytime = strtotime(date("Y-m-d"));
        if (empty($start_date)) {
            $start_date = $end_date = date('Y-m-d', $todaytime - 86400);
        }
        $date_arr = Db::name('date_list')->where(['time' => ['between', [$start_date, $end_date]]])->order('time asc')->column('time');
        if (empty($date_arr)) {
            $output->newLine();
            $output->writeln('日期参数错误');
            return false;
        }

        //获取渠道数据
        $promote_data = Db::table('tab_promote')->order('id asc')->column('id');
        if (empty($promote_data)) {
            $promote_data = [];
        }
        array_unshift($promote_data, 0);//添加0的官方渠道
        $top_promote_data = Db::table('tab_promote')->where(['parent_id' => 0,'promote_level'=>1])->order('id asc')->column('id');
        if (empty($top_promote_data)) {
            $top_promote_data = [];
        }
        array_unshift($top_promote_data, 0);
        // 每日循环统计
        foreach ($date_arr as $dk => $dv) {
            $date = $dv;
            //今日及以后不可汇总
            if ($todaytime <= strtotime($date)) {
                $output->newLine();
                $output->writeln($date . "该日期不可汇总");
                continue;
            }
            $is_exist = Db::table('tab_datareport_every_pid')->field('id')->where(['time' => $date])->find();
            //判断是否重复汇总
            if (!empty($is_exist)) {
                $output->newLine();
                $output->writeln($date . "已汇总，不用重复汇总");
                continue;
            }
            // 汇总开始
            $output->newLine();
            $output->writeln($date . '汇总开始' . time());
            $data = [];
            foreach ($promote_data as $k => $v) {
                //每日汇总
                $basedata = $event->basedata_every_pid($v, $date);
                if (!empty($basedata)) {
                    $data[] = $basedata;
                }
            }
            $new_data = [];
            foreach ($data as $kk => $vv) {
                foreach ($vv as $k => $v) {
                    $redisdata['datareporteverypid_' . $date][$k] = $new_data[] = $v;
                }
            }
            //记录数据库
            $res = Db::table('tab_datareport_every_pid')->insertAll($new_data);
            $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
            $redis->multi();
            foreach ($redisdata as $key => $value) {
                foreach ($value as $kk => $vv) {
                    $redis->hSet($key, $kk, json_encode($vv));
                }
            }
            $redis->exec();
            //上级渠道记录
            foreach ($top_promote_data as $k => $v) {
                //每日汇总
                $redistopdata = $topdata = $event->basedata_top_pid($v, $date);
                $topdata = array_values($topdata);
                if (empty($topdata)) {
                    continue;
                }
                //记录数据库
                $res = Db::table('tab_datareport_top_pid')->insertAll($topdata);
                $redis->multi();
                foreach ($redistopdata as $kk => $vv) {
                    $redis->hSet('datareporttoppid_' . $date, $kk, json_encode($vv));
                }
                $redis->exec();
            }
            $output->newLine();
            $output->writeln($date . '汇总结束' . time());
        }
        // 将操作过的数据状态标记为1
        $operate_nums = Db::table('tab_datareport_help')->where(['time_string' => ['between', [$start_date, $end_date]]])->setField('status',1);
    }
}