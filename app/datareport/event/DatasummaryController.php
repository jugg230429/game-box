<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 19:13
 */

namespace app\datareport\event;

use cmf\controller\HomeBaseController;
use cmf\org\RedisSDK\RedisController as Redis;
use think\Db;

class DatasummaryController extends HomeBaseController
{
    /**
     * descript 基础数据汇总
     * @param string $date 汇总日期
     * @return array
     */
    public function basedata_every_pid($pid = '', $date = '')
    {
        $todaytime = strtotime(date("Y-m-d"));
        if (empty($date)) {
            $date = date('Y-m-d', $todaytime - 86400);
        }
        if ($todaytime <= strtotime($date)) {
            return ['status' => 0, 'msg' => '该日期不可汇总'];
        }
        $gameids = Db::table('tab_game')
//            ->join(['tab_user_play' => 'up'], ['up.game_id = tab_game.id'])
//            ->where(['promote_id' => $pid])
//            ->group('tab_game.id')
            ->order('tab_game.id asc')
            ->column('tab_game.id');
        $key = 0;
        $gameids[] = 0;
        foreach ($gameids as $k => $v) {
            $key = $pid . '_' . $v;
            // 新增注册账号数 只记录大号
            $map = ['puid'=>0,'register_time' => date_time($date, false), 'fgame_id' => $v, 'promote_id' => ['eq', $pid]];
            $group = null;
            $field = 'GROUP_CONCAT(id) as data_str,fgame_id';
            $new_register_user = $this->new_register_user($map, $group, $field);
            $data[$key]['new_register_user'] = $new_register_user = $new_register_user[0]['data_str'] ?: '';

            // 活跃用户 日活  当日的新增用户+老用户 //注册后登录 //只记录大号
            $map = ['tab_user_day_login.login_time' => date_time($date, false), 'tab_user.puid'=>0,'tab_user.promote_id' => ['eq', $pid], 'user_id' => ['gt', '0'], 'game_id' => ['eq', $v]];
            $group = null;
            $field = 'GROUP_CONCAT(DISTINCT(user_id)) as data_str,game_id';
            $active_user = $this->get_active_num($map, $group, $field);
            $data[$key]['active_user'] = $active_user = $active_user[0]['data_str'] ?: '';

            //周活
            $day7 = ['between', [strtotime($date) - 6 * 24 * 3600, strtotime($date) + 24 * 3600 - 1]];
            $map = ['tab_user_day_login.login_time' => $day7, 'tab_user.puid'=>0,'tab_user.promote_id' => ['eq', $pid], 'user_id' => ['gt', '0'], 'game_id' => ['eq', $v]];
            $group = null;
            $field = 'GROUP_CONCAT(DISTINCT(user_id)) as data_str,game_id';
            $active_user7 = $this->get_active_num($map, $group, $field);
            $data[$key]['active_user7'] = $active_user7 = $active_user7[0]['data_str'] ?: '';

            //月活
            $day30 = ['between', [strtotime($date) - 29 * 24 * 3600, strtotime($date) + 24 * 3600 - 1]];
            $map = ['tab_user_day_login.login_time' => $day30, 'tab_user.puid'=>0,'tab_user.promote_id' => ['eq', $pid], 'user_id' => ['gt', '0'], 'game_id' => ['eq', $v]];
            $group = null;
            $field = 'GROUP_CONCAT(DISTINCT(user_id)) as data_str,game_id';
            $active_user30 = $this->get_active_num($map, $group, $field);
            $data[$key]['active_user30'] = $active_user30 = $active_user30[0]['data_str'] ?: '';

            //付费用户  包含所有付费方式 1绑币，2:平台币,3:支付宝,4:微信5谷歌6苹果支付
            $map = ['pay_time' => date_time($date, false), 'tab_spend.promote_id' => ['eq', $pid], 'pay_status' => 1, 'pay_way' => ['egt', 1], 'game_id' => ['eq', $v]];
            $group = null;
            $field = "GROUP_CONCAT(DISTINCT(user_id)) as data_user_str,GROUP_CONCAT(DISTINCT(pay_order_number)) as data_order_str,sum(pay_amount) as data_pay_str";
            $today_pay_data = $this->get_pay_num($map, $group, $field);
            $data[$key]['pay_user'] = $pay_user = $today_pay_data[0]['data_user_str'] ?: '';
            //订单数
            $data[$key]['total_order'] = $total_order = $today_pay_data[0]['data_order_str'] ?: '';
            //总付费额
            $data[$key]['total_pay'] = $total_pay = null_to_0($today_pay_data[0]['data_pay_str']);

            //新增付费用户
            $map = ['pay_time' => date_time($date, false), 'tab_spend.promote_id' => ['eq', $pid], 'pay_status' => 1, 'pay_way' => ['egt', 1], 'game_id' => ['eq', $v]];
            if (!empty($new_register_user)) {
                $map['user_id'] = ['in', $new_register_user];//新增注册用户id
            } else {
                $map['user_id'] = ['in', [-1]];
            }
            $group = null;
            $field = "GROUP_CONCAT(DISTINCT(user_id)) as data_user_str,GROUP_CONCAT(DISTINCT(pay_order_number)) as data_order_str,sum(pay_amount) as data_pay_str";
            $today_new_pay_data = $this->get_pay_num($map, $group, $field);
            $data[$key]['new_pay_user'] = $new_pay_user = $today_new_pay_data[0]['data_user_str'] ?: '';
            //新增付费订单数
            $data[$key]['new_total_order'] = $new_total_order = $today_new_pay_data[0]['data_order_str'] ?: '';
            //新增付费额
            $data[$key]['new_total_pay'] = $new_total_pay = null_to_0($today_new_pay_data[0]['data_pay_str']);

            //新增激活设备
            $map = ['create_time' => date_time($date, false), 'promote_id' => $pid, 'game_id' => $v, 'first_device' => ['eq', 1]];
            $group = null;
            $field = 'GROUP_CONCAT(DISTINCT(equipment_num)) as data_str,game_id';
            $fire_device_data = $this->get_register_device_num($map, $group, $field);
            $data[$key]['fire_device'] = $fire_device = $fire_device_data[0]['data_str'] ?: '';
            //设备总登录 激活设备+老设备登录
            $map = ['create_time' => date_time($date, false), 'promote_id' => $pid, 'game_id' => $v, 'first_device' => ['in', '1,2']];
            $group = null;
            $field = 'GROUP_CONCAT(DISTINCT(equipment_num)) as data_str,game_id';
            $active_device_data = $this->get_register_device_num($map, $group, $field);
            $data[$key]['active_device'] = $active_device = $active_device_data[0]['data_str'] ?: '';

            $data[$key]['time'] = $date;
            $data[$key]['promote_id'] = $pid;
            $data[$key]['game_id'] = $v;
            $data[$key]['create_time'] = time();
            $data[$key]['update_time'] = time();
            //不记录无数据 数据
            if (empty($new_register_user) && empty($active_user) && empty($active_user7) && empty($active_user30) && empty($pay_user) && empty($total_order) && empty($new_pay_user) && empty($new_total_order) && empty($fire_device) && empty($active_device) && $total_pay == 0 && $new_total_pay == 0) {
                unset($data[$key]);
            }
        }
        return empty($data) ? [] : $data;
    }

