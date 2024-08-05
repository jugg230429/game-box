<?php

namespace app\member\model;

use think\Model;

/**
 * gjt
 */
class UserPlayInfoModel extends Model
{

    protected $table = 'tab_user_play_info';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @添加或更新角色信息
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2019/8/13 14:52
     */
    public function add_user_play_info($request=[]){
        if($request['user_id']!=$request['small_id']&&$request['small_id']!=''){//开启小号
            $real_user_id = $request['small_id'];
        }else{
            $real_user_id = $request['user_id'];
        }
        $map['user_id'] = $real_user_id;
        $map['game_id'] = $request['game_id'];
        $map['server_id'] = $request['server_id'];
        $map['server_name'] = $request['server_name'];
        $map['role_id'] = $request['game_player_id'];
        $data = $this->field('id')->where($map)->find();
        if($data){
            $data = $data->toArray();
            $data['role_name'] = $request['game_player_name'];
            $data['role_level'] = $request['role_level'];
            $data['combat_number'] = $request['combat_number'];
            $data['play_time'] =  time();
            $data['play_ip'] = get_client_ip();
            if($request['user_id']!=$request['small_id']&&$request['small_id']!=''){
                $data["puid"] = $request['user_id'];//记录大号
                $user_info = get_user_entity($request['user_id'],false,'promote_id,promote_account');
                $data['promote_id'] = $user_info['promote_id'];
                $data['promote_account'] = $user_info['promote_account'];
            }
            if(!empty($request['player_reserve'])){
                $data['player_reserve'] = $request['player_reserve'];
            }
            $res =  $this->where('id', $data['id'])->update($data);
        }else{
            $user_data = get_user_entity($real_user_id,false,'nickname,promote_id,promote_account,account');
            $data['promote_id'] = $user_data['promote_id'];
            $data['promote_account'] = $user_data['promote_account'];
            $data['nickname'] = $user_data['nickname'];
            $data['game_id'] = $request['game_id'];
            $data['game_name'] = $request['game_name'];
            $data['server_id'] = $request['server_id'];
            $data['server_name'] = $request['server_name'];
            $data['role_name'] = $request['game_player_name'];
            $data['role_id'] = $request['game_player_id'];
            $data['role_level'] = $request['role_level'];
            $data['combat_number'] = $request['combat_number']?:'';
            $data['user_id'] = $real_user_id;
            $data['user_account'] = $user_data['account'];
            $data['play_time'] = $data['update_time'] = time();
            $data["sdk_version"] = $request["sdk_version"];
            $data['player_reserve'] = $request['player_reserve']?:'';
            if($request['user_id']!=$request['small_id']&&$request['small_id']!=''){
                $data["puid"] = $request['user_id'];//记录大号
                $user_info = get_user_entity($request['user_id'],false,'promote_id,promote_account');
                $data['promote_id'] = $user_info['promote_id'];
                $data['promote_account'] = $user_info['promote_account'];

            }
            $data['play_ip'] = get_client_ip();
            $data['create_time'] = time();
            $res = $this->field(true)->insert($data);
        }
        return $res;
    }
}