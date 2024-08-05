<?php

namespace app\recharge\command;

use app\recharge\model\CouponRecordModel;
use app\site\model\TipModel;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;

class Coupon extends Command
{

    protected function configure()
    {
        $this
            ->setName('coupon')
            ->setDescription('代金券过期提醒');
    }

    protected function execute(Input $input, Output $output)
    {
        $model = new CouponRecordModel();
        $data = $model->get_notice_coupon();
        $tipmodel = new TipModel();
        $url = url('mobile/coupon/index',['type'=>1],'',false);
        foreach ($data as $key=>$v){
            $save['user_id'] = $v['user_id'];
            $save['title'] = "代金券到期通知";
            $save['content'] = '您在平台中还有未使用的代金券即将过期，<a style="color:blue;text-decoration:underline;" href="'.$url.'">点击查看></a>';
            $save['create_time'] = time();
            $save['type'] = 1; // 添加消息类型
            
            $tipmodel->insert($save);
        }
        echo "执行结束";
    }
}