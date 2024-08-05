<?php

namespace app\site\model;

use think\Model;

/**
 * gjt
 */
class EquipmentGameModel extends Model
{

    protected $table = 'tab_equipment_game';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @设备登录
     * @param string $type
     *
     * @author: 郭家屯
     * @since: 2019/8/12 16:31
     */
    public function login($map=[],$request=[])
    {
        $equipment = $this->field('id,create_time')->where($map)->order('id desc')->find();
        $equipment = empty($equipment)?[]:$equipment->toArray();
        if (!$equipment || (date('Y-m-d', $equipment['create_time']) != date('Y-m-d'))) {
            $data['equipment_num'] = $request['equipment_num'];
            $data['promote_id'] = $request['promote_id'];
            $data['game_id'] = $request['game_id'];
            $data['sdk_version'] = $request['sdk_version'];
            $data['device_name'] = $request['device_name']?:'';
            $data['ip'] = get_client_ip();
            $data['create_time'] = time();
            if ($equipment) {
                $data['first_device'] = 2;
            } else {
                $data['first_device'] = 1;
            }
            $result = $this->insert($data);
        }
        return $result;
    }

    /**
     * @函数或方法说明
     * @统计新增设备
     * @param array $map
     * @param int $flag 0新增  1 活跃
     *
     * @return int
     *
     * @author: 郭家屯
     * @since: 2020/4/1 15:18
     */
    public function total_device($map=[],$flag=0) {

        if($flag) {
            $data = $this->active($map);
        } else {
            $data = $this->news($map);
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * #新增设备查询
     * @param array $map
     *
     * @return float|string
     *
     * @author: 郭家屯
     * @since: 2020/4/1 15:21
     */
    protected function news($map=[])
    {
        $data = $this->field('id')
                ->where($map)
                ->where('equipment_num','neq','')
                ->where('first_device',1)
                ->group('equipment_num')
                ->count();
        return $data;
    }

    /**
     * @函数或方法说明
     * @活跃数据查询
     * @param array $map
     *
     * @author: 郭家屯
     * @since: 2020/4/1 15:21
     */
    protected function active($map=[])
    {
        $data = $this->field('id')
                ->where($map)
                ->where('equipment_num','neq','')
                ->group('equipment_num')
                ->count();
        return $data;
    }

    /*
		 * 新增设备
		 * @param  array    $map      条件数组
		 * @param  string   $field	  字段别名
		 * @param  string   $group    分组字段名
		 * @author 郭家屯
		 */
    public function news_on_time($map=array(),$field='news',$flag=1,$group='time',$order='time') {

        switch($flag) {
            case 2:{$dateform = '%Y-%m';};break;
            case 3:{$dateform = '%Y-%u';};break;
            case 4:{$dateform = '%Y';};break;
            case 5:{$dateform = '%Y-%m-%d %H';};break;
            default:$dateform = '%Y-%m-%d';
        }
        $map['first_device'] = 1;
        $news = $this
                ->field('FROM_UNIXTIME(create_time, "'.$dateform.'") as '.$group.',group_concat(id) as id,sdk_version as version,group_concat(distinct equipment_num) as equipment_num ,COUNT(distinct equipment_num) AS '.$field)
                ->where($map)
                ->group($group)
                ->order($order)
                ->select()->toArray();
        return $news;

    }
}