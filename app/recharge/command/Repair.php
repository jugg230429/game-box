<?php

namespace app\recharge\command;

use app\recharge\model\SpendModel;
use app\api\GameApi;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;

class Repair extends Command
{

    protected function configure()
    {
        $this
            ->setName('repair')
            ->setDescription('订单通知失败自动补单');
    }

    // 2021-07-24-qsh 根据auto_repair_times<=5 执行自动补单计划任务
    protected function execute(Input $input, Output $output)
    {
        $model = new SpendModel();
        $map['pay_status'] = ['in', '1,2']; // 充值成功或苹果支付
        $map['pay_game_status'] = ['eq', '0']; // 通知失败
        $map['auto_repair_times'] = ['elt', '5']; // 自动补单次数小于等于5
        $order = $model->field('id,pay_order_number,pay_status,pay_game_status,auto_repair_times,user_id,promote_id,extend')->where($map)->order('id desc')->select();
        if (empty($order)) {
            $output->newLine();
            $output->writeln("暂无需要补单的数据");
        }
        $order = $order->toArray();
        foreach ($order as $key=>$v) {
            $user_entity = get_user_entity($v["user_id"],false,'id,account,promote_id,promote_account,parent_name,parent_id,cumulative');
            $game = new GameApi();
            if ($v['pay_status'] == 2) {
                Db::startTrans();
                try {
                    //修改订单状态
                    $model->where('pay_order_number', $v['pay_order_number'])->where('pay_way', 6)->setField('pay_status', 1);
                    //更新VIP等级和充值总金额
                    set_vip_level($v["user_id"], $v['pay_order_number'],$user_entity['cumulative']);
                    //添加结算订单
                    if ($v['promote_id']) {
                        $promote = get_promote_entity($v['promote_id'],'pattern');
                        if ($v['pattern'] == 0) {
                            set_promote_radio($v,$user_entity);
                        }
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $output->newLine();
                    $output->writeln("处理失败");
                    continue;
                }
            }
            $v['out_trade_no'] = $v['pay_order_number'];
            $res = $game->game_pay_notify($v);
            // 补单次数+1
            $add_times = $model->where('pay_order_number', $v['pay_order_number'])->setInc('auto_repair_times', 1);
            if($res===false){
                $output->newLine();
                $output->writeln('订单号【'.$v['pay_order_number'].'】处理失败');
            }else{
                $output->newLine();
                $output->writeln('订单号【'.$v['pay_order_number'].'】处理成功');
            }

        }
    }
}