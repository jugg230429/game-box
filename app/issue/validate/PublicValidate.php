<?php

namespace app\issue\validate;

use app\issue\model\OpenUserModel;
use think\Validate;

class PublicValidate extends Validate
{

    protected $rule = [
            'account' => 'require|alphaNum|length:6,30',
            'password' => 'require|alphaNum|length:6,30',
    ];
    protected $message = [
            'account.require' => '请输入账号',
            'account.alphaNum' => '账号为6-30位字母和数字',
            'account.length' => '账号为6-30位字母和数字',
            'password.require' => '请输入密码',
            'password.alphaNum' => '密码为6-30位字母和数字',
            'password.length' => '密码为6-30位字母和数字',
    ];
    protected $scene = [
            'login' => ['account', 'password'],
    ];


    /**
     * @验证用户信息
     *
     * @author: zsl
     * @since: 2020/7/11 12:05
     */
    public function checkUserInfo($param)
    {
        $result = ['code' => 1, 'msg' => '验证通过', 'data' => []];
        //获取用户信息
        $mOpenUser = new OpenUserModel();
        $field = 'id,account,password,status';
        $where = [];
        $where['account'] = $param['account'];
        $userInfo = $mOpenUser -> field($field) -> where($where) -> find();
        //验证是否存在
        if (empty($userInfo)) {
            $result['code'] = 0;
            $result['msg'] = '用户不存在';
            return $result;
        }
        //验证密码
        if (!xigu_compare_password($param['password'], $userInfo['password'])) {
            $result['code'] = 0;
            $result['msg'] = '用户名或密码输入错误,请重试';
            return $result;
        }
        //验证状态
        if ($userInfo['status'] != '1') {
            $result['code'] = 0;
            $result['msg'] = '用户被禁用,登录失败';
            return $result;
        }
        $result['data'] = $userInfo;
        return $result;
    }

}