<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-13
 */

namespace app\issuesy\logic\packtool;

use app\issue\logic\PlatformLogic;
use app\issue\logic\PublicLogic;
use app\issue\model\OpenUserModel;
use app\issue\validate\PublicValidate;
use think\Request;

class UserLogic extends BaseLogic
{
    public function user_login($data){
        $result = ['code' => 200, 'msg' => '登录成功', 'data' => []];
        if(empty($data['account'])){
            $this->set_message("1003","用户账号不能为空");
        }
        if(empty($data['password'])){
            $this->set_message("1004","用户密码不能为空");
        }
        $lPublic = new PublicLogic();
        $res = $lPublic -> login($data);
        if($res['code']==0){
            $this->set_message("1005","用户不存在或已锁定");
        }
        $userInfo = $res['data'];
        //更新用户信息
        $mOpenUser = new OpenUserModel();
        $mOpenUser -> save(['last_login_time' => time(), 'last_login_ip' => get_client_ip()], ['id' => $userInfo['id']]);
        $result['data']['user_id'] = $userInfo['id'];
        $result['data']['token'] = base64_encode(password_hash($userInfo['password'],PASSWORD_DEFAULT));
        $lPlatform = new PlatformLogic();
        $platformList = $lPlatform -> getUserPlatform(['open_user_id' => $userInfo['id']]);
        $platformData=[];
        foreach ($platformList as $key=>$value){
            $platformData[$key]['platform_id'] = $value['id'];
            $platformData[$key]['platform_name'] = $value['account'];
        }
        $result['data']['platform_lists'] = $platformData;
        $this->set_message($result['code'],$result['msg'],$result['data']);
    }
}