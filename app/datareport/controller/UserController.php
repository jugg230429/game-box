<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 19:13
 */

namespace app\datareport\controller;

use app\common\controller\BaseController as Base;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatasqlsummaryController as Sqlsummary;
use app\datareport\event\DatabaseController as Database;
use think\Db;

class UserController extends Base
{
    //数据报表 基础数据
    public function basedata()
    {
        $request = $this->request->param();
//        dump($request);
        //时间
        if (empty($request['datetime'])) {
            $date = date("Y-m-d", strtotime("-6 day")) . '~' . date("Y-m-d");
        } else {
            $date = $request['datetime'];
        }
        $dateexp = explode('~', $date);
        $starttime = $dateexp[0];
        $endtime = $dateexp[1];
        $this->assign('start', $starttime);
        $this->assign('end', $endtime);
        $promote_id = $this->request->param('promote_id', '');
        $game_id = $this->request->param('game_id', '');

//        dump($dateexp);
//        dump($endtime);
//        dump($dateexp);die;
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $game_id = get_admin_view_game_ids(session('ADMIN_ID'),$game_id);
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end

        $databaseevent = new Database();
        $array_keys = ['time', 'new_register_user', 'active_user', 'pay_user', 'total_order', 'total_pay', 'new_pay_user', 'new_total_pay', 'fire_device'];
        $new_data = $databaseevent->basedata($starttime, $endtime, $promote_id, $game_id, $array_keys);
        //排序
        $new_data = parent::array_order($new_data, $request['sort_type'], $request['sort']);
        parent::array_page($new_data, $request);
        return $this->fetch();
    }

    //用户分析
    public function user_analysis()
    {
        $request = $this->request->param();
        //时间
        if (empty($request['datetime'])) {
            $date = date("Y-m-d", strtotime("-6 day")) . '~' . date("Y-m-d");
        } else {
            $date = $request['datetime'];
        }
        $dateexp = explode('~', $date);
        $starttime = $dateexp[0];
        $endtime = $dateexp[1];
        $this->assign('start', $starttime);
        $this->assign('end', $endtime);
        $promote_id = $this->request->param('promote_id', '');
        $game_id = $this->request->param('game_id', '');
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $game_id = get_admin_view_game_ids(session('ADMIN_ID'),$game_id);
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $databaseevent = new Database();
        $data = $new_data = $databaseevent->basedata($starttime, $endtime, $promote_id, $game_id, ['new_register_user', 'active_user', 'active_user7', 'active_user30']);
        //图表
        ksort($data);
        $this->assign('keys', json_encode(array_keys($data)));
        $active = array_column($data, 'count_active_user');
        $active7 = array_column($data, 'count_active_user7');
        $active30 = array_column($data, 'count_active_user30');
        $this->assign('active', json_encode($active));
        $this->assign('active7', json_encode($active7));
        $this->assign('active30', json_encode($active30));
        //列表
        parent::array_page($new_data, $request);
        return $this->fetch();
    }

