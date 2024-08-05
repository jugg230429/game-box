<?php

namespace app\member\model;

use think\Model;

class UserTransactionModel extends Model
{

    protected $table = 'tab_user_transaction';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @获取详情
     * @param int $id
     *
     * @author: 郭家屯
     * @since: 2020/3/2 10:16
     */
    public function getdetail($id=0)
    {
        $data = $this
                ->where('id',$id)
                ->find();
        return $data?$data->toArray():[];
    }
}