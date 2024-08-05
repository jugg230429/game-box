<?php

namespace app\btwelfare\api;

class Game2Api extends BaseApi
{

    //加密key: 16位随机字符串
    const KEY = 'KVctAn8dTsIevwD6';

    //发放道具url
    const SEND_PROP_URL = 'http://www.example.com';


    /**
     * @发放道具
     *
     * @author: zsl
     * @since: 2021/1/14 20:38
     */
    public function send($param)
    {


        $request = [
                'game_id' => $param['game_id'],
                'user_id' => $param['user_id'],
                'server_id' => $param['server_id'],
                'server_name' => $param['server_name'],
                'game_player_id' => $param['game_player_id'],
                'game_player_name' => $param['game_player_name'],
                'prop_id' => $param['prop_id'],   //道具标识
                'prop_num' => $param['prop_num'],   //道具数量
        ];
        $request['sign'] = $this -> vSign($request, self::KEY);
        $res = $this -> post($request, self::SEND_PROP_URL);
        $result = json_decode($res, true);
        if ($result['code'] == 0) {
            return false;
        }
        return true;
    }


    /**
     * @函数或方法说明
     *
     * @author: zsl
     * @since: 2021/1/14 20:46
     */
    public function checkSign($param)
    {
        $sign = $this -> vSign($param, self::KEY);
        if ($param['sign'] != $sign) {
            return false;
        }
        return true;
    }


}
