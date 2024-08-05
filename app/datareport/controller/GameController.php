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
use app\datareport\event\DatabaseController as Database;
use app\datareport\event\GameController as Game;
use think\Db;

class GameController extends Base
{
    //数据报表 基础数据
    public function game_data()
    {
        $request = $this->request->param();
        //时间判断
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

        //实例化redis
        $promote_id = $this->request->param('promote_id', '');
        $game_id = $this->request->param('game_id', '');
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $game_id = get_admin_view_game_ids(session('ADMIN_ID'),$game_id);
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $gameevent = new Game();
        $new_data = $gameevent->game_base($starttime, $endtime, $promote_id, $game_id);
        //排序
        $new_data = parent::array_order($new_data, $request['sort_type']?:'total_pay', $request['sort']);
//        unset($new_data['']);
        parent::array_page($new_data, $request);
        return $this->fetch();
    }

    //游戏概况 运营指标
    public function game_survey()
    {
        $this->data_survey();
        return $this->fetch();
    }

    //付费渗透
    public function payment()
    {
        $this->data_survey();
        return $this->fetch();
    }

    //平均游戏时长
    public function game_duration()
    {
        $this->data_survey();
        return $this->fetch();
    }

    //数据分析
    private function data_survey()
    {
        $request = $this->request->param();
        $action = $this->request->action();
        //时间判断
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
        //顶部实时
        $this->header_data();//增加对有消息权限的判断
        //下方图表
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $request['game_id'] = get_admin_view_game_ids(session('ADMIN_ID'),$request['game_id']);
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        switch ($action) {
            case 'payment':
                $this->payment_data($request, $starttime, $endtime);
                break;
            case 'game_duration':
                $this->duration_data($request, $starttime, $endtime);
                break;
            default:
                $this->operate_data($request, $starttime, $endtime);
                break;
        }
    }

    //头部实时数据
    private function header_data()
    {
        $event = new \app\datareport\event\DatasummaryController();
        //激活设备
        $map = ['first_device' => 1, 'game_id' => ['gt', 0]];
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $game_id = get_admin_view_game_ids(session('ADMIN_ID'));
        if(!empty($game_id)){
            $map['game_id'] = ['IN',$game_id];
        }
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $group = null;
        $field = 'GROUP_CONCAT(DISTINCT(equipment_num)) as data_str,game_id';
        $data = $event->get_register_device_num($map, $group, $field);
        $equipment_num = $data[0]['data_str'];
        $devicenum = empty($equipment_num) ? 0 : count(explode(',', $equipment_num));
        $this->assign('device_num', $devicenum);

        //新增用户
        $map = ['fgame_id' => ['gt', 0],'puid'=>0];
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        if(!empty($game_id)){
            $map['fgame_id'] = ['IN',$game_id];
        }
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $group = null;
        $field = 'GROUP_CONCAT(id) as data_str,fgame_id';
        $new_register_user = $event->new_register_user($map, $group, $field);
        $new_user = $new_register_user[0]['data_str'];
        $newusernum = empty($new_user) ? 0 : count(explode(',', $new_user));
        $this->assign('newusernum', $newusernum);

        //付费用户  包含所有付费方式 1绑币，2:平台币,3:支付宝,4:微信5谷歌6苹果支付
        $map = ['pay_status' => 1, 'pay_way' => ['egt', 1], 'game_id' => ['gt', 0]];
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        if(!empty($game_id)){
            $map['game_id'] = ['IN',$game_id];
        }
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $group = null;
        $field = "GROUP_CONCAT(DISTINCT(user_id)) as data_user_str,GROUP_CONCAT(DISTINCT(pay_order_number)) as data_order_str,sum(pay_amount) as data_pay_str";
        $pay_data = $event->get_pay_num($map, $group, $field);
        $payuser = $pay_data[0]['data_user_str'];
        $totalpay = $pay_data[0]['data_pay_str'];
        $this->assign('payuser', empty($payuser) ? 0 : count(explode(',', $payuser)));
        //付费金额
        $this->assign('totalpay', $totalpay);

        //不参与结算
        if(AUTH_PROMOTE== 1){
            $model = new \app\promote\model\PromotesettlementModel;
            $map = ['is_check' => ['eq', 0], 'status' => ['eq', 0]];
            $field = "sum(sum_money) as neveramout";
            $neversettle = $model->get_settlement_record($map, $field);
        }else{
            $neversettle['neveramout'] = 0;
        }
        $this->assign('neveramount', null_to_0($neversettle['neveramout']));
    }

