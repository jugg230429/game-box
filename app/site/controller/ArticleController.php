<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\site\controller;

use cmf\controller\AdminBaseController;
use app\site\model\PortalPostModel;
use app\site\service\PostService;
use app\site\model\PortalCategoryModel;
use think\Db;
use app\admin\model\ThemeModel;

class ArticleController extends AdminBaseController
{

    /**
     * 文章列表
     * @adminMenu(
     *     'name'   => 'PC官网文章管理',
     *     'parent' => 'portal/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文章列表',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists()
    {
        $content = hook_one('portal_admin_article_index_view');

        if (!empty($content)) {
            return $content;
        }

        $param = $this->request->param();
        $categoryId = $this->request->param('category', 2, 'intval');
        $postService = new PostService();
        if($param['game_name']){
            $game_ids = get_promote_relation_game_id([$param['game_name']]);
            $param['game_id'] = $game_ids;
        }

        $data = $postService->adminArticleList($param);

        $data->appends($param);

        $portalCategoryModel = new PortalCategoryModel();
        $categoryTree = $portalCategoryModel->adminCategoryTree($categoryId);

        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('keyword', isset($param['keyword']) ? $param['keyword'] : '');
        $this->assign('articles', $data->items());
        $this->assign('category_tree', $categoryTree);
        $this->assign('category', $categoryId);
        $this->assign('page', $data->render());

        return $this->fetch();
    }

    /**
     * 文章列表
     * @adminMenu(
     *     'name'   => 'PC官网文档管理',
     *     'parent' => 'portal/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文章列表',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists_doc()
    {
        $content = hook_one('portal_admin_article_index_view');

        if (!empty($content)) {
            return $content;
        }

        $param = $this->request->param();

        $categoryId = 7;
        $param['category'] = 7;
        $postService = new PostService();
        $data = $postService->adminArticleList($param);

        $data->appends($param);

        $portalCategoryModel = new PortalCategoryModel();
        $categoryTree = $portalCategoryModel->adminCategoryTree($categoryId);

        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('keyword', isset($param['keyword']) ? $param['keyword'] : '');
        $this->assign('articles', $data->items());
        $this->assign('category_tree', $categoryTree);
        $this->assign('category', $categoryId);
        $this->assign('page', $data->render());

        return $this->fetch();
    }

    /**
     * 添加文章
     * @adminMenu(
     *     'name'   => '添加文章',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加文章',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        $content = hook_one('portal_admin_article_add_view');

        if (!empty($content)) {
            return $content;
        }

        $themeModel = new ThemeModel();
        $articleThemeFiles = $themeModel->getActionThemeFiles('site/Article/wap');
        $this->assign('article_theme_files', $articleThemeFiles);
        $this->assign('category', $this->request->param('category', 0, 'intval'));
        return $this->fetch();
    }

    /**
     * 添加文章提交
     * @adminMenu(
     *     'name'   => '添加文章提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加文章提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['post']['start_time'] = empty($data['post']['start_time']) ? time() : strtotime($data['post']['start_time']);
            $data['post']['end_time'] = empty($data['post']['end_time']) ? 0 : strtotime($data['post']['end_time']);
            //状态只能设置默认值。未发布、未置顶、未推荐
            $data['post']['post_status'] = 1;
            $data['post']['is_top'] = 0;
            $data['post']['recommended'] = 0;
            $data['post']['website'] = empty($data['post']['website']) ? 0 : array_sum($data['post']['website']);
            $role_ids = $data['role_id'] ?? '';
            if($data['post']['website'] == 17 && empty($role_ids)){
                $this->error('请选择角色权限');
            }
            unset($data['role_id']);
            $data['post']['role_ids'] = json_encode($role_ids);
            $post = $data['post'];
            if ($data['category'] == 7) {
                $result = $this->validate($post, 'Article.wendang');
            } else {
                $result = $this->validate($post, 'Article');
            }
            if ($result !== true) {
                $this->error($result);
            }

            $portalPostModel = new PortalPostModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url) {
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }

            if (!empty($data['file_names']) && !empty($data['file_urls'])) {
                $data['post']['more']['files'] = [];
                foreach ($data['file_urls'] as $key => $url) {
                    $fileUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['files'], ["url" => $fileUrl, "name" => $data['file_names'][$key]]);
                }
            }
            $portalPostModel->adminAddArticle($data['post'], $data['post']['categories']);

            $data['post']['id'] = $portalPostModel->id;
            $hookParam = [
                'is_add' => true,
                'article' => $data['post']
            ];
            hook('portal_admin_after_save_article', $hookParam);
            if ($data['category'] == 7) {
                write_action_log("文档设置");
            } else {
                write_action_log("活动资讯设置");
            }
            if (in_array($data['category'], [2, 3, 4, 5])) {
                $this->success('添加成功!', url('Article/lists', ['category' => $data['category']]));
            } else {
                $this->success('添加成功!', url('Article/lists_doc', ['category' => $data['category']]));
            }
        }

    }

    /**
     * 编辑文章
     * @adminMenu(
     *     'name'   => '编辑文章',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑文章',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $content = hook_one('portal_admin_article_edit_view');
        if (!empty($content)) {
            return $content;
        }

        $id = $this->request->param('id', 0, 'intval');

        $portalPostModel = new PortalPostModel();
        $post = $portalPostModel->where('id', $id)->find();
        $post['role_ids'] = json_decode($post['role_ids'], true);
        // echo json_encode($post);exit;
        // var_dump($post);exit;
        $postCategories = $post->categories()->alias('a')->column('a.name', 'a.id');
        $postCategoryIds = implode(',', array_keys($postCategories));

        $themeModel = new ThemeModel();
        $articleThemeFiles = $themeModel->getActionThemeFiles('portal/Article/index');
        $this->assign('article_theme_files', $articleThemeFiles);
        $this->assign('post', $post);
        $this->assign('category', $this->request->param('category', 0, 'intval'));
        $this->assign('post_categories', $postCategories);
        $this->assign('post_category_ids', $postCategoryIds);
        $this->assign('role_id', $post['role_ids']);

        return $this->fetch();
    }

    /**
     * 编辑文章提交
     * @adminMenu(
     *     'name'   => '编辑文章提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑文章提交',
     *     'param'  => ''
     * )
     * @throws \think\Exception
     */
    public function editPost()
    {

        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['post']['start_time'] = empty($data['post']['start_time']) ? time() : strtotime($data['post']['start_time']);
            $data['post']['end_time'] = empty($data['post']['end_time']) ? 0 : strtotime($data['post']['end_time']);
            $data['post']['website'] = empty($data['post']['website']) ? 0 : array_sum($data['post']['website']);
            //需要抹除发布、置顶、推荐的修改。
            unset($data['post']['post_status']);
            unset($data['post']['is_top']);
            unset($data['post']['recommended']);
            $role_ids = $data['role_id'] ?? '';
            if($data['post']['website'] == 17 && empty($role_ids)){
                $this->error('请选择角色权限');
            }
            unset($data['role_id']);
            $data['post']['role_ids'] = json_encode($role_ids);
            
            $post = $data['post'];
            if ($data['category'] == 7) {
                $result = $this->validate($post, 'Article.wendang');
            } else {
                $result = $this->validate($post, 'Article');
            }

            if ($result !== true) {
                $this->error($result);
            }

            $portalPostModel = new PortalPostModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url) {
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }

