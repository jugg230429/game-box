<?php
namespace app\common\model;

use think\Db;
use think\Model;

class UserAgeRecordModel extends Model{

    protected $table = 'tab_user_age_record';
    protected $autoWriteTimestamp = true;

}