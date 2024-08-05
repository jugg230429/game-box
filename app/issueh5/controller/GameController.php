<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-09
 */

namespace app\issueh5\controller;

use app\issueh5\logic\GameLogic;
use cmf\controller\HomeBaseController;
use think\WechatAuth;
use think\Db;

class GameController extends HomeBaseController
{
    public function play(GameLogic $logicGame)
    {
        $getData = request()->param();
        return $logicGame->play($getData);
    }

}