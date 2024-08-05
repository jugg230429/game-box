<?php

namespace app\game\model;

use think\Model;

/**
 * gjt
 */
class CommentfollowModel extends Model
{

    protected $table = 'tab_game_comment_follow';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @点赞方法
     * @author: 郭家屯
     * @since: 2020/8/19 11:49
     */
    public function follow($user_id=0,$comment_id=0)
    {
        $follow = $this
                ->where('user_id',$user_id)
                ->where('comment_id',$comment_id)
                ->find();
        if($follow){
            $status = $follow->status == 1 ? 0 : 1;
            $save['status'] = $status;
            $save['create_time'] = time();
            $result = $this->where('id',$follow->id)->update($save);
            if($result){
                return $follow->status == 1 ? '取消成功' : '点赞成功';
            }else{
                return $follow->status == 1 ? '取消失败' : '点赞失败';
            }
        }else{
            $add['user_id'] = $user_id;
            $add['comment_id'] = $comment_id;
            $add['status'] = 1;
            $add['create_time'] = time();
            $result = $this->insert($add);
            if($result){
                return '点赞成功';
            }else{
                return '点赞失败';
            }
        }
    }
}