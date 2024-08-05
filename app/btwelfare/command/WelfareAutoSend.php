<?php

namespace app\btwelfare\command;

use app\btwelfare\logic\MonthCardLogic;
use app\btwelfare\logic\WeekCardLogic;
use app\btwelfare\model\BtWelfareMonthcardModel;
use app\btwelfare\model\BtWelfareWeekcardModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class WelfareAutoSend extends Command
{


    protected function configure()
    {
        $this
                -> setName('welfare_auto_send')
                -> setDescription('自动发放周卡,月卡福利');
    }

    protected function execute(Input $input, Output $output)
    {

        //发放周卡
        $mWeekCard = new BtWelfareWeekcardModel();
        $where = [];
        $where['w.status'] = 0;
        $where['w.expire_time'] = ['gt', time()];
        $where['p.bt_welfare_week_auto'] = 1;
        $weekCardLists = $mWeekCard -> alias('w')
                -> field('w.id')
                -> join(['tab_promote' => 'p'], 'w.promote_id=p.id')
                -> where($where)
                -> select();
        if (!$weekCardLists -> isEmpty()) {
            $lWeekCard = new WeekCardLogic();
            foreach ($weekCardLists as $v) {
                $lWeekCard -> send($v['id']);
            }
        }
        //发放月卡
        $mMonthCard = new BtWelfareMonthcardModel();
        $where = [];
        $where['m.status'] = 0;
        $where['m.expire_time'] = ['gt', time()];
        $where['p.bt_welfare_month_auto'] = 1;
        $monthCardLists = $mMonthCard -> alias('m')
                -> field('m.id')
                -> join(['tab_promote' => 'p'], 'm.promote_id=p.id')
                -> where($where)
                -> select();
        if (!$monthCardLists -> isEmpty()) {
            $MonthCard = new MonthCardLogic();
            foreach ($monthCardLists as $v) {
                $MonthCard -> send($v['id']);
            }
        }
        echo "执行结束";
    }


}