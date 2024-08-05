<?php
namespace app\sdkyy\api;

use think\Controller;
use think\Db;
class Demo extends Controller{
    private $name; //接口名称
    private $unid; //游戏中的标识
    private $login_url;  //登录地址
    private $pay_url;  //支付地址
    private $login_key; //登录key
    private $pay_key; //支付key
    private $pay_party; //充值方 1：游戏房 2：运营方
    private $role_url;

    protected function _initialize() {
        $info = Db::table('tab_game_interface')->where(array('tag'=>'Demo'))->find();
        $this->name = $info['name'];
        $this->unid = $info['unid'];
        $this->login_url = $info['login_url'];
        $this->pay_url =$info['pay_url'];
        $this->login_key = $info['login_key'];
        $this->pay_key = $info['pay_key'];
        $this->pay_party = $info['pay_party'];
        $this->role_url = $info['role_url'];
    }

    /**
     * [游戏登录接口]
     * @param $game_id  游戏真实ID
     * @param $docking_server_id  游戏真实区服
     * @param $user 用户ID
     * @return string 返回登录地址
     * @author 郭家屯[gjt]
     */
	public function play($game_id,$docking_server_id,$user_id,$pay_ur_img='',$pay_url=''){
        $start['pid'] = $this->unid;
        $start['uid'] = $user_id;
        $start['gid'] = $game_id;
        $start['sid'] = $docking_server_id;
        $start['time']=time();
        $start['sign']=md5($start['uid'].$start['gid'].$start['sid'].$start['pid'].$start['time'].$this->login_key);
        $start['pay_url_img'] = $pay_ur_img;
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
    public function pay($out_trade_no,$pay_amount,$game_id,$docking_server_id,$user_id){
        $cz_url = $this->pay_url;
        $pid =$this->unid;//渠道id（也是混服标示，自己后台查看）
        $uid =$user_id;
        $gid =$game_id;
        $sid =$docking_server_id;
        $time=time();
        $key =$this->pay_key;//登陆key和充值key是一个，自己后台查看
        $order=$out_trade_no;//订单号
        //$order='';//订单号
        $money=(int)$pay_amount;//充值人民币
        $sign = md5($order.$uid.$gid.$sid.$money.$pid.$time.$key);
        $data = "gid={$gid}&sid={$sid}&pid={$pid}&time={$time}&uid={$uid}&order={$order}&money={$money}&sign={$sign}";
        $url=$cz_url."?".$data;
        //file_put_contents(dirname(__FILE__).'/pay_url.txt',$url);
        $datas = curl($url);
        //file_put_contents(dirname(__FILE__).'/pay.txt',$datas);
        switch ($datas){
            case 1:
                $result['status'] = 1;
                $result['msg'] = "成功";
                break;
            case -1:
                $result['status'] = 0;
                $result['msg'] = "pid不存在";
                break;
            case -2:
                $result['status'] = 0;
                $result['msg'] = "加密错误";
                break;
            case -3:
                $result['status'] = 0;
                $result['msg'] = "游戏没开混服";
                break;
            case -4:
                $result['status'] = 0;
                $result['msg'] = "区服不存在";
                break;
            case -5:
                $result['status'] = 0;
                $result['msg'] = "区服没到开服时间\游戏已关闭\区服已关闭";
                break;
            case -6:
                $result['status'] = 0;
                $result['msg'] = "金额小于0";
                break;
            case -7:
                $result['status'] = 0;
                $result['msg'] = "订单不能为空";
                break;
            case -8:
                $result['status'] = 0;
                $result['msg'] = "订单已存在";
                break;
            default:
                $result['status'] = 0;
                $result['msg'] = "未知错误";
        }
        return $result;
    }

    /**
     * @函数或方法说明
     * @获取角色
     * @author: 郭家屯
     * @since: 2020/9/22 20:15
     */
    public function get_role($game_id,$server_id,$user_id,$server_name='')
    {
        $pid = $this->unid;//渠道id（也是混服标示，自己后台查看）
        $uid = $user_id;
        $gid = $game_id;
        $sid = $server_id;
        $time = time();
        $key =$this->pay_key;//登陆key和充值key是一个，自己后台查看
        $sign = md5($uid.$gid.$sid.$pid.$time.$key);
        $login_url = $this->role_url;//角色查询
        $roleUrl="$login_url?game_id={$gid}&server_id={$sid}&pid={$pid}&time={$time}&user_id={$uid}&sign={$sign}";
        //$data = curl($roleUrl);
        //$data = json_decode($data,true);
        $data = [
                'status'=>1,
                'id'=>'425351',
                'name'=>'孙行者',
                'level'=>90
        ];
        if($data['status'] == 1){
            $roleInfo['server_id'] = $server_id;
            $roleInfo['server_name'] = $data['server_name']?:$server_name;
            $roleInfo['role_id'] = $data['id']?:'';
            $roleInfo['role_name'] = $data['name'];
            $roleInfo['role_level'] = $data['level'];
            $info[] = $roleInfo;
            return $info;
        }else{
            return false;
        }
    }



	
	
}






?>