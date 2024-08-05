<?php
/**
 * Created by www.dadmin.cn
 * User: imdong
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\extend\validate;

use think\Validate;

class MsgValidate extends Validate
{

    protected $rule = [
        'type' => 'require',
        'title' => 'require',
        'content' => 'require',
    ];
    protected $message = [
        'type.require' => '数据异常，请刷新后重试。',
        'title.require' => '文档标题不能为空',
        'content.require' => '文档内容不能为空！',
    ];

}

