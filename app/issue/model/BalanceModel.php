<?php

namespace app\issue\model;

use think\Model;

class BalanceModel extends Model
{
    protected $table = 'tab_issue_open_user_balance';

    protected $autoWriteTimestamp = true;

    public function add_issue_open_user_balance($data)
    {
        $this->user_id = $data['user_id'];
        $this->pay_order_number = $data['pay_order_number'];
        $this->props_name = $data['props_name'];
        $this->pay_amount = $data['pay_amount'];
        $this->pay_way = $data['pay_way'];
        $this->pay_time = $data['pay_time'];
        $this->pay_ip = $data['pay_ip'];
        $this->save();
        return $this->getLastInsID();
    }
}
