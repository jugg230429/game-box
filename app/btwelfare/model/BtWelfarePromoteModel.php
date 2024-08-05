<?php

namespace app\btwelfare\model;

use think\Model;

class BtWelfarePromoteModel extends Model
{
    protected $table = 'tab_bt_welfare_promote';

    /**
     * @新增福利设置
     *
     * @author: zsl
     * @since: 2021/1/12 19:45
     */
    public function updateBtWelfare($param, $bt_welfare_id)
    {
        if (empty($param['promote_ids'])) {
            return false;
        }
        //清除所有关联
        $this -> where(['bt_welfare_id' => $bt_welfare_id]) -> delete();
        //重新写入关联数据
        $data = [];
        foreach ($param['promote_ids'] as $k => $promote_id) {
            $data[$k]['bt_welfare_id'] = $bt_welfare_id;
            $data[$k]['promote_id'] = $promote_id;
        }
        $result = $this -> saveAll($data);
        return $result;
    }


}