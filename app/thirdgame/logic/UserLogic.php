<?php

namespace app\thirdgame\logic;

use app\thirdgame\model\UserModel;
use think\Request;

class UserLogic
{
    /**
     * 用户登录
     *
     * @param array $param
     * @return array
     * @author: Juncl
     * @time: 2021/08/24 20:03
     */
    public function login($param=[])
    {
        $userInfo = get_user_entity($param['account'],true,'id,account,password,idcard,real_name,age_status,lock_status,phone,email,promote_id,promote_account');
        if(!$userInfo || $userInfo['lock_status'] != 1){
            return ['status'=>0,'msg'=>'账号不存在或被禁用'];
        }
        $platformInfo = get_platform_entity_by_map(['platform_url'=>$param['platform']],'id,api_key');
        $param['password'] = $this->simple_decode($param['password'],$platformInfo['api_key']);
        if (!xigu_compare_password($param['password'],$userInfo['password'])) {
            return ['status'=>0,'msg'=>'密码错误'];
        }
        //获取游戏信息
        $gameInfo = get_third_game_entity($platformInfo['id'],$param['game_id'],'id,game_name,sdk_version,game_appid');
        if($gameInfo){
            $param['game_id'] = $gameInfo['id'];
            $param['game_name'] = $gameInfo['game_name'];
            $param['sdk_version'] = $gameInfo['sdk_version'];
            $param['game_appid'] = $gameInfo['game_appid'];
        }else{
            $param['game_id'] = 0;
            $param['game_name'] = '';
            $param['sdk_version'] = 0;
        }
        //插入登录记录
        $UserModel = new UserModel();
        $UserModel->user_login_record($param,$userInfo);
        // 更新用户登录信息
        $UserModel->update_user_login($param,$userInfo);
        // 插入玩家记录表
        if($param['game_id'] > 0){
            $UserModel->add_user_play($param,$userInfo);
        }
        $data = [];
        $data['user_id'] = $userInfo['id'];
        $data['account'] = $userInfo['account'];
        $data['idcard'] = $userInfo['idcard'];
        $data['real_name'] = $userInfo['real_name'];
        $data['age_status'] = $userInfo['age_status'];
        $data['phone'] = $userInfo['phone'];
        $data['email'] = $userInfo['email'];
        return ['status'=>1,'msg'=>'登录成功','data'=>$data];
    }

    /**
     * 用户注册
     *
     * @param array $param
     * @author: Juncl
     * @time: 2021/08/24 16:23
     */
    public function register($param = [])
    {
        $user_info = get_user_entity($param['account'],true,'id');
        if($user_info['id'] > 0){
            return ['status'=>0,'msg'=>'账号已存在'];
        }
        //获取平台ID
        $platformInfo = get_platform_entity_by_map(['platform_url'=>$param['platform']],'id,api_key');
        $platform_id = $platformInfo['id'];
        $param['platform_id'] = $platform_id;
        $param['password'] = $this->simple_decode($param['password'],$platformInfo['api_key']);
        $UserModel = new UserModel();
        //获取游戏信息
        $gameInfo = get_third_game_entity($platform_id,$param['game_id'],'id,game_name,sdk_version,game_appid');
        if($gameInfo){
            $param['game_id'] = $gameInfo['id'];
            $param['game_name'] = $gameInfo['game_name'];
            $param['sdk_version'] = $gameInfo['sdk_version'];
            $param['game_appid'] = $gameInfo['game_appid'];
        }else{
            $param['game_id'] = 0;
            $param['game_name'] = '';
            $param['sdk_version'] = 0;
        }
        $userId = $UserModel->register($param);
        if($userId > 0){
            // 插入登录记录
            $UserModel = new UserModel();
            $userInfo = get_user_entity($userId,false,'id,account,promote_id,promote_account');
            $param['login_ip'] = $param['register_ip'];
            $UserModel->user_login_record($param,$userInfo);
            // 插入玩家记录表
            if($param['game_id'] > 0){
                $UserModel->add_user_play($param,$userInfo);
            }
            // 返回数据
            $data = [];
            $data['user_id'] = $userInfo['id'];
            $data['account'] = $userInfo['account'];
            return ['status'=>1,'msg'=>'注册成功','data'=>$data];
        }else{
            return ['status'=>0,'msg'=>'注册失败'];
        }
    }

    /**
     * 获取用户信息
     *
     * @param array $param
     * @author: Juncl
     * @time: 2021/08/24 20:33
     */
    public function getUserInfo($param=[])
    {
        switch ($param['type']){
            case 1:
                $map['id'] = $param['user_id'];
                break;
            case 2:
                $map['phone'] = $param['phone'];
                break;
            case 3:
                $map['email'] = $param['email'];
                break;
            case 4:
                $map['account'] = $param['account'];
                break;
            default:
                break;
        }
        $UserModel = new UserModel();
        $data = $UserModel
            ->field('id,account,idcard,real_name,age_status,phone,email')
            ->where($map)
            ->where('lock_status',1)
            ->find();
        if(empty($data)){
            return false;
        }else{
            $userData = $data->toArray();
            $userData['user_id'] = $userData['id'];
            return $userData;
        }
    }

