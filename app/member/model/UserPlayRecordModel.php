<?php

namespace app\member\model;

use think\Model;
use think\Db;

/**
 * gjt
 */
class UserPlayRecordModel extends Model
{

    protected $table = 'tab_user_play_record';

    protected $autoWriteTimestamp = true;


    /**
     * @函数或方法说明
     * @页游游戏在玩记录
     * @param array $map
     * @param array $user
     * @param array $game
     *
     * @author: 郭家屯
     * @since: 2019/8/12 16:56
     */
    public function login($param=[]){
        $res = $this->field('id')->where($param)->find();
        if(!$res){
           $param['create_time'] = time();
           $this->insert($param);
        }else{
            $this->where('id',$res['id'])->setField('create_time',time());
        }
        return true;
    }

}