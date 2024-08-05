<?php

namespace app\issue\controller;

use app\issue\logic\PublicLogic;
use cmf\controller\HomeBaseController;
use geetest\lib\GeetestLib;
use think\captcha\Captcha;

class PublicController extends HomeBaseController
{

    public $jyparam = [];

    public function __construct()
    {
        parent ::__construct();
        $this -> jyparam = [
                "user_id" => "test", # 这个是用户的标识，或者说是给极验服务器区分的标识，如果你项目没有预先设置，可以先这样设置：
                "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
                "ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
        ];
    }

    /**
     * @分发平台登录
     *
     * @author: zsl
     * @since: 2020/7/11 11:24
     */
    public function login()
    {
        $param = $this -> request -> param();

        if (empty($param['geetest_challenge']) || empty($param['geetest_validate']) || empty($param['geetest_seccode'])) {
            $this->error('请先图形验证');
        }
        $geetest_id = cmf_get_option('admin_set')['auto_verify_index'];
        $geetest_key = cmf_get_option('admin_set')['auto_verify_admin'];
        $geetest = new GeetestLib($geetest_id, $geetest_key);
        if (session('pro_gtserver') == 1) {
            $validay = $geetest->success_validate($param['geetest_challenge'], $param['geetest_validate'], $param['geetest_seccode'], $this->jyparam);
        } else { //宕机
            $validay = $geetest->fail_validate($param['geetest_challenge'], $param['geetest_validate'], $param['geetest_seccode']);
        }
        if ($validay == 0) {
            $this->error('图形验证码验证失败');
        }

//        //验证参数
//        if (!captcha_check($param['captcha'], 'ff')) {
//            $this -> error('验证码不正确');
//        }
        $lPublic = new PublicLogic();
        $result = $lPublic -> login($param);
        if ($result['code'] == '200') {
            $this -> success($result['msg'],url('issue/management/index'));
        } else {
            $this -> error($result['msg']);
        }
    }


    /**
     * @分发平台用户登出
     *
     * @author: zsl
     * @since: 2020/7/11 13:57
     */
    public function logout()
    {
        session('issue_open_user_info', null);
        session('platform_id', null);
        $this -> success('登出成功',url('issue/index/index'));

    }


}