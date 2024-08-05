<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\mobile\controller;

use cmf\controller\HomeBaseController;

class ErrorController extends HomeBaseController
{
    /**
     * 空控制器操作
     *
     * @return mixed
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\3\30 0030 16:05
     */
    public function index()
    {

        $url = request()->scheme() . '://' . request()->host() . '/' . request()->pathinfo();

        $this->assign('url', $url);

        return $this->fetch('Index/404');
    }

    /**
     * 空操作
     *
     * @return mixed
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\3\30 0030 16:05
     */
    public function _empty()
    {

        $url = request()->scheme() . '://' . request()->host() . '/' . request()->pathinfo();

        $this->assign('url', $url);

        return $this->fetch('Index/404');

    }

}
