<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\site\service;

use app\site\model\PortalPostModel;
use think\db\Query;

class PostService
{
    /**
     * 文章查询
     * @param $filter
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function adminArticleList($filter)
    {
        return $this->adminPostList($filter);
    }

    /**
     * 文章查询
     * @param $filter
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function ArticleList($filter, $isPage = false, $limit = 10,$order='')
    {
        return $this->PostList($filter, $isPage, $limit,$order);
    }

    /**
     * 文章查询
     * @param $filter
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function NewArticle($filter, $isPage = false)
    {
        return $this->NewPost($filter, $isPage);
    }

    /**
     * 文章查询
     * @param $filter
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function WapArticleList($filter, $isPage = false)
    {
        return $this->WapPostList($filter, $isPage);
    }

    /**
     * 游戏文章查询
     * @param $filter
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function GameArticleList($filter, $isPage = false, $limit = 10)
    {
        return $this->GamePostList($filter, $isPage, $limit);
    }

    /**
     * 页面文章列表
     * @param $filter
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function adminPageList($filter)
    {
        return $this->adminPostList($filter, true);
    }

    /**
     * 文章查询
     * @param      $filter
     * @param bool $isPage
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function adminPostList($filter, $isPage = false)
    {
        $page = $filter['row'] ? $filter['row'] : 10;
        $join = [];
        $category = empty($filter['category']) ? 0 : intval($filter['category']);
        if (!empty($category)) {
            array_push($join, [
                '__PORTAL_CATEGORY_POST__ b', 'a.id = b.post_id'
            ]);
            $field = 'a.*,b.id AS post_category_id,b.list_order,b.category_id';
        }

        $portalPostModel = new PortalPostModel();
        $articles = $portalPostModel->alias('a')->field($field)
            ->join($join)
            ->where('a.create_time', '>=', 0)
            ->where('a.delete_time', 0)
            ->order('a.sort desc,a.id desc')
            ->where(function (Query $query) use ($filter, $isPage) {

                $category = empty($filter['category']) ? 0 : intval($filter['category']);
                if (!empty($category)) {
                    $query->where('b.category_id', $category);
                }

                $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
                $endTime = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
                if (!empty($startTime) && !empty($endTime)) {
                    $query->where('a.update_time', 'between', [$startTime, $endTime+24*3600-1]);
                }
                if (!empty($startTime)) {
                    $query->where('a.update_time', '>=', $startTime);
                }
                if (!empty($endTime)) {
                    $query->where('a.update_time', '<=', $endTime+24*3600-1);
                }
                $game_id = empty($filter['game_id']) ? '' : $filter['game_id'];
                if ($game_id) {
                    $query->where('a.game_id','in', $game_id);
                }
                $showwebsite = empty($filter['showwebsite']) ? '' : $filter['showwebsite'];
                if($showwebsite){
                    switch ($showwebsite){
                        case 1:
                            $query->where('a.website', ['in','2,3,6,7,9']);
                            break;
                        case 2:
                            $query->where('a.website', ['in','1,3,5,7,9']);
                            break;
                        case 3:
                            $query->where('a.website', ['in','4,5,6,7']);
                            break;
                        case 4:
                            $query->where('a.website', ['in','8']);
                            break;
                        case 5:
                            $query->where('a.website', ['in','16']);
                            break;
                        case 6:
                            $query->where('a.website', ['in','17']);
                            break;
                    }
                }
                $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
                if (!empty($keyword)) {
                    $query->where('a.post_title', 'like', "%$keyword%");
                }
                $post_status = $filter['post_status'] == '' ? '' : $filter['post_status'];
                if ($post_status != '') {
                    $query->where('a.post_status', 'eq', $post_status);
                }
                if ($isPage) {
                    $query->where('a.post_type', 2);
                } else {
                    $query->where('a.post_type', 1);
                }
            })
            ->order('update_time', 'DESC')
            ->paginate($page);

        return $articles;

    }

    /**
     * 文章查询
     * @param      $filter
     * @param bool $isPage
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function NewPost($filter, $isPage = false)
    {
        $join = [];
        $category = empty($filter['category']) ? 0 : ($filter['category']);
        if (!empty($category)) {
            array_push($join, [
                    '__PORTAL_CATEGORY_POST__ b', 'a.id = b.post_id'
            ]);
            $field = 'a.*,b.id AS post_category_id,b.list_order,b.category_id';
        }
        $portalPostModel = new PortalPostModel();
        $articles = $portalPostModel->alias('a')->field($field)
                ->join($join)
                ->where('a.create_time', '>=', 0)
                ->where('a.start_time', ['<', time()], ['=', 0], 'or')
                ->where('a.end_time', ['>', time()], ['=', 0], 'or')
                ->where('a.delete_time', 0)
                ->order('id desc')
                ->where(function (Query $query) use ($filter, $isPage) {

                    $category = empty($filter['category']) ? 0 : $filter['category'];
                    if (!empty($category)) {
                        $query->where('b.category_id', 'in', $category);
                    }
                    $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
                    $endTime = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
                    if (!empty($startTime) && !empty($endTime)) {
                        $query->where('a.update_time', 'between', [$startTime, $endTime]);
                    }
                    if (!empty($startTime)) {
                        $query->where('a.update_time', '>=', $startTime);
                    }
                    if (!empty($endTime)) {
                        $query->where('a.update_time', '<=', $endTime);
                    }
                    $module = request()->module();
                    if ($category != 7) {
                        if ($module == 'media') {
                            $query->where('a.website', 'in', [1, 3, 5, 7,9]);
                        } elseif ($module == 'mobile') {
                            $query->where('a.website', 'in', [2, 3, 6, 7,9]);
                        } elseif ($module == 'channelsite' || $module == 'channelwap') {
                            $query->where('a.website', 8);
                        } elseif ($module == 'app') {
                            $query->where('a.website', 'in', [4, 5, 6, 7,9]);
                        }
                    }else{
                        //文档
                        if ($module == 'media') {
                            $query->where('a.website', 'in', [1, 3, 5, 7,9]);
                        }
                    }
                    $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
                    if (!empty($keyword)) {
                        $query->where('a.post_title', 'like', "%$keyword%");
                    }
                    $post_status = empty($filter['post_status']) ? '' : $filter['post_status'];
                    if (!empty($post_status)) {
                        $query->where('a.post_status', 'eq', $post_status);
                    }
                    $game_id = empty($filter['game_id']) ? '' : $filter['game_id'];
                    if (!empty($game_id)) {
                        $query->where('a.game_id', 'in', $game_id);
                    }
                    $id = empty($filter['id']) ? '' : $filter['id'];
                    if (!empty($id)) {
                        $query->where('a.id', 'eq', $id);
                    }
                    if ($isPage) {
                        $query->where('a.post_type', 2);
                    } else {
                        $query->where('a.post_type', 1);
                    }
                })
                ->group('a.id')
                ->find();
        return $articles ? $articles->toArray() : [];

    }

    /**
     * 文章查询
     * @param      $filter
     * @param bool $isPage
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function PostList($filter, $isPage = false, $limit = 10,$order='a.sort desc,a.id desc')
    {
        if(!$order){
            $order='a.sort desc,a.id desc';
        }
        $join = [];
        $category = empty($filter['category']) ? 0 : ($filter['category']);
        if (!empty($category)) {
            array_push($join, [
                '__PORTAL_CATEGORY_POST__ b', 'a.id = b.post_id'
            ]);
            $field = 'a.*,b.id AS post_category_id,b.list_order,b.category_id';
        }
        if(cmf_is_mobile()){
            array_push($join, [
                    ['tab_game'=>'g'], 'a.game_id = g.relation_game_id','left'
            ]);
        }
        // 测试游戏 渠道独占游戏 在官方网站隐藏
        $hidden_game_ids = empty($filter['hidden_game_ids']) ? 0 : $filter['hidden_game_ids'];
        $map_tmp = [];
        if (!empty($hidden_game_ids)) {
            $map_tmp['a.game_id'] = ['notin', $hidden_game_ids]; // ->where('a.game_id', 'notin', $hidden_game_ids);
        }
        $portalPostModel = new PortalPostModel();
        $articles = $portalPostModel->alias('a')->field($field)
            ->join($join)
            ->where('a.create_time', '>=', 0)
            ->where('a.start_time', ['<', time()], ['=', 0], 'or')
            ->where('a.end_time', ['>', time()], ['=', 0], 'or')
            ->where('a.delete_time', 0)
            ->where($map_tmp)
            ->order($order)
            ->where(function (Query $query) use ($filter, $isPage) {

                $category = empty($filter['category']) ? 0 : $filter['category'];
                if (!empty($category)) {
                    $query->where('b.category_id', 'in', $category);
                }

                $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
                $endTime = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
                if (!empty($startTime) && !empty($endTime)) {
                    $query->where('a.update_time', 'between', [$startTime, $endTime]);
                }
                if (!empty($startTime)) {
                    $query->where('a.update_time', '>=', $startTime);
                }
                if (!empty($endTime)) {
                    $query->where('a.update_time', '<=', $endTime);
                }
                $module = request()->module();
                if ($category != 7) {
                    if ($module == 'media') {
                        $query->where('a.website', 'in', [1, 3, 5, 7,9]);
                    } elseif ($module == 'mobile') {
                        $query->where('a.website', 'in', [2, 3, 6, 7,9]);
                    } elseif ($module == 'channelsite' || $module == 'channelwap') {
                        $query->where('a.website', 8);
                    } elseif ($module == 'app') {
                        $query->where('a.website', 'in', [4, 5, 6, 7,9]);
                    } elseif ($module == 'issue') {
                        $query->where('a.website', 'in', [16]);
                    }
                }else{
                    //文档
                    if ($module == 'media') {
                        $query->where('a.website', 'in', [1, 3, 5, 7,9]);
                    }
                }
                $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
                if (!empty($keyword)) {
                    $query->where('a.post_title', 'like', "%$keyword%");
                }
                $post_status = empty($filter['post_status']) ? '' : $filter['post_status'];
                if (!empty($post_status)) {
                    $query->where('a.post_status', 'eq', $post_status);
                }
                $game_id = empty($filter['game_id']) ? '' : $filter['game_id'];
                if (!empty($game_id)) {
                    $query->where('a.game_id', 'in', $game_id);
                }
                $id = empty($filter['id']) ? '' : $filter['id'];
                if (!empty($id)) {
                    $query->where('a.id', 'eq', $id);
                }
                if ($isPage) {
                    $query->where('a.post_type', 2);
                } else {
                    $query->where('a.post_type', 1);
                }
            })
            ->group('a.id')
            ->paginate($limit);
        return $articles;

    }

    /**
     * 文章查询
     * @param      $filter
     * @param bool $isPage
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function WapPostList($filter, $isPage = false)
    {
        $join = [];
        $category = empty($filter['category']) ? 0 : ($filter['category']);
        if (!empty($category)) {
            array_push($join, [
                '__PORTAL_CATEGORY_POST__ b', 'a.id = b.post_id'
            ]);
            $field = 'a.*,b.id AS post_category_id,b.list_order,b.category_id';
        }
        // 测试游戏 渠道独占游戏 在官方网站隐藏
        $hidden_game_ids = empty($filter['hidden_game_ids']) ? 0 : $filter['hidden_game_ids'];
        $map_tmp = [];
        if (!empty($hidden_game_ids)) {
            $map_tmp['a.game_id'] = ['notin', $hidden_game_ids]; // ->where('a.game_id', 'notin', $hidden_game_ids);
        }

        $portalPostModel = new PortalPostModel();
        $articles = $portalPostModel->alias('a')->field($field)
            ->join($join)
            ->join(['tab_game' => 'g'], 'g.relation_game_id=a.game_id','left')
            ->where('a.create_time', '>=', 0)
            ->where('a.start_time', ['<', time()], ['=', 0], 'or')
            ->where('a.end_time', ['>', time()], ['=', 0], 'or')
            ->where('a.delete_time', 0)
            ->where($map_tmp)
            ->order('a.sort desc,a.id desc')
            ->where(function (Query $query) use ($filter, $isPage) {

                $category = empty($filter['category']) ? 0 : $filter['category'];
                if (!empty($category)) {
                    $query->where('b.category_id', 'in', $category);
                }
                $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
                $endTime = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
                if (!empty($startTime) && !empty($endTime)) {
                    $query->where('a.update_time', 'between', [$startTime, $endTime]);
                }
                if (!empty($startTime)) {
                    $query->where('a.update_time', '>=', $startTime);
                }
                if (!empty($endTime)) {
                    $query->where('a.update_time', '<=', $endTime);
                }

                $module = request()->module();
                if ($module == 'media') {
                    $query->where('a.website', 'in', [1, 3, 5, 7]);
                } elseif ($module == 'mobile') {
                    $query->where('a.website', 'in', [2, 3, 6, 7]);
                } elseif ($module == 'channelsite' || $module == 'channelwap') {
                    $query->where('a.website', 8);
                } elseif ($module == 'app') {
                    $query->where('a.website', 'in', [4, 5, 6, 7]);
                }
                $post_status = empty($filter['post_status']) ? '' : $filter['post_status'];
                if (!empty($post_status)) {
                    $query->where('a.post_status', 'eq', $post_status);
                }
                $game_id = empty($filter['game_id']) ? '' : $filter['game_id'];
                if (!empty($game_id)) {

                    $query->where('a.game_id', 'in', $game_id);
                }
                $id = empty($filter['id']) ? '' : $filter['id'];
                if (!empty($id)) {
                    $query->where('a.id', 'eq', $id);
                }
                $game_name = empty($filter['game_name']) ? '' : $filter['game_name'];
                if (!empty($game_name)) {
                    $query->where('game_name', 'like', "%$game_name%");
                }
                $post_title = empty($filter['post_title']) ? '' : $filter['post_title'];
                if (!empty($post_title)) {
                    $query->where('post_title', 'like', "%$post_title%");
                }
                if ($isPage) {
                    $query->where('a.post_type', 2);
                } else {
                    $query->where('a.post_type', 1);
                }
            })
            ->group('a.id')
            ->select();
        return $articles;

    }

    /**
     * 文章查询
     * @param      $filter
     * @param bool $isPage
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function GamePostList($filter, $isPage = false, $limit = 10)
    {
        $join = [];
        $category = empty($filter['category']) ? 0 : ($filter['category']);
        if (!empty($category)) {
            array_push($join, [
                '__PORTAL_CATEGORY_POST__ b', 'a.id = b.post_id'
            ]);
            $field = 'a.*,b.id AS post_category_id,b.list_order,b.category_id';
        }
        $portalPostModel = new PortalPostModel();
        $articles = $portalPostModel->alias('a')->field($field)
            ->join($join)
            ->where('a.create_time', '>=', 0)
            ->where('a.start_time', ['<', time()], ['=', 0], 'or')
            ->where('a.end_time', ['>', time()], ['=', 0], 'or')
            ->where('a.delete_time', 0)
            ->order('a.sort desc,a.id desc')
            ->where(function (Query $query) use ($filter, $isPage) {

                $category = empty($filter['category']) ? 0 : $filter['category'];
                if (!empty($category)) {
                    $query->where('b.category_id', 'in', $category);
                }

                $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
                $endTime = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
                if (!empty($startTime) && !empty($endTime)) {
                    $query->where('a.update_time', 'between', [$startTime, $endTime]);
                }
                if (!empty($startTime)) {
                    $query->where('a.update_time', '>=', $startTime);
                }
                if (!empty($endTime)) {
                    $query->where('a.update_time', '<=', $endTime);
                }
                if (!empty($filter['id'])) {
                    $query->where('a.id', 'neq', $filter['id']);
                }
                $module = request()->module();
                if ($module == 'media') {
                    $query->where('a.website', 'in', [1, 3, 5, 7]);
                } elseif ($module == 'mobile') {
                    $query->where('a.website', 'in', [2, 3, 6, 7]);
                } elseif ($module == 'channelsite' || $module == 'channelwap') {
                    $query->where('a.website', 8);
                } elseif ($module == 'app') {
                    $query->where('a.website', 'in', [4, 5, 6, 7]);
                }
                $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
                if (!empty($keyword)) {
                    $query->where('a.post_title', 'like', "%$keyword%");
                }
                $post_status = empty($filter['post_status']) ? '' : $filter['post_status'];
                if (!empty($post_status)) {
                    $query->where('a.post_status', 'eq', $post_status);
                }
                $game_id = empty($filter['game_id']) ? '' : $filter['game_id'];
                if (!empty($game_id)) {
                    $query->where('a.game_id', 'in', $game_id);
                }
                if ($isPage) {
                    $query->where('a.post_type', 2);
                } else {
                    $query->where('a.post_type', 1);
                }
            })
            ->group('a.id')
            ->limit($limit)
            ->select();
        return $articles;

    }


    /**
     * 已发布文章查询
     * @param int $postId 文章id
     * @param int $categoryId 分类id
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function publishedArticle($postId, $categoryId = 0)
    {
        $portalPostModel = new PortalPostModel();

        if (empty($categoryId)) {

            $where = [
                'post.post_type' => 1,
                'post.post_status' => 1,
                'post.delete_time' => 0,
                'post.id' => $postId
            ];

            $article = $portalPostModel->alias('post')->field('post.*')
                ->where($where)
                ->where('post.start_time', ['<', time()], ['=', 0], 'or')
                ->where('post.end_time', ['>', time()], ['=', 0], 'or')
                ->find();
        } else {
            $where = [
                'post.post_type' => 1,
                'post.post_status' => 1,
                'post.delete_time' => 0,
                'relation.category_id' => $categoryId,
                'relation.post_id' => $postId
            ];

            $join = [
                ['__PORTAL_CATEGORY_POST__ relation', 'post.id = relation.post_id']
            ];
            $article = $portalPostModel->alias('post')->field('post.*')
                ->join($join)
                ->where($where)
                ->where('a.start_time', ['<', time()], ['=', 0], 'or')
                ->where('a.end_time', ['>', time()], ['=', 0], 'or')
                //->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->find();
        }


        return $article;
    }

    /**
     * 上一篇文章
     * @param int $postId 文章id
     * @param int $categoryId 分类id
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function publishedPrevArticle($postId, $categoryId = 0,$game_id=0)
    {
        $portalPostModel = new PortalPostModel();

        if (empty($categoryId)) {

            $where = [
                'post.post_type' => 1,
                'post.post_status' => 1,
                'post.delete_time' => 0,
                'post.id ' => ['<', $postId]
            ];
            if($game_id > 0){
                $where['game_id'] = $game_id;
            }
            $article = $portalPostModel
                ->alias('post')
                ->field('post.*')
                ->where($where)
                ->where('a.start_time', ['<', time()], ['=', 0], 'or')
                ->where('a.end_time', ['>', time()], ['=', 0], 'or')
                //->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->order('sort', 'DESC')
                ->find();

        } else {
            $where = [
                'post.post_type' => 1,
                'post.post_status' => 1,
                'post.delete_time' => 0,
                'relation.category_id' => $categoryId,
                'relation.post_id' => ['<', $postId]
            ];
            if($game_id > 0){
                $where['game_id'] = $game_id;
            }
            $join = [
                ['__PORTAL_CATEGORY_POST__ relation', 'post.id = relation.post_id']
            ];
            $module = request()->module();
            if ($module == 'media') {
                $where['post.website'] = ['in',[1, 3, 5, 7]];
            } elseif ($module == 'mobile') {
                $where['post.website'] = ['in',[2, 3, 6, 7]];
            } elseif ($module == 'channelsite' || $module == 'channelwap') {
                $where['post.website'] =  8;
            } elseif ($module == 'app') {
                $where['post.website'] = ['in',[4, 5, 6, 7]];
            }
            $article = $portalPostModel
                ->alias('post')
                ->field('post.*')
                ->join($join)
                ->where($where)
                ->where('post.start_time', ['<', time()], ['=', 0], 'or')
                ->where('post.end_time', ['>', time()], ['=', 0], 'or')
                //->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->order('sort', 'DESC')
                ->find();
        }


        return $article;
    }

    /**
     * 下一篇文章
     * @param int $postId 文章id
     * @param int $categoryId 分类id
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function publishedNextArticle($postId, $categoryId = 0,$game_id=0)
    {
        $portalPostModel = new PortalPostModel();

        if (empty($categoryId)) {

            $where = [
                'post.post_type' => 1,
                'post.post_status' => 1,
                'post.delete_time' => 0,
                'post.id' => ['>', $postId]
            ];

            $article = $portalPostModel->alias('post')->field('post.*')
                ->where($where)
                ->where('post.start_time', ['<', time()], ['=', 0], 'or')
                ->where('post.end_time', ['>', time()], ['=', 0], 'or')
                //->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->order('sort', 'ASC')
                ->find();
        } else {
            $where = [
                'post.post_type' => 1,
                'post.post_status' => 1,
                'post.delete_time' => 0,
                'relation.category_id' => $categoryId,
                'relation.post_id' => ['>', $postId]
            ];

            $join = [
                ['__PORTAL_CATEGORY_POST__ relation', 'post.id = relation.post_id']
            ];
            $module = request()->module();
            if ($module == 'media') {
                $where['post.website'] = ['in',[1, 3, 5, 7]];
            } elseif ($module == 'mobile') {
                $where['post.website'] = ['in',[2, 3, 6, 7]];
            } elseif ($module == 'channelsite' || $module == 'channelwap') {
                $where['post.website'] =  8;
            } elseif ($module == 'app') {
                $where['post.website'] = ['in',[4, 5, 6, 7]];
            }
            $article = $portalPostModel->alias('post')->field('post.*')
                ->join($join)
                ->where($where)
                ->where('post.start_time', ['<', time()], ['=', 0], 'or')
                ->where('post.end_time', ['>', time()], ['=', 0], 'or')
                //->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->order('sort', 'ASC')
                ->find();
        }


        return $article;
    }

    /**
     * 页面管理查询
     * @param int $pageId 文章id
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function publishedPage($pageId)
    {

        $where = [
            'post_type' => 2,
            'post_status' => 1,
            'delete_time' => 0,
            'id' => $pageId
        ];

        $portalPostModel = new PortalPostModel();
        $page = $portalPostModel
            ->where($where)
            ->where('published_time', ['< time', time()], ['> time', 0], 'and')
            ->find();

        return $page;
    }

}