            if (!empty($data['file_names']) && !empty($data['file_urls'])) {
                $data['post']['more']['files'] = [];
                foreach ($data['file_urls'] as $key => $url) {
                    $fileUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['files'], ["url" => $fileUrl, "name" => $data['file_names'][$key]]);
                }
            }

            $result = $portalPostModel->adminEditArticle($data['post'], $data['post']['categories']);
            $hookParam = [
                'is_add' => false,
                'article' => $data['post']
            ];
            hook('portal_admin_after_save_article', $hookParam);
            if ($data['category'] == 7) {
                write_action_log("文档设置");
            } else {
                write_action_log("活动资讯设置");
            }
            if (in_array($data['category'], [2, 3, 4, 5])) {
                $this->success('保存成功!', url('Article/lists', ['category' => $data['category']]));
            } else {
                $this->success('保存成功!', url('Article/lists_doc', ['category' => $data['category']]));
            }
        }
    }

    /**
     * 文章删除
     * @adminMenu(
     *     'name'   => '文章删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文章删除',
     *     'param'  => ''
     * )
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delete()
    {
        $param = $this->request->param();
        $portalPostModel = new PortalPostModel();

        if (isset($param['id'])) {
            $id = $this->request->param('id', 0, 'intval');
            $result = $portalPostModel->where('id', $id)->find()->toArray();
            $data = [
                'object_id' => $result['id'],
                'create_time' => time(),
                'table_name' => 'portal_post',
                'name' => $result['post_title'],
                'user_id' => cmf_get_current_admin_id()
            ];
            $resultPortal = $portalPostModel
                ->where('id', $id)
                ->update(['delete_time' => time()]);
            if ($resultPortal) {
                Db::name('portal_category_post')->where('post_id', $id)->update(['status' => 0]);
                Db::name('portal_tag_post')->where('post_id', $id)->update(['status' => 0]);

                Db::name('recycleBin')->insert($data);
            }
            write_action_log("活动资讯设置");
            $this->success("删除成功！", '');

        }

        if (isset($param['ids'])) {
            $ids = $this->request->param('ids/a');
            $recycle = $portalPostModel->where('id', 'in', $ids)->select();
            $result = $portalPostModel->where('id', 'in', $ids)->update(['delete_time' => time()]);
            if ($result) {
                Db::name('portal_category_post')->where('post_id', 'in', $ids)->update(['status' => 0]);
                Db::name('portal_tag_post')->where('post_id', 'in', $ids)->update(['status' => 0]);
                foreach ($recycle as $value) {
                    $data = [
                        'object_id' => $value['id'],
                        'create_time' => time(),
                        'table_name' => 'portal_post',
                        'name' => $value['post_title'],
                        'user_id' => cmf_get_current_admin_id()
                    ];
                    Db::name('recycleBin')->insert($data);
                }
                $this->success("删除成功！", '');
            }
        }
    }

    /**
     * @函数或方法说明
     * @设置浏览量
     * @author: 郭家屯
     * @since: 2019/4/12 17:39
     */
    public function setparam()
    {
        $id = $this->request->param('id/d');
        $field = $this->request->param('param/s');
        $value = $this->request->param('data/d');
        $result = Db::name('portal_post')->where('id', $id)->setField($field, $value);
        if ($result !== false) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }

    /**
     * 文章发布
     * @adminMenu(
     *     'name'   => '文章发布',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文章发布',
     *     'param'  => ''
     * )
     */
    public function publish()
    {
        $param = $this->request->param();
        $portalPostModel = new PortalPostModel();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            $portalPostModel->where('id', 'in', $ids)->update(['post_status' => 1, 'published_time' => time()]);
            write_action_log("活动资讯设置");
            $this->success("设置成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            $portalPostModel->where('id', 'in', $ids)->update(['post_status' => 0]);
            write_action_log("活动资讯设置");
            $this->success("设置成功！", '');
        }

    }

    /**
     * 文章置顶
     * @adminMenu(
     *     'name'   => '文章置顶',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文章置顶',
     *     'param'  => ''
     * )
     */
    public function top()
    {
        $param = $this->request->param();
        $portalPostModel = new PortalPostModel();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');

            $portalPostModel->where('id', 'in', $ids)->update(['is_top' => 1]);

            $this->success("置顶成功！", '');

        }

        if (isset($_POST['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');

            $portalPostModel->where('id', 'in', $ids)->update(['is_top' => 0]);

            $this->success("取消置顶成功！", '');
        }
    }

    /**
     * 文章推荐
     * @adminMenu(
     *     'name'   => '文章推荐',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文章推荐',
     *     'param'  => ''
     * )
     */
    public function recommend()
    {
        $param = $this->request->param();
        $portalPostModel = new PortalPostModel();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');

            $portalPostModel->where('id', 'in', $ids)->update(['recommended' => 1]);

            $this->success("推荐成功！", '');

        }
        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');

            $portalPostModel->where('id', 'in', $ids)->update(['recommended' => 0]);

            $this->success("取消推荐成功！", '');

        }
    }

    /**
     * 文章排序
     * @adminMenu(
     *     'name'   => '文章排序',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '文章排序',
     *     'param'  => ''
     * )
     */
    public function listOrder()
    {
        parent::listOrders(Db::name('portal_category_post'));
        $this->success("排序更新成功！", '');
    }
}
