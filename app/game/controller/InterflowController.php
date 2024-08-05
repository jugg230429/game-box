<?php

namespace app\game\controller;

use app\game\logic\InterflowLogic;
use app\game\model\GameInterflowModel;
use cmf\controller\AdminBaseController;

class InterflowController extends AdminBaseController
{


    /**
     * @游戏互通列表
     *
     * @author: zsl
     * @since: 2021/8/27 9:39
     */
    public function lists()
    {

        $param = $this -> request -> param();
        $lInterflow = new InterflowLogic();
        $lists = $lInterflow -> adminLists($param) -> getData();
        $this -> assign('lists', $lists);
        return $this -> fetch();
    }


    /**
     * @添加游戏互通
     *
     * @author: zsl
     * @since: 2021/8/27 9:50
     */
    public function add()
    {
        $param = $this -> request -> param();
        $lInterflow = new InterflowLogic();
        if ($this -> request -> isPost()) {
            if (false === $lInterflow -> add($param)) {
                $this -> error($lInterflow -> getErrorMsg());
            }
            $this -> success('保存成功', url('game/interflow/lists'));
        }
        //获取已绑定游戏
        $lInterflow -> getGameLists($param);
        $this -> assign('game_lists', $lInterflow -> getData());
        return $this -> fetch();
    }


    /**
     * @追加互通游戏
     *
     * @author: zsl
     * @since: 2021/8/27 20:06
     */
    public function append()
    {
        $param = $this -> request -> param();
        $lInterflow = new InterflowLogic();
        if ($this -> request -> isPost()) {
            if (false === $lInterflow -> append($param)) {
                $this -> error($lInterflow -> getErrorMsg());
            }
            $this -> success('追加成功', url('game/interflow/lists'));
        }
        //获取已绑定游戏
        $lInterflow -> getGameLists($param);
        $this -> assign('game_lists', $lInterflow -> getData());
        return $this -> fetch();
    }


    /**
     * @删除游戏互通
     *
     * @author: zsl
     * @since: 2021/8/27 20:12
     */
    public function delete()
    {
        $param = $this -> request -> param();
        $where = [];
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        } elseif (!empty($param['interflow_tag'])) {
            $where['interflow_tag'] = $param['interflow_tag'];
        }
        $mInterflow = new GameInterflowModel();
        $result = $mInterflow -> where($where) -> delete();
        if (false === $result) {
            $this -> error('删除失败');
        }
        $this -> success('删除成功');
    }

}
