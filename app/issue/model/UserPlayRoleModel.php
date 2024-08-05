<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-09
 */

namespace app\issue\model;

use think\Model;

class UserPlayRoleModel extends Model
{
    protected $table = 'tab_issue_user_play_role';

    protected $autoWriteTimestamp = true;



    /**
     * @保存角色信息
     *
     * @author: yyh
     * @since: 2020/6/13 15:34
     */
    public function saveRole($param, $userInfo, $gameInfo)
    {
        $mUserPlayRole = $this;
        $mUserPlayRole->field('id,role_level,play_time,play_ip');
        $mUserPlayRole->where('user_id','=',$userInfo['user_id']);
        $mUserPlayRole->where('game_id','=',$gameInfo['game_id']);
        $mUserPlayRole->where('server_id','=',$param['server_id']);
        $mUserPlayRole->where('role_id','=',$param['role_id']);
        $mUserPlayRole->where('platform_id','=',$userInfo['platform_id']);
        $mUserPlayRoleData = $mUserPlayRole -> find();
        if (empty($mUserPlayRoleData)) {
            $mUserPlayRole->user_id = $userInfo['user_id'];
            $mUserPlayRole->platform_openid = $param['platform_openid'];
            $mUserPlayRole->user_account = $userInfo['account'];
            $mUserPlayRole->game_id = $gameInfo['game_id'];
            $mUserPlayRole->game_name = $gameInfo['game_name'];
            $mUserPlayRole->server_id = $param['server_id'];
            $mUserPlayRole->server_name = $param['server_name'];
            $mUserPlayRole->role_id = $param['role_id'];
            $mUserPlayRole->role_name = $param['role_name'];
            $mUserPlayRole->role_level = $param['role_level']?:'';
            $mUserPlayRole->combat_number = $param['combat_number']?:'';
            $mUserPlayRole->platform_id = $userInfo['platform_id'];
            $mUserPlayRole->open_user_id = $gameInfo['open_user_id'];
            $mUserPlayRole->platform_account = get_table_entity(new PlatformModel(),['id'=>$userInfo['platform_id']],'account')['account'];
            $mUserPlayRole->play_time = $mUserPlayRole->update_time = time();
            $mUserPlayRole->play_ip = get_client_ip();
            $mUserPlayRole->save();
            return $mUserPlayRole->getLastInsID();
        } else {
            $mUserPlayRoleData->role_level = $param['role_level'];
            $mUserPlayRoleData->play_time = $mUserPlayRoleData->update_time = time();
            $mUserPlayRoleData->play_ip = get_client_ip();
            $mUserPlayRoleData->save();
            return $mUserPlayRoleData->id;
        }
    }
}