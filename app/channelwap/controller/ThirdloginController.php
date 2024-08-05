<?php

namespace app\channelwap\controller;


use cmf\controller\HomeBaseController;
use think\Db;
use think\WechatAuth;
use app\member\model\UserModel;

class ThirdloginController extends HomeBaseController
{
    //初始验证
    protected function _initialize()
    {
        app_auth_value();
    }

    public function wechatlogin()
    {
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
            $this->redirect('Index/index');
        }
    }

    public function tiaozhuan()
    {

        $this->error('网页登录授权过时，请重新登录');

    }

}