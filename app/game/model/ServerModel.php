<?php

namespace app\game\model;

use think\Db;
use think\Model;

/**
 * gjt
 */
class ServerModel extends Model
{

    protected $table = 'tab_game_server';

    protected $autoWriteTimestamp = true;

    /**
     * [首页区服列表]
     * @param int $limit
     * @author 郭家屯[gjt]
     */
    public function getServerLists($map = [], $limit = 10)
    {
        //今日开服
        $today = $this->field('game_id,game_name,server_name,start_time,relation_game_id')
            ->alias('s')
            ->join(['tab_game' => 'g'], 'g.id=s.game_id')
            ->where('status', 1)
            ->where('g.game_status', 1)
            ->where('start_time', 'between', [strtotime(date('Y-m-d')), strtotime(date("Y-m-d", strtotime("+1 day")))])
            ->where($map)
            ->order('start_time asc')
            ->limit($limit*10)
            ->select()->toArray();
        $data['today'] = array_chunk($today, $limit);
        //即将开服
        $tomorrow = $this->field('game_id,game_name,server_name,start_time,relation_game_id')
            ->alias('s')
            ->join(['tab_game' => 'g'], 'g.id=s.game_id')
            ->where('status', 1)
            ->where('g.game_status', 1)
            ->where('start_time', 'between', [strtotime(date("Y-m-d", strtotime("+1 day"))), strtotime(date("Y-m-d", strtotime("+7 day"))) + 86399])
            ->where($map)
            ->order('start_time asc')
            ->limit($limit*10)
            ->select()->toArray();
        $data['tomorrow'] = array_chunk($tomorrow, $limit);
        //已开服
        $yestoday = $this->field('game_id,game_name,server_name,start_time,relation_game_id')
            ->alias('s')
            ->join(['tab_game' => 'g'], 'g.id=s.game_id')
            ->where('status', 1)
            ->where('g.game_status', 1)
            ->where('start_time', 'between', [strtotime(date("Y-m-d", strtotime("-7 day"))), strtotime(date('Y-m-d'))-1])
            ->where($map)
            ->limit($limit*10)
            ->order('start_time desc')
            ->select()->toArray();
        $data['yestoday'] = array_chunk($yestoday, $limit);
        return $data;

    }

    /**
     * @函数或方法说明
     * @获取游戏区服分页
     * @param array $map
     * @param int $p
     * @param int $limit
     *
     * @author: 郭家屯
     * @since: 2020/2/19 17:19
     */
    public function getserver($map=[],$p=1,$row=10,$order="start_time desc")
    {
        $data = $this->field('s.id,game_id,game_name,server_name,start_time,relation_game_id,game_type_name,dow_num,relation_game_name,icon,tag_name,g.sdk_version')
                ->alias('s')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id')
                ->where('status', 1)
                ->where('g.game_status', 1)
                ->where($map)
                ->order($order)
                ->page($p,$row)
                ->select()->toArray();
        return $data?:[];
    }

    /**
     * @函数或方法说明
     * @获取游戏区服分页
     * @param array $map
     * @param int $p
     * @param int $limit
     *
     * @author: 郭家屯
     * @since: 2020/2/19 17:19
     */
    public function getserver_page($map=[],$row=10,$order="start_time desc")
    {
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $data = $this->field('s.id,game_id,game_name,server_name,start_time,relation_game_id,game_type_name,dow_num,relation_game_name,icon,tag_name,g.sdk_version')
                ->alias('s')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id', 'right')
                ->where('status', 1)
                ->where('g.game_status', 1)
                ->where($map)
                ->order($order)
                ->paginate($row, false, ['query' => request()->param()]);
        return $data;
    }


    /**
     * @函数或方法说明
     * @获取游戏区服数量
     * @param array $map
     * @param int $p
     * @param int $limit
     *
     * @author: 郭家屯
     * @since: 2020/2/19 17:19
     */
    public function getservercount($map=[])
    {
        $data = $this->field('game_id,game_name,server_name,start_time,relation_game_id,game_type_name,dow_num,relation_game_name,icon')
                ->alias('s')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id')
                ->where('status', 1)
                ->where('g.game_status', 1)
                ->where($map)
                ->count();
        return $data?:0;
    }

