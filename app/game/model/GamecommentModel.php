<?php

namespace app\game\model;

use Think\Db;
use think\Model;

/**
 * gjt
 */
class GamecommentModel extends Model
{

    protected $table = 'tab_game_comment';

    protected $autoWriteTimestamp = true;


    /**
     * @函数或方法说明
     * @获取评论
     * @param array $map
     * @param int $limit
     *
     * @author: 郭家屯
     * @since: 2020/8/19 10:09
     */
    public function getLimit($map=[],$limit=100)
    {
        $data = $this->field('content')
                ->where($map)
                ->limit($limit)
                ->order('id desc')
                ->select()->toArray();
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取所有评论（分页）
     * @author: 郭家屯
     * @since: 2020/8/19 10:48
     */
    public function getCommetList($map=[],$user_id=0,$page=1,$limit=10)
    {
        $data = $this->field('id as top_id,user_id,user_account,content,create_time')
                ->where($map)
                ->order('id asc')
                ->page($page,$limit)
                ->select()->toArray();
        foreach ($data as $key=>$v){
            $data[$key]['create_time'] = date('m-d H:i',$v['create_time']);
            $data[$key]['head_img'] = cmf_get_image_url(get_user_entity($v['user_id'],false,'head_img')['head_img']);
            //点赞数量
            $data[$key]['follow_num'] = Db::table('tab_game_comment_follow')->where('comment_id',$v['top_id'])->where('status',1)->count();
            //一级评论人发表次数
            $data[$key]['total_num'] = $this->where('user_id',$v['user_id']) ->count()?:0;
            //点赞状态
            if($user_id > 0){
                $follow = Db::table('tab_game_comment_follow')->field('id')->where('comment_id',$v['top_id'])->where('user_id',$user_id)->where('status',1)->find();
                if($follow){
                    $data[$key]['follow_status'] = 1;
                }else{
                    $data[$key]['follow_status'] = 0;
                }
            }else{
                $data[$key]['follow_status'] = 0;
            }
            //评论数量和详情
            $map1['status'] = 1;
            $map1['top_id'] = $v['top_id'];
            $comment = $this->field('id as zi_id,user_account,comment_id,comment_account,content,top_id')
                    ->where($map1)
                    ->select()->toArray();
            if(!empty($comment)){
                // 区分二级和三级评论
                foreach ($comment as $k => $val){
                    if($val['top_id'] == $val['comment_id']){
                        $comment[$k]['comment_level'] = 2;
                    }else{
                        $comment[$k]['comment_level'] = 3;
                    }
                }
            }
            $data[$key]['comment_count'] = count($comment);
            $data[$key]['comment'] = $comment;
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * 游戏评论
     * @param $game_id
     * @param $user_id
     * @param $comment_id
     * @param $comment_account
     *
     * @author: 郭家屯
     * @since: 2020/8/19 13:54
     */
    public function game_comment($game_id=0,$user_id=0,$content='',$comment_id=0,$comment_account='')
    {
        $add['user_id'] = $user_id;
        $add['user_account'] = get_user_entity($user_id,false,'account')['account'];
        $add['content'] = $content;
        $add['game_id'] = $game_id;
        $add['game_name'] = get_game_entity($game_id,'game_name')['game_name'];
        $add['comment_id'] = $comment_id?:0;
        $add['comment_account'] = $comment_account ? :'';
        $add['create_time'] = time();
        if($comment_id > 0){
            $comment = $this->field('id,user_id,user_account,top_id')->where('id',$comment_id)->find();
            $add['top_id'] = $comment->top_id ? : $comment->id;
            $add['status'] = 1; //子评论无需审核
        }
        $config = Db::table('tab_game_config')->where('name','comment_auto_audit')->find();
        if($config['status'] == 1){
            $add['status'] = 1;
        }
        $result = $this->insertGetId($add);
        if($result && $comment_id > 0 && $config['status'] == 1){
            //添加评论消息
            $save['user_id'] = $comment['user_id'];
            $save['title'] = '评论回复';
            $save['content'] = $add['user_account'].'对您的评论进行了回复，请及时查看';
            $save['create_time'] = time();
            $save['comment_id'] = $result;
            $save['type'] = 5; // 添加类型
            Db::table('tab_tip')->insert($save);
        }
        return $result;
    }












}
