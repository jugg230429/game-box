<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 9:35
 */

namespace app\mobile\controller;

use app\site\service\PostService;
use app\site\model\PortalPostModel;
use think\Db;

class ArticleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('index/index'));
            };
        }
    }

    /**
     * [文章首页]
     * @author 郭家屯[gjt]
     */
    public function index()
    {
        if (MOBILE_PID > 0) {
            $game_ids = get_promote_relation_game_id(get_promote_game_id(MOBILE_PID));
            $map['game_id'] = $game_ids;
            $map['game_id'][] = '0';
        }
        $category_id = $this->request->param('category_id/d', 3);
        $postService = new PostService();
        $map['category'] = $category_id;
        $map['post_status'] = 1;
        $map['sdk_version'] = get_devices_type();
        // 渠道独占 或者 测试游戏不显示 相关的资讯
        $hidden_game_ids = get_test_game_ids();
        if(!empty($hidden_game_ids)){
            $map['hidden_game_ids'] = $hidden_game_ids;
        }
        
        $lists = $postService->WapArticleList($map, false);
        $this->assign('data', $lists);
        $this->assign('category_id', $category_id);

        return $this->fetch();
    }


    /**
     *[文章详情]
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author chen
     */
    public function detail()
    {
        $id = $this->request->param('id/d', 0);
        $portalPostModel = new PortalPostModel();
        $data = $portalPostModel
                ->alias('p')
                ->field('p.id,p.post_title,p.update_time,p.create_time,p.post_content,g.icon,g.features,g.relation_game_name,g.relation_game_id,p.game_id,p.thumbnail')
                ->where('p.id', $id)
                ->join(['tab_game' => 'g'], 'g.relation_game_id=p.game_id','left')
                ->find();
        if ($data) {
            $data = $data->toArray();
            $portalPostModel->where('id', $id)->setInc('post_hits');
        }else{
            $this->error('不存在文章或文章已删除');
        }
        if($data['thumbnail']){
            $this->assign('own_share_img',cmf_get_image_url($data['thumbnail']));
        }
        $this->assign('data', $data);


        //占位图
        $this->slide('slider_gift');
        return $this->fetch();
    }


    /**
     * [搜索文章]
     * @author chen
     */
    public function searchArticle($article_name = '')
    {
        if (empty($article_name)) {
            return json(['code' => 0, 'msg' => '']);
        }else{
            $gmap['game_name'] = ['like', '%' . addcslashes($article_name, '%') . '%'];
            $game_ids = array_column(get_game_list('id',$gmap),'id');
            $game_ids = empty($game_ids)?[-1]:$game_ids;
        }
        $postService = new PostService();
        $map['category'] = [2, 3, 4];
        $map['game_id'] = $game_ids;
        $map['sdk_version'] = get_devices_type();
        $map['post_status'] = 1;
        $data = $postService->WapArticleList($map, false)->each(function ($item, $key) {
            $item['post_title'] = '《'.$item['relation_game_name'].'》'.$item['post_title'];
            $item['url'] = url('Article/detail', ['id' => $item['id']]);
            return $item;
        });
        if (empty($data->toArray())) {
            return json(['code' => 0, 'msg' => '']);
        } else {
            return json(['code' => 1, 'data' => $data]);
        }
    }


}