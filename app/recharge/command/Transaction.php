<?php

namespace app\recharge\command;

use app\member\model\UserTransactionModel;
use app\member\model\UserTransactionOrderModel;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;

class Transaction extends Command
{

    protected function configure()
    {
        $this
            ->setName('transaction')
            ->setDescription('交易解除锁定');
    }

    protected function execute(Input $input, Output $output)
    {
        $model = new UserTransactionModel();
        $ordermodel = new UserTransactionOrderModel();
        $time = time()-300;
        //解除交易锁定
        $data = $model->alias('t')
                ->field('id,small_id,user_id')
                ->where('lock_time','elt',$time)
                ->where('status',-1)
                ->select()
                ->toArray();
        foreach ($data as $key=>$v){
            Db::startTrans();
            try{
                $save['status'] = 1;
                $price = Db::table('tab_user_transaction_tip')->where('transaction_id',$v['id'])->where('type',1)->where('status',0)->find();
                $lower = Db::table('tab_user_transaction_tip')->where('transaction_id',$v['id'])->where('type',2)->where('status',0)->find();
                if($price){
                    $save['money'] = $price['price'];
                    Db::table('tab_user_transaction_tip')->where('id',$price['id'])->setField('status',1);
                }
                if($lower){
                    $save['status'] = 4;
                    $save['lower_shelf'] = '';
                    Db::table('tab_user')->where('id',$v['small_id'])->setField('puid',$v['user_id']);
                    Db::table('tab_user_play_info')->where('user_id',$v['small_id'])->setField('puid',$v['user_id']);
                    Db::table('tab_user_transaction_tip')->where('id',$lower['id'])->setField('status',1);
                }
                $model->where('id',$v['id'])->update($save);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
        }
        //超时支付设置无效
        $ordermodel->where('pay_time','elt',$time)->where('pay_status',0)->setField('pay_status',2);
        echo "执行结束";
    }
}