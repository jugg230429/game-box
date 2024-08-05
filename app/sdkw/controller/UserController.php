<?php
/**
 * @Copyright (c) 2021  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License 江苏溪谷网络科技有限公司版权所有
 * @since 2021-04-16
 */
namespace app\sdkw\controller;

use app\sdkw\logic\UserLogic;
use think\Request;

class UserController extends BaseController
{
    /**
     * @var UserLogic
     *
     * @descript 描述
     */
    private $obj;

    /**
     * UserController constructor.
     * @param Request|null $request
     */
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> obj = new UserLogic();
    }

    /**
     * 方法 info
     *
     * @descript 个人信息
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 11:01
     */
    public function info()
    {
        $this -> response($this -> obj -> info($this -> input()));
    }

    /**
     * 方法 bind
     *
     * @descript 绑定手机号/邮箱
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 13:32
     */
    public function bind()
    {
        $this -> response($this -> obj-> bind($this -> input()));
    }

    /**
     * 方法 password
     *
     * @descript 修改密码
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 13:39
     */
    public function password()
    {
        $this -> response($this -> obj -> password($this -> input()));
    }

    /**
     * 方法 role
     *
     * @descript 上传角色信息
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 13:39
     */
    public function role()
    {
        $this -> response($this -> obj -> role($this -> input()));
    }


    /**
     * 查询SDK内玩家(渠道/设备号/ip)是否被封禁
     * by:byh-2021-7-16 09:49:08
     */
    public function get_ban_status()
    {
        $this -> response($this -> obj -> get_ban_status($this -> input()));
    }

}