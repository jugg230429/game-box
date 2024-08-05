<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\controller;

use app\admin\model\UserModel;
use cmf\controller\AdminBaseController;
use geetest\lib\GeetestLib;
use think\Db;

class PublicController extends AdminBaseController
{
    public $admin_param = [];

    public function initialize()
    {
        $this->admin_param = [
            "user_id" => "test", # 这个是用户的标识，或者说是给极验服务器区分的标识，如果你项目没有预先设置，可以先这样设置：
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
        ];
    }

    /**
     * 后台登陆界面
     */
    public function login()
    {
        $loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
        if (empty($loginAllowed) && !cookie('__LOGIN_BY_CMF_ADMIN_PW__')) {
            throw new \think\Exception('页面不存在');
        }

        $admin_id = session('ADMIN_ID');
        if (!empty($admin_id)) {//已经登录
            return redirect(url("admin/Index/index"));
        } else {
            $site_admin_url_password = config("cmf_SITE_ADMIN_URL_PASSWORD");
            $upw = session("__CMF_UPW__");
            if (!empty($site_admin_url_password) && $upw != $site_admin_url_password) {
                return redirect(cmf_get_root() . "/admin");
            } else {
                session("__SP_ADMIN_LOGIN_PAGE_SHOWED_SUCCESS__", true);
                $result = hook_one('admin_login');
                if (!empty($result)) {
                    return $result;
                }
                $data = cmf_get_option('admin_set');
                $this->assign('config', $data);
                return $this->fetch(":login");
            }
        }
    }

