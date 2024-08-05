<?php

namespace app\scrm\command;

use app\common\model\DataChangeRecordModel;
use app\scrm\lib\Security;
use cmf\org\RedisSDK\RedisController;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class PushData extends Command
{

    protected function configure()
    {
        $this -> setName('scrm:push_data')
                -> setDescription('推送scrm平台');
    }


    protected function execute(Input $input, Output $output)
    {
        Config ::load(APP_PATH . '/scrm/config.php');
        $push_url = config('scrm.push_data_api');
        if (empty($push_url)) {
            return false;
        }
        $s = new Security(config('scrm.key'));
        //获取待同步数据
        $mChangeRecord = new DataChangeRecordModel();
        $field = "id,data_id,type";
        $where = [];
        $where['notice_scrm_status'] = '0';
        $pushData = $mChangeRecord -> field($field) -> where($where) -> order('create_time asc') -> limit('10') -> select();
        if (empty($pushData)) {
            return false;
        }
        //通知scrm平台
        request_post($push_url, ['request' => $s -> encrypt(json_encode($pushData))]);
        //修改数据状态
        foreach ($pushData as $data) {
            $data -> notice_scrm_status = 1;
            $data -> save();
        }
        return true;
    }


}