    //注册留存
    public function register_retain()
    {
        $action = $this->request->action();
        $request = $this->request->param();
        $request['row'] = 100000;
        //时间
        if (empty($request['datetime'])) {
            $date = date("Y-m-d", strtotime("-6 day")) . '~' . date("Y-m-d");
        } else {
            $date = $request['datetime'];
        }
        $datemodel = new \app\common\model\DateListModel();
        $type = $datemodel->get_date_type($date);
        $type = $type == false ? 'day' : $type;
        $dateexp = explode('~', $date);
        $starttime = $dateexp[0];
        $endtime = $dateexp[1];
        $this->assign('start', $starttime);
        $this->assign('end', $endtime);
        $promote_id = $this->request->param('promote_id', '');
        $game_id = $this->request->param('game_id', '');
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $game_id = get_admin_view_game_ids(session('ADMIN_ID'),$game_id);
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $array_keys = ['new_register_user', 'active_user', 'fire_device', 'active_device'];
        $databaseevent = new Database();
        //计算留存
        switch ($type) {
            case 'fourweek':
                $num = 4;
                $samerate = $this->week_retain($request, $starttime, $endtime, $promote_id, $game_id, $array_keys, $num);
                $assginkey = json_encode(['初始用户', '1周后', '2周后', '3周后', '4周后']);
                $this->assign('keys', $assginkey);
                $this->assign('nextdata', json_encode($samerate));
                break;
            case 'eightweek':
                $num = 8;
                $samerate = $this->week_retain($request, $starttime, $endtime, $promote_id, $game_id, $array_keys, $num);
                $assginkey = json_encode(['初始用户', '1周后', '2周后', '3周后', '4周后', '5周后', '6周后', '7周后', '8周后']);
                $this->assign('keys', $assginkey);
                $this->assign('nextdata', json_encode($samerate));
                break;
            case 'twelveweek':
                $num = 12;
                $samerate = $this->week_retain($request, $starttime, $endtime, $promote_id, $game_id, $array_keys, $num);
                $assginkey = json_encode(['初始用户', '1周后', '2周后', '3周后', '4周后', '5周后', '6周后', '7周后', '8周后', '9周后', '10周后', '11周后', '12周后']);
                $this->assign('keys', $assginkey);
                $this->assign('nextdata', json_encode($samerate));
                break;
            case 'threemonth':
                $num = 3;
                $samerate = $this->month_retain($request, $starttime, $endtime, $promote_id, $game_id, $array_keys, $num);
                $assginkey = json_encode(['初始用户', '1月后', '2月后', '3月后']);
                $this->assign('keys', $assginkey);
                $this->assign('nextdata', json_encode($samerate));
                break;
            default:
                $interval = count(periodDate($starttime, $endtime)) - 1;
                if ($interval > 29) {
                    $interval = 29;
                }
                $lastendtime = date("Y-m-d", strtotime($endtime) + $interval * 24 * 3600);
                $data = $databaseevent->basedata($starttime, $lastendtime, $promote_id, $game_id, $array_keys,0);
                $user_retain = $databaseevent->user_retain($data, $interval, $action);
                $this->assign('data_lists', $user_retain);
//                parent::array_page($user_retain,$request);
                ksort($data);
                $keydata = array_slice($data, 0, (count($data) - $interval));
                $new_user_retain = $user_retain;
                $new_user_retain7 = array_slice($user_retain, 0, $interval + 1);
                $samerate[0] = 100;
                $keys[] = ['初始用户'];
                for ($i = 1; $i <= $interval; $i++) {
                    $key = 'same' . $i;
                    //去除未计算出留存的日期
                    foreach ($new_user_retain7 as $k => $v) {
                        if ($v[$key] === false) {
                            unset($new_user_retain[$k]);
                        }
                    }
                    $new_register_user_arr = array_column($new_user_retain, 'new_register_user');//初始用户
                    $new_register_user = count(array_filter(explode(',', implode(',', array_filter($new_register_user_arr)))));
                    $key1 = 'all' . $i;
                    $samearr = array_unique(array_filter(explode(',', implode(',', array_filter(array_column($user_retain, $key))))));
                    $samecount = count($samearr);
                    $samerate[$i] = $new_register_user == 0 ? '0.00' : null_to_0(null_to_0(($samecount / $new_register_user) * 100));
                    $keys[] = $i . '天后';
                }
                $assginkey = json_encode($keys);
                $this->assign('keys', $assginkey);
                $this->assign('nextdata', json_encode($samerate));
                session(session('tokentime') . $action . '_nextdata', json_encode($user_retain));
                break;
        }
        session(session('tokentime') . $action . '_assginkey', $assginkey);
        return $this->fetch();
    }

