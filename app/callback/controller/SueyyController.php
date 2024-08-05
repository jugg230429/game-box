<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\callback\controller;


use app\game\model\GameModel;
use app\game\model\ServerModel;
use app\recharge\model\SpendModel;
use think\Request;
use think\Db;
class SueyyController extends BaseController
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    /**
     * @函数或方法说明
     * @创建订单
     * @author: 郭家屯
     * @since: 2020/10/22 15:20
     */
    public function createorder()
    {
        $params = $this->request->param();
        $user = get_user_entity($params['uid'], false, 'id,account,promote_id,promote_account,age_status,balance');
        if (empty($user)) {
            echo '用户不存在';exit;
        }

        //判断充值金额
        $params['amount'] = (float)$params['amount'];
        if ($params['amount'] < 0.01) {
            echo '金额不能低于1分钱';exit;
        }
        //判断订单
        $spend = Db::table('tab_spend')->where('extend',$params['trade_no'])->field('id')->find();
        if($spend){
            echo '游戏订单已存在，请重新下单';exit;
        }
        //获取游戏信息
        $mGame = new GameModel();
        $field = 'id,game_name,game_appid,pay_status,interface_id';
        $where = [];
        $where['game_appid'] = $params['game_appid'];
        $gameInfo = $mGame -> field($field) -> where($where) -> find();
        if (empty($gameInfo)) {
            echo '游戏不存在';exit;
        }
        //判断游戏是否开启充值
        if ($gameInfo['pay_status'] != '1') {
            echo '游戏未开启充值';exit;
        }
        $servermodel = new ServerModel();
        $servermodel->where('game_id',$gameInfo['id']);
        $servermodel->where('server_num',$params['sid']);
        $serverData = $servermodel->find();
        if(empty($serverData)){
            echo '区服不存在';exit;
        }
        $params['game_id'] = $gameInfo['id'];
        $params['game_name'] = $gameInfo['game_name'];
        $params['server_id'] = $serverData['id'];
        $params['server_name'] = $serverData['server_name'];
        //参数验签
        $interface = Db::table('tab_game_interface')->field('pay_key')->where('id',$gameInfo['interface_id'])->find();
        $data = [];
        $data['trade_no'] = $params['trade_no'];
        $data['gid'] = $params['gid'];
        $data['uid'] = $params['uid'];
        $data['sid'] = $params['sid'];
        $data['amount'] = $params['amount'];
        $data['game_appid'] = $params['game_appid'];
        $data['time'] = $params['time'];
        $data['sign'] = md5($data['trade_no'].$data['gid'].$data['uid'].$data['sid'].$data['amount'].$data['game_appid'].$data['time'].$interface['pay_key']);
        if ($data['sign'] != $params['sign']) {
            //file_put_contents(dirname(__FILE__).'/sign.txt',json_encode($data));
            echo '充值验签失败';exit;
        }
        $params["pay_order_number"] = create_out_trade_no('SP_');
        $params['cost'] = $params['pay_amount'] = $params['amount'];
        $params["sdk_version"] = 4;
        $params["pay_way"] = 0;
        $params['extend'] = $params['trade_no'];
        $result = add_spend($params,$user);
        if($result){
            echo 'success';exit;
        }else{
            echo '创建订单失败';exit;
        }
    }

    /**
     * @函数或方法说明
     * @游戏通知状态
     * @author: 郭家屯
     * @since: 2020/10/23 13:48
     */
    public function paynotice()
    {
        $data = $this->request->param();
        $model = new SpendModel();
        $model->field('id,game_id,pay_order_number');
        $model->where('extend',$data['out_trade_no']);
        $spend = $model->find();
        $gamemodel = new GameModel();
        $gameDate = $gamemodel->field('interface_id')->where('id',$spend['game_id'])->find();
        $interface = Db::table('tab_game_interface')->field('pay_key')->where('id',$gameDate['interface_id'])->find();
        $sign = md5($data['out_trade_no'].$data['pay_extra'].$data['pay_status'].$data['pay_game_status'].$data['price'].$data['user_id'].$data['pay_way'].$interface['pay_key']);
        if($data['sign'] != $sign){
            echo '验签失败';exit;
        }
        $orderinfo['real_amount'] = $data['price'];
        $orderinfo['trade_no'] = $data['out_trade_no'];
        $orderinfo['pay_game_status'] = $data['pay_game_status'];
        $orderinfo['pay_way'] = $data['pay_way'];
        $orderinfo['out_trade_no'] = $spend['pay_order_number'];
        $result = $this->set_spend_ff($orderinfo);//分发支付调用
        if($result !== false){
            echo  'success';exit;
        }
        echo 'fail';exit;
    }

    //H5加密方式
    protected function h5SignData($data, $game_key)
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