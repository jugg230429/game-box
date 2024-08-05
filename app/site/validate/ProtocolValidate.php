<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\site\validate;

use think\Validate;

class ProtocolValidate extends Validate
{

    protected $rule = [
        'title' => 'require',
        'content' => 'require',
    ];
    protected $message = [
        'title.require' => '标题不能为空',
        'content.require' => '内容不能为空',
    ];
    protected $scene = [
        'edit' => ['title', 'content'],
    ];

}

