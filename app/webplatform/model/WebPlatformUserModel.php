<?php

namespace app\webplatform\model;

use think\Db;
use think\Model;

class WebPlatformUserModel extends Model
{
    protected $table = 'tab_web_platform_user';

    protected $autoWriteTimestamp = true;
}