<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\thirdgame\validate;

use app\thirdgame\model\PlatformModel;
use think\Validate;

class PlatValidate extends Validate
{

    protected $rule = [
        'platform_name' => 'require|checkName|length:1,30',
        'platform_url'=>'require|url',
        'api_key'=>'require|alphaNum|length:12,32',
        'marking'=>'require|length:1,50',
    ];
    protected $message = [
        'platform_name.require' => '平台名称不能为空',
        'platform_name.checkName' => '平台名称已存在',
        'platform_name.length' => '平台名称不超过30个字符',
        'platform_url.require' => '平台域名不能为空',
        'platform_url.url' => '请输入正确的平台域名',
        'api_key.require'=>'api密钥不能为空',
        'api_key.alphaNum'=>'api密钥必须是数字和字母',
        'api_key.length'=>'api密钥长长度为12-32位数字或字母',
        'marking.require'=>'平台标识不能为空',
        'marking.length'=>'平台标识长度为1-50位数字或字母',


    ];
    protected $scene = [
        'setStatus'=>[],
    ];

    // 自定义验证规则
    protected function checkName($value, $rule, $data = [])
    {
        $model = new PlatformModel();
        $map['platform_name'] = $value;
        if($data['id']){
            $map['id'] = ['neq',$data['id']];
        }
        $result = $model->where($map)->find();
        $result=$result?$result->toArray():[];
        if ($result) {
            return false;
        } else {
            return true;
        }
    }
}

