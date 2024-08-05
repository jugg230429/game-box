<?php

namespace app\member\model;

use think\Model;
use think\Db;

/**
 * gjt
 */
class UserBehaviorModel extends Model
{

    protected $table = 'tab_user_behavior';

    /**
     * @函数或方法说明
     * @玩家行为记录
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2019/8/13 9:19
     */
    public function set_record($request=[]){
        $behavior = $this
                ->field('id,update_time')
                ->where('user_id', $request['user_id'])
                ->where('game_id', $request['game_id'])
                ->where('status', 3)  // `status`-1取消收藏，1已收藏，-2不显示足迹，2显示足迹 3浏览通知',
                ->find();
        if ($behavior) {
            $behavior = $behavior->toArray();
            $this->where('id', $behavior['id'])->setField('update_time', time());
            $result = $behavior['update_time'];
        } else {
            $data['game_id'] = $request['game_id'];
            $data['user_id'] = $request['user_id'];
            $data['status'] = 3;
            $data['update_time'] = time();
            $data['create_time'] = time();
            $result = $this->insert($data);
        }
        return $result;
    }

    /**
     * @函数或方法说明
     * @获取玩家行为记录
     * @param array $data
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2019/8/13 9:25
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_record($data=[]){
        $record = $this
                ->where('user_id', $data['user_id'])
                ->where('game_id', $data['game_id'])
                ->where('status', 3)
                ->find();
        return empty($record) ? [] : $record->toArray() ;
    }

}