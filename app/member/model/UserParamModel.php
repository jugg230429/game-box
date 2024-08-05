<?php

namespace app\member\model;

use think\Model;

class UserParamModel extends Model
{

    protected $table = 'tab_user_param';

    protected $autoWriteTimestamp = true;

    /**
     * [获取第三方登录方式]
     * @param string $game_id
     * @author 郭家屯[gjt]
     */
    public function getLists($game_id = '')
    {
        $lists = $this
            ->field('id,type,game_id')
            ->where('game_id', $game_id)
            ->where('status', 1)
            ->where(['type'=>['in',[1,2]]])
            ->select()->toArray();
        return $lists;
    }
}