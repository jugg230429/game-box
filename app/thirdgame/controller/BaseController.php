<?php

namespace app\thirdgame\controller;

use cmf\controller\AdminBaseController;

class BaseController extends AdminBaseController
{
    protected function _initialize()
    {
        app_auth_value();
        if (AUTH_THIRD_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买第三方游戏权限');
            } else {
                $this->error('请购买第三方游戏权限', url('admin/main/index'));
            };
        }
    }
}