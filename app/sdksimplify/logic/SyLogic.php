<?php

namespace app\sdk\logic;


use app\game\model\GameModel;
use app\game\validate\GameValidate;
use think\Db;

class SyLogic
{
    /**
     * @函数或方法说明
     * @添加游戏
     * @param array $data
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/6/10 16:00
     */
    public function add($data=[])
    {
        $validate = new GameValidate();
        if($data['down_port'] == 3){
            $data['ios_game_address'] = $data['super_address'];
            if(empty( $data['super_address'])){
                return ['status'=>0,'msg'=>'请输入超级签地址'];
            }
        }
        if (!$validate->check($data)) {
            return ['status'=>0,'msg'=>$validate->getError()];
        }
        $game_name = $data['game_name'];
        $data['tag_name'] = implode(',', array_filter([$data['tag_name_one'], $data['tag_name_two'], $data['tag_name_three']]));
        $data['game_name'] = $data['sdk_version'] == 1 ? $game_name . "(安卓版)" : $game_name . "(苹果版)";
        $data['recommend_status'] = $data['recommend_status'] ? implode(',', $data['recommend_status']) : 0;
        $data['game_type_name'] = get_game_type_name($data['game_type_id']);
        $data['screenshot'] = $data['screenshot'] ? implode(',', $data['screenshot']) : '';
        $data['create_time'] = time();
        $model = new GameModel();
        $result = $model->field(true)->insert($data);
        if ($result) {
            $id = $model->getLastInsID();
            $save['relation_game_id'] = $id;
            $save['relation_game_name'] = $game_name;
            $model->where('id', $id)->update($save);
            $data['game_id'] = $id;
            $data['id'] = $id;
            //同步添加游戏设置表
            Db::table('tab_game_set')->field(true)->insert($data);
            write_action_log("新增游戏【" . $game_name . "】");
            $this->set_relation_tip($id,$game_name,$data['sdk_version']);
            return ['status'=>1,'game_id'=>$id];
        } else {
            return ['status'=>0,'msg'=>'添加失败'];
        }
    }

    private function set_relation_tip($game_id,$source_game_name,$sdk_version)
    {
        if($sdk_version!=2){
            $game_name = $source_game_name.'(安卓版)';
            $relation_gmae_name = $source_game_name.'(苹果版)';
        }else{
            $game_name = $source_game_name.'(苹果版)';
            $relation_gmae_name = $source_game_name.'(安卓版)';
        }
        cookie('relation_tip',1);
        cookie('relation_tip_game',$game_name);
        cookie('relation_tip_game_id',$game_id);
        cookie('relation_tip_game_another',$relation_gmae_name);
    }

    /**
     * @函数或方法说明
     * @编辑游戏
     * @author: 郭家屯
     * @since: 2020/6/10 16:00
     */
    public function edit($data=[])
    {
        unset($data['game_name']);
        unset($data['game_appid']);
        unset($data['sdk_version']);
        if($data['down_port'] == 3){
            $data['ios_game_address'] = $data['super_address'];
            if(empty( $data['super_address'])){
                return ['status'=>0,'msg'=>'请输入超级签地址'];
            }
        }
        $validate = new GameValidate();
        if (!$validate->scene('edit')->check($data)) {
            return ['status'=>0,'msg'=>$validate->getError()];
        }
        $data['tag_name'] = implode(',', array_filter([$data['tag_name_one'], $data['tag_name_two'], $data['tag_name_three']]));
        $data['recommend_status'] = $data['recommend_status'] ? implode(',', $data['recommend_status']) : 0;
        $data['game_type_name'] = get_game_type_name($data['game_type_id']);
        $data['screenshot'] = $data['screenshot'] ? implode(',', $data['screenshot']) : '';
        $model = new GameModel();
        $result = $model->field(true)->update($data);
        if ($result !== false) {
            //同步更新游戏设置表
            Db::table('tab_game_set')->field(true)->update($data);
            //同步关联信息
            //获取当前游戏信息
            $game = $model->field('id,relation_game_id,relation_game_name')->where('id', $data['id'])->find()->toArray();
            //获取关联版本信息
            $map['relation_game_id'] = $game['relation_game_id'];
            $map['id'] = array('neq', $game['id']);
            $another = $model->where($map)->find();  //获取另一个所有
            if ($another) {
                $another = $another->toArray();
                $info['game_type_id'] = $data['game_type_id'];
                $info['tag_name'] = $data['tag_name'];
                $info['dow_num'] = $data['dow_num'];
                $info['game_type_name'] = $data['game_type_name'];
                $info['recommend_status'] = $data['recommend_status'];
                $info['sort'] = $data['sort'];
                $info['game_score'] = $data['game_score'];
                $info['features'] = $data['features'];
                $info['introduction'] = $data['introduction'];
                $info['icon'] = $data['icon'];
                $info['cover'] = $data['cover'];
                $info['hot_cover'] = $data['hot_cover'];
                $info['screenshot'] = $data['screenshot'];
                $info['material_url'] = $data['material_url'];
                $info['dev_name'] = $data['dev_name'];
                $info['groom'] = $data['groom'];
                $info['back_describe'] = $data['back_describe'];
                $info['dow_icon'] = $data['dow_icon'];
                $info['back_map'] = $data['back_map'];
                //$info['video_cover'] = $data['video_cover'];
                //$info['video'] = $data['video'];
                //$info['video_url'] = $data['video_url'];
                $model->field(true)->where('id', $another['id'])->update($info);
                //清除缓存
                cmf_clear_cache();
            }
            write_action_log("编辑游戏【" . $game['relation_game_name'] . "】");
            return ['status'=>1];
        } else {
            return ['status'=>0,'msg'=>'编辑失败'];
        }
    }

