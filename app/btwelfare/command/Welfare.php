<?php

namespace app\btwelfare\command;

use app\btwelfare\model\BtWelfareMonthcardModel;
use app\btwelfare\model\BtWelfareWeekcardModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Welfare extends Command
{

    protected function configure()
    {
        $this
                -> setName('welfare')
                -> setDescription('每日充值月卡,周卡发放状态');
    }


    protected function execute(Input $input, Output $output)
    {

        //重置未过期月卡发放状态
        $mMonthCard = new BtWelfareMonthcardModel();
        $where = [];
        $where['status'] = 1;//已发放
        $where['expire_time'] = ['gt', time()];
        $mMonthCard -> where($where) -> setField('status', 0);
        //充值未过期周卡发放状态
        $mWeekCard = new BtWelfareWeekcardModel();
        $where = [];
        $where['status'] = 1;//已发放
        $where['expire_time'] = ['gt', time()];
        $mWeekCard -> where($where) -> setField('status', 0);
        echo "执行结束";
    }
}