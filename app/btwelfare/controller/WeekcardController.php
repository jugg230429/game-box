<?php

namespace app\btwelfare\controller;

use app\btwelfare\logic\WeekCardLogic;
use cmf\controller\AdminBaseController;

class WeekcardController extends AdminBaseController
{


    /**
     * @周卡列表
     *
     * @author: zsl
     * @since: 2021/1/15 16:07
     */
    public function lists()
    {
        $param = $this -> request -> param();
        $lRegister = new WeekCardLogic();
        $result = $lRegister -> adminLists($param, true);
        $this -> assign('lists', $result['lists']);
        // 获取分页显示
        $page = $result['lists'] -> render();
        $this -> assign("page", $page);
        //汇总数据
        $this -> assign('res_total', $result['res_total']);
        return $this -> fetch();
    }



}