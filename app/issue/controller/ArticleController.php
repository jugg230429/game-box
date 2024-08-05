<?php

namespace app\issue\controller;

use app\issue\logic\WordLogic;
use app\site\model\PortalCategoryModel;
use app\site\service\PostService;

class ArticleController extends ManagementBaseController
{


    /**
     * @资讯列表
     *
     * @author: zsl
     * @since: 2020/7/20 9:45
     */
    public function index()
    {
        $param = $this -> request -> param();
        $categoryId = $this -> request -> param('category', 0, 'intval');
        $postService = new PostService();
        if ($param['game_name']) {
            $game_ids = get_promote_relation_game_id([$param['game_name']]);
            $param['game_id'] = $game_ids;
        }
        $param['showwebsite'] = 5;
        $param['post_status'] = 1;
        $row = $param['row'] ? $param['row'] : '10';
        $data = $postService -> ArticleList($param, false, $row);
        $data -> appends($param);
        $portalCategoryModel = new PortalCategoryModel();
        $categoryTree = $portalCategoryModel -> adminCategoryTree($categoryId, 6);
        $this -> assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this -> assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this -> assign('keyword', isset($param['keyword']) ? $param['keyword'] : '');
        $this -> assign('articles', $data -> items());
        $this -> assign('category_tree', $categoryTree);
        $this -> assign('category', $categoryId);
        $this -> assign('page', $data -> render());
        return $this -> fetch();
    }

    /**
     * @下载文档
     *
     * @author: zsl
     * @since: 2020/7/20 10:16
     */
    public function download()
    {
        $id = $this -> request -> param('id');
        $postService = new PostService();
        $info = $postService -> publishedArticle($id);
        if (empty($info)) {
            die('文档不存在');
        }
        $html = $info -> post_content;
        $word = new WordLogic();
        $word -> export($html, $info -> post_title . '.doc');


    }


}