<?php

namespace app\btwelfare\controller;

use app\btwelfare\logic\MonthCardLogic;
use cmf\controller\AdminBaseController;

class MonthcardController extends AdminBaseController
{


    /**
     * @月卡列表
     *
     * @author: zsl
     * @since: 2021/1/18 9:40
     */
    public function lists()
    {
        $param = $this -> request -> param();
        $lRegister = new MonthCardLogic();
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
