<?php

namespace app\btwelfare\controller;

use app\btwelfare\logic\BtPropLogic;
use app\btwelfare\logic\BtWelfareLogic;
use app\btwelfare\model\BtWelfareModel;
use cmf\controller\AdminBaseController;

class BtController extends AdminBaseController
{


    /**
     * @福利设置
     *
     * @author: zsl
     * @since: 2021/1/12 11:32
     */
    public function setting()
    {
        $param = $this -> request -> param();
        $lBtWelfare = new BtWelfareLogic();
        $lists = $lBtWelfare -> adminLists($param);
        $this -> assign('lists', $lists);
        // 获取分页显示
        $page = $lists->render();
        $this->assign("page", $page);
        return $this -> fetch();
    }

    /**
     * @设置福利
     *
     * @author: zsl
     * @since: 2021/1/12 16:22
     */
    public function add()
    {
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            $lBtWelfare = new BtWelfareLogic();
            $result = $lBtWelfare -> add($param);
            if ($result['code'] == 0) {
                $this -> error($result['msg']);
            }
            $this -> success($result['msg']);
        }
        return $this -> fetch();
    }


    /**
     * @编辑福利
     *
     * @author: zsl
     * @since: 2021/1/12 20:27
     */
    public function edit()
    {
        $param = $this -> request -> param();
        if ($this -> request -> isPost()) {
            $lBtWelfare = new BtWelfareLogic();
            $result = $lBtWelfare -> edit($param);
            if ($result['code'] == 0) {
                $this -> error($result['msg']);
            }
            $this -> success($result['msg'], url('btwelfare/bt/setting'));
        }
        //查询记录信息
        $mBtWelfare = new BtWelfareModel();
        $info = $mBtWelfare -> with('btWelfarePromote') -> where(['id' => $param['id']]) -> find();
        $this -> assign('info', $info);
        //查询已选择推广员
        $lists = array_flip(array_column($info['bt_welfare_promote'] -> toArray(), 'promote_id'));
        $this -> assign('lists', $lists);
        $map['status'] = 1;
        $promote_list = get_promote_list($map, "id,account,promote_level");
        $this -> assign('promote_list', $promote_list);
        $promote_choose = $this -> fetch("promote_choose");
        $this -> assign('promote_choose', $promote_choose);
        //查询游戏下所有道具
        $lProp = new BtPropLogic();
        $propLists = $lProp -> getPropLists(['game_id' => $info['game_id']]);
        $this -> assign('prop_lists', $propLists['data']);
        return $this -> fetch();
    }


    /**
     * 删除Bt福利
     *
     * @author: zsl
     * @since: 2021/1/13 10:23
     */
    public function del()
    {

        $id = $this -> request -> param('id');
        $lBtWelfare = new BtWelfareLogic();
        $result = $lBtWelfare -> del($id);
        if ($result['code'] == 0) {
            $this -> error($result['msg']);
        }
        $this -> success($result['msg'], url('btwelfare/bt/setting'));
    }


    /**
     * @获取推广员列表
     *
     * @author: zsl
     * @since: 2021/1/12 16:38
     */
    public function ajaxGetPromote()
    {
        if ($this -> request -> isAjax()) {
            $map['status'] = 1;
            $promote_list = get_promote_list($map, "id,account,promote_level");
            $this -> assign('promote_list', $promote_list);
            $choose = $this -> fetch("promote_choose");
            $this -> success($choose);
        }
    }


    /**
     * @修改状态
     *
     * @author: zsl
     * @since: 2021/1/13 10:36
     */
    public function changestatus()
    {

        $param = $this -> request -> param();
        $lBtWelfare = new BtWelfareLogic();
        $result = $lBtWelfare -> changeStatus($param);
        if ($result['code'] == 0) {
            $this -> error($result['msg']);
        }
        $this -> success($result['msg']);
    }

}