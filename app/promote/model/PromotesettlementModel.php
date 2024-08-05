<?php

namespace app\promote\model;

use think\Model;
use think\Pinyin;
use think\Db;

class PromotesettlementModel extends Model
{

    protected $table = 'tab_promote_settlement';

    protected $autoWriteTimestamp = true;

    /**
     * [promote_apply 一级渠道申请提现]
     * @return [settlement_number] [要操作的订单号]
     * @return [status] [要修改的状态]
     */
    public function settlementChangeStatus($data = [])
    {
        $with = Db::table('tab_promote_withdraw')->field('id,status')->where(['settlement_number' => $data['settlement_number']])->find();
        $this->startTrans();
        if (empty($with)) {
            $add['settlement_number'] = $data['settlement_number'];
            $add['sum_money'] = $data['sum_money'];
            $add['promote_id'] = $data['promote_id'];
            $add['create_time'] = time();
            $add['status'] = $data['status'];
            $sett = $this->where(['settlement_number' => $data['settlement_number']])->update(['ti_status' => $data['status']]);
            if ($sett !== false) {
                $res = Db::table('tab_promote_withdraw')->insert($add);
                if ($res != false) {
                    $this->commit();
                    return true;
                } else {
                    $this->rollback();
                    return false;
                }
            } else {
                $this->rollback();
                return false;
            }
        } else {
            $save['id'] = $with['id'];
            $save['status'] = $data['status'];
            $sett = $this->where(['settlement_number' => $data['settlement_number']])->update(['ti_status' => $data['status']]);
            if ($sett !== false) {
                $res = Db::table('tab_promote_withdraw')->update($save);
                if ($res !== false) {
                    $this->commit();
                    return true;
                } else {
                    $this->rollback();
                    return false;
                }
            } else {
                $this->rollback();
                return false;
            }
        }
    }

    public function get_settlement_record($map = [], $field = '*', $group = null)
    {
        $data = $this->field($field)->where($map)->find();
        return empty($data) ? [] : $data->toArray();
    }

}