<?php

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Task extends Command
{
    protected function configure()
    {
        $this -> setName('run_task') -> setDescription('执行任务队列');
    }

    protected function execute(Input $input, Output $output)
    {
        while (1) {
            $lTask = new \app\common\task\Task();
            if (!$lTask -> run()) {
                if (!empty($lTask -> getErrorMsg())) {
                    $output -> newLine();
                    $output -> writeln($lTask -> getErrorMsg());
                }
            }
            sleep(1);
        }
    }
}
