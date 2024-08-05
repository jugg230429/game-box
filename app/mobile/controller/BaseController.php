<?php

namespace app\mobile\controller;

use app\site\model\TipModel;
use cmf\controller\HomeBaseController;
use app\site\model\AdvModel;
use think\Request;
use think\WechatAuth;
use think\Db;


class BaseController extends HomeBaseController
{
    protected static $userSession;
    const COOKIE_EXPIRE_TIME = 315360000;

    //初始验证
    protected function _initialize()
    {
        $config = cmf_get_option('admin_set');
        if ($config['web_cache'] == 0) {
            exit('站点已关闭');
        }
        app_auth_value();
        $controller = $this->request->controller();
        $action = $this->request->action();
        //PC端自动切换
        if (!cmf_is_mobile() && $controller != 'Invitation') {
            //忽略方法
            $ignore_arr = ['Downfile/index','Downfile/indexh5','Downfile/down','Downfile/download_app','Downfile/downapp', 'Downfile/qrcode', 'Downfile/show','User/sendsms','User/protocol','User/privacy','User/h5shell_imei','User/register','User/login','User/app_third_login','Downfile/protocol', 'Downfile/privacy','Downfile/get_ban_status','Downfile/get_gid','Downfile/checkiospaydownload','User/unsubscribe_protocol'];
            if (!in_array($controller . '/' . $action, $ignore_arr)) {
                $url = str_replace('mobile', 'media', $_SERVER['REQUEST_URI']);
                $headerurl = cmf_get_domain() . $url;
                return $this->redirect($headerurl);
            }
        }

        // 控制WAP站默认首先显示手游或H5========================================START
        // by wjd
        $wap_set_info = cmf_get_option('wap_set');
        $default_show_sy_h5 = isset($wap_set_info['default_show_sy_h5']) ? $wap_set_info['default_show_sy_h5'] : 0;

        if(session('choose_model') !==0 && session('choose_model')!==1){
            // 手游
            if($default_show_sy_h5 == 1){
                session('choose_model',0);
            }
            // H5
            if($default_show_sy_h5 == 2){
                session('choose_model',1);
            }
        }

        // // 不做设置
        // if($default_show_sy_h5 == 0){
        //     $model = cmf_get_option('wap_set')['model_wap']?:3;
        //     if($model == 3){
        //         define('WAP_MODEL',3);
        //         if(session('choose_model') == 1){
        //             define('MODEL',2);
        //         }else{
        //             define('MODEL',1);
        //         }
        //     }else{
        //         define('WAP_MODEL',$model);
        //         define('MODEL',$model);
        //     }
        // }
        // 控制WAP站默认首先显示手游或H5========================================END
        //确认模块
        if(PERMI == 1){
            define('MODEL',1);
            define('WAP_MODEL',1);
        }elseif(PERMI == 2){
            define('MODEL',2);
            define('WAP_MODEL',2);
        }
        else{
            $wap_set_info = cmf_get_option('wap_set');
            $default_show_sy_h5 = isset($wap_set_info['default_show_sy_h5']) ? $wap_set_info['default_show_sy_h5'] : 0;

            $model = cmf_get_option('wap_set')['model_wap']?:3;
            if($model == 3){
                define('WAP_MODEL',3);
                if(session('choose_model') == 1){
                    define('MODEL',2);
                }else{
                    define('MODEL',1);
                }
                // var_dump(session('choose_model'));exit;  //session('choose_model') =0


            }else{
                define('WAP_MODEL',$model);
                define('MODEL',$model);
            }
        }
        $serverhost = $_SERVER['HTTP_HOST'];
        $config = cmf_get_option('admin_set');
        if ($serverhost != $config['web_site']) {
            session('for_third', $config['web_site']);
        }

        if (AUTH_PROMOTE == 1) {
            $serverhostarr = [$serverhost, "http://" . $serverhost, "https://" . $serverhost];
            $union_model = new \app\promote\model\PromoteunionModel();
            $host = $union_model->where('domain_url', 'in', $serverhostarr)->find();
            if (empty($host) && $serverhost != $config['web_site']) {
                define('MOBILE_PID', 0);
              //  die('<h1>404 not found</h1>The requested URL /media was not found on this server');
            } else {
                if ($host) {
                    $host = $host->toArray();
                    if ($host['status'] == 1) {
                        session('union_host', $host);
                        $union_set = json_decode($host['union_set'], true);
                        $this->assign('union_set', $union_set);
                        define('MOBILE_PID', $host['union_id']);
                    } else {
                        die('<h1>The site is not audited</h1>');
                    }
                }else{
                    define('MOBILE_PID', 0);
                }
            }
        }
        //分享参数
         $full_url = cmf_get_domain(). $_SERVER["REQUEST_URI"];
        if(request()->controller() == "Invitation"){
            $name = cmf_get_option('app_set')['app_name'] ? : "游戏盒子";
            $share_title = "【".$name."】邀请好友送豪礼";
            $share_describe = '什么值得玩？来'.$name.'，福利多多任你玩。';
            $share_img = cmf_get_option('app_set')['app_logo'] ? cmf_get_image_url(cmf_get_option('app_set')['app_logo']) : cmf_get_domain().'/static/images/empty.jpg';
        }else{
            $share_title = cmf_get_option('wap_set')['share_title'] ? : '手游联运-发现更多好游戏';
            $share_describe = cmf_get_option('wap_set')['share_describe'] ? : '该平台是一个高质量的手游联运平台，最热门游戏都在这里。';
            $share_img = cmf_get_option('wap_set')['share_icon'] ? cmf_get_image_url(cmf_get_option('wap_set')['share_icon']) : cmf_get_domain().'/static/images/empty.jpg';
            if(session('union_host')) {
                if($union_set['share_title']){
                    $share_title = $union_set['share_title'];
                }
                if($union_set['share_describe']){
                    $share_describe = $union_set['share_describe'];
                }
                if($union_set['share_ico']){
                    $share_img = cmf_get_image_url($union_set['share_ico']);
                }
            }
            //渠道APP下载页面分享
            if(AUTH_PROMOTE == 1 && request()->action()=='download_app' && request()->param('promote_id') > 0){
                // $shost = $union_model->where('union_id', request()->param('promote_id'))->find()->toArray(); // 原
                $shost = $union_model->where('union_id', request()->param('promote_id'))->find();  // 新修改 wjd
                $share_set = json_decode($shost['union_set'], true);
                if($share_set['share_title']){
                    $share_title = $share_set['share_title'];
                }
                if($share_set['share_describe']){
                    $share_describe = $share_set['share_describe'];
                }
                if($share_set['share_ico']){
                    $share_img = cmf_get_image_url($share_set['share_ico']);
                }
            }
        }
        $shareparams['title'] = $share_title;
        $shareparams['desc'] = $share_describe;
        $shareparams['imgUrl'] = $share_img;
        $shareparams['link'] = $full_url;
        $this->assign('shareparams', $shareparams);
        $wechat_status = get_user_config('wechat');
        if (cmf_is_wechat() && $wechat_status == 1) {  //微信分享
            //微信分享参数
            $signPackage = $this->sharefun();
            $this->assign('signPackage', $signPackage);
        }
        if (AUTH_USER == 1 && cmf_is_company_wechat() == 0) {
            //登录按钮
            $thirloginstr = ["qq_login", "weixin_login"];
            $tool = Db::table('tab_user_config')->field('name,status')->where('name', 'in', $thirloginstr)->select()->toArray();
            foreach ($tool as $key => $val) {
                $this->assign($tool[$key]['name'], $val['status']);
            }

        }
        $this->assign('action_name', request()->action());
        $this->assign('controller_name', request()->controller());
        //获取seo信息
        $seo = Db::table('tab_seo')->where('name', 'like', 'wap_%')->select()->toArray();
        foreach ($seo as $key => $v) {
            $seokey[$v['name']] = ['seo_title' => $v['seo_title'], 'seo_keyword' => $v['seo_keyword'], 'seo_description' => $v['seo_description']];
        }
        $this->assign('seo', $seokey);
        if (AUTH_USER == 1) {
            //排除自动登录方法
            $array = ['wechatlogin'];
            if (!in_array($action, $array) && !session('wechat_token') && is_weixin() && !cmf_is_company_wechat() && request()->controller() != "Downfile") {
                $this->wechat_login();
            }
            //不符合状态 强制退出
            if (session('member_auth') ) {
                $userdata = get_user_entity(session('member_auth.user_id'),false,'password,lock_status,vip_level,point,receive_address');
                if(session('member_auth.password')!=$userdata['password']||$userdata['lock_status']!=1){
                    session('member_auth', null);
                    return $this->redirect('Index/index');
                }
            }
        }
        session('member_auth.point',$userdata['point']);
        session('member_auth.receive_address',$userdata['receive_address']);
        session('member_auth.vip_level',$userdata['vip_level']);
        self::$userSession = $session = array(
            'login_auth' => session('member_auth') ? 1 : 0,
            'login_user_id' => session('member_auth.user_id'),
            'login_account' => session('member_auth.account'),
            'is_union' => session('union_host') ? 1 : 0,
            'login_head_img' => cmf_get_image_url(session('member_auth.head_img')),
            'point' => $userdata['point'],
            'vip_level' => $userdata['vip_level'],
            'receive_address' => $userdata['receive_address'],
            'app_user_login'=> session('app_user_login')
        );
        $this->assign('session', $session);
        define('UID', session('member_auth.user_id') ?: 0);
        define('ACCOUNT', session('member_auth.account') ?: '');
        //注册协议
        $portalPostModel = new \app\site\model\PortalPostModel;
        $portal_info = $portalPostModel->where('id', 'in',[16,27])->order('id desc')->select();
        $portal['post_title'] = $portal_info[0]['post_title'];
        $portal['post_title_yinsi'] = $portal_info[1]['post_title'];
        //消息状态
        $model = new TipModel();
        $tip_status = $model->get_tip_status(UID);
        $this->assign('tip_status',$tip_status);
        $this->assign('portal', $portal);
    }

