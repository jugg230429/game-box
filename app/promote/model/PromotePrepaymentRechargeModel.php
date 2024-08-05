<?php

namespace app\promote\model;

use think\Model;
use think\Pinyin;

class PromotePrepaymentRechargeModel extends Model
{

    protected $table = 'tab_promote_prepayment_recharge';

    // 获取器
    // public function getPayTimeAttr($value)
    // {
    //     return date('Y-m-d H:i:s', $value);
    // }
    // public function getCreateTimeAttr($value)
    // {
    //     return date('Y-m-d H:i:s', $value);
    // }
    // 查询记录带分页
    public function lists($search_data = [],$row=10,$order='',$req=[]){
        // $where = [];
        $lists = $this->where($search_data)->order($order)->paginate($row, false, ['query' => $req]);
        return $lists;
    }
    // 汇总充值的总金额
    public function countPayAmount($search=[])
    {
        $payAmount = $this->where($search)->select()->toArray(); // sum('pay_amount');
        $totalPayAmount = 0;
        foreach($payAmount as $v){
            if($v['pay_status'] == 1){
                $totalPayAmount += $v['pay_amount'];
            }
        }
        return $totalPayAmount;
    }

}