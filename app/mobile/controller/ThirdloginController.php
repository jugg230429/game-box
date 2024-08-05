<?php

namespace app\mobile\controller;


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
        if (AUTH_PROMOTE == 1) {
            $state = $this->request->param('state');
            $serverhostarr = array($state, "http://" . $state, "https://" . $state);
            $host = Db::table('tab_promote_union')->where('domain_url', 'in', $serverhostarr)->find();
            if ($host && $state != $_SERVER['HTTP_HOST']) {
                $url = (is_https()?'https://':'http://') . $state . '/mobile/Thirdlogin/wechatlogin?' . http_build_query($_GET);
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
            $model = new UserModel();
            $user = $model->where('unionid', $userInfo['unionid'])->find();
            if ($user) {
                if($user['lock_status']!=1){
                    $this->error('用户已被锁定，无法登录',url('user/index'));
                }
                $data['type'] = 2;//PC登录
                $data['id'] = $user['id'];
                $data['account'] = $user['account'];
                $data['promote_id'] = $user['promote_id'];
                $data['openid'] = $userInfo['openid'];
                $model->auth_login($data);
                $user['user_id'] = $user['id'];
                userInfo($user);
                $this->redirect(session('redirect_url'));
            } else {
                do {
                    $data['account'] = 'wx_' . sp_random_string();
                    $account = $model->where('account', $data['account'])->find();
                } while (!empty($account));
                $data['nickname'] = $data['wx_nickname'] = $userInfo['nickname'];
                $data['head_img'] = $userInfo['headimgurl'];
                $data['unionid'] = $userInfo['unionid'];
                $data['openid'] = $userInfo['openid'];
                $data['register_way'] = 3;
                $data['register_type'] = 3;
                $data['type'] = 1;
                $data['promote_id'] = session('union_host') ? session('union_host.union_id') : ($this->request->param('pid') ?: (session('device_promote_id')?:0));
                //$data['promote_account'] = get_promote_name($data['promote_id']);
                //$data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
                //$data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
                $gid = $this->request->param('gid');
                $data['game_id'] = $gid ? $gid : (session('device_game_id')?:0);
                $data['game_name'] = $gid ? get_game_name($gid) : '';
                $data['equipment_num'] = session('device_code')?:'';
                $result = $model->register($data);
                if ($result == -1) {
                    session("wechat_token", null);
                    exit('注册失败');
                } else {
                    $result['user_id'] = $result['id'];
                    userInfo($result);
                    $this->redirect(session('redirect_url'));
                }
            }
        }
    }

    public function tiaozhuan()
    {

        $this->error('网页登录授权过时，请重新登录');

    }

}