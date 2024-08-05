<?php

namespace app\issue\model;

use think\helper\Time;
use think\Model;

class UserDayLoginModel extends Model
{

    protected $table = 'tab_issue_user_day_login';

    protected $autoWriteTimestamp = true;


    /**
     * @新增用户每日登陆记录
     *
     * @author: zsl
     * @since: 2021/8/14 11:53
     */
    public function addDayLogin($user, $data)
    {
        $where = [];
        $where['game_id'] = $data['game_id'];
        $where['user_id'] = $user -> id;
        $where['login_year'] = date("Y");
        $where['login_month'] = date("m");
        $where['login_day'] = date("d");
        $info = $this -> where($where) -> find();
        if (empty($info)) {
            $this -> game_id = $data['game_id'];
            $this -> user_id = $user -> id;
            $this -> platform_id = $user -> platform_id;
            $this -> login_time = time();
            $this -> login_ip = get_client_ip();
            $this -> login_year = date("Y");
            $this -> login_month = date("m");
            $this -> login_day = date("d");
            $this -> equipment_num = $data['equipment_num'];
            $this -> open_user_id = $data['open_user_id'];
            $this -> isUpdate(false) -> save();
        }

    }


}
