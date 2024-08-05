<?php
/**
 * Created by www.dadmin.cn
 * User: imdong
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\member\validate;

use think\Validate;
use app\member\model\UserModel;

class UserValidate2 extends Validate
{

    protected $rule = [
        'account' => 'require|length:6,30|checkName:thinkphp',
        'register_type' => 'require|between:1,3',
        'password' => 'require|alphaNum|length:6,15',
        'repassword' => 'require|confirm:password',
        'code' => 'requireIf:register_type,3',
        'agreement' => 'require',
    ];
    protected $message = [
        'account.require' => '请输入邮箱',
        'account.length' => '请输入正确邮箱地址',
        'register_type.require' => '注册类型错误',
        'register_type.between' => '注册类型错误',
        'password.require' => '请输入密码',
        'password.alphaNum' => '密码为6-15位字母或数字组合',
        'password.length' => '密码为6-15位字母或数字组合',
        'repassword.require' => '确认密码不能为空！',
        'repassword.confirm' => '两次输入的密码不一致！',
        'code' => '请输入验证码',
        'agreement' => '请同意注册协议'
    ];
    protected $scene = [
            'account' => ['account'],
    ];
    // 验证用户名
    protected function checkName($value, $rule, $data)
    {
        $model = new UserModel();
        if ($data['type'] == 1) {
            $user = $model->where('account|email', $value)->field('id')->find();
        } else {
            $user = $model->where('account', $value)->field('id')->find();
        }
        return empty($user) ? true : '邮箱已被注册';
    }

}

