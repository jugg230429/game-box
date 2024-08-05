<?php

namespace app\common\task;

use app\common\model\TaskTriggerModel;
use app\common\validate\TaskValidate;

class Task
{

    private $m;
    private $v;
    private $errorMsg;

    public function __construct()
    {
        $this -> m = new TaskTriggerModel();
        $this -> v = new TaskValidate();
        $this -> errorMsg = '';

    }


    /**
     * @创建任务
     *
     * @param $title : 任务标题
     * @param $class_name : 任务类名
     * @param $function_name : 任务执行方法
     * @param $param : 执行所需参数
     * @param string $remark : 任务备注
     *
     * @author: zsl
     * @since: 2021/8/2 21:30
     */
    public function create($param)
    {
        //验证参数
        if (!$this -> v -> check($param)) {
            $this -> errorMsg = $this -> v -> getError();
            return false;
        }
        if(!$this->v->checkOther($param)){
            $this -> errorMsg = $this -> v -> getError();
            return false;
        }
        //写入记录
        $result = $this -> m -> add($param);
        if (false === $result) {
            $this -> errorMsg = '写入记录失败';
            return false;
        }
        return true;
    }


    /**
     * @执行任务
     *
     * @author: zsl
     * @since: 2021/8/2 21:28
     */
    public function run()
    {
        //获取一条待执行任务
        $info = $this -> m -> getOneData();
        if (empty($info)) {
//            $this -> errorMsg = '暂无待执行任务';
            return false;
        }
        try {
            //执行任务
            $class = new $info['class_name']();
            $param = json_decode($info['param'], true);
            $func_name = $info['function_name'];
            $res = $class -> $func_name($param);
            if (false === $res) {
                $this -> errorMsg = '任务' . $info -> id . '执行失败';
                $info -> error_num += 1;
                $info -> isUpdate(true) -> save();
                return false;
            }
            //修改状态
            $info -> status = 1;
            $info -> isUpdate(true) -> save();
            return true;
        } catch (\Exception $e) {
            if (false === $res) {
                $info -> error_num += 1;
                $info -> isUpdate(true) -> save();
                return false;
            }
            $this -> errorMsg = '任务' . $info -> id . '执行失败';
            return false;
        }
    }


    /**
     * @获取错误信息
     *
     * @return string
     *
     * @author: zsl
     * @since: 2021/8/2 21:41
     */
    public function getErrorMsg()
    {
        return $this -> errorMsg;
    }


}
