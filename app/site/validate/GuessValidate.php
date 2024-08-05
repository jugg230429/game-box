<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\site\validate;

use think\Validate;

class GuessValidate extends Validate
{

    protected $rule = [
        'title' => 'require',
        'url' => 'require',
        'icon' => 'require',
    ];
    protected $message = [
        'title.require' => '链接名称不能为空',
        'url.require' => '链接地址不能为空',
        'icon.require' => '图标不能为空',
    ];
}

