<?php

namespace app\member\model;

use think\Model;
use think\Db;

class UserGameLoginModel extends Model
{

    protected $table = 'tab_user_game_login';

    protected $autoWriteTimestamp = true;

    public function duration_data($map = [])
    {
        $data = $this
            ->where($map)
            ->select()
            ->toArray();
        $new_data = array_group_by($data, 'time');
        return $new_data;
    }

    /**
     * @函数或方法说明
     * @游戏登录
     * @author: 郭家屯
     * @since: 2019/8/12 17:24
     */
    public function login($map=[],$data=[],$user=[],$request=[]){
        $gameLogin = $this->field('id,login_count,effective_time,play_time,last_login_time,last_down_time')->where($map)->find();
        if ($gameLogin) {
            $gameLogin = $gameLogin->toArray();
            if($gameLogin['last_login_time'] > $gameLogin['last_down_time']){
                $outtime =  intval((time()-$gameLogin['last_login_time'])/3);
                if($outtime > 3600){
                    $outtime = 3600;
                }
                $gameLogin['last_down_time'] = $gameLogin['last_login_time'] + $outtime;
                $save['play_time'] = $gameLogin['play_time'] + $outtime;
                $gameLogin['effective_time'] = $gameLogin['effective_time'] + $outtime;
            }
            if ($data['hours_cover'] && $gameLogin['last_down_time']) {
                $time = (time() - $gameLogin['last_down_time']) / 3600;
                if ($time >= $data['hours_cover']) {
                    $gameLogin['effective_time'] = 0;
                }
            }
            $play_time = $gameLogin['effective_time'];
            $save['login_count'] = $gameLogin['login_count'] + 1;
            $save['last_login_time'] = time();
            $save['last_down_time'] = $gameLogin['last_down_time'];
            $save['effective_time'] = $gameLogin['effective_time'];
            $result = $this->where('id', $gameLogin['id'])->update($save);
        } else {
            $play_time = 0;
            $save['time'] = date('Y-m-d');
            $save['user_id'] = $request['user_id'];
            $save['game_id'] = $request['game_id'];
            $save['promote_id'] = $user['promote_id'];
            $save['login_count'] = 1;
            $save['last_login_time'] = time();
            $result = $this->insert($save);
        }
        return $play_time;
    }

    public function get_down($map=[],$request=[]){
        $gameLogin = $this->field('id,play_time,last_login_time,effective_time')->where($map)->find();
        if ($gameLogin) {//一日内
            $gameLogin = $gameLogin->toArray();
            $save['play_time'] = $gameLogin['play_time'] + time() - $gameLogin['last_login_time'];//当天在线总时长
            $save['last_down_time'] = time();
            $save['effective_time'] = $gameLogin['effective_time'] + time() - $gameLogin['last_login_time'];//单次在线时长
            $this->where('id', $gameLogin['id'])->update($save);
        } else {//跨日期
            //更新昨日记录
            $map1['user_id'] = $request['user_id'];
            $map1['game_id'] = $request['game_id'];
            $gamelogin_yes = $this->field('id,play_time,last_login_time')->where($map1)->order('time desc')->find();
            $save['last_down_time'] = strtotime(date('Y-m-d')) - 1;//跨日期 重新计算防沉迷
            $save['play_time'] = $gamelogin_yes['play_time'] + $save['last_down_time'] - $gamelogin_yes['last_login_time'];
            $this->where('id', $gamelogin_yes['id'])->update($save);
            //今日游戏信息
            $user = get_user_entity($request['user_id'],false,'promote_id');
            $data['time'] = date('Y-m-d');
            $data['user_id'] = $request['user_id'];
            $data['game_id'] = $request['game_id'];
            $data['promote_id'] = $user['promote_id'];
//            $data['play_time'] = time() - strtotime(date('Y-m-d'));
            $data['play_time'] = 0;
            $data['login_count'] = 1;
            $data['last_login_time'] = strtotime(date('Y-m-d'));
            $data['last_down_time'] = time();
//            $data['effective_time'] = time() - strtotime(date('Y-m-d'));//跨日期 重新计算防沉迷
            $data['effective_time'] = 0;
            $this->insert($data);
        }
        return true;
    }
}
