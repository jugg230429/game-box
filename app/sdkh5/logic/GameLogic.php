<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */

namespace app\sdkh5\logic;

use app\member\model\UserModel;
use think\Db;
use  app\game\model\GameModel as modelGame;

class GameLogic extends BaseLogic
{
    public function get_login_url(array $params)
    {
        if (empty($params['user_id']) || empty($params['game_id'])) {
            return '';
        }
        //用户信息
        $mUser = new UserModel();
        $mUser -> field('id,account,nickname,head_img,sex,age_status,idcard');
        $mUser -> where(['id' => $params['user_id']]);
        $userInfo = $mUser -> find();
        //透传参数,需要cp方回传信息
        $channelExt['host'] = $this -> request -> host();
        $channelExt['ip'] = $this -> request -> ip();
        $channelExt['for_third'] = session('for_third') == '' ? '' : session('for_third');
        $channelExt['time'] = time();
        $channelExt['pid'] = $params['pid'];
        $ext = simple_encode(json_encode($channelExt));//登录透传信息，cp跳转页面后需要解密处理
        //游戏信息
        $modelGame = new modelGame();
        $modelGame -> alias('g');
        $modelGame -> field('g.id,g.game_appid,g.game_status,set.login_notify_url,set.agent_id,set.game_key,g.dev_name');
        $modelGame -> where('g.id', '=', $params['game_id']);
        $modelGame -> join(['tab_game_set' => 'set'], 'set.game_id = tab_game.id');
        $modelGameData = $modelGame -> find();
        if (empty($modelGameData) || $modelGameData['game_status'] != 1) {
            return '';
        }
        if($modelGameData['dev_name'] == '群黑'){
            $data['uname'] = $params['user_id'];
            $data['unid'] = '';
            $data['gid'] = $modelGameData['game_key'];
            $data['time'] = time();
            $data['isadult'] = $userInfo['age_status'] == '2' ? 1 : 0;
            if(!empty($userInfo['head_img'])){
                $data['uimg'] = cmf_get_image_url($userInfo['head_img']);
            }
            $data['nname'] = $userInfo['nickname'];
            $data['sex'] = $userInfo['sex'] == '0' ? 1 : 2;
            $data['gurl'] = 1;
            $data['cb'] = cmf_get_domain() . '/media/game/open_game?game_id=' . $modelGameData['id'];
            $dataStr = $data['unid'] . $data['uname'] . $data['gid'] . $data['time'] . '';
            $data['sign'] = md5($dataStr);
            foreach ($data as $k => $v) {
                $tmp[] = $k . '=' .$v;
            }
            $urlStr = implode('&', $tmp);
            $_loginUrl = 'https://m.qunhei.com/cps/login.html' . "?" . $urlStr;
            return $_loginUrl;
        }
        if(empty($modelGameData['login_notify_url'])){
            return '';
        }
        $data['channelExt'] = $ext;
        $data['agent_id'] = $modelGameData['agent_id'];
        $data['game_appid'] = $modelGameData['game_appid'];
        $data['timestamp'] = time();
        $data['loginplatform2cp'] = get_client_ip();
        $data['user_id'] = $params['user_id'];
        $data['birthday'] = get_birthday_by_idcard($userInfo['idcard']);
        ksort($data);//字典排序
        $data['sign'] = $this -> h5SignData($data, $modelGameData['game_key']);
        if(strpos($modelGameData['login_notify_url'],'?') === false){
            $_loginUrl = trim($modelGameData['login_notify_url']) . "?" . http_build_query($data);
        }else{
            $_loginUrl = trim($modelGameData['login_notify_url']) . "&" . http_build_query($data);
        }

        return $_loginUrl;

    }


    /**
     * @H5游戏签名加密方法
     *
     * @author: zsl
     * @since: 2020/6/12 17:49
     */
    public function h5SignData($data, $game_key)
    {
        ksort($data);
        foreach ($data as $k => $v) {
            $tmp[] = $k . '=' .urlencode($v);
        }
        $str = implode('&', $tmp) . $game_key;
        //file_put_contents(dirname(__FILE__)."/check.txt",$str);
        return md5($str);
    }

}