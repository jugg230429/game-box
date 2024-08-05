<?php

namespace app\api\model\migrate;

use think\Db;
use think\Model;

class GameModel extends Model
{

    protected $table = 'tab_game';

    protected $autoWriteTimestamp = false;


    /**
     * @游戏数据迁移
     *
     * @author: zsl
     * @since: 2021/2/4 9:45
     */
    public function migrateData($param)
    {


        $gameData = [];
        $gameSetData = [];
        foreach ($param as $k => $v) {

            $gameData[$k]['id'] = $v['id'];
            $gameData[$k]['game_name'] = $v['game_name'];
            $gameData[$k]['sort'] = $v['sort'];
            $gameData[$k]['short'] = $v['short'];
            $gameData[$k]['game_type_id'] = $v['game_type_id'];
            $gameData[$k]['game_type_name'] = $v['game_type_name'];
            $gameData[$k]['features'] = $v['features'];
            $gameData[$k]['introduction'] = $v['introduction'];
            $gameData[$k]['icon'] = $v['icon'];
            $gameData[$k]['cover'] = $v['cover'];
            $gameData[$k]['hot_cover'] = $v['hot_cover'];
            $gameData[$k]['screenshot'] = $v['screenshot'];
            $gameData[$k]['groom'] = $v['groom'];
            $gameData[$k]['dow_num'] = $v['dow_num'];
            $gameData[$k]['create_time'] = $v['create_time'];
            $gameData[$k]['marking'] = $v['game_appid'];
            $gameData[$k]['game_appid'] = $v['game_appid'];
            $gameData[$k]['category'] = 0;
            $gameData[$k]['sdk_version'] = $v['sdk_version'];
            $gameData[$k]['relation_game_id'] = $v['relation_game_id'];
            $gameData[$k]['relation_game_name'] = $v['relation_game_name'];
            $gameData[$k]['update_time'] = $v['update_time'];


            $gameSetData[$k]['id'] = $v['id'];
            $gameSetData[$k]['game_id'] = $v['id'];
            $gameSetData[$k]['login_notify_url'] = $v['login_notify_url'];
            $gameSetData[$k]['pay_notify_url'] = $v['pay_notify_url'];
            $gameSetData[$k]['game_role_url'] = $v['game_role_url'];
            $gameSetData[$k]['game_gift_url'] = $v['game_gift_url'];
            $gameSetData[$k]['game_server_url'] = $v['game_server_url'];
            $gameSetData[$k]['game_key'] = $v['game_key'];
            $gameSetData[$k]['access_key'] = $v['access_key'];
            $gameSetData[$k]['agent_id'] = $v['agent_id'];
            $gameSetData[$k]['agent_id'] = $v['agent_id'];
        }
        $this -> startTrans();
        $result = $this -> allowField(true) -> isUpdate(false) -> insertAll($gameData);
        if (false === $result) {
            $this -> rollback();
            return $result;
        }
        // game_set表数据
        $setResult = Db ::table('tab_game_set') -> insertAll($gameSetData);
        if (false === $setResult) {
            $this -> rollback();
            return $result;
        }
        $this -> commit();
        return $result;
    }

}
