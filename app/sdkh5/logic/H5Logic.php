<?php

namespace app\sdkh5\logic;

use app\game\model\GameModel;
use app\game\validate\GameValidate1;
use think\Db;
class H5Logic
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
        $data['sdk_version'] = 3;
        $validate = new GameValidate1();
        if (!$validate->check($data)) {
            return ['status'=>0,'msg'=>$validate->getError()];
        }
        $game_name = $data['game_name'];
        $data['tag_name'] = implode(',', array_filter([$data['tag_name_one'], $data['tag_name_two'], $data['tag_name_three']]));
        $data['recommend_status'] = $data['recommend_status'] ? implode(',', $data['recommend_status']) : 0;
        $data['game_type_name'] = get_game_type_name_str($data['game_type_id']);
        $data['screenshot'] = $data['screenshot'] ? implode(',', $data['screenshot']) : '';
        $data['create_time'] = time();
        $model = new GameModel();
        $model->startTrans();
        $result = $model->field(true)->insert($data);
        if ($result) {
            $id = $model->getLastInsID();

            $attrData = [
                'game_id' => $id,
                'xg_kf_url' => $data['xg_kf_url']??'',
                'is_control' => $data['is_control']??0,
                'bind_recharge_discount' => $data['bind_recharge_discount']??10,
                'bind_continue_recharge_discount' => $data['bind_continue_recharge_discount']??10,
                'discount' => $data['discount']??10,
                'continue_discount' => $data['continue_discount']??10,
                'support_introduction' => $data['support_introduction'] ?? '',
                'promote_level_limit'=>$data['promote_level_limit']??0,
                'promote_declare'=>$data['promote_declare']??'',
                'discount_show_status'=>$data['discount_show_status']??1,
                'coupon_show_status'=>$data['coupon_show_status']??1,
            ];
            if($data['ratio'] > 0){
                $attrData['ratio_begin_time'] = time();
            }
            if($data['money'] > 0){
                $attrData['money_begin_time'] = time();
            }
            Db::table('tab_game_attr')->insertGetId($attrData);//增加游戏关联表数据写入-20210816-byh

            $save['relation_game_id'] = $id;
            $save['relation_game_name'] = $game_name;
            $model->where('id', $id)->update($save);
            $data['game_id'] = $id;
            $data['id'] = $id;
            // 获得H5的游戏key并赋值
            $data['game_key'] = sp_random_string(15);
            //同步添加游戏设置表
            Db::table('tab_game_set')->field(true)->insert($data);
            write_action_log("新增游戏【" . $game_name . "】");
            $model->commit();
            return ['status'=>1,'game_id'=>$id];
        } else {
            $model->rollback();
            return ['status'=>0,'msg'=>'添加失败'];
        }
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
        $validate = new GameValidate1();
        if (!$validate->scene('edit')->check($data)) {
            return ['status'=>0,'msg'=>$validate->getError()];
        }
        $data['tag_name'] = implode(',', array_filter([$data['tag_name_one'], $data['tag_name_two'], $data['tag_name_three']]));
        $data['recommend_status'] = $data['recommend_status'] ? implode(',', $data['recommend_status']) : 0;
        $data['game_type_name'] = get_game_type_name_str($data['game_type_id']);
        $data['screenshot'] = $data['screenshot'] ? implode(',', $data['screenshot']) : '';
        $model = new GameModel();
        $model->startTrans();
        $result = $model->field(true)->update($data);
        //先查询游戏关联表数据是否存在,存在则修改,不存在则写入-byh-s
        $res_attr = Db::table('tab_game_attr')->where('game_id',$data['id'])->count('id');
        $attrData = [
            'game_id' => $data['id'],
            'xg_kf_url' => $data['xg_kf_url']??'',
            'is_control' => $data['is_control']??0,
            'bind_recharge_discount' => $data['bind_recharge_discount']??10,
            'bind_continue_recharge_discount' => $data['bind_continue_recharge_discount']??10,
            'discount' => $data['discount']??10,
            'continue_discount' => $data['continue_discount']??10,
            'support_introduction' => $data['support_introduction']??'',
            'promote_level_limit'=>$data['promote_level_limit']??0,
            'promote_declare'=>$data['promote_declare']??'',
            'discount_show_status'=>$data['discount_show_status']??1,
            'coupon_show_status'=>$data['coupon_show_status']??1,
        ];
        if($data['old_ratio'] != $data['ratio']){
            $attrData['ratio_begin_time'] = time();
        }
        if($data['old_money'] != $data['money']){
            $attrData['money_begin_time'] = time();
        }
        if($res_attr>0){//存在
            Db::table('tab_game_attr')->where('game_id',$data['id'])->update($attrData);
        }else{//不存在
            Db::table('tab_game_attr')->insertGetId($attrData);
        }
        //先查询游戏关联表数据是否存在,存在则修改,不存在则写入-byh-e
        if ($result !== false) {
            //同步更新游戏设置表
            Db::table('tab_game_set')->field(true)->update($data);
            $game = $model->field('id,game_name')->where('id', $data['id'])->find()->toArray();
            write_action_log("编辑游戏【" . $game['game_name'] . "】");
            $model->commit();
            return ['status'=>1];
        } else {
            $model->rollback();
            return ['status'=>0,'msg'=>'编辑失败'];
        }
    }


}