    //付费渗透
    private function payment_data($request = '', $starttime, $endtime)
    {
        //实例化redis
        $gameevent = new Database();
        $description = $request['paymenttype'];
        switch ($description) {
            case 'dayarpu':
                $data = $gameevent->basedata($starttime, $endtime, $request['promote_id'], $request['game_id'], ['total_pay', 'active_user']);
                ksort($data);
                $arpu = array_column($data, 'arpu');
                $this->assign('keys', json_encode(array_keys($data)));
                $this->assign('arpu', json_encode($arpu));
                $sumarpu = 0;
                foreach ($arpu as $k => $v) {
                    $sumarpu = $sumarpu + $v;
                }
                $this->assign('avg_arpu', null_to_0($sumarpu / count($arpu)));
                break;
            case 'dayarppu':
                $data = $gameevent->basedata($starttime, $endtime, $request['promote_id'], $request['game_id'], ['total_pay', 'pay_user']);
                ksort($data);
                $arppu = array_column($data, 'arppu');
                $this->assign('keys', json_encode(array_keys($data)));
                $this->assign('arppu', json_encode($arppu));
                $sumarppu = 0;
                foreach ($arppu as $k => $v) {
                    $sumarppu = $sumarppu + $v;
                }
                $this->assign('avg_arppu', null_to_0($sumarppu / count($arppu)));
                break;
            default:
                $data = $gameevent->basedata($starttime, $endtime, $request['promote_id'], $request['game_id'], ['new_register_user', 'pay_user']);
                ksort($data);
                $rate = array_column($data, 'rate');
                array_map(function ($item) use (& $new_rate) {
                    $new_rate[] = substr($item, 0, '-1');
                }, $rate);
                $this->assign('keys', json_encode(array_keys($data)));
                $this->assign('rate', json_encode($new_rate));
                $sumrate = 0;
                foreach ($rate as $k => $v) {
                    $sumrate = $sumrate + substr($v, 0, '-1');
                }
                $this->assign('avg_rate', null_to_0($sumrate / count($rate)) . '%');
                break;
        }
    }

