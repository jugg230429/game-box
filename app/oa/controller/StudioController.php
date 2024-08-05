<?php

namespace app\oa\controller;

use app\oa\logic\StudioLogic;
use app\oa\model\StudioModel;
use cmf\controller\AdminBaseController;

class StudioController extends AdminBaseController
{

    /**
     * @工作室列表
     *
     * @author: zsl
     * @since: 2021/3/1 13:42
     */
    public function lists()
    {
        $param = $this -> request -> param();
        $lStudio = new StudioLogic();
        $lists = $lStudio -> adminLists($param);
        $this -> assign('lists', $lists);
        // 获取分页显示
        $page = $lists -> render();
        $this -> assign("page", $page);
        return $this -> fetch();
    }


    /**
     * @添加工作室
     *
     * @author: zsl
     * @since: 2021/3/1 13:51
     */
    public function add()
    {
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            $lStudio = new StudioLogic();
            $result = $lStudio -> add($param);
            if ($result['code'] == '1') {
                $this -> success($result['msg'], url('studio/lists'));
            } else {
                $this -> error($result['msg']);
            }
        }
        return $this -> fetch();
    }


    /**
     * @编辑工作室
     *
     * @author: zsl
     * @since: 2021/3/1 14:11
     */
    public function edit()
    {

        $param = $this -> request -> param();
        if ($this -> request -> isPost()) {
            $lStudio = new StudioLogic();
            $result = $lStudio -> edit($param);
            if ($result['code'] == 0) {
                $this -> error($result['msg']);
            }
            $this -> success($result['msg'], url('studio/lists'));
        }
        //查询记录信息
        $mStudio = new StudioModel();
        $info = $mStudio -> where(['id' => $param['id']]) -> find();
        $this -> assign('info', $info);
        return $this -> fetch();
    }


    /**
     * @删除工作室
     *
     * @author: zsl
     * @since: 2021/3/1 17:28
     */
    public function del()
    {
        $id = $this -> request -> param('id');
        $lStudio = new StudioLogic();
        $result = $lStudio -> del($id);
        if ($result['code'] == 0) {
            $this -> error($result['msg']);
        }
        $this -> success($result['msg'], url('studio/lists'));
    }


}