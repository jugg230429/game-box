<?php

namespace app\scrm\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use xingwenge\canal_php\CanalClient;
use xingwenge\canal_php\CanalConnectorFactory;
use xingwenge\canal_php\Fmt;

class Canal extends Command
{


    protected function configure()
    {
        $this -> setName('scrm:canal')
                -> setDescription('监听数据库变化');
    }

    /**
     * @监听数据库变化
     *
     * @param Input $input
     * @param Output $output
     *
     * @throws \Exception
     * @since: 2021/8/7 14:33
     * @author: zsl
     */
    protected function execute(Input $input, Output $output)
    {

        $databaseName = config('database.database');

        //连接calan服务器
        $client = CanalConnectorFactory ::createClient(CanalClient::TYPE_SOCKET_CLUE);
        $client -> connect("127.0.0.1", 11111);
        $client -> checkValid();
        //监听规则
        $filter = [
                "{$databaseName}.tab_user",
                "{$databaseName}.tab_promote",
                "{$databaseName}.tab_promote_business",
                "{$databaseName}.tab_spend",
                "{$databaseName}.tab_support",
        ];
        //订阅事件
        $client -> subscribe("1001", "example", implode(',', $filter));
        while (true) {
            $message = $client -> get(100);
            if ($entries = $message -> getEntries()) {
                foreach ($entries as $entry) {
                    Fmt ::callback($entry);
                }
            }
            sleep(1);
        }
        $client -> disConnect();
    }

}
