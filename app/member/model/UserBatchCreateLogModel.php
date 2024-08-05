<?php

namespace app\member\model;

use think\Model;

class UserBatchCreateLogModel extends Model
{


    protected $table = 'tab_user_batch_create_log';

    protected $autoWriteTimestamp = true;


    /**
     * @新增日志
     *
     * @author: zsl
     * @since: 2021/7/10 9:18
     */
    public function createLog($param)
    {
        $this -> create_number = $param['create_number'];
        $this -> password = $param['password'];
        $this -> promote_id = $param['promote_id'];
        $this -> promote_account = get_promote_name($param['promote_id']);
        $this -> game_id = $param['game_id'];
        $this -> game_name = get_game_name($param['game_id']);
        $res = $this -> save();
        return $res;
    }


    /**
     * @批量创建
     *
     * @author: zsl
     * @since: 2021/7/10 9:33
     */
    public function batchCreate($param)
    {
        $this -> startTrans();
        try {
            for ($i = 0; $i < $param['create_number']; $i ++) {
                //批量生成用户
                $res = $this -> _createUser($param);
                if (false === $res) {
                    $this -> rollback();
                    return false;
                }
            }
            $this -> commit();
            return true;
        } catch (\Exception $e) {
            $this -> rollback();
            return false;
        }
    }

    private function _createUser($param)
    {
        $mUser = new UserModel();
        $account = 'SC_' . cmf_random_string(6);
        $where = [];
        $where['account'] = $account;
        while ($mUser -> where($where) -> count()) {
            $account = 'SC_' . cmf_random_string(6);
        }
        $mUser -> account = $account;
        $mUser -> password = cmf_password($param['password']);
        $mUser -> promote_id = $param['promote_id'];
        $mUser -> promote_account = get_promote_name($param['promote_id']);
        $mUser -> parent_id = get_parent_promote_id($param['promote_id']);
        $mUser -> parent_name = get_promote_name($mUser -> parent_id);
        $mUser -> fgame_id = $param['game_id'];
        $mUser -> fgame_name = get_game_name($param['game_id']);
        $mUser -> nickname = $account;
        $mUser -> register_time = time();
        $mUser -> register_ip = get_client_ip();
        $mUser -> is_batch_create = 1;
        $res = $mUser -> save();
        return $res;

    }


}
