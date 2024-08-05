<?php

use think\Db;

#-------------------------------------------
/**
 * 两日期形成数组
 * @param $start_time
 * @param $end_time
 * @return mixed
 * @author yyh
 */
function periodDate($start_time, $end_time)
{
    $start_time = strtotime($start_time);
    $end_time = strtotime($end_time);
    $i = 0;
    while ($start_time <= $end_time) {
        $arr[$i] = date('Y-m-d', $start_time);
        $start_time = strtotime('+1 day', $start_time);
        $i++;
    }

    return $arr;
}

/**
 * 获取条件内的激活设备数
 * @param array $map
 * @return int
 */
function fire_device($map = [])
{
    $event = new \app\datareport\event\DatasummaryController();
    $map['first_device'] = 1;
    $map['game_id'] = ['gt', 0];
    $group = null;
    $field = 'GROUP_CONCAT(DISTINCT(equipment_num)) as data_str,game_id';
    $data = $event->get_register_device_num($map, $group, $field);
    $equipment_num = $data[0]['data_str'];
    $res = empty($equipment_num) ? 0 : count(explode(',', $equipment_num));
    return $res;
}
