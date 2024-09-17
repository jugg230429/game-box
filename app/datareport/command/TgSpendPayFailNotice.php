<?php

namespace app\datareport\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatasummaryController as Summary;

class TgSpendPayFailNotice extends Command
{

    protected function configure()
    {
        $this
            ->setName('tgspendpayfailnotice')
            ->setDescription('tg机器人定时查询下单错误日志表推送通知脚本');
    }

    protected function execute(Input $input, Output $output)
    {
        $botToken = "7141736457:AAFjB0HJDQJbpYECsZ-EwADX1WTNsWRcf88"; // 机器人Token
        $chatId = "-4508993735"; // 群组 ChatID
        $url = "https://api.telegram.org/bot$botToken/sendMessage";

        //查询下单异常表推送后，更新状态
        $log = Db::table('tab_spend_promote_fail_log')->where('status',0)->order('id asc')->select()->toArray();
        if (empty($log)) {
            $output->newLine();
            $output->writeln(date('Y-m-d H:i:s') .'暂无错误日志');
            return false;
        }
        foreach($log as $v){
            $payType = $v['pay_type'] == 1 ? '支付宝':'微信';
            $message = "异常订单数据：" . "\n" .
                '时间：  '  . $v['create_time'] . "\n" . 
                '订单号：'  . $v['pay_order_number'] . "\n" .
                '金额：'    . $v['pay_amount'] . "\n" .
                '支付商家：' . $v['promote_name'] . "\n" .
                '支付方式：' . $payType . "\n" .
                '通道编码：' . $v['channel_coding'] . "\n".
                '错误内容：' . $v['content'] . "\n";

            $data = [
                'chat_id' => $chatId,
                'text' => $message,
            ];

            // 使用 cURL 发送 POST 请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response,true);
            if(isset($response['ok']) && $response['ok'] === true){
                Db::table('tab_spend_promote_fail_log')->where('id',$v['id'])->update(['status'=>1]);
            }
        }
        
        $output->newLine();
        $output->writeln(date('Y-m-d H:i:s') . '推送完成');
    }
}