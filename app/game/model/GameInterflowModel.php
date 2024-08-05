<?php

namespace app\game\model;

use think\Model;

class GameInterflowModel extends Model
{


    protected $table = 'tab_game_interflow';


    /**
     * @获取已绑定游戏id
     *
     * @author: zsl
     * @since: 2021/8/27 17:26
     */
    public function getGameIds($param)
    {
        //获取已关联游戏id
        $where = [];
//        if (!empty($param['interflow_tag'])) {
//            $where['interflow_tag'] = $param['interflow_tag'];
//        }
        $gameIds = $this -> where($where) -> column('game_id');
        return $gameIds;
    }

}
