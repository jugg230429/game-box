<?php
/**
 * @Copyright (c) 2021  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License 江苏溪谷网络科技有限公司版权所有
 * @since 2021-04-16
 */
namespace app\sdkw\controller;

use app\sdkw\logic\PublicLogic;
use think\Request;

class PublicController extends BaseController
{

    /**
     * @var PublicLogic
     *
     * @descript 描述
     */
    private $obj;

    /**
     * PublicController constructor.
     * @param Request|null $request
     */
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> obj = new PublicLogic();
    }

    /**
     * 方法 common
     *
     * @descript 公共参数
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/19 0019 9:35
     */
    public function common()
    {
        $this -> response($this -> obj -> common($this -> input()));
    }

    /**
     * 方法 login
     *
     * @descript 登录
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 11:39
     */
    public function login()
    {
        $this -> response($this -> obj -> login($this -> input()));
    }

    /**
     * 方法 third
     *
     * @descript 第三方登录（苹果登录，脸书登录）
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/25 0025 9:37
     */
    public function third()
    {
        $this -> response($this -> obj -> third($this -> input()));
    }

    /**
     * 方法 visitor
     *
     * @descript 游客登录
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 9:59
     */
    public function visitor()
    {
        $this -> response($this -> obj -> visitor($this -> input()));
    }

    /**
     * 方法 forget
     *
     * @descript 忘记密码-查询当前账号是否绑定手机号/邮箱
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 17:19
     */
    public function forget()
    {
        $this -> response($this -> obj -> forget($this -> input()));
    }

    /**
     * 方法 password
     *
     * @descript 忘记密码-重置密码
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 17:43
     */
    public function password()
    {
        $this -> response($this -> obj -> password($this ->input()));
    }

    /**
     * 方法 sms
     *
     * @descript 发送短信
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 15:42
     */
    public function sms()
    {
        $this -> response($this -> obj -> sms($this -> input()));
    }

    /**
     * 方法 email
     *
     * @descript 发送邮件
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 15:43
     */
    public function email()
    {
        $this -> response($this -> obj -> email($this -> input()));
    }

    /**
     * 方法 verify
     *
     * @descript 驗證
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/27 0027 15:45
     */
    public function verify()
    {
        $this -> response($this -> obj -> verify($this -> input()));
    }

    /**
     * 方法 register
     *
     * @descript 注册
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 15:41
     */
    public function register()
    {
        $this -> response($this -> obj -> register($this -> input()));
    }

}