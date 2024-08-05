<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */
namespace app\sdkh5\validate;

use think\Validate;

class GameValidate extends Validate
{
    protected $rule = [
        'game_id'  =>  'require|integer',
    ];

    protected $message  =   [
        'game_id' => '游戏错误',
    ];

    protected $scene = [
        'login'  =>  ['game_id'],
    ];
}