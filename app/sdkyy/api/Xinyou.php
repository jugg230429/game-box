<?php

namespace app\sdkyy\api;

use think\Controller;
use think\Db;

class Xinyou extends Controller
{
    private $name; //接口名称
    private $pid; //游戏中的标识
    private $login_url;  //登录地址
    private $pay_url;  //支付地址
    private $role_url;//角色地址
    private $login_key; //登录key
    private $pay_key; //支付key
    private $pay_party; //充值方 1：游戏房 2：运营方

    protected function _initialize()
    {
        $info = Db ::table('tab_game_interface') -> where(array('tag' => 'Xinyou')) -> find();
        $this -> name = $info['name'];
        $this -> pid = $info['unid'];
        $this -> login_url = $info['login_url'];
        $this -> pay_url = $info['pay_url'];
        $this -> login_key = $info['login_key'];
        $this -> role_url = $info['role_url'];
        $this -> pay_key = $info['pay_key'];
        $this -> pay_party = $info['pay_party'];

    }

    /**
     * @函数或方法说明
     * @游戏登录链接
     *
     * @param int $game_id 游戏真是ID
     * @param int $docking_server_id 真是渠道ID
     * @param int $user_id
     *
     * @return string
     * @author: Juncl
     * @time: 2021/03/10 11:39
     */
    public function play($game_id = 0, $docking_server_id = 0, $user_id = 0, $pay_url = '', $url = '', $password = '')
    {
        $userInfo = get_user_entity($user_id, false, 'age_status');
        $data['type'] = 'game';
        $data['pid'] = $this -> pid;
        $data['uid'] = $user_id;
        $data['gid'] = $game_id;
        $data['sid'] = $docking_server_id;
        $data['time'] = time();
        $data['sign'] = md5($data['pid'] . '#' . $data['uid'] . '#' . $data['gid'] . '#' . $data['sid'] . '#' . $data['time'] . '#' . $this -> login_key);
        $data['fcm'] = $userInfo['age_status'] == 2 ? 1 : 0;
        $data['xyclient'] = 0;
        $data['serverpassword'] = $password;
        $login_url = $this -> login_url . '?' . http_build_query($data);
//        dump($login_url);exit;
        $res = curl($login_url);
        $res = json_decode($res, true);
//        dump($res);exit;
        if ($res['code'] == 1) {
            return $res['data'];
        } else {
            return false;
        }
    }

    public function pay($out_trade_no, $pay_amount, $game_id, $docking_server_id, $user_id, $pay_url = '')
    {
        $spend_info = Db ::table('tab_spend') -> field('game_id,extend') -> where('pay_order_number', $out_trade_no) -> find();
        file_put_contents(dirname(__FILE__) . '/spend.txt', json_encode($spend_info));
        //原始传奇支付回调
//        if ($spend_info['game_id'] == 0) {
//            $data['other'] = $spend_info['extend'];
//        }
        $data['type'] = 'pay';
        $data['pid'] = $this -> pid;
        $data['uid'] = $user_id;
        $data['gid'] = $game_id;
        $data['sid'] = $docking_server_id;
        $data['time'] = time();
        $data['sign'] = md5($data['pid'] . '#' . $data['uid'] . '#' . $data['gid'] . '#' . $data['sid'] . '#' . $data['time'] . '#' . $this -> pay_key);
        $data['orderid'] = $out_trade_no;
        $data['money'] = $pay_amount;
        $data['paymoney'] = $pay_amount;
        $data['getmoney'] = $pay_amount;
        $data['rolename'] = '';
        $data['other'] = $spend_info['extend'];
        $pay_url = $this -> pay_url . '?' . http_build_query($data);
        file_put_contents(dirname(__FILE__) . '/pay_url.txt', $pay_url);
        $res = curl($pay_url);
        file_put_contents(dirname(__FILE__) . '/pay.txt', json_encode($res));
        $res = json_decode($res, true);
        if ($res['code'] == 1) {
            $result['status'] = 1;
            $result['msg'] = "成功";
        } else {
            $result['status'] = 0;
            $result['msg'] = "失败";
        }
        return $result;
    }

    public function get_role($game_id, $server_id, $user_id, $server_name = '')
    {
        $data['type'] = 'role';
        $data['pid'] = $this -> pid;
        $data['uid'] = $user_id;
        $data['gid'] = $game_id;
        $data['sid'] = $server_id;
        $data['time'] = time();
        $data['sign'] = md5($data['pid'] . '#' . $data['uid'] . '#' . $data['gid'] . '#' . $data['sid'] . '#' . $data['time'] . '#' . $this -> login_key);
        $role_url = $this -> role_url . '?' . http_build_query($data);
        file_put_contents(dirname(__FILE__) . '/role_url.txt', $role_url);
        $res = curl($role_url);
        file_put_contents(dirname(__FILE__) . '/role.txt', json_encode($res));
        $res = json_decode($res, true);
        if ($res['code'] == 1 && !empty($res['data'])) {
            $roleInfo = array();
            foreach ($res['data'] as $key => $val) {
                $roleInfo[$key]['server_id'] = $val['sid'];
                $roleInfo[$key]['server_name'] = $server_name ?: $val['sid'];
                $roleInfo[$key]['role_id'] = $val['rolenumber'];
                $roleInfo[$key]['role_name'] = $val['rolename'];
                $roleInfo[$key]['role_level'] = $val['level'];
                $roleInfo[$key]['fightvalue'] = $val['fighting'];
            }
            return $roleInfo;
        } else {
            return false;
        }

    }
}
