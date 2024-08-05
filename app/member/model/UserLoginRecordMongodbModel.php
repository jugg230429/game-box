<?php

namespace app\member\model;

use think\Model;

class UserLoginRecordMongodbModel extends Model
{
    protected $table = 'tab_user_login_record';

    protected $autoWriteTimestamp = true;

    protected $connection = [
            'type' => '\think\mongo\Connection',
            'hostname' => '127.0.0.1',
            'database' => 'qj_mongo_db',
            'username' => '',
            'password' => '',
            'hostport' => 27017,
    ];

}
