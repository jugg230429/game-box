<?php

namespace app\issue\controller;

use app\issue\logic\DataLogic;
use cmf\controller\AdminBaseController;

class DataController extends AdminBaseController
{


    /**
     * @数据总览
     *
     * @author: zsl
     * @since: 2021/8/14 10:33
     */
    public function overview()
    {
        $param = $this -> request -> param();
        $lData = new DataLogic();
        $data = $lData -> overview($param);
        $this -> assign('data', $data);
        return $this -> fetch();
    }


    /**
     * @日报数据
     *
     * @author: zsl
     * @since: 2021/8/16 9:25
     */
    public function daily()
    {
        $param = $this -> request -> param();
        $lData = new DataLogic();
        $result = $lData -> daily($param);
        $this -> assign('lists', $result['lists']);
        $this -> assign('page', $result['page']);
        $this -> assign('total', $result['total']);
        return $this -> fetch();
    }


    /**
     * @日报数据(时)
     *
     * @author: zsl
     * @since: 2021/8/16 20:23
     */
    public function dailyHour()
    {
        $param = $this -> request -> param();
        $lData = new DataLogic();
        $result = $lData -> dailyHour($param);
        $this -> assign('lists', $result['lists']);
        $this -> assign('page', $result['page']);
        $this -> assign('total', $result['total']);
        return $this -> fetch();
    }


}