    /**
     * @函数或方法说明
     * @关联游戏
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/6/10 16:04
     */
    public function relation($data = [])
    {
        $validate = new GameValidate();
        $game_id = $data['id'];
        unset($data['id']);
        if ($data['down_port'] == 3) {
            $data['ios_game_address'] = $data['super_address'];
            if (empty($data['super_address'])) {
                return ['status' => 0, 'msg' => '请输入超级签地址'];
            }
        }
        $data['is_guanlian'] = 1;
        if (!$validate -> check($data)) {
            return ['status' => 0, 'msg' => $validate -> getError()];
        }
        $game_name = $data['game_name'];
        $data['game_name'] = $data['sdk_version'] == 1 ? $game_name . "(安卓版)" : $game_name . "(苹果版)";
        $data['recommend_status'] = $data['recommend_status'] ? implode(',', $data['recommend_status']) : 0;
        $data['game_type_name'] = get_game_type_name($data['game_type_id']);
        $data['screenshot'] = $data['screenshot'] ? implode(',', $data['screenshot']) : '';
        $data['relation_game_id'] = $game_id;
        $data['relation_game_name'] = $game_name;
        $data['create_time'] = time();
        $model = new GameModel();
        $model -> startTrans();
        try {
            $has_game = $model -> field('id') -> where('sdk_version', $data['sdk_version']) -> where('relation_game_id', $game_id) -> find();
            if ($has_game)
                return ['status' => 0, 'msg' => '游戏已关联'];
            $result = $model -> field(true) -> insert($data);
            if ($result) {
                //同步添加游戏设置表
                $id = $model -> getLastInsID();
                $data['game_id'] = $id;
                $data['id'] = $id;
                Db ::table('tab_game_set') -> field(true) -> insert($data);
                //同步关联信息
                $info['game_type_id'] = $data['game_type_id'];
                $info['dow_num'] = $data['dow_num'];
                $info['game_type_name'] = $data['game_type_name'];
                $info['recommend_status'] = $data['recommend_status'];
                $info['sort'] = $data['sort'];
                $info['game_score'] = $data['game_score'];
                $info['features'] = $data['features'];
                $info['introduction'] = $data['introduction'];
                $info['icon'] = $data['icon'];
                $info['cover'] = $data['cover'];
                $info['hot_cover'] = $data['hot_cover'];
                $info['screenshot'] = $data['screenshot'];
                $info['material_url'] = $data['material_url'];
                $info['dev_name'] = $data['dev_name'];
                $info['back_describe'] = $data['back_describe'];
                $info['dow_icon'] = empty($data['dow_icon']) ? '' : $data['dow_icon'];
                $info['back_map'] = $data['back_map'];
                //$info['video_cover'] = $data['video_cover'];
                //$info['video'] = $data['video'];
                //$info['video_url'] = $data['video_url'];
                $model -> field(true) -> where('id', $game_id) -> update($info);
                $model -> commit();
                //清除缓存
                cmf_clear_cache();
                return ['status' => 1];
            } else {

                $model -> rollback();
                return ['status' => 0, 'msg' => '关联失败'];
            }
        } catch (\Exception $e) {
            $model -> rollback();
        }
    }
}
