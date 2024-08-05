<?php

namespace app\common\task;

use app\common\logic\AntiaddictionLogic;
use app\member\model\UserModel;
use app\member\model\UserPlayModel;
use think\Db;

class UserTask
{


    /**
     * @添加每日登陆游戏任务
     *
     * @author: zsl
     * @since: 2021/8/3 20:27
     */
    public function createTaskGameLogin($user_id)
    {
        $task = new Task();
        $data = [
                'title' => '每日登陆游戏任务',
                'class_name' => '\app\common\task\UserTask',
                'function_name' => 'taskGameLogin',
                'param' => ['user_id' => $user_id],
                'remark' => '',
        ];
        $result = $task -> create($data);
        return $result;
    }

    /**
     * @每日登陆游戏任务
     *
     * @author: zsl
     * @since: 2021/8/3 20:32
     */
    public function taskGameLogin($param)
    {
        try {
            if (empty($param)) {
                return false;
            }
            $mUserPlay = new UserPlayModel();
            $mUserPlay -> task_game_login($param['user_id']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * @上传国家实名认证
     *
     * @author: zsl
     * @since: 2021/8/4 13:47
     */
    public function taskUpRealName($param)
    {
        try {
            if (empty($param)) {
                return false;
            }
            $data = $param['data'];
            $user = $param['user'];
            if ($data['game_id'] > 0 && get_game_age_type($data['game_id']) == 2) {
                if (!empty($user['idcard']) && !empty($user['real_name'])) {
                    $map['user_id'] = $user['id'];
                    $map['game_id'] = $data['game_id'];
                    $user_age = Db ::table('tab_user_age_record') -> field('id') -> where($map) -> find();
                    if (empty($user_age)) {
                        $logic = new AntiaddictionLogic($data['game_id']);
                        $res = $logic -> checkUser($user['real_name'], $user['idcard'], $user["id"], $data['game_id']);
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * @设置渠道结算比例
     *
     * @author: zsl
     * @since: 2021/8/4 14:17
     */
    public function taskSetPromoteRadio($param)
    {
        try {
            if (empty($param)) {
                return false;
            }
            $user = $param['user'];
            $data = $param['data'];
            $promote = $param['promote'];
            $mUser = new UserModel();
            $res = $mUser -> set_promote_radio($user, $data, $promote);
            return !!$res;

        } catch (\Exception $e) {
            return false;
        }
    }

}
