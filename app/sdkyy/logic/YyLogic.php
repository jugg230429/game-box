<?php

namespace app\sdkyy\logic;

use app\game\model\GameModel;
use app\game\validate\GameValidate2;
use think\Db;
class YyLogic
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
        $data['sdk_version'] = 4;
        $validate = new GameValidate2();
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
        $model -> startTrans();
        try {
            $result = $model -> field(true) -> insert($data);
            if ($result) {
                $id = $model -> getLastInsID();
                $gameInfo = $model -> find($id);
                $gameInfo -> relation_game_id = $id;
                $gameInfo -> relation_game_name = $game_name;
                $gameInfo -> isUpdate() -> save();
                write_action_log("新增游戏【" . $game_name . "】");
                //更新关联数据
                $info = $model ::get($id);
                $attrData = [];
                $attrData['game_id'] = $id;
                $attrData['issue'] = $data['issue'];
                $attrData['sue_pay_type'] = $data['sue_pay_type'];
                $attrData['xg_kf_url'] = $data['xg_kf_url']??'';//溪谷客服
                $attrData['sdk_type'] = $data['sdk_type']??1;//SDK类型
                $attrData['is_control'] = $data['is_control']??0;//是否严控
                $attrData['bind_recharge_discount'] = $data['bind_recharge_discount']??10;
                $attrData['bind_continue_recharge_discount'] = $data['bind_continue_recharge_discount']??10;
                $attrData['discount'] = $data['discount']??10;
                $attrData['continue_discount'] = $data['continue_discount']??10;
                $attrData['support_introduction'] = $data['support_introduction']??'';
                $attrData['promote_level_limit'] = $data['promote_level_limit']??'';
                $attrData['promote_declare'] = $data['promote_declare']??'';
                $attrData['discount_show_status'] = $data['discount_show_status']??'';
                $attrData['coupon_show_status'] = $data['coupon_show_status']??'';
                if($data['ratio'] > 0){
                    $attrData['ratio_begin_time'] = time();
                }
                if($data['money'] > 0){
                    $attrData['money_begin_time'] = time();
                }
                $info -> gameAttr() -> save($attrData);
                $model -> commit();
                return ['status' => 1, 'game_id' => $id];
            } else {
                $model -> rollback();
                return ['status' => 0, 'msg' => '添加失败'];
            }

        } catch (\Exception $e) {
            $model -> rollback();
            return ['status' => 0, 'msg' => $e -> getMessage()];
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
        $validate = new GameValidate2();
        if (!$validate->scene('edit')->check($data)) {
            return ['status'=>0,'msg'=>$validate->getError()];
        }
        $data['tag_name'] = implode(',', array_filter([$data['tag_name_one'], $data['tag_name_two'], $data['tag_name_three']]));
        $data['recommend_status'] = $data['recommend_status'] ? implode(',', $data['recommend_status']) : 0;
        $data['game_type_name'] = get_game_type_name_str($data['game_type_id']);
        $data['screenshot'] = $data['screenshot'] ? implode(',', $data['screenshot']) : '';
        $model = new GameModel();
        $model->startTrans();

        try {
            $result = $model -> field(true) -> update($data);
            if ($result !== false) {
                $game = $model -> field('id,game_name') -> where('id', $data['id']) -> find() -> toArray();
                write_action_log("编辑游戏【" . $game['game_name'] . "】");
                //更新关联表
                $info = $model ::get($data['id']);
                $info -> gameAttr -> issue = $data['issue'];
                $info -> gameAttr -> sue_pay_type = $data['sue_pay_type'];
                $info -> gameAttr -> xg_kf_url = $data['xg_kf_url']??'';
                $info -> gameAttr -> sdk_type = $data['sdk_type']??1;
                $info -> gameAttr -> is_control = $data['is_control']??0;
                $info -> gameAttr -> bind_recharge_discount = $data['bind_recharge_discount']??10;
                $info -> gameAttr -> bind_continue_recharge_discount = $data['bind_continue_recharge_discount']??10;
                $info -> gameAttr -> discount = $data['discount']??10;
                $info -> gameAttr -> continue_discount = $data['continue_discount']??10;
                $info -> gameAttr -> support_introduction = $data['support_introduction']??'';
                $info -> gameAttr -> promote_level_limit = $data['promote_level_limit']??0;
                $info -> gameAttr -> promote_declare = $data['promote_declare']??'';
                $info -> gameAttr -> discount_show_status = $data['discount_show_status']??1;
                $info -> gameAttr -> coupon_show_status = $data['coupon_show_status']??1;
                if($data['old_ratio'] != $data['ratio']){
                    $info -> gameAttr -> ratio_begin_time = time();
                }
                if($data['old_money'] != $data['money']){
                    $info -> gameAttr -> money_begin_time = time();
                }
                $info -> gameAttr -> save();
                $model -> commit();
                return ['status' => 1];
            } else {
                $model -> rollback();
                return ['status' => 0, 'msg' => '编辑失败'];
            }
        } catch (\Exception $e) {

            $model -> rollback();
            return ['status' => 0, 'msg' => $e -> getMessage()];

        }


    }


}
