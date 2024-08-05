<?php

namespace app\btwelfare\validate;

use think\Validate;

class BtPropValidate extends Validate
{
    protected $rule = [
            'game_id' => 'require|integer|gt:0',
            'prop_name' => 'require',
            'prop_tag' => 'require',
            'number' => 'require|integer|gt:0',
    ];
    protected $message = [
            'game_id' => [
                    'require' => '请选择游戏',
                    'integer' => '请选择游戏',
                    'gt' => '请选择游戏',
            ],
            'prop_name' => [
                    'require' => '请填写道具名称',
            ],
            'prop_tag' => [
                    'require' => '请填写道具标识',
            ],
            'number' => [
                    'require' => '请输入道具数量',
                    'integer' => '请输入道具数量',
                    'gt' => '请输入道具数量',
            ],
    ];


    protected $scene = [
            'add' => ['game_id', 'prop_name', 'prop_tag', 'number'],
            'edit' => ['game_id', 'prop_name', 'prop_tag', 'number'],
    ];


}