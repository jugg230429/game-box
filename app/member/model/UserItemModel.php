<?php

namespace app\member\model;

use think\Db;
use think\Model;

class UserItemModel extends Model
{

    protected $table = 'tab_user_item';

    protected $autoWriteTimestamp = true;
}
