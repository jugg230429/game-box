<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */

namespace app\issueyy\logic;

use app\issue\model\IssueGameModel;
use app\issue\model\PlatformModel;
use app\issue\model\OpenUserModel;
use app\issue\model\UserModel;
use app\issueyy\model\GameApplyModel;
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
    public function createRole($request = [])
    {
        //平台状态
        $mPlatform = new PlatformModel();
        $platformData = $mPlatform
                ->alias('plat')
                ->field('plat.id,plat.open_user_id,plat.platform_config_yy,plat.service_config,plat.controller_name_yy,plat.status,user.auth_status,user.balance,min_balance')
                ->where('plat.id','=',$request['channel_code'])
                ->join(['tab_issue_open_user'=>'user'],'user.id=plat.open_user_id')
                ->find();
        if ($platformData['status'] != 1 || $platformData['auth_status'] != 1) {
            return ['code' => 1020, 'msg' => '平台用户已锁定'];
        }
        //获取游戏信息
        $mGame = new IssueGameModel();
        $mGame->field('tab_issue_game.id as game_id,tab_issue_game.sdk_version,game_appid,tab_issue_game.game_name,tab_issue_game.status as online_status,enable_status,cp_game_id,interface_id,platform_config,platform_id,open_user_id,login_notify_url,pay_notify_url,apply.ratio');
        $mGame->where('tab_issue_game.id','=',$request['game_id']);
        $mGame->where('platform_id','=',$request['channel_code']);
        $mGame->join(['tab_issue_game_apply'=>'apply'],'apply.game_id=tab_issue_game.id');
        $mGameData = $mGame->find();
        if(empty($mGameData['online_status'])){
            return ['code' => 1020, 'msg' => '游戏不存在或者已下架'];
        }
        $usermodel = new UserModel();
        $userData = $usermodel
                ->field('user_id,lock_status,account,tab_issue_user.platform_id,openid as platform_openid')
                ->where('tab_issue_user.openid','=',$request['user_id'])
                ->where('tab_issue_user.platform_id','=',$request['channel_code'])
                ->join(['tab_issue_user_play'=>'player'],'player.user_id = tab_issue_user.id')
                ->find();
        if(empty($userData)){
            return ['code' => 1020, 'msg' => '用户不存在'];
        }
        $interface = Db::table('tab_game_interface')->field('tag')->where('id',$mGameData['interface_id'])->find();
        if(empty($interface)){
            return ['code'=>1021,'msg'=>'CP接口错误'];
        }
        $server = Db::table('tab_game_server')->field('server_num,server_name')->where('id',$request['server_id'])->find();
        if(empty($server)){
            return ['code'=>1021,'msg'=>'区服不存在'];
        }
        $controller_game_name = "\app\sdkyy\api\\".$interface['tag'];
        $gamecontroller = new $controller_game_name;
        $role_info = $gamecontroller->get_role($mGameData['cp_game_id'],$server['server_num'],'sue_'.$userData['user_id'],$server['server_name']);
        //保存角色信息
        $mUserPlayRole = new UserPlayRoleModel();
        foreach ($role_info as $key=>$v){
            $v['platform_openid'] = $userData['platform_openid'];
            $mUserPlayRole -> saveRole($v, $userData, $mGameData);
        }
        return ['code'=>1,'msg'=>'获取成功'];
    }

    /**
     * @函数或方法说明
     * @获取角色信息
     * @author: 郭家屯
     * @since: 2020/11/12 16:28
     */
    public function getRole($request=[])
    {
        //平台状态
        $mPlatform = new PlatformModel();
        $platformData = $mPlatform
                ->alias('plat')
                ->field('plat.id,plat.open_user_id,plat.platform_config_yy,plat.service_config,plat.controller_name_yy,plat.status,user.auth_status,user.balance,min_balance')
                ->where('plat.id','=',$request['channel_code'])
                ->join(['tab_issue_open_user'=>'user'],'user.id=plat.open_user_id')
                ->find();
        if ($platformData['status'] != 1 || $platformData['auth_status'] != 1) {
            return ['status' => 1020, 'msg' => '平台用户已锁定'];
        }
        //获取游戏信息
        $mGame = new IssueGameModel();
        $mGame->field('tab_issue_game.id as game_id,tab_issue_game.sdk_version,game_appid,tab_issue_game.game_name,tab_issue_game.status as online_status,enable_status,cp_game_id,interface_id,platform_config,platform_id,open_user_id,login_notify_url,pay_notify_url,apply.ratio');
        $mGame->where('tab_issue_game.id','=',$request['game_id']);
        $mGame->where('platform_id','=',$request['channel_code']);
        $mGame->join(['tab_issue_game_apply'=>'apply'],'apply.game_id=tab_issue_game.id');
        $mGameData = $mGame->find();
        if(empty($mGameData['online_status'])){
            return ['status' => 1020, 'msg' => '游戏不存在或者已下架'];
        }
        $usermodel = new UserModel();
        $userData = $usermodel
                ->field('user_id,lock_status,account,tab_issue_user.platform_id,openid as platform_openid')
                ->where('tab_issue_user.openid','=',$request['user_id'])
                ->where('tab_issue_user.platform_id','=',$request['channel_code'])
                ->join(['tab_issue_user_play'=>'player'],'player.user_id = tab_issue_user.id')
                ->find();
        if(empty($userData)){
            return ['status' => 1020, 'msg' => '用户不存在'];
        }
        $interface = Db::table('tab_game_interface')->field('tag')->where('id',$mGameData['interface_id'])->find();
        if(empty($interface)){
            return ['status'=>1021,'msg'=>'CP接口错误'];
        }
        $server = Db::table('tab_game_server')->field('id,server_num,server_name')->where('id',$request['server_id'])->find();
        if(empty($server)){
            return ['status'=>1021,'msg'=>'区服不存在'];
        }
        $class = '\app\\issueyy\\logic\\pt\\'.$platformData['controller_name_yy'].'Logic';
        $logic = new $class();
        $game_data = json_decode($mGameData->platform_config,true);
        $key = $logic->get_pay_key($game_data);
        $sign = md5($request['user_id'].$request['game_id'].$request['server_id'].$request['pid'].$request['time'].$key);
        if($sign != $request['sign']){
            return ['status'=>1021,'msg'=>'验签错误'];
        }
        //获取角色信息
        $mUserPlayRole = new UserPlayRoleModel();
        $mUserPlayRole->field('server_id,server_name,role_id as id,role_name as name,role_level as level,combat_number');
        $mUserPlayRole->where('platform_openid',$request['user_id']);
        $mUserPlayRole->where('game_id',$mGameData['game_id']);
        $mUserPlayRole->where('server_id',[['eq',$server['id']],['eq',$server['server_num']],'or']);
        $mUserPlayRole->where('platform_id',$platformData['id']);
        $mUserPlayRole->order('play_time desc');
        $roleData = $mUserPlayRole->find();
        $roleData = $roleData->toArray();
        $roleData['status'] = 1;
        return $roleData;
    }

}