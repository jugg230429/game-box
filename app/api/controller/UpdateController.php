<?php

namespace app\api\controller;

use app\game\model\GameAttrModel;
use app\game\model\GameInterflowModel;
use app\game\model\GameModel;
use cmf\controller\HomeBaseController;

class UpdateController extends HomeBaseController
{


    /**
     * @960升级脚本
     *
     * @game表中数据转移到game_attr
     *
     * @author: zsl
     * @since: 2021/9/18 14:33
     */
    public function update960()
    {
        $mGame = new GameModel();
        $mAttr = new GameAttrModel();
        $gameLists = $mGame -> field('id as game_id,issue,sue_pay_type,xg_kf_url,sdk_type,bind_recharge_discount,discount') -> select() -> toArray();
        $res = $mAttr -> saveAll($gameLists);
        if (false === $res) {
            echo 'error';
        } else {
            echo 'success';
        }
    }


    /**
     * @查询关联游戏添加到互通游戏表中
     *
     * @since: 2021/9/18 15:13
     * @author: zsl
     */
    public function update960_2()
    {
        $mGame = new GameModel();
        $mInterflow = new GameInterflowModel();
        $lists = $mGame -> field('id,GROUP_CONCAT(id) as id,count(*) as id_count') -> group('relation_game_id') -> having('id_count > 1') -> select();
        $data = [];
        foreach ($lists as $k => $v) {
            $ids = explode(',', $v['id']);
            $interflow_tag = md5(uniqid());
            foreach ($ids as $key => $id) {
                $data[] = [
                        'game_id' => $id,
                        'interflow_tag' => $interflow_tag,
                ];
            }
        }
        $res = $mInterflow -> saveAll($data);
        if (false === $res) {
            echo 'error';
        } else {
            echo 'success';
        }
    }


}
