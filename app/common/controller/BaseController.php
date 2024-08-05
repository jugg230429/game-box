<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\common\controller;

use app\member\model\UserConfigModel;
use cmf\controller\AdminBaseController;
use think\Request;
use cmf\paginator\Bootstrap;


class BaseController extends AdminBaseController
{

    //单表pagin查询
    public function data_list($model = null, $where = [], $extend = [],$where_str='')
    {
        $model || $this->error('模型名标识必须！');
        $page = intval($extend['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = (int)$extend['row'] ?: ($this->request->param('row') ?: config('paginate.list_rows'));//每页数量
        $field = $extend['field'] ?: true;
        $order = $extend['order'] ?: 'id desc';
        $group = $extend['group'] ?: null;
        $whereOr = $extend['whereor'];
        $data = $model
            ->field($field)
            ->whereOr($whereOr)
            ->where($where)
            ->where($where_str)
            ->order($order)
            ->group($group)
            ->paginate($row, false, ['query' => $this->request->param()]);//https://www.kancloud.cn/manual/thinkphp5/154294
        return $data;
    }

    //多表pagin查询
    public function data_list_join($model = null, $where = [], $extend = [],$where_str='')
    {
//        dump($where_str);die;
        $model || $this->error('模型名标识必须！');
        $page = intval($extend['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = (int)$extend['row'] ?: ($this->request->param('row') ?: config('paginate.list_rows'));//每页数量
        $field = $extend['field'] ?: true;
        $order = $extend['order'];
        $group = $extend['group'] ?: null;
        $join1 = $extend['join1'];
        $join2 = $extend['join2'];
        $join3 = $extend['join3'];
        $data = $model
            ->field($field)
            ->join($join1[0], $join1[1], $join1[2])
            ->join($join2[0], $join2[1], $join2[2])
            ->join($join3[0], $join3[1], $join3[2])
            ->where($where)
            ->where($where_str)
            ->order($order)
            ->group($group)
            ->paginate($row, false, ['query' => $this->request->param()]);
        return $data;
    }

    //单表select查询
    public function data_list_select($model = null, $where = [], $extend = [])
    {
        $model || $this->error('模型名标识必须！');
        $page = intval($extend['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = (int)$extend['row'] ?: config('paginate.list_rows');//每页数量
        $field = $extend['field'] ?: true;
        $order = $extend['order'] ?: 'id desc';
        $group = $extend['group'] ?: null;
        $data = $model
            ->field($field)
            ->where($where)
            ->order($order)
            ->group($group)
            ->select()->toarray();
        return $data;
    }

    //多表select查询
    public function data_list_join_select($model = null, $where = [], $extend = [])
    {
        $model || $this->error('模型名标识必须！');
        $page = intval($extend['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = (int)$extend['row'] ?: config('paginate.list_rows');//每页数量
        $field = $extend['field'] ?: true;
        $order = $extend['order'];
        $group = $extend['group'] ?: null;
        $join1 = $extend['join1'];
        $join2 = $extend['join2'];
        $join3 = $extend['join3'];
        $data = $model
            ->field($field)
            ->join($join1[0], $join1[1], $join1[2])
            ->join($join2[0], $join2[1], $join2[2])
            ->join($join3[0], $join3[1], $join3[2])
            ->where($where)
            ->order($order)
            ->group($group)
            ->select()->toarray();
        return $data;
    }

    /**
     *获取用户设置信息
     */
    public function BaseConfig($name = '')
    {
        $model = new UserConfigModel();
        $map['name'] = array('in', $name);
        $tool = $model->where($map)->select();
        if (!$tool) {
            $this->error('没有此设置');
        }
        foreach ($tool as $key => $v) {
            $this->assign($tool[$key]['name'], $v);
            $this->assign($tool[$key]['name'] . "_data", json_decode($v['config'], true));
        }
    }

    public function saveConfig($name, $data)
    {
        $save['status'] = $data['status'];
        $save['config'] = json_encode($data['config'], true);
        $model = new UserConfigModel();
        $flag = $model->where('name', $name)->update($save);
        return $flag;
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

    /**
     * 判断防沉迷逻辑-如开启官方防沉迷,则实名认证必须开启
     * by:byh 2021-9-6 14:38:31
     * @param $data
     */
    public function judgeOfficialAntiAddiction($data)
    {
        if(!empty($data) && $data['name'] == 'age_prevent' && $data['status'] == 1){
            //判断是否开启国家官方防沉迷
            $config = $data['config'];
            if(!empty($config) && $config['way'] == 1){
                //查询实名认证开关
                $model = new UserConfigModel();
                $map['name'] = 'age';
                $age_arr = $model->field('status')->where($map)->find();
                if(!empty($age_arr) && $age_arr['status'] != 1){
                    return false;
                }
            }
        }
        return true;
    }

}