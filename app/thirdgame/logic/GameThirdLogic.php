<?php

namespace app\thirdgame\logic;

use app\common\controller\BaseController;
use app\thirdgame\model\GameModel;
use app\game\model\GameTypeModel;
use app\thirdgame\model\GameThridModel;
use app\thirdgame\model\PlatformModel;

class GameThirdLogic
{
    /**
     * @第三方游戏列表
     * @author: Juncl
     * @time: 2021/08/11 20:18
     */
    public function lists($map = []){
         $model = new GameModel();
         $base = new BaseController();
         $exend['field'] = 'tab_game.id,game_name,tab_game.icon,game_type_name,sdk_version,game_appid,recommend_status,game_status,dow_num,tab_game.sort,relation_game_id,relation_game_name,promote_ids,cp_id,third_game_id,promote_declare';
        //排序优先，ID在后
         $exend['order'] = 'tab_game.sort desc,id desc';
        //关联游戏类型表
         $exend['join1'] = [['tab_game_attr' => 't'], 'tab_game.id=t.game_id', 'left'];
         $exend['join2'] = [['tab_game_third' => 'd'], 'tab_game.third_game_id=d.id', 'left'];
         $status_arr =  ['0' => '已下架', '1' => '上架中'];
         $data = $base->data_list_join($model, $map, $exend)->each(function ($item, $key) use($status_arr) {
            $recommend = explode(',', $item['recommend_status']);
            $recommend_status = '';
            foreach ($recommend as $kk => $vo) {
                $recommend_status .= " " . get_info_status($vo, 7);
            }
            $item['recommend_status'] = $recommend_status;
            $item['relation_status'] = get_relation_game($item['id'], $item['relation_game_id']) === -1 ?: (get_relation_game($item['id'], $item['relation_game_id']) ? 1 : 0);
            $item['status_name'] = $status_arr[$item['game_status']];
            if($item['promote_ids']){
                $item['promote_ids'] = count(array_filter(explode(',',$item['promote_ids'])));
            }else{
                $item['promote_ids'] = 0;
            }
            return $item;
         });
         return $data?:[];
    }
    /**
     * @根据平台获取所有第三方游戏ID
     * @param int $platform
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: Juncl
     * @time: 2021/08/11 19:54
     */
     public function get_platform_games($platform=0)
     {
         $gamethirdModel = new GameThridModel();
         $platModel = new PlatformModel();
         $map['id'] = $platform;
         $map['status'] = 1;
         $platform_info = $platModel->get_platform($map);
         //平台不存在
         if(empty($platform_info)){
             return [0];
         }
         $gmap['platform_id'] = $platform_info[0]['id'];
         $game_lists = $gamethirdModel->get_game_lists($gmap,'id');
         if(empty($game_lists)){
             return [0];
         }
         $game_lists = array_column($game_lists,'id');
         return $game_lists;
     }

     public function getGameType()
     {
          $model = new GameTypeModel();
          $data = $model->field('id,type_name')->where('status', 1)->select();
          return $data?:[];
     }

     public function setGameField($map=[],$data=[])
     {
         $model = new GameModel();
         $result = $model->where($map)->update($data);
         return $result;
     }
}