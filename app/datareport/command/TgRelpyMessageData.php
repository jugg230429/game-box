<?php

namespace app\datareport\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatasummaryController as Summary;

class TgRelpyMessageData extends Command
{

    protected function configure()
    {
        $this
            ->setName('tgreplymessagedata')
            ->setDescription('tg机器人定时获取群查询信息并推送对应数据脚本');
    }

    protected function execute(Input $input, Output $output)
    {
        $botToken = "7141736457:AAFjB0HJDQJbpYECsZ-EwADX1WTNsWRcf88"; // 机器人Token
        $chatId = "-4581505821"; // 群组 ChatID
        $message = "Hello, this is a message from your bot!"; // 发送的消息

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

        // 输出 API 响应（可选）
        echo $response;
    }
}