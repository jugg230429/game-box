<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\oa\validate;

use think\Validate;

class StudioValidate extends Validate
{

    protected $rule = [
            'studio_name' => 'require',
            'appid' => 'require|length:32|unique:Studio',
            'domain' => 'require',
            'api_key' => 'require|length:64',
    ];
    protected $message = [
            'studio_name' => [
                    'require' => '请输入工作室名称',
            ],
            'appid' => [
                    'require' => '请输入APPID',
                    'length' => '请输入32位字符串',
                    'unique' => 'APPID已存在',
            ],
            'domain' => [
                    'require' => '请输入域名',
            ],
            'api_key' => [
                    'require' => '请输入APIKEY',
                    'length' => '请输入64位字符串',
            ],
    ];
    protected $scene = [
            'add' => ['studio_name', 'appid', 'domain', 'api_key'],
            'edit' => ['studio_name', 'domain', 'api_key'],
    ];

}

