<?php

namespace app\api\validate\migrate;

use think\Validate;

class PromoteValidate extends Validate
{


    protected $rule = [
            'id' => 'require',
            'account' => 'require',
            'password' => 'require',
    ];
    protected $message = [
            'id.require' => '推广员id不能为空',
            'account.require' => '推广员账号不能为空',
            'password.require' => '推广员密码不能为空',
    ];


}