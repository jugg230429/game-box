<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 19:13
 * sql查询
 */

namespace app\datareport\event;

use app\member\model\UserLoginRecordModel;
use cmf\controller\HomeBaseController;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatasqlsummaryController as Sqlsummary;
use app\datareport\event\DatasummaryController as Summary;
use think\Db;

class DatabaseController extends HomeBaseController
{
    public function basedata($starttime = '', $endtime = '', $promote_id = '', $game_id = '', $array_keys = '',$include0game=1,$type='pc')
    {
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        $datearr = Db::name('date_list')->where(['time' => ['between', [$starttime, $endtime]]])->column('time');
        foreach ($datearr as $k => $v) {
//            if($v == date('Y-m-d')){
//                $this->basedata_today();
//            }
            $key = 'datareporttoppid_' . $v;
            $keyarr = $redis->hKeys($key);//得到该日期所有域值
            //redis数据丢失时 实时取sql重新写入
            if (empty($keyarr)) {
                $sqlsummary = new Sqlsummary();
                $sqlsummary->basedata_pid_everyday($v);
                $keyarr = $redis->hKeys($key);//得到该日期所有域值
            }
            if($promote_id=='is_gf'){
                $promote_id=0;
            }
            //搜索条件 移除不符合key
            if ($promote_id > 0 || $promote_id ===0 || !empty($game_id)) {
                foreach ($keyarr as $kak => $kav) {
                    if ($promote_id > 0) {
                        if (strstr($kav, '_', true) != $promote_id) {
                            unset($keyarr[$kak]);
                        }
                    }
                    if ($promote_id === 0) {
                        if (strstr($kav, '_', true) === false || (int)strstr($kav, '_', true) !== $promote_id) {
                            unset($keyarr[$kak]);
                        }
                    }
//                    if ($game_id > 0) {
//                        if (ltrim(strstr($kav, '_', false), '_') != $game_id) {
//                            unset($keyarr[$kak]);
//                        }
//                    }
                    //修改-game_id可能是多个id-20210624-byh-s
                    if (!empty($game_id)) {
                        if (!in_array(ltrim(strstr($kav, '_', false), '_'),explode(',',$game_id))) {
                            unset($keyarr[$kak]);
                        }
                    }
                    //修改-game_id可能是多个id-20210624-byh-e
                }
            }elseif(!$include0game){
                foreach ($keyarr as $kak => $kav) {
                    if (ltrim(strstr($kav, '_', false), '_') == 0) {
                        unset($keyarr[$kak]);
                    }
                }
            }
            $keystr = implode(',', $keyarr);
            $field = empty($keystr) ? '' : $keystr;
            $redisdata = array_values($redis->hMget($key, $field));
            $data = [];
            array_map(function ($item) use (& $data) {
                $data[] = json_decode($item, true);
            }, $redisdata);
            $array_keys = empty($array_keys) ? ['time', 'new_register_user', 'new_register_device', 'new_register_device_user', 'active_user', 'active_user7', 'active_user30', 'pay_user', 'total_order', 'total_pay', 'new_pay_user', 'new_total_order', 'new_total_pay', 'fire_device'] : $array_keys;
            $new_data[$v] = [];
            foreach ($array_keys as $kk) {
                if ($kk == 'new_register_user' || $kk == 'total_pay' || $kk == 'new_total_pay') {
                    $unique = 0;
                } else {
                    $unique = 1;
                }
                $new_data[$v][$kk] = str_unique(trim(array_key_value_link($kk, $data), ','), $unique);//每个游戏的数据合并
                $new_data[$v]['count_' . $kk] = arr_count($new_data[$v][$kk]);
                if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                    $new_data[$v][$kk] = str_arr_sum($new_data[$v][$kk]);
                }
                $total_data[$kk] = '';//汇总数据数组
            }
            $new_data[$v]['time'] = $v;
        }
        //计算汇总
        unset($total_data['time'], $total_data['promote_id'], $total_data['game_id'], $total_data['create_time'], $total_data['update_time']);
        foreach ($total_data as $kk => $vv) {
            $total_data[$kk] = array_filter(array_column($new_data, $kk));
            if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                $total_data[$kk] = null_to_0(array_sum($total_data[$kk]));
            } else {
                $new = implode(',', $total_data[$kk]);
                $total_data[$kk] = count(array_filter(array_unique(explode(',', $new))));
            }
        }
        //新增逻辑，重新查询订单总值，只查询真实充值，剔除绑币和平台币充值
        $map = [];
        $map['pay_status'] = 1;
        $map['pay_way'] = ['>',2];
        $map['pay_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
        if($game_id){
            $map['game_id'] = $game_id;
        }
        if($promote_id){
            $map['promote_id'] = $promote_id;
        }
        //付费用户
        $result = Db::table('tab_spend')->distinct(true)->field('user_id')->where($map)->select();
        $total_data['pay_user'] =  empty($result) ? 0 : count($result); 
        //订单数
        $total_data['total_order'] = Db::table('tab_spend')->where($map)->count();
        //总付费额 
        $total_data['total_pay'] = Db::table('tab_spend')->where($map)->sum('pay_amount');

        // //新增付费用户
        // $inserUserId = Db::table('tab_user')->where('register_time','between',[strtotime($starttime), strtotime($endtime) + 86399])->column('id');
        // $map['user_id'] = ['in',$inserUserId];
        // $total_data['new_pay_user'] = Db::table('tab_spend')->where($map)->count();
        // //新增付费额
        // $total_data['new_total_pay'] = Db::table('tab_spend')->where($map)->sum('pay_amount');
        
        //列表计算
        foreach ($new_data as &$v) {
            //新增逻辑，重新查询订单总值，只查询真实充值，剔除绑币和平台币充值
            $map = [];
            $map['pay_status'] = 1;
            $map['pay_way'] = ['>',2];
            $map['pay_time'] = ['between', [strtotime($v['time']), strtotime($v['time']) + 86399]];
            if($game_id){
                $map['game_id'] = $game_id;
            }
            if($promote_id){
                $map['promote_id'] = $promote_id;
            }
            //付费用户
            $result = Db::table('tab_spend')->distinct(true)->field('user_id')->where($map)->select();
            $v['count_pay_user'] = empty($result) ? 0 : count($result); 
            $v['pay_user'] = empty($result) ? 0 : count($result); 
            //订单数
            $v['count_total_order'] = Db::table('tab_spend')->where($map)->count();
            //总付费额 
            $v['total_pay'] = Db::table('tab_spend')->where($map)->sum('pay_amount');
            
            // //新增付费用户
            // $inserUserId = Db::table('tab_user')->where('register_time','between',[strtotime($starttime), strtotime($endtime) + 86399])->column('id');
            // $map['user_id'] = ['in',$inserUserId];
            // $new_data[$k]['new_pay_user'] = Db::table('tab_spend')->where($map)->count();
            // //新增付费额
            // $new_data[$k]['new_total_pay'] = Db::table('tab_spend')->where($map)->sum('pay_amount');
            $v['rate'] = empty($v['active_user']) ? '0.00%' : (empty($v['pay_user']) ? '0.00%' : null_to_0($v['pay_user']/ count(explode(',', $v['active_user'])) * 100) . '%');
            $v['arpu'] = empty($v['active_user']) ? '0.00' : null_to_0($v['total_pay'] / count(explode(',', $v['active_user'])));
            $v['arppu'] = empty($v['pay_user']) ? '0.00' : null_to_0($v['total_pay'] / $v['pay_user']);
        }
        if($type == 'wap'){
            return ['data'=>$new_data,'total'=>$total_data];
        }else{
            $this->assign('total_data', $total_data);
            return $new_data;
        }
    }

    public function user_retain($data = [], $num = 1, $needkey = 'register_retain')
    {
        $new_data = array_slice($data, $num);
        foreach ($new_data as $k => $v) {
            for ($i = 1; $i <= $num; $i++) {
                $user_day_retain = $this->user_every_day_retain($data, $k, $i, $num, $needkey);
                if ($needkey == 'register_retain') {
                    $new_data[$k]['count_next' . $i] = $user_day_retain[0];
                    $new_data[$k]['next' . $i] = $user_day_retain[1];
                    $new_data[$k]['same' . $i] = $user_day_retain[2];
                } elseif ($needkey == 'active_retain') {
                    $new_data[$k]['count_next' . $i] = $user_day_retain[0];
                    $new_data[$k]['next' . $i] = $user_day_retain[1];
                    $new_data[$k]['same' . $i] = $user_day_retain[2];
                    $new_data[$k]['active_count_next' . $i] = $user_day_retain[0];
                    $new_data[$k]['active_next' . $i] = $user_day_retain[1];
                    $new_data[$k]['active_same' . $i] = $user_day_retain[2];
                } elseif ($needkey == 'device_retain') {
                    $new_data[$k]['count_next' . $i] = $user_day_retain[0];
                    $new_data[$k]['next' . $i] = $user_day_retain[1];
                    $new_data[$k]['same' . $i] = $user_day_retain[2];
                    $new_data[$k]['device_count_next' . $i] = $user_day_retain[0];
                    $new_data[$k]['device_next' . $i] = $user_day_retain[1];
                    $new_data[$k]['device_same' . $i] = $user_day_retain[2];
                }
            }
        }
        return $new_data;
    }

    //每天
    public function user_every_day_retain($data = [], $date, $i = 1, $day = 1, $needkey = 'new_register_user')
    {
        $datetime = strtotime($date) + $i * 24 * 3600;
        $todaytime = strtotime(date('Y-m-d'));
        if ($datetime >= $todaytime) {
            return [false, false, false];//今天及之后留存不显示数值
        }
        if ($needkey == 'register_retain') {
            $new_register_user = $data[$date]['new_register_user'];
            $date_register_user = empty($new_register_user) ? [] : array_unique(explode(',', $new_register_user));
            asort($date_register_user);
            //几日后
            $nextdate = date('Y-m-d', $datetime);
            $active_user = $data[$nextdate]['active_user'];
            $next_day_active = empty($active_user) ? [] : array_unique(explode(',', $active_user));
        } elseif ($needkey == 'active_retain') {
            $new_register_user = $data[$date]['active_user'];
            $date_register_user = empty($new_register_user) ? [] : array_unique(array_filter(explode(',', $new_register_user)));
            asort($date_register_user);
            //几日后
            $nextdate = date('Y-m-d', $datetime);
            $active_user = $data[$nextdate]['active_user'];
            $next_day_active = empty($active_user) ? [] : array_unique(array_filter(explode(',', $active_user)));
        } elseif ($needkey == 'device_retain') {
            $new_register_user = $data[$date]['fire_device'];
            $date_register_user = empty($new_register_user) ? [] : array_unique(array_filter(explode(',', $new_register_user)));
            asort($date_register_user);
            //几日后
            $nextdate = date('Y-m-d', $datetime);
            $active_user = $data[$nextdate]['active_device'];
            $next_day_active = empty($active_user) ? [] : array_unique(array_filter(explode(',', $active_user)));
        } else {
            exit('参数错误');
        }
        $samearr = array_unique(array_intersect($date_register_user, $next_day_active));
        $same = count($samearr);
        $samerate = empty(count($date_register_user)) ? '0.00%' : (null_to_0($same / count($date_register_user)) * 100) . '%';
        return [$same, $samerate, implode(',', $samearr)];
    }

    //每周
    public function user_week_retain($data = [], $num = 1)
    {
        $sdefaultDate = date("Y-m-d");
        $w = date('w', strtotime($sdefaultDate));
        //本周开始
        $week_start = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - 1 : 6) . ' days'));
        //本周结束日期
        $week_end = date('Y-m-d', strtotime("$week_start +6 days"));
        $week_end_time = strtotime($week_end);
        $slice = array_slice($data, $num * 7);
        $datakeys = array_keys($data);
        $thisweekdata = array_slice($data, array_search($week_end, $datakeys), 7);
        $new_data[$week_start . '~' . $week_end] = $thisweekdata;
        for ($i = 0; $i < $num; $i++) {
            $slicedata = array_slice($slice, $i * 7, 7);
            reset($slicedata);
//            $key1 = substr(key($slicedata),5);
            $key1 = key($slicedata);
            end($slicedata);
//            $key2 = substr(key($slicedata),5);
            $key2 = key($slicedata);
            $new_data[$key2 . '~' . $key1] = $slicedata;
        }
        foreach ($new_data as $k => $v) {
            $res[$k]['new_register_user'] = implode(',', array_filter(explode(',', implode(',', array_filter(array_column($v, 'new_register_user'))))));
            $res[$k]['count_new_register_user'] = array_sum(array_column($v, 'count_new_register_user'));
            $res[$k]['active_user'] = implode(',', array_unique(array_filter(explode(',', implode(',', array_filter(array_column($v, 'active_user')))))));
            $res[$k]['count_active_user'] = count(array_filter(explode(',', $res[$k]['active_user'])));
            $res[$k]['fire_device'] = implode(',', array_unique(array_filter(explode(',', implode(',', array_filter(array_column($v, 'fire_device')))))));
            $res[$k]['count_fire_device'] = count(array_filter(explode(',', $res[$k]['fire_device'])));
            $res[$k]['active_device'] = implode(',', array_unique(array_filter(explode(',', implode(',', array_filter(array_column($v, 'active_device')))))));
            $res[$k]['count_active_device'] = count(array_filter(explode(',', $res[$k]['active_device'])));
            //计算几周后
            for ($ii = 1; $ii <= $num; $ii++) {
                $enddatetime = strtotime(substr($k, -10)) + $ii * 7 * 24 * 3600;
                if ($enddatetime > $week_end_time) {
                    //注册
                    $res[$k]['count_next' . $ii] = false;
                    $res[$k]['next' . $ii] = false;
                    $res[$k]['same' . $ii] = false;
                    //活跃
                    $res[$k]['active_count_next' . $ii] = false;
                    $res[$k]['active_next' . $ii] = false;
                    $res[$k]['active_same' . $ii] = false;
                    //设备
                    $res[$k]['device_count_next' . $ii] = false;
                    $res[$k]['device_next' . $ii] = false;
                    $res[$k]['device_same' . $ii] = false;
                } else {
                    $user_week_retain = $this->user_every_week_retain($data, $v, $ii, $num);
                    //注册
                    $res[$k]['count_next' . $ii] = $user_week_retain['same'];
                    $res[$k]['next' . $ii] = $user_week_retain['samerate'];
                    $res[$k]['same' . $ii] = implode(',', $user_week_retain['samearr']);
                    //活跃
                    $res[$k]['active_count_next' . $ii] = $user_week_retain['ativesame'];
                    $res[$k]['active_next' . $ii] = $user_week_retain['activesamerate'];
                    $res[$k]['active_same' . $ii] = implode(',', $user_week_retain['activesamearr']);
                    //设备
                    $res[$k]['device_count_next' . $ii] = $user_week_retain['devicesame'];
                    $res[$k]['device_next' . $ii] = $user_week_retain['devicesamerate'];
                    $res[$k]['device_same' . $ii] = implode(',', $user_week_retain['devicesamearr']);

                }
            }
            $res[$k]['time'] = $k;
        }
        return $res;
    }

    public function user_every_week_retain($data = [], $new_data = [], $ii = 1, $num = 4)
    {
        //获取几周后 各周数据 计算留存
        for ($i = 1; $i <= $num; $i++) {
            $next_data[$num + 1 - $i] = array_slice($data, 7 * ($i - 1), 7);
        }
        $next_data = $next_data[$ii];
        $res = $this->deal_data($new_data, $next_data);
        return $res;
    }

    //每月
    public function user_month_retain($data = [], $lastendtime = '', $num = 1)
    {
        //本月开始
        $month_start = date('Y-m-01');
        //本周结束日期
        $month_end = date('Y-m-d', strtotime("$month_start +1 month -1 day"));
        $moth_end_time = strtotime($month_end);
        $datecount = periodDate($month_start, $lastendtime);
        $slice = array_slice($data, count($datecount));
        $datakeys = array_keys($data);
        $thismonthdata = array_slice($data, array_search($month_end, $datakeys), count(periodDate($month_start, $month_end)));
        $new_data[$month_start . '~' . $month_end] = $thismonthdata;
        foreach ($slice as $dk => $dv) {
            $slice[$dk]['month'] = substr($slice[$dk]['time'], 0, 7);
        }
        $groupdata = array_group_by($slice, 'month');
        foreach ($groupdata as $gk => $gv) {
            $key1 = $gk . '-01';
            $key2 = date('Y-m-d', strtotime("$key1 +1 month -1 day"));
            $new_data[$key1 . '~' . $key2] = $gv;
        }
        foreach ($new_data as $k => $v) {
            $res[$k]['new_register_user'] = implode(',', array_filter(explode(',', implode(',', array_filter(array_column($v, 'new_register_user'))))));
            $res[$k]['count_new_register_user'] = array_sum(array_column($v, 'count_new_register_user'));
            $res[$k]['active_user'] = implode(',', array_unique(array_filter(explode(',', implode(',', array_filter(array_column($v, 'active_user')))))));
            $res[$k]['count_active_user'] = count(array_filter(explode(',', $res[$k]['active_user'])));
            $res[$k]['fire_device'] = implode(',', array_unique(array_filter(explode(',', implode(',', array_filter(array_column($v, 'fire_device')))))));
            $res[$k]['count_fire_device'] = count(array_filter(explode(',', $res[$k]['fire_device'])));
            $res[$k]['active_device'] = implode(',', array_unique(array_filter(explode(',', implode(',', array_filter(array_column($v, 'active_device')))))));
            $res[$k]['count_active_device'] = count(array_filter(explode(',', $res[$k]['active_device'])));

            //计算几月后
            for ($ii = 1; $ii <= $num; $ii++) {
                $time1 = date('Y-m-01', strtotime(substr($k, -10)) + 24 * 3600 + 1);
                $enddatetime = strtotime(date('Y-m-d', strtotime("$time1 +$ii month -1 day")));
                if ($enddatetime > $moth_end_time) {
                    //注册
                    $res[$k]['count_next' . $ii] = false;
                    $res[$k]['next' . $ii] = false;
                    $res[$k]['same' . $ii] = false;
                    //活跃
                    $res[$k]['active_count_next' . $ii] = false;
                    $res[$k]['active_next' . $ii] = false;
                    $res[$k]['active_same' . $ii] = false;
                    //设备
                    $res[$k]['device_count_next' . $ii] = false;
                    $res[$k]['device_next' . $ii] = false;
                    $res[$k]['device_same' . $ii] = false;
                } else {
                    $user_week_retain = $this->user_every_month_retain($data, $v, $ii, $num);
                    //注册
                    $res[$k]['count_next' . $ii] = $user_week_retain['same'];
                    $res[$k]['next' . $ii] = $user_week_retain['samerate'];
                    $res[$k]['same' . $ii] = implode(',', $user_week_retain['samearr']);
                    //活跃
                    $res[$k]['active_count_next' . $ii] = $user_week_retain['ativesame'];
                    $res[$k]['active_next' . $ii] = $user_week_retain['activesamerate'];
                    $res[$k]['active_same' . $ii] = implode(',', $user_week_retain['activesamearr']);
                    //设备
                    $res[$k]['device_count_next' . $ii] = $user_week_retain['devicesame'];
                    $res[$k]['device_next' . $ii] = $user_week_retain['devicesamerate'];
                    $res[$k]['device_same' . $ii] = implode(',', $user_week_retain['devicesamearr']);
                }
            }
            $res[$k]['time'] = $k;
        }
        return $res;
    }

    public function user_every_month_retain($data = [], $new_data = [], $ii = 1, $num = 4)
    {
        //该周注册用户
        //获取几月后 各月数据 计算留存
        for ($i = 1; $i <= $num; $i++) {
            $thisdate = $new_data[0]['month'] . '-1';
            $firstday = date("Y-m-01", strtotime("+$i month", strtotime($thisdate)));
            $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
            $datacount = count(periodDate($firstday, $lastday));
            $data_keys = array_keys($data);
            $keys_index = array_search($lastday, $data_keys);
            $next_data[$i] = array_slice($data, $keys_index, $datacount);
        }
        $next_data = $next_data[$ii];
        $res = $this->deal_data($new_data, $next_data);
        return $res;
    }

    private function deal_data($new_data, $next_data)
    {
        $new_register_user_arr = array_filter(explode(',', implode(',', array_filter(array_column($new_data, 'new_register_user')))));
        $active_user_arr = array_unique(array_filter(explode(',', implode(',', array_filter(array_column($new_data, 'active_user'))))));
        $fire_device_arr = array_unique(array_filter(explode(',', implode(',', array_filter(array_column($new_data, 'fire_device'))))));
        //某几周后 活跃
        $active_user = array_unique(array_filter(explode(',', implode(',', array_filter(array_column($next_data, 'active_user'))))));
        $device_user = array_filter(explode(',', implode(',', array_filter(array_column($next_data, 'active_device')))));
//        asort($active_user);
        //注册交集
        $res['samearr'] = $samearr = array_unique(array_intersect($new_register_user_arr, $active_user));
        $res['same'] = $same = count($samearr);
        $res['samerate'] = $samerate = empty(count($new_register_user_arr)) ? '0.00%' : (null_to_0($same / count($new_register_user_arr) * 100)) . '%';
        //活跃交集
        $res['activesamearr'] = $activesamearr = array_unique(array_intersect($active_user_arr, $active_user));
        $res['ativesame'] = $ativesame = count($activesamearr);
        $res['activesamerate'] = $activesamerate = empty(count($active_user_arr)) ? '0.00%' : (null_to_0($ativesame / count($active_user_arr) * 100)) . '%';
        //设备活跃
        $res['devicesamearr'] = $devicesamearr = array_unique(array_intersect($fire_device_arr, $device_user));
        $res['devicesame'] = $devicesame = count($devicesamearr);
        $res['devicesamerate'] = $devicesamerate = empty(count($fire_device_arr)) ? '0.00%' : (null_to_0($devicesame / count($fire_device_arr) * 100)) . '%';
        return $res;
    }

    /**
     * 某一日 每个小时详细数据
     * yyh
     */
    public function date_detail($date = '', $promote_id = '', $is_top = 0)  // 当天的时间, 推广员id, 1
    {
        $beginTime = strtotime($date);
        for ($i = 0; $i < 24; $i++) {
            $data[$i + 1] = ['usercount' => 0, 'activecount' => 0, 'paycount' => 0, 'paytotal' => '0.00'];
        }
        if ($is_top == 1) {
            $zi = array_column(get_song_promote_lists($promote_id,2), 'id');
            $zi[] = $promote_id;
            $promote_id = ['in', $zi];
        }
        $betweentime = ['between', [$beginTime, $beginTime + 86399]];
        //新增用户
        $new_user = Db::table('tab_user')->field('id,register_time')->where(['promote_id' => $promote_id])->where(['register_time' => $betweentime])->where('puid',0)->select()->toArray();
        if ($new_user) {
            foreach ($new_user as $key => $vo) {
                $hour = date('H', $vo['register_time']) + 1;
                $data[$hour]['usercount'] = $data[$hour]['usercount'] + 1;
            }
        }
        //活跃用户
        $mUserLoginRecord = new UserLoginRecordModel();
        $login_record = $mUserLoginRecord->field('user_id,min(login_time) as login_time')->where(['login_time' => $betweentime, 'promote_id' => $promote_id])->group('user_id')->where('puid',0)->select()->toArray();
        if ($login_record) {
            foreach ($login_record as $key => $vo) {
                $hour = date('H', $vo['login_time']) + 1;
                $data[$hour]['activecount'] = $data[$hour]['activecount'] + 1;
            }
        }
        //充值数据
        // $spend = Db::table('tab_spend')
        //     ->field('user_id,min(pay_time) as pay_time,sum(pay_amount) as pay_amount')
        //     ->where(['pay_time' => $betweentime, 'promote_id' => $promote_id, 'pay_status' => 1])
        //     ->group('user_id')
        //     ->select()->toArray();

        // 修改 ---------------------------------------------------by wjd----------------START
        // 充值数据
        $spend = Db::table('tab_spend')
            ->field('user_id,pay_time,pay_amount')
            ->where(['pay_time' => $betweentime, 'promote_id' => $promote_id, 'pay_status' => 1])
            ->select()->toArray();
        // 修改 ---------------------------------------------------by wjd----------------END
        if ($spend) {
            foreach ($spend as $key => $vo) {
                $hour = date('H', $vo['pay_time']) + 1;
                $data[$hour]['paycount'] = $data[$hour]['paycount'] + 1; // 原
                $data[$hour]['paytotal'] = $data[$hour]['paytotal'] + $vo['pay_amount'];
            }
        }
        // 新增付费用户数统计---------------START
        // $spend22 = Db::table('tab_spend')
        //     ->field('user_id,min(pay_time) as pay_time')
        //     ->where(['pay_time' => $betweentime, 'promote_id' => $promote_id, 'pay_status' => 1])
        //     ->group('user_id')
        //     ->select()->toArray();

        // if ($spend22) {
        //     foreach ($spend as $key => $vo) {
        //         $hour = date('H', $vo['pay_time']) + 1;
        //         $data[$hour]['paycount'] = $data[$hour]['paycount'] + 1;
        //     }
        // }
        // 新增付费用户数统计---------------END

        return $data;
    }


    /**
     * @函数或方法说明
     * @今日汇总基础数据生日
     * @author: 郭家屯
     * @since: 2020/9/8 16:50
     */
    public function basedata_today()
    {
        $date = date('Y-m-d');
        //获取渠道数据
        $top_promote_data = Db::table('tab_promote')->where(['parent_id' => 0,'promote_level'=>1])->order('id asc')->column('id');
        if (empty($top_promote_data)) {
            $top_promote_data = [];
        }
        array_unshift($top_promote_data, 0);
        //每日汇总
        $event = new Summary();
        $data = $event->new_basedata_today_pid($date);
        foreach ($data as $kk => $vv) {
            foreach ($vv as $k => $v) {
                $redisdata['datareporteverypid_' . $date][$k] = $v;
            }
        }
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        $redis->multi();
        foreach ($redisdata as $key => $value) {
            foreach ($value as $kk => $vv) {
                $redis->hSet($key, $kk, json_encode($vv));
            }
        }
        $redis->exec();
        foreach ($top_promote_data as $k => $v) {
            //每日汇总
            $redistopdata = $topdata = $event->basedata_top_pid($v, $date);
            $topdata = array_values($topdata);
            if (empty($topdata)) {
                continue;
            }
            $redis->multi();
            foreach ($redistopdata as $kk => $vv) {
                $redis->hSet('datareporttoppid_' . $date, $kk, json_encode($vv));
            }
            $redis->exec();
        }
    }
}
