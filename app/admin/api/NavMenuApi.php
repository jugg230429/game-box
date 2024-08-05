<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\api;

use app\admin\model\NavMenuModel;

class NavMenuApi
{
    /**
     * 导航菜单模板数据源 用于模板设计
     * @param array $param
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($param = [])
    {
        $navMenuModel = new NavMenuModel();

        $result = $navMenuModel
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $query->where('name', 'like', "%{$param['keyword']}%");
                }

                if (!empty($param['id'])) {
                    $query->where('nav_id', intval($param['id']));
                }
            })->select();

        return $result;
    }

}