    //上级渠道记录
    public function basedata_top_pid($top_id, $date)
    {
        if ($top_id == 0) {
            $ids[] = $top_id;
        } else {
            $ids = Db::table('tab_promote')->where(['id|parent_id|top_promote_id' => $top_id])->column('id');
        }
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        $new_game_arr = [];
        $allparams = [];
        foreach ($ids as $k => $v) {
            $key = 'datareporteverypid_' . $date;//某日期
            $keys = $redis->hKeys($key);
            foreach ($keys as $kk => $vv) {
                if (strstr($vv, '_', true) != $v) {
                    unset($keys[$kk]);
                }
            }
            $keys = implode(',', $keys);
            $field = empty($keys) ? '' : $keys;
            $gamedata = [];
            $gamedata = $redis->hMget($key, $field);
            if (!empty($gamedata)) {
                foreach ($gamedata as $gk => $gv) {
                    $arr = json_decode($gv, true);
                    $allparams = $arr ? array_merge($allparams,array_keys($arr)) : $allparams;
                    $new_game_arr[$top_id . '_' . $arr['game_id']][] = $arr;
                }
            }
        }
        $allparams = array_unique($allparams);
        foreach ($allparams as $kk => $vv) {
            foreach ($new_game_arr as $k => $v) {
                if ($vv == 'new_register_user' || $vv == 'total_pay' || $vv == 'new_total_pay') {
                    $unique = 0;
                } else {
                    $unique = 1;
                }

                $data[$k][$vv] = str_unique(trim(array_key_value_link($vv, $v), ','),$unique);//每个游戏的数据合并
                if ($vv == 'create_time' || $vv == 'update_time') {
                    $data[$k][$vv] = time();
                }
                if ($vv == 'promote_id') {
                    $data[$k][$vv] = $top_id;
                }
                if ($vv == 'total_pay' || $vv == 'new_total_pay') {
                    $data[$k][$vv] = str_arr_sum($data[$k][$vv]);
                }
            }
        }
        //上级渠道 激活设备去重

        foreach ($data as $k => $v) {
            $nowdevice = $v['fire_device'];
            if (empty($nowdevice)) {
                continue;
            } else {
                $devicemap['equipment_num'] = ['in', $nowdevice];
                $devicemap['create_time'] = ['lt', strtotime($date)];
                $devicemap['promote_id'] = ['in', $ids];
                $olddevice = Db::table('tab_equipment_game')->where($devicemap)->group('equipment_num')->column('equipment_num');
                $nowdevicearr = explode(',', $nowdevice);
                $data[$k]['fire_device'] = implode(',', array_diff($nowdevicearr, $olddevice));//去重 今天之前上级及子渠道 已算过激活的设备不计算在今天
            }
        }

        return $data;
    }

