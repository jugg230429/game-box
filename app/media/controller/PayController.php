<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 16:43
 */

namespace app\media\controller;

use app\game\model\ServerModel;
use app\recharge\logic\CheckAgeLogic;
use app\recharge\model\SpendModel;
use app\sdkyy\logic\GameLogic;
use app\api\GameApi;
use think\Db;
use think\weixinsdk\Weixin;
use app\recharge\model\SpendBalanceModel;
use app\recharge\model\SpendBindModel;

class PayController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $action = request()->action();
        if ($action != 'pay' && (AUTH_PAY != 1 || AUTH_USER != 1)) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限和充值权限');
            } else {
                $this->error('请购买用户权限和充值权限', url('index/index'));
            }
        }
    }

    /**
     * [支付]
     * @author 郭家屯[gjt]
     */
    public function pay()
    {
        $game_id = $this->request->param('gameid');
        $server_id = $this->request->param('serverid');

        //封禁判断-20210713-byh
        if(!judge_user_ban_status($this->request->param('promote_id'),$game_id,session('member_auth.user_id'),$this->request->param('equipment_num'),get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }
        if($game_id && $server_id){
            $game = Db::table('tab_game_server')->where(['game_id'=>$game_id,'server_num' => $server_id])->value('id');
            $this->assign('game_id',$game_id);
            $this->assign('server_id',$game??$server_id);
        }
        return $this->fetch();
    }

    /**
     * [检查用户名并返回订单号]
     * @return \think\response\Json
     * @author 郭家屯[gjt]
     */
    public function checkAccount()
    {
        $account = $this->request->param('account');
        $coin_type = $this->request->param('coin_type');
        $game_id = $this->request->param('game_id');
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($this->request->param('promote_id'),$game_id,session('member_auth.user_id'),$this->request->param('equipment_num'),get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }

        //排除页游
        $gameInfo = get_game_entity($game_id,'id,sdk_version');
        if($gameInfo['sdk_version']!=4){
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($account, true);
            if ($isCustom) {
                return json(['code' => 2]);
            }
        }

        if(empty($coin_type)){
            $user = get_user_entity($account, true);
            $orderno = create_out_trade_no("PF_");
        }else{
            $user = Db::table('tab_user_play')->field('id')->where('user_account',$account)->where('game_id',$game_id)->find();
            $orderno = create_out_trade_no("PB_");
        }
        if ($user) {
            return json(['code' => 1, 'orderno' => $orderno]);
        } else {
            return json(['code' => 0]);
        }
    }

    /**
     * [支付宝支付]
     * @param $data
     * @author 郭家屯[gjt]
     */
    public function alipay()
    {
        $data = $this->request->param();
        //封禁判断-20210713-byh
        $ban_game_id = $data['game_id'];
        if($data['coin_type'] == '1'){
            $ban_game_id = $data['ygame_id'];
        }
        if(!judge_user_ban_status($data['promote_id'],$ban_game_id,session('member_auth.user_id'),$data['equipment_num'],get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }
        // 判断是否是pc端充值
        $is_pc = (int) ($data['is_pc'] ?? 0); // 0 还是之前的逻辑 1 是pc页面请求的充值

        if($is_pc == 1){

        }else{
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($data['account'], true);
            if ($isCustom) {
                $this->error('该账号暂不支持平台币/绑币，请在游戏中支付');
            }
        }

        $user = get_user_entity($data['account'], true,'id,account,nickname,promote_id,promote_account');
        if (empty($user)) {
            $this->error('用户不存在');
        }
        $data['money'] = (int)$data['money'];
        if ($data['money'] < 1) {
            $this->error('金额不能低于1元');
        }
        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "create_direct_pay_by_user";
        $data['pay_way'] = 3;
        $discount = 10;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        $data['cost'] = $data['money'];
        if($data['coin_type'] == '-1'){
            if(empty($data['game_id'])){
                $this->error('请选择游戏');
            }
            $userplay = Db::table('tab_user_play')->field('user_id,user_account,game_id,game_name')->where('user_account',$data['account'])->where('game_id',$data['game_id'])->find();
            if(!$userplay){
                $this->error('游戏角色不存在');
            }
            $data['game_id'] = $userplay['game_id'];
            $data['game_name'] = $userplay['game_name'];
            $body = "绑币充值";
            $title = "绑币";
            $table = 'bind';
            //获取折扣-绑币充值折扣新增-byh-20210825-start
            $lPay = new \app\common\logic\PayLogic();
            $discount_info = $lPay -> get_bind_discount($data['game_id'], $user['promote_id'], $user['id']);
            $data['discount'] = $discount_info['discount']*10;
            $data['discount_type'] = $discount_info['discount_type'];
            $data['money'] = round($data['money']*$discount_info['discount'],2);
            //获取折扣-绑币充值折扣新增-byh-20210825-end
        }elseif($data['coin_type'] == '1'){

            //检查未成年用户是否满足充值条件
            if (get_user_config_info('age')['real_pay_status'] == 1) {
                $lCheckAge = new CheckAgeLogic();
                $checkAgeRes = $lCheckAge -> run($user['id'], $data['money']);
                if (false === $checkAgeRes) {
                    $this -> error($lCheckAge -> getErrorMsg());
                }
            }

            $body = "游戏充值";
            $title = "游戏币";
            $data['game_id'] = $data['ygame_id'];
            $userplay = Db::table('tab_user_play_record')
                    ->field('id')
                    ->where('user_id',$user['id'])
                    ->where('game_id',$data['game_id'])
                    ->where('server_id',$data['server_id'])
                    ->find();
            if(!$userplay){
                $this->error('游戏角色不存在');
            }
            $game = get_game_entity($data['game_id'],'game_name,sdk_version');
            $data['game_name'] = $game['game_name'];
            $data['sdk_version'] = $game['sdk_version'];
            $data['server_name'] = get_server_name($data['server_id']);
            $lPay = new \app\common\logic\PayLogic();
            $discount_info = $lPay -> get_discount($data['game_id'], $user['promote_id'], $user['id']);
            $data['discount'] = $discount_info['discount']*10;
            $data['discount_type'] = $discount_info['discount_type'];
            $data['money'] = round($data['money']*$discount_info['discount'],2);
            $data['extra_param'] = cmf_get_domain();
            $table = 'spend';
        }else{
            $body = "平台币充值";
            $title = "平台币";
            $table = "deposit";
        }
        $pay = new \think\Pay($data['pay_type'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody($body)
            ->setFee($data['money'])//支付金额
            ->setTitle($title)
            ->setOrderNo($data['order_no'])
            ->setService($data['service'])
            ->setSignType("MD5")
            ->setPayMethod("direct")
            ->setTable($table)
            ->setPayWay($data['pay_way'])
            ->setUserId($user['id'])
            ->setAccount($user['account'])
            ->setUserNickName($user['nickname'])
            ->setPromoteId($user['promote_id'])
            ->setPromoteName($user['promote_account'])
            ->setGameId($data['game_id'])
            ->setGameName($data['game_name'])
            ->setExtend(!empty($data['other']) ? $data['other'] : $data['order_no'])
            ->setServerId($data['server_id'])
            ->setServerName($data['server_name'])
            ->setDiscount($data['discount'])
            ->setSdkVersion($data['sdk_version'])
            ->setDiscount_type($data['discount_type'])
            ->setCost($data['cost']);
        echo $pay->buildRequestForm($vo);
    }

    /**
     * [微信支付]
     * @param $data
     * @author 郭家屯[gjt]
     */
    public function weixinpay()
    {
        $data = $this->request->param();
        //封禁判断-20210713-byh
        $ban_game_id = $data['game_id'];
        if($data['coin_type'] == '1'){
            $ban_game_id = $data['ygame_id'];
        }
        if(!judge_user_ban_status($data['promote_id'],$ban_game_id,session('member_auth.user_id'),$data['equipment_num'],get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }

        // 判断是否是pc端充值
        $is_pc = (int) ($data['is_pc'] ?? 0); // 0 还是之前的逻辑 1 是pc页面请求的充值

        if($is_pc == 1){

        }else{
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($data['account'], true);
            if ($isCustom) {
                $this->error('该账号暂不支持平台币/绑币，请在游戏中支付');
            }
        }

        $user = get_user_entity($data['account'], true,'id,account,promote_id,promote_account');
        if (empty($user)) {
            $this->error('用户不存在');
        }
        $data['money'] = (int)$data['money'];
        if ($data['money'] < 1) {
            $this->error('金额不能低于1元');
        }
        if (pay_type_status('wxscan') != 1) {
            $this->error('微信支付未开启');
        }
        $data['cost'] = $data['money'];
        if($data['coin_type'] == '-1'){
            if(empty($data['game_id'])){
                $this->error('请选择游戏');
            }
            $userplay = Db::table('tab_user_play')->field('user_id,user_account,game_id,game_name')->where('user_account',$data['account'])->where('game_id',$data['game_id'])->find();
            $data['game_name'] = $userplay['game_name'];
            if(!$userplay){
                $this->error('游戏角色不存在');
            }
            //获取折扣
            $title = "绑币充值";
            //获取折扣-绑币充值折扣新增-byh-20210825-start
            $lPay = new \app\common\logic\PayLogic();
            $discount_info = $lPay -> get_bind_discount($data['game_id'], $user['promote_id'], $user['id']);
            $data['discount'] = $discount_info['discount']*10;
            $data['discount_type'] = $discount_info['discount_type'];
            $data['money'] = round($data['money']*$discount_info['discount'],2);
            //获取折扣-绑币充值折扣新增-byh-20210825-end
        }elseif($data['coin_type'] == '1'){
            //检查未成年用户是否满足充值条件
            if (get_user_config_info('age')['real_pay_status'] == 1) {
                $lCheckAge = new CheckAgeLogic();
                $checkAgeRes = $lCheckAge -> run($user['id'], $data['money']);
                if (false === $checkAgeRes) {
                    $this -> error($lCheckAge -> getErrorMsg());
                }
            }
            $data['title'] = $title = "游戏充值";
            $data['game_id'] = $data['ygame_id'];
            $userplay = Db::table('tab_user_play_record')
                    ->field('id')
                    ->where('user_id',$user['id'])
                    ->where('game_id',$data['game_id'])
                    ->where('server_id',$data['server_id'])
                    ->find();
            if(!$userplay){
                $this->error('游戏角色不存在');
            }
            $game = get_game_entity($data['game_id'],'game_name,sdk_version');
            $data['game_name'] = $game['game_name'];
            $data['sdk_version'] = $game['sdk_version'];
            $data['server_name'] = get_server_name($data['server_id']);
            $lPay = new \app\common\logic\PayLogic();
            $discount_info = $lPay -> get_discount($data['game_id'], $user['promote_id'], $user['id']);
            $data['discount'] = $discount_info['discount'];
            $data['discount_type'] = $discount_info['discount_type'];
            $data['money'] = round($data['money']*$discount_info['discount'],2);
            $data['extra_param'] = cmf_get_domain();
        }else{
            $title = "平台币充值";
        }
        $data['pay_amount'] = $data['money'];
        $data['pay_order_number'] = $data['order_no'];
        $data['pay_status'] = 0;
        $data['pay_way'] = 4;
        $data['user_id'] = $user['id'];
        $data['spend_ip'] = get_client_ip();
        if (!empty($data['other'])) {
            $data['extend'] = $data['other'];
        }
        $weixn = new Weixin();
        $is_pay = json_decode($weixn->weixin_pay($title, $data['pay_order_number'], $data['money']), true);
        if ($is_pay['status'] == 1) {
            if($data['coin_type'] == '-1'){
                add_bind($data,$user);
            }elseif($data['coin_type'] == '1'){
                add_spend($data,$user);
            }else{
                add_deposit($data,$user);
            }
        } else {
            $this->error('支付失败');
        }
        $this->assign('param', $data);
        $this->assign('result', $is_pay);
        return $this->fetch('weixinpay');
    }

    /**
     * @函数或方法说明
     * @平台币充值
     * @author: 郭家屯
     * @since: 2020/9/24 19:47
     */
    public function platformpay()
    {
        $data = $this->request->param();
        //封禁判断-20210713-byh
        $ban_game_id = $data['game_id'];
        if($data['coin_type'] == '1'){
            $ban_game_id = $data['ygame_id'];
        }
        if(!judge_user_ban_status($data['promote_id'],$ban_game_id,session('member_auth.user_id'),$data['equipment_num'],get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }

        //排除页游
        $gameInfo = get_game_entity($data['game_id'], 'id,sdk_version');
        if ($gameInfo['sdk_version'] != 4) {
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($data['account'], true);
            if ($isCustom) {
                $this -> error('该账号暂不支持平台币/绑币，请在游戏中支付');
            }
        }

        $from_user = get_user_entity(session('member_auth.user_id'),false,'id,account,balance,pay_password');
        $user = get_user_entity($data['account'], true,'id,account,promote_id,promote_account');
        if (empty($user)|| empty($from_user)) {
            $this->error('用户不存在');
        }
        if(empty($from_user['pay_password'])){
            $this->error('请前往个人中心设置支付密码');
        }
        if (!xigu_compare_password($data['pay_password'], $from_user['pay_password'])) {
            $this->error('支付密码不正确');
        }
        $data['money'] = (int)$data['money'];
        if ($data['money'] < 1) {
            $this->error('金额不能低于1元');
        }
        $data['cost'] = $data['money'];
        if($data['coin_type'] == '-1') {
            if (empty($data['game_id'])) {
                $this -> error('请选择游戏');
            }
            $userplay = Db ::table('tab_user_play') -> field('user_id,user_account,game_id,game_name') -> where('user_account', $data['account']) -> where('game_id', $data['game_id']) -> find();
            $data['game_name'] = $userplay['game_name'];
            if (!$userplay) {
                $this -> error('游戏角色不存在');
            }
            //获取折扣
            $data['title'] = $title = "绑币充值";
            //获取折扣-绑币充值折扣新增-byh-20210825-start
            $lPay = new \app\common\logic\PayLogic();
            $discount_info = $lPay -> get_bind_discount($data['game_id'], $user['promote_id'], $user['id']);
            $data['discount'] = $discount_info['discount']*10;
            $data['discount_type'] = $discount_info['discount_type'];
            $data['money'] = round($data['money']*$discount_info['discount'],2);
            //获取折扣-绑币充值折扣新增-byh-20210825-end
        }elseif($data['coin_type'] == '1'){

            //检查未成年用户是否满足充值条件
            if (get_user_config_info('age')['real_pay_status'] == 1) {
                $lCheckAge = new CheckAgeLogic();
                $checkAgeRes = $lCheckAge -> run($user['id'], $data['money']);
                if (false === $checkAgeRes) {
                    $this -> error($lCheckAge -> getErrorMsg());
                }
            }

            $data['title'] = $title = "游戏充值";
            $data['game_id'] = $data['ygame_id'];
            $userplay = Db::table('tab_user_play_record')
                    ->field('id')
                    ->where('user_id',$user['id'])
                    ->where('game_id',$data['game_id'])
                    ->where('server_id',$data['server_id'])
                    ->find();
            if(!$userplay){
                $this->error('游戏角色不存在');
            }
            $game = get_game_entity($data['game_id'],'game_name,sdk_version');
            $data['game_name'] = $game['game_name'];
            $data['sdk_version'] = $game['sdk_version'];
            $data['server_name'] = get_server_name($data['server_id']);
            $lPay = new \app\common\logic\PayLogic();
            $discount_info = $lPay -> get_discount($data['game_id'], $user['promote_id'], $user['id']);
            $data['discount'] = $discount_info['discount'];
            $data['discount_type'] = $discount_info['discount_type'];
            $data['money'] = round($data['money']*$discount_info['discount'],2);
            $data['extra_param'] = cmf_get_domain();
        }else{
            $this->error('支付方式错误');
        }

        if($data['money'] > $from_user['balance']){
            $this->error('余额不足');
        }
        $data['pay_amount'] = $data['money'];
        $data['pay_order_number'] = $data['order_no'];
        $data['pay_way'] = 2;
        $data['user_id'] = $user['id'];
        $data['spend_ip'] = get_client_ip();
        $data['pay_status'] = 1;
        Db::startTrans();
        try{
            //绑币充值
            if($data['coin_type'] == '-1'){
                Db::table('tab_user')->where('id',$from_user['id'])->setDec('balance',$data['pay_amount']);
                Db::table('tab_user_play')->where('user_id',$userplay['user_id'])->where('game_id',$userplay['game_id'])->setInc('bind_balance',$data['cost']);
                add_bind($data,$user);
            }else{
                if (!empty($data['other'])) {
                    $data['extend'] = $data['other'];
                }
                //游戏充值
                Db::table('tab_user')->where('id',$from_user['id'])->setDec('balance',$data['pay_amount']);
                add_spend($data,$user);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error('支付失败');
        }
        //游戏充值通知
        if($data['coin_type'] == '1'){
            $logic = new GameApi();
            $param['out_trade_no'] = $data['order_no'];
            $logic->game_pay_notify($param);
        }
        $this->success('支付成功');
    }

    /**
     * @函数或方法说明
     * @绑币充值游戏
     * @author: 郭家屯
     * @since: 2020/9/24 19:47
     */
    public function bindpay()
    {
        $data = $this->request->param();
        //封禁判断-20210713-byh
        $ban_game_id = $data['game_id'];
        if($data['coin_type'] == '1'){
            $ban_game_id = $data['ygame_id'];
        }
        if(!judge_user_ban_status($data['promote_id'],$ban_game_id,session('member_auth.user_id'),$data['equipment_num'],get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }
        $from_user = get_user_entity(session('member_auth.user_id'),false,'id,account,balance,pay_password');
        $user = get_user_entity($data['account'], true,'id,account,promote_id,promote_account');
        if (empty($user)) {
            $this->error('用户不存在');
        }
        if(empty($from_user['pay_password'])){
            $this->error('请前往个人中心设置支付密码');
        }
        if (!xigu_compare_password($data['pay_password'], $from_user['pay_password'])) {
            $this->error('支付密码不正确');
        }
        $data['money'] = (int)$data['money'];
        if ($data['money'] < 1) {
            $this->error('金额不能低于1元');
        }
        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($from_user['id'], $data['money']);
            if (false === $checkAgeRes) {
                $this -> error($lCheckAge -> getErrorMsg());
            }
        }

        $data['cost'] = $data['money'];
        $data['title'] = $title = "游戏充值";
        $data['game_id'] = $data['ygame_id'];
        $userplayrecord = Db::table('tab_user_play_record')
                ->field('id')
                ->where('user_id',$user['id'])
                ->where('game_id',$data['game_id'])
                ->where('server_id',$data['server_id'])
                ->find();
        if(!$userplayrecord){
            $this->error('游戏角色不存在');
        }
        $game = get_game_entity($data['game_id'],'game_name,sdk_version');
        $data['game_name'] = $game['game_name'];
        $data['sdk_version'] = $game['sdk_version'];
        $data['server_name'] = get_server_name($data['server_id']);
        $data['extra_param'] = cmf_get_domain();
        //查找余额
        $userplay = Db::table('tab_user_play') -> field('user_id,user_account,game_id,game_name,bind_balance') -> where('user_id',session('member_auth.user_id')) -> where('game_id', $data['game_id']) -> find();
        if($data['money'] > $userplay['bind_balance']){
            $this->error('绑币余额不足，支付失败');
        }
        $data['pay_amount'] = $data['money'];
        $data['pay_order_number'] = $data['order_no'];
        $data['pay_way'] = 1;
        $data['user_id'] = $user['id'];
        $data['spend_ip'] = get_client_ip();
        $data['pay_status'] = 1;
        Db::startTrans();
        try{
            if (!empty($data['other'])) {
                $data['extend'] = $data['other'];
            }
            //游戏充值
            Db::table('tab_user_play')->where('user_id',session('member_auth.user_id')) -> where('game_id', $data['game_id'])->setDec('bind_balance',$data['pay_amount']);
            add_spend($data,$user);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error('支付失败');
        }
        $logic = new GameApi();
        $param['out_trade_no'] = $data['order_no'];
        $logic->game_pay_notify($param);
        $this->success('支付成功');
    }

    /**
     * [检查充值状态]
     * @author 郭家屯[gjt]
     */
    public function checkstatus()
    {
        $order_no = $this->request->param('out_trade_no');
        $pay_where = substr($order_no, 0, 2);
        switch ($pay_where){
            case 'PF':
                $model = new SpendBalanceModel();
                $result = $model->where('pay_order_number', $order_no)->field('id,pay_status')->find();
                break;
            case 'SP':
                $model = new SpendModel();
                $result = $model->where('pay_order_number', $order_no)->field('id,pay_status')->find();
                break;
            case 'PB':
                $model = new SpendBindModel();
                $result = $model->where('pay_order_number', $order_no)->field('id,pay_status')->find();
                break;
        }
       if (empty($result)) {
            return json(['code' => 0]);
        } else {
            $result = $result->toArray();
            if ($result['pay_status'] == 1) {
                return json(['code' => 1, 'reurl' => url('Pay/payresult', ['order_no' => $order_no])]);
            } else {
                return json(['code' => 0]);
            }
        }
    }

    /**
     * [充值结果]
     * @author 郭家屯[gjt]
     */
    public function payresult()
    {
        $order_no = $this->request->param('order_no');
        $pay_where = substr($order_no, 0, 2);
        switch ($pay_where){
            case 'PF':
                $model = new SpendBalanceModel();
                $data = $model->field('user_id,pay_order_number,cost,pay_way,pay_status,pay_amount')->where('pay_order_number', $order_no)->find();
                $data['type'] = 1;
                break;
            case 'PB':
                $model = new SpendBindModel();
                $data = $model->field('user_id,pay_order_number,cost,pay_way,pay_status,pay_amount,game_name')->where('pay_order_number', $order_no)->find();
                $data['type'] = 0;
                break;
            case 'SP':
                $model = new SpendModel();
                $data = $model->field('user_id,pay_order_number,cost,pay_way,pay_status,pay_amount,game_id,game_name')->where('pay_order_number', $order_no)->find();
                $data['type'] = 2;
                break;
        }

        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @检查角色是否存在
     * @author: 郭家屯
     * @since: 2019/10/14 19:11
     */
    public function checkuserplay(){
        $account = $this->request->param('account');
        $game_id = $this->request->param('game_id');
        if(empty($account) || empty($game_id)){
            $this->error('参数错误');
        }
        $userplay = Db::table('tab_user_play')->field('id')->where('user_account',$account)->where('game_id',$game_id)->find();
        if(!$userplay){
            $this->error('游戏角色不存在');
        }
        return true;
    }

    /**
     * @函数或方法说明
     * @检查页游角色是否存在
     * @author: 郭家屯
     * @since: 2019/10/14 19:11
     */
    public function checkyyuserplay(){
        $account = $this->request->param('account');
        $game_id = $this->request->param('game_id');
        $server_id = $this->request->param('server_id');
        if(empty($account) || empty($game_id) || empty($server_id)){
            $this->error('参数错误');
        }
        $user = get_user_entity($account,true,'id');
        if(empty($user)){
            $this->error('用户不存在');
        }
        $userplay = Db::table('tab_user_play_record')
                ->field('id')
                ->where('user_id',$user['id'])
                ->where('game_id',$game_id)
                ->where('server_id',$server_id)
                ->find();
        if(!$userplay){
            $this->error('游戏角色不存在');
        }
        return json(['code' => 1, 'orderno' => create_out_trade_no('SP_')]);
    }

    /**
     * @函数或方法说明
     * @获取区服列表
     * @author: 郭家屯
     * @since: 2020/9/24 11:09
     */
    public function get_server_list()
    {
        $game_id = $this->request->param('game_id');
        $pay_type = $this->request->param('pay_type');
        $account = $this->request->param('account');
        $user = get_user_entity($account,true,'id,promote_id');
        $model = new ServerModel();
        $map['game_id'] = $game_id;
        $data = $model->getlists($map);
        if($pay_type == 'bind'){
            $discount = 10;
        }else{
            $lPay = new \app\common\logic\PayLogic();
            $discount = $lPay -> get_discount($game_id, $user['promote_id'], $user['id']);
            $discount = $discount['discount']*10;
        }
        return json(['discount'=>$discount,'data'=>$data]);
    }

    /**
     * @函数或方法说明
     * @检查是否设置支付密码
     * @author: 郭家屯
     * @since: 2020/10/15 11:30
     */
    public function check_pay_password()
    {
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,balance,pay_password');
        if(empty($user['pay_password'])){
            $this->error('未设置');
        }
        $this->success('已设置');
    }

    /**
     * 查询玩家在当前游戏中享受的折扣
     * by:byh 2021-9-1 16:08:53
     */
    public function get_account_game_bind_discount()
    {
        $game_id = $this->request->param('game_id');
        $account = $this->request->param('account');
        $user = get_user_entity($account,true,'id,promote_id');
        $lPay = new \app\common\logic\PayLogic();
        $discount = $lPay -> get_bind_discount($game_id, $user['promote_id'], $user['id']);
        $discount = $discount['discount']*10;
        return json(['discount'=>$discount]);
    }


    /**
     * @检查未成年用户是否满足充值条件
     *
     * @author: zsl
     * @since: 2021/9/22 13:45
     */
    public function checkUserAge()
    {
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $param = $this -> request -> param();
            $user = get_user_entity($param['account'], true, 'id,promote_id');
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($user['id'], $param['pay_money']);
            if (false === $checkAgeRes) {
                $this -> error($lCheckAge -> getErrorMsg());
            }
            $this -> success('success');
        }
        $this -> success('success');
    }

}
