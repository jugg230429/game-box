<?php

namespace app\api\model\migrate;

use think\Model;

class SpendModel extends Model
{

    protected $table = 'tab_spend';

    protected $autoWriteTimestamp = false;


    /**
     * @游戏订单数据迁移
     *
     * @author: zsl
     * @since: 2021/2/5 10:05
     */
    public function migrateData($param)
    {

        $saveData = [];
        foreach ($param as $k => $v) {
            $saveData[$k]['id'] = $v['id'];
            $saveData[$k]['user_id'] = $v['user_id'];
            $saveData[$k]['user_account'] = $v['user_account'];
            $saveData[$k]['game_id'] = $v['game_id'];
            $saveData[$k]['game_name'] = $v['game_name'];
            $saveData[$k]['server_id'] = $v['server_id'];
            $saveData[$k]['server_name'] = $v['server_name'];
            $saveData[$k]['game_player_id'] = $v['game_player_id'];
            $saveData[$k]['game_player_name'] = $v['game_player_name'];
            $saveData[$k]['promote_id'] = $v['promote_id'];
            $saveData[$k]['promote_account'] = $v['promote_account'];
            $saveData[$k]['order_number'] = $v['order_number'];
            $saveData[$k]['pay_order_number'] = $v['pay_order_number'];
            $saveData[$k]['props_name'] = $v['props_name'];
            $saveData[$k]['pay_amount'] = $v['pay_amount'];
            $saveData[$k]['discount'] = $v['discount'];
            $saveData[$k]['discount_type'] = $v['discount_type'];
            $saveData[$k]['cost'] = $v['cost'];
            $saveData[$k]['pay_time'] = $v['pay_time'];
            $saveData[$k]['pay_status'] = $v['pay_status'];
            $saveData[$k]['pay_game_status'] = $v['pay_game_status'];
            $saveData[$k]['extra_param'] = $v['extra_param'];
            $saveData[$k]['extend'] = $v['extend'];
            $saveData[$k]['pay_way'] = $v['pay_way'];
            $saveData[$k]['spend_ip'] = $v['spend_ip'];
            $saveData[$k]['is_check'] = $v['is_check'];
            $saveData[$k]['sdk_version'] = $v['sdk_version'];
            $saveData[$k]['role_level'] = $v['role_level'];
            $saveData[$k]['small_id'] = $v['small_id'];
            $saveData[$k]['small_nickname'] = $v['small_nickname'];
            $saveData[$k]['coupon_record_id'] = $v['coupon_record_id'];
            $saveData[$k]['is_weiduan'] = $v['is_weiduan'];
            $saveData[$k]['update_time'] = $v['update_time'];
        }
        $result = $this -> allowField(true) -> isUpdate(false) -> insertAll($saveData);
        return $result;
    }

}