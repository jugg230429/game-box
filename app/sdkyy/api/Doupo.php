<?php
namespace app\sdkyy\api;

use think\Controller;
use think\Db;
class Doupo extends Controller{
    private $name; //接口名称
    private $unid; //游戏中的标识
    private $login_url;  //登录地址
    private $pay_url;  //支付地址
    private $login_key; //登录key
    private $pay_key; //支付key
    private $pay_party; //充值方 1：游戏房 2：运营方

    protected function _initialize() {
        $info = Db::table('tab_game_interface')->where(array('tag'=>'Doupo'))->find();
        $this->name = $info['name'];
        $this->unid = $info['unid'];
        $this->login_url = $info['login_url'];
        $this->pay_url =$info['pay_url'];
        $this->login_key = $info['login_key'];
        $this->pay_key = $info['pay_key'];
        $this->pay_party = $info['pay_party'];
    }

    /**
     * [游戏登录接口]
     * @param $game_id  游戏真实ID
     * @param $docking_server_id  游戏真实区服
     * @param $user 用户ID
     * @return string 返回登录地址
     * @author 郭家屯[gjt]
     */
	public function play($game_id,$docking_server_id,$user_id){
        $start['uid'] = $user_id;
        $start['time']=time();
        $start['token']=md5($start['uid'].$start['time'].$this->login_key.$docking_server_id);
        $start['sid'] = $docking_server_id;
        $start['isclient'] = 0;
        $start['platname'] = $this->unid;
        $url=$this->login_url.'?'.http_build_query($start);
        return $url;
    }

    /**
     * [游戏币支付接口]
     * @param $out_trade_no 订单号
     * @param $pay_amount  支付金额
     * @param $game_id  游戏真实ID
     * @param $docking_server_id  游戏真实区服
     * @param $user_id 用户ID
     * @return mixed 返回支付结果
     * @author 郭家屯[gjt]
     */
    public function pay($out_trade_no,$pay_amount,$game_id,$docking_server_id,$user_id,$pay_url=''){
        $cz_url = $this->pay_url;
        $pay_key = $this->pay_key;
        $agent_id = $this->unid;

        //$gid = strtolower($game_id);
        $serverNo = $docking_server_id;
        $uid = $user_id;
        $orderID = $out_trade_no;
        $amount = (int)$pay_amount;
        $amount = 1;
        $time=time();
        $sign_var=$uid.$time.$amount.$orderID.$pay_key.$serverNo;
        $sign=strtolower(md5($sign_var));
        $data="uid={$uid}&time={$time}&point={$amount}&order={$orderID}&token={$sign}&sid={$serverNo}&isclient=0&platname={$agent_id}&pay_url={$pay_url}";
        $url=$cz_url."?".$data;
        //$datas = curl($url);
        $datas = 1;
        $myfile = fopen("pay.txt", "w");
        fwrite($myfile, $datas);
        fclose($myfile);
        switch ($datas){
            case 1:
                $result['status'] = 1;
                $result['msg'] = "成功";
                break;
            case 0:
                $result['status'] = 0;
                $result['msg'] = "参数不全或服务器报错";
                break;
            case 2:
                $result['status'] = 0;
                $result['msg'] = "合作方式不存在";
                break;
            case 3:
                $result['status'] = 0;
                $result['msg'] = "金额超出范围（<=100,000）";
                break;
            case 4:
                $result['status'] = 0;
                $result['msg'] = "服务器未登记";
                break;
            case 5:
                $result['status'] = 0;
                $result['msg'] = "验证失败";
                break;
            case 6:
                $result['status'] = 0;
                $result['msg'] = "充值游戏不存在";
                break;
            case 7:
                $result['status'] = 0;
                $result['msg'] = "玩家角色不存在";
                break;
            case -7:
                $result['status'] = 0;
                $result['msg'] = "订单号重复";
                break;
            case -1:
                $result['status'] = 0;
                $result['msg'] = "访问IP不在白名单";
                break;
            case -4:
                $result['status'] = 0;
                $result['msg'] = "充值失败";
                break;
            case -14:
                $result['status'] = 0;
                $result['msg'] = "充值失败";
                break;
            case -102:
                $result['status'] = 0;
                $result['msg'] = "游戏无响应";
                break;
            case -200:
                $result['status'] = 0;
                $result['msg'] = "超时，请校验服务器时间";
                break;
        }
        return $result;
    }

