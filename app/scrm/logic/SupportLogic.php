<?php

namespace app\scrm\logic;

use app\common\model\SupportModel;

class SupportLogic extends BaseLogic
{


    /**
     * @获取扶持数据
     *
     * @author: zsl
     * @since: 2021/8/5 11:41
     */
    public function lists($param)
    {

        try {
            //验证参数
            $page = empty($param['page']) ? '1' : $param['page'];
            $limit = empty($param['limit']) ? '10' : $param['limit'];
            $mSupport = new SupportModel();
            $field = "id as support_id,promote_id,game_id,game_name,user_id,apply_num,support_type,usable_num as useable_num,
            send_num,status,server_id,server_name,0 as game_player_id,role_name as game_player_name,remark,create_time as apply_time,
            audit_time,audit_idea";
            $where = [];
            if (!empty($param['promote_id'])) {
                if(is_array($param['promote_id'])){
                    $where['promote_id'] = ['in',$param['promote_id']];
                }else{
                    $where['promote_id'] = $param['promote_id'];
                }
            }
            if (!empty($param['support_id'])) {
                $where['id'] = $param['support_id'];
            }
            $lists = $mSupport -> field($field) -> where($where) -> paginate($limit, false, ['page' => $page]);
            if (empty($lists)) {
                $this -> data = $lists;
                return true;
            }
            $this -> data = $lists;
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }


    }


}
