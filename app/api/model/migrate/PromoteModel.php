<?php

namespace app\api\model\migrate;

use think\Model;

class PromoteModel extends Model
{

    protected $table = 'tab_promote';

    protected $autoWriteTimestamp = false;


    /**
     * @推广员数据迁移
     *
     * @author: zsl
     * @since: 2021/2/4 13:48
     */
    public function migrateData($param)
    {
        $saveData = [];
        foreach ($param as $k => $v) {


            $saveData[$k]['id'] = $v['id'];
            $saveData[$k]['account'] = $v['account'];
            $saveData[$k]['password'] = '##$##' . $v['password'];
            $saveData[$k]['second_pwd'] = '##$##' . $v['second_pwd'];
            $saveData[$k]['nickname'] = $v['nickname'];
            $saveData[$k]['mobile_phone'] = $v['mobile_phone'];
            $saveData[$k]['email'] = $v['email'];
            $saveData[$k]['real_name'] = $v['real_name'];
            $saveData[$k]['bank_name'] = $v['bank_name'];
            $saveData[$k]['bank_card'] = $v['bank_card'];
            $saveData[$k]['bank_phone'] = $v['bank_phone'];
            $saveData[$k]['money'] = $v['money'];
            $saveData[$k]['balance_coin'] = $v['balance_coin'];
            $saveData[$k]['balance_profit'] = $v['balance_profit'];
            $saveData[$k]['status'] = $v['status'];
            $saveData[$k]['parent_id'] = $v['parent_id'];
            $saveData[$k]['parent_name'] = $v['parent_name'];
            $saveData[$k]['promote_level'] = $v['promote_level'];
            $saveData[$k]['top_promote_id'] = $v['top_promote_id'];
            $saveData[$k]['last_login_time'] = $v['last_login_time'];
            $saveData[$k]['create_time'] = $v['create_time'];
            $saveData[$k]['admin_id'] = $v['admin_id'];
            $saveData[$k]['mark1'] = $v['mark1'];
            $saveData[$k]['bank_area'] = $v['bank_area'];
            $saveData[$k]['account_openin'] = $v['account_openin'];
            $saveData[$k]['bank_account'] = $v['bank_account'];
            $saveData[$k]['mark2'] = $v['mark2'];
            $saveData[$k]['busier_id'] = $v['busier_id'];
            $saveData[$k]['alipay_account'] = $v['alipay_account'];
            $saveData[$k]['pattern'] = $v['pattern'];
            $saveData[$k]['register_type'] = $v['register_type'];
            $saveData[$k]['alipay_name'] = $v['alipay_name'];
            $saveData[$k]['settment_type'] = $v['settment_type'];
            $saveData[$k]['settment_type'] = $v['settment_type'];
            $saveData[$k]['update_time'] = $v['update_time'];
        }
        $result = $this -> allowField(true) -> isUpdate(false) -> insertAll($saveData);
        return $result;
    }

}