<?php

namespace app\thirdgame\model;

use app\thirdgame\model\UserPlayModel;
use app\thirdgame\model\UserPlayInfoModel;
use think\Model;
use app\member\model\UserLoginRecordModel;
use app\member\model\UserGameLoginModel;
use app\member\model\UserLoginRecordMongodbModel;
use think\Db;

class UserModel extends Model
{
    protected $table = 'tab_user';

    /**
     * 注册
     *
     * @param array $data
     * @return bool
     * @author: Juncl
     * @time: 2021/08/24 20:00
     */
    public function register($data=[])
    {
        if ($data['promote_id']) {
            $promote = get_promote_entity($data['promote_id'],'id,account,parent_id,parent_name,pattern,promote_level,game_ids');
            if ($promote['pattern'] == 1) {
                $is_check = 1;
            }
        }
        $save = array(
            'account' => $data['account'],
            'password' => cmf_password($data['password']),
            'nickname' => $data['nickname']?:$data['account'],
            'wx_nickname' =>'',
            'phone' => empty($data['phone']) ? '' : $data['phone'],
            'email' => empty($data['email']) ? '' : $data['email'],
            'unionid' => '',
            'openid' => '',
            'promote_id' => empty($data['promote_id']) ? 0 : $data['promote_id'],
            'head_img' => cmf_get_option('admin_set')['web_set_avatar']?:'sdk/logoo.png',
            'promote_account' => $promote['account'] ? : "官方渠道",
            'register_way' => $data['register_way']?:1,//注册来源 1sdk 2app 3PC 4wap
            'register_type' => $data['register_type']?:1,//注册方式 0游客1账号 2 手机 3微信 4QQ
            'register_ip' => $data['register_ip'],
            'parent_id' => empty($promote['parent_id']) ? 0 : $promote['parent_id'],
            'parent_name' => $promote['parent_name']?:'',
            'fgame_id' => $data['game_id']?:0,
            'fgame_name' => $data['game_name']?:'',
            'register_time' => time(),
            'real_name' => empty($data['real_name']) ? "" : $data['real_name'],
            'idcard' => empty($data['idcard']) ? '' : $data['idcard'],
            'age_status' => empty($data['age_status']) ? 0 : $data['age_status'],
            'sex' => 0,
            'login_time' => time(),
            'login_ip' => $data['register_ip'],
            'is_check' => empty($is_check) ? 0 : 1,
            'equipment_num' => '',
            'device_name'=> '',
            'invitation_id' => 0,
        );
        Db::startTrans();
        $uid = $this->field(true)->insertGetId($save);
        if ($uid>0) {
            $token = think_encrypt(json_encode(array('uid' => $uid, 'password' => $save['password'])),1);
            $this->where('id',$uid)->update(['token'=>$token]);
            //更新第三方用户表
            $UserThirdModel = new UserThirdModel();
            $userDta['user_id'] = $uid;
            $userDta['user_account'] = $save['account'];
            $userDta['platform_id'] = $data['platform_id'];
            $userThirdId = $UserThirdModel->insertGetId($userDta);
            if($userThirdId > 0){
                Db::commit();
                return $uid;
            }else{
                Db::rollback();
                return false;
            }
        } else {
            Db::rollback();
            return false;
        }
    }

    /**
     * 插入登录记录
     *
     * @param array $param
     * @return array|bool
     * @author: Juncl
     * @time: 2021/08/25 21:23
     */
    public function user_login_record($param=[],$user=[])
    {
        $mUserLoginRecord = new UserLoginRecordModel();
        //保存登录记录表
        $save = array(
            'user_id' =>$user['id'],
            'user_account' => $user['account'],
            'type' => 1,
            'promote_id' => $user['promote_id'],
            'login_time' => time(),
            'login_ip' => $param['login_ip'],
            'game_id' => $param['game_id'] ,
            'game_name' => $param['game_name'],
            'sdk_version' => $param['sdk_version'],
            'puid' =>0,
        );
        $login_id = $mUserLoginRecord->insertGetId($save);
        //插入每天登录记录表
        if ($login_id > 0) {//小号不记录
            $UserDayLogin = new UserDayLoginModel();
            $map['user_id'] = $user['id'];
            $map['login_time'] = total(1, 1);
            $map['game_id'] = $param['game_id'];
            $login_record = $UserDayLogin->field('id')->where($map)->find();
            if (empty($login_record)) {
                $day_login['login_record_id'] = $login_id;
                $day_login['user_id'] = $user['id'];
                $day_login['promote_id'] = $user['promote_id'];
                $day_login['game_id'] = $param['game_id'];
                $day_login['is_new'] = 1;
                $day_login['login_time'] = time();
                $UserDayLogin->insert($day_login);
            }
        }
    }

