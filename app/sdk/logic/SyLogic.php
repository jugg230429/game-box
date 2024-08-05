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
        $data['game_type_name'] = get_game_type_name_str($data['game_type_id']);
        $data['screenshot'] = $data['screenshot'] ? implode(',', $data['screenshot']) : '';
        $data['create_time'] = time();
        $model = new GameModel();
        $model->startTrans();
        echo json_encode($data);
        exit();
        $result = $model->field(true)->insert($data);

        if ($result) {
            $id = $model->getLastInsID();
            // 是否需要付费下载
            if($data['down_port'] == 3){
                $d_time = time();
                Db::table('tab_game_ios_pay_to_download')->insert([
                    'game_id'=>$id,
                    'pay_download'=>$data['pay_download'] ?? 0,
                    'pay_price'=>$data['pay_price'] ?? 0.00,
                    'create_time'=>$d_time,
                    'update_time'=>$d_time,
                ]);
            }

            $save['relation_game_id'] = $id;
            $save['relation_game_name'] = $game_name;
            $model->where('id', $id)->update($save);
            $data['game_id'] = $id;
            $attrData = [
                'game_id' => $id,
                'xg_kf_url' => $data['xg_kf_url']??'',
                'sdk_type' => $data['sdk_type']??1,
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
            // 分成比例和注册单价生效时间
            if($data['ratio'] > 0){
                $attrData['ratio_begin_time'] = time();
            }
            if($data['money'] > 0){
                $attrData['money_begin_time'] = time();
            }
            Db::table('tab_game_attr')->insertGetId($attrData);//增加游戏关联表数据写入-20210816-byh


            $data['id'] = $id;
            // 获得手游的游戏key和访问秘钥并赋值
            $data['game_key'] = sp_random_string(16);
            $data['access_key'] = sp_random_string(15);

            //同步添加游戏设置表
            Db::table('tab_game_set')->field(true)->insert($data);
            write_action_log("新增游戏【" . $game_name . "】");
            $this->set_relation_tip($id,$game_name,$data['sdk_version']);
            $model->commit();
            return ['status'=>1,'game_id'=>$id];
        } else {
            $model->rollback();
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
        $data['game_type_name'] = get_game_type_name_str($data['game_type_id']);
        $data['screenshot'] = $data['screenshot'] ? implode(',', $data['screenshot']) : '';
        $model = new GameModel();
        $model->startTrans();
        $result = $model->field(true)->update($data);
        // 判断是否是需要付费下载
        if($data['down_port'] == 3){
            $d_time = time();
            $pay_download_info = Db::table('tab_game_ios_pay_to_download')->where(['game_id'=>$data['id']])->field('game_id,pay_download,pay_price')->find();
            if(empty($pay_download_info)){
                Db::table('tab_game_ios_pay_to_download')->insert([
                    'game_id'=>$data['id'],
                    'pay_download'=>$data['pay_download'],
                    'pay_price'=>$data['pay_price'],
                    'create_time'=>$d_time,
                    'update_time'=>$d_time,
                ]);
            }else{
                Db::table('tab_game_ios_pay_to_download')
                    ->where(['game_id'=>$data['id']])
                    ->update([
                        'game_id'=>$data['id'],
                        'pay_download'=>$data['pay_download'],
                        'pay_price'=>$data['pay_price'],
                        'create_time'=>$d_time,
                        'update_time'=>$d_time,
                    ]);
            }
        }

        //判断
        //先查询游戏关联表数据是否存在,存在则修改,不存在则写入-byh-s
        $res_attr = Db::table('tab_game_attr')->where('game_id',$data['id'])->count('id');
        $attrData = [
            'game_id' => $data['id'],
            'xg_kf_url' => $data['xg_kf_url']??'',
            'sdk_type' => $data['sdk_type']??1,
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
                $info['sdk_area'] = $data['sdk_area'];
                //$info['video_cover'] = $data['video_cover'];
                //$info['video'] = $data['video'];
                //$info['video_url'] = $data['video_url'];
                $model->field(true)->where('id', $another['id'])->update($info);
                // 关联游戏同步修改游戏扩展参数
                $another_attr = Db::table('tab_game_attr')->where('game_id',$another['id'])->count('id');
                $anotherAttr = [
                    'game_id' => $another['id'],
                    'discount_show_status'=>$data['discount_show_status']??1,
                    'coupon_show_status'=>$data['coupon_show_status']??1,
                ];
                if($another_attr>0){//存在
                    Db::table('tab_game_attr')->where('game_id',$another['id'])->update($anotherAttr);
                }else{//不存在
                    Db::table('tab_game_attr')->insertGetId($anotherAttr);
                }
                //清除缓存
                cmf_clear_cache();
            }
            write_action_log("编辑游戏【" . $game['relation_game_name'] . "】");
            $model->commit();
            return ['status'=>1];
        } else {
            $model->rollback();
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
        $data['game_type_name'] = get_game_type_name_str($data['game_type_id']);
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
                $attrData = [
                    'game_id' => $id,
                    'xg_kf_url' => $data['xg_kf_url']??'',
                    'sdk_type' => $data['sdk_type']??1,
                    'is_control' => $data['is_control']??0,
                    'bind_recharge_discount' => $data['bind_recharge_discount']??10,
                    'bind_continue_recharge_discount' => $data['bind_continue_recharge_discount']??10,
                    'promote_level_limit'=>$data['promote_level_limit']??0,
                    'promote_declare'=>$data['promote_declare']??'',
                    'discount_show_status'=>$data['discount_show_status']??1,
                    'coupon_show_status'=>$data['coupon_show_status']??1,
                ];
                Db::table('tab_game_attr')->insertGetId($attrData);//增加游戏关联表数据写入-20210816-byh
                $data['game_id'] = $id;
                $data['id'] = $id;
                // 获得手游的游戏key和访问秘钥并赋值
                $data['game_key'] = sp_random_string(16);
                $data['access_key'] = sp_random_string(15);
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
                return ['status' => 1, 'game_id'=>$id];
            } else {

                $model -> rollback();
                return ['status' => 0, 'msg' => '关联失败'];
            }
        } catch (\Exception $e) {
            $model -> rollback();
        }
    }
}
