<?php

namespace xingwenge\canal_php;

use cmf\org\RedisSDK\RedisController;
use Com\Alibaba\Otter\Canal\Protocol\Column;
use Com\Alibaba\Otter\Canal\Protocol\Entry;
use Com\Alibaba\Otter\Canal\Protocol\EntryType;
use Com\Alibaba\Otter\Canal\Protocol\EventType;
use Com\Alibaba\Otter\Canal\Protocol\RowChange;
use Com\Alibaba\Otter\Canal\Protocol\RowData;

class Fmt
{
    /**
     * @param Entry $entry
     *
     * @throws \Exception
     */
    public static function println($entry)
    {
        switch ($entry -> getEntryType()) {
            case EntryType::TRANSACTIONBEGIN:
            case EntryType::TRANSACTIONEND:
                return;
                break;
        }
        $rowChange = new RowChange();
        $rowChange -> mergeFromString($entry -> getStoreValue());
        $evenType = $rowChange -> getEventType();
        $header = $entry -> getHeader();
        echo sprintf("================> binlog[%s : %d],name[%s,%s], eventType: %s", $header -> getLogfileName(), $header -> getLogfileOffset(), $header -> getSchemaName(), $header -> getTableName(), $header -> getEventType()), PHP_EOL;
        echo $rowChange -> getSql(), PHP_EOL;
        /** @var RowData $rowData */
        foreach ($rowChange -> getRowDatas() as $rowData) {
            switch ($evenType) {
                case EventType::DELETE:
                    self ::ptColumn($rowData -> getBeforeColumns());
                    break;
                case EventType::INSERT:
                    self ::ptColumn($rowData -> getAfterColumns());
                    break;
                default:
                    echo '-------> before', PHP_EOL;
                    self ::ptColumn($rowData -> getBeforeColumns());
                    echo '-------> after', PHP_EOL;
                    self ::ptColumn($rowData -> getAfterColumns());
                    break;
            }
        }
    }

    private static function ptColumn($columns)
    {
        /** @var Column $column */
        foreach ($columns as $column) {
            echo sprintf("%s : %s  update= %s", $column -> getName(), $column -> getValue(), var_export($column -> getUpdated(), true)), PHP_EOL;
        }
    }


    /**
     * @param Entry $entry
     *
     * @throws \Exception
     */
    public static function callback($entry)
    {
        switch ($entry -> getEntryType()) {
            case EntryType::TRANSACTIONBEGIN:
            case EntryType::TRANSACTIONEND:
                return;
                break;
        }
        $rowChange = new RowChange();
        $rowChange -> mergeFromString($entry -> getStoreValue());
        $evenType = $rowChange -> getEventType();
        $header = $entry -> getHeader();
        $tableName = $header -> getTableName();
        foreach ($rowChange -> getRowDatas() as $rowData) {
            $data = [];
            $data['tableName'] = $tableName;
            $data['event'] = $evenType;
            switch ($evenType) {
                case EventType::INSERT:
                    foreach ($rowData -> getAfterColumns() as $column) {
                        $data['data'][$column -> getName()] = $column -> getValue();
                    }
                    break;
                case EventType::UPDATE:
                    foreach ($rowData -> getAfterColumns() as $column) {
                        $data['data'][$column -> getName()] = $column -> getValue();
                    }
                    break;
                case EventType::DELETE:
                    foreach ($rowData -> getBeforeColumns() as $column) {
                        $data['data'][$column -> getName()] = $column -> getValue();
                    }
                    break;
                default:
                    break;
            }
            //存入待通知队列,计划任务定时通知
            $pushData = self ::getPushData($data);
            if(!empty($pushData)){
                $redis = new RedisController(['host' => '127.0.0.1', 'port' => 6379]);
                $redis -> rPush('scrm_push_data', json_encode($pushData));
            }

            unset($data);
            unset($pushData);
        }


    }


    private static function getPushData($param)
    {
        $data = [];
        switch ($param['tableName']) {
            case "tab_user":
                $data['data_id'] = $param['data']['id'];
                $data['type'] = 1;//玩家
                break;
            case "tab_promote":
                $data['data_id'] = $param['data']['id'];
                $data['type'] = 2;//推广员
                break;
            case "tab_promote_business":
                $data['data_id'] = $param['data']['id'];
                $data['type'] = 3;//商务
                break;
            case "tab_spend":
                $data['data_id'] = $param['data']['id'];
                $data['type'] = 4;//游戏订单
                break;
            case "tab_support":
                $data['data_id'] = $param['data']['id'];
                $data['type'] = 5;//扶持
                break;
            default:
                break;
        }
        return $data;

    }

}