    /**
     * 更新用户登录时间
     *
     * @param array $param
     * @param array $user
     * @author: Juncl
     * @time: 2021/08/26 13:59
     */
    public function update_user_login($param=[], $user=[])
    {
        $save['login_time'] = time();
        $save['login_ip'] = $param['login_ip'];
        $this->where('id',$user['id'])->update($save);
    }


    /**
     * 存入玩家记录表
     *
     * @param array $param
     * @param array $user
     * @author: Juncl
     * @time: 2021/08/26 14:10
     */
    public function add_user_play($param=[], $user=[])
    {
        $UserPlayModel = new UserPlayModel();
        $map['user_id'] = $user['id'];
        $map['game_id'] = $param['game_id'];
        $res = $UserPlayModel->where($map)->value('id');
        if($res > 0){
            $save['play_time'] = time();
            $save['play_ip'] = $param['login_ip'];
            $save['is_del'] = 0;
            $UserPlayModel->where('id', $res['id'])->update($save);
        }else{
            $data["user_id"] = $user["id"];
            $data["user_account"] = $user["account"];
            $data["game_id"] = $param["game_id"];
            $data["game_appid"] = $param["game_appid"];
            $data["game_name"] = $param["game_name"];
            $data["bind_balance"] = 0;
            $data["promote_id"] = $user["promote_id"];
            $data["promote_account"] = $user["promote_account"];
            $data['play_time'] = time();
            $data['create_time'] = time();
            $data['play_ip'] = $param['login_ip'];
            $data['is_small'] =0;//是否是小号
            $data["sdk_version"] = $param["sdk_version"];
            $UserPlayModel->insert($data);
        }
    }

    /**
     * 上传角色信息
     *
     * @author: Juncl
     * @time: 2021/08/26 14:37
     */
    public function save_user_play($param=[], $user=[])
    {
        $UserPlayModel = new UserPlayInfoModel();
        $map['user_id'] = $user['id'];
        $map['game_id'] = $param['game_id'];
        $map['server_id'] = $param['server_id'];
        $map['server_name'] = $param['server_name'];
        $map['role_id'] = $param['role_id'];
        $data = $UserPlayModel->field('id')->where($map)->find();
        if($data){
            $data = $data->toArray();
            $data['role_name'] = $param['role_name'];
            $data['role_level'] = $param['role_level'];
            $data['combat_number'] = $param['combat_number'];
            $data['play_time'] =  time();
            $data['play_ip'] = $param['play_ip'];
            $data['promote_id'] = $user['promote_id'];
            $data['promote_account'] = $user['promote_account'];
            $res =  $UserPlayModel->where('id', $data['id'])->update($data);
        }else{
            $data['promote_id'] = $user['promote_id'];
            $data['promote_account'] = $user['promote_account'];
            $data['nickname'] = $user['nickname'];
            $data['game_id'] = $param['game_id'];
            $data['game_name'] = $param['game_name'];
            $data['server_id'] = $param['server_id'];
            $data['server_name'] = $param['server_name'];
            $data['role_name'] = $param['role_name'];
            $data['role_id'] = $param['role_id'];
            $data['role_level'] = $param['role_level'];
            $data['combat_number'] = $param['combat_number']?:'';
            $data['user_id'] = $user['id'];
            $data['user_account'] = $user['account'];
            $data['play_time'] = $data['update_time'] = time();
            $data["sdk_version"] = $param["sdk_version"];
            $data['player_reserve'] = '';
            $data['play_ip'] = $param['play_ip'];
            $data['create_time'] = time();
            $res = $UserPlayModel->field(true)->insert($data);
        }
        return $res;
    }
}