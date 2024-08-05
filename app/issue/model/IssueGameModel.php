<?php

namespace app\issue\model;

use think\Model;

class IssueGameModel extends Model
{
    protected $table = 'tab_issue_game';

    protected $autoWriteTimestamp = true;


    public function getAllGameLists($map = [])
    {
        $map['status'] = 1;
        $data = $this -> field('id,game_name,sdk_version,icon,game_type_name,features,short')
                -> where($map)
                -> order('sort desc,id desc')
                -> select() -> toArray();
        return $data;
    }

}
