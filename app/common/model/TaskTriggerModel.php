<?php

namespace app\common\model;

use think\Model;

class TaskTriggerModel extends Model
{
    protected $table = 'tab_task_trigger';

    protected $autoWriteTimestamp = true;


    /**
     * @新增待执行任务
     *
     * @author: zsl
     * @since: 2021/8/2 21:45
     */
    public function add($param)
    {
        $this -> title = $param['title'];
        $this -> class_name = $param['class_name'];
        $this -> function_name = $param['function_name'];
        $this -> param = json_encode($param['param']);
        $this -> remark = $param['remark'];
        $this -> status = 0;
        $this -> error_num = 0;
        $result = $this -> isUpdate(false) -> save();
        return $result;
    }


    /**
     * @获取一条数据
     *
     * @author: zsl
     * @since: 2021/8/3 18:00
     */
    public function getOneData()
    {
        $field = "id,class_name,function_name,param,create_time,status,error_num";
        $where = [];
        $where['status'] = 0;
        $where['error_num'] = ['lt', 4];
        $info = $this -> field($field) -> where($where) -> order('create_time asc') -> find();
        return $info;
    }


}
