<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */

namespace app\issuesy\api;

use app\issue\model\PlatformModel;

class LoginApi
{
    function channel_login_api($user,$game_data,$plat_data)
    {
        $api = $plat_data['sdk_config_name'];
        $version = $plat_data['sdk_config_version'];
        switch ($api){
            case '9game':
                $login_verify = $this->login_verify($user,$game_data,$plat_data);
                if($login_verify['code']!=200){
                    return ['code'=>0,'msg'=>$login_verify['msg'],'data'=>[]];
                }
                $user['channel_uid'] = $login_verify['data']['data']['accountId'];

                break;
            case '360':
                $login_verify = $this->login_verify($user,$game_data,$plat_data);
                if($login_verify['code']!=200){
                    return ['code'=>0,'msg'=>$login_verify['msg'],'data'=>[]];
                }
                $user['channel_uid'] = $login_verify['data']['id'];

                break;
            default:

                break;
        }
        return ['code'=>200,'msg'=>'成功','data'=>$user];
    }
    public function login_verify($request,$game_data,$plat_data){
        if(empty($plat_data['controller_name_sy'])){
            return ['code'=>0,'msg'=>'平台接口文件未配置'];
        }
        $class_name = 'Sdk_'.strtolower($plat_data['controller_name_sy']);
        $class = '\app\issuesy\logic\sdk\\'.$class_name.'Logic';
        if(class_exists($class)){
            $logic = new $class();
            $res = $logic->login_verify($request,$game_data,$plat_data);
            return $res;
        }else{
            return ['code'=>0,'msg'=>'平台接口文件不存在'];
        }
    }
    public function user_login($request,$game_data,$plat_data){
        if(empty($plat_data['controller_name_sy'])){
            return ['code'=>0,'msg'=>'平台接口文件未配置'];
        }
        $class_name = 'Sdk_'.strtolower($plat_data['controller_name_sy']);
        $class = '\app\issuesy\logic\sdk\\'.$class_name.'Logic';
        if(class_exists($class)){
            $logic = new $class();
            $res = $logic->user_login($request,$game_data);
            return $res;
        }else{
            return ['code'=>0,'msg'=>'平台接口文件不存在'];
        }
    }

}