    //每周留存
    private function week_retain($request, $starttime, $endtime, $promote_id, $game_id, $array_keys, $num)
    {
        $databaseevent = new Database();
        $lastendtime = date("Y-m-d", strtotime($endtime) + $num * 7 * 24 * 3600);//取几周后数据
        $data = $databaseevent->basedata($starttime, $lastendtime, $promote_id, $game_id, $array_keys,0);
        $user_retain = $databaseevent->user_week_retain($data, $num);
        parent::array_page($user_retain, $request);
        $new_user_retain = $user_retain;
        $samerate[0] = 100;
        for ($i = 1; $i <= $num; $i++) {
            $action = $this->request->action();
            switch ($action) {
                case 'active_retain':
                    $key = 'active_same' . $i;
                    $key1 = 'active_user';
                    break;
                case 'device_retain':
                    $key = 'device_same' . $i;
                    $key1 = 'fire_device';
                    break;
                default:
                    $key = 'same' . $i;
                    $key1 = 'new_register_user';
                    break;
            }
            //去除未计算出留存的日期
            foreach ($new_user_retain as $k => $v) {
                if ($v[$key] === false) {
                    unset($new_user_retain[$k]);
                }
            }
            $new_register_user_arr = array_column($new_user_retain, $key1);//初始用户
            $new_register_user = count(array_filter(explode(',', implode(',', array_filter($new_register_user_arr)))));
            $samearr = array_unique(array_filter(explode(',', implode(',', array_filter(array_column($user_retain, $key))))));
            $samecount = count($samearr);
            $samerate[$i] = $new_register_user == 0 ? '0.00' : null_to_0(null_to_0(($samecount / $new_register_user) * 100));
        }
        $action = request()->action();
        session(session('tokentime') . $action . '_nextdata', json_encode($user_retain));
        return $samerate;
    }

    //每月留存
    private function month_retain($request, $starttime, $endtime, $promote_id, $game_id, $array_keys, $num)
    {
        $databaseevent = new Database();
        $next_num_monthstrtotime = date('Y-m-01', strtotime("+$num month", strtotime(substr($endtime, 0, 7) . '-01')));
        $lastendtime = date('Y-m-d', strtotime("$next_num_monthstrtotime +1 month -1 day"));
        $data = $databaseevent->basedata($starttime, $lastendtime, $promote_id, $game_id, $array_keys,0);
        $user_retain = $databaseevent->user_month_retain($data, $lastendtime, $num);
        parent::array_page($user_retain, $request);
        $new_user_retain = $user_retain;
        $samerate[0] = 100;
        for ($i = 1; $i <= $num; $i++) {
            $action = $this->request->action();
            switch ($action) {
                case 'active_retain':
                    $key = 'active_same' . $i;
                    $key1 = 'active_user';
                    break;
                case 'device_retain':
                    $key = 'device_same' . $i;
                    $key1 = 'fire_device';
                    break;
                default:
                    $key = 'same' . $i;
                    $key1 = 'new_register_user';
                    break;
            }
            //去除未计算出留存的日期
            foreach ($new_user_retain as $k => $v) {
                if ($v[$key] === false) {
                    unset($new_user_retain[$k]);
                }
            }
            $new_register_user_arr = array_column($new_user_retain, $key1);//初始用户
            $new_register_user = count(array_filter(explode(',', implode(',', array_filter($new_register_user_arr)))));
            $samearr = array_unique(array_filter(explode(',', implode(',', array_filter(array_column($user_retain, $key))))));
            $samecount = count($samearr);
            $samerate[$i] = $new_register_user == 0 ? '0.00' : null_to_0(null_to_0(($samecount / $new_register_user) * 100));
        }
        $action = request()->action();
        session(session('tokentime') . $action . '_nextdata', json_encode($user_retain));
        return $samerate;
    }

    //活跃留存
    public function active_retain()
    {
        $this->register_retain();
        return $this->fetch();
    }

    //设备留存
    public function device_retain()
    {
        $this->register_retain();
        return $this->fetch();
    }

    //新增、活跃留存详情
    public function user_detail()
    {
        $request = $this->request->param();
        $user = new \app\member\model\UserPlayModel();
        $res = $user->user_login_retain($request['data'], $request['game_id'], $request['date']);
        $new_data = parent::array_order($res, $request['sort_type'], $request['sort']);
        $this->assign('data_lists', $new_data);
        return $this->fetch();
    }

    //设备留存详情
    public function device_detail()
    {
        $request = $this->request->param();
        $user = new \app\member\model\EquipmentGameModel();
        $res = $user->equipment_game_retain($request['data'], $request['game_id'], $request['date']);
        $new_data = parent::array_order($res, $request['sort_type']?:"last_time", $request['sort']);
        $this->assign('data_lists', $new_data);
        return $this->fetch();
    }
}