<?php

namespace app\scrm\lib;

class Time
{


    /**
     * @获取一年中每个月的开始和结束时间戳
     *
     * @author: zsl
     * @since: 2021/8/6 15:16
     */
    public static function everyMonth($y = '')
    {
        if (empty($y)) {
            $y = date('Y');
        }
        $month = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        $monthArr = [];
        foreach ($month as $k => $v) {
            $monthArr[$v]['start'] = mktime(0, 0, 0, $v, 1, $y);
            $monthArr[$v]['end'] = mktime(0, 0, 0, $v + 1, 1, $y) - 1;
        }
        return $monthArr;
    }

    /**
     * @指定月的开始和结束时间戳
     *
     * @author: zsl
     * @since: 2021/8/6 15:16
     */
    public static function month($month = '')
    {
        if (empty($month)) {
            return [];
        }
        $monthArr = [];
        if(is_array($month)){
            foreach ($month as $k => $v) {
                $monthArr[$v]['start'] = mktime(0, 0, 0, $v, 1);
                $monthArr[$v]['end'] = mktime(0, 0, 0, $v + 1, 1) - 1;
            }
        }else{
            $monthArr['start'] = mktime(0, 0, 0, $month, 1);
            $monthArr['end'] = mktime(0, 0, 0, $month + 1, 1) - 1;
        }
        return $monthArr;
    }


}
