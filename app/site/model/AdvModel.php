<?php

namespace app\site\model;

use think\Model;

/**
 * gjt
 */
class AdvModel extends Model
{

    protected $table = 'tab_adv';

    protected $autoWriteTimestamp = true;

    /**
     * [获取广告列表]
     * @param string $type
     * @return array|false|\PDOStatement|string|\think\Collection|Model
     * @author 郭家屯[gjt]
     */
    public function getAdv($type = '',$promote_id=0)
    {
        $model = new AdvposModel();
        $pos = $model->where('name', $type)->find()->toArray();
        // var_dump($pos);exit;

        $map['pos_id'] = $pos['id'];
        if($promote_id > 0){
            $game_ids = get_promote_game_id($promote_id);
        }
        $test_game_ids = get_test_game_ids(true);
        if ($pos['type'] == 1) {
            $list = $this
                    ->field('title,data,url,target,icon,type,game_id')
                    ->where($map)
                    ->where('start_time', ['<', time()], ['=', 0], 'or')
                    ->where('end_time', ['>', time()], ['=', 0], 'or')
                    ->where('game_id',['=',0],['not in',$test_game_ids],'or')
                    ->order('sort desc,id desc')
                    ->find();
            $list = $list ? $list->toArray() : [];
            if($list){
                $list['data'] = cmf_get_image_url($list['data']);
                if($list['type'] == 1 && $promote_id > 0 && !in_array($list['game_id'],$game_ids)){
                    $list = [];
                }elseif ($list['type'] == 1){
                    $game = get_game_entity($list['game_id'],'game_status,relation_game_id,sdk_version');
                    if($game['game_status'] == 0){
                        $list = [];
                    }else{
                        if($game['sdk_version'] == 4){
                            $list['url'] = url('game/ydetail',['game_id'=>$game['relation_game_id']],true,true);
                        }elseif($game['sdk_version'] == 3){
                            if(cmf_is_mobile()){
                                $list['url'] = url('game/detail',['game_id'=>$game['relation_game_id']],true,true);
                            }else{
                                $list['url'] = url('game/hdetail',['game_id'=>$game['relation_game_id']],true,true);
                            }
                        }else{
                            $list['url'] = url('game/detail',['game_id'=>$game['relation_game_id']],true,true);
                        }
                        $list['sdk_version'] = $game['sdk_version'];
                    }
                }
            }
        } else {
            $list = $this
                    ->field('title,data,url,target,icon,type,game_id')
                    ->where($map)
                    ->where('start_time', ['<', time()], ['=', 0], 'or')
                    ->where('end_time', ['>', time()], ['=', 0], 'or')
                    ->where('game_id',['=',0],['not in',$test_game_ids],'or')
                    ->order('sort desc,id desc')
                    ->select()->toArray();
            foreach($list as $key=>$v) {
                $list[$key]['data'] = cmf_get_image_url($v['data']);
                if($v['type'] == 1 && $promote_id > 0 && !in_array($v['game_id'],$game_ids)){
                    unset($list[$key]);
                }elseif ($v['type'] == 1){
                    $game = get_game_entity($v['game_id'],'game_status,relation_game_name,relation_game_id,features,icon,sdk_version');

                    if($game['game_status'] == 0){
                        unset($list[$key]);
                    }else{
                        $list[$key]['relation_game_name'] = $game['relation_game_name'];
                        $list[$key]['features'] = $game['features'];
                        $list[$key]['game_icon'] = $game['icon'];
                        $list[$key]['sdk_version'] = $game['sdk_version'];
                        if($game['sdk_version'] == 4){
                            $list[$key]['url'] = url('game/ydetail',['game_id'=>$game['relation_game_id']],true,true);
                        }elseif($game['sdk_version'] == 3){
                            if(cmf_is_mobile()){
                                $list[$key]['url'] = url('game/detail',['game_id'=>$game['relation_game_id']],true,true);
                            }else{
                                $list[$key]['url'] = url('game/hdetail',['game_id'=>$game['relation_game_id']],true,true);
                            }
                        }else{
                            $list[$key]['url'] = url('game/detail',['game_id'=>$game['relation_game_id']],true,true);
                        }

                    }
                }
            }
            $list = $list ? array_values($list) : [];
        }
        return $list;
    }
}
