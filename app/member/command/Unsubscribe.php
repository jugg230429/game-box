<?php

namespace app\member\command;

use app\user\logic\UnsubscribeLogic;
use app\user\model\UserUnsubscribeModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Unsubscribe extends Command
{

    protected function configure()
    {
        $this -> setName('unsubscribe')
                -> setDescription('注销申请到期的账号');
    }


    /**
     * @注销用户账号
     *
     * @author: zsl
     * @since: 2021/5/14 10:34
     */
    protected function execute(Input $input, Output $output)
    {

        $lUnsubscribe = new UnsubscribeLogic();
        //获取待注销账号
        $mUnsubscribe = new UserUnsubscribeModel();
        $record = $mUnsubscribe -> getExpireUser();
        if (empty($record)) {
            $output -> newLine();
            $output -> writeln("无待注销用户");
            return false;
        }
        // 注销账号
        try {
            $record -> status = 3; //注销中
            $record -> save();
            $mUnsubscribe -> startTrans();
            // 开始注销
            $userInfo = $mUnsubscribe -> doUnsubscribe($record);
            //发送通知
            if (!empty($userInfo['phone'])) {
                // 发送短信通知
                $lUnsubscribe -> sendSms($userInfo['phone']);
            } elseif (!empty($userInfo['email'])) {
                // 发送邮件通知
                $lUnsubscribe -> sendMail($userInfo['email']);
            }
            //完成注销, 修改状态
            $record -> status = 2; //已注销.
            $record -> save();
            $mUnsubscribe -> commit();
            $output -> newLine();
            $output -> writeln($record['user_account'] . "完成注销");
        } catch (\Exception $e) {
            $mUnsubscribe -> rollback();
            //记录错误信息
            $record -> err_num += 1;
            $record -> exception_msg = $e -> getMessage();
            $record -> status = 1; // 待注销
            $record -> isUpdate(true) -> save();
            $output -> newLine();
            $output -> writeln($record['user_account'] . "注销失败");
        }
        return true;
    }


}
