<?php

namespace app\media\controller;

use app\site\model\KefuModel;
use app\site\model\KefutypeModel;

class ServiceController extends BaseController
{


    public function index()
    {
        //获取分类
        $mKefutype = new KefutypeModel();
        $typeLists = $mKefutype -> getPcTypeLists();
        $this -> assign('typeLists', $typeLists);
        return $this -> fetch();
    }

    /**
     * @获取分类下文章详情
     *
     * @author: zsl
     * @since: 2021/4/20 20:22
     */
    public function article()
    {
        $result = ['code' => 200, 'msg' => '请求成功', 'data' => []];
        $type = $this -> request -> param('type');
        //获取分类详情
        $mKefutype = new KefutypeModel();
        $type_name = $mKefutype -> where(['id' => $type]) -> value('name');
        $result['type_name'] = $type_name;
        //获取分类下文章
        $mKefu = new KefuModel();
        $field = "id,zititle,content";
        $where = [];
        $where['type'] = $type;
        $where['status'] = 1;
        $lists = $mKefu -> field($field) -> where($where) -> order('sort desc') -> select();
        $result['data'] = $lists ? $lists : [];
        return json($result);
    }

}
