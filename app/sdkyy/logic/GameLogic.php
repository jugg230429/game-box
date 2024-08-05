<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link http://www.apache.org/licenses/LICENSE-2.0
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */

namespace app\sdkyy\logic;

use app\member\model\UserModel;
use app\member\model\UserPlayInfoModel;
use app\recharge\model\SpendModel;
use think\Db;
use  app\game\model\GameModel as modelGame;

class GameLogic extends BaseLogic
{
    /**
     * @函数或方法说明
     * @保存角色信息
     * @author: 郭家屯
     * @since: 2020/9/23 9:56
     */
    public function save_role($game=[],$role=[],$user=[])
    {
        $saveData['user_id'] = $user['id'];
        $saveData['user_account'] = $user['account'];
        $saveData['game_id'] = $game['id'];
        $saveData['game_name'] = $game['game_name'];
        $saveData['server_id'] = $role['server_id']?:0;
        $saveData['server_name'] = $role['server_name']?:'';
        $saveData['role_id'] = $role['role_id']?:'';
        $saveData['role_name'] = $role['role_name']?:'';
        $saveData['role_level'] = $role['role_level']?:0;
        $saveData['combat_number'] = $role['fightvalue']?:'';
        $saveData['promote_id'] = $user['promote_id'];
        $saveData['promote_account'] = $user['promote_account'];
        $saveData['update_time'] = time();
        $saveData['play_ip'] = get_client_ip();
        $saveData['nickname'] = $user['nickname'];
        $mUserPlayInfo = new UserPlayInfoModel();
        $where = [];
        $where['user_id'] = $saveData['user_id'];
        $where['game_id'] = $saveData['game_id'];
        $where['server_id'] = $saveData['server_id'];
        if($saveData['role_id']){
            $where['role_id'] = $saveData['role_id'];
        }else{
            $where['role_name'] = $saveData['role_name'];
        }
        $playInfo = $mUserPlayInfo->field('id') -> where($where) -> find();
        if (empty($playInfo)) {
            $saveData['play_time'] = time();
            $result = $mUserPlayInfo -> insert($saveData);
        } else {
            $result = $mUserPlayInfo -> where(['id' => $playInfo->id]) -> update($saveData);
        }
        if (false === $result) {
            return false;
        }
        return true;
    }

}