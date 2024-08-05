<?php

namespace app\promote\model;

use think\Model;
use think\Pinyin;
use think\Db;

class PromotesonsettlementModel extends Model
{

    protected $table = 'tab_promote_son_settlement';

    protected $autoWriteTimestamp = true;

    /**
     * [promote_apply 一级渠道申请提现]
     * @return [settlement_number] [要操作的订单号]
     * @return [status] [要修改的状态]
     */
    public function settlementChangeStatus($data = [])
    {
        $save['status'] = $data['status'];
        $sett = $this->where(['promote_id' => $data['promote_id'], 'settlement_number' => $data['settlement_number']])->update(['ti_status' => $data['status']]);
        if ($sett !== false) {
            return true;
        } else {
            return false;
        }
    }

}