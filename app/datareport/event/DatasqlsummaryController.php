<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 19:13
 * sql查询
 */

namespace app\datareport\event;

use cmf\controller\HomeBaseController;
use cmf\org\RedisSDK\RedisController as Redis;
use think\Db;

class DatasqlsummaryController extends HomeBaseController
{
    public function basedata_pid_everyday($date = '')
    {
        if (strtotime($date) - 1 > strtotime(date('Y-m-d', time()))) {
            return;
        }
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        $host = config("database.hostname");
        $dbname = config("database.database");
        $account = config("database.username");
        $password = config("database.password");
        $pdo = new \PDO("mysql:host={$host};dbname={$dbname}", $account, $password);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $uresult = $pdo->query("SELECT * FROM `tab_datareport_every_pid` where `time` = \"{$date}\"");
        //$data = Db::table('tab_datareport_every_pid')->where(['time' => $date])->select()->toArray();
//        if (empty($data)) {
//            $redis->hSet('datareporteverypid_' . $date, '', '');
//        }
        if (empty($uresult)) {
            $redis->hSet('datareporteverypid_' . $date, '', '');
        }
        $redis->multi();
        if($uresult){
            while($value = $uresult->fetch(\PDO::FETCH_ASSOC)) {
                $redis->hSet('datareporteverypid_' . $date, $value['promote_id'] . '_' . $value['game_id'], json_encode($value));
            }
        }
//        foreach ($data as $key => $value) {
//            $redis->hSet('datareporteverypid_' . $date, $value['promote_id'] . '_' . $value['game_id'], json_encode($value));
//        }
        $redis->exec();
        $this->basedata_top_everyday($date);
    }

    public function basedata_top_everyday($date = '')
    {
        if (strtotime($date) - 1 > strtotime(date('Y-m-d', time()))) {
            return;
        }
        //$data = Db::table('tab_datareport_top_pid')->where(['time' => $date])->select()->toArray();
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        $host = config("database.hostname");
        $dbname = config("database.database");
        $account = config("database.username");
        $password = config("database.password");
        $pdo = new \PDO("mysql:host={$host};dbname={$dbname}", $account, $password);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $uresult = $pdo->query("SELECT * FROM `tab_datareport_top_pid` where time = \"{$date}\"");
//        if (empty($data)) {
//            $redis->hSet('datareporttoppid_' . $date, '', '');
//        }
        if(empty($uresult)){
            $redis->hSet('datareporttoppid_' . $date, '', '');
        }
        $redis->multi();
        if($uresult){
            while($value = $uresult->fetch(\PDO::FETCH_ASSOC)) {
                $redis->hSet('datareporttoppid_' . $date, $value['promote_id'] . '_' . $value['game_id'], json_encode($value));
            }
        }
//        foreach ($data as $key => $value) {
//            $redis->hSet('datareporttoppid_' . $date, $value['promote_id'] . '_' . $value['game_id'], json_encode($value));
//        }
        $redis->exec();

    }
}