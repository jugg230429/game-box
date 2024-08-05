<?php

namespace app\member\validate;

use think\Validate;

class UserBatchCreateValidate extends Validate
{

    protected $rule = [
            'create_number' => 'require|integer|gt:0|elt:10000',
            'password' => 'require|alphaNum|length:6,15',
            'promote_id' => 'require',
    ];

    protected $message = [
            'create_number' => [
                    'require' => '账号数量不能为空',
                    'integer' => '账号数量只可输入整数',
                    'gt' => '账号数量不正确',
                    'elt' => '单次最大生成10000个账号',
            ],
            'password' => [
                    'require' => '账号密码不能为空',
                    'alphaNum' => '密码为6~15位字母或数字组合',
                    'length' => '密码为6~15位字母或数字组合',
            ],
            'promote_id' => '请选择所属渠道',
    ];


    protected $scene = [
            'create' => ['create_number', 'password', 'promote_id'],
    ];


}
