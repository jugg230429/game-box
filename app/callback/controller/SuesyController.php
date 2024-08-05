<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\callback\controller;


use app\game\model\GameModel;
use app\recharge\model\SpendModel;
use think\Request;
use think\Db;
use app\callback\controller\BaseController;
class SuesyController extends BaseController
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
        $puid = get_user_entity($params['user_id'],false,'id,account,nickname,puid');
        $params['small_id'] = $puid['id'];
        $params['small_nickname'] = $puid['nickname'];
        $user = get_user_entity($puid['puid'], false, 'id,account,promote_id,promote_account,age_status,balance');
        if (empty($user)) {
            echo '用户不存在';exit;
        }
        //判断充值金额
        $params['amount'] = (int) $params['amount'];
        if ($params['amount'] < 1) {
            //echo '金额不能低于1元';exit;
        }
        //判断订单
        $spend = Db::table('tab_spend')->where('extend',$params['trade_no'])->field('id')->find();
        if($spend){
            echo '游戏订单已存在，请重新下单';exit;
        }
        //获取游戏信息
        $mGame = new GameModel();
        $field = 'id,game_name,game_appid,pay_status,weiduan_pay_status,sdk_version';
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
        $params['game_id'] = $gameInfo['id'];
        $params['game_name'] = $gameInfo['game_name'];
        //获取微端APP支付开启状态
        $params['weiduan_pay_status'] = $gameInfo['weiduan_pay_status'];
        //获取游戏扩展信息
        $gameSetInfo = Db ::table('tab_game_set') -> field('game_id,game_key') -> where(['game_id' => $gameInfo['id']]) -> find();
        $params['game_player_id'] = $params['role_id'];
        $params['game_player_name'] = $params['role_name'];
        $params["pay_order_number"] = create_out_trade_no('SP_');
        $params['cost'] = $params['pay_amount'] = $params['amount'];
        $params["sdk_version"] = $gameInfo['sdk_version'];
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
        $game_set = Db::table('tab_game_set')->field('game_key')->where('game_id',$spend->game_id)->find();
        $param = $data;
        unset($param['sign']);
        $datasign = implode($param);
        $md5_sign = md5($datasign.$game_set['game_key']);
        if($data['sign'] != $md5_sign){
            echo '验签失败';exit;
        }
        $orderinfo['real_amount'] = $data['price'];
        $orderinfo['trade_no'] = $data['out_trade_no'];
        $orderinfo['out_trade_no'] = $spend['pay_order_number'];
        $orderinfo['pay_game_status'] = $data['pay_game_status'];
        $orderinfo['pay_way'] = $data['pay_way'];
        $result = $this->set_spend_ff($orderinfo);//分发支付调用
        if($result !== false){
            echo  'success';exit;
        }
        echo 'fail';exit;
    }
}