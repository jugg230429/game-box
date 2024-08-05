<?php

namespace app\member\model;

use think\Model;

class UserLoginRecordModel extends Model
{

    protected $table = 'tab_user_login_record';

    protected $autoWriteTimestamp = true;
}