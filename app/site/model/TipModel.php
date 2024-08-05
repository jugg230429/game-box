<?php

namespace app\site\model;

use app\member\model\UserBehaviorModel;
use think\Model;

/**
 * gjt
 */
class TipModel extends Model
{

    protected $table = 'tab_tip';

    protected $autoWriteTimestamp = true;

    /**
     * [获取消息列表]
     * @return false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getLists($user_id=0,$type=0)
    {
        if($type != 0){
            $lists = $this
                ->field('title,content,create_time,game_id,comment_id')
                ->where('user_id',$user_id)
                ->where('type', $type)
                ->order('id desc')
                ->select()->toArray();
            return $lists?:[];
        }
        $lists = $this
            ->field('title,content,create_time,game_id,comment_id')
            ->where('user_id',$user_id)
            ->order('id desc')
            ->select()->toArray();
        return $lists?:[];
    }

    public function getPageLists($user_id=0,$p=1,$limit=10)
    {
        $lists = $this
                ->field('title,content,create_time,game_id,comment_id')
                ->where('user_id',$user_id)
                ->order('id desc')
                ->page($p,$limit)
                ->select()->toArray();
        return $lists?:[];
    }

    // 获取未读消息数
    public function getUnreadMsgNum($user_id, $map=[]){
        if(empty($user_id)){
            return 0;
        }
        $unread_num = $this
            ->where('user_id', $user_id)
            ->where($map)
            ->where('read_or_not',1) // 统计未读消息
            ->count();
        return $unread_num;
    }
    // 获取消息列表2 根据不同类型或取消息,并标记为已读
    // by wjd
    public function getTipList($user_id, $map=[]){
        if(empty($user_id)){
            return [];
        }
        $tip_list = $this
                ->where('user_id', $user_id)
                ->where($map)
                ->order('read_or_not desc,id desc')
                ->select()
                ->toArray();
        
        // 将消息标记为已读
        $ids = [];
        if(!empty($tip_list)){
            foreach($tip_list as $key=>$val){
                if($val['read_or_not'] == 1){   
                    $ids[] = $val['id'];
                }
            }
            $this->setReaded($ids);
        }

        return $tip_list;
    }
    // 将消息标记为已读状态 by wjd
    private function setReaded($ids=[]){
        $num = 0;
        if(!empty($ids)){
            $num = $this->where('id','in',$ids)->update(['read_or_not'=>0]);
        }
        return $num;
    }
    /**
     * @函数或方法说明
     * @获取消息状态
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/2/24 15:19
     */
    public function get_tip_status($user_id=0)
    {
        if(empty($user_id)){
            return 0;
        }
        $new_tip = $this->field('create_time')
                ->where('user_id',$user_id)
                ->order('id desc')
                ->find();
        if(!$new_tip){
            return 0;
        }
        $new_tip = $new_tip->toArray();
        $model = new UserBehaviorModel();
        $behavior = $model->field('update_time')
                ->where('user_id',$user_id)
                ->where('game_id',0)
                ->where('status',3)
                ->find();
        if(!$behavior){
            return 1;
        }
        $behavior = $behavior->toArray();
        if($new_tip['create_time'] > $behavior['update_time']){
            return 1;
        }
        return 0;
    }

    /**
     * @函数或方法说明
     * @获取APP消息状态
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/2/24 15:19
     */
    public function get_app_tip_status($user_id=0)
    {
        if(empty($user_id)){
            return 0;
        }
        $new_tip = $this->field('create_time')
                ->where('user_id',$user_id)
                ->order('id desc')
                ->find();
        if(!$new_tip){
            return 0;
        }
        $model = new UserBehaviorModel();
        $behavior = $model->field('update_time')
                ->where('user_id',$user_id)
                ->where('game_id',0)
                ->where('status',3)
                ->find();
        if(!$behavior){
            $behavior['update_time'] = 0;
        }else{
            $behavior = $behavior->toArray();
        }
        $count =  $this->field('create_time')
                ->where('user_id',$user_id)
                ->where('create_time','>',$behavior['update_time'])
                ->order('id desc')
                ->count();
        return $count?:0;
    }

}