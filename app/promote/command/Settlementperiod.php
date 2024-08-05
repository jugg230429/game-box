<?php

namespace app\promote\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;

class Settlementperiod extends Command
{
    /*
        按周期统计 记录 不同渠道的结算单/生成结算单 并将归到结算单里面的划分为已结算
        created by wjd
        2021-6-29 10:43:00
        $time_type=归档时间类型0: 按天, 1:按每月的几号,$promote_id=推广员id,$day_period=按多少天的周期,$date_period=按每月的几号
        $time_type=0,$promote_id=0,$day_period=20,$date_period=1
    */
    protected function configure()
    {
        $this->setName('settlementperiod')->setDescription('Auto settlement promote orders');
    }

    protected function execute(Input $input, Output $output)
    {
        $d_time = time();
        $promoteInfos = Db::table('tab_promote_settlement_time')->where(['next_count_time'=>['<=',$d_time],'day_period'=>['>',0]])->select();
        $promoteInfo = [];
        foreach($promoteInfos as $k=>$v){
            $promoteInfo[$k]['promote_id'] = $v['promote_id'];
            $promoteInfo[$k]['time_type'] = $v['time_type'];
            $promoteInfo[$k]['day_period'] = $v['day_period'];
            $promoteInfo[$k]['next_count_time'] = $v['next_count_time'];
            $promoteInfo[$k]['promote_account'] = $v['promote_account'];
        }
        if(empty($promoteInfo)){
            echo json_encode(['code'=>-1,'msg'=>'没有符合计算的渠道'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // 也可以直接用 $promoteInfos 直接遍历查询
        $j = 0; // 记录执行成功的条数
        foreach($promoteInfo as $k1=>$v1){
            $do_res = $this->do_period_record($v1['time_type'],$v1['promote_id'],$v1['day_period'],1,$v1['next_count_time'],$v1['promote_account']);
            $code = $do_res['code'] ?? -1;
            if($code > 0){
                $j ++;
            }
        }
        $operateMsg = '操作次数:'.$j;
        echo $operateMsg;
        $output->writeln($operateMsg);
    }
    // 进行计算符合条件的结算记录, 并生成归档记录
    private function do_period_record($time_type=0,$promote_id=0,$day_period=20,$date_period=1,$next_count_time,$promote_account){
        // 按照指定天数结算
        $d_time = time();
        if($time_type == 0){
            if($day_period < 1){
                $day_period = 20;
            }
            // 将推广员结算表中符合结算的记录标记为已结算 并取出相应的数据进行计算
            if($next_count_time < $d_time-100){  // 下次计算时间小于当前时间 则当前时间之前的记录全部统计
                $next_count_time = strtotime(date('Y-m-d'));
            }
            // 计算一级渠道和所有的子渠道的 top_promote_id
            $settlementInfo = Db::table('tab_promote_settlement')
                ->where(['top_promote_id'=>$promote_id,'status'=>0,'is_check'=>1,'ti_status'=>0,'create_time'=>['<',$next_count_time]])
                ->order('create_time desc')
                ->select();
            $tmpIds = []; // 将需要更新的数据的id先存起来
            $t_pay_amount = 0.00; // 总实付金额
            $t_sum_money = 0.00; // 渠道得到的总的佣金
            $tmp_create_time = $d_time; // 仅用作记录最早的一条记录
            // var_dump($settlementInfo);exit;
            if(empty($settlementInfo->toArray())){
                // echo '没有符合计算的记录!';
                return ['code'=>-1,'msg'=>'没有符合计算的记录!'];
            }
            $total_cps = 0;
            $total_cpa = 0;
            $register_user_num = 0;
            $plateform_earn = 0;

            $endTimeInt = 0;
            foreach($settlementInfo as $k3=>$v3){
                $tmpIds[] = $v3['id'];
                $t_pay_amount += $v3['pay_amount'];
                $t_sum_money += $v3['sum_money'];
                // $tmp_create_time[] = $v3['create_time'];
                $tmp_create_time = $v3['create_time'];
                // 按照cps分成汇总
                if($v3['pattern'] == 0){  // cps
                    $total_cps += $v3['sum_money'];
                    // $plateform_earn += $v3['pay_amount']*(1-$v3['ratio']);
                    $plateform_earn += $v3['pay_amount'] - $v3['sum_money'];
                }
                // 按照cpa分成汇总
                if($v3['pattern'] == 1){  // cpa
                    $total_cpa += $v3['sum_money'];
                    $register_user_num ++;
                }
                if($v3['create_time'] > $endTimeInt){
                    $endTimeInt = $v3['create_time'];
                }
            }
            // 计算时间段
            $startTime = date('Y-m-d',$tmp_create_time);
            $endTime = date('Y-m-d', $next_count_time-100);
            // 下个结算的时间点
            $next_count_time = strtotime(date('Y-m-d')) + $day_period * 24*60*60; // 下个结算的时间点
            // 启用事务
            Db::startTrans();
            try{
                // 更新渠道周期表
                Db::table('tab_promote_settlement_time')->where(['promote_id'=>$promote_id])->update(['next_count_time'=>$next_count_time]);
                // 插入周期结算统计记录
                $period_id = Db::table('tab_promote_settlement_period')
                    ->insertGetId([
                        'period'=>$startTime.'至'.$endTime,
                        'promote_id'=>$promote_id,
                        'promote_account'=>$promote_account,
                        'order_num'=>$this->create_order(),
                        'total_money'=>$t_pay_amount,  // 总实付金额
                        'promoter_earn'=>$t_sum_money,  // 渠道得到的总佣金
                        'plateform_earn'=>$plateform_earn,
                        'is_remit'=>0,
                        'create_time'=>$d_time,
                        'update_time'=>0, // 更新时间 默认为打款时间,打款操作的时候进行更新
                        'period_start'=>$tmp_create_time, // 结算开始时间
                        'period_end'=>$endTimeInt, // 结算结束时间
                        'total_cps'=>$total_cps,
                        'total_cpa'=>$total_cpa,
                        'register_user_num'=>$register_user_num
                    ]);

                Db::table('tab_promote_settlement')
                    ->where(['id'=>['in',$tmpIds]])
                    ->update(['status'=>1,'period_id'=>$period_id,'update_time'=>$d_time]);
                Db::table('tab_promote')->where('id', $promote_id)->setInc('balance_profit', $t_sum_money);
                // var_dump($next_count_time);exit;
                // 提交事务
                Db::commit();
                return ['code'=>1,'msg'=>'修改成功!'];
            } catch (\Exception $e) {
                Db::rollback();
                return ['code'=>-1,'msg'=>'修改失败!'];
            }
        }

        // 按照每月几号进行结算
        if($time_type == 1){
            // 获取下个月的指定号的时间戳(如果下个月没有这个号, 则跳过 直接找下下个月, 以此类推)
            $whichDay = $date_period;
            if($whichDay > 31 || $whichDay < 1){
                $whichDay = 5; // 如果 whichDay 赋值不正确 则默认改成每月的5号
            }
            $whichDay = 31;
            $time = date('Y-m-'.$whichDay.'' , time());
            $next_date = date('Y-m-'.$whichDay.'' , strtotime('+1 month' , strtotime($time)));
            var_dump($next_date);exit;
        }
    }

    // 生成订单号
    private function create_order(){
        $a = uniqid(mt_rand());
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $length = strlen($str) - 1; //获取字符串的长度
        //2.字符串截取bai开始位置
        $start=rand(0,$length-6);
        //3.字符串截取长度 6 位
        $count = 6; // 取六位
        $use_str = str_shuffle($str); // 随机打乱字符串
        $b = substr($use_str, $start, $count); // 截取字符串，取其中的一部分字符串
        $final_name = 'JS-'.date('Ymd').$a.$b;
        return $final_name;
    }
}