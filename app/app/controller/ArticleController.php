<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\app\controller;


use app\common\logic\GameLogic;
use app\site\model\PortalPostModel;
use app\site\service\PostService;
use think\Db;

class ArticleController extends BaseController
{

    /**
     * @函数或方法说明
     * @文章列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/17 17:06
     */
    public function articlelists()
    {
        $data = $this->request->param();
        $data['limit'] = $data['limit'] ? : 10;
        if(PROMOTE_ID > 0){
            $game_idss = get_promote_relation_game_id(get_promote_game_id(PROMOTE_ID));
            $map['game_id'] = $game_idss;
            $map['game_id'][] = '0';
        }
        $postService = new PostService();
        $map['category'] = $data['category'];
        $map['post_status'] = 1;
        // 渠道独占 或者 测试游戏不显示 相关的资讯
        $hidden_game_ids = get_test_game_ids();
        if(!empty($hidden_game_ids)){
            $map['hidden_game_ids'] = $hidden_game_ids;
        }
        $lists = $postService->ArticleList($map, false, $data['limit'])->toArray();
        $data_list = [];
        foreach ($lists['data'] as $key=>$v){
            $data_list[$key]['article_id'] = $v['id'];
            $data_list[$key]['thumbnail'] = cmf_get_image_url($v['thumbnail']);
            $data_list[$key]['post_title'] = $v['post_title'];
            $data_list[$key]['create_time'] = date("Y-m-d H:i",$v['create_time']);
        }
        $this->set_message(200,'获取成功',$data_list);
    }

    /**
     * @函数或方法说明
     * @资讯详情页
     * @author: 郭家屯
     * @since: 2020/2/18 13:46
     */
    public function articledetail()
    {
        $request = $this->request->param();
        $portalPostModel = new PortalPostModel();
        $article = $portalPostModel->field("post_title,post_content,game_id,thumbnail,update_time")->where('id', $request['article_id'])->find()->toArray();
        $request['game_id'] = $article['game_id'];
        $data = $this->getGameinfo($request);
        $data['article_url'] = url('Article/detail',['article_id'=>$request['article_id']],'',true);
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @资讯详情页面
     * @author: 郭家屯
     * @since: 2020/2/28 17:27
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException\
     */
    public function detail()
    {
        $request = $this->request->param();
        $portalPostModel = new PortalPostModel();
        $article = $portalPostModel->field("post_title,post_content,game_id,thumbnail,update_time")->where('id', $request['article_id'])->find()->toArray();
        $this->assign('data',$article);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取游戏详情
     * @param int $game_id
     *
     * @author: 郭家屯
     * @since: 2020/2/18 13:59
     */
    protected function getGameinfo($request=[]){
        $logic = new GameLogic();
        $game = $logic->getGameDetail($request);
        return $game;
    }
}