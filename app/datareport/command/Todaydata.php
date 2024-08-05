<?php

namespace app\datareport\command;

use app\datareport\event\DatabaseController;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Todaydata extends Command
{

    protected function configure()
    {
        $this
                -> setName('todaydata')
                -> setDescription('生成当日统计数据');
    }

    protected function execute(Input $input, Output $output)
    {
        $event = new DatabaseController();
        $event -> basedata_today();
        $output -> newLine();
        $output -> writeln('当日数据更新结束');
    }


}
