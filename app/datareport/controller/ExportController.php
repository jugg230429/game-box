<?php
/**
 * Created by gjt.
 * User: Administrator
 * Date: 2019/1/23
 * Time: 15:34
 */

namespace app\datareport\controller;

use app\common\controller\BaseController as Base;
use app\datareport\event\GameController as Game;
use app\datareport\event\PromoteController as Promote;
use app\issue\logic\StatLogic;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatabaseController as Database;
use app\site\model\EquipmentGameModel;
use app\site\model\EquipmentLoginModel;
use think\Db;

class ExportController extends Base
{
    function expUser()
    {
        $id = $this->request->param('id', 0, 'intval');
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();
        switch ($id) {
            case 1://用户数据
                $xlsCell = array(
                    array('time', '日期'),
                    array('promote_id', "渠道"),
                    array('new_register_user', "新增用户"),
                    array('active_user', "活跃用户"),
                    array('pay_user', "付费用户"),
                    array('new_pay_user', "新增付费用户"),
                    array('new_total_pay', '新增付费额'),
                    array('total_order', "订单数"),
                    array('total_pay', '总付费额'),
                    array('rate', "总付费率"),
                    array('arpu', "ARPU"),
                    array('arppu', "ARPPU"),
                    array('fire_device', "激活设备"),
                );
                $request = $this->request->param();
                //时间
                if (empty($request['rangepickdate'])) {
                    $date = date("Y-m-d", strtotime("-6 day")) . '至' . date("Y-m-d");
                } else {
                    $date = $request['rangepickdate'];
                }
                $dateexp = explode('至', $date);
                $starttime = $dateexp[0];
                $endtime = $dateexp[1];
                $promote_id = $this->request->param('promote_id', '');
                $game_id = $this->request->param('game_id', '');
                //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
                $game_id = get_admin_view_game_ids(session('ADMIN_ID'),$game_id);
                //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
                $databaseevent = new Database();
                $array_keys = ['time', 'new_register_user', 'active_user', 'pay_user', 'total_order', 'total_pay', 'new_pay_user', 'new_total_pay', 'fire_device'];
                $new_data = $databaseevent->basedata($starttime, $endtime, $promote_id, $game_id, $array_keys);
                //排序
                krsort($new_data);
                //列表计算
                foreach ($new_data as $k => $v) {
                    $new_data[$k]['rate'] = empty($v['new_register_user']) ? '0.00%' : (empty($v['pay_user']) ? '0.00%' : null_to_0(count(explode(',', $v['pay_user'])) / count(explode(',', $v['new_register_user'])) * 100) . '%');
                    $new_data[$k]['arpu'] = empty($v['active_user']) ? '0.00' : null_to_0($v['total_pay'] / count(explode(',', $v['active_user'])));
                    $new_data[$k]['arppu'] = empty($v['pay_user']) ? '0.00' : null_to_0($v['total_pay'] / count(explode(',', $v['pay_user'])));
                }
                //排序
                $new_data = parent::array_order($new_data, $request['sort_type'], $request['sort']);
                if (!empty($new_data)) {
                    //汇总
                    $total_data = [];
                    $totalrate = $request['new_register_user'] == 0 ? '0.00%' : null_to_0($request['pay_user'] / $request['new_register_user'] * 100) . '%';
                    $totalarpu = $request['active_user'] == 0 ? '0.00' : null_to_0($request['total_pay'] / $request['active_user']);
                    $totalarppu = $request['pay_user'] == 0 ? '0.00' : null_to_0($request['total_pay'] / $request['pay_user']);
                    $xlsData[0] = ['time' => '汇总:', 'promote_id' => '--', 'new_register_user' => $request['new_register_user'], 'active_user' => $request['active_user'], 'pay_user' => $request['pay_user'],
                        'new_pay_user' => $request['new_pay_user'], 'new_total_pay' => $request['new_total_pay'], 'total_order' => $request['total_order'], 'total_pay' => $request['total_pay'], 'rate' => $totalrate,
                        'arpu' => $totalarpu, 'arppu' => $totalarppu, 'fire_device' => $request['fire_device']];
                }
                $i = 1;
                foreach ($new_data as $key => $vo) {
                    $xlsData[$i]['time'] = $vo['time'];
                    $xlsData[$i]['promote_id'] = $request['promote_id'] == 0 ? '全部' : get_promote_name($request['promote_id']);
                    $xlsData[$i]['new_register_user'] = arr_count($vo['new_register_user']);
                    $xlsData[$i]['active_user'] = arr_count($vo['active_user']);
                    $xlsData[$i]['pay_user'] = arr_count($vo['pay_user']);
                    $xlsData[$i]['total_order'] = arr_count($vo['total_order']);
                    $xlsData[$i]['total_pay'] = $vo['total_pay'];
                    $xlsData[$i]['new_pay_user'] = arr_count($vo['new_pay_user']);
                    $xlsData[$i]['new_total_order'] = arr_count($vo['new_total_order']);
                    $xlsData[$i]['new_total_pay'] = $vo['new_total_pay'];
                    $xlsData[$i]['fire_device'] = arr_count($vo['fire_device']);
                    $xlsData[$i]['rate'] = $vo['rate'];
                    $xlsData[$i]['arpu'] = $vo['arpu'];
                    $xlsData[$i]['arppu'] = $vo['arppu'];
                    $i++;
                }
                write_action_log("导出日报数据");
                break;
            case 2://游戏数据
                $xlsCell = array(
                    array('game_id', '游戏'),
                    array('promote_id', "渠道"),
                    array('total_pay', "付费金额"),
                    array('pay_user', "付费用户"),
                    array('new_register_user', "新增用户"),
                    array('rate', "付费率"),
                );
                $request = $this->request->param();
                //时间判断
                if (empty($request['rangepickdate'])) {
                    $date = date("Y-m-d", strtotime("-6 day")) . '至' . date("Y-m-d");
                } else {
                    $date = $request['rangepickdate'];
                }
                $dateexp = explode('至', $date);
                $starttime = $dateexp[0];
                $endtime = $dateexp[1];
                //实例化redis
                $promote_id = $this->request->param('promote_id', '');
                $game_id = $this->request->param('game_id', '');
                //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
                $game_id = get_admin_view_game_ids(session('ADMIN_ID'),$game_id);
                //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
                $gameevent = new Game();
                $new_data = $gameevent->game_base($starttime, $endtime, $promote_id, $game_id);
                //排序
                $new_data = parent::array_order($new_data, $request['sort_type'], $request['sort']);
                if (!empty($new_data)) {
                    //汇总
                    $total_data = [];
                    $totalrate = $request['new_register_user'] == 0 ? '0.00%' : null_to_0($request['pay_user'] / $request['new_register_user'] * 100) . '%';
                    $xlsData[0] = ['game_id' => '--', 'promote_id' => '--', 'total_pay' => $request['total_pay'], 'pay_user' => $request['pay_user'], 'new_register_user' => $request['new_register_user'],
                        'rate' => $totalrate];
                }
                $i = 1;
                foreach ($new_data as $key => $vo) {
                    $xlsData[$i]['game_id'] = get_game_name($vo['game_id']);
                    $xlsData[$i]['promote_id'] = $request['promote_id'] == 0 ? '全部' : get_promote_name($request['promote_id']);
                    $xlsData[$i]['total_pay'] = $vo['total_pay'];
                    $xlsData[$i]['pay_user'] = arr_count($vo['pay_user']);
                    $xlsData[$i]['new_register_user'] = arr_count($vo['new_register_user']);
                    $xlsData[$i]['rate'] = $vo['rate'];
                    $i++;
                }
                write_action_log("导出游戏数据");
                break;
            case 3://渠道数据
                $xlsCell = array(
                    array('promote_id', '渠道ID'),
                    array('promote_account', "渠道"),
                    array('new_register_user', "新增用户"),
                    array('active_user', "活跃用户"),
                    array('pay_user', "付费用户"),
                    array('new_pay_user', "新增付费用户"),
                    array('total_pay', "总付费额"),
                    array('rate', "付费率"),
                );
                $request = $this->request->param();
                //时间
                if (empty($request['rangepickdate'])) {
                    $date = date("Y-m-d", strtotime("-6 day")) . '至' . date("Y-m-d");
                } else {
                    $date = $request['rangepickdate'];
                }
                $dateexp = explode('至', $date);
                $starttime = $dateexp[0];
                $endtime = $dateexp[1];
                $promote_id = $this->request->param('promote_id', '');
                $game_id = $this->request->param('game_id', '');
                $promoteevent = new Promote();
                $new_data = $promoteevent->promote_base($starttime, $endtime, $promote_id, $game_id);
                //排序
                $new_data = parent::array_order($new_data, $request['sort_type'], $request['sort']);
                if (!empty($new_data)) {
                    //汇总
                    $total_data = [];
                    $totalrate = $request['new_register_user'] == 0 ? '0.00%' : null_to_0($request['pay_user'] / $request['new_register_user'] * 100) . '%';
                    $xlsData[0] = ['promote_id' => '--', 'promote_account' => '--', 'new_register_user' => $request['new_register_user'], 'active_user' => $request['active_user'], 'pay_user' => $request['pay_user'], 'new_pay_user' => $request['new_pay_user'], 'total_pay' => $request['total_pay'],
                        'rate' => $totalrate];
                }
                $i = 1;
                foreach ($new_data as $key => $vo) {
                    $xlsData[$i]['promote_id'] = $vo['promote_id'];
                    $xlsData[$i]['promote_account'] = $vo['promote_account'];
                    $xlsData[$i]['new_register_user'] = arr_count($vo['new_register_user']);
                    $xlsData[$i]['active_user'] = arr_count($vo['active_user']);
                    $xlsData[$i]['pay_user'] = arr_count($vo['pay_user']);
                    $xlsData[$i]['new_pay_user'] = arr_count($vo['new_pay_user']);
                    $xlsData[$i]['total_pay'] = $vo['total_pay'];
                    $xlsData[$i]['rate'] = $vo['rate'];
                    $i++;
                }
                write_action_log("导出渠道数据报表");
                break;
            case 4:
                $xlsCell = array(
                    array('time', '起始日期'),
                    array('game_id', "来源游戏"),
                    array('promote_id', "渠道"),
                    array('count_new_register_user', "新增用户"),
                    array('count_old_user', "老用户"),
                    array('count_active_user', "DAU"),
                    array('count_active_user7', "WAU"),
                    array('count_active_user30', "MAU"),
                );
                $request = $this->request->param();
                //时间
                if (empty($request['rangepickdate'])) {
                    $date = date("Y-m-d", strtotime("-6 day")) . '至' . date("Y-m-d");
                } else {
                    $date = $request['rangepickdate'];
                }
                $dateexp = explode('至', $date);
                $starttime = $dateexp[0];
                $endtime = $dateexp[1];
                $promote_id = $this->request->param('promote_id', '');
                $game_id = $this->request->param('game_id', '');
                //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
                $game_id = get_admin_view_game_ids(session('ADMIN_ID'),$game_id);
                //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
                $databaseevent = new Database();
                $data = $new_data = $databaseevent->basedata($starttime, $endtime, $promote_id, $game_id, ['new_register_user', 'active_user', 'active_user7', 'active_user30']);
                //图表
                ksort($data);
                if (!empty($new_data)) {
                    $total_data = json_decode($request['extend_data'], true);
                    //汇总
                    $xlsData[0] = ['time' => '汇总', 'game_id' => '--', 'promote_id' => '--', 'count_new_register_user' => $request['new_register_user'], 'count_old_user' => $request['active_user'] - $request['new_register_user'], 'count_active_user' => $request['active_user'], 'count_active_user7' => $request['active_user7'],
                        'count_active_user30' => $request['active_user30']];
                }
                $i = 1;
                foreach ($new_data as $key => $vo) {
                    $xlsData[$i]['time'] = $vo['time'];
                    $xlsData[$i]['game_id'] = $request['game_id'] == 0 ? '全部' : get_game_name($request['game_id']);
                    $xlsData[$i]['promote_id'] = $request['promote_id'] == 0 ? '全部' : get_promote_name($request['promote_id']);
                    $xlsData[$i]['count_new_register_user'] = $vo['count_new_register_user'];
                    $xlsData[$i]['count_old_user'] = $vo['count_active_user'] - $vo['count_new_register_user'];
                    $xlsData[$i]['count_active_user'] = $vo['count_active_user'];
                    $xlsData[$i]['count_active_user7'] = $vo['count_active_user7'];
                    $xlsData[$i]['count_active_user30'] = $vo['count_active_user30'];
                    $i++;
                }
                write_action_log("导出用户分析数据");
                break;
            case 5:
                $xlsCell = array(
                    array('time', '时间'),
                    array('count_new_register_user', "新增用户"),
                );
                $request = $this->request->param();
                $action = $request['action'];
                $keys = session(session('tokentime') . $action . '_assginkey');
                if (empty($keys) || !is_array(json_decode($keys, true))) {
                    return false;
                }
                foreach (json_decode($keys, true) as $kk => $kv) {
                    if ($kk > 0) {
                        $xlsCell[] = ['param' . $kk, $kv];
                    }
                }
                $new_data = json_decode(session(session('tokentime') . $action . '_nextdata'), true);
                $new_data = array_reverse($new_data);
                $i = 0;
                foreach ($new_data as $key => $vo) {
                    $xlsData[$i]['time'] = $vo['time'];
                    $xlsData[$i]['count_new_register_user'] = $vo['count_new_register_user'];
                    foreach (json_decode($keys, true) as $kk => $kv) {
                        if ($kk > 0 && $vo['count_next' . $kk] !== false) {
                            $xlsData[$i]['param' . $kk] = $vo['count_next' . $kk];
                        }
                    }
                    $i++;
                }
                write_action_log("导出新增用户留存数据");
                break;
            case 6:
                $xlsCell = array(
                    array('time', '时间'),
                    array('count_active_user', "活跃用户"),
                );
                $request = $this->request->param();
                $action = $request['action'];
                $keys = session(session('tokentime') . $action . '_assginkey');
                if (empty($keys) || !is_array(json_decode($keys, true))) {
                    return false;
                }
                foreach (json_decode($keys, true) as $kk => $kv) {
                    if ($kk > 0) {
                        $xlsCell[] = ['param' . $kk, $kv];
                    }
                }
                $new_data = json_decode(session(session('tokentime') . $action . '_nextdata'), true);
                $new_data = array_reverse($new_data);
                $i = 0;
                foreach ($new_data as $key => $vo) {
                    $xlsData[$i]['time'] = $vo['time'];
                    $xlsData[$i]['count_active_user'] = $vo['count_active_user'];
                    foreach (json_decode($keys, true) as $kk => $kv) {
                        if ($kk > 0 && $vo['count_next' . $kk] !== false) {
                            $xlsData[$i]['param' . $kk] = $vo['active_count_next' . $kk];
                        }
                    }
                    $i++;
                }
                write_action_log("导出活跃用户留存数据");
                break;
            case 7:
                $xlsCell = array(
                    array('time', '时间'),
                    array('count_fire_device', "激活设备"),
                );
                $request = $this->request->param();
                $action = $request['action'];
                $keys = session(session('tokentime') . $action . '_assginkey');
                if (empty($keys) || !is_array(json_decode($keys, true))) {
                    return false;
                }
                foreach (json_decode($keys, true) as $kk => $kv) {
                    if ($kk > 0) {
                        $xlsCell[] = ['param' . $kk, $kv];
                    }
                }
                $new_data = json_decode(session(session('tokentime') . $action . '_nextdata'), true);

                $new_data = array_reverse($new_data);
                $i = 0;
                foreach ($new_data as $key => $vo) {
                    $xlsData[$i]['time'] = $vo['time'];
                    $xlsData[$i]['count_fire_device'] = $vo['count_fire_device'];
                    foreach (json_decode($keys, true) as $kk => $kv) {
                        if ($kk > 0 && $vo['count_next' . $kk] !== false) {
                            $xlsData[$i]['param' . $kk] = $vo['device_count_next' . $kk];
                        }
                    }
                    $i++;
                }
                write_action_log("导出激活设备留存数据");
                break;
            case 8:
                $xlsCell = array(
                    array('user_account', '账号'),
                    array('user_id', "用户ID"),
                    array('create_time', "首次访问时间"),
                    array('play_time', "最后一次访问时间"),
                    array('login_count30', "近30天访问次数"),
                    array('online_time30', "近30天访问时长"),
                );
                $request = $this->request->param();
                $user = new \app\member\model\UserPlayModel();
                $xlsData = $user->user_login_retain($request['data'], $request['game_id'], $request['date']);
                $i = 0;
                foreach ($xlsData as $key => $vo) {
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
                    $xlsData[$key]['play_time'] = date('Y-m-d H:i:s', $vo['play_time']);
                }
                break;
            case 9:
                $xlsCell = array(
                        array('equipment_num', '设备码'),
                        array('start_time', "首次访问时间"),
                        array('last_time', "最后一次访问时间"),
                        array('login_count30', "近30天访问次数"),
                        array('online_time30', "近30天访问时长"),
                );
                $request = $this->request->param();
                $user = new \app\member\model\EquipmentGameModel();
                $xlsData = $user->equipment_game_retain($request['data'], $request['game_id'], $request['date']);
                foreach ($xlsData as $key => $vo) {
                    $xlsData[$key]['start_time'] = date('Y-m-d H:i:s', $vo['start_time']);
                    $xlsData[$key]['last_time'] = date('Y-m-d H:i:s', $vo['last_time']);
                }
                break;
            case 10://导出数据
                $request = $this->request->param();
                $num = 1;
                $key = $request['key'];
                if($key=='model') {
                    $xlsCell = array(
                            array('device_name',$request['name']),
                            array('sdk_version','系统版本'),
                            array('count','数量'),
                    );
                } else {
                    $xlsCell = array(
                            array('time','日期'),
                            array('sdk_version','系统版本'),
                            array('count',$request['name']),
                    );
                }
                if (is_file(dirname(__FILE__).'/device_data_foldline.txt')) {
                    $filetxt = file_get_contents(dirname(__FILE__).'/device_data_foldline.txt');
                    $data = json_decode($filetxt,true);
                    $xlsData = $data[$key];
                } else {
                    $start = $request['start'] ? : date('Y-m-d');
                    $end = $request['end'] ? : date('Y-m-d');
                    $starttime = strtotime($start);
                    $endtime = strtotime($end) + 86399;
                    $device_record = new EquipmentLoginModel();
                    $device_game = new EquipmentGameModel();

                    //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
                    $request['game_id']  = get_admin_view_game_ids(session('ADMIN_ID'),$request['game_id'] );
                    //增加对登录管理员是否有游戏查看的限制-20210624-byh-end

                    if($request['game_id'] > 0){
                        $map['game_id'] = ['IN',$request['game_id']];
                        $map1['game_id'] = ['IN',$request['game_id']];
                    }
                    if($request['promote_id'] > 0){
                        $map['promote_id'] = $request['promote_id'];
                        $map1['promote_id'] = $request['promote_id'];
                    }
                    $map['create_time'] = ['between',[$starttime,$endtime]];
                    $map1['time'] = ['between',[date('Y-m-d',$starttime),date('Y-m-d',$endtime)]];
                    if ($start == $end) {
                        $hours = ['00~01', '02~03', '04~05', '06~07', '08~09', '10~11', '12~13', '14~15', '16~17', '18~19', '20~21', '22~23'];
                        foreach ($hours as $v) {
                            if ($key == 'news') {
                                $data['news']['ios'][$v] = 0;
                                $data['news']['and'][$v] = 0;
                            }
                            if ($key == 'active') {
                                $data['active']['ios'][$v] = 0;
                                $data['active']['and'][$v] = 0;
                            }
                        }
                        // 新增设备
                        if ($key == 'news') {
                            $hoursnews = $device_game -> news_on_time($map, 'news', 5, 'time,sdk_version');
                            foreach ($hours as $v) {
                                foreach ($hoursnews as $h) {
                                    $time = explode(' ', $h['time']);
                                    if (strpos($v, $time[1]) !== false) {
                                        $data['news'][$h['sdk_version'] == 1 ? 'and' : 'ios'][$v] += (integer) $h['news'];
                                    }
                                }
                            }

                        }
                        if ($key == 'active') {
                            // 活跃设备
                            $hoursactive = $device_record-> active_on_time($map1, 'active', 5, 'time,sdk_version');
                            foreach ($hours as $v) {
                                foreach ($hoursactive as $h) {
                                    $time = explode(' ', $h['time']);
                                    if (strpos($v, $time[1]) !== false) {
                                        $data['active'][$h['sdk_version'] == 1 ? 'and' : 'ios'][$v] += (integer) $h['active'];
                                    }
                                }
                            }
                        }
                        if ($key == 'model') {
                            // 启动机型
                            $hoursmodel = $device_record -> model($map1);
                            foreach ($hoursmodel as $k => $h) {
                                $data['model'][$h['sdk_version'] == 1 ? 'and' : 'ios'][$h['device_name']] = (integer) $h['count'];
                            }

                        }
                    } else {
                        $num = 1;
                        if(get_days($endtime,$starttime) >=365){
                            $num = 7;
                        }
                        $datelist = get_date_list($starttime, $endtime, $num==7?4:1);
                        foreach ($datelist as $k => $v) {
                            if ($key == 'news') {
                                $data['news']['ios'][$v] = 0;
                                $data['news']['and'][$v] = 0;
                            }
                            if ($key == 'active') {
                                $data['active']['ios'][$v] = 0;
                                $data['active']['and'][$v] = 0;
                            }
                        }
                        if ($key == 'news') {
                            // 新增设备
                            $news = $device_game -> news_on_time($map, 'news', $num == 7 ? 2 : 1, 'time,sdk_version');
                            foreach ($datelist as $v) {
                                foreach ($news as $h) {
                                    if ($v == $h['time']) {
                                        $data['news'][$h['sdk_version'] == 1 ? 'and' : 'ios'][$v] += (integer) $h['news'];
                                    }
                                }
                            }
                        }
                        if ($key == 'active') {
                            // 活跃设备
                            $active = $device_record -> active_on_time($map1, 'active', $num == 7 ? 2 : 1, 'time,sdk_version');
                            foreach ($datelist as $v) {
                                foreach ($active as $h) {
                                    if ($v == $h['time']) {
                                        $data['active'][$h['sdk_version'] == 1 ? 'and' : 'ios'][$v] += (integer) $h['active'];
                                    }
                                }
                            }
                        }
                        if ($key == 'model') {
                            // 启动机型
                            $model = $device_record -> model($map1);
                            foreach ($model as $k => $h) {
                                $data['model'][$h['sdk_version'] == 1 ? 'and' : 'ios'][$h['device_name']] = (integer) $h['count'];
                            }
                        }


                    }
                    foreach ($data as $k => $v) {
                        $sum = 0;
                        $tempexport = [];
                        if ($k == 'model') {
                            foreach ($v['ios'] as $t => $s) {
                                $tempexport[] = ['device_name' => $t?:'其他', 'count' => $s, 'version' => 'ios'];
                            }
                            foreach ($v['and'] as $t => $s) {
                                $tempexport[] = ['device_name' => $t?:'其他', 'count' => $s, 'version' => 'android'];
                            }
                        } else {
                            foreach ($v['ios'] as $t => $s) {
                                $sum += $s;
                                $tempexport[] = ['time' => $t, 'count' => $s, 'version' => 'ios'];
                            }
                            foreach ($v['and'] as $t => $s) {
                                $sum += $s;
                                $tempexport[] = ['time' => $t, 'count' => $s, 'version' => 'android'];
                            }
                        }
                        $export[$k] = $tempexport;
                        $export['sum'][$k] = $sum;
                    }
                    $xlsData = $export[$key];
                }
                write_action_log("导出应用概况数据");
                break;
            case 11:

                $xlsCell = array(
                        array('date', '日期'),
                        array('newsplayer', '新增玩家'),
                        array('activeplayer', '活跃玩家'),
                        array('payuser', '付费玩家'),
                        array('newpayuser', '新付费玩家'),
                        array('olduser', '老玩家'),
                        array('totalpay', '付费金额'),
                        array('rate', '付费率'),
                        array('totalpayuser', '累计付费玩家'),
                );
                $param['type'] = $this->request->param('type');
                if(empty($param['type'])){
                    $param['type'] = 1;
                }
                $lStat = new StatLogic();
                $xlsData = $lStat -> table($param);
                if (!empty($xlsData)) {
                    foreach ($xlsData as $k => $v) {
                        $xlsData[$k]['date'] = $k;
                        $xlsData[$k]['olduser'] = $v['activeplayer'] - $v['newsplayer'];
                    }
                }
                $xlsData = array_values($xlsData);
                break;
        }
        foreach ($xlsData as $key => $val) {
            foreach ($xlsCell as $k => $v) {
                if (isset($v[2])) {
                    $ar_k = array_search('*', $v);
                    if ($ar_k !== false) {
                        $v[$ar_k] = $val[$v[0]];
                    }
                    $fun = $v[2];
                    $param = $v;
                    unset($param[0], $param[1], $param[2]);
                    $xlsData[$key][$v[0]] = call_user_func_array($fun, $param);
                }
            }
        }
        $this->exportExcel($xlsName, $xlsCell, $xlsData);
    }

}
