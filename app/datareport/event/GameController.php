<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 19:13
 * sql查询
 */

namespace app\datareport\event;

use cmf\controller\HomeBaseController;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatasqlsummaryController as Sqlsummary;
use think\Db;

class GameController extends HomeBaseController
{
    public function game_base($starttime, $endtime, $promote_id, $game_id, $array_keys = [])
    {
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        //搜索条件
        if (!empty($game_id)) {
//            $gamemap['id'] = $game_id;
            $gamemap['id'] = ['IN',$game_id];
        }
        $gaemidss = $gameids = Db::table('tab_game')->where($gamemap)->order('id asc')->column('id,game_name');
        $datearr = Db::name('date_list')->where(['time' => ['between', [$starttime, $endtime]]])->order('time asc')->column('time');

        //每天数据
        foreach ($datearr as $dk => $dv) {
//            if($dv == date('Y-m-d')){
//                $event = new DatabaseController();
//                $event->basedata_today();
//            }
            $key = 'datareporttoppid_' . $dv;
            $keyarr = $redis->hKeys($key);//得到该日期所有域值
            //redis数据丢失时 实时取sql重新写入
            if (empty($keyarr)) {
                $sqlsummary = new Sqlsummary();
                $sqlsummary->basedata_pid_everyday($dv);
                $keyarr = $redis->hKeys($key);//得到该日期所有域值
            }
            if($promote_id=='is_gf'){
                $promote_id=0;
            }
            //搜索条件 移除不符合key
            if ($promote_id > 0 || $promote_id ===0 || $game_id > 0) {
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
            }else{
                foreach ($keyarr as $kak => $kav) {
                    if (ltrim(strstr($kav, '_', false), '_') == 0) {
                        unset($keyarr[$kak]);
                    }
                }
            }
            $keystr = implode(',', $keyarr);
            $field = empty($keystr) ? '' : $keystr;
            $redisdataarr[] = array_values($redis->hMget($key, $field));
        }
        //数据合并
        $redisdata = array_reduce($redisdataarr, function ($result, $item = []) {
            if (empty($item)) {
                $item = [];
            }
            $result = array_merge($result, $item);
            return $result;
        }, []);
        array_map(function ($item) use (& $dataarr) {
            $dataarr[] = json_decode($item, true);
        }, $redisdata);
        $_data = [];

        //相同id放到一起
        foreach ($dataarr as $v) {
            $_data[$v['game_id']][] = $v;
        }
        foreach ($_data as $_k => $_v) {
            unset($gameids[$_k]);
        }
        $mergedata = $_data + $gameids;
        ksort($mergedata);//根据推id排序
        foreach ($mergedata as $mk => $mv) {
            $data[$mk] = $mv;
            if (!is_array($mv)) {
                $data[$mk] = [];
            }
        }
        $array_keys = empty($array_keys) ? ['total_pay', 'pay_user', 'new_register_user', 'active_user','rate'] : $array_keys;
        $new_data = [];
        //游戏的每个数据合并
        foreach ($data as $dk => $dv) {
            foreach ($array_keys as $kk) {
                if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                    $unique = 0;
                } else {
                    $unique = 1;
                }
                $new_data[$dk][$kk] = str_unique(array_key_value_link($kk, $dv), $unique);
                $new_data[$dk]['count_' . $kk] = arr_count($new_data[$dk][$kk]);
                if ($kk == 'total_pay') {
                    $new_data[$dk][$kk] = str_arr_sum($new_data[$dk][$kk]);
                }
                if ($kk == 'rate') {
                    $new_data[$dk][$kk] = empty($new_data[$dk]['new_register_user']) ? '0.00%' : (empty($new_data[$dk]['pay_user']) ? '0.00%' : null_to_0(count(explode(',', $new_data[$dk]['pay_user'])) / count(explode(',', $new_data[$dk]['new_register_user'])) * 100) . '%');
                }
                $total_data[$kk] = '';//汇总数据数组
            }
            unset($new_data[$dk]['count_total_pay']);
            unset($new_data[$dk]['count_rate']);
            $new_data[$dk]['game_id'] = $dk;
            $new_data[$dk]['game_name'] = $gaemidss[$dk];
        }
        //计算汇总
        foreach ($total_data as $kk => $vv) {
            $total_data[$kk] = array_filter(array_column($new_data, $kk));
            if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                $total_data[$kk] = null_to_0(array_sum($total_data[$kk]));
            } else {
                $new = implode(',', $total_data[$kk]);
                $total_data[$kk] = count(array_filter(array_unique(explode(',', $new))));
            }
        }
        //新增逻辑，重新查询订单总值，只查询真实充值，平台币，微信，支付宝
        $game_id = intval($game_id);
        $map = [];
        $map['pay_status'] = 1;
        $map['pay_way'] = ['>',1];
        $map['pay_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
        if($game_id){
            $map['game_id'] = $game_id;
        }
        if($promote_id){
            $map['promote_id'] = $promote_id;
        }
        //总付费额 
        $total_data['total_pay'] = Db::table('tab_spend')->where($map)->sum('pay_amount');
        //付费用户
        $result = Db::table('tab_spend')->distinct(true)->field('user_id')->where($map)->select();
        $total_data['pay_user'] =  empty($result) ? 0 : count($result); 
        //订单数
        $total_data['total_order'] = Db::table('tab_spend')->where($map)->count();
        //新增用户
        $usermap = [
            'register_time' => ['between', [strtotime($starttime), strtotime($endtime) + 86399]],
            'puid' => 0
        ];
        if($game_id){
            $usermap['fgame_id'] = $game_id;
        }
        if($promote_id){
            $usermap['promote_id'] = $promote_id;
        }
        $total_data['new_register_user'] = Db::table('tab_user')->where($usermap)->count();
        //列表计算
        foreach ($new_data as $k => $v) {
            //新增逻辑，重新查询订单总值，只查询真实充值，平台币，微信，支付宝
            $map['game_id'] = $v['game_id'];
            //总付费额 
            $new_data[$k]['total_pay'] = Db::table('tab_spend')->where($map)->sum('pay_amount');
            //付费用户
            $result = Db::table('tab_spend')->distinct(true)->field('user_id')->where($map)->select();
            $new_data[$k]['count_pay_user'] = empty($result) ? 0 : count($result); 
            //订单数
            $new_data[$k]['total_order'] = Db::table('tab_spend')->where($map)->count();
            //新增用户
            $usermap = [
                'register_time' => ['between', [strtotime($starttime), strtotime($endtime) + 86399]],
                'puid' => 0,
                'fgame_id' => $v['game_id']
            ];
            if($promote_id){
                $usermap['promote_id'] = $promote_id;
            }
            $new_data[$k]['count_new_register_user'] = Db::table('tab_user')->where($usermap)->count();
            $new_data[$k]['rate'] =  $new_data[$k]['count_active_user']==0?'0.00%':null_to_0($new_data[$k]['count_pay_user']/$new_data[$k]['count_active_user']*100).'%';
        }

        unset($total_data['rate']);
        $this->assign('total_data', $total_data);
        return $new_data;
    }
}
