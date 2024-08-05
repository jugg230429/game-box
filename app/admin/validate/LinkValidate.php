<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\validate;

use think\Validate;

class LinkValidate extends Validate
{
    protected $rule = [
        'name' => 'require',
        'url' => 'require',
    ];

    protected $message = [
        'name.require' => '名称不能为空',
        'url.require' => '链接地址不能为空',
    ];

}