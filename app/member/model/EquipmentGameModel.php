<?php

namespace app\member\model;

use think\Model;
use think\Db;

/**
 * gjt
 */
class EquipmentGameModel extends Model
{

    protected $table = 'tab_equipment_game';

    protected $autoWriteTimestamp = true;


    function equipment_game_retain($equipments = '', $game_id = '', $date = '')
    {
        if ($equipments == '') {
            return [];
        } else {
            $map['tab_equipment_game.equipment_num'] = ['in', $equipments];
        }
        if ($game_id == '') {
            $map['tab_equipment_game.game_id'] = ['gt', 0];
            $gmap['game_id'] = ['gt', 0];
        } else {
            $map['tab_equipment_game.game_id'] = $game_id;
            $gmap['game_id'] = $game_id;
        }
        $data = $this
            ->field('equipment_num,create_time,GROUP_CONCAT(create_time) as time_str,game_id')
            ->where($map)
            ->order('create_time asc')
            ->group('equipment_num')
            ->select()->toArray();
        foreach ($data as $k => $v) {
            $timearr = explode(',', $v['time_str']);
            if (count($timearr) > 1) {
                asort($timearr);
                $data[$k]['start_time'] = reset($timearr);
                $data[$k]['last_time'] = end($timearr);
            } else {
                $data[$k]['last_time'] = $data[$k]['start_time'] = $v['create_time'];
            }
        }
        $end = date('Y-m-d');
        $start = date('Y-m-d', strtotime($end) - 29 * 24 * 3600);
        $gmap['time'] = ['in', periodDate($start, $end)];
        foreach ($data as $k => $v) {
            $gmap['equipment_num'] = $v['equipment_num'];
            $visit30 = Db::table('tab_equipment_login')->field('sum(play_time) as sumtime,sum(login_count) as sunmount')->where($gmap)->select()->toArray();
            $data[$k]['online_time30'] = empty($visit30['sumtime']) ? 0 : $visit30['sumtime'];
            $data[$k]['login_count30'] = empty($visit30['suncount']) ? 0 : $visit30['sunmount'];
        }
        return $data;
    }


}