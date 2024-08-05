<?php

namespace app\common\lib\Util;

/**
 * @ 获取法定节假日类
 * @ 每年最新节假日安排公布后更新一次
 * Class HolidayUtil
 *
 * @package app\common\lib\Util
 */
class HolidayUtil
{

    // 法定节假日
    private static $HOLIDAY = [
            '2021-01-01', '2021-01-02', '2021-01-03',
            '2021-02-11', '2021-02-12', '2021-02-13', '2021-02-14', '2021-02-15', '2021-02-16', '2021-02-17',
            '2021-04-03', '2021-04-04', '2021-04-05',
            '2021-05-01', '2021-05-02', '2021-05-03', '2021-05-04', '2021-05-05',
            '2021-06-12', '2021-06-13', '2021-06-14',
            '2021-09-19', '2021-09-20', '2021-09-21',
            '2021-10-01', '2021-10-02', '2021-10-03', '2021-10-04', '2021-10-05', '2021-10-06', '2021-10-07',
    ];

    // 调休工作日
    private static $WORKDAY = [
            '2021-02-7', '2021-02-20', '2021-04-25', '2021-05-08', '2021-09-18', '2021-09-26', '2021-10-09',
    ];

    /**
     * @检查是否休息日
     *
     * @author: zsl
     * @since: 2021/9/1 10:53
     */
    public static function checkDayOff($timestamp = 0)
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }
        // 检查是否法定节假日
        if (in_array(date("Y-m-d", $timestamp), self::$HOLIDAY)) {
            return true;
        }
        // 检查是否周五,周六,周日并且未调休
        $week = date("w", $timestamp);
        if (($week == '5' || $week == '6' || $week == '0') && !in_array(date("Y-m-d", $timestamp), self::$WORKDAY)) {
            return true;
        }
        return false;
    }


    /**
     * @检查是否可以玩游戏
     *
     * @param int $timestamp
     *
     * @return bool|string false:不可进入游戏 | 剩余可玩游戏时间
     *
     * @author: zsl
     * @since: 2021/9/1 14:27
     */
    public static function checkPlayGameStatus($timestamp = 0)
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }
        //是否节假日
        if (false == self ::checkDayOff($timestamp)) {
            return false;
        }
        //时间是否在20时至21时
        $hour = date("H", $timestamp);
        if ($hour != '20') {
            return false;
        }
        //查询剩余时间
        $finishTimes = mktime(21, 0, 0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
        $surplus = $finishTimes - $timestamp;
        if($surplus<=0){
            return 0;
        }
        return $surplus;
    }


}
