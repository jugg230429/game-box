<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\user\validate;

use think\Validate;

class UserArticlesValidate extends Validate
{
    protected $rule = [
        'post_title' => 'require',
    ];
    protected $message = [
        'post_title.require' => '文章标题不能为空',
    ];

    protected $scene = [
        'add' => ['post_title'],
        'edit' => ['post_title'],
    ];
}