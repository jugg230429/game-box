<?php

namespace app\api\validate\migrate;

use think\Validate;

class GameValidate extends Validate
{


    protected $rule = [
            'id' => 'require',
            'game_name' => 'require',
    ];
    protected $message = [
            'id.require' => '游戏id不能为空',
            'game_name.require' => '游戏名称不能为空',
    ];


}