    /**
     * 更新玩家状态
     *
     * @param array $param
     * @author: Juncl
     * @time: 2021/08/24 20:42
     */
    public function setUserInfo($param=[])
    {
        $data = [];
        $platformInfo = get_platform_entity_by_map(['platform_url'=>$param['platform']],'id,api_key');
        if(isset($param['password']) && !empty($param['password'])){
            $data['password'] = cmf_password($this->simple_decode($param['password'],$platformInfo['api_key']));
        }
        if(isset($param['phone']) && !empty($param['phone'])){
            $data['phone'] = $param['phone'];
        }
        if(isset($param['email']) && !empty($param['email'])){
            $data['email'] = $param['email'];
        }
        if(isset($param['idcard']) && !empty($param['idcard'])){
            $data['idcard'] = $param['idcard'];
        }
        if(isset($param['real_name']) && !empty($param['real_name'])){
            $data['real_name'] = $param['real_name'];
        }
        if(isset($param['age_status']) && !empty($param['age_status'])){
            $data['age_status'] = $param['age_status'];
        }
        if(isset($param['nickname']) && !empty($param['nickname'])){
            $data['nickname'] = $param['nickname'];
        }
        $UserModel = new UserModel();
        $result = $UserModel->where('id',$param['user_id'])->update($data);
        return $result;
    }

    /**
     * 上传角色信息
     *
     * @param array $param
     * @return array
     * @author: Juncl
     * @time: 2021/09/04 9:26
     */
    public function save_user_play($param=[])
    {
        //查询当前用户
        $userInfo = get_user_entity($param['user_id'],false,'id,account,promote_id,promote_account,nickname');
        if(empty($userInfo)){
            return ['status'=>0,'msg'=>'用户不存在'];
        }
        //获取平台ID
        $platformInfo = get_platform_entity_by_map(['platform_url'=>$param['platform']],'id');
        $param['platform_id'] = $platformInfo['id'];
        //获取游戏信息
        $gameInfo = get_third_game_entity($param['platform_id'],$param['game_id'],'id,game_name,sdk_version,game_appid');
        if($gameInfo){
            $param['game_id'] = $gameInfo['id'];
            $param['game_name'] = $gameInfo['game_name'];
            $param['game_appid'] = $gameInfo['game_appid'];
        }else{
            return ['status'=>0,'msg'=>'游戏不存在'];
        }
        $UserModel = new UserModel();
        $result = $UserModel->save_user_play($param,$userInfo);
        if($result>0){
            return ['status'=>1,'msg'=>'角色信息上传成功'];
        }else{
            return ['status'=>0,'msg'=>'角色信息上传失败'];
        }
    }

    /**
     * H5/页游获取第三方游戏链接
     *
     * @author: Juncl
     * @time: 2021/09/07 15:01
     */
    public function get_play_info($user_id=0, $game_id=0,$pid=0)
    {
         $game_info = get_game_entity($game_id,'sdk_version,game_appid,game_name,add_game_address');
         $user_info = get_user_entity($user_id,false,'id,account,promote_id,promote_account');
         $platData['game_id'] = $game_id;
         $platData['game_name'] = $game_info['game_name'];
         $platData['sdk_version'] = $game_info['sdk_version'];
         $platData['game_appid'] = $game_info['game_appid'];
         $platData['login_ip'] = get_client_ip();
         $UserModel = new UserModel();
         $UserModel->add_user_play($platData,$user_info);
         $token = $this->get_user_token($user_id,$game_id);
         if(empty($game_info['add_game_address'])){
             return false;
         }
         if(strpos($game_info['add_game_address'],'?') === false){
            $game_url = trim($game_info['add_game_address']) . "?" . 'user_token=' . $token;
         }else{
             $game_url = trim($game_info['add_game_address']) . "&" . 'user_token=' . $token;
         }
        $model = Request::instance()->module();
        if($model == 'media' && strpos($game_url,'/mobile/') !== false){
            $game_url = str_replace('/mobile/','/media/',$game_url);
        }else if($model == 'mobile' && strpos($game_url,'/media/') !== false){
            $game_url = str_replace('/media/','/mobile/',$game_url);
        }
        return $game_url;
    }

    /**
     * H5游戏生成用户token
     *
     * @param int $user_id
     * @param int $game_id
     * @author: Juncl
     * @time: 2021/09/01 10:25
     */
    public function get_user_token($user_id=0, $game_id=0)
    {
         $userInfo = get_user_entity($user_id,false,'account,password,idcard,real_name,age_status,phone,email');
         $gameInfo = get_game_entity($game_id,'platform_id');
         $platformInfo = get_platform_entity_by_map($gameInfo['platform_id'],'api_key');
         $data['user_id'] = $user_id;
         $data['account'] = $userInfo['account'];
         $data['idcard'] = $userInfo['idcard'];
         $data['real_name'] = $userInfo['real_name'];
         $data['age_status'] = $userInfo['age_status'];
         $data['phone'] = $userInfo['phone'];
         $data['email'] = $userInfo['email'];
         $data = json_encode($data);
         $userToken = $this->simple_encode($data,$platformInfo['api_key']);
         return $userToken;
    }

    /**
     * 简单对称加密算法之加密
     * @param String $string 需要加密的字串
     * @param String $skey 加密EKY
     * @return String
     * @author yyh
     */
    function simple_encode($string = '', $apiKey='')
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
     * @author yyh
     */
    function simple_decode($string = '', $apiKey='')
    {
        $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
        $strCount = count($strArr);
        foreach (str_split($apiKey) as $key => $value)
            $key <= $strCount && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
        return base64_decode(join('', $strArr));
    }

}