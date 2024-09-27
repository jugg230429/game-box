<?php

namespace app\recharge\command;

use app\recharge\model\CouponRecordModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class MemberCoupon extends Command
{

    protected function configure()
    {
        $this
            ->setName('memberCoupon')
            ->setDescription('每日凌晨向尊享卡用户发送优惠券');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->newLine();
        $output->writeln(date('Y-m-d H:i:s') . '程序开始');
        //程序配置的赠送代金券
        $mcard_set = cmf_get_option('mcard_set');
        $coupon_id = $mcard_set['coupon_id'];
        $coupon = get_coupon_info($coupon_id);
        //查找未到期的尊享卡用户
        $userList = Db::table('tab_user_member')->where('pay_status',1)->where('end_time','>',time())->order('id asc')->column('user_id');
        foreach($userList as $userId){
            $user = get_user_entity($userId,false,'id,account,member_days,end_time');
            $coupon_data = $this->get_coupon_data($coupon,$user);
            Db::table('tab_coupon_record')->insert($coupon_data);
        }
        $output->newLine();
        $output->writeln(date('Y-m-d H:i:s') . '执行结束');
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