<?php
/**
 * Created by www.dadmin.cn
 * User: imdong
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\member\validate;

use think\Validate;

class UserTplayValidate extends Validate
{

    protected $rule = [
        'game_id' => 'require|number',
        'game_name' => 'require',
        'server_id' => 'require|number',
        'server_name' => 'require',
        'time_out' => 'require|number',
    ];
    protected $message = [
        'game_id' => '游戏不能为空',
        'game_name' => '游戏不能为空',
        'server_id' => '区服名称不能为空',
        'server_name' => '区服名称不能为空',
        'time_out' => '任务时限不能为空',
    ];
}