    /** * 第三方微信公众号登陆 * */
    public function wechat_login($state = 0)
    {
        if (!session("wechat_token") && is_weixin() && get_user_config('wechat') == 1) {
            //手动点击微信按钮 跳回用户中心  解决无限登录问题
            if(request()->action()!='thirdlogin'){
                session('redirect_url', (is_https()?'https://':'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            }else{
                session('redirect_url', url('user/index'));
            }
            $config = get_user_config_info('wechat');
            $appid = $config['appid'];
            $appsecret = $config['appsecret'];
            $auth = new WechatAuth($appid, $appsecret);
            $gid = $this->request->param('gid', 0, 'intval');
            $pid = $this->request->param('pid', 0, 'intval');
            $admin_config = cmf_get_option('admin_set');
            if (session('for_third') == $admin_config['web_site']) {
                $state = $_SERVER['HTTP_HOST'];
                $redirect_uri = (is_https()?'https://':'http://') . session('for_third') . "/mobile/Thirdlogin/wechatlogin/gid/" . $gid . "/pid/" . $pid;
            } else {
                $redirect_uri = (is_https()?'https://':'http://') . $_SERVER['HTTP_HOST'] . "/mobile/Thirdlogin/wechatlogin/gid/" . $gid . "/pid/" . $pid;
            }
            $this->redirect($auth->getRequestCodeURL($redirect_uri, $state));
        }
    }

    /**
     * [轮播图]
     * @param int $pos_id
     * @author 郭家屯[gjt]
     */
    public function slide($name = '')
    {
        $model = new AdvModel();
        $list = $model->getAdv($name,MOBILE_PID);
        //确认模块
        if(PERMI == 3){
            $model = cmf_get_option('wap_set')['model_wap']?:3;
            if($model == 1){
                foreach ($list as $key=>$v){
                    if(!in_array($v['sdk_version'],[1,2]) && $v['type'] == 1){
                        unset($list[$key]);
                    }
                }
            }elseif($model == 2){
                foreach ($list as $key=>$v){
                    if($v['sdk_version']!=3 && $v['type'] == 1){
                        unset($list[$key]);
                    }
                }
            }
        }
        $this->assign("carousel", $list);
    }

    /*
        *平台币充值记录
        */
    protected function add_deposit($data)
    {
        $deposit = new \app\recharge\model\SpendBalanceModel();
        $deposit_data = $this->deposit_param($data);
        $result = $deposit->field(true)->insert($deposit_data);
        return $result;
    }


    /**
     *平台币充值记录表 参数
     */
    private function deposit_param($param = array())
    {
        $user_entity = get_user_entity($param['user_id']);
        $data_deposit['order_number'] = $param["order_number"] ? $param["order_number"] : '';
        $data_deposit['pay_order_number'] = $param["pay_order_number"];
        $data_deposit['user_id'] = $param["user_id"];
        $data_deposit['promote_id'] = $user_entity["promote_id"];
        $data_deposit['pay_amount'] = $param["price"];
        $data_deposit['cost'] = $param["price"];
        $data_deposit['pay_status'] = $param["pay_status"];
        $data_deposit['pay_way'] = $param["pay_way"];
        $data_deposit['pay_ip'] = $param["spend_ip"];
        $data_deposit['pay_time'] = time();
        return $data_deposit;
    }


    /**
     * 空操作
     *
     * @return mixed
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\3\30 0030 16:04
     */
    public function _empty()
    {

        $url = request()->scheme() . '://' . request()->host() . '/' . request()->pathinfo();

        $this->assign('url', $url);
        return $this->fetch('index/404');

    }

    /**
     *[微信分享]
     * @return mixed
     * @author chen
     */
    private function sharefun()
    {
        $result = auto_get_access_token('access_token_validity.txt');
        $user_config = get_user_config_info('wechat');
        $appid = $user_config['appid'];
        $appsecret = $user_config['appsecret'];
        if (!$result['is_validity']) {
            $auth = new WechatAuth($appid, $appsecret);
            $token = $auth->getAccessToken();
            $token['expires_in_validity'] = time() + $token['expires_in'];
            wite_text(json_encode($token), 'access_token_validity.txt');
            $result = auto_get_access_token('access_token_validity.txt');
        }
        $auth = new WechatAuth($appid, $appsecret, $result['access_token']);
        $ticket = auto_get_ticket(dirname(__FILE__) . '/ticket.txt');
        if (!$ticket['is_validity']) {
            $jsapiTicketjson = $auth->getticket();
            $jsapiTicketarr = json_decode($jsapiTicketjson, true);

            $jsapiTicketarr['expires_in_validity'] = time() + $jsapiTicketarr['expires_in'];
            wite_text(json_encode($jsapiTicketarr), dirname(__FILE__) . '/ticket.txt');
            $ticket = auto_get_ticket(dirname(__FILE__) . '/ticket.txt');
        }

        $signPackage = $auth->getSignPackage($ticket['ticket']);

        return $signPackage;

    }

    /**
     * 获取用户登录状态
     * yyh
     */
    public function get_user_status()
    {
        $userSession = self::$userSession;
        $userSession['menu_controller_name'] = strtolower(request()->controller());
        $cookie_account = cookie('cookie_account');
        if (empty($cookie_account)) {
            $cookie_account = json_encode([0 => ['account' => '', 'password' => '']]);
        }
        $userSession['cookie_account'] = $cookie_account;
        if (empty($userSession['login_user_id'])) {
            return json(['status' => 0, 'data' => $userSession]);
        } else {
            return json(['status' => 1, 'data' => $userSession]);
        }
    }

    /**
     * 登录检测
     *
     * @return mixed
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\8\23 0023 10:07
     */
    public function isLogin() {
        if (!session('member_auth')) {
            if($this->request->isPost()) {
                $this->error('你已退出登录，不可操作！', 'Index/index');
            } else {
                return $this->redirect('user/index');
            }
        }
    }

    /**
     * 游戏权限检测
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\8\23 0023 10:07
     */
    public function isGameAuth() {
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
    }

    /**
     * 支付权限检测
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\8\23 0023 10:08
     */
    public function isPayAuth() {
        if (AUTH_PAY != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买充值权限');
            } else {
                $this->error('请购买充值权限', url('admin/main/index'));
            };
        }
    }

    /**
     * @函数或方法说明
     * @切换手游H5模块
     * @author: 郭家屯
     * @since: 2020/6/16 19:23
     */
    public function changemodel()
    {
        $model = $this->request->param('model');
        if($model == 'h5'){
            session('choose_model',1);
        }else{
            session('choose_model',0);
        }
        return json(['status'=>1]);
    }

}