    //运营数据
    private function operate_data($request = '', $starttime, $endtime)
    {
        //实例化redis
        $gameevent = new Database();
        $description = $request['description'];
        switch ($description) {
            case 'active':
                $data = $gameevent->basedata($starttime, $endtime, $request['promote_id'], $request['game_id'], ['new_register_user', 'active_user'],0);
                ksort($data);
                $new_register_user = array_column($data, 'count_new_register_user');
                $active = array_column($data, 'count_active_user');
                foreach ($active as $ak => $av) {
                    $old_user[$ak] = $av - $new_register_user[$ak];
                }
                $this->assign('keys', json_encode(array_keys($data)));
                $this->assign('new_user', json_encode($new_register_user));
                $this->assign('old_user', json_encode($old_user));
                $this->assign('all_user', json_encode($active));
                $this->assign('avg_user', round((array_sum($active)) / count($active)));//avg
                break;
            case 'payuser':
                $data = $gameevent->basedata($starttime, $endtime, $request['promote_id'], $request['game_id'], ['pay_user', 'new_pay_user'],0);
                ksort($data);
                $pay_user = array_column($data, 'count_pay_user');
                $new_pay_user = array_column($data, 'count_new_pay_user');
                foreach ($pay_user as $ak => $av) {
                    $old_pay_user[$ak] = $av - $new_pay_user[$ak];
                }
                $this->assign('keys', json_encode(array_keys($data)));
                $this->assign('new_pay_user', json_encode($new_pay_user));
                $this->assign('old_pay_user', json_encode($old_pay_user));
                $this->assign('all_pay_user', json_encode($pay_user));
                $this->assign('avg_pay_user', round((array_sum($pay_user)) / count($pay_user)));//avg
                break;
            case 'paytotal':
                $data = $gameevent->basedata($starttime, $endtime, $request['promote_id'], $request['game_id'], ['total_pay'],0);
                ksort($data);
                $pay_total = array_column($data, 'total_pay');
                $this->assign('keys', json_encode(array_keys($data)));
                $this->assign('all_pay_total', json_encode($pay_total));
                $this->assign('sum_pay_total', null_to_0(array_sum($pay_total)));
                $this->assign('avg_pay_total', null_to_0((array_sum($pay_total)) / count($pay_total)));//avg
                break;
            default://新增激活和设备
                $data = $gameevent->basedata($starttime, $endtime, $request['promote_id'], $request['game_id'], ['new_register_user', 'fire_device'],0);
                ksort($data);
                $new_register_user = array_column($data, 'count_new_register_user');
                $fire_device = array_column($data, 'count_fire_device');
                $unique_fire_device = array_unique(array_filter(explode(',',implode(',',array_filter(array_column($data, 'fire_device'))))));//每天有可能会有重复的，重新计算去重
                $this->assign('keys', json_encode(array_keys($data)));
                $this->assign('new_register_user', json_encode($new_register_user));//图表用户
                $this->assign('sum_new_register_user', array_sum($new_register_user));//sum用户
                $this->assign('avg_new_register_user', round((array_sum($new_register_user)) / count($new_register_user)));//avg用户
                $this->assign('fire_device', json_encode($fire_device));//图表设备
                $this->assign('sum_fire_device', count($unique_fire_device));//sum设备
                $this->assign('avg_fire_device', round((count($unique_fire_device)) / count($fire_device)));//avg设备
                break;
        }
    }

    //平均游戏时长
    private function duration_data($request = '', $starttime, $endtime)
    {
        if ($request['game_id'] != '') {
            $map['game_id'] = ['IN',$request['game_id']];
        } else {
            $map['game_id'] = ['gt', 0];
        }
        $map['tab_user_game_login.time'] = ['in', periodDate($starttime, $endtime)];
        $time = Db::name('date_list')->where(['time' => ['in', periodDate($starttime, $endtime)]])->order('time asc')->column('time');
        $model = new \app\member\model\UserGameLoginModel();
        $res = $model->duration_data($map);
        $gameevent = new Database();
        $activedata = $gameevent->basedata($starttime, $endtime, $request['promote_id'], $request['game_id'], ['new_register_user', 'active_user']);
        ksort($activedata);
        foreach ($time as $k => $v) {
            $data[$v]['time'] = $v;
            $date_data = $res[$v];
            //时长
            $online_time = array_column($date_data, 'play_time');
            $online_time = empty($online_time) ? 0 : array_sum($online_time);
            $online_time = empty($activedata[$v]['count_active_user']) ? 0 : round($online_time / $activedata[$v]['count_active_user']);
            $online_time = round($online_time / 60);
            $data[$v]['online_time'] = $online_time;
            //次数
            $login_count = array_column($date_data, 'login_count');
            $login_count = empty($login_count) ? 0 : array_sum($login_count);
            $login_count = empty($activedata[$v]['count_active_user']) ? 0 : round(($login_count / $activedata[$v]['count_active_user']));
            $data[$v]['login_count'] = $login_count;
        }
        $this->assign('keys', json_encode($time));
        $this->assign('online_time', json_encode(array_column($data, 'online_time')));
        $this->assign('login_count', json_encode(array_column($data, 'login_count')));
    }
}