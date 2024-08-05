<?php

namespace app\game\model;

use think\Model;

class GameAttrModel extends Model
{
    protected $table = 'tab_game_attr';


    public function game()
    {
        return $this->belongsTo('\app\game\model\GameModel','id');
    }



}
