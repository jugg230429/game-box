<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */

namespace app\sdkh5\logic;

use app\game\model\GameModel;
use app\member\model\UserModel;
use app\member\model\UserPlayInfoModel;
use think\Db;

class RoleLogic extends BaseLogic
{


    /**
     * @保存角色信息
     *
     * @author: zsl
     * @since: 2020/6/13 14:56
     */
    public function createRole($param = [])
    {
        //获取游戏信息
        $mGame = new GameModel();
        $field = 'id,game_name,game_appid,pay_status';
        $where = [];
        $where['game_appid'] = $param['game_appid'];
        $gameInfo = $mGame -> field($field) -> where($where) -> find();
        if (empty($gameInfo)) {
            return $this -> failResult('游戏不存在');
        }
//        $channelExt = json_decode(simple_decode($param['channelExt']),true);
//        if(empty($channelExt)){
//            return $this -> failResult('登录透传数据丢失');
//        }
        //获取游戏扩展信息
        $gameSetInfo = Db ::table('tab_game_set') -> field('game_id,game_key') -> where(['game_id' => $gameInfo['id']]) -> find();
        //验证签名
        $checkRes = $this -> checkSign($param, $gameSetInfo['game_key']);
        if (false === $checkRes) {
            return $this -> failResult('验签失败');
        }
        //查询用户数据
        $userInfo = get_user_entity($param['user_id'], false, 'id,account,promote_id,promote_account,puid,nickname');
        if (empty($userInfo)) {
            return $this -> failResult('玩家不存在');
        }
        //保存角色信息
        $saveRoleRes = $this -> saveRole($param, $userInfo, $gameInfo);
        if (!$saveRoleRes) {
            return $this -> failResult('发生错误,上报角色信息失败');
        }
        return $this -> successResult([], '上报角色信息成功');
    }


    /**
     * @保存角色信息
     *
     * @author: zsl
     * @since: 2020/6/13 15:34
     */
    private function saveRole($param, $userInfo, $gameInfo)
    {
        $saveData = [];
        $saveData['user_id'] = $userInfo['id'];
        $saveData['user_account'] = $userInfo['account'];
        $saveData['game_id'] = $gameInfo['id'];
        $saveData['game_name'] = $gameInfo['game_name'];
        $saveData['server_id'] = $param['server_id'];
        $saveData['server_name'] = $param['server_name'];
        $saveData['role_id'] = $param['role_id'];
        $saveData['role_name'] = $param['role_name'];
        $saveData['role_level'] = $param['role_level'];
        $saveData['combat_number'] = $param['combat_number']?:'0';
        $saveData['promote_id'] = $userInfo['promote_id'];
        $saveData['promote_account'] = $userInfo['promote_account'];
        $saveData['play_time'] = $saveData['update_time'] = time();
        $saveData['play_ip'] = get_client_ip();
        $saveData['nickname'] = $userInfo['nickname'];
        $saveData['puid'] = get_user_puid($userInfo['id'],false);
        $mUserPlayInfo = new UserPlayInfoModel();
        $where = [];
        $where['user_id'] = $saveData['user_id'];
        $where['game_id'] = $saveData['game_id'];
        $where['server_id'] = $saveData['server_id'];
        $where['role_id'] = $saveData['role_id'];
        $playInfo = $mUserPlayInfo ->field('id') -> where($where) -> find();
        if (empty($playInfo)) {
            $result = $mUserPlayInfo -> insert($saveData);
        } else {
            $result = $mUserPlayInfo -> where(['id' => $playInfo->id]) -> update($saveData);
        }
        if (false === $result) {
            return false;
        }
        return true;
    }


    /**
     * @验证签名
     *
     * @author: zsl
     * @since: 2020/6/13 15:32
     */
    private function checkSign($param, $key)
    {
        $signdata = [];
        $signdata['user_id'] = $param['user_id'];
        $signdata['game_appid'] = $param['game_appid'];
        $signdata['server_id'] = $param['server_id'];
        $signdata['server_name'] = $param['server_name'];
        $signdata['role_id'] = $param['role_id'];
        $signdata['role_name'] = $param['role_name'];
        $signdata['role_level'] = $param['role_level'];
        $signdata['combat_number'] = $param['combat_number'];
        $signdata['channelExt'] = $param['channelExt'];
        $signdata['timestamp'] = $param['timestamp'];
        $lGame = new GameLogic();
        $signdata['sign'] = $lGame -> h5SignData($signdata, $key);
        if ($signdata['sign'] != $param['sign']) {
            return false;
        }
        return true;
    }


}
