<?php

namespace app\webplatform\logic;

use app\webplatform\model\UserModel;
use app\webplatform\model\WebPlatformUserModel;
use app\webplatform\model\WebPlatformModel;

class UserLogic
{
     private $web_paltform_id = 0;//平台ID
     private  $promote_id = 0;//绑定渠道ID
     private $platform_url = '';//平台域名
     private $my_url = '';//我方平台域名
     private $api_key = '';     //平台加密秘钥
     private $user_login_url = '/thirdgame/api/user_login';//用户登录接口
     private $user_register_url = '/thirdgame/api/user_register';//用户注册接口
     private $user_info_url = '/thirdgame/api/get_user_info';//获取用户信息接口
     private $update_user_url = '/thirdgame/api/set_user_info';//更新用户信息接口
     private $pay_init_url = '/thirdgame/api/pay_init';//获取支付折扣接口
     private $update_role_url = '/thirdgame/api/save_user_play';//上传角色接口

    public function __construct($promote_id=0)
    {
        $Model = new WebPlatformModel();
        $platformInfo = $Model->field('id,api_key,platform_url,my_url')->where('promote_id',$promote_id)->find();
        $this->web_paltform_id = $platformInfo['id'];
        $this->promote_id = $promote_id;
        $this->platform_url = $platformInfo['platform_url'];
        $this->my_url = $platformInfo['my_url'];
        $this->api_key = $platformInfo['api_key'];
    }
    /**
     * 用户登录
     *
     * @param array $param
     * @author: Juncl
     * @time: 2021/08/27 19:02
     */
    public function user_login($param=[])
    {
        $data = $this->getSign();
        $data['account'] = $param['account'];
        $data['password'] = $this->simple_encode($param['password'],$this->api_key);
        $data['promote_id'] = $param['third_promote_id']?:0;
        $data['game_id'] = $param['game_id']?:0;
        $data['login_ip'] = get_client_ip();
        $result = $this->post($data,$this->platform_url . $this->user_login_url);
        if($result['status'] == 200){
            $userData = $result['data'];
            $UserModel = new UserModel();
            $userData['promote_id'] = $param['promote_id'];
            $userData['game_id'] = $param['promote_id'];
            $userData['login_ip'] = $data['login_ip'];
            $userData['web_platform_id'] = $this->web_paltform_id;
            $res = $UserModel->login($userData);
            if($res){
                return $res;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     *
     *
     * @param array $param
     * @return bool
     * @author: Juncl
     * @time: 2021/08/30 14:27
     */
    public function user_register($param=[])
    {
        $data = $this->getSign();
        $data['account'] = $param['account'];
        $data['password'] = $this->simple_encode($param['password'],$this->api_key);
        $data['promote_id'] = $param['third_promote_id']?:0;
        $data['game_id'] = $param['game_id']?:0;
        $data['register_ip'] = get_client_ip();
        $data['nickname'] = $param['nickname']?:$param['account'];
        $data['phone'] = $param['phone']?:'';
        $data['email'] = $param['email']?:'';
        $data['register_way'] = $param['register_way']?:1;
        $data['register_type'] = $param['register_type']?:1;
        $data['real_name'] = $param['real_name']?:'';
        $data['idcard'] = $param['idcard']?:'';
        $data['age_status'] = $param['age_status']?:0;
        $data['unionid'] = $param['unionid']?:'';
        $result = $this->post($data,$this->platform_url . $this->user_register_url);
        if($result['status'] == 200){
            $UserModel = new UserModel();
            $param['login_ip'] = get_client_ip();
            $param['user_id'] = $result['data']['user_id'];
            $param['account'] = $result['data']['account'];
            $param['web_platform_id'] = $this->web_paltform_id;
            $res = $UserModel->addUser($param);
            if($res>0){
                return $res;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 更新用户信息
     *
     * @author: Juncl
     * @time: 2021/08/30 14:58
     */
    public function update_user_info($user_id=0, $param=[])
    {
        $data = $this->getSign();
        $ThridUserModel = new WebPlatformUserModel();
        $thirdUserInfo = $ThridUserModel->where('user_id',$user_id)->find();
        if(empty($thirdUserInfo)){
            return false;
        }
        $data['user_id'] = $thirdUserInfo['third_user_id'];
        if(isset($param['phone'])){
            $data['phone'] = $param['phone'];
        }
        if(isset($param['email'])){
            $data['email'] = $param['email'];
        }
        if(isset($param['idcard']) && isset($param['real_name'])){
            $data['idcard'] = $param['idcard'];
            $data['real_name'] = $param['real_name'];
            $data['age_status'] = $param['age_status'];
        }
        $result = $this->post($data,$this->platform_url . $this->update_user_url);
        if($result['status'] == 200){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取简化版玩家信息
     *
     * @param array $param
     * @param int $type 1ID 2手机号 3邮箱 4账号
     * @author: Juncl
     * @time: 2021/08/30 16:20
     */
    public function get_user_info($account, $type=1)
    {
        $data = $this->getSign();
        switch ($type){
            case 1:
                $data['type'] = 1;
                $data['user_id'] = $account;
                break;
            case 2:
                $data['type'] = 2;
                $data['phone'] = $account;
                break;
            case 3:
                $data['type'] = 3;
                $data['email'] = $account;
                break;
            case 4:
                $data['type'] = 4;
                $data['account'] = $account;
                break;
            default:
                break;
        }
        $result = $this->post($data,$this->platform_url . $this->user_info_url);
        if($result['status'] == 200){
            $ThirdUserModel = new WebPlatformUserModel();
            $userData = $result['data'];
            $userId = $ThirdUserModel->where('third_user_id',$userData['user_id'])->value('user_id');
            if($userId > 0){
                return $userId;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 获取玩家折扣
     *
     * @param int $user_id
     * @param int $game_i
     * @author: Juncl
     * @time: 2021/08/30 19:21
     */
    public function get_user_discount($user_id=0, $game_id=0)
    {
        $data = $this->getSign();
        $ThridUserModel = new WebPlatformUserModel();
        $thirdUserInfo = $ThridUserModel->where('user_id',$user_id)->find();
        if(empty($thirdUserInfo)){
            return false;
        }
        $data['user_id'] = $thirdUserInfo['third_user_id'];
        $data['game_id'] = $game_id;
        $result = $this->post($data,$this->platform_url . $this->pay_init_url);
        if($result['status'] == 200){
            return $result['data'];
        }else{
            return false;
        }
    }

    /**
     * 上传角色
     *
     * @param array $param
     * @author: Juncl
     * @time: 2021/08/31 17:18
     */
    public function saveUserPlayInfo($param=[])
    {
        $data = $this->getSign();
        $data['user_id'] = get_third_user_id($param['user_id']);
        // 第三方玩家不存在则不上报
        if($data['user_id'] == 0){
            return true;
        }
        $data['game_id'] = $param['game_id'];
        $data['play_ip'] = get_client_ip();
        $data['server_id'] = $param['server_id'];
        $data['server_name'] = $param['server_name'];
        $data['role_id'] = $param['game_player_id'];
        $data['role_name'] = $param['game_player_name'];
        $data['role_level'] = $param['role_level'];
        $data['combat_number'] = $param['combat_number'];
        $data['player_reserve'] = $param['player_reserve'];
        $result = $this->post($data,$this->platform_url . $this->update_role_url);
        if($result['status'] == 200){
            return true;
        }else{
            return false;
        }
    }

    /**
     * H5免登陆进入游戏
     *
     * @param string $token
     * @param int $game_id
     * @return bool|int|mixed|string
     * @author: Juncl
     * @time: 2021/09/03 11:07
     */
    public function h5_user_login($token='',$game_id=0)
    {
        if($game_id == 0){
            return false;
        }
        $userInfo = $this->simple_decode($token,$this->api_key);
        $userInfo = json_decode($userInfo,true);
        if(empty($userInfo)){
            return false;
        }
        $userInfo['promote_id'] = $this->promote_id;
        $UserModel = new UserModel();
        $userInfo['login_ip'] =get_client_ip();
        $userInfo['game_id'] = $game_id;
        $userInfo['web_platform_id'] = $this->web_paltform_id;
        $user_id = $UserModel->login($userInfo);
        return $user_id;
    }

    /**
     * 获取简化版域名
     *
     * @return mixed|string
     * @author: Juncl
     * @time: 2021/09/18 13:28
     */
    public function get_platform_url()
    {
        return $this->platform_url;
    }

    /**
     * 获取加密sign
     *
     * @author: Juncl
     * @time: 2021/08/27 19:02
     */
    protected function getSign()
    {
         $data = [];
         $data['timestamp'] = time();
         $data['platform'] = $this->my_url;
         $data['sign'] = md5($data['platform'] . $data['timestamp'] . $this->api_key);
         return $data;
    }

    /**
     * 简单对称加密算法之加密
     * @param String $string 需要加密的字串
     * @param String $skey 加密EKY
     * @return String
     * @author Juncl
     */
    protected function simple_encode($string = '', $apiKey='')
    {
        $strArr = str_split(base64_encode($string));
        $strCount = count($strArr);
        foreach (str_split($apiKey) as $key => $value)
            $key < $strCount && $strArr[$key] .= $value;
        return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
    }

    /**
     * 简单对称加密算法之解密
     * @param String $string 需要解密的字串
     * @param String $skey 解密KEY
     * @return String
     * @author juncl
     */
    protected function simple_decode($string = '', $apiKey='')
    {
        $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
        $strCount = count($strArr);
        foreach (str_split($apiKey) as $key => $value)
            $key <= $strCount && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
        return base64_decode(join('', $strArr));
    }

    /**
     *post提交数据
     */
    protected function post($param, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        $data = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($data,true);
        return $data;
    }
}