    /**
     * [游戏首冲接口]
     * @param $out_trade_no 订单号
     * @param $game_id  游戏真实ID
     * @param $docking_server_id  游戏真实区服
     * @param $user_id 用户ID
     * @return mixed 返回支付结果
     * @author 郭家屯[gjt]
     */
    public function firstpay($out_trade_no,$game_id,$docking_server_id,$user_id){
        $cz_url = $this->pay_url;
        $pay_key = $this->pay_key;
        $agent_id = $this->unid;
        $gid = strtolower($game_id);
        $serverNo = $docking_server_id;
        $uid = $user_id;
        $orderID = $out_trade_no;
        $time=time();
        $sign_var=$agent_id.$gid.$serverNo.$uid.$orderID.$time.$pay_key;
        $sign=strtolower(md5($sign_var));
        $data="agent_id={$agent_id}&game_id={$gid}&serverNo={$serverNo}&uid={$uid}&OrderID={$orderID}&time={$time}&sign={$sign}";
        $url=$cz_url."?".$data;
        $datas = curl($url);
        $myfile = fopen("firstpay.txt", "w");
        fwrite($myfile, $datas);
        fclose($myfile);
        if($datas['status'] == "SUCCESS"){
            $result['status'] = 1;
        }else{
            $result['starus'] = 0;
        }
        return $result;
    }

    /**
     * @函数或方法说明
     * @获取角色
     * @author: 郭家屯
     * @since: 2020/9/22 20:15
     */
    public function get_role($api_id,$server_id,$user_id)
    {
        $start['uid'] = $user_id;
        $start['time'] = time();
        $start['server_id'] = $server_id;
        $start['sign'] = md5($start['uid'] . $start['server_id'] . $start['time'] . '1b64fadee636f3743cfba8e70ca1f408');
        $roleUrl = $this->role_url . '?' . http_build_query($start);
        \Think\Log::record("角色获取url：".$roleUrl);
        $data = curl($roleUrl);
        \Think\Log::record("角色返回数据：".$data);
        $data = json_decode($data,true);
        if($data['code'] == 1 && !empty($data['data'])){
            $roleInfo = array();
            foreach($data['data'] as $key => $val){
                $roleInfo[$key]['server_id'] = $val['server_id'];
                $roleInfo[$key]['server_name'] = $val['server_name'];
                $roleInfo[$key]['role_id'] = $val['id'];
                $roleInfo[$key]['role_name'] = $val['name'];
                $roleInfo[$key]['level'] = $val['level'];
                $roleInfo[$key]['fightvalue'] = $val['fightPower'];
            }
            return $roleInfo;
        }else{
            return false;
        }
    }




    public function getCard($game,$server,$user,$gift){
		$start['tp']='card';
		$start['gid']=$server['gid'];
		$start['sid']=$server['sid'];
		$start['uid']=$user['id'];
		$start['mid']=$game['unid'];
		$start['type']=$gift['type'];
		$start['p']=$gift['p'] ?: 'false';
		$start['cate']=$gift['cate'] ?: '0';
		$start['sign']=md5($start['agent_id'].$start['game_id'].$start['serverNo'].$start['uid'].$start['time'].$this->info['pay_key']);
		$data=\Org\Net\HttpCurl::get('http://www.ufojoy.com/auth/dengji.phtml',$start);
		return '签名错误';
	}



	
	
}






?>