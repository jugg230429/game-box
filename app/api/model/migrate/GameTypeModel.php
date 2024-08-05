<?php

namespace app\api\model\migrate;

use think\Model;

class GameTypeModel extends Model
{

    protected $table = 'tab_game_type';

    protected $autoWriteTimestamp = false;


    /**
     * @游戏类型数据迁移
     *
     * @author: zsl
     * @since: 2021/2/5 9:52\
     */
    public function migrateData($param)
    {
        $saveData = [];
        foreach ($param as $k => $v) {
            $saveData[$k]['id'] = $v['id'];
            $saveData[$k]['type_name'] = $v['type_name'];
            $saveData[$k]['status'] = $v['status'];
            $saveData[$k]['sort'] = $v['sort'];
            $saveData[$k]['create_time'] = $v['create_time'];
        }
        $result = $this -> allowField(true) -> isUpdate(false) -> insertAll($saveData);
        return $result;
    }


}