    //获取条件范围内的新增注册账号
    public function new_register_user($map = [], $group = null, $field = 'id')
    {
        $data = Db::table('tab_user')->field($field)->where($map)->group($group)->select()->toArray();
        if (empty($data)) {
            return [];
        } else {
            return $data;
        }
    }

    //获取条件范围内活跃人数
    public function get_active_num($map = [], $group = null, $field = 'user_id')
    {
        $data = Db::table('tab_user_day_login')
            ->field($field)
            ->join(['tab_user'], 'tab_user.id = tab_user_day_login.user_id')
            ->where($map)
            ->group($group)
            ->select()->toArray();

        if (empty($data)) {
            return [];
        } else {

            return $data;
        }
    }

    //条件范围内的充值人数
    public function get_pay_num($map = [], $group = null, $field = 'tab_spend.id,user_id,pay_amount')
    {
        $data = Db::table('tab_spend')
            ->field($field)
            ->join(['tab_user'], 'tab_user.id = tab_spend.user_id')
            ->where($map)
            ->group($group)
            ->select()->toArray();
        if (empty($data)) {
            return [];
        } else {
            return $data;
        }
    }

    //获取条件范围内的新增激活设备数
    public function get_register_device_num($map = [], $group = null, $field = 'equipment_num')
    {
        $data = Db::table('tab_equipment_game')->field($field)->where($map)->group($group)->select()->toArray();
        if (empty($data)) {
            return [];
        } else {
            return $data;
        }
    }

