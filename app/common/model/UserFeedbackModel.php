<?php

namespace app\common\model;

use think\Model;

class UserFeedbackModel extends Model
{


    protected $table = 'tab_user_feedback';

    protected $autoWriteTimestamp = true;


    /**
     * @提交反馈
     *
     * @author: zsl
     * @since: 2021/4/21 11:47
     */
    public function addFeedback($param)
    {
        if (empty($param['user_id'])) {
            return false;
        }
        $param['images'] = empty($param['images']) ? [] : $param['images'];
        $this -> user_id = $param['user_id'];
        $this -> user_account = get_user_entity($param['user_id'])['account'];
        $this -> game_id = $param['game_id'];
        $this -> game_name = get_game_name($param['game_id']);
        $this -> qq = $param['qq'];
        $this -> tel = $param['tel'];
        $this -> report_type = json_encode($param['report_type'],JSON_UNESCAPED_UNICODE);
        $this -> remark = $param['remark'];
        $this -> mobile_type = get_phone_brand();
        $this -> images = json_encode($param['images'],JSON_UNESCAPED_UNICODE);
        $res = $this -> isUpdate(false) -> save();
        return $res;
    }


}
