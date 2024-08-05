<?php

namespace app\promote\model;

use think\Model;
use think\Db;

class PromotebindModel extends Model
{

    protected $table = 'tab_promote_bind';

    protected $autoWriteTimestamp = true;

    /**
     *用户绑币充值记录
     */
    public function add_promote_bind($vo)
    {
        $bind_data['pay_order_number'] = $vo['pay_order_number'];
        $bind_data['game_id'] = $vo['game_id'];
        $bind_data['game_name'] = get_game_entity($bind_data['game_id'],'game_name')['game_name'];
        $bind_data['user_id'] = $vo['to_id'];
        $bind_data['user_account'] = get_user_entity($bind_data['user_id'],false,'account')['account'];
        $bind_data['promote_id'] = $vo['promote_id'];
        $bind_data['promote_account'] = get_promote_name($bind_data['promote_id']);
        $bind_data['pay_amount'] = $vo['price'];
        $bind_data['cost'] = $vo['cost'];
        $bind_data['pay_status'] = 0;
        $bind_data['discount'] = $vo['discount'];
        $bind_data['pay_way'] = $vo['pay_way'];
        $bind_data['pay_ip'] = get_client_ip();
        $bind_data['pay_time'] = time();
        $result = Db::table('tab_promote_bind')->insertGetId($bind_data);
        return $result;
    }

}