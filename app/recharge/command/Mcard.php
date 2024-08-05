<?php

namespace app\recharge\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;

class Mcard extends Command
{

    protected function configure()
    {
        $this
            ->setName('mcard')
            ->setDescription('尊享卡发放代金券');
    }

    protected function execute(Input $input, Output $output)
    {
        //发放代金券
        $mcard_set = cmf_get_option('mcard_set');
        $coupon_id = $mcard_set['coupon_id'];
        $coupon = get_coupon_info($coupon_id);
        $user = Db::table('tab_user')
                ->field('id,account')
                ->where('end_time','gt',time())
                ->select();
        $list = [];
        foreach ($user as $key=>$v){
            $list[] = $this->get_coupon_data($coupon,$v);
        }
        $result = Db::table('tab_coupon_record')->insertAll($list);
        if($result){
            echo "执行结束";
        }else{
            echo "执行失败";
        }
    }

    /**
     * @函数或方法说明
     * @代金券数据
     * @author: 郭家屯
     * @since: 2020/8/13 13:50
     */
    protected function get_coupon_data($coupon=[],$user=[])
    {
        $add['user_id'] = $user['id'];
        $add['user_account'] = $user['account'];
        $add['coupon_id'] = $coupon['id'];
        $add['coupon_name'] = cmf_get_option('mcard_set')['coupon_name']?:$coupon['coupon_name'];
        $add['game_id'] = $coupon['game_id'];
        $add['game_name'] = $coupon['game_name'];
        $add['mold'] = $coupon['mold'];
        $add['money'] = $coupon['money'];
        $add['limit_money'] = $coupon['limit_money'];
        $add['create_time'] = time();
        $add['start_time'] = strtotime(date('Y-m-d'));
        $add['end_time'] = strtotime(date('Y-m-d'))+86399;
        $add['limit'] = $coupon['limit'];
        $add['get_way'] = 2;//尊享卡发放
        return $add;
    }
}