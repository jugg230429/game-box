<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\validate;

use think\Validate;

class RouteValidate extends Validate
{
    protected $rule = [
        'url' => 'require',
        'full_url' => 'require',
    ];

    protected $message = [
        'url.require' => '显示网址不能为空',
        'full_url.require' => '原始网址不能为空',
    ];

}