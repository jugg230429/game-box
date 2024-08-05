<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */

namespace app\issue\controller;

use cmf\controller\AdminBaseController;
use app\issue\logic\GameLogic as logicGame;

class GameController extends AdminBaseController
{
    //判断权限
    public function __construct()
    {
        parent ::__construct();
//        if (AUTH_ISSUE != 1) {
//            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
//                $this->error('请购买分发权限');
//            } else {
//                $this->error('请购买分发权限', url('admin/main/index'));
//            };
//        }
    }

    public function lists()
    {
        $type = input('type', 0);
        if (empty($type)) {
            //判断是否购买模块 优先展示H5游戏
            if (is_buy_h5_issue()) {
                $c = new \app\issueh5\controller\AdminGameController();
            } elseif (is_buy_sy_issue()) {
                $c = new \app\issuesy\controller\AdminGameController();
            }elseif (is_buy_yy_issue()) {
                $c = new \app\issueyy\controller\AdminGameController();
            } else {
                $this -> error('联运分发功能不存在');
            }
        } else {
            //1: H5游戏  2:手游
            if ($type == '1') {
                $c = new \app\issueh5\controller\AdminGameController();
            } elseif ($type == '2') {
                $c = new \app\issuesy\controller\AdminGameController();
            }elseif ($type == '3') {
                $c = new \app\issueyy\controller\AdminGameController();
            } else {
                $this -> error('联运分发功能不存在');
            }
        }
        return $c -> lists();
    }

}