<?php

namespace app\api\model\migrate;

use think\Model;

class UserPlayModel extends Model
{

    protected $table = 'tab_user_play';

    protected $autoWriteTimestamp = false;


    /**
     * @用户数据迁移
     *
     * @author: zsl
     * @since: 2021/2/4 9:45
     */
    public function migrateData($param)
    {

        $saveData = [];
        foreach ($param as $k => $v) {
            $saveData[$k]['id'] = $v['id'];
            $saveData[$k]['user_id'] = $v['user_id'];
            $saveData[$k]['user_account'] = $v['user_account'];
            $saveData[$k]['game_id'] = $v['game_id'];
            $saveData[$k]['game_name'] = $v['game_name'];
            $saveData[$k]['game_appid'] = $v['game_appid'];
            $saveData[$k]['bind_balance'] = $v['bind_balance'];
            $saveData[$k]['promote_id'] = $v['promote_id'];
            $saveData[$k]['promote_account'] = $v['promote_account'];
            $saveData[$k]['sdk_version'] = $v['sdk_version'];
            $saveData[$k]['play_time'] = $v['play_time'];
            $saveData[$k]['play_ip'] = $v['play_ip'];
            $saveData[$k]['create_time'] = $v['create_time'];
            $saveData[$k]['is_small'] = $v['is_small'];
            $saveData[$k]['is_del'] = $v['is_del'];
        }
        $result = $this -> allowField(true) -> isUpdate(false) -> insertAll($saveData);
        return $result;
    }

}
