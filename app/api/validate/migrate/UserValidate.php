<?php

namespace app\api\validate\migrate;

use think\Validate;

class UserValidate extends Validate
{



    protected $rule = [
            'id' => 'require',
            'account' => 'require',
            'password' => 'require',
    ];
    protected $message = [
            'id.require' => '用户id不能为空',
            'account.require' => '用户账号不能为空',
            'password.require' => '用户密码不能为空',
    ];



}