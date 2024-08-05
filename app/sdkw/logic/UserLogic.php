<?php
/**
 * @Copyright (c) 2021  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License 江苏溪谷网络科技有限公司版权所有
 * @since 2021-04-21
 */
namespace app\sdkw\logic;

use app\member\model\UserModel;
use app\member\model\UserPlayInfoModel;
use think\Cache;
use think\Exception;

class UserLogic extends BaseLogic
{
    /**
     * 方法 info
     *
     * @descript 个人信息
     *
     * @param $param
     * @return array
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 11:26
     */
    public function info($param)
    {
        $user = get_user_entity($param['user_id'],false,'account,nickname,phone,email,register_type,head_img');
        $user['register_type'] = get_user_register_type($user['register_type']);
        $user['icon'] = cmf_get_image_url($user['head_img']);
        return ['code' => 200, 'data' => $user];
    }

    /**
     * 方法 bind
     *
     * @descript 绑定手机/邮箱
     *
     * @param $param
     * @return int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 15:02
     */
    public function bind($param)
    {
        try {
            $model = new UserModel();
            $data = [];
            switch ($param['bind_type']) {
                case 1:
                    $phone = $param['phone'];
                    if (empty($phone)) {
                        return 1025;
                    }
                    if (empty($param['code'])) {
                        return 1118;
                    }
                    if (!cmf_check_mobile($phone)) {
                        return 1112;
                    }
                    $check = $model->field('id')
                        ->where('phone|account', $phone)
                        ->find();
                    if (!empty($check)) {
                        return 1119;
                    }
                    $code = $this->smsVerify($phone, $param['code']);
                    if ($code) {
                        return $code;
                    }
                    $data['phone'] = $phone;
                    break;
                case 2:
                    $email = $param['email'];
                    if (empty($email)) {
                        return 1027;
                    }
                    if (empty($param['code'])) {
                        return 1118;
                    }
                    if (!cmf_check_email($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        return 1121;
                    }
                    $check = $model->field('id')
                        ->where('email|account', $email)
                        ->find();
                    if (!empty($check)) {
                        return 1120;
                    }
                    $code = $this->emailVerify($email, $param['code']);
                    if ($code) {
                        return $code;
                    }
                    $data['email'] = $email;
                    break;
                default:
                    return 1105;
            }
            $result = $model->update_user_info($param['user_id'], $data);
            if ($result) {
                return 205;
            } else {
                return 1106;
            }
        } catch (Exception $e) {
            return 1106;
        }
    }

    /**
     * 方法 password
     *
     * @descript 修改密码
     *
     * @param $param
     * @return int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 15:34
     */
    public function password($param)
    {
        if (empty($param['user_id'])) {
            return 1003;
        }
        if (empty($param['old_password'])) {
            return 1116;
        }
        if (empty($param['password'])) {
            return 1010;
        }
        $reg = '/^[A-Za-z0-9]{6,15}$/';
        if(!preg_match($reg, $param['password'])) {
            return 1115;
        }
        if ($param['password'] == $param['old_password']) {
            return 1038;
        }
        $model = new UserModel();
        return $model -> updateUserPassword(
            $param['user_id'],
            $param['password'], $param['old_password']);
    }

    /**
     * 方法 role
     *
     * @descript 上传角色信息
     *
     * @param $param
     * @return int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 15:51
     */
    public function role($param)
    {
        //设置请求频率
//        if (!check_ip_access_frequency(30, 1)) {
//            return 1107;
//        }

        $game_data = Cache::get('sdk_game_data'.$param['game_id']);

        $param['game_name'] = $game_data['game_name'];

        if($param['server_id'] =='' ){
            return 1064;
        }
        if($param['server_name']==''){
            return 1065;
        }
        if($param['game_player_name']==''){
            return 1066;
        }
        if($param['role_level']==''){
            return 1068;
        }
        $model = new UserPlayInfoModel();
        $result = $model->add_user_play_info($param);
        if($result){
            return 206;
        }else{
            return 1069;
        }
    }

    public function get_ban_status($param)
    {
        if(empty($param['game_id']) || empty($param['ban_type'])){
            return 1003;
        }
        $msg = [0,1128,1127,1126];
        $ban_type = $param['ban_type']??2;
        $promote_id = $param['promote_id']??0;
        $user_id = $param['user_id']??0;
        $res = judge_user_ban_status($promote_id,$param['game_id'],$user_id,$param['equipment_num'],get_client_ip(),$ban_type);
        if($res){//true,未封禁
            return 200;
        }
        return $msg[$ban_type];
    }

}