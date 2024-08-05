<?php

namespace app\promote\validate;

use think\Validate;

class PromoteAppValidate extends Validate
{

    protected $rule = [
            'app_new_name' => 'require',
            'app_new_icon' => 'require|isPng',
            'start_img1' => 'isPng',
            'start_img2' => 'isPng',
    ];
    protected $message = [
            'app_new_name' => [
                    'require' => '请输入APP名称'
            ],
            'app_new_icon' => [
                    'require' => '请上传APP图标',
                    'isPng' => '请上传png格式的APP图标',
            ],
            'start_img1' => [
                    'isPng' => '请上传png格式的启动图(上)',
            ],
            'start_img2' => [
                    'isPng' => '请上传png格式的启动图(下)',
            ],
    ];

    protected $scene = [
            'user_define' => ['app_new_name', 'app_new_icon', 'start_img1', 'start_img2'],
    ];


    // 自定义验证规则
    protected function isPng($value, $rule, $data)
    {
        $ext = substr(strrchr($value, "."), 1);
        if ('png' != $ext) {
            return false;
        }
        return true;
    }

}