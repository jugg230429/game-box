<?php

namespace app\api\model\migrate;

use think\Model;

class BusierModel extends Model
{

    protected $table = 'tab_promote_business';

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
            $saveData[$k]['nickname'] = $v['nickname'];
            $saveData[$k]['mobile_phone'] = $v['mobile_phone'];
            $saveData[$k]['real_name'] = $v['real_name'];
            $saveData[$k]['qq'] = $v['qq'];
            $saveData[$k]['status'] = $v['status'];
            $saveData[$k]['last_login_time'] = $v['last_login_time'];
            $saveData[$k]['promote_ids'] = $v['promote_ids'];
            $saveData[$k]['create_time'] = $v['create_time'];
            $saveData[$k]['admin_id'] = $v['admin_id'];
            $saveData[$k]['mark1'] = $v['mark1'];
        }
        $result = $this -> allowField(true) -> isUpdate(false) -> insertAll($saveData);
        return $result;
    }

}