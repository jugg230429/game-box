<?php

namespace app\promote\model;

use think\Model;
use think\Pinyin;
use think\Db;

class PromotewithdrawModel extends Model
{

    protected $table = 'tab_promote_withdraw';

//    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @兑换
     * @author: 郭家屯
     * @since: 2019/8/6 16:53
     */
    public function settlement($amount=0,$type=1,$promote=[]){
        $promote_withdraw['widthdraw_number'] = 'JS_'.date('YmdHis').rand(100,999);
        $promote_withdraw['sum_money'] = $amount;
        if($type == 1 && cmf_get_option('cash_set')['payment_fee'] && $promote['parent_id'] == 0){
            $promote_withdraw['fee'] = round($amount * cmf_get_option('cash_set')['payment_fee']/100,2);
        }
        $promote_withdraw['promote_id'] = $promote['id'];
        $promote_withdraw['promote_account'] = $promote['account'];
        $promote_withdraw['create_time'] = time();
        $promote_withdraw['type'] = $type;
        $promote_withdraw['promote_level'] = $promote['promote_level'];
        // 启动事务
        Db::startTrans();
        try {
            $this->insert($promote_withdraw);
            Db::table('tab_promote')->where('id',$promote['id'])->setDec('balance_profit',$amount);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }

}
