<?php

namespace app\common\validate;

use think\Validate;

class TaskValidate extends Validate
{


    protected $rule = [
            'title' => 'require',
            'class_name' => 'require',
            'function_name' => 'require',
    ];
    protected $message = [
            'title' => [
                    'require' => '请输入任务标题',
            ],
            'class_name' => [
                    'require' => '请输入类名称',
            ],
            'function_name' => [
                    'require' => '请输入执行方法',
            ],
    ];


    public function checkOther($param)
    {
        //验证类是否存在
        if (!class_exists($param['class_name'])) {
            $this -> error = '指定类不存在';
            return false;
        }
        //验证方法是否存在
        $class = new $param['class_name']();
        if (!method_exists($class, $param['function_name'])) {
            $this -> error = '未定义' . $param['function_name'] . '方法';
            return false;
        }
        return true;
    }

}