    /**
     * @函数或方法说明(登录方法)
     *
     * @author: 郭家屯
     * @since: 2019/3/27 13:52
     */
    public function doLogin()
    {
        if (hook_one('admin_custom_login_open')) {
            $this->error('您已经通过插件自定义后台登录！');
        }
        $data = $this->request->param();
        if (empty($data['geetest_challenge']) || empty($data['geetest_validate']) || empty($data['geetest_seccode'])) {
            $this->error('请先图形验证');
        }

        $userData = (new UserModel())->checkUser($data);
        if($userData['code']!=1){
            return $userData;
        }
        $result = $userData['result'];

        $pass = $data["password"];
        if (empty($pass)) {
            $this->error(lang('PASSWORD_REQUIRED'));
        }
        if (!empty($result) && $result['user_type'] == 1) {
            if (xigu_compare_password($pass, $result['user_pass'])) {
                $groups = Db::name('RoleUser')
                    ->alias("a")
                    ->join('__ROLE__ b', 'a.role_id =b.id')
                    ->where(["user_id" => $result["id"], "status" => 1])
                    ->value("role_id");
                if ($result["id"] != 1 && (empty($groups) || empty($result['user_status']))) {
                    $this->error('该账号已被禁用，请联系管理员');
                }
                $geetest_id = cmf_get_option('admin_set')['auto_verify_index'];
                $geetest_key = cmf_get_option('admin_set')['auto_verify_admin'];
                $geetest = new GeetestLib($geetest_id, $geetest_key);

                if(!(session('NEED_CODE')===1 && $data['login_check_code']==1)){
                    if (session('admin_gtserver') == 1) {
                        $validay = $geetest->success_validate($data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode'], $this->admin_param);
                    } else { //宕机
                        $validay = $geetest->fail_validate($data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode']);
                    }
                    if ($validay == 0) {
                        $this->error('图形验证码验证失败');
                    }
                }
                //增加账号登录验证码验证判断和验证处理
                if(session('NEED_CODE')===1 && $data['login_check_code']==1){
                    //短信验证
                    $checkCode = $this->sms_verify($result['mobile']??'',$data['yzm']??'',1);
                    if($checkCode['code']!=1){
                        return $checkCode;
                    }
                }
                // if(session('NEED_CODE') !== 2){
                //     $check_code_res = $this->checkCode($result["id"]);
                //     if($check_code_res===false){
                //         $this->error(lang('USERNAME_NOT_EXIST'), url('public/login'));
                //     }elseif($check_code_res===-1){
                //         $this->error(lang('您还未设置手机号，无法通过短信验证登录'));
                //     }elseif($check_code_res===0){
                //         session('NEED_CODE', 1);
                //         $this->success('需要短信验证码验证', null,['need_code'=>1,'admin_mobile'=>hidden_mobile($result['mobile'])]);
                //     }
                // }
                //登入成功页面跳转
                session('ADMIN_ID', $result["id"]);
                session('name', $result["user_login"]);
                session('NEED_CODE', null);
                $result['last_login_ip'] = get_client_ip(0, true);
                $result['last_login_time'] = time();
                $result['login_times'] += 1;
                $token = cmf_generate_user_token($result["id"], 'web');
                $session_id = $this->getSessionId();
                $result['latest_session_id'] = $session_id;
                if (!empty($token)) {
                    session('token', $token);
                    session('tokentime', md5($token . time()));//当前登录用户标识
                }
                Db::name('user')->update($result);
                cookie("admin_username", $data['username'], 3600 * 24 * 30);
                session("__LOGIN_BY_CMF_ADMIN_PW__", null);
                write_action_log('管理员登录');
                $this->success(lang('LOGIN_SUCCESS'), url("admin/Index/index"));
                //记录登录日志
            } else {
                $this->error(lang('PASSWORD_NOT_RIGHT'), url('public/login'));
            }
        } else {
            $this->error(lang('USERNAME_NOT_EXIST'), url('public/login'));
        }
    }

    /**
     *短信验证
     */
    protected function sms_verify($phone = "", $code = "", $type = 2)
    {
        $session = session($phone);
        $result = [];
        if (empty($phone) ||empty($session)) {
            $result['code'] = 0;
            $result['msg'] = '请先获取验证码';
            return ($result);
        }
        #验证短信验证码
        if (session($phone . ".code") != $code) {
            $result['code'] = 0;
            $result['msg'] = '验证码不正确，请重新输入';
            return ($result);
        }
        #验证码是否超时
        $time = time() - session($phone . ".create");
        if ($time > 600) {//$time > 180
            $result['code'] = 0;
            $result['msg'] = '验证码已失效，请重新获取';
            return ($result);
        }
        session('NEED_CODE', 2);//更改短信验证标识,正常提交登录
        if ($type == 1) {
            session($phone,null);
            $result['code'] = 1;
            $result['msg'] = '正确';
            return ($result);
        } else {
            return true;
        }
    }

    /**
     * 判断登录账号是否需要使用手机验证码验证登录
     * 原方法-yyh;更改-byh-2021-8-16 14:09:51
     * @param int $admin_id
     * @return bool|int
     */
    private function checkCode($admin_id=0)
    {
        if(empty($admin_id)){
            return false;
        }
        $modelUser = new UserModel();
        $modelUser = $modelUser->field('id,last_login_ip,mobile');
        $modelUser = $modelUser->where('id',$admin_id);
        $modelUserData = $modelUser->find();
        if(empty($modelUserData)){
            return false;
        }
        $modelUserData = $modelUserData->toArray();
        $res = 1;
        if(!empty($modelUserData['last_login_ip'])){
            if(!empty($modelUserData['last_login_ip']) && $modelUserData['last_login_ip'] != get_client_ip()){//排除第一次登录
                $res = 0;
            }
        }
        $now_hour = date('H');
        if($now_hour>=0&&$now_hour<9){
            $res = 0;
        }
        if($res == 0 && empty($modelUserData['mobile'])){
            $res = -1;
        }
        return $res;
    }

    /**
     * 后台管理员退出
     */
    public function logout()
    {
        write_action_log('管理员退出');
        $admin_id = session('ADMIN_ID');
        $addpwd = cmf_get_option('admin_settings')['admin_password'];
        if($admin_id&&$addpwd){
            $url = url('/'.$addpwd,[],false,false);
        }else{
            $url = url('/admin', [], false, true);
        }
        session('ADMIN_ID', null);
        return redirect($url);
    }

    /**
     * @函数或方法说明
     * 添加极验方法
     * @author: 郭家屯
     * @since: 2019/3/27 15:07
     */
    public function checkgeetest()
    {
        $geetest_id = cmf_get_option('admin_set')['auto_verify_index'];
        $geetest_key = cmf_get_option('admin_set')['auto_verify_admin'];
        $geetest = new GeetestLib($geetest_id, $geetest_key);
        $status = $geetest->pre_process($this->admin_param, 1);
        session('admin_gtserver', $status);
        echo $geetest->get_response_str();
        exit;
    }
}
