<?php

namespace app\common\validate;

use think\Validate;

class UserFeedbackValidate extends Validate
{

    protected $rule = [
            'game_id' => 'require|gt:0',
            'qq' => 'require',
            'tel' => 'checkMobile',
            'report_type' => 'require',
            'remark' => 'require|length:6,200',
    ];
    protected $message = [
            'game_id' => [
                    'require' => '请选择要投诉的游戏',
                    'gt' => '请选择要投诉的游戏',
            ],
            'qq' => [
                    'require' => '请输入联系QQ',
            ],
            'tel' => [
                    'checkMobile' => '请输入正确的手机号',
            ],
            'report_type' => [
                    'require' => '请选择举报类型',
            ],
            'remark' => [
                    'require' => '请输入举报详细描述',
                    'length' => '描述长度为6-200个字符',
            ],
    ];

    public function sceneAdd()
    {
        return $this -> only(['game_id', 'qq', 'tel', 'report_type', 'remark']);
    }


    protected function checkMobile($value, $rule, $data)
    {
        return cmf_check_mobile($value) ? true : false;
    }
}
