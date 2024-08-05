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


class UserValidate3 extends Validate
{

    protected $rule = [
        'account' => 'require|alphaNum|length:6,15|checkName:thinkphp',
        'password' => 'require|alphaNum|length:6,15',
    ];
    protected $message = [
        'account.require' => '请输入用户名',
        'account.alphaNum' => '账号为6-15位字母或数字组合',
        'account.length' => '账号为6-15位字母或数字组合',
        'password.require' => '请输入密码',
        'password.alphaNum' => '密码为6-15位字母或数字组合',
        'password.length' => '密码为6-15位字母或数字组合',
    ];
    protected $scene = [
        'password' =>['password'],
    ];

    // 验证用户名
    protected function checkName($value, $rule, $data)
    {
        $model = new UserModel();
        if ($data['type'] == 1) {
            $user = $model->where('account|phone', $value)->field('id')->find();
        } else {
            $user = $model->where('account', $value)->field('id')->find();
        }
        return empty($user) ? true : '账号已被注册';
    }


}