    /**
     * [wap区服列表]
     * @param int $limit
     * @author chen
     */
    public function getWapServerLists($map = [],$user_id=0)
    {
        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $data['today'] = $this->field('game_id,game_name,server_name,start_time,relation_game_id,game_type_name,dow_num,relation_game_name,icon,g.sdk_version')
            ->alias('s')
            ->join(['tab_game' => 'g'], 'g.id=s.game_id','left')
            ->where('status', 1)
            ->where('g.game_status', 1)
            ->where('start_time', 'between', total(1, 2))
            ->where($map)
            ->order('start_time asc')
            ->select()->toArray();
        if($user_id > 0 ){
            $data['last'] = $this->field('s.id as server_id,game_id,game_name,server_name,start_time,relation_game_id,game_type_name,dow_num,relation_game_name,icon,g.sdk_version,n.status as notice_status')
                    ->alias('s')
                    ->join(['tab_game' => 'g'], 'g.id=s.game_id','left')
                    ->join(['tab_game_server_notice'=>'n'],'s.id=n.server_id and n.user_id='.$user_id,'left')
                    ->where('s.status', 1)
                    ->where('g.game_status', 1)
                    ->where('start_time', 'between', [strtotime(date("Y-m-d", strtotime("+1 day"))), strtotime(date("Y-m-d", strtotime("+1 week"))) + 86399])
                    ->where($map)
                    ->order('start_time asc')
                    ->select()->toArray();
        }else{
            $data['last'] = $this->field('s.id as server_id,game_id,game_name,server_name,start_time,relation_game_id,game_type_name,dow_num,relation_game_name,icon,g.sdk_version')
                    ->alias('s')
                    ->join(['tab_game' => 'g'], 'g.id=s.game_id','left')
                    ->where('status', 1)
                    ->where('g.game_status', 1)
                    ->where('start_time', 'between', [strtotime(date("Y-m-d", strtotime("+1 day"))), strtotime(date("Y-m-d", strtotime("+1 week"))) + 86399])
                    ->where($map)
                    ->order('start_time asc')
                    ->select()->toArray();
        }
        $data['before'] = $this->field('game_id,game_name,server_name,start_time,relation_game_id,game_type_name,dow_num,relation_game_name,icon,g.sdk_version')
            ->alias('s')
            ->join(['tab_game' => 'g'], 'g.id=s.game_id','left')
            ->where('status', 1)
            ->where('g.game_status', 1)
            ->where('start_time', 'between', [strtotime(date("Y-m-d", strtotime("-1 week"))), strtotime(date("Y-m-d")) - 1])
            ->where($map)
            ->order('start_time desc')
            ->select()->toArray();

        return $data;

    }

    /**
     * @函数或方法说明
     * @获取区服列表
     * @param array $map
     *
     * @author: 郭家屯
     * @since: 2020/7/1 14:22
     */
    public function getlists($map=[],$order = "start_time asc")
    {
        $data = $this->field('s.id,game_id,game_name,server_name,start_time,relation_game_id,game_type_name,dow_num,relation_game_name,icon,g.sdk_version,tag_name')
                ->alias('s')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id')
                ->where('status', 1)
                ->where('g.game_status', 1)
                ->where($map)
                ->order($order)
                ->select()->toArray();
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取推广员区服列表
     * @author: 郭家屯
     * @since: 2019/8/9 15:10
     */
    public function get_promote_server(){
        $serverdata = $this->field('game_id,game_name,server_name,start_time,relation_game_id,game_type_name,dow_num,relation_game_name,icon')
                ->alias('s')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id')
                ->where('status', 1)
                ->where('game_status', 1)
                ->order('start_time desc')
                ->limit(100)
                ->select()->toArray();
        return $serverdata;
    }


    /**
     * [获取游戏已开的区服]
     * @param $map
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function getGameServer($map)
    {
        $data = $this->field('game_id,game_name,server_name,start_time,relation_game_id')
            ->alias('s')
            ->join(['tab_game' => 'g'], 'g.id=s.game_id')
            ->where('status', 1)
            ->where('g.game_status', 1)
            ->where('start_time', '>', (strtotime(date('Y-m-d'))-1))
            ->where($map)
            ->order('start_time asc')
            ->select()->toArray();
        return $data;
    }

    /**
     * [获取游戏已经开服的区服]
     * @param $map
     * @return mixed
     * @author chen
     */
    public function getBeforeGameServer($map)
    {
        $data = $this->field('game_id,game_name,server_name,start_time,relation_game_id')
            ->alias('s')
            ->join(['tab_game' => 'g'], 'g.id=s.game_id')
            ->where('status', 1)
            ->where('g.game_status', 1)
            ->where('start_time', '<', strtotime(date('Y-m-d')))
            ->where($map)
            ->order('start_time desc')
            ->select()->toArray();
        return $data;
    }

}
