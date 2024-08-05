<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 16:43
 */

namespace app\media\controller;

use app\recharge\logic\CheckAgeLogic;
use think\Db;
use think\WechatAuth;
class WappayController extends BaseController
{
    /**
     * [支付]
     * @author 郭家屯[gjt]
     */
    public function pay()
    {
        $data = $this->request->param();
        if(!$data['user_id'] && $data['uid']){
            $data['user_id'] = $data['uid'];
        }
        if(!$data['game_id'] && $data['gid']){
            $data['game_id'] = $data['gid'];
        }
        if(!$data['server_id'] && $data['sid']){
            $data['server_id'] = $data['sid'];
        }
        if(!$data['game_id'] || !$data['server_id'] || !$data['user_id']){
            $this->assign('error_msg','参数出错，请联系客服');
        }
        if(!cmf_is_wechat() && !cmf_is_alipay()){
            $this->assign('error_msg','请使用支付宝或微信扫码付款');
        }
        if (!session('wechat_token') && is_weixin() && !cmf_is_company_wechat()) {
            $this->wechat_login();
        }
        $user = get_user_entity($data['user_id'],false,'id,account,promote_id');
        if(empty($user)){
            $this->assign('error_msg','用户不存在，请刷新游戏后扫码');
        }
        $this->assign('user',$user);
        $game = get_game_entity($data['game_id'],'id,game_name,currency_name,currency_ratio');
        if(empty($game)){
            $this->assign('error_msg','游戏不存在，请刷新游戏后扫码');
        }
        $this->assign('game',$game);
        $server_name = get_server_name($data['server_id']);
        if(empty($server_name)){
            $this->assign('error_msg','区服不存在，请刷新游戏后扫码');
        }
        $data['server_name'] = $server_name;
        //折扣信息
        $lPay = new \app\common\logic\PayLogic();
        $discount_info = $lPay -> get_discount($data['game_id'], $user['promote_id'], $user['id']);
        $data['discount'] = $discount_info['discount'];
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
        $user = get_user_entity($data['user_id'], false,'id,account,nickname,promote_id,promote_account');
        if (empty($user)) {
            $this->error('用户不存在');
        }
        $data['money'] = (int)$data['money'];
        if ($data['money'] < 1) {
            $this->error('金额不能低于1元');
        }
        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $data['pay_way'] = 3;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($user['id'], $data['money']);
            if (false === $checkAgeRes) {
                $this -> error($lCheckAge -> getErrorMsg());
            }
        }
        $data['cost'] = $data['money'];
        $body = "游戏充值";
        $title = "游戏币";
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
        $data['order_no'] = create_out_trade_no('SP_');
        $table = 'spend';
        $pay = new \think\Pay($data['pay_type'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody($body)
            ->setFee($data['money'])//支付金额
            ->setTitle($title)
            ->setOrderNo($data['order_no'])
            ->setService($data['service'])
            ->setSignType("MD5")
            ->setPayMethod("wap")
            ->setTable($table)
            ->setPayWay($data['pay_way'])
            ->setUserId($user['id'])
            ->setAccount($user['account'])
            ->setUserNickName($user['nickname'])
            ->setPromoteId($user['promote_id'])
            ->setPromoteName($user['promote_account'])
            ->setGameId($data['game_id'])
            ->setGameName($data['game_name'])
            ->setExtend($data['extend'])
            ->setServerId($data['server_id'])
            ->setServerName($data['server_name'])
            ->setDiscount($data['discount'])
            ->setSdkVersion($data['sdk_version'])
            ->setDiscount_type($data['discount_type'])
            ->setCost($data['cost']);
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
                $redirect_uri = (is_https()?'https://':'http://') . session('for_third') . "/media/Wappay/wechatlogin";
            } else {
                $redirect_uri = cmf_get_domain() . "/media/Wappay/wechatlogin";
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
                $url = (is_https()?'https://':'http://') . $state . '/media/Wappay/wechatlogin?' . http_build_query($_GET);
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


}
