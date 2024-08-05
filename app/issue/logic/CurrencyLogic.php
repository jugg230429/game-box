<?php

namespace app\issue\logic;

use app\issue\model\BalanceModel;

class CurrencyLogic
{


    /**
     * @获取联运币订单列表
     *
     * @author: zsl
     * @since: 2020/7/17 11:32*
     */
    public function orders($param = [])
    {
        $mBalance = new BalanceModel();
        $row = $param['row']?$param['row']:10;
        $field = 'id,pay_order_number,pay_amount,pay_way,pay_status,create_time';
        $where = [];
        $start_time = $param['start_time'];
        $end_time = $param['end_time'];
        if ($start_time && $end_time) {
            $where['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $where['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $where['create_time'] = ['egt', strtotime($start_time)];
        }
        $where['user_id'] = $param['open_user_id'];
        $lists = $mBalance -> field($field) -> where($where) -> order('create_time desc') -> paginate($row);
        return $lists;
    }


}