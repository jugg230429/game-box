<?php

namespace app\thirdgame\model;

use think\Db;
use think\Model;

class GameThridModel extends Model
{
    protected $table = 'tab_game_third';

    protected $autoWriteTimestamp = true;

    public function get_game_lists($map=[],$field="id")
    {
          $data = $this->field($field)->where($map)->select()->toArray();
          return $data?:[];
    }
}