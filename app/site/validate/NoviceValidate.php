<?php
/**
 * Created by www.dadmin.cn
 * User: yyh
 * Date: 2018/8/15
 * Time: 9:32
 */

namespace app\site\validate;

use think\Validate;

class NoviceValidate extends Validate
{

    protected $rule = [
        'game_id' => 'require',
        'title' => 'require',
        'content' => 'require',
    ];
    protected $message = [
        'game_id.require' => '游戏名称不能为空',
        'title.require' => '标题不能为空',
        'content.require' => '内容不能为空',
    ];
    protected $scene = [
        'edit' => ['title', 'content'],
    ];

}

