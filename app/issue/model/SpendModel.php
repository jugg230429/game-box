<?php

namespace app\issue\model;

use think\Model;

class SpendModel extends Model
{
    protected $table = 'tab_issue_spend';

    protected $autoWriteTimestamp = true;


    //统计: 总充值金额
    public function totalAmount($start_time, $end_time, $param = [])
    {
        $where = [];
        if (isset($param['user_id'])) {
            $where['user_id'] = ['in', $param['user_id']];
        }
        if ($param['type'] == '1') {
            $where['sdk_version'] = '3';
        } elseif($param['type'] == '2'){
            $where['sdk_version'] = ['lt', '3'];
        }else {
            $where['sdk_version'] = '4';
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $where['pay_time'] = ['between', [$start_time, $end_time]];
        $where['pay_status'] = 1;
        $pay_amount = $this -> where($where) -> sum('pay_amount');
        return $pay_amount;
    }

    //统计: 总充值金额
    public function totalFenfa($start_time, $end_time, $param = [])
    {
        $where = [];
        if (isset($param['user_id'])) {
            $where['user_id'] = ['in', $param['user_id']];
        }
        if ($param['type'] == '1') {
            $where['sdk_version'] = '3';
        } elseif($param['type'] == '2'){
            $where['sdk_version'] = ['lt', '3'];
        }else {
            $where['sdk_version'] = '4';
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $where['is_check'] = 1;
        $where['pay_time'] = ['between', [$start_time, $end_time]];
        $where['pay_status'] = 1;
        $pay_amount = $this -> where($where) -> sum('ratio_money');
        return $pay_amount;
    }

}
