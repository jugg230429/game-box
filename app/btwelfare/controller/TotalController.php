<?php

namespace app\btwelfare\controller;

use app\btwelfare\logic\TotalRechargeLogic;
use cmf\controller\AdminBaseController;

class TotalController extends AdminBaseController
{

    /**
     * @累计充值福利
     *
     * @author: zsl
     * @since: 2021/1/15 10:23
     */
    public function lists()
    {

        $param = $this -> request -> param();
        $lRecharge = new TotalRechargeLogic();
        $lists = $lRecharge -> adminLists($param);
        $this -> assign('lists', $lists);
        // 获取分页显示
        $page = $lists -> render();
        $this -> assign("page", $page);
        return $this -> fetch();
    }


}