<?php

namespace app\common\logic;

use app\member\model\UserModel;
use app\promote\model\PromoteapplyModel;
use app\promote\model\PromoteModel;

class SimulatorLogic
{


    /**
     * @获取可配置白名单游戏列表
     *
     * @author: zsl
     * @since: 2021/5/25 14:08
     */
    public function getGameLists($param)
    {
        $mApply = new PromoteapplyModel();
        $field = "a.id,g.id as game_id,g.game_name,simulator_channel_status";
        $where = [];
        $where['a.promote_id'] = $param['promote_id'];
        $where['a.status'] = 1;
        $where['g.simulator_channel_status'] = 1;
        $gameLists = $mApply -> alias('a')
                -> field($field)
                -> where($where)
                -> join(['tab_game' => 'g'], 'a.game_id = g.id', 'left')
                -> select();
        return $gameLists;
    }


    /**
     * @获取添加到模拟器白名单用户列表
     *
     * @author: zsl
     * @since: 2021/5/25 16:33
     */
    public function getUserLists($param)
    {
        $row = empty($param['row']) ? 10 : $param['row'];
        $mUser = new UserModel();
        $field = "id,account,promote_id,is_simulator,login_time";
        $where = [];
        if (!empty($param['user_account'])) {
            $where['account'] = ['like', '%' . $param['user_account'] . '%'];
        }
        if (!empty($param['user_id'])) {
            $where['id'] = $param['user_id'];
        }
        $where['promote_id'] = $param['promote_id'];
        $where['is_unsubscribe'] = 0;// 未注销
        $where['is_simulator'] = 1;// 允许模拟器登录
        $userLists = $mUser -> field($field) -> where($where) -> paginate($row);
        return $userLists;

    }


    /**
     * @添加玩家到白名单
     *
     * @author: zsl
     * @since: 2021/5/25 15:58
     */
    public function addUser($param)
    {
        $result = ['code' => 1, 'msg' => '保存成功', 'data' => []];
        $ids = array_merge(array_filter($param['ids']));
        if (empty($ids)) {
            $result['code'] = 0;
            $result['msg'] = '请输入玩家ID';
            return $result;
        }
        //查询本渠道下的用户id
        $mUser = new UserModel();
        $where = [];
        $where['id'] = ['in', $ids];
        $where['promote_id'] = $param['promote_id'];
        $where['is_unsubscribe'] = 0;
        $userIds = $mUser -> where($where) -> column('id');
        if (empty($userIds)) {
            $result['code'] = 0;
            $result['msg'] = '请输入本渠道下用户ID';
            return $result;
        }
        //允许用户使用模拟器登录
        $where = [];
        $where['id'] = ['in', $userIds];
        $where['promote_id'] = $param['promote_id'];
        $where['is_unsubscribe'] = 0;
        $res = $mUser -> where($where) -> setField('is_simulator', 1);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '添加失败，请稍后重试';
            return $result;
        }
        return $result;
    }


    /**
     * @将用户移出模拟器白名单
     *
     * @author: zsl
     * @since: 2021/5/25 16:56
     */
    public function removeUser($param)
    {
        $result = ['code' => 1, 'msg' => '移除成功', 'data' => []];
        $mUser = new UserModel();
        $where = [];
        if (is_array($param['id'])) {
            $where['id'] = ['in', $param['id']];
        } else {
            $where['id'] = $param['id'];
        }
        $where['promote_id'] = $param['promote_id'];
        $where['is_unsubscribe'] = 0;
        $res = $mUser -> where($where) -> setField('is_simulator', 0);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '移除失败，请稍后重试';
            return $result;
        }
        return $result;
    }

    /**
     * @移除全部白名单
     *
     * @author: zsl
     * @since: 2021/7/22 13:41
     */
    public function removeAllUser($param)
    {
        $result = ['code' => 1, 'msg' => '移除成功', 'data' => []];
        $mUser = new UserModel();
        $where = [];
        $where['promote_id'] = $param['promote_id'];
        $where['is_unsubscribe'] = 0;
        $res = $mUser -> where($where) -> setField('is_simulator', 0);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '移除失败，请稍后重试';
            return $result;
        }
        return $result;
    }


    /**
     * @检查用户是否有模拟器权限
     *
     * @author: zsl
     * @since: 2021/5/26 10:45
     */
    public function checkUser($param)
    {
        if (empty($param['user_id'])) {
            return false;
        }
        //获取用户信息
        $userInfo = get_user_entity($param['user_id'], false, 'id,account,promote_id,is_simulator');
        if (empty($userInfo)) {
            return false;
        }
        //获取游戏信息
        $gameInfo = get_game_entity($param['game_id'], 'id,game_name,simulator_official_status,simulator_channel_status');
        if (empty($gameInfo)) {
            return false;
        }
        //检测权限
        if ($userInfo['promote_id'] == '0') {
            //官方玩家
            //游戏是否允许官方玩家模拟器登录
            if ($gameInfo['simulator_official_status'] == '0') {
                return false;
            }
        } else {
            //渠道玩家
            //是否将权限赋予推广员
            if ($gameInfo['simulator_channel_status'] == '0') {
                return false;
            }
            //推广员是否授权全部用户
            $mPromote = new PromoteModel();
            $allow_simulator = $mPromote -> where(['id' => $userInfo['promote_id']]) -> value('allow_simulator');
            if ($allow_simulator == '1') {
                //全部允许
                return true;
            }
            //推广员用户是否添加到白名单
            if ($userInfo['is_simulator'] == 0) {
                return false;
            }
        }
        return true;
    }


}
