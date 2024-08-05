<?php

namespace app\oa\logic;

use app\oa\model\StudioModel;
use app\oa\validate\StudioValidate;

class  StudioLogic
{

    /**
     * @游戏道具后台列表
     *
     * @author: zsl
     * @since: 2021/1/13 16:10\
     */
    public function adminLists($param)
    {

        $mStudio = new StudioModel();
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $where = [];
        if (!empty($param['studio_name'])) {
            $where['studio_name'] = ['like', '%' . $param['studio_name'] . '%'];
        }
        if ($param['status'] === '1' || $param['status'] === '0') {
            $where['status'] = $param['status'];
        }
        $lists = $mStudio -> where($where) -> order('create_time desc,id desc') -> paginate($row, false, ['query' => $param]);
        return $lists;
    }


    /**
     * @添加公会
     *
     * @author: zsl
     * @since: 2021/3/1 14:49
     */
    public function add($param)
    {
        $result = ['code' => 1, 'msg' => '添加成功', 'data' => []];
        $vStudio = new StudioValidate();
        if (!$vStudio -> scene('add') -> check($param)) {
            $result['code'] = 0;
            $result['msg'] = $vStudio -> getError();
            return $result;
        }
        $mStudio = new StudioModel();
        $param['create_type'] = 1;
        $res = $mStudio -> add($param);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '添加失败';
            return $result;
        }
        return $result;
    }


    /**
     * @编辑公会
     *
     * @author: zsl
     * @since: 2021/3/1 16:03
     */
    public function edit($param)
    {
        $result = ['code' => 1, 'msg' => '请求成功', 'data' => []];
        //验证请求参数
        $vStudio = new StudioValidate();
        if (!$vStudio -> scene('edit') -> check($param)) {
            $result['code'] = 0;
            $result['msg'] = $vStudio -> getError();
            return $result;
        }
        //写入数据
        $mStudio = new StudioModel();
        $res = $mStudio -> edit($param);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '保存失败';
            return $result;
        }
        $result['msg'] = '保存成功';
        return $result;
    }


    /**
     * @删除工作室
     *
     * @author: zsl
     * @since: 2021/3/1 17:29
     */
    public function del($id)
    {
        $result = ['code' => 1, 'msg' => '删除成功', 'data' => []];
        $mStudio = new StudioModel();
        $res = $mStudio -> where(['id' => $id]) -> delete();
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '删除失败';
            return $result;
        }
        return $result;
    }

}