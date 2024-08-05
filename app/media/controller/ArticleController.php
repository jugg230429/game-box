<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 9:35
 */

namespace app\media\controller;

use app\site\service\PostService;
use app\site\model\PortalPostModel;
use app\game\model\GameModel;
use think\Db;

class ArticleController extends BaseController
{
    /**
     * [文章首页]
     * @author 郭家屯[gjt]
     */
    public function index()
    {
        if (MEDIA_PID > 0) {
            $game_ids = get_promote_game_id(MEDIA_PID);
            $map['game_id'] = get_promote_relation_game_id($game_ids);
            $map['game_id'][] = '0';
            $map1['id'] = ['in', $game_ids];
        }
        $category_id = $this->request->param('category_id/d', 3);
        $postService = new PostService();
        $map['category'] = $category_id;
        $map['post_status'] = 1;
        // 渠道独占 或者 测试游戏不显示 相关的资讯
        $hidden_game_ids = get_test_game_ids();
        if(!empty($hidden_game_ids)){
            $map['hidden_game_ids'] = $hidden_game_ids;
        }
        // var_dump($hidden_game_ids);exit;

        $lists = $postService->ArticleList($map, false, 10,$order='a.update_time desc,a.sort desc,a.id desc');
        $data_lists = $lists->toArray()['data'];
        foreach ($data_lists as $k=>$v){
            $data_lists[$k]['update_date'] = date('Y-m-d',$v['update_time']);
            $new_lists[date('Y-m-d',$v['update_time'])][] = $data_lists[$k];
        }
        $this->assign('page', $lists->render());
        $this->assign('data', $new_lists);
        $this->assign('category_id', $category_id);
        $this->rank_lists($map1);
        return $this->fetch();
    }

