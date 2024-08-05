<?php

namespace app\channelsite\model;

use think\Model;
use think\Pinyin;

class PromoteModel extends Model
{

    protected $table = 'tab_promote';

    protected $autoWriteTimestamp = true;

    //yyh
    public function lists($map = [], $field = '*')
    {
        $data = $this->field($field)->where($map)->select();
        return $data;
    }
}