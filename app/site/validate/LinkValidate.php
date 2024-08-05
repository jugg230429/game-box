<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\site\validate;

use think\Validate;

class LinkValidate extends Validate
{

    protected $rule = [
        'title' => 'require',
        'link_url' => 'require',
    ];
    protected $message = [
        'title.require' => '标题不能为空',
        'link_url.require' => '友链地址不能为空',
    ];

}

