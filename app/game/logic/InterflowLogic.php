<?php

namespace app\game\logic;

use app\game\model\GameInterflowModel;
use app\game\model\GameModel;

class InterflowLogic extends BaseLogic
{


    /**
     * @互通游戏后台列表
     *
     * @author: zsl
     * @since: 2021/8/27 10:04
     */
    public function adminLists($param)
    {
        $mInterflow = new GameInterflowModel();
        $field = "group_concat(game_id) as game_ids,interflow_tag";
        $where = [];
        if (!empty($param['game_id'])) {
            $interflow_tag = $mInterflow -> where(['game_id' => $param['game_id']]) -> value('interflow_tag');
            $where['interflow_tag'] = $interflow_tag;
        }
        $lists = $mInterflow -> field($field) -> where($where) -> group('interflow_tag') -> order('id desc') -> paginate(15);
        $this -> data = $lists;
        return $this;
    }


    /**
     * @新增游戏互通
     *
     * @author: zsl
     * @since: 2021/8/27 16:59
     */
    public function add($param)
    {
        try {

            //验证参数
            if (empty($param['game_ids'])) {
                $this -> errorMsg = '请选择需要绑定的游戏';
                return false;
            }
            if (count($param['game_ids']) < 2) {
                $this -> errorMsg = '请至少选择两个游戏';
                return false;
            }
            //写入关联数据
            $gameIds = array_unique($param['game_ids']);
            $interflow_tag = md5(uniqid());
            $insData = [];
            foreach ($gameIds as $k => $gameId) {
                $insData[$k]['game_id'] = $gameId;
                $insData[$k]['interflow_tag'] = $interflow_tag;
            }
            $mInterflow = new GameInterflowModel();
            $result = $mInterflow -> saveAll($insData);
            if (false === $result) {
                $this -> errorMsg = '保存失败,请重试';
                return false;
            }
            return true;
        } catch (\Exception $e) {

            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }


    /**
     * @获取未绑定游戏列表
     *
     * @author: zsl
     * @since: 2021/8/27 17:26
     */
    public function getGameLists($param)
    {
        $mGameInterflow = new GameInterflowModel();
        $gameIds = $mGameInterflow -> getGameIds($param);
        //获取游戏列表
        $mGame = new GameModel();
        $field = 'id,game_name';
        $where = [];
        $where['id'] = ['not in', $gameIds];
        $gameLists = $mGame -> field($field) -> where($where) -> select();
        $this -> data = $gameLists;
        return true;
    }


    /**
     * @追加游戏
     *
     * @author: zsl
     * @since: 2021/8/30 9:06
     */
    public function append($param)
    {
        try {
            //验证参数
            if (empty($param['game_ids'])) {
                $this -> errorMsg = '请选择需要绑定的游戏';
                return false;
            }
            //写入关联数据
            $gameIds = array_unique($param['game_ids']);
            $interflow_tag = $param['interflow_tag'];
            $insData = [];
            foreach ($gameIds as $k => $gameId) {
                $insData[$k]['game_id'] = $gameId;
                $insData[$k]['interflow_tag'] = $interflow_tag;
            }
            $mInterflow = new GameInterflowModel();
            $result = $mInterflow -> saveAll($insData);
            if (false === $result) {
                $this -> errorMsg = '保存失败,请重试';
                return false;
            }
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }


    /**
     * @获取互通游戏ids
     *
     * @author: zsl
     * @since: 2021/8/30 10:48
     */
    public function getInterflowGameIds($param)
    {
        $mInterflow = new GameInterflowModel();
        $interflowTag = $mInterflow -> where(['game_id' => $param['game_id']]) -> value('interflow_tag');
        $gameIds = $mInterflow -> where(['interflow_tag' => $interflowTag]) -> column('game_id');
        return $gameIds;
    }


}
