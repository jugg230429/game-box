<?php
/**
 * @获取游戏验证开启状态
 *
 */
function get_game_sdk_verify($id)
{
    $sdk_verify = \think\Db ::table('tab_game') -> where(['id' => $id]) -> value('sdk_verify');
    return $sdk_verify;
}

/**
 * @检测访问频率
 *
 */
function check_access_frequency($key, $time = 60, $limit = 1, $db = 2)
{
    $redis = new Redis();
    $redis -> connect('127.0.0.1', 6379);
    $redis -> select($db);
    $check = $redis -> exists($key);
    //如果存在请求记录,则判断请求次数
    if ($check) {
        $redis -> incr($key);
        $count = $redis -> get($key);
        if ($count > $limit) {
            //请求频繁
            return false;
        }
        return true;
    }
    $redis -> incr($key);
    //设置限制时间
    $redis -> expire($key, $time);
    return true;
}

