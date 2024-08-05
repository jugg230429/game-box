<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\validate;

use think\Validate;

class StorageQiniuValidate extends Validate
{
    protected $rule = [
        'accessKey' => 'require',
        'secretKey' => 'require',
        'domain' => 'require',
    ];

    protected $message = [
        'accessKey.require' => 'AccessKey不能为空',
        'secretKey.require' => 'secretKey不能为空',
        'domain.require' => '空间域名不能为空',
    ];

}