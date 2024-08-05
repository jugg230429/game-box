<?php

namespace app\issue\controller;

use app\issue\logic\DataLogic;
use app\issue\logic\StatLogic;
use cmf\controller\AdminBaseController;
use think\helper\Time;

class ExportController extends AdminBaseController
{


    public function expUser()
    {

        $id = $this -> request -> param('id', 0, 'intval');
        $xlsName = $this -> request -> param('xlsname');
        $param = $this -> request -> param();
        switch ($id) {
            case 1:
                $xlsCell = array(
                        array('date', '日期'),
                        array('open_user_id', '分发用户'),
                        array('platform_id', '分发平台'),
                        array('game_id', '游戏名称'),
                        array('newUserNum', '新增玩家'),
                        array('activeNum', '活跃玩家'),
                        array('newUserAmount', '新增玩家付费'),
                        array('totalAmount', '累计充值'),
                );
                $lStat = new StatLogic();
                if (empty($param['start_time'])) {
                    $param['start_time'] = date("Y-m-d", Time ::daysAgo(10));
                }
                if (empty($param['end_time'])) {
                    $param['end_time'] = date("Y-m-d", Time ::daysAgo(1));
                }
                $param['type'] = $param['type'] ? $param['type'] : '1';
                $result = $lStat -> paySummary($param);
                $xlsData = $result['data'];
                if (!empty($xlsData)) {
                    foreach ($xlsData as $k => $v) {
                        $xlsData[$k]['open_user_id'] = $param['open_user_id'] ? get_issue_open_useraccount($param['open_user_id']) : '全部';
                        $xlsData[$k]['platform_id'] = $param['platform_id'] ? get_pt_account($param['platform_id']) : '全部';
                        $xlsData[$k]['game_id'] = $param['game_id'] ? get_issue_game_name($param['game_id']) : '全部';
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
        $this -> exportExcel($xlsName, $xlsCell, $xlsData);

    }


    /**
     * @导出日报数据
     *
     * @author: zsl
     * @since: 2021/8/16 17:40
     */
    public function expDaily()
    {

        $param = $this -> request -> param();
        $xlsCell = array(
                array('date', '日期'),
                array('open_user_id', '分发用户'),
                array('platform_id', '分发平台'),
                array('game_id', '游戏名称'),
                array('new_user_data', '新增数据'),
                array('active_data', '活跃数据'),
                array('pay_data', '付费数据(元)'),
                array('new_pay_data', '新增付费(元)'),
                array('pay_rate_data', '付费率(%)'),
                array('new_arpu_data', 'ARPU值(元)'),
        );
        //获取要查询的时间
        $param['start_time'] = empty($param['start_time']) ? date("Y-m-d", Time ::daysAgo(9)) : $param['start_time'];
        $param['end_time'] = empty($param['end_time']) ? date("Y-m-d") : $param['end_time'];
        $lData = new DataLogic();
        $lists = $lData -> daily($param,true);
        if (!empty($lists)) {
            $xlsData = [];
            foreach ($lists as $k => $v) {
                $v['date'] = $k;
                if (empty($param['open_user_id'])) {
                    $v['open_user_id'] = '全部';
                } else {
                    $v['open_user_id'] = get_issue_open_useraccount($param['open_user_id']);
                }
                if (empty($param['platform_id'])) {
                    $v['platform_id'] = '全部';
                } else {
                    $v['platform_id'] = get_pt_account(input('platform_id'));
                }
                if (empty($param['game_id'])) {
                    $v['game_id'] = '全部';
                } else {
                    $v['game_id'] = get_issue_game_name(input('platform_id'));
                }
                $v['new_user_data'] = "新增用户: {$v['new_user']}" . PHP_EOL . "新增设备: {$v['new_equipment']}" . PHP_EOL . "注册转化率: {$v['CR']}";
                $v['active_data'] = "活跃用户: {$v['active_user']}" . PHP_EOL . "活跃设备: {$v['active_equipment']}";
                $v['pay_data'] = "付费用户: {$v['pay_user']}" . PHP_EOL . "付费总额: {$v['pay_amount']}" . PHP_EOL . "订单数: {$v['order_count']}" . PHP_EOL . "付费率: {$v['pay_rate']}";
                $v['new_pay_data'] = "新增付费用户: {$v['new_pay_user_count']}" . PHP_EOL . " 新增付费额: {$v['new_pay_amount']}";
                $v['pay_rate_data'] = "付费率: {$v['pay_rate']}" . PHP_EOL . "新增付费率: {$v['new_pay_rate']}";
                $v['new_arpu_data'] = "新增ARPU: {$v['new_arpu']}" . PHP_EOL . "活跃ARPU: {$v['active_arpu']}" . PHP_EOL . "付费ARPU: {$v['pay_arpu']}" . PHP_EOL . "新增付费ARPU: {$v['new_pay_arpu']}";
                $xlsData[] = $v;
            }
        }
        $xlsName = "日报数据_{$param['start_time']}到{$param['end_time']}";
        write_action_log("导出联运分发日报数据");
        $this -> exportExcel($xlsName, $xlsCell, $xlsData);
    }


    /**
     * @导出日报表(时)数据
     *
     * @author: zsl
     * @since: 2021/8/16 21:55
     */
    public function expDailyHour()
    {
        $param = $this -> request -> param();
        $xlsCell = array(
                array('date', '日期'),
                array('open_user_id', '分发用户'),
                array('platform_id', '分发平台'),
                array('game_id', '游戏名称'),
                array('new_user_data', '新增数据'),
                array('active_data', '活跃数据'),
                array('pay_data', '付费数据(元)'),
                array('new_pay_data', '新增付费(元)'),
                array('pay_rate_data', '付费率(%)'),
                array('new_arpu_data', 'ARPU值(元)'),
        );
        $param['start_time'] = empty($param['start_time']) ? date("Y-m-d") : $param['start_time'];
        $lData = new DataLogic();
        $lists = $lData -> dailyHour($param,true);
        if (!empty($lists)) {
            $xlsData = [];
            foreach ($lists as $k => $v) {
                $v['date'] = $k;
                if (empty($param['open_user_id'])) {
                    $v['open_user_id'] = '全部';
                } else {
                    $v['open_user_id'] = get_issue_open_useraccount($param['open_user_id']);
                }
                if (empty($param['platform_id'])) {
                    $v['platform_id'] = '全部';
                } else {
                    $v['platform_id'] = get_pt_account(input('platform_id'));
                }
                if (empty($param['game_id'])) {
                    $v['game_id'] = '全部';
                } else {
                    $v['game_id'] = get_issue_game_name(input('platform_id'));
                }
                $v['new_user_data'] = "新增用户: {$v['new_user']}" . PHP_EOL . "新增设备: {$v['new_equipment']}" . PHP_EOL . "注册转化率: {$v['CR']}";
                $v['active_data'] = "活跃用户: {$v['active_user']}" . PHP_EOL . "活跃设备: {$v['active_equipment']}";
                $v['pay_data'] = "付费用户: {$v['pay_user']}" . PHP_EOL . "付费总额: {$v['pay_amount']}" . PHP_EOL . "订单数: {$v['order_count']}" . PHP_EOL . "付费率: {$v['pay_rate']}";
                $v['new_pay_data'] = "新增付费用户: {$v['new_pay_user_count']}" . PHP_EOL . " 新增付费额: {$v['new_pay_amount']}";
                $v['pay_rate_data'] = "付费率: {$v['pay_rate']}" . PHP_EOL . "新增付费率: {$v['new_pay_rate']}";
                $v['new_arpu_data'] = "新增ARPU: {$v['new_arpu']}" . PHP_EOL . "活跃ARPU: {$v['active_arpu']}" . PHP_EOL . "付费ARPU: {$v['pay_arpu']}" . PHP_EOL . "新增付费ARPU: {$v['new_pay_arpu']}";
                $xlsData[] = $v;
            }
        }
        write_action_log("导出联运分发日报表（时）数据");
        $xlsName = "日报表(时)_{$param['start_time']}";
        $this -> exportExcel($xlsName, $xlsCell, $xlsData);
    }


}
