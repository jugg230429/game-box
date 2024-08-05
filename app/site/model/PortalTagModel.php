<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\site\model;

use think\Model;

class PortalTagModel extends Model
{
    public static $STATUS = array(
        0 => "未启用",
        1 => "已启用",
    );
}