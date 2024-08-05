<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\site\validate;

use think\Validate;

class KefuValidate extends Validate
{

    protected $rule = [
        'zititle' => 'require',
        'type' => 'require',
        'content' => 'require',
    ];
    protected $message = [
        'zititle.require' => '标题不能为空',
        'type.require' => '分类不能为空',
        'content.require' => '内容不能为空',
    ];

}

