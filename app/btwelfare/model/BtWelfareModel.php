<?php

namespace app\btwelfare\model;

use think\Model;

class BtWelfareModel extends Model
{
    protected $table = 'tab_bt_welfare';

    protected $autoWriteTimestamp = true;


    /**
     * @关联推广员表
     *
     * @author: zsl
     * @since: 2021/1/13 9:15
     */
    public function btWelfarePromote()
    {
        return $this -> hasMany('BtWelfarePromoteModel', 'bt_welfare_id');
    }


    /**
     * @新增福利设置
     *
     * @author: zsl
     * @since: 2021/1/12 19:48
     */
    public function add($param)
    {
        $this -> game_id = $param['game_id'];
        if (!empty($param['register_prop_ids'])) {
            $this -> register_prop_ids = implode(',', $param['register_prop_ids']);
        }
        if (!empty($param['recharge_prop_ids'])) {
            $this -> recharge_prop_ids = implode(',', $param['recharge_prop_ids']);
        }
        //累计充值奖励
        $total_recharge_prop = $this -> totalRechargeFormart($param['total_recharge']);
        //单笔充值月卡福利
        if (!empty($param['month_card_money']) && !empty($param['month_card_prop_ids'])) {
            $this -> month_card_money = $param['month_card_money'];
            $this -> month_card_prop_ids = implode(',', $param['month_card_prop_ids']);
        }
        //单笔充值周卡福利
        if (!empty($param['week_card_money']) && !empty($param['week_card_prop_ids'])) {
            $this -> week_card_money = $param['week_card_money'];
            $this -> week_card_prop_ids = implode(',', $param['week_card_prop_ids']);
        }
        $this -> total_recharge_prop = json_encode($total_recharge_prop);
        $this -> start_time = strtotime($param['start_time']);
        $this -> end_time = strtotime($param['end_time']);
        $this -> status = $param['status'];
        $result = $this -> isUpdate(false) -> save();
        return $result;
    }


    /**
     * @编辑福利设置
     *
     * @author: zsl
     * @since: 2021/1/13 10:17
     */
    public function edit($param)
    {
        $this -> game_id = $param['game_id'];
        if (!empty($param['register_prop_ids'])) {
            $this -> register_prop_ids = implode(',', $param['register_prop_ids']);
        }
        if (!empty($param['recharge_prop_ids'])) {
            $this -> recharge_prop_ids = implode(',', $param['recharge_prop_ids']);
        }
        //累计充值奖励
        $total_recharge_prop = $this -> totalRechargeFormart($param['total_recharge']);
        //单笔充值月卡福利
        if (!empty($param['month_card_money']) && !empty($param['month_card_prop_ids'])) {
            $this -> month_card_money = $param['month_card_money'];
            $this -> month_card_prop_ids = implode(',', $param['month_card_prop_ids']);
        }
        //单笔充值周卡福利
        if (!empty($param['week_card_money']) && !empty($param['week_card_prop_ids'])) {
            $this -> week_card_money = $param['week_card_money'];
            $this -> week_card_prop_ids = implode(',', $param['week_card_prop_ids']);
        }
        $this -> total_recharge_prop = json_encode($total_recharge_prop);
        $this -> start_time = strtotime($param['start_time']);
        $this -> end_time = strtotime($param['end_time']);
        $this -> status = $param['status'];
        $result = $this -> allowField(true) -> isUpdate(true) -> save($this, ['id' => $param['id']]);
        return $result;
    }


    /**
     * @返回格式化后的累充数据规则
     *
     * @author: zsl
     * @since: 2021/1/14 11:33
     */
    private function totalRechargeFormart($total_recharge)
    {
        $total_recharge_prop = [];
        //累计充值奖励
        if (empty($total_recharge)) {
            return $total_recharge_prop;
        }
        $total_money = $total_recharge['money'];
        $total_prop = $total_recharge['prop'];
        foreach ($total_money as $k => $money) {
            if (!empty($money) && is_numeric($money) && !empty($total_prop[$k])) {
                $total_recharge_prop[] = ['money' => $money, 'prop' => $total_prop[$k]];
            }
        }
        //根据充值金额排序
        $last_names = array_column($total_recharge_prop, 'money');
        array_multisort($last_names, SORT_ASC, $total_recharge_prop);
        return $total_recharge_prop;
    }


}