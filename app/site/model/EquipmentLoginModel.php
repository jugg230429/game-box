<?php

namespace app\site\model;

use think\Model;

/**
 * gjt
 */
class EquipmentLoginModel extends Model
{

    protected $table = 'tab_equipment_login';

    protected $autoWriteTimestamp = true;

    /**
     * [设备登录接口]
     * @param string $type
     * @return array|false|\PDOStatement|string|\think\Collection|Model
     * @author 郭家屯[gjt]
     */
    public function login($map=[],$request=[])
    {
        $equipmentLogin = $this->field('id,login_count,play_time,last_login_time,last_down_time')->where($map)->find();
        if ($equipmentLogin) {
            $equipmentLogin = $equipmentLogin->toArray();
            if($equipmentLogin['last_login_time'] > $equipmentLogin['last_down_time']){
                $outtime =  intval((time()-$equipmentLogin['last_login_time'])/3);
                if($outtime > 3600){
                    $outtime = 3600;
                }
                $gameLogin['last_down_time'] = $equipmentLogin['last_login_time'] + $outtime;
                $save['play_time'] = $equipmentLogin['play_time'] + $outtime;
            }
            $save['login_count'] = $equipmentLogin['login_count'] + 1;
            $save['last_login_time'] = time();
            $save['last_down_time'] = $gameLogin['last_down_time'];
            $result = $this->where('id', $equipmentLogin['id'])->update($save);
        } else {
            $data['play_time'] = 0;
            $save['time'] = date('Y-m-d');
            $save['equipment_num'] = $request['equipment_num'];
            $save['game_id'] = $request['game_id'];
            $save['promote_id'] = $request['promote_id'];
            $save['login_count'] = 1;
            $save['last_login_time'] = time();
            $save['sdk_version'] = $request['sdk_version'];
            $save['device_name'] = $request['device_name']?:'';
            $save['first_login_time'] = time();
            $result = $this->insert($save);
        }
        return $result;
    }

    /**
     * @函数或方法说明
     * @设备下线接口
     * @param array $map
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2019/8/12 16:38
     */
    public function down($map=[],$request=[]){
        $equipmentLogin = $this->field('id,play_time,last_login_time')->where($map)->find();
        if ($equipmentLogin) {//一日内
            $equipmentLogin->toArray();
            $save['play_time'] = $equipmentLogin['play_time'] + time() - $equipmentLogin['last_login_time'];//当天在线总时长
            $save['last_down_time'] = time();
            $result = $this->where('id', $equipmentLogin['id'])->update($save);
        } else {//跨日期
            //更新昨日记录
            $map1['equipment_num'] = $request['equipment_num'];
            $map1['game_id'] = $request['game_id'];
            $map1['promote_id'] = $request['promote_id'];
            $equipmentLogin_yes = $this->field('id,play_time,last_login_time')->where($map1)->order('time desc')->find();
            if($equipmentLogin_yes){
                $equipmentLogin_yes = $equipmentLogin_yes->toArray();
                $save['last_down_time'] = strtotime(date('Y-m-d')) - 1;//跨日期 重新计算防沉迷
                $save['play_time'] = $equipmentLogin_yes['play_time'] + $save['last_down_time'] - $equipmentLogin_yes['last_login_time'];
                $this->where('id', $equipmentLogin_yes['id'])->update($save);
            }
            //今日设备信息
            $data['time'] = date('Y-m-d');
            $data['equipment_num'] = $request['equipment_num'];
            $data['game_id'] = $request['game_id'];
            $data['promote_id'] = $request['promote_id'];
            $data['play_time'] = time() - strtotime(date('Y-m-d'));
            $data['login_count'] = 1;
            $data['last_login_time'] = strtotime(date('Y-m-d'));
            $data['last_down_time'] = time();
            $data['sdk_version'] = $request['sdk_version'];
            $data['device_name'] = $request['device_name']?:'';
            $data['first_login_time'] = strtotime(date('Y-m-d'));
            $result = $this->table('tab_equipment_login')->insert($data);
        }
        return $result;
    }

    /**
     * @函数或方法说明
     * @获取设备总数
     * @return float|string
     *
     * @author: 郭家屯
     * @since: 2020/4/1 15:14
     */
    public function all_device($map = [])
    {
        $count = $this->field('id')
            ->where($map)
            ->where('equipment_num','neq','')
            ->group('equipment_num')
            ->count();
        return $count;
    }

    /**
     * @函数或方法说明
     * @获取平均时长
     * @param array $map
     * @param int $active
     *
     * @author: 郭家屯
     * @since: 2020/4/1 16:16
     */
    public function single_duration($map=[],$active=0)
    {
        if(empty($active)){
            return 0;
        }
        $duration = $this->where($map)
                ->where('equipment_num','neq','')
                ->sum('play_time');
        return floor($duration/$active);
    }

    /*
		 * 活跃设备
		 * @param  array    $map      条件数组
		 * @param  string   $field	  字段别名
		 * @param  string   $group    分组字段名
		 * @author gjt
		 */
    public function active_on_time($map=array(),$field='active',$flag=1,$group='time',$order='time')
    {
        switch($flag) {
            case 2:{$dateform = '%Y-%m';};break;
            case 3:{$dateform = '%Y-%u';};break;
            case 4:{$dateform = '%Y';};break;
            case 5:{$dateform = '%Y-%m-%d %H';};break;
            default:$dateform = '%Y-%m-%d';
        }
        $active = $this
                ->field('FROM_UNIXTIME(first_login_time, "'.$dateform.'") as '.$group.',group_concat(id) as id,sdk_version,group_concat(distinct equipment_num) as equipment_num ,COUNT(distinct equipment_num) AS '.$field)
                ->group($group)
                ->order($order)
                ->where($map)
                ->select()->toArray();
        return $active;

    }

    /**
     * 启动机型
     * @author gjt
     */
    public function model($map=[])
    {
        $data = $this
                ->field('device_name,sdk_version,count(equipment_num) as count')
                ->group('device_name,sdk_version')
                ->order('count desc')
                ->where($map)
                ->select()->toArray();
        return $data;

    }
}