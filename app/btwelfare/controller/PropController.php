<?php

namespace app\btwelfare\controller;

use app\btwelfare\logic\BtPropLogic;
use app\btwelfare\model\BtPropModel;
use cmf\controller\AdminBaseController;
use think\Request;

class PropController extends AdminBaseController
{

    /**
     * @道具管理
     *
     * @author: zsl
     * @since: 2021/1/13 15:43
     */
    public function lists()
    {

        $param = $this -> request -> param();
        $lBtProp = new BtPropLogic();
        $lists = $lBtProp -> adminLists($param);
        $this -> assign('lists', $lists);
        // 获取分页显示
        $page = $lists -> render();
        $this -> assign("page", $page);
        return $this -> fetch();

    }


    /**
     * @新增道具
     *
     * @author: zsl
     * @since: 2021/1/13 15:45
     */
    public function add()
    {

        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            $lBtProp = new BtPropLogic();
            $result = $lBtProp -> add($param);
            if ($result['code'] == 0) {
                $this -> error($result['msg']);
            }
            $this -> success($result['msg']);
        }
        return $this -> fetch();
    }


    /**
     * @编辑道具
     *
     * @author: zsl
     * @since: 2021/1/13 15:45
     */
    public function edit()
    {

        $param = $this -> request -> param();
        if ($this -> request -> isPost()) {
            $lBtProp = new BtPropLogic();
            $result = $lBtProp -> edit($param);
            if ($result['code'] == 0) {
                $this -> error($result['msg']);
            }
            $this -> success($result['msg'], url('btwelfare/prop/lists'));
        }
        //查询记录信息
        $mBtProp = new BtPropModel();
        $info = $mBtProp -> where(['id' => $param['id']]) -> find();
        $this -> assign('info', $info);
        return $this -> fetch();
    }


    /**
     * @删除道具
     *
     * @author: zsl
     * @since: 2021/1/13 15:46
     */
    public function del()
    {

        $id = $this -> request -> param('id');
        $lBtWelfare = new BtPropLogic();
        $result = $lBtWelfare -> del($id);
        if ($result['code'] == 0) {
            $this -> error($result['msg']);
        }
        $this -> success($result['msg']);

    }


    /**
     * @修改状态
     *
     * @author: zsl
     * @since: 2021/1/13 15:47
     */
    public function changeStatus()
    {
        $param = $this -> request -> param();
        $lBtProp = new BtPropLogic();
        $result = $lBtProp -> changeStatus($param);
        if ($result['code'] == 0) {
            $this -> error($result['msg']);
        }
        $this -> success($result['msg']);
    }


    /**
     * @获取游戏下道具列表
     *
     * @author: zsl
     * @since: 2021/1/13 17:54
     */
    public function getGameProp()
    {
        $param = $this -> request -> param();
        $lBtProp = new BtPropLogic();
        $result = $lBtProp -> getPropLists($param);
        return json($result);
    }


}