<?php

namespace app\promote\model;

use think\Model;

class PromotePrepaymentSendRecordModel extends Model
{

    protected $table = 'tab_promote_prepayment_send_record';

    protected $autoWriteTimestamp = true;


    /**
     * @新增记录
     *
     * @author: zsl
     * @since: 2021/2/1 19:21
     */
    public function addRecord($param)
    {
        $this -> promote_id = $param['promote_id'];
        $this -> promote_account = $param['promote_account'];
        $this -> send_amount = $param['send_amount'];
        $this -> new_amount = $param['new_amount'];
        $this -> op_id = cmf_get_current_admin_id();
        $this -> op_account = get_admin_name($this -> op_id);
        $this -> status = 1;
        $result = $this -> allowField(true) -> isupdate(false) -> save();
        return $result;
    }
    // 发放预付款记录汇总 send_amount  wjd 2021-6-8 16:14:32
    public function countSendAmount($where)
    {
        $totalSendAmount = $this->where($where)->sum('send_amount');
        return $totalSendAmount;
    }

}