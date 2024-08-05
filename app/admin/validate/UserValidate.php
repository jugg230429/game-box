<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\validate;

use think\Validate;

class UserValidate extends Validate
{
    protected $rule = [
        'user_login' => 'require|min:6|max:30|unique:user',
        'user_pass' => 'require|min:6|max:30|confirm',
        'second_pass' => 'require|min:6|max:30',
        'user_email' => 'email|unique:user,user_email',
        'mobile' => 'unique:user,mobile',
    ];
    protected $message = [
            'user_login.require' => '账号为6-30位字母或数字组合',
            'user_login.unique' => '用户名已存在',
            'user_login.min' => '账号为6-30位字母或数字组合',
            'user_login.max' => '账号为6-30位字母或数字组合',
            'user_pass.require' => '密码为6-30位字母或数字组合',
            'user_pass.min' => '密码为6-30位字母或数字组合',
            'user_pass.max' => '密码为6-30位字母或数字组合',
            'user_pass.confirm' => '两次密码输入不一致',
            'second_pass.require' => '密码为6-30位字母或数字组合',
            'second_pass.min' => '密码为6-30位字母或数字组合',
            'second_pass.max' => '密码为6-30位字母或数字组合',
            'user_email.require' => '邮箱不能为空',
            'user_email.email' => '邮箱不正确',
            'user_email.unique' => '邮箱已经存在',
            'mobile.unique' => '手机号已经存在',
    ];

    protected $scene = [
        'add' => ['user_login', 'user_pass'],
        'edit' => [
            'user_pass' => 'min:6|max:30',
            'second_pass' => 'min:6|max:30',
            'mobile' => 'unique:user,mobile',
            'user_email' => 'unique:user,user_email'
        ],
        'logined_edit' => [
            'user_pass' => 'min:6|max:30',
            'second_pass' => 'min:6|max:30'
        ],
    ];
}
