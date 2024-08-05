<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\game\validate;

use app\game\model\GameInterfaceModel;
use think\Validate;

class GameInterfaceValidate extends Validate
{

    protected $rule = [
        'name' => 'require|checkTypeName',
        'tag' =>'require',
        'unid' =>'require',
        'login_url' =>'require',
        'pay_url' =>'require',
        'login_key' =>'require',
        'pay_key' =>'require',
        'role_url' =>'require',
    ];
    protected $message = [
        'name.require' => '接口名称不能为空',
        'name.checkTypeName' => '接口名称已存在',
        'tag.require' => '标签不能为空',
        'unid.require' => '标识不能为空',
        'login_url.require' => '登录地址不能为空',
        'pay_url.require' => '充值地址不能为空',
        'login_key.require' => '登录Key不能为空',
        'pay_key.require' => '充值Key不能为空',
        'role_url.require' => '角色地址不能为空',
    ];

    // 自定义验证规则
    protected function checkTypeName($value, $rule, $data = [])
    {
        $model = new GameInterfaceModel();
        $map['name'] = $value;
        if($data['id']){
            $map['id'] = ['neq',$data['id']];
        }
        $result = $model->field('id')->where($map)->find();
        if ($result) {
            return false;
        } else {
            return true;
        }
    }
}

