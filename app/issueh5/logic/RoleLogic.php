<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */

namespace app\issueh5\logic;

use app\issue\model\IssueGameModel;
use app\issue\model\PlatformModel;
use app\issue\model\UserModel;
use app\issue\model\UserPlayRoleModel;
use cmf\controller\HomeBaseController;
use think\Db;

class RoleLogic extends HomeBaseController
{
    /**
     * @保存角色信息
     *
     * @author: zsl
     * @since: 2020/6/13 14:56
     */
    public function createRole($params = [])
    {
        if((time()-$params['timestamp'])>5){
            return ['code'=>1005,'msg'=>'验签超时'];
        }
        $channelExt = json_decode(simple_decode($params['channelExt']),true);
        if(empty($channelExt)){
            return ['code'=>1006,'msg'=>'登录透传数据丢失'];
        }
        //获取游戏信息
        $mGame = new IssueGameModel();
        $mGame->field('tab_issue_game.id as game_id,tab_issue_game.sdk_version,game_appid,tab_issue_game.game_name,tab_issue_game.status as online_status,enable_status,game_key,access_key,agent_id,platform_config,platform_id,open_user_id');
        $mGame->where('game_appid','=',$params['game_appid']);
        $mGame->where('platform_id','=',$channelExt['ff_platform']);
        $mGame->join(['tab_issue_game_apply'=>'apply'],'apply.game_id=tab_issue_game.id');
        $mGameData = $mGame->find();
        $game_key = $mGameData['game_key'];
        //验证签名
        $checkRes = $this -> checkSign($params, $game_key);
        if (false === $checkRes) {
            return ['code'=>1003,'msg'=>'角色验签失败'];
        }
        //判断玩家状态
        $params['user_id'] = substr($params['user_id'],4);
        $usermodel = new UserModel();
        $userData = $usermodel
            ->field('user_id,lock_status,account,tab_issue_user.platform_id,openid as platform_openid')
            ->where('tab_issue_user.id','=',$params['user_id'])
            ->where('tab_issue_user.platform_id','=',$channelExt['ff_platform'])
            ->join(['tab_issue_user_play'=>'player'],'player.user_id = tab_issue_user.id')
            ->find();
        if(empty($userData)){
            return ['code'=>1008,'msg'=>'未找到平台该玩家'];
        }
        $params['platform_openid'] = $userData['platform_openid'];
        $mPlatform = new  PlatformModel();
        $platformData = $mPlatform->field('id,open_user_id,platform_config_h5,service_config,controller_name_h5,status')->where('id','=',$channelExt['ff_platform'])->find();
        //保存角色信息
        $mUserPlayRole = new UserPlayRoleModel();
        $res = $mUserPlayRole -> saveRole($params, $userData, $mGameData);
        if (!$res) {
            return ['code'=>1015,'msg'=>'上报角色信息失败'];
        }else{
            $mUserPlayRole = new UserPlayRoleModel();
            $roleData = $mUserPlayRole::get($res);
            if(!empty($platformData['controller_name_h5'])){
                if(!file_exists(APP_PATH.'issueh5/logic/pt/'.$platformData['controller_name_h5'].'Logic.php')){
                    return json(['code'=>1004,'msg'=>'平台接口文件错误']);
                }
                $class = '\app\\'.request()->module().'\\logic\\pt\\'.$platformData['controller_name_h5'].'Logic';
                $logic = new $class();
                $role_data = $logic->pull_role($roleData,$platformData,$mGameData,$params);
            }else{//平台接联运分发

            }
            return ['code'=>200,'msg'=>'上报角色信息成功','data'=>$role_data];
        }
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
        $signdata['sign'] = (new \app\sdkh5\logic\GameLogic()) -> h5SignData($signdata, $key);
        if ($signdata['sign'] != $param['sign']) {
            return false;
        }
        return true;
    }
}