<?php

namespace app\scrm\logic;

use app\recharge\model\SpendModel;

class OrderLogic extends BaseLogic
{


    /**
     * @获取玩家订单数据
     *
     * @author: zsl
     * @since: 2021/8/5 9:19
     */
    public function lists($param)
    {
        try {
            //验证参数
            $page = empty($param['page']) ? '1' : $param['page'];
            $limit = empty($param['limit']) ? '10' : $param['limit'];
            $mSpend = new SpendModel();
            $field = "user_id,id as order_id,game_id,game_name,promote_id,pay_order_number,server_id,server_name,game_player_id,
            game_player_name,extra_param as cp_order_number,pay_way,props_name,pay_amount,cost,discount,pay_game_status,pay_status,pay_time,pay_time as add_time,spend_ip as pay_id";
            $where = [];
            if (!empty($param['user_id'])) {
                if (is_array($param['user_id'])) {
                    $where['user_id'] = ['in', $param['user_id']];
                } else {
                    $where['user_id'] = $param['user_id'];
                }
            }
            if (!empty($param['order_id'])) {
                $where['id'] = $param['order_id'];
            }
            $lists = $mSpend -> field($field) -> where($where) -> paginate($limit, '', ['page' => $page]);
            if (empty($lists)) {
                $this -> data = $lists;
                return true;
            }
            foreach ($lists as $v) {
                $cpName = get_game_cp_name($v['game_id']);
                $v -> cp_name = $cpName ? $cpName : '';
                $v -> pay_way = get_pay_way($v -> pay_way);
            }
            $this -> data = $lists;
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }


}
