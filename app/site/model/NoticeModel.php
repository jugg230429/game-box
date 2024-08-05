<?php

namespace app\site\model;

use app\member\model\UserBehaviorModel;
use think\Model;

/**
 * gjt
 */
class NoticeModel extends Model
{

    protected $table = 'tab_notice';

    protected $autoWriteTimestamp = true;

    /**
     * [获取今日游戏公告]
     * @param array $map
     * @return false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getTodayNotice($map = [])
    {
        $lists = $this
            ->field('id,title,create_time,content')
            ->where($map)
            ->where('start_time', ['<', time()], ['=', 0], 'or')
            ->where('end_time', ['>', time()], ['=', 0], 'or')
            ->order('level desc,id desc')
            ->select()->toArray();
        return $lists;
    }

    /**
     * [通知详情]
     * @param array $map
     * @return array|false|\PDOStatement|string|Model
     * @author 郭家屯[gjt]
     */
    public function getNoticeDetail($map = [])
    {
        $detail = $this
            ->field('id,game_name,title,content,create_time')
            ->where($map)
            ->find();
        return $detail ? $detail->toArray() : [];
    }

    public function getNewNotice($map=[])
    {
        $notice = $this
            ->field('id,game_name,title,content,if(start_time>0,start_time,create_time) as start_time')
            ->where('start_time', ['<', time()], ['=', 0], 'or')
            ->where('end_time', ['>', time()], ['=', 0], 'or')
            ->where($map)
            ->order('id desc')
            ->find();
        return $notice ? $notice->toArray() : [];
    }

    public function getNoticeMark($data=[],$user=[])
    {
        $user['read_status'] = 0;//红点
        if($data['game_id'] > 0 ){
            $map['game_id'] = $data['game_id'];
        }else{
            $map = [];
        }
        $notice = $this->getNewNotice($map);
        if (empty($notice)) {
            $user['read_status'] = 1;
        } else {
            $behaviormodel = new UserBehaviorModel();
            $behavior = $behaviormodel->get_record($data);
            if ($behavior && ($notice['start_time'] <= $behavior['update_time'])) {
                $user['read_status'] = 1;
            }
        }
        return $user;
    }
}