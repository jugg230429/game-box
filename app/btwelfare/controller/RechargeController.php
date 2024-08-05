<?php

namespace app\btwelfare\controller;

use app\btwelfare\logic\RechargeLogic;
use cmf\controller\AdminBaseController;

class RechargeController extends AdminBaseController
{


    /**
     * @注册福利列表
     *
     * @author: zsl
     * @since: 2021/1/14 17:17
     */
    public function lists()
    {
        $param = $this -> request -> param();
        $lRecharge = new RechargeLogic();
        $lists = $lRecharge -> adminLists($param);
        $this -> assign('lists', $lists);
        // 获取分页显示
        $page = $lists -> render();
        $this -> assign("page", $page);
        return $this -> fetch();
    }


}