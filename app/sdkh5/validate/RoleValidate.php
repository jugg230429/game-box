<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */
namespace app\sdkh5\validate;

use think\Validate;

class RoleValidate extends Validate
{
    protected $rule = [
        'user_id'  =>  'require',
        'game_appid'  =>  'require',
    ];

    protected $message  =   [
        'user_id' => '用户错误',
        'game_appid' => '游戏错误',
    ];

    protected $scene = [
        'create'  =>  ['user_id,game_appid'],
    ];
}