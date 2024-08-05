<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\webplatform\validate;

use app\webplatform\model\WebPlatformModel;
use app\promote\model\PromoteModel;
use think\Validate;

class WebPlatValidate extends Validate
{

    protected $rule = [
        'platform_name' => 'require|checkName|length:1,30',
        'platform_url'=>'require|url',
        'api_key'=>'require|alphaNum|length:12,32',
        'promote_account' => 'require|min:6|max:30|checkPromote|regex:^[A-Za-z0-9]+$',
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
        'promote_account.require' => '绑定渠道不能为空',
        'promote_account.checkPromote' => '渠道账号已存在',
        'promote_account.max' => '渠道账号6-30位数字或字母',
        'promote_account.min' => '渠道账号6-30位数字或字母',
        'promote_account.regex' => '渠道账号6-30位数字或字母',

    ];
    protected $scene = [
        'edit'=>['platform_name','platform_url','api_key'],
    ];

    // 自定义验证规则
    protected function checkName($value, $rule, $data = [])
    {
        $model = new WebPlatformModel();
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

    // 自定义验证规则
    protected function checkPromote($value, $rule, $data = [])
    {
        $model = new PromoteModel();
        $map['account'] = $value;
        $result = $model->where($map)->find();
        $result=$result?$result->toArray():[];
        if ($result) {
            return false;
        } else {
            return true;
        }
    }
}

