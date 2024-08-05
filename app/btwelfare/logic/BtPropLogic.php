<?php

namespace app\btwelfare\logic;

use app\btwelfare\model\BtPropModel;
use app\btwelfare\validate\BtPropValidate;

class BtPropLogic extends BaseLogic
{


    public function __construct()
    {
        parent ::__construct();
    }


    /**
     * @游戏道具后台列表
     *
     * @author: zsl
     * @since: 2021/1/13 16:10\
     */
    public function adminLists($param)
    {

        $mBtProp = new BtPropModel();
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $where = [];
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        if (!empty($param['prop_name'])) {
            $where['prop_name'] = $param['prop_name'];
        }
        if ($param['status'] === '1' || $param['status'] === '0') {
            $where['status'] = $param['status'];
        }
        $lists = $mBtProp -> where($where) -> order('create_time desc,id desc') -> paginate($row, false, ['query' => $param]);
        return $lists;
    }


    /**
     * @新增道具
     *
     * @author: zsl
     * @since: 2021/1/13 16:25
     */
    public function add($param)
    {

        //验证请求参数
        $lBtProp = new BtPropValidate();
        if (!$lBtProp -> scene('add') -> check($param)) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = $lBtProp -> getError();
            return $this -> result;
        }
        //写入数据
        $mBtProp = new BtPropModel();
        //新增游戏福利设置
        $result = $mBtProp -> add($param);
        if (false === $result) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '添加失败';
            return $this -> result;
        }
        $this -> result['msg'] = '添加成功';
        return $this -> result;
    }


    public function edit($param)
    {

        //验证请求参数
        $lBtProp = new BtPropValidate();
        if (!$lBtProp -> scene('edit') -> check($param)) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = $lBtProp -> getError();
            return $this -> result;
        }
        //写入数据
        $mBtProp = new BtPropModel();
        //新增游戏福利设置
        $result = $mBtProp -> edit($param);
        if (false === $result) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '保存失败';
            return $this -> result;
        }
        $this -> result['msg'] = '保存成功';
        return $this -> result;
    }


    public function changeStatus($param)
    {
        if ($param['status'] != '0' && $param['status'] != '1') {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '修改失败';
        }
        $mBtProp = new BtPropModel();
        $result = $mBtProp -> where(['id' => $param['id']]) -> setField('status', $param['status']);
        if (false === $result) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '修改失败';
        }
        $this -> result['msg'] = '修改成功';
        return $this -> result;
    }


    public function del($id)
    {
        $mBtProp = new BtPropModel();
        //删除福利配置
        $result = $mBtProp -> where(['id' => $id]) -> delete();
        if (false === $result) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = "发生错误,删除失败";
            return $this -> result;
        }
        $this -> result['msg'] = '删除成功';
        return $this -> result;
    }


    /**
     * @获取道具列表
     *
     * @author: zsl
     * @since: 2021/1/13 17:54
     */
    public function getPropLists($param)
    {
        $mBtProp = new BtPropModel();
        $where = [];
        $where['status'] = 1;
        $where['game_id'] = $param['game_id'];
        $lists = $mBtProp -> where($where) -> order('create_time desc,id desc') -> select();
        if ($lists -> isEmpty()) {
            $this -> result['code'] = 0;
        }
        $this -> result['data'] = $lists;
        return $this -> result;
    }


}