    /**
     * descript 基础数据汇总
     * @param string $date 汇总当日日期
     * @return array
     */
    public function basedata_today_pid($pid = '',$date='')
    {
        $gameids = Db::table('tab_game')
//            ->join(['tab_user_play' => 'up'], ['up.game_id = tab_game.id'])
//            ->where(['promote_id' => $pid])
//            ->group('tab_game.id')
                ->order('tab_game.id asc')
                ->column('tab_game.id');
        $key = 0;
        $gameids[] = 0;
        foreach ($gameids as $k => $v) {
            $key = $pid . '_' . $v;
            // 新增注册账号数 只记录大号
            $map = ['puid'=>0,'register_time' => date_time($date, false), 'fgame_id' => $v, 'promote_id' => ['eq', $pid]];
            $group = null;
            $field = 'GROUP_CONCAT(id) as data_str,fgame_id';
            $new_register_user = $this->new_register_user($map, $group, $field);
            $data[$key]['new_register_user'] = $new_register_user = $new_register_user[0]['data_str'] ?: '';

            // 活跃用户 日活  当日的新增用户+老用户 //注册后登录 //只记录大号
            $map = ['tab_user_day_login.login_time' => date_time($date, false), 'tab_user.puid'=>0,'tab_user.promote_id' => ['eq', $pid], 'user_id' => ['gt', '0'], 'game_id' => ['eq', $v]];
            $group = null;
            $field = 'GROUP_CONCAT(DISTINCT(user_id)) as data_str,game_id';
            $active_user = $this->get_active_num($map, $group, $field);
            $data[$key]['active_user'] = $active_user = $active_user[0]['data_str'] ?: '';

            //周活
            $day7 = ['between', [strtotime($date) - 6 * 24 * 3600, strtotime($date) + 24 * 3600 - 1]];
            $map = ['tab_user_day_login.login_time' => $day7, 'tab_user.puid'=>0,'tab_user.promote_id' => ['eq', $pid], 'user_id' => ['gt', '0'], 'game_id' => ['eq', $v]];
            $group = null;
            $field = 'GROUP_CONCAT(DISTINCT(user_id)) as data_str,game_id';
            $active_user7 = $this->get_active_num($map, $group, $field);
            $data[$key]['active_user7'] = $active_user7 = $active_user7[0]['data_str'] ?: '';

            //月活
            $day30 = ['between', [strtotime($date) - 29 * 24 * 3600, strtotime($date) + 24 * 3600 - 1]];
            $map = ['tab_user_day_login.login_time' => $day30, 'tab_user.puid'=>0,'tab_user.promote_id' => ['eq', $pid], 'user_id' => ['gt', '0'], 'game_id' => ['eq', $v]];
            $group = null;
            $field = 'GROUP_CONCAT(DISTINCT(user_id)) as data_str,game_id';
            $active_user30 = $this->get_active_num($map, $group, $field);
            $data[$key]['active_user30'] = $active_user30 = $active_user30[0]['data_str'] ?: '';

            //付费用户  包含所有付费方式 1绑币，2:平台币,3:支付宝,4:微信5谷歌6苹果支付
            $map = ['pay_time' => date_time($date, false), 'tab_spend.promote_id' => ['eq', $pid], 'pay_status' => 1, 'pay_way' => ['egt', 1], 'game_id' => ['eq', $v]];
            $group = null;
            $field = "GROUP_CONCAT(DISTINCT(user_id)) as data_user_str,GROUP_CONCAT(DISTINCT(pay_order_number)) as data_order_str,sum(pay_amount) as data_pay_str";
            $today_pay_data = $this->get_pay_num($map, $group, $field);
            $data[$key]['pay_user'] = $pay_user = $today_pay_data[0]['data_user_str'] ?: '';
            //订单数
            $data[$key]['total_order'] = $total_order = $today_pay_data[0]['data_order_str'] ?: '';
            //总付费额
            $data[$key]['total_pay'] = $total_pay = null_to_0($today_pay_data[0]['data_pay_str']);

            //新增付费用户
            $map = ['pay_time' => date_time($date, false), 'tab_spend.promote_id' => ['eq', $pid], 'pay_status' => 1, 'pay_way' => ['egt', 1], 'game_id' => ['eq', $v]];
            if (!empty($new_register_user)) {
                $map['user_id'] = ['in', $new_register_user];//新增注册用户id
            } else {
                $map['user_id'] = ['in', [-1]];
            }
            $group = null;
            $field = "GROUP_CONCAT(DISTINCT(user_id)) as data_user_str,GROUP_CONCAT(DISTINCT(pay_order_number)) as data_order_str,sum(pay_amount) as data_pay_str";
            $today_new_pay_data = $this->get_pay_num($map, $group, $field);
            $data[$key]['new_pay_user'] = $new_pay_user = $today_new_pay_data[0]['data_user_str'] ?: '';
            //新增付费订单数
            $data[$key]['new_total_order'] = $new_total_order = $today_new_pay_data[0]['data_order_str'] ?: '';
            //新增付费额
            $data[$key]['new_total_pay'] = $new_total_pay = null_to_0($today_new_pay_data[0]['data_pay_str']);

            //新增激活设备
            $map = ['create_time' => date_time($date, false), 'promote_id' => $pid, 'game_id' => $v, 'first_device' => ['eq', 1]];
            $group = null;
            $field = 'GROUP_CONCAT(DISTINCT(equipment_num)) as data_str,game_id';
            $fire_device_data = $this->get_register_device_num($map, $group, $field);
            $data[$key]['fire_device'] = $fire_device = $fire_device_data[0]['data_str'] ?: '';
            //设备总登录 激活设备+老设备登录
            $map = ['create_time' => date_time($date, false), 'promote_id' => $pid, 'game_id' => $v, 'first_device' => ['in', '1,2']];
            $group = null;
            $field = 'GROUP_CONCAT(DISTINCT(equipment_num)) as data_str,game_id';
            $active_device_data = $this->get_register_device_num($map, $group, $field);
            $data[$key]['active_device'] = $active_device = $active_device_data[0]['data_str'] ?: '';

            $data[$key]['time'] = $date;
            $data[$key]['promote_id'] = $pid;
            $data[$key]['game_id'] = $v;
            $data[$key]['create_time'] = time();
            $data[$key]['update_time'] = time();
            //不记录无数据 数据
            if (empty($new_register_user) && empty($active_user) && empty($active_user7) && empty($active_user30) && empty($pay_user) && empty($total_order) && empty($new_pay_user) && empty($new_total_order) && empty($fire_device) && empty($active_device) && $total_pay == 0 && $new_total_pay == 0) {
                unset($data[$key]);
            }
        }
        return empty($data) ? [] : $data;
    }


