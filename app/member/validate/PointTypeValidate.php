<?php
/**
 * Created by www.dadmin.cn
 * User: imdong
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\member\validate;

use think\Validate;

class PointTypeValidate extends Validate
{

    protected $rule = [
        'name' => 'require',
        'point' => 'require|number',
        'description' => 'require',
        'time_of_day' => 'number',
        'type' => 'require|in:1,2,3,4',
        'birthday_point' => 'number',
    ];
    protected $message = [
        'name.require' => '名称不能为空',
        'point.require' => '请输入奖励积分值',
        'point.number' => '奖励积分值格式错误',
        'description' => '任务说明不能为空',
        'time_of_day' => '累计积分格式错误',
        'type' => '任务类型错误',
        'birthday_point' => '生日积分格式错误',
    ];
}

