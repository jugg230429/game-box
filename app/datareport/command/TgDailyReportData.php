<?php

namespace app\datareport\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatasummaryController as Summary;

class TgDailyReportData extends Command
{
    protected function configure()
    {
        $this
            ->setName('tgdailyreportdata')
            ->setDescription('tg机器人每日12点和24点推送统计数据');
    }

    protected function execute(Input $input, Output $output)
    {
        $botToken = "7141736457:AAFjB0HJDQJbpYECsZ-EwADX1WTNsWRcf88"; // 机器人Token
        $chatId = "-4581505821"; // 群组 ChatID
            


        $message = "
                    每日推送数据：\n
                    一。汇总数据\n
                    时间：年月日-时分秒\n
                    新增用户：3915\n
                    活跃用户：6488\n
                    付费用户：772\n
                    新增付费账号：329\n
                    新增付费金额：12436\n
                    总付费金额：63221\n
                    激活设备数：13647\n
                    \n
                    二。游戏明细数据\n
                    时间：年月日-时分秒\n
                    游戏名称：后宫三国（安卓版）\n
                    新增用户：3915\n
                    活跃用户：6488\n
                    付费用户：772\n
                    付费用户：314\n
                    新增付费账号：165\n
                    订单数：3789\n
                    新增付费总额：4524\n
                    付费金额（真实支付）：10359\n
                    Arpu：1.60\n
                    Arppu：32.99\n
                    付费率：4.84%\n
                    新增付费率：4.22%\n
                    \n
                    "; // 发送的消息

        $url = "https://api.telegram.org/bot$botToken/sendMessage";

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

        $result = json_decode($response,true)['ok'];
        $output->newLine();
        if($result == 'ok'){
            $output->writeln('数据推送成功' . date("Y-m-d H:i:s"));
        }
        // 输出 API 响应（可选）
        // echo $response;
    }
}