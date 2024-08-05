<?php

namespace app\promote\model;

use think\Model;
use think\Pinyin;

class PromotePrepaymentDeductRecordModel extends Model
{

    protected $table = 'tab_promote_prepayment_deduct_record';

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
    // 汇总预付款扣除记录 deduct_amount
    public function countDeductAmount($search)
    {
        $totalDeductAmount = $this->where($search)->sum('deduct_amount');
        return $totalDeductAmount;
    }

}