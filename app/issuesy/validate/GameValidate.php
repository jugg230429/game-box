<?php

namespace app\issuesy\validate;

use think\Validate;

class GameValidate extends Validate
{


    protected $rule = [
            'game_type_id' => 'gt:0',
            'ff_ratio' => 'egt:0|elt:100',
            'cp_ratio' => 'egt:0|elt:100',
    ];

    protected $message = [
            'game_type_id.gt' => '请选择游戏类型',
            'ff_ratio.egt' => '输入0~100之前的数字',
            'ff_ratio.elt' => '输入0~100之前的数字',
            'cp_ratio.egt' => '输入0~100之前的数字',
            'cp_ratio.elt' => '输入0~100之前的数字',
    ];

    protected $scene = [
            'edit' => ['game_type_id', 'ff_ratio', 'cp_ratio'],
    ];


}