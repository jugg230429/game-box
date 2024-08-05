<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use app\portal\service\PostService;

class PageController extends HomeBaseController
{
    /**
     * 页面管理
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $postService = new PostService();
        $pageId = $this->request->param('id', 0, 'intval');
        $page = $postService->publishedPage($pageId);

        if (empty($page)) {
            abort(404, ' 页面不存在!');
        }

        $this->assign('page', $page);

        $more = $page['more'];

        $tplName = empty($more['template']) ? 'page' : $more['template'];

        return $this->fetch("/$tplName");
    }

}
