<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-09
 */

namespace app\issue\model;

use app\issue\model\PlatformModel;
use think\Db;
use think\Model;

class UserModel extends Model
{
    protected $table = 'tab_issue_user';

    protected $autoWriteTimestamp = true;

    public function user_login($data)
    {
        $this->where('openid','=',$data['user_id']);
        $this->where('platform_id','=',$data['platform_id']);
        $userData = $this->find();
        $this->startTrans();
        if(empty($userData)){
            $user_id = $this->add_user($data);
            //平台注册自增
            (new PlatformModel())->where('id','=',$data['platform_id'])->setInc('total_register',1);
            $userData = $this::get($user_id);
        }else{//更新用户
            $userData->birthday = $data['birthday']?:'';
            $userData->last_login_ip = request()->ip();
            $userData->last_login_time = time();
            $userData->update_time = time();
            if (empty($userData -> equipment_num)) {
                $userData -> equipment_num = $data['equipment_num'];
            }
            $userData->save();
        }
        $this->user_play($userData,$data);
        $this->login_record($userData,$data);
        $this->day_login_record($userData,$data);


        //判断是否绑定渠道,获取绑定渠道id
        if (!empty($userData -> parent_id)) {
            $userData = $this -> where(['id' => $userData -> parent_id]) -> find();
        }

        $this->commit();
        return $userData;
    }
    //新增用户
    private function add_user($data)
    {
        $this->account = 'U_'.$data['platform_id'].'_'.sp_random_string(6);
        $this->game_id = $data['game_id'];
        $this->openid = $data['user_id'];
        $this->platform_id = $data['platform_id'];
        $this->equipment_num = $data['equipment_num']??'';
        $this->open_user_id = (new PlatformModel())->where('id','=',$data['platform_id'])->value('open_user_id');
        $this->register_ip = request()->ip();
        $this->last_login_ip = request()->ip();
        $this->last_login_time = time();
        $this->birthday = $data['birthday']?:'';
        $this->save();
        return $this->getLastInsID();
    }
    //玩家
    private function user_play($user,$data)
    {
        $userplaymodel = new UserPlayModel();
        $userplaymodel->where('user_id','=',$user->id);
        $userplaymodel->where('game_id','=',$data['game_id']);
        $playData = $userplaymodel->field('id,equipment_num')->find();
        if(empty($playData)){
            $userplaymodel->user_id = $user->id;
            $userplaymodel->game_id = $data['game_id'];
            $userplaymodel->platform_id = $user->platform_id;
            $userplaymodel->open_user_id = $user->open_user_id;
            $userplaymodel->sdk_version = get_issue_game_sdk_version($data['game_id']);
            $userplaymodel->play_time = time();
            $userplaymodel->play_ip = request()->ip();
            $userplaymodel->equipment_num = $data['equipment_num'];
            $playData->login_extend = $data['login_extend'];
            $userplaymodel->save();
            (new IssueGameApplyModel())->where('game_id','=',$data['game_id'])->where('platform_id','=',$user->platform_id)->setInc('total_register',1);
            (new IssueGameModel())->where('id','=',$data['game_id'])->setInc('total_reg',1);
        }else{
            $playData->play_time = time();
            $playData->update_time = time();
            $playData->play_ip = request()->ip();
            $playData->login_extend = $data['login_extend'];
            if (empty($playData -> equipment_num)) {
                $playData -> equipment_num = $data['equipment_num'];
            }
            $playData->save();
        }
    }
    //登录记录
    private function login_record($user,$data)
    {
        $recordmodel = new UserLoginRecordModel();
        $recordmodel->game_id = $data['game_id'];
        $recordmodel->user_id = $user->id;
        $recordmodel->platform_id = $user->platform_id;
        $recordmodel->open_user_id = $user->open_user_id;
        $recordmodel->login_time = time();
        $recordmodel->login_ip = request()->ip();
        $recordmodel->sdk_version = get_issue_game_sdk_version($data['game_id']);
        $recordmodel->pt_type = 1;
        $recordmodel->equipment_num = $data['equipment_num'];
        $recordmodel->save();
    }

    /**
     * @每日登陆记录
     *
     * @author: zsl
     * @since: 2021/8/14 11:04
     */
    private function day_login_record($user, $data)
    {
        $mUserDayLogin = new UserDayLoginModel();
        $mUserDayLogin -> addDayLogin($user, $data);
    }


    //统计:新增用户
    public function newAddUser($start_time, $end_time, $param = [])
    {
        $where = [];
        if ($param['type'] == '1') {
            $where['g.sdk_version'] = '3';
        } elseif($param['type'] == '2'){
            $where['g.sdk_version'] = ['lt', '3'];
        }else {
            $where['g.sdk_version'] = '4';
        }
        if (!empty($param['open_user_id'])) {
            $where['u.open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['u.platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['u.game_id'] = $param['game_id'];
        }
        $where['u.create_time'] = ['between', [$start_time, $end_time]];
        $ids = $this -> alias('u')
                -> join(['tab_issue_game' => 'g'], 'g.id = u.game_id', 'left')
                -> where($where) -> column('u.id');
        return $ids;
    }

    //统计:活跃用户
    public function activeUser($start_time, $end_time, $param = [])
    {
        $where = [];
        if ($param['type'] == '1') {
            $where['sdk_version'] = '3';
        } elseif($param['type'] == '2'){
            $where['sdk_version'] = ['lt', '3'];
        }else {
            $where['sdk_version'] = '4';
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $where['login_time'] = ['between', [$start_time, $end_time]];
        $ids = Db ::table('tab_issue_user_login_record') -> where($where) -> group('user_id') -> column('user_id');
        return $ids;
    }



}
