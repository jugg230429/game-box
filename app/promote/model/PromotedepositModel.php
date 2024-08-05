<?php

namespace app\promote\model;

use think\Model;
use think\Pinyin;

class PromotedepositModel extends Model
{

    protected $table = 'tab_promote_deposit';

    protected $autoWriteTimestamp = true;


    public function lists($map = [], $field = '*')
    {
        $data = $this->field($field)->where($map)->select();
        return $data;
    }

    /**
     * 余额充值
     */
    public function add_promote_deposit($vo)
    {
        $balance_data['order_number'] = "";
        $balance_data['pay_order_number'] = $vo['pay_order_number'];
        $balance_data['promote_id'] = $vo['promote_id'];
        $balance_data['to_id'] = $vo['to_id'];
        $balance_data['pay_amount'] = $vo['pay_amount'];
        $balance_data['pay_status'] = 0;
        $balance_data['pay_way'] = $vo['pay_way'];
        $balance_data['type'] = $vo['type'];
        $balance_data['create_time'] = time();
        $balance_data['spend_ip'] = get_client_ip();
        $result = $this->insert($balance_data);
        return $result;
    }

}