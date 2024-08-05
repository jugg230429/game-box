<?php

namespace app\game\model;

use think\Db;
use think\Model;

/**
 * gjt
 */
class ServerNoticeModel extends Model
{

    protected $table = 'tab_game_server_notice';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @设置提醒
     * @author: 郭家屯
     * @since: 2020/9/15 9:58
     */
    public function set_notice($user_id=0,$server_id=0)
    {
        if(empty($user_id) || empty($server_id)){
            return 0;
        }
        $notice = $this->field('id,status')
                ->where('user_id',$user_id)
                ->where('server_id',$server_id)
                ->find();
        if(empty($notice)){
            $save['user_id'] = $user_id;
            $save['server_id'] = $server_id;
            $save['create_time'] = time();
            $result = $this->insert($save);
        }else{
            $status = $notice->status == 0 ? 1 : 0;
            $result = $this->where('id',$notice->id)->setField('status',$status);
        }
        return $result;
    }

}