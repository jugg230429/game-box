<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */
namespace app\issueyy\validate;

use think\Validate;

class PayValidate extends Validate
{
    protected $rule = [
        'game_appid'  =>  'require',
        'channel_code' => 'require',
        'game_id' => 'require',
    ];

    protected $message  =   [
        'game_appid' => '游戏错误',
        'channel_code' => '平台标识不能为空',
        'game_id' => '游戏标识不能为空',
    ];

    protected $scene = [
        'init'  =>  ['game_appid'],
        'callback'  =>  ['channel_code','game_id'],
    ];
}