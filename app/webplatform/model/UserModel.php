<?php

namespace app\webplatform\model;

use app\webplatform\model\WebPlatformUserModel;
use think\Db;
use think\Model;

class UserModel extends Model
{
    protected $table = 'tab_user';

    protected $autoWriteTimestamp = true;
    /**
     * 登录
     *
     * @param array $param
     * @author: Juncl
     * @time: 2021/08/27 20:15
     */
    public function login($param=[])
    {
        //查询是否绑定
        $WebPlatformModel = new WebPlatformUserModel();
        $userId = $WebPlatformModel->where('third_user_id',$param['user_id'])->value('user_id');
        if($userId > 0){
            return $userId;
        }else{
            //新增绑定玩家
            $res = $this->addUser($param);
            return $res;
        }
    }

    /**
     * 新增账号
     *
     * @param array $param
     * @author: Juncl
     * @time: 2021/08/27 21:20
     */
    public function addUser($param=[])
    {
        Db::startTrans();
        $saveData['account'] = 'TH_' . cmf_random_string();
        $isSet = $this->where('account',$saveData['account'])->value('id');
        if($isSet>0){
            $saveData['account'] = 'TH_' . cmf_random_string();
        }
        $saveData['nickname'] = $saveData['account'];
        $saveData['password'] = cmf_password($saveData['account']);
        $saveData['promote_id'] = $param['promote_id']?:0;
        $saveData['promote_account'] = get_promote_name($param['promote_id'])?:'官方渠道';
        $saveData['fgame_id'] = $param['game_id']?:0;
        $saveData['fgame_name'] = get_game_name($param['game_id'])?:'';
        $saveData['phone'] = $param['phone']?:'';
        $saveData['email'] = $param['email']?:'';
        $saveData['idcard'] = $param['idcard']?:'';
        $saveData['real_name'] = $param['real_name']?:'';
        $saveData['age_status'] = $param['age_status']?:0;
        $saveData['register_ip'] = $param['login_ip'];
        $saveData['login_ip'] = $param['login_ip'];
        $saveData['register_time'] = time();
        $saveData['login_time'] = time();
        $saveData['head_img'] = cmf_get_option('admin_set')['web_set_avatar']?:'sdk/logoo.png';
        $saveData['register_wap'] = 1;
        $saveData['register_type'] = 1;
        $saveData['unionid'] = '';
        $saveData['openid'] = '';
        $UserId = $this->field(true)->insertGetId($saveData);
        if($UserId > 0){
            $WebPlatformModel = new WebPlatformUserModel();
            $thirdData['user_id'] = $UserId;
            $thirdData['user_account'] = $saveData['account'];
            $thirdData['third_user_id'] = $param['user_id'];
            $thirdData['third_user_account'] = $param['account'];
            $thirdData['web_platform_id'] = $param['web_platform_id'];
            $thirdUserId = $WebPlatformModel->field(true)->insertGetId($thirdData);
            if($thirdUserId > 0){
                Db::commit();
                return $UserId;
            }else{
                Db::rollback();
                return false;
            }
        }else{
            Db::rollback();
            return false;
        }

    }
}