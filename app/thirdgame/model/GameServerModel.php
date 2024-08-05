<?php

namespace app\thirdgame\model;

use think\Model;

class GameServerModel extends Model
{
    protected $table = 'tab_game_server';

    public function importServer($game_id=0, $param=[])
    {
        // 导入失败个数
        $err_num = 0;
        // 导入成功个数
        $suc_num = 0;
        $game_info = get_game_entity($game_id,'sdk_version');
        foreach ($param as $key => $val)
        {
            $ServerData = [];
            $ServerData['game_id'] = $game_id;
            $ServerData['server_name'] = $val['server_name']?:'';
            $ServerData['server_num'] = $val['server_num']?:0;
            $ServerData['desride'] = $val['desride']?:'';
            $ServerData['start_time'] = $val['start_time']?:'';
            $ServerData['sdk_version'] = $game_info['sdk_version']?:'';
            $ServerData['third_server_id'] = $val['id']?:0;
            $ServerId = $this->insertGetId($ServerData);
            if($ServerId > 0){
                $suc_num++;
            }else{
                $err_num++;
            }
        }
        $data = [];
        $data['err_num'] = $err_num;
        $data['suc_num'] = $suc_num;
        return $data;
    }
}