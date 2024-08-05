<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */
namespace app\sdkh5\validate;

use think\Validate;

class PayValidate extends Validate
{
    protected $rule = [
        'game_appid'  =>  'require',
    ];

    protected $message  =   [
        'game_appid' => '游戏错误',
    ];

    protected $scene = [
        'init'  =>  ['game_appid'],
    ];
}