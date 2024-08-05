<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\portal\controller;

use cmf\controller\HomeBaseController;

class SearchController extends HomeBaseController
{
    /**
     * 搜索
     * @return mixed
     */
    public function index()
    {
        $keyword = $this->request->param('keyword');

        if (empty($keyword)) {
            $this->error("关键词不能为空！请重新输入！");
        }

        $this->assign("keyword", $keyword);
        return $this->fetch('/search');
    }
}
