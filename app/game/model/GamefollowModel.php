<?php

namespace app\game\model;

use think\Model;

/**
 * gjt
 */
class GamefollowModel extends Model
{

    protected $table = 'tab_game_follow';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @点赞方法
     * @author: 郭家屯
     * @since: 2020/8/19 11:49
     */
    public function follow($user_id=0,$game_id=0)
    {
        $follow = $this
                ->where('user_id',$user_id)
                ->where('game_id',$game_id)
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
            $add['game_id'] = $game_id;
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

    /**
     * @函数或方法说明
     * @点赞列表
     * @param array $map
     * @param int $page
     * @param int $limit
     *
     * @author: 郭家屯
     * @since: 2020/8/19 15:35
     */
    public function follow_list($map=[],$page=1,$limit=10)
    {
        $data = $this->field('g.id,game_name,relation_game_id,sdk_version,relation_game_name,icon,dow_num,game_type_name,if(down_port=1,game_size,game_address_size) as game_size,tag_name,features,down_port,video_cover,video,video_url')
                ->alias('f')
                ->join(['tab_game'=>'g'],'f.game_id=g.id','left')
                ->where($map)
                ->where('f.status',1)
                ->page($page,$limit)
                ->order('f.id desc')
                ->select()->toArray();
        return $data;
    }




}