    /**
     * descript 基础数据汇总
     * @param string $date 汇总当日日期
     * @return array
     */
    public function new_basedata_today_pid($date='')
    {
        // 新增注册账号数 只记录大号
        $map = ['puid'=>0,'register_time' => date_time($date, false)];
        $group = 'promote_id,fgame_id';
        $field = 'GROUP_CONCAT(id) as data_str,fgame_id,promote_id';
        $new_register_user = $this->new_register_user($map, $group, $field);
        foreach ($new_register_user as $key=>$v){
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['fgame_id']]['new_register_user'] = $v['data_str']?:'';
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['fgame_id']]['promote_id'] = $v['promote_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['fgame_id']]['game_id'] = $v['fgame_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['fgame_id']]['time'] = $date;
        }

        // 活跃用户 日活  当日的新增用户+老用户 //注册后登录 //只记录大号
        $map = ['tab_user_day_login.login_time' => date_time($date, false), 'tab_user.puid'=>0, 'user_id' => ['gt', '0'],];
        $group = 'tab_user.promote_id,game_id';
        $field = 'GROUP_CONCAT(DISTINCT(user_id)) as data_str,game_id,tab_user.promote_id';
        $active_user = $this->get_active_num($map, $group, $field);
        foreach ($active_user as $key=>$v){
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['active_user'] = $v['data_str']?:'';
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['promote_id'] = $v['promote_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['game_id'] = $v['game_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['time'] = $date;
        }

        //周活
        $day7 = ['between', [strtotime($date) - 6 * 24 * 3600, strtotime($date) + 24 * 3600 - 1]];
        $map = ['tab_user_day_login.login_time' => $day7, 'tab_user.puid'=>0, 'user_id' => ['gt', '0']];
        $group = 'tab_user.promote_id,game_id';
        $field = 'GROUP_CONCAT(DISTINCT(user_id)) as data_str,game_id,tab_user.promote_id';
        $active_user7 = $this->get_active_num($map, $group, $field);
        foreach ($active_user7 as $key=>$v){
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['active_user7'] = $v['data_str']?:'';
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['promote_id'] = $v['promote_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['game_id'] = $v['game_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['time'] = $date;
        }

        //月活
        $day30 = ['between', [strtotime($date) - 29 * 24 * 3600, strtotime($date) + 24 * 3600 - 1]];
        $map = ['tab_user_day_login.login_time' => $day30, 'tab_user.puid'=>0, 'user_id' => ['gt', '0']];
        $group = 'tab_user.promote_id,game_id,tab_user.promote_id';
        $field = 'GROUP_CONCAT(DISTINCT(user_id)) as data_str,game_id,tab_user.promote_id';
        $active_user30 = $this->get_active_num($map, $group, $field);
        foreach ($active_user30 as $key=>$v){
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['active_user30'] = $v['data_str']?:'';
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['promote_id'] = $v['promote_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['game_id'] = $v['game_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['time'] = $date;
        }

        //付费用户  包含所有付费方式 1绑币，2:平台币,3:支付宝,4:微信5谷歌6苹果支付
        $map = ['pay_time' => date_time($date, false), 'pay_status' => 1, 'pay_way' => ['egt', 1]];
        $group = 'tab_spend.promote_id,game_id';
        $field = "GROUP_CONCAT(DISTINCT(user_id)) as data_user_str,GROUP_CONCAT(DISTINCT(pay_order_number)) as data_order_str,sum(pay_amount) as data_pay_str,tab_spend.promote_id,game_id";
        $today_pay_data = $this->get_pay_num($map, $group, $field);
        foreach ($today_pay_data as $key=>$v){
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['pay_user'] = $v['data_user_str']?:'';
            //订单数
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['total_order'] = $v['data_order_str']?:'';
            //总付费额
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['total_pay'] = $v['data_pay_str']?:'';
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['promote_id'] = $v['promote_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['game_id'] = $v['game_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['time'] = $date;
        }

        //新增付费用户
        $map = ['pay_time' => date_time($date, false), 'pay_status' => 1, 'pay_way' => ['egt', 1]];
        if (!empty($new_register_user)) {
            $new_register_user_str = explode(',',implode(',',array_column($new_register_user,'data_str')));
            $map['user_id'] = ['in', $new_register_user_str];//新增注册用户id
        } else {
            $map['user_id'] = ['in', [-1]];
        }
        $group = 'tab_spend.promote_id,game_id';
        $field = "GROUP_CONCAT(DISTINCT(user_id)) as data_user_str,GROUP_CONCAT(DISTINCT(pay_order_number)) as data_order_str,sum(pay_amount) as data_pay_str,tab_spend.promote_id,game_id";
        $today_new_pay_data = $this->get_pay_num($map, $group, $field);
        foreach ($today_new_pay_data as $key=>$v){
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['new_pay_user'] = $v['data_user_str']?:'';
            //新增付费订单数
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['new_total_order'] = $v['data_order_str']?:'';
            //新增付费额
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['new_total_pay'] = $v['data_pay_str']?:'';
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['promote_id'] = $v['promote_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['game_id'] = $v['game_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['time'] = $date;
        }

        //新增激活设备
        $map = ['create_time' => date_time($date, false), 'first_device' => ['eq', 1]];
        $group = 'promote_id,game_id';
        $field = 'GROUP_CONCAT(DISTINCT(equipment_num)) as data_str,game_id,promote_id';
        $fire_device_data = $this->get_register_device_num($map, $group, $field);
        foreach ($fire_device_data as $key=>$v){
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['fire_device'] = $v['data_str']?:'';
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['promote_id'] = $v['promote_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['game_id'] = $v['game_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['time'] = $date;
        }

        //设备总登录 激活设备+老设备登录
        $map = ['create_time' => date_time($date, false), 'first_device' => ['in', '1,2']];
        $group = 'promote_id,game_id';
        $field = 'GROUP_CONCAT(DISTINCT(equipment_num)) as data_str,game_id,promote_id';
        $active_device_data = $this->get_register_device_num($map, $group, $field);
        foreach ($active_device_data as $key=>$v){
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['active_device'] = $v['data_str']?:'';
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['promote_id'] = $v['promote_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['game_id'] = $v['game_id'];
            $data[$v['promote_id']][$v['promote_id'].'_'.$v['game_id']]['time'] = $date;
        }
        return empty($data) ? [] : $data;
    }
}
