<?php

namespace app\member\model;

use app\common\task\UserTask;
use think\Model;
use think\Db;

/**
 * gjt
 */
class UserPlayModel extends Model
{

    protected $table = 'tab_user_play';

    protected $autoWriteTimestamp = true;


    function user_login_retain($userids = '', $game_id = '', $date = '')
    {
        if ($userids == '') {
            $map['tab_user_play.user_id'] = -1;
        } else {
            $map['tab_user_play.user_id'] = ['in', $userids];
        }
        if ($game_id == '') {
            $map['tab_user_play.game_id'] = ['gt', 0];
            $gmap['game_id'] = ['gt', 0];
        } else {
            $map['tab_user_play.game_id'] = $game_id;
            $gmap['game_id'] = $game_id;
        }
        $data = $this
            ->field('tab_user_play.user_id,tab_user_play.user_account,tab_user_play.create_time,tab_user_play.play_time,tab_user_play.game_id')
            ->where($map)
            ->order('play_time desc')
            ->group('user_id')
            ->select()->toArray();
        $end = date('Y-m-d');
        $start = date('Y-m-d', strtotime($end) - 29 * 24 * 3600);
        $gmap['time'] = ['in', periodDate($start, $end)];
        foreach ($data as $k => $v) {
            $gmap['user_id'] = $v['user_id'];
            $visit30 = Db::table('tab_user_game_login')->field('sum(play_time) as sumtime,sum(login_count) as sunmount')->where($gmap)->select()->toArray();
            $data[$k]['online_time30'] = empty($visit30[0]['sumtime']) ? 0 : $visit30[0]['sumtime'];
            $data[$k]['login_count30'] = empty($visit30[0]['sunmount']) ? 0 : $visit30[0]['sunmount'];
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @游戏登录
     * @param array $map
     * @param array $user
     * @param array $game
     *
     * @author: 郭家屯
     * @since: 2019/8/12 16:56
     */
    public function login($map=[],$user=[],$game=[],$new = 0){
        if($user['puid']==0){
            $tUser = new UserTask();
            $systemSet = cmf_get_option('admin_set');
            if(empty($systemSet['task_mode'])){
                $this->task_game_login($user["id"]);//每日登陆游戏任务
            }else{
                $tUser->createTaskGameLogin($user['id']);//每日登陆游戏任务 添加到待执行列表
            }
        }
        $res = empty($new) ? $this->field('id')->where($map)->find() : 0;
        if (empty($res)) {
            if($user['puid']>0){
                return false;
            }
            //添加账号
            $data["user_id"] = $user["id"];
            $data["user_account"] = $user["account"];
            $data["game_id"] = $game["id"];
            $data["game_appid"] = $game["game_appid"];
            $data["game_name"] = $game["game_name"];
            $data["bind_balance"] = 0;
            $data["promote_id"] = $user["promote_id"];
            $data["promote_account"] = $user["promote_account"];
            $data['play_time'] = time();
            $data['create_time'] = time();
            $data['play_ip'] = get_client_ip();
            $data['is_small'] = $user['puid']?1:0;//是否是小号
            $data["sdk_version"] = $game["sdk_version"];
            $this->insert($data);
            return 1;
        } else {
            $res = $res->toArray();
            $save['play_time'] = time();
            $save['play_ip'] = get_client_ip();
            $save['is_del'] = 0;
            $this->where('id', $res['id'])->update($save);
            return 2;
        }
    }

    /**
     * 每日登录游戏任务
     * @descript author
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author yyh
     * @since 2020-05-14
     *
     */
    public function task_game_login($user_id){
//        $userPlayData = $this->field('id')->where('user_id','=',$user_id)->where('FROM_UNIXTIME(play_time,"%Y-%m-%d") = "'.date('Y-m-d').'"')->find();
        $mUserPointRecord = new PointRecordModel();
        $where = [];
        $where['type_id'] = 11;
        $where['user_id'] = $user_id;
        $pointRecordData = $mUserPointRecord->field('id')->where($where)->where('FROM_UNIXTIME(create_time,"%Y-%m-%d") = "'.date('Y-m-d').'"')->find();
        if(empty($pointRecordData)){
            $modelUser = new UserModel();
            $modelUser->auto_task_complete($user_id,'game_login',1);//每日登录游戏任务
        }
    }
    /**
     * @函数或方法说明
     * #获取用户绑币列表
     * @author: 郭家屯
     * @since: 2019/10/15 17:53
     */
    public function getLists($data=[],$field = '*'){
        $list = $this->field($field)
                ->where('user_id',$data['user_id'])
                ->select()->toArray();
        return $list ? $list : [];
    }

}
