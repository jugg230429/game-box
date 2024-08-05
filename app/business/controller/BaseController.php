<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\business\controller;

use cmf\controller\HomeBaseController;
use cmf\paginator\Bootstrap;
use think\WechatAuth;
use think\Db;

class BaseController extends HomeBaseController
{
    public $promote;
    public function __construct()
    {
        parent::__construct();
        $bid = is_b_login();
        if(!$bid&&$this->request->controller()!='index'&&$this->request->action()!='login'){
            $this->redirect(url('index/login'));
        }
        define(BID,$bid);
//        $config = cmf_get_option('admin_set');
//        if ($config['web_cache'] == 0) {
//            exit('站点已关闭');
//        }
    }

    //分页 yyh
    public function array_page($data, $request)
    {
        $page = intval($request['page']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = intval($request['row']) ?: config('paginate.list_rows');//每页数量
        $dataTo = array_chunk($data, $row);
        if ($dataTo) {
            $showdata = $dataTo[$page - 1];
        } else {
            $showdata = null;
        }

        $p = Bootstrap::make($showdata, $row, $page, count($data), false, [
                'var_page' => 'page',
                'path' => url($this->request->action()),
                'query' => [],
                'fragment' => '',
        ]);
        if (!empty($_GET)) {
            $p->appends($_GET);
        } else {
            $p->appends(request()->param());
        }
        // 获取分页显示
        $page = $p->render();
        $this->assign("page", $page);
        $this->assign("data_lists", $p);
    }

    //二位数组排序 yyh
    public function array_order($data = [], $sortkey = '', $sort_order = 1)
    {
        if (empty($sortkey)) {
            return $data;
        } else {
            if ($sort_order == 1) {
                return my_sort($data, '', '');//1为不排序
            }
            $sort_order = $sort_order == 2 ? SORT_ASC : SORT_DESC;
            return my_sort($data, $sortkey, (int)$sort_order);
        }

    }
}