    /**
     * [页游游戏文章首页]
     * @author 郭家屯[gjt]
     */
    public function game_article()
    {
        $game_id = $this->request->param('game_id');
        if(empty($game_id))$this->error('参数错误');
        $map['game_id'] = get_promote_relation_game_id([$game_id]);
        $category_id = $this->request->param('category_id/d', 4);
        $postService = new PostService();
        $map['category'] = $category_id;
        $map['post_status'] = 1;
        $lists = $postService->ArticleList($map, false, 100,$order='a.update_time desc,a.sort desc,a.id desc');
        $data_lists = $lists->toArray()['data'];
        foreach ($data_lists as $k=>$v){
            $data_lists[$k]['update_date'] = date('Y-m-d',$v['update_time']);
            $new_lists[date('Y-m-d',$v['update_time'])][] = $data_lists[$k];
        }
        $game = get_game_entity($game_id,'groom');
        $this->assign('game',$game);
        $this->assign('page', $lists->render());
        $this->assign('data', $new_lists);
        $this->assign('category_id', $category_id);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @游戏文章详情页
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/9/23 14:41
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	 public function game_detail()
	 {
         $id = $this->request->param('id/d', 0);
         $category_id = $this->request->param('category_id/d', 0);
         if (empty($category_id)) {
             $category = Db::table('sys_portal_category_post')->where('post_id', $id)->order('id desc')->find();
             $category_id = $category['category_id'];
         }
         $portalPostModel = new PortalPostModel();
         $data = $portalPostModel->where('id', $id)->find();
         if ($data) {
             $portalPostModel->where('id', $id)->setInc('post_hits');
         }
         $this->assign('category_id', $category_id);
         $this->assign('data', $data);
         $postService = new PostService();
         //上一篇
         $prev = $postService->publishedPrevArticle($id, $category_id,$data['game_id']);
         $this->assign('prev', $prev);
         //下一篇
         $next = $postService->publishedNextArticle($id, $category_id,$data['game_id']);
         $this->assign('next', $next);
         if (AUTH_GAME == 1) {
             //相关文章
             $map['id'] = $id;
             $map['game_id'] = $data['game_id'];
             $map['post_status'] = 1;
             $others = $postService->GameArticleList($map, false, 4);
             $this->assign('others', $others);
             if (MEDIA_PID > 0) {
                 $game_ids = get_promote_game_id(MEDIA_PID);
                 $map2['id'] = ['in', $game_ids];
                 $map1['and_id|ios_id|h5_id'] = ['in', $game_ids];
             }
             $map2['sdk_version'] = ['in',[1,2]];
             $this->recommendGame($map2);//推荐游戏
             $this->hotGift($map1);
         }
         //占位图
         $this->slide('media_zixun_gift');
		 return $this->fetch();
	 }
    /**
     * @函数或方法说明
     * @游戏排行榜
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/10/16 16:30
     */
    private function rank_lists($map=[]){
        if(PERMI !=2){
            $map['sdk_version'] = ['in',[1,2]];
            //热门游戏
            $hotgame = $this->get_first_game(1,$map);
            $this->assign('hotgame',$hotgame);
            //推荐游戏
            //$resgame = $this->get_first_game(3);
            //$this->assign('resgame',$resgame);
            //最新游戏
            $newgame = $this->get_first_game(2,$map);
            $this->assign('newgame',$newgame);
            //下载游戏
            $downgame = $this->get_first_game(4,$map);
            $this->assign('downgame',$downgame);
        }
        if(PERMI != 1){
            $map['sdk_version'] = 3;
            //热门游戏
            $hotgame = $this->get_first_game(1,$map);
            $this->assign('h5hotgame',$hotgame);
            //推荐游戏
            //$resgame = $this->get_first_game(3);
            //$this->assign('resgame',$resgame);
            //最新游戏
            $newgame = $this->get_first_game(2,$map);
            $this->assign('h5newgame',$newgame);
            //下载游戏
            $downgame = $this->get_first_game(4,$map);
            $this->assign('h5downgame',$downgame);
        }
        if(YPERMI == 1){
            $map['sdk_version'] = 4;
            //热门游戏
            $hotgame = $this->get_first_game(1,$map);
            $this->assign('yyhotgame',$hotgame);
            //推荐游戏
            //$resgame = $this->get_first_game(3);
            //$this->assign('resgame',$resgame);
            //最新游戏
            $newgame = $this->get_first_game(2,$map);
            $this->assign('yynewgame',$newgame);
            //下载游戏
            $downgame = $this->get_first_game(4,$map);
            $this->assign('yydowngame',$downgame);
        }
    }
    /**
     * @函数或方法说明
     * @获取首页游戏
     * @param int $type
     *
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2019/10/16 17:12
     */
    private function get_first_game($type=1,$map=[],$p=1,$limit=15)
    {
        $order = "sort desc,id desc";
        switch ($type){
            case 1:
                $map['recommend_status'] = ['like', '%2%'];
                break;
            case 2:
                $map['recommend_status'] = ['like', '%3%'];
                break;
            case 3:
                $map['recommend_status'] = ['like', '%1%'];
                break;
            case 4:
                $order = 'dow_num desc,id desc';
                break;
            default:
                break;
        }
        $model = new GameModel();
        $data = $model->getMoreGame($map,$order,$p,$limit,'relation_game_id')->each(function($item,$key){
            $item['icon'] = cmf_get_image_url($item['icon']);
            if($item['sdk_version'] == 4){
                $item['url'] = url('Game/ydetail',['game_id'=>$item['relation_game_id']]);
            }elseif($item['sdk_version'] == 3){
                $item['url'] = url('Game/hdetail',['game_id'=>$item['relation_game_id']]);
            }else{
                $item['url'] = url('Game/detail',['game_id'=>$item['relation_game_id']]);
            }
        })->toArray();
        return $data;
    }

    /**
     * [推荐游戏]
     * @author 郭家屯[gjt]
     */
    public function recommendGame($map = [])
    {
        $model = new \app\game\model\GameModel();
        $map['recommend_status'] = ['like', '%1%'];
        $data = $model->getGameLists($map, 16)->each(function ($item, $key) {
            $server = Db::table('tab_game_server')->field('id,server_name')->where('game_id', $item['id'])->where('start_time', '<', time())->order('id desc')->find();
            $item['server_name'] = $server ? $server['server_name'] : '';
            return $item;
        });
        $this->assign('recommend_game', $data);
    }

    public function detail()
    {
        $id = $this->request->param('id/d', 0);
        $category_id = $this->request->param('category_id/d', 0);
        if (empty($category_id)) {
            $category = Db::table('sys_portal_category_post')->where('post_id', $id)->order('id desc')->find();
            $category_id = $category['category_id'];
        }
        $portalPostModel = new PortalPostModel();
        $data = $portalPostModel->where('id', $id)->find();
        if ($data) {
            $portalPostModel->where('id', $id)->setInc('post_hits');
        }
        $this->assign('category_id', $category_id);
        $this->assign('data', $data);
        $postService = new PostService();
        //上一篇
        $prev = $postService->publishedPrevArticle($id, $category_id);
        $this->assign('prev', $prev);
        //下一篇
        $next = $postService->publishedNextArticle($id, $category_id);
        $this->assign('next', $next);
        if (AUTH_GAME == 1) {
            //相关文章
            $map['id'] = $id;
            $map['game_id'] = $data['game_id'];
            $map['post_status'] = 1;
            $others = $postService->GameArticleList($map, false, 4);
            $this->assign('others', $others);
            if (MEDIA_PID > 0) {
                $game_ids = get_promote_game_id(MEDIA_PID);
                $map2['id'] = ['in', $game_ids];
                $map1['and_id|ios_id|h5_id'] = ['in', $game_ids];
            }
            $map2['sdk_version'] = ['in',[1,2]];
            $this->recommendGame($map2);//推荐游戏
            $this->hotGift($map1);
        }
        //占位图
        $this->slide('media_zixun_gift');
        return $this->fetch();
    }

    /**
     * [热门礼包]
     * @author 郭家屯[gjt]
     */
    public function hotGift($map = [])
    {
        $model = new \app\game\model\GiftbagModel();
        $lists = $model->getGiftIndexLists(session('member_auth.user_id'), $map, 6);
        $this->assign('gift', $lists);
    }

    /**
     * [家长监督]
     * @author 郭家屯[gjt]
     */
    public function supervise()
    {
        return $this->fetch();
    }

    /**
     * [申请]
     * @author 郭家屯[gjt]
     */
    public function superviseApply()
    {
        return $this->fetch();
    }

    /**
     * [服务]
     * @author 郭家屯[gjt]
     */
    public function superviseServer()
    {
        return $this->fetch();
    }

    public function about()
    {
        if (session('union_host')) {
            $id = $this->request->param('id/d', 0);
            $lists = Db::table('tab_promote_union_article')->where(['promote_id'=>MEDIA_PID])->select();
            $data = array();
            foreach ($lists as $key => $vo) {
                if ($vo['id'] == $id) {
                    $data = $vo;
                }
            }
            $this->assign('data', $data);
            $this->assign('lists', $lists);
            $this->assign('id', $id);
        }else{
            $id = $this->request->param('id/d', 14);
            $postService = new PostService();
            $map['category'] = 7;
            $lists = $postService->ArticleList($map, false, 10)->toArray();
            $data = array();
            foreach ($lists['data'] as $key => $vo) {
                if ($vo['id'] == $id) {
                    $data = $vo;
                }
            }
            $this->assign('data', $data);
            $this->assign('lists', $lists);
            $this->assign('id', $id);

        }

        return $this->fetch();
    }

}
