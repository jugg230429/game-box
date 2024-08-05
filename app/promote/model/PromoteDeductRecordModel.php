<?php

namespace app\promote\model;

use app\promote\logic\CustompayLogic;
use think\Model;
use think\Pinyin;
use think\Db;

class PromoteDeductRecordModel extends Model
{

    protected $table = 'tab_promote_prepayment_deduct_record';

    protected $autoWriteTimestamp = true;


    /**
     * @新增扣款记录并扣款
     *
     * @author: zsl
     * @since: 2021/1/27 15:36
     */
    public function addRecord($orderInfo)
    {

        $this -> startTrans();
        try {
            $lCustomPay = new CustompayLogic();
            $top_promote_id = get_top_promote_id($orderInfo['pay_promote_id']);
            //查询余额
            $prepayment = Db ::table('tab_promote') -> where(['id' => $top_promote_id]) -> value('prepayment');
            //获取应扣除金额
            $deductAmont = $lCustomPay -> getDeductAmount($top_promote_id, $orderInfo['game_id'], $orderInfo['pay_amount']);
            if ($prepayment < $deductAmont) {
                return false;
            }
            //新增扣款记录
            $this -> promote_id = $top_promote_id;
            $this -> promote_account = get_promote_name($top_promote_id);
            $this -> pay_order_number = $orderInfo['pay_order_number'];
            $this -> old_amount = $prepayment;
            $this -> new_amount = $prepayment - $deductAmont;
            $this -> deduct_amount = $deductAmont;
            $result = $this -> allowField(true) -> isUpdate(false) -> save();
            if (false === $result) {
                $this -> rollback();
                return false;
            }
            //扣除预付款
            $mPromote = new PromoteModel();
            $decRes = $mPromote -> where(['id' => $top_promote_id]) -> setDec('prepayment', $deductAmont);
            if (false === $decRes) {
                $this -> rollback();
                return false;
            }
            $this -> commit();
            return $result;
        } catch (\Exception $e) {
            $this -> rollback();
            return $e -> getMessage();
        }


    }

}
