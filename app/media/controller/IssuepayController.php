<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 16:43
 */

namespace app\media\controller;

use app\issue\model\SpendModel;
use app\issue\model\UserModel;
use think\Db;
use think\WechatAuth;
use app\issue\model\PlatformModel;
use app\issue\model\OpenUserModel;
use app\issue\model\IssueGameModel;
class IssuepayController extends BaseController
{
    /**
     * [支付]
     * @author 郭家屯[gjt]
     */
    public function pay()
    {
        $data = $this->request->param();
        //平台状态
        $mPlatform = new  PlatformModel();
        $platformData = $mPlatform->field('id,open_user_id,platform_config_yy,service_config,controller_name_yy,order_notice_url_yy,pay_notice_url_yy,status')->where('id','=',$data['channel_code'])->find();
        if($platformData['status']!=1){
            $this->assign('error_msg','分发平台状态已关闭');
        }
        if(empty($platformData['order_notice_url_yy']) || empty($platformData['pay_notice_url_yy'])){
            $this->assign('error_msg','平台配置支付地址设置错误');
        }
        //用户状态
        $mOpenUser = new OpenUserModel();
        $openUserData = $mOpenUser -> field('id,account,auth_status,status,settle_type') -> where('id', '=', $platformData['open_user_id']) -> find();
        if ($openUserData['status'] != 1 || $openUserData['auth_status'] != 1) {
            $this->assign('error_msg','平台用户已锁定');
        }

        if(!$data['game_id'] ){
            $this->assign('error_msg','游戏不能为空，请联系客服');
        }
        if(!$data['server_id']){
            $this->assign('error_msg','区服不能为空，请联系客服');
        }
        if(!$data['user_id']){
            $this->assign('error_msg','用户不能为空，请联系客服');
        }
        if(!cmf_is_wechat() && !cmf_is_alipay()){
            $this->assign('error_msg','请使用支付宝或微信扫码付款');
        }
        if (!session('wechat_token') && is_weixin() && !cmf_is_company_wechat()) {
            $this->wechat_login();
        }
        $user = get_table_entity(new UserModel(),['id'=>$data['user_id']],'id,account');
        if(empty($user)){
            $this->assign('error_msg','用户不存在，请刷新游戏后扫码');
        }
        //游戏状态
        $mGame = new IssueGameModel();
        $mGameData = $mGame->field('id,game_name,status,pay_notify_url,game_key,currency_name,currency_ratio')->where('id','=',$data['game_id'])->find();
        if(empty($mGameData)){
            $this->assign('error_msg','游戏不存在，请刷新游戏后扫码');
        }
        if($mGameData['status']!=1){
            $this->assign('error_msg','游戏已下架');
        }
        $this->assign('game',$mGameData);
        $server_name = get_server_name($data['server_id']);
        if(empty($server_name)){
            $this->assign('error_msg','区服不存在，请刷新游戏后扫码');
        }
        $data['server_name'] = $server_name;
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * [支付宝支付]
     * @param $data
     * @author 郭家屯[gjt]
     */
    public function alipay()
    {
        $data = $this->request->param();
        //平台状态
        $mPlatform = new  PlatformModel();
        $platformData = $mPlatform->field('id,account,open_user_id,platform_config_yy,service_config,controller_name_yy,order_notice_url_yy,pay_notice_url_yy,status')->where('id','=',$data['channel_code'])->find();
        $data['platform_account'] = $platformData['account'];
        if($platformData['status']!=1){
            $this->error('分发平台状态已关闭');
        }
        if(empty($platformData['order_notice_url_yy']) || empty($platformData['pay_notice_url_yy'])){
            $this->error('平台配置支付地址设置错误');
        }
        //用户状态
        $mOpenUser = new OpenUserModel();
        $openUserData = $mOpenUser -> field('id,account,auth_status,status,settle_type') -> where('id', '=', $platformData['open_user_id']) -> find();
        if ($openUserData['status'] != 1 || $openUserData['auth_status'] != 1) {
            $this->error('平台用户已锁定');
        }
        $usermodel = new UserModel();
        $userData = $usermodel
                ->field('user_id,lock_status,account,tab_issue_user.platform_id,openid as platform_openid')
                ->where('tab_issue_user.id','=',$data['user_id'])
                ->join(['tab_issue_user_play'=>'player'],'player.user_id = tab_issue_user.id')
                ->find();
        $data['platform_openid'] = $userData['platform_openid'];
        $data['account'] = $userData['account'];
        if(empty($userData)){
            $this->error('用户不存在，请刷新游戏后扫码');
        }
        $data['money'] = (float)$data['money'];
        if ($data['money'] < 0.01) {
            $this->error('金额不能低于0.01元');
        }
        $data['pay_way'] = 3;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        $data['cost'] = $data['money'];
        $body = "游戏充值";
        $title = "游戏币";
        //获取游戏信息
        $mGame = new IssueGameModel();
        $mGame->field('tab_issue_game.id as game_id,tab_issue_game.sdk_version,game_appid,tab_issue_game.game_name,tab_issue_game.status,enable_status,cp_game_id,interface_id,platform_config,platform_id,open_user_id,login_notify_url,pay_notify_url,apply.ratio');
        $mGame->where('tab_issue_game.id','=',$data['game_id']);
        $mGame->where('platform_id','=',$data['channel_code']);
        $mGame->join(['tab_issue_game_apply'=>'apply'],'apply.game_id=tab_issue_game.id');
        $mGameData = $mGame->find();
        if(empty($mGameData)){
            $this->error('游戏不存在，请刷新游戏后扫码');
        }
        if($mGameData['status']!=1){
            $this->error('游戏已下架');
        }
        $data['game_name'] = $mGameData['game_name'];
        $data['server_name'] = get_server_name($data['server_id']);
        $spend_order = $this->create_order($data,$mGameData);
        if(!$spend_order){
            $this->error('创建订单失败');
        }
        $spendmodel = new SpendModel();
        $spendDate = $spendmodel::get($spend_order);
        $game_config = json_decode($mGameData['platform_config'],true);
        $url = $platformData['order_notice_url_yy'];
        $param['trade_no'] = $spendDate['pay_order_number'];
        $param['gid'] = $data['game_id'];
        $param['uid'] = $data['platform_openid'];
        $param['sid'] = $data['server_id'];
        $param['amount'] = $data['money'];
        $param['game_appid'] = $game_config['game_appid'];
        $param['time'] = time();
        $controller_name = $platformData['controller_name_yy'];
        $class = '\app\\issueyy\\logic\\pt\\'.$controller_name.'Logic';
        $logic = new $class();
        $pay_key = $logic->get_pay_key($game_config);
        $param['sign'] = md5($param['trade_no'].$param['gid'].$param['uid'].$param['sid'].$param['amount'].$param['game_appid'].$param['time'].$pay_key );
        $result = $this->post($param,$url);
        if($result != 'success'){
            $this->error('创建订单失败');
        }
        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $table = 'issue_spend';
        $pay = new \think\Pay($data['pay_type'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody($body)
            ->setFee($data['money'])//支付金额
            ->setTitle($title)
            ->setOrderNo($spendDate['pay_order_number'])
            ->setService($data['service'])
            ->setSignType("MD5")
            ->setPayMethod("wap")
            ->setTable($table);
        return redirect($pay->buildRequestForm($vo));
    }

    /** * 第三方微信公众号登陆 * */
    public function wechat_login($state = 0)
    {
        if (!session("wechat_token") && is_weixin() && get_user_config('wechat') == 1) {
            //手动点击微信按钮 跳回用户中心  解决无限登录问题
            if(request()->action()!='thirdlogin'){
                session('redirect_url', cmf_get_domain() . $_SERVER['REQUEST_URI']);
            }else{
                session('redirect_url',(is_https()?'https://':'http://') . cmf_get_option('admin_set')['web_site'] . $_SERVER['REQUEST_URI']);
            }
            $config = get_user_config_info('wechat');
            $appid = $config['appid'];
            $appsecret = $config['appsecret'];
            $auth = new WechatAuth($appid, $appsecret);
            $admin_config = cmf_get_option('admin_set');
            if (session('for_third') == $admin_config['web_site']) {
                $state = $_SERVER['HTTP_HOST'];
                $redirect_uri = (is_https()?'https://':'http://') . session('for_third') . "/media/Issuepay/wechatlogin";
            } else {
                $redirect_uri = cmf_get_domain() . "/media/Issuepay/wechatlogin";
            }
            $this->redirect($auth->getRequestCodeURL($redirect_uri, $state));
        }
    }

    public function wechatlogin()
    {
        if (AUTH_PROMOTE == 1) {
            $state = $this->request->param('state');
            $serverhostarr = array($state, "http://" . $state, "https://" . $state);
            $host = Db::table('tab_promote_union')->where('domain_url', 'in', $serverhostarr)->find();
            if ($host && $state != $_SERVER['HTTP_HOST']) {
                $url = (is_https()?'https://':'http://') . $state . '/media/Issuepay/wechatlogin?' . http_build_query($_GET);
                $this->redirect($url);
            }
        }
        if (is_weixin()) {
            $config = get_user_config_info('wechat');
            $appid = $config['appid'];
            $appsecret = $config['appsecret'];
        } else {
            $userconfig = new \app\member\model\UserConfigModel();
            $config = $userconfig->getSet('weixin_login');
            $appid = $config['config']['appid'];
            $appsecret = $config['config']['appsecret'];
        }
        $auth = new WechatAuth($appid, $appsecret);
        $token = $auth->getAccessToken("code", $_GET['code']);
        if (isset($_GET['auto_get_openid'])) {
            if (base64_decode($_GET['auto_get_openid']) != 'auto_get_openid') {
                die('非法操作！');
            }
        } else {
            $userInfo = $auth->getUserInfo($token['openid']);
            if (empty($userInfo['unionid']) || !isset($userInfo['unionid'])) {
                $this->error('前往微信开放平台（open.weixin.qq.com）绑定公众号后，才可利用UnionID机制');
                exit;
            }
            session('wechat_token', array('openid' => $token['openid']));
            session('openid', $token['openid']);
            $this->redirect(session('redirect_url'));
        }
    }

    public function create_order($orderData,$gameData)
    {
        $mSpend = new SpendModel();
        $mSpend->user_id = $orderData['user_id'];
        $mSpend->platform_openid = $orderData['platform_openid'];
        $mSpend->user_account = $orderData['account'];
        $mSpend->game_id = $gameData['game_id'];
        $mSpend->game_name = $gameData['game_name'];
        $mSpend->server_id = $orderData['server_id'];
        $mSpend->server_name = $orderData['server_name']?:'';
        $mSpend->game_player_id = $orderData['role_id']?:'';
        $mSpend->game_player_name = $orderData['role_name']?:'';
        $mSpend->role_level = $orderData['role_level']?:'';
        $mSpend->platform_id = $gameData['platform_id'];
        $mSpend->platform_account = $orderData['platform_account'];
        $mSpend->open_user_id = $gameData['open_user_id'];
        $mSpend->pay_order_number = create_out_trade_no('FF_');
        $mSpend->props_name = $orderData['props_name']?:'充值';
        $mSpend->pay_amount = $orderData['money'];
        $mSpend->pay_time = time();
        $mSpend->extra_param = $orderData['channelExt']?:'';
        $mSpend->extend = $orderData['order']?:'';
        $mSpend->spend_ip = request()->ip();
        $mSpend->sdk_version = $gameData['sdk_version'];
        $mSpend->save();
        return $mSpend->getLastInsID();
    }

    /**
     *post提交数据
     */
    protected function post($param, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}