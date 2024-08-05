<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/13
 * Time: 16:23
 */

namespace app\media\controller;

use app\common\logic\CaptionLogic;
use app\common\logic\GameLogic;
use app\common\logic\PayLogic;
use app\common\logic\PointLogic;
use app\common\logic\PointTypeLogic;
use app\member\model\PointRecordModel;
use app\member\model\PointUseModel;
use app\member\model\UserPlayModel;
use app\recharge\model\CouponRecordModel;
use app\recharge\model\SpendModel;
use app\recharge\model\SpendBalanceModel;
use app\game\model\GiftRecordModel;
use app\member\model\UserModel;
use app\member\validate\UserValidate;
use app\member\validate\UserValidate1;
use app\member\validate\UserValidate2;
use app\extend\model\MsgModel;
use app\game\model\GameModel;
use app\member\model\UserConfigModel;
use app\common\controller\BaseHomeController;
use app\member\model\UserBehaviorModel;
use app\recharge\model\SpendBindModel;
use app\recharge\model\SpendRebateRecordModel;
use app\site\model\TipModel;
use think\Validate;
use think\xigusdk\Xigu;
use think\WechatAuth;
use think\thinksdk\ThinkOauth;
use think\Checkidcard;
use geetest\lib\GeetestLib;
use think\Db;
use think\Request;
use think\captcha\Captcha;

class UserController extends BaseController
{
    public $pc_param = [];

    public function __construct()
    {
        parent::__construct();
        if (AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('index/index'));
            };
        }
        $array = ['logout', 'userinfo', 'realauth', 'safe', 'password', 'mygame', 'mygift', 'myoverduegift', 'delgift', 'myorder', 'pay_record','bind_lists','my_collect','coupon','getcoupon','rebate','phone','phone_check','email','integral','integral_pay'];
        $action = request()->action();
        if (in_array($action, $array)) {
            if (!session('member_auth')) {
                return $this->redirect('Index/index');
            }
        }
        $array1 = ['mygame', 'mygift', 'myoverduegift', 'delgift'];
        if (in_array($action, $array1)) {
            if (AUTH_GAME != 1) {
                if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                    $this->error('请购买游戏权限');
                } else {
                    $this->error('请购买游戏权限', url('admin/main/index'));
                };
            }
        }
        $array2 = ['myorder'];
        if (in_array($action, $array2)) {
            if (AUTH_PAY != 1 or AUTH_GAME != 1 or AUTH_USER != 1) {
                if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                    $this->error('请购买充值权限,游戏权限和用户权限');
                } else {
                    $this->error('请购买充值权限,游戏权限和用户权限', url('admin/main/index'));
                };
            }
        }
        $this->pc_param = [
            "user_id" => "test", # 这个是用户的标识，或者说是给极验服务器区分的标识，如果你项目没有预先设置，可以先这样设置：
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
        ];

        // 这里面对应的所有页面都需要VIP等级
        // $vip_level = '';
        // $this->assign('vip_level', $vip_level);
    }

    /**
     * @函数或方法说明
     * @积分收入明细
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/8/17 20:07
     */
    public function integral()
    {
        $model = new PointRecordModel();
        $base = new BaseHomeController();
        $map['user_id'] = session('member_auth.user_id');
        $extend['order'] = 'id desc';
        $extend['row'] = 10;
        $extend['field'] = 'point,type_name,create_time';
        $list = $base->data_list($model, $map, $extend);
        //总积分
        $total = $model->where($map)->sum('point');
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @积分消费明细
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/8/17 20:07
     */
    public function integral_pay()
    {
        $model = new PointUseModel();
        $base = new BaseHomeController();
        $map['user_id'] = session('member_auth.user_id');
        $extend['order'] = 'id desc';
        $extend['row'] = 10;
        $extend['field'] = 'point,type_name,create_time';
        $list = $base->data_list($model, $map, $extend);
        //总积分
        $total = $model->where($map)->sum('point');
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }
    /**
     * [登录]
     * @author 郭家屯[gjt]
     */
    public function login()
    {
        $data['account'] = $this->request->param('account/s');
        if (empty($data['account'])) $this->error('请输入用户名');
        $data['password'] = $this->request->param('password/s');
        if (empty($data['password'])) $this->error('密码不能为空');
        $model = new UserModel();
        $data['type'] = 2;//PC登录
        $result = $model->login($data);
        $errorurl = request()->isAjax()?'':url('index/index');
        switch ($result) {
            case 1005:
                $this->error('账号不存在或被禁用',$errorurl);
                break;
            case 1006:
                $this->error('密码错误',$errorurl);
                break;
            case 1007:
                $this->error('账号不存在或被禁用',$errorurl);
                break;
            default:
                $zdlogin = $this->request->param('zdlogin');
                //保存账号密码
                if ($zdlogin) {
                    cookie('login_account', $data['account'], 86400 * 7);
                    cookie('login_password', simple_encode($data['password']), 86400 * 7);
                } else {
                    cookie('login_account', null);
                    cookie('login_password', null);
                }
                cookie('last_login_account', $data['account'], 86400 * 7);
                $result['user_id'] = $result['id'];
                session('member_auth', $result);
                break;
        }
        $this->success('登录成功');
    }

    /**
     * [第三方登录]
     * @param null $type
     * @author 郭家屯[gjt]
     */
    public function thirdlogin()
    {

        if (cmf_get_option('media_set')['pc_user_allow_register'] == 0) {
            $this->error('用户注册入口已关闭');
        }
        $type = $this->request->param('type');
        if (!in_array($type, ['qq', 'weixin'])) {
            $this->error('未知登录类型');
        }
        $header = Request::instance()->header();
        $url = $this->request->param('url');
        if (empty($url)) {
            $url = $header['referer'] ? : cmf_get_domain() . url('Index/index');
        } else {
            $url = base64_decode($url);
        }
        session('redirect_url', $url);
        if ($type == "weixin") {
            $this->wechat_qrcode_login();
        } else {
            //加载ThinkOauth类并实例化一个对象
            $sns = ThinkOauth::getInstance($type);
            if (!empty($sns)) {
                //跳转到授权页面
                $gid = $this->request->param('gid', 0, 'intval');
                if(MEDIA_PID >0 ){
                    $pid = MEDIA_PID;
                }else{
                    $pid = $this->request->param('pid', 0, 'intval');
                }
                $this->redirect($sns->getRequestCodeURL($gid, $pid));
            }

        }
    }

    /**
     * [第三方微信扫码登录]
     * @author 郭家屯[gjt]
     */
    public function wechat_qrcode_login()
    {
        if (!session("user_auth.user_id") && !is_weixin()) {
            $userconfig = new UserConfigModel();
            $config = $userconfig->getSet('weixin_login');
            $appid = $config['config']['appid'];
            $appsecret = $config['config']['appsecret'];
            $auth = new WechatAuth($appid, $appsecret);
            $gid = $this->request->param('gid', 0, 'intval');
            $pid = $this->request->param('pid', 0, 'intval');
            $is_bind = $this->request->param('is_bind',0,'intval');
            $admin_config = cmf_get_option('admin_set');
            if (session('for_third') == $admin_config['web_site']) {
                $state = $_SERVER['HTTP_HOST'];
                $redirect_uri = (is_https()?'https://':'http://') . session('for_third') . "/media/User/wechat_login/gid/" . $gid . "/pid/" . $pid . "/is_bind" . $is_bind;
            } else {
                $redirect_uri = (is_https()?'https://':'http://') . $_SERVER['HTTP_HOST'] . "/media/User/wechat_login/gid/" . $gid . "/pid/" . $pid . "/is_bind" . $is_bind;
            }
            $this->redirect($auth->getQrconnectURL($redirect_uri, $state));
        }
    }

    /**
     * [微信登录]
     * @author 郭家屯[gjt]
     */
    public function wechat_login()
    {
        if (AUTH_PROMOTE == 1) {
            $state = $this->request->param('state');
            $serverhostarr = array($state, "http://" . $state, "https://" . $state);
            $host = Db::table('tab_promote_union')->where('domain_url', 'in', $serverhostarr)->find();
            if ($host && $state != $_SERVER['HTTP_HOST']) {
                $url = (is_https()?'https://':'http://') . $state . '/media/User/wechat_login?' . http_build_query($_GET);
                $this->redirect($url);
            }
        }
        $pid = $this->request->param('pid');
        $gid = $this->request->param('gid');
        $model = new UserModel();
        $userconfig = new UserConfigModel();
        $config = $userconfig->getSet('weixin_login');
        $appid = $config['config']['appid'];
        $appsecret = $config['config']['appsecret'];
        $auth = new WechatAuth($appid, $appsecret);
        $token = $auth->getAccessToken("code", $_GET['code']);
        $userInfo = $auth->getUserInfo($token['openid']);
        if (empty($userInfo['unionid']) || !isset($userInfo['unionid'])) {
            $this->error('前往微信开放平台（open.weixin.qq.com）绑定公众号后，才可利用UnionID机制');
            exit;
        }
        $user = $model->where('unionid', $userInfo['unionid'])->find();
        if ($user) {
            if($user['lock_status']!=1){
                $this->error('用户已被锁定，无法登录',url('index/index'));
            }
            $data['type'] = 2;//PC登录
            $data['id'] = $user['id'];
            $data['account'] = $user['account'];
            $data['promote_id'] = $user['promote_id'];
            $model->auth_login($data);
            $user['user_id'] = $user['id'];
            session('member_auth', $user);
            return $this->redirect(session('redirect_url'));
        } else {
            do {
                $data['account'] = 'wx_' . sp_random_string();
                $account = $model->where('account', $data['account'])->find();
            } while (!empty($account));
            $data['nickname'] = $userInfo['nickname'];
            $data['head_img'] = $userInfo['headimgurl'];
            $data['unionid'] = $userInfo['unionid'];
            $data['register_way'] = 3;
            $data['register_type'] = 3;
            $data['type'] = 1;
            $data['promote_id'] = session('union_host') ? session('union_host.union_id') : ($pid ? $pid : 0);
            //$data['promote_account'] = get_promote_name($data['promote_id']);
            //$data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
            //$data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
            $data['game_id'] = $gid ? $gid : 0;
            $data['game_name'] = $gid ? get_game_name($gid) : '';
            $model = new UserModel();
            $result = $model->register($data);
            if ($result == -1) {
                return $this->redirect(session('redirect_url'));
            } else {
                $result['user_id'] = $result['id'];
                session('member_auth', $result);
                return $this->redirect(session('redirect_url'));
            }
        }
    }

    /** * 回调函数 */
    public function callback()
    {
        $sta = $this->request->param('sta');
        $type = $this->request->param('type');
        $code = $this->request->param('code');
        $pid = $this->request->param('pid');
        $gid = $this->request->param('gid');
        if ($sta != '' && $type != 'baidu') {
            $re_uri = (is_https()?'https://':'http://') . $sta . url('callback', array('type' => $type, 'code' => $code, 'pid' => $pid, 'gid' => $gid));
            $this->redirect($re_uri);
        }
        if (empty($type) || empty($code)) {
            $this->error('参数错误');
        }
        //加载ThinkOauth类并实例化一个对象
        $sns = ThinkOauth::getInstance($type);
        $token = $sns->getAccessToken($code, $extend);
        //获取当前登录用户信息
        if (is_array($token)) {
            $controller = new LoginTypeController();
            $user_info = $controller->qq($token);
            //官方openid
            //$user_info['openid'] = $sns->openid();
            $data['head_img'] = $user_info['headpic'];
            if (empty($user_info['openid'])) {
                $this->error('腾讯公司应用未打通 未将所有appid设置统一unionID 请到http://wiki.connect.qq.com/%E7%89%B9%E6%AE%8A%E9%97%AE%E9%A2%98-top10参考第一天');
                exit;
            }

            $model = new UserModel();
            $user = $model->where('unionid', $user_info['openid'])->find();
            if ($user) {
                if($user['lock_status']!=1){
                    $this->error('用户已被锁定，无法登录',url('index/index'));
                }
                $data['type'] = 2;//PC登录
                $data['id'] = $user['id'];
                $data['account'] = $user['account'];
                $data['promote_id'] = $user['promote_id'];
                $model->auth_login($data);
                $user['user_id'] = $user['id'];
                session('member_auth', $user);
                $this->redirect(session('redirect_url'));
            } else {
                $prefix = "qq_";
                do {
                    $data['account'] = $prefix . sp_random_string();
                    $account = $model->where('account', $data['account'])->find();
                } while (!empty($account));
                $data['nickname'] = $user_info['nick'] ? $user_info['nick'] : $user_info['nickname'];
                $data['unionid'] = $user_info['openid'];
                $data['register_type'] = 4;
                $data['register_way'] = 3;
                $data['type'] = 1;
                $data['promote_id'] = session('union_host') ? session('union_host.union_id') : ($pid ? $pid : 0);
                //$data['promote_account'] = get_promote_name($data['promote_id']);
                //$data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
               // $data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
                $data['game_id'] = $gid ? $gid : 0;
                $data['game_name'] = $gid ? get_game_name($gid) : '';
                $model = new UserModel();
                $result = $model->register($data);
                if ($result == -1) {
                    $this->redirect(session('redirect_url'));
                } else {
                    $result['user_id'] = $result['id'];
                    session('member_auth', $result);
                    $this->redirect(session('redirect_url'));
                }
            }

        }
    }

    /**
     * [用户退出]
     * @author 郭家屯[gjt]
     */
    public function logout()
    {
        session('member_auth', null);
        cookie('last_login_account', null);
        $this->success('退出成功');
    }

    /**
     * [用户注册]
     * @author 郭家屯[gjt]
     */
    public function register()
    {
        if($this->request->param('gid')>0){
            //封禁判断-20210712-byh
            if(!judge_user_ban_status($this->request->param('pid'),$this->request->param('gid'),'',$this->request->param('equipment_num'),get_client_ip(),$type=2)){
                $this->error('您当前被禁止注册，请联系客服');
            }
        }
        if (cmf_get_option('media_set')['pc_user_allow_register'] == 0) {
            $this->error('用户注册入口已关闭');
        }
        //验证注册ip
        if(!checkregiserip()){
            $this->error('暂时无法注册，请联系客服');
        }
        $data = $this->request->param();
        //验证
        if (empty($data['account']) && $data['register_type'] == 2) {
            $this->error('请输入11位手机号码');
        }
        if (empty($data['account']) && $data['register_type'] == 1) {
            $this->error('账号为6-15位字母或数字组合');
        }
        if (empty($data['account']) && $data['register_type'] == 3) {
            $this->error('账号为6-15位字母或数字组合');
        }
        if($data['register_type'] == 3) {
            $validate = new UserValidate2();
        }else{
            $validate = new UserValidate();
        }
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if(get_user_config_info('age')['real_register_status'] > 0 ){
            $data = $this->check_auth($data);
        }
        //图形验证
        $verify_tag = $data['verify_tag'];
        $verify_token = $data['verify_token'];
        if ($data['register_type'] == 2) {
            $res = (new CaptionLogic()) -> checkToken($verify_token, $verify_tag,0);
            if ($res['code']!=200) {
                $this->error($res['info']);
            }
            $this->sms_verify($data['account'], $data['code']);
            $data['phone'] = $data['account'];
        }elseif($data['register_type'] == 3){
            $this->verify_email($data['account'], $data['code']);
            $data['register_type'] = 7;
            $data['email'] = $data['account'];
        } else {

            $res = (new CaptionLogic()) -> checkToken($verify_token, $verify_tag,0);
            if ($res['code']!=200) {
                $this->error($res['info']);
            }
        }
        (new CaptionLogic()) -> clearToken($verify_tag);
        $pid = $this->request->param('pid');
        $gid = $this->request->param('gid');
        $data['register_way'] = 3;
        $data['type'] = 1;
        $data['promote_id'] = session('union_host') ? session('union_host.union_id') : ($pid?:0);
        //$data['promote_account'] = get_promote_name($data['promote_id']);
        //$data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
        //$data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
        //$data['head_img'] = cmf_get_domain() . '/upload/sdk/logoo.png';
        $data['game_id'] = $gid?:0;
        $data['game_name'] = $gid?get_game_entity($gid,'game_name')['game_name']:'';
        $model = new UserModel();
        $result = $model->register($data);
        if ($result == -1) {
            $this->error('注册失败');
        } else {
            $result['user_id'] = $result['id'];
            session('member_auth', $result);
            $this->success('注册成功');
        }
    }

    /**
     * [用户资料]
     * @author 郭家屯[gjt]
     */
    public function userinfo()
    {
        if ($this->request->isPost()) {
            $data['nickname'] = trim($this->request->param('nickname/s'));
            $data['head_img'] = $this->request->param('head_img/s');
            if (empty($data['nickname']) || strlen($data['nickname']) > 24) {
                $this->error('昵称为1-24个字符');
            }
            $model = new UserModel();
            $user = $model->where('nickname', $data['nickname'])->where('id', 'neq', session('member_auth.user_id'))->field('id')->find();
            if ($user) {
                $this->error('昵称已被使用');
            }
            $result = $model->where('id', session('member_auth.user_id'))->update($data);
            if ($result !== false) {
                session('member_auth.nickname', $data['nickname']);
                session('member_auth.head_img', $data['head_img']);
                $this->success('资料更新成功');
            } else {
                $this->error('资料更新失败');
            }
        }
        $userConfig = cmf_get_option('vip_set');
        $vipLevel = empty($userConfig['vip'])?'':explode(',',$userConfig['vip']);
        $this->assign('vipLevel',$vipLevel );
        $imageext = cmf_get_option('upload_setting')['file_types']['image']['extensions']?:'jpg,jpeg,png,gif,bmp';
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,balance,vip_level');
        $this->assign('user', $user);
        $this->assign('imageext', $imageext);
        $res = (new PointTypeLogic())->user_vip(session('member_auth.user_id'));
        $this->assign('vip_upgrade',$res['vip_upgrade']);
        $this->assign('next_pay',$res['next_pay']);
        $this->assign('data',$res['data']);
        return $this->fetch();
    }

    /**
     * [实名认证]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function realauth()
    {
        if ($this->request->isPost()) {
            $data['real_name'] = $this->request->param('real_name/s');
            $data['idcard'] = $this->request->param('idcard/s');
            $data = $this->check_auth($data);
            $userModel = new UserModel();
            $result = $userModel->where('id', session('member_auth.user_id'))->update($data);
            $userModel->task_complete(session('member_auth.id'),'auth_idcard',$data['idcard']);//绑定任务
            if (!$result) {
                $this->error("实名认证失败");
            } else {
                $this->success('实名认证成功');
            }
        }
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,real_name,idcard,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @检查实名认证信息
     * @param array $data
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2019/12/31 11:56
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function check_auth($data=[])
    {
        //非必填信息
        if(get_user_config_info('age')['real_register_status'] == 2 && (empty($data['real_name']) || empty($data['idcard']))){
            unset($data['real_name'],$data['idcard']);
            return $data;
        }
        $rule = [
                'real_name' => 'require|min:2|max:25|chs',
        ];
        $msg = [
                'real_name.require' => '请输入真实姓名',
                'real_name.max' => '姓名长度需要在2-25个字符之间',
                'real_name.min' => '姓名长度需要在2-25个字符之间',
                'real_name.chs' => '姓名格式错误',
        ];
        $validate = new Validate($rule, $msg);
        $result = $validate->check($data);
        if (!$result) {
            $this->error($validate->getError());
        }
        if (substr($data['idcard'], -1) === 'X') {
            $data['idcard'] = str_replace('X', 'x', $data['idcard']);
        }
        $checkidcard = new Checkidcard();
        $invidcard = $checkidcard->checkIdentity($data['idcard']);
        if (!$invidcard) {
            $this->error("身份证号码填写不正确！");
        }

        $userconfig = new UserConfigModel();
        //实名认证设置
        $config = $userconfig->getSet('age');

        $userModel = new UserModel();
        if ($config['config']['can_repeat'] != '1') {
            $cardd = $userModel->where('idcard', $data['idcard'])->field('id')->find();
            if ($cardd) {
                $this->error("身份证号码已被认证！");
            }
        }

        if (($config['status'] == 0) || ($config['status'] == 1 && $config['config']['ali_status'] == 0)) {
            //判断年龄是否大于16岁
            if (is_adult($data['idcard'])) {
                $data['age_status'] = 2;
            } else {
                $data['age_status'] = 3;
            }
        } else {
            //真实判断身份证是否有效
            $re = age_verify($data['idcard'], $data['real_name'], $config['config']['appcode']);
            switch ($re) {
                case -1:
                    $this->error("短信数量已经使用完！");
                    break;
                case -2:
                    $this->error("连接接口失败");
                    break;
                case 0:
                    $this->error("认证信息错误");
                    break;
                case 1://成年
                    $data['age_status'] = 2;
                    $data['anti_addiction'] = 1;
                    break;
                case 2://未成年
                    $data['age_status'] = 3;
                    break;
                default:
            }
        }
        return $data;
    }

    /**
     * [账号管理]
     * @author 郭家屯[gjt]
     */
    public function safe()
    {
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level,phone,email');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * [修改密码]
     * @author 郭家屯[gjt]
     */
    public function password()
    {
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,register_type,password,vip_level');
        if (!in_array($user['register_type'], [0, 1, 2, 7])) {
            $this->error('您的账号不支持修改密码');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['old_password'])) $this->error('请输入原密码');
            if (empty($data['password'])) $this->error('请输入新密码');
            if (check_password($data['password']) == 1012) {
                $this->error('密码为6-15位字母或数字组合');
            }
            if ($data['old_password'] == $data['password']) {
                $this->error('新密码与原始密码不能相同');
            }
            if ($data['password'] != $data['repassword']) {
                $this->error('两次输入的密码不一致');
            }
            if (!xigu_compare_password($data['old_password'], $user['password'])) {
                $this->error('原密码错误');
            }
            $save['password'] = cmf_password($data['password']);
            $model = new UserModel();
            $result = $model->update_user_info(session('member_auth.user_id'), $save);
            if ($result) {
                session('member_auth.password', $save['password']);
                $this->success('修改成功', url('safe'));
            } else {
                $this->error('修改失败');
            }
        }
        $this->assign('user', $user);
        return $this->fetch();
    }


    /**
     * @支付密码设置
     *
     * @author: zsl
     * @since: 2020/10/9 11:57
     */
    public function second_password()
    {
        $user = get_user_entity(session('member_auth.user_id'), false, 'id,password,account,nickname,head_img,vip_level,phone,email');
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            if (empty($param['phone'])) {
                $this -> error('请输入11位手机号码');
            }
            if (!cmf_check_mobile($param['phone'])) {
                $this -> error("手机号码格式错误");
            }
            $this -> sms_verify($param['phone'], $param['code']);
            //验证登录密码
            if (!xigu_compare_password($param['login_password'], $user['password'])) {
                $this -> error('登录密码错误');
            }
            //验证支付密码
            if (strlen($param['second_password']) < 6 || strlen($param['second_password']) > 15 || !ctype_alnum($param['second_password'])) {
                $this -> error('支付密码为6-15位字母或数字组合');
            }
            $model = new UserModel();
            $save = [];
            $save['pay_password'] = cmf_password($param['second_password']);
            if (empty($user['phone'])) {
                $save['phone'] = $param['phone'];
            }
            $where = [];
            $where['id'] = $user['id'];
            $res = $model -> isUpdate(true) -> allowField(false) -> save($save, $where);
            if (false === $res) {
                $this -> error('设置失败，请重新提交');
            }
            session($param['phone'], null);
            $this -> success('绑定成功', url('media/user/safe'));
        }
        $this -> assign('user', $user);
        return $this -> fetch();
    }



    public function phone() {
        if($this->request->isPost()){
            $data = $this->request->param();
            if (empty($data['phone'])) {
                $this->error('请输入11位手机号码');
            }
            if (!cmf_check_mobile($data['phone'])) {
                $this->error("手机号码格式错误");
            }
            //图形验证
            $verify_tag = $data['verify_tag'];
            $verify_token = $data['verify_token'];
            $res = (new CaptionLogic()) -> checkToken($verify_token, $verify_tag,0);
            if ($res['code']!=200) {
                $this->error($res['info']);
            }
            $this->sms_verify($data['phone'], $data['code']);
            (new CaptionLogic()) -> clearToken($verify_tag);
            session($data['phone'],null);
            $save['phone'] = $data['phone'];
            $model = new UserModel();
            $result = $model->update_user_info(session('member_auth.id'), $save);
            $model->task_complete(session('member_auth.id'),'bind_phone',$save['phone']);//绑定任务
            if ($result) {
                $this->success('绑定成功',url('safe'));
            } else {
                $this->error('绑定失败，请重新提交',url('phone'));
            }
        }else{
            $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level,phone,email');
            $this->assign('phone',$user['phone']);
            $this->assign('user',$user);
            return $this->fetch();
        }
    }

    /**
     * 解绑
     * @return mixed|void
     */
    public function phone_check() {
        if($this->request->isPost()){
            $data = $this->request->param();
            //图形验证
            $verify_tag = $data['verify_tag'];
            $verify_token = $data['verify_token'];
            $res = (new CaptionLogic()) -> checkToken($verify_token, $verify_tag,0);
            if ($res['code']!=200) {
                $this->error($res['info']);
            }
//            $this->sms_verify($data['phone'], $data['code']);
            (new CaptionLogic()) -> clearToken($verify_tag);
            session($data['phone'],null);
            $save['phone'] = '';
            $model = new UserModel();
            $result = $model->update_user_info(session('member_auth.id'), $save);
            if ($result) {
                $this->success('已解除绑定',url('phone'));
            } else {
                $this->error('解绑失败，请重新提交',url('phone_check'));
            }
        }else{
            $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level,phone,email');
            $this->assign('user',$user);
            if(empty($user['phone'])){
                return $this->redirect(url('phone'));
            }
            $this->assign('phone',$user['phone']);
            return $this->fetch();
        }
    }

    public function email() {
        if($this->request->isPost()){
            $data = $this->request->param();
            if (!cmf_check_email($data['email'])) {
                $this->error("邮箱地址格式错误");
            }
            $this->verify_email($data['email'], $data['code']);
            $save['email'] = $data['email'];
            $model = new UserModel();
            $result = $model->update_user_info(session('member_auth.id'), $save);
            $model->task_complete(session('member_auth.id'),'bind_email',$save['email']);//绑定任务
            if ($result) {
                $this->success('绑定成功',url('safe'));
            } else {
                $this->error('绑定失败，请重新提交',url('email'));
            }

        }else{
            $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level,phone,email');
            $this->assign('user',$user);
            $this->assign('email',$user['email']);
            return $this->fetch();
        }
    }


    public function email_check() {
        if($this->request->isPost()){
            $data = $this->request->param();
            if (!cmf_check_email($data['email'])) {
                $this->error("邮箱地址格式错误");
            }
            $this->verify_email($data['email'], $data['code']);
            $save['email'] = '';
            $model = new UserModel();
            $result = $model->update_user_info(session('member_auth.id'), $save);
            if ($result) {
                $this->success('已解除绑定',url('email'));
            } else {
                $this->error('解绑失败，请重新提交',url('email_check'));
            }

        }else{
            $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level,phone,email');
            $this->assign('user',$user);
            $this->assign('email',$user['email']);
            return $this->fetch();
        }
    }

    /**
     * [我的游戏]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function mygame()
    {
        $model = new \app\game\model\GameModel();

        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $map['user_id']=session('member_auth.user_id');
        $map['is_del'] =0;
        $list = $model->get_play_game($map);
        $this->assign('list', $list);
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * [我的礼包]
     * @author 郭家屯[gjt]
     */
    public function mygift()
    {
        $model = new GiftRecordModel();
        $list = $model->alias('gb')
            ->field('gb.id,gift_id,icon,relation_game_id,relation_game_name,gift_version,start_time,end_time,gift_name,novice,gb.gift_version')
            ->join(['tab_game' => 'g'], 'g.id=gb.game_id', 'left')
            ->where('user_id', session('member_auth.user_id'))
            ->where('game_status', 1)
            ->where('gb.start_time', ['<', time()], ['=', 0], 'or')
            ->where('gb.end_time', ['>', time()], ['=', 0], 'or')
            ->order('gb.create_time desc,id desc')
            ->paginate(3);
        // 获取分页显示
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('list', $list);

        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * [过期礼包]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function myoverduegift()
    {
        $model = new GiftRecordModel();
        $list = $model->alias('gr')
            ->field('gr.id,gift_id,icon,relation_game_id,relation_game_name,gift_version,start_time,end_time,gift_name,novice')
            ->join(['tab_game' => 'g'], 'g.id=gr.game_id', 'left')
            ->where('user_id', session('member_auth.user_id'))
            ->where('end_time', '>', 0)
            ->where('end_time', '<', time())
            ->where('delete_status', 0)
            ->order('gr.create_time desc')
            ->paginate(8);
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('list', $list);
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * [用户删除礼包]
     * @author 郭家屯[gjt]
     */
    public function delgift()
    {
        $gift_id = $this->request->param('gift_id/d');
        $type = $this->request->param('type/s');
        $model = new GiftRecordModel();
        //type为all为全部删除
        if ($gift_id || $type == 'all') {
            if ($gift_id) {
                $map['id'] = $gift_id;
            }
            $result = $model
                ->where($map)
                ->where('user_id', session('member_auth.user_id'))
                ->where('end_time', '>', 0)
                ->where('end_time', '<', time())
                ->setField('delete_status', 1);
        } else {
            $result = false;
        }
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * [游戏账单]
     * @author 郭家屯[gjt]
     */
    public function myorder()
    {
        $base = new BaseHomeController();
        $model = new SpendModel();
        $map['user_id'] = session('member_auth.user_id');
        $map['pay_status'] = 1;
        $extend['order'] = 'pay_time desc';
        $extend['row'] = 10;
        $extend['field'] = 'game_name,pay_amount,pay_way,pay_time';
        $list = $base->data_list($model, $map, $extend);
        //总充值
        $total = $model->where($map)->sum('pay_amount');
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @平台币充值记录
     * @author: 郭家屯
     * @since: 2019/7/8 16:49
     */
    public function pay_record()
    {
        $base = new BaseHomeController();
        $model = new SpendBalanceModel();
        $map['pay_id'] = session('member_auth.user_id');
        $map['pay_status'] = 1;
        $extend['order'] = 'pay_time desc';
        $extend['row'] = 10;
        $extend['field'] = 'pay_amount,pay_way,pay_time,user_id';
        $list = $base->data_list($model, $map, $extend);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        //总充值
        $total = $model->where($map)->sum('pay_amount');
        $this->assign('total', $total);
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @我的收藏
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/10/15 13:58
     */
    public function mycollect()
    {
        $model = new GameModel();

        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $map['g.game_status'] = 1;
        $list = $model->getMyCollectList(session('member_auth.user_id'),$map);
        $this->assign('list', $list);
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @绑币充值记录
     * @author: 郭家屯
     * @since: 2019/7/8 16:49
     */
    public function bind_record()
    {
        $base = new BaseHomeController();
        $model = new SpendBindModel();
        $map['pay_id'] = session('member_auth.user_id');
        $map['pay_status'] = 1;
        $extend['order'] = 'pay_time desc';
        $extend['row'] = 10;
        $extend['field'] = 'pay_amount,pay_way,pay_time,user_account';
        $list = $base->data_list($model, $map, $extend);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        //总充值
        $total = $model->where($map)->sum('pay_amount');
        $this->assign('total', $total);
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @平台币充值记录
     * @author: 郭家屯
     * @since: 2019/7/8 16:49
     */
    public function bind_lists()
    {
        $base = new BaseHomeController();
        $model = new UserPlayModel();
        $map['user_id'] = session('member_auth.user_id');
        $map['bind_balance'] = ['gt',0];
        $extend['order'] = 'id desc';
        $extend['row'] = 10;
        $extend['field'] = 'game_name,bind_balance';
        $list = $base->data_list($model, $map, $extend);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @福利列表
     * @author: 郭家屯
     * @since: 2019/7/8 16:49
     */
    public function rebate()
    {
        $base = new BaseHomeController();
        $model = new SpendRebateRecordModel();
        $map['user_id'] = session('member_auth.user_id');
        $extend['order'] = 'id desc';
        $extend['row'] = 10;
        $extend['field'] = 'game_name,pay_amount,create_time,ratio_amount';
        $list = $base->data_list($model, $map, $extend);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        //总返利
        $total = $model->where($map)->sum('ratio_amount');
        $this->assign('total', $total);
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @福利列表
     * @author: 郭家屯
     * @since: 2019/7/8 16:49
     */
    public function coupon()
    {
        $type = $this->request->param('type/d');
        $user_id = session('member_auth.user_id');
        $user = get_user_entity($user_id,false,'id,account,nickname,head_img,vip_level,promote_id,parent_id');
        $promote_id = $user['promote_id'];
        if(!$type){
            $this->get_reciver_coupon($user_id,$promote_id);
        }else{
            $this->get_my_coupon($user_id,$type);
        }
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @我的消息
     * @author: 郭家屯
     * @since: 2020/11/4 11:05
     */
    // 代金券到期提醒
    public function my_tips()
    {
        $type = 1;
        $tips_list = $this->getMessageRemind($type);
        $page = $tips_list->render();
        $this->assign('page', $page);

        $tips_list_arr = $tips_list->toArray();

        if(!empty($tips_list_arr['data'])){
            $ids = [];
            foreach($tips_list_arr['data'] as $key=>$val){
                if($val['read_or_not'] == 1){
                    $ids[] = $val['id'];
                }
                // 将代金券提醒的手机端链接改为PC端的代金券链接
                $string_from = 'href="/mobile/coupon/index';
                $is_replace_link = strpos($val['content'],$string_from);

                if($is_replace_link !== false){
                    // 包含 需要替换 将 /mobile/coupon/index/type/1 替换为 media/user/coupon.html
                    $a = explode('href',$val['content']);

                    $b = $a['0'].'href="/media/user/coupon.html">点击查看></a>';
                    $tips_list_arr['data'][$key]['content'] = $b;

                }
            }

            $this->setReaded($ids);  // 标记为已读
        }
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        $this->assign('tips_list', $tips_list_arr['data']);

        $unread_num_single = $this->getUnreadNum();

        $this->assign('unread_num_single', $unread_num_single);
        // $this->assign('tips_info', $tips_info);
        return $this->fetch();
    }

    // 邀请奖励提醒
    public function reward(){
        // echo '邀请奖励';
        $type = 2;
        $tips_list = $this->getMessageRemind($type);
        $page = $tips_list->render();
        $this->assign('page', $page);
        $tips_list_arr = $tips_list->toArray();
        if(!empty($tips_list_arr['data'])){
            $ids = [];
            foreach($tips_list_arr['data'] as $key=>$val){
                if($val['read_or_not'] == 1){
                    $ids[] = $val['id'];
                }

                // media/user/rebate.html
                // if(!empty($val['content'])){
                //     $append_string = ', <a style="color:blue;text-decoration:underline;" href="/media/user/rebate.html">点击查看></a>';
                //     $tips_list_arr['data'][$key]['content'] = $val['content'].$append_string;
                // }
            }
            $this->setReaded($ids);
        }
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        $this->assign('tips_list', $tips_list_arr['data']);
        $unread_num_single = $this->getUnreadNum();
        $this->assign('unread_num_single', $unread_num_single);
        return $this->fetch();
    }
    // 试玩任务提醒奖励发放提醒
    public function demo_task(){
        // echo '试玩任务';
        $type = 3;

        $tips_list = $this->getMessageRemind($type);
        $page = $tips_list->render();
        $this->assign('page', $page);
        $tips_list_arr = $tips_list->toArray();

        if(!empty($tips_list_arr['data'])){
            $ids = [];
            foreach($tips_list_arr['data'] as $key=>$val){
                if($val['read_or_not'] == 1){
                    $ids[] = $val['id'];
                }
            }
            $this->setReaded($ids);
        }
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        $this->assign('tips_list', $tips_list_arr['data']);
        $unread_num_single = $this->getUnreadNum();
        $this->assign('unread_num_single', $unread_num_single);
        return $this->fetch();
    }
    // 开服提醒
    public function open_service_remind(){
        // echo '开服提醒';
        $type = 4;

        $tips_list = $this->getMessageRemind($type);
        $page = $tips_list->render();
        $this->assign('page', $page);
        $tips_list_arr = $tips_list->toArray();

        if(!empty($tips_list_arr['data'])){
            $ids = [];
            foreach($tips_list_arr['data'] as $key=>$val){
                if($val['read_or_not'] == 1){
                    $ids[] = $val['id'];
                }

                // 开服提醒里面都有 game_id, 添加链接可以跳转到 该游戏的详情页
                // 根据游戏的不同类型去跳转(手游, 页游, H5, )
                if($val['game_id']>0){
                    $game_id = $val['game_id'];
                    // 获取game的类型来判断是手游H5还是页游 game表里面的sdk_version  1安卓 2苹果(手游),  0 双版本, 3 H5, 4 页游',
                    // $game_info = Db::table('tab_game')->where()->field('id,sdk_version')->find();

                    $game_info = Db::table('tab_game')->where('id', $game_id)->field('id,relation_game_id,sdk_version')->find();
                    $game_id = $game_info['relation_game_id'];  //
                    $game_version = $game_info['sdk_version'];

                    if($game_version == 1 || $game_version == 2){
                        // 手游
                        $append_string = ',<a style="color:blue;text-decoration:underline;" href="/media/game/detail/game_id/'.$game_id.'.html">去体验></a>';
                    }
                    if($game_version == 3){
                        // H5
                        $append_string = ',<a style="color:blue;text-decoration:underline;" href="/media/game/hdetail/game_id/'.$game_id.'.html">去体验></a>';
                    }
                    if($game_version == 4){
                        // 页游 (暂时没有,估计后期会用到)
                        $append_string = ',<a style="color:blue;text-decoration:underline;" href="/media/game/ydetail/game_id/'.$game_id.'.html">去体验></a>';
                    }

                    $tips_list_arr['data'][$key]['content'] = $val['content'].$append_string;
                }

            }

            $this->setReaded($ids);
        }

        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        $this->assign('tips_list', $tips_list_arr['data']);
        $unread_num_single = $this->getUnreadNum();
        $this->assign('unread_num_single', $unread_num_single);
        return $this->fetch();
    }
    // 评论回复提醒
    public function comment_back(){
        // echo '评论回复';
        $type = 5;

        $tips_list = $this->getMessageRemind($type);
        $page = $tips_list->render();
        $this->assign('page', $page);
        $tips_list_arr = $tips_list->toArray();

        if(!empty($tips_list_arr['data'])){
            $ids = [];
            foreach($tips_list_arr['data'] as $key=>$val){
                if($val['read_or_not'] == 1){
                    $ids[] = $val['id'];
                }
            }
            $this->setReaded($ids);
        }
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        $this->assign('tips_list', $tips_list_arr['data']);
        $unread_num_single = $this->getUnreadNum();
        $this->assign('unread_num_single', $unread_num_single);
        return $this->fetch();
    }
    // 充值返利 到账消息
    public function rebate_remind(){
        // echo '返利提醒';
        $type = 6;

        $tips_list = $this->getMessageRemind($type);
        $page = $tips_list->render();
        $this->assign('page', $page);
        $tips_list_arr = $tips_list->toArray();

        // 获取未读消息数

        if(!empty($tips_list_arr['data'])){
            $ids = [];
            foreach($tips_list_arr['data'] as $key=>$val){
                if($val['read_or_not'] == 1){
                    $ids[] = $val['id'];
                }

                // media/user/rebate.html
                if(!empty($val['content'])){
                    $append_string = ', <a style="color:blue;text-decoration:underline;" href="/media/user/rebate.html">点击查看></a>';
                    $tips_list_arr['data'][$key]['content'] = $val['content'].$append_string;
                }
            }
            $this->setReaded($ids);
        }
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        $this->assign('tips_list', $tips_list_arr['data']);
        $unread_num_single = $this->getUnreadNum();
        $this->assign('unread_num_single', $unread_num_single);
        return $this->fetch();
    }

    // 获取消息
    private function getMessageRemind($type=0,$per_page=10){

        $user_id = session('member_auth.user_id'); // 得到当前登录的用户id
        // if(empty($user_id)){
        //     return [];
        // }

        $tips_list = Db::table('tab_tip')
            ->where("user_id='$user_id' AND type=$type")
            ->order('read_or_not desc,id desc')
            ->paginate($per_page);

        return $tips_list;

    }
    // 获取各个栏目的未读消息数目
    private function getUnreadNum(){
        $user_id = session('member_auth.user_id'); // 得到当前登录的用户id
        if(empty($user_id)){
            return [];
        }

        // 代金券未读消息数
        $unread_num1 = Db::table('tab_tip')
            ->where("user_id=$user_id AND type=1 AND read_or_not=1")
            ->count();
        // 邀请奖励未读消息数
        $unread_num2 = Db::table('tab_tip')
            ->where("user_id=$user_id AND type=2 AND read_or_not=1")
            ->count();
        // 试玩任务未读消息数
        $unread_num3 = Db::table('tab_tip')
            ->where("user_id=$user_id AND type=3 AND read_or_not=1")
            ->count();
         // 开服提醒未读消息数
         $unread_num4 = Db::table('tab_tip')
            ->where("user_id=$user_id AND type=4 AND read_or_not=1")
            ->count();
        // 开服提醒未读消息数
        $unread_num5 = Db::table('tab_tip')
            ->where("user_id=$user_id AND type=5 AND read_or_not=1")
            ->count();
        // 充值返利 到账消息
        $unread_num6 = Db::table('tab_tip')
            ->where("user_id=$user_id AND type=6 AND read_or_not=1")
            ->count();

        $return_data = [];
        $return_data['unread_num1'] = $unread_num1;
        $return_data['unread_num2'] = $unread_num2;
        $return_data['unread_num3'] = $unread_num3;
        $return_data['unread_num4'] = $unread_num4;
        $return_data['unread_num5'] = $unread_num5;
        $return_data['unread_num6'] = $unread_num6;
        // 注意 ** 类型再多的话 期望一次取出来 在做分类统计未读已读数目
        return $return_data;
    }
    // 将消息标记为已读
    private function setReaded($ids=[]){
        $user_id = session('member_auth.user_id'); // 得到当前登录的用户id
        $num = 0;
        if(!empty($ids)){
            $num = Db::table('tab_tip')
                ->where('id','in',$ids)
                ->update(['read_or_not'=>0]);
        }
        return $num;
    }

    /**
     * @函数或方法说明
     * @可领取列表
     * @author: 郭家屯
     * @since: 2020/2/5 16:14
     */
    protected function get_reciver_coupon($user_id=0,$promote_id=0)
    {
        $paylogic = new PayLogic();
        $coupon = $paylogic->get_coupon_lists($user_id,$promote_id);
        $this->assign('coupon',$coupon);
    }

    /**
     * @函数或方法说明
     * @我的优惠券
     * @param int $type
     *
     * @author: 郭家屯
     * @since: 2020/2/5 16:15
     */
    protected function get_my_coupon($user_id=0,$type=1)
    {
        $model = new CouponRecordModel();
        $coupon = $model->get_my_coupon($user_id,$type);
        $this->assign('coupon',$coupon);
    }

    /**
     * @函数或方法说明
     * @领取优惠券
     * @author: 郭家屯
     * @since: 2020/2/5 11:19
     */
    public function getcoupon()
    {
        $coupon_id = $this->request->param('coupon_id');
        if(empty($coupon_id))$this->error('参数错误');
        $user_id = session('member_auth.user_id');
        $logic = new GameLogic();
        $result = $logic->getCoupon($user_id,$coupon_id);
        if($result){
            $this->success('领取成功');
        }else{
            $this->error('领取失败');
        }
    }


    /**
     * [检查用户名]
     * @author 郭家屯[gjt]
     */
    public function checkRegisterName()
    {
        $data = $this->request->param();
        //验证
        if ($data['register_type'] == 3) {//邮箱
            $validate = new UserValidate2();
            if (!$validate->scene('account')->check($data)) {
                $this->error($validate->getError());
            }
            if (!cmf_check_email($data['account'])) {
                $this->error('请输入正确邮箱地址');
            }
            $this->success('输入正确');
        }elseif ($data['register_type'] == 2) {//手机号
            $validate = new UserValidate1();
            if (!$validate->scene('account')->check($data)) {
                $this->error($validate->getError());
            }
            if (!cmf_check_mobile($data['account'])) {
                $this->error('请输入正确手机号');
            }
            $this->success('输入正确');
        } else {
            $validate = new UserValidate();
            if (!$validate->scene('checkname')->check($data)) {
                $this->error($validate->getError());
            }
            $this->success('输入正确');
        }
    }

    /**
     * [检查找回密码用户名]
     * @author 郭家屯[gjt]
     */
    public function checkForgetName()
    {
        $data = $this->request->param();
        if (empty($data['account'])) {
            $this->error('账号不能为空');
        }
        $model = new UserModel();
        $user = $model->field('id,phone,email,lock_status')->where('account', $data['account'])->find();
        if ($user) {
            if ($user['lock_status'] == 0) {
                $this->error('账号不存在或被禁用');
            }
            if (empty($user['phone']) && empty($user['email'])) {
                $this->error('当前账号未绑定手机和邮箱，可联系客服找回密码');
            }
            session('forget_account', $data['account']);
            if ($user['phone']) {
                if ($user['email']) {
                    return json(['code' => 1]);//两者都绑定
                } else {
                    return json(['code' => 3]);//只有手机
                }
            } else {
                return json(['code' => 2]);//只有邮箱
            }
        } else {
            $this->error('账号不存在或被禁用');
        }
    }

    /**
     * [验证手机号码]
     * @author 郭家屯[gjt]
     */
    public function checkForgetPhone()
    {
        $data = $this->request->param();
        if(empty($data['phone'])) {
            $this->error('请输入绑定手机');
        }
        //图形验证
        $verify_tag = $data['verify_tag'];
        $verify_token = $data['verify_token'];
        $res = (new CaptionLogic()) -> checkToken($verify_token, $verify_tag,0);
        if ($res['code']!=200) {
            $this->error($res['info']);
        }
        if (empty($data['code'])) {
            $this->error('请输入验证码');
        }
        $account = session('forget_account');
        if (empty($account)) {
            $this->error('请先验证账号');
        }
        $this->sms_verify($data['phone'], $data['code']);
        session('forget_phone', $data['phone']);
        $this->success('验证正确');
    }

    /**
     * [验证邮箱]
     * @author 郭家屯[gjt]
     */
    public function checkForgetEmail()
    {
        $data = $this->request->param();
        if (empty($data['email'])) {
            $this->error('请输入绑定邮箱');
        }
        if (empty($data['code'])) {
            $this->error('请输入验证码');
        }
        $account = session('forget_account');
        if (empty($account)) {
            $this->error('请先验证账号');
        }
        $this->verify_email($data['email'], $data['code']);
        session('forget_email', $data['email']);
        $this->success('验证正确');
    }

    /**
     * [修改忘记密码接口]
     * @author 郭家屯[gjt]
     */
    public function forget_password()
    {
        $data = $this->request->param();
        #验证短信验证码
        $validate = new UserValidate1();
        if (!$validate->scene('password')->check($data)) {
            $this->error($validate->getError());
        }
        if ($data['code_type'] == 'phone') {
            $account = session('forget_account');
            $phone = session('forget_phone');
            if (empty($account) || empty($phone)) {
                $this->error('请先验证用户名和绑定手机号');
            }
        } elseif ($data['code_type'] == 'email') {
            $account = session('forget_account');
            $email = session('forget_email');
            if (empty($account) || empty($email)) {
                $this->error('请先验证用户名和绑定邮箱');
            }
        } else {
            $this->error('未知验证类型');
        }
        $model = new UserModel();
        $user_id = get_user_entity($account, true,'id')['id'];
        $result = $model->updatePassword($user_id, $data['password']);
        if ($result == true) {
            $this->success("密码重设成功！");
        } else {
            $this->error("密码重设成失败！");
        }

    }

    /**
     * [修改用户信息]
     * @author 郭家屯[gjt]
     */
    public function user_update_data()
    {
        $data = $this->request->param();
        $model = new UserModel();
        switch ($data['code_type']) {
            case 'rephone':
                $this->sms_verify($data['phone'], $data['code']);
                $save['phone'] = '';
                break;
            case 'phone':
                $this->sms_verify($data['phone'], $data['code']);
                $save['phone'] = $data['phone'];
                break;
            case 'nickname':
                $nk = $model->where('nickname', $data['nickname'])->field('id')->find();
                if ($nk) {
                    $this->error('昵称已被使用');
                }
                $save['nickname'] = $data['nickname'];
                break;
            case 'pwd' :
                $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,password');
                if ($data['old_password'] == $data['password']) {
                    $this->error('新密码与原始密码不能相同');
                }
                if (!xigu_compare_password($data['old_password'], $user['password'])) {
                    $this->error('密码错误');
                }
                $save['password'] = cmf_password($data['password']);
                break;
            default:
                $this->error("修改信息不明确");
                break;
        }
        $result = $model->update_user_info($data['user_id'], $save);
        if ($result) {
            $this->error('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

    /**
     *短信验证
     */
    public function sms_verify($phone = "", $code = "", $type = 2)
    {
        $session = session($phone);
        if (empty($session)) {
            $this->error("请先获取验证码");
        }
        #验证码是否超时
        $time = time() - session($phone . ".create");
        if ($time > 180) {//$tiem > 60
            $this->error("验证码已失效，请重新获取");
        }
        #验证短信验证码
        if (session($phone . ".code") != $code) {
            $this->error("验证码不正确，请重新输入");
        }
        if ($type == 1) {
            session($phone,null);
            $this->success("正确");
        } else {
            return true;
        }

    }

    /**
     *短信发送
     */

    public function send_sms()
    {
        $request = $this->request->param();
        $phone = $request['phone'];
        $account = $request['account'];
        if (empty($phone)) {
            $this->error('请输入手机号码');
        }
        if (strlen($phone) != 11) {
            $this->error('请输入11位手机号码');
        }
        if (!cmf_check_mobile($phone)) {
            $this->error("请输入正确手机号");
        }
        $userModel = new UserModel();
        //图形验证
        $verify_tag = $request['verify_tag'];
        $verify_token = $request['verify_token'];
        $res = (new CaptionLogic()) -> checkToken($verify_token, $verify_tag,0);
        if ($res['code']!=200) {
            $this->error($res['info']);
        }
        if ($request['reg'] == 1) { /* 注册检查 */
            $user = $userModel->field('id')->where('phone|account', $phone)->find();
            if ($user) {
                $this->error("手机号已被使用");
            }
        } elseif ($request['reg'] == 2) {/* 忘记密码检查 */
            $user = $userModel->field('id')->where('account', $account)->where('phone', $phone)->find();
            if (empty($user)) {
                $this->error("手机号码错误");
            }

        } elseif ($request['reg'] == 3) {/* 解绑绑定检查 */
            $user = $userModel->field('id')->where('account', $account)->where('phone', $phone)->find();
            if (empty($user)) {
                $this->error("用户名不存在");
            }
        } elseif ($request['reg'] == 4) {/* 绑定手机检查  */
            $user = $userModel->field('id')->where('phone', $phone)->find();
            if ($user) {
                $this->error("手机号已被使用");
            }
        } elseif ($request['reg'] == 5) {/* 是否绑定手机号检查  */

            $user = get_user_entity(session('member_auth.user_id'),false,'id,account,phone');
            if (empty($user['phone'])) {
                $this->error("您还未绑定手机号，需先绑定手机号");
            }
            if($user['phone']!=$phone){
                $this->error("请输入正确手机号");
            }

        } elseif ($request['reg'] == 6) {
            $user = $userModel -> field('id') -> where(['phone' => $phone, 'id' => ['neq', session('member_auth.user_id')]]) -> find();
            if ($user) {
                $this -> error("手机号已被使用");
            }
        } else {
            $this->error("未知发送类型");
        }
        $msg = new MsgModel();
        $data = $msg::get(1);
        if (empty($data)) {
            $this->error("没有配置短信发送");
        }
        $data = $data->toArray();
        if ($data['status'] == 1) {
            $xigu = new Xigu($data);
            $res = sdkchecksendcode($phone, $data['client_send_max'], 2);
            if($res==1072){
                $this->error('短信验证已达上限，请明天再试');
            }
            if($res==1073){
                $this->error('请一分钟后再次尝试');
            }
            $sms_code = session($phone);//发送没有session 每次都是新的
            $sms_rand = sms_rand($phone);
            $rand = $sms_rand['rand'];
            $new_rand = $sms_rand['rand'];
            /// 产生手机安全码并发送到手机且存到session
            $param = $rand . "," . '3';
            $result = json_decode($xigu->sendSM( $phone, $data['captcha_tid'], $param), true);
            $result['send_status'] = '000000';
            $result['create_time'] = time();
            $result['pid'] = 0;
            $result['phone'] = $phone;
            $result['create_ip'] = get_client_ip();
            Db::table('tab_sms_log')->field(true)->insert($result);
            #TODO 短信验证数据
            if ($result['send_status'] == '000000') {
                $safe_code['code'] = $rand;
                $safe_code['phone'] = $phone;
                $safe_code['time'] = $new_rand ? time() : $sms_code['time'];
                $safe_code['delay'] = 3;
                $safe_code['create'] = $result['create_time'];
                session($phone, $safe_code);
                $this->success("短信已发放，请注意查收！");
            } else {
                $this->error("验证码发送失败，请重新获取");
            }
        } else {
            $this->error("没有配置短信发送");
        }
    }

    /**
     * @函数或方法说明
     * 添加极验方法
     * @author: 郭家屯
     * @since: 2019/3/27 15:07
     */
    public function checkgeetest()
    {
        if (AUTH_USER == 1) {
            $geetest_id = cmf_get_option('admin_set')['auto_verify_index'];
            $geetest_key = cmf_get_option('admin_set')['auto_verify_admin'];
            $geetest = new GeetestLib($geetest_id, $geetest_key);
            $status = $geetest->pre_process($this->pc_param, 1);
            session('pc_gtserver', $status);
            echo $geetest->get_response_str();
            exit;
        }
    }


    /**
     * [验证邮箱]
     * @author 郭家屯[gjt]
     */
    public function verify_email($email, $code, $type = 2)
    {
        $code_result = $this->emailVerify($email, $code);
        if ($code_result == 1) {
            if ($type == 1) {
                $this->success("验证成功");
            } else {
                return true;
            }
        } else {
            if ($code_result == 2) {
                $this->error("请先获取验证码");
            } elseif ($code_result == -98) {
                $this->error("验证码超时");
            } elseif ($code_result == -97) {
                $this->error("验证码不正确，请重新输入");
            }
        }
    }


    /**
     * @param $email
     * @param $code
     * @param int $time
     * @return int
     * 验证 邮箱验证码
     */
    public function emailVerify($email, $code, $time = 30)
    {
        $session = session($email);
        if (empty($session)) {
            return 2;
        } elseif ((time() - $session['create_time']) > $time * 60) {
            return -98;
        } elseif ($session['code'] != $code) {
            return -97;
        }
        session($email, null);
        return 1;
    }

    /**
     * 发送邮件验证码 注册传1 解绑传2 绑定传3
     */

    public function send_email()
    {
        $data = $this->request->param();
        $code_type = $data['code_type'];
        $email = $data['email'];
        $account = $data['account'];
        if (!cmf_check_email($email)) {
            $this->error("邮箱地址格式错误");
        }
        $usermodel = new UserModel();
        if ($code_type == 1) {/* 注册 */
            $user = $usermodel->where('email|account', $email)->find();
            if ($user) {
                $this->error("邮箱已被使用");
            }
            $msg = "注册邮箱";
        } elseif ($code_type == 2) {/* 忘记密码 */
            $user = $usermodel->where('account', $account)->where('email', $email)->find();
            if (empty($user)) {
                $this->error("用户未绑定该邮箱");
                die;
            }
            $msg = "忘记密码";
        } elseif ($code_type == 3) {/* 解绑 */
            $user = $usermodel->where('account', $account)->where('email', $email)->find();
            if (empty($user)) {
                $this->error("用户名不存在");
                die;
            }
            $msg = "解绑邮箱";
        } elseif ($code_type == 4) {/* 绑定 */
            $user = $usermodel->where('email', $email)->find();
            if ($user) {
                $this->error("邮箱已被使用");
            }
            $msg = "绑定邮箱";
        }
        $session = session($data['email']);
        if (!empty($session) && (time() - $session['create_time']) < 60) {
            $this->error("验证码发送过于频繁，请稍后再试");
            exit;
        }
        $email = $data['email'];
        $code = rand(100000, 999999);
        $smtpSetting = cmf_get_option('email_template_verification_code');
        $smtpSetting['template'] = str_replace('{$code}', $code, htmlspecialchars_decode($smtpSetting['template']));
        $result = cmf_send_email($data['email'], $msg, $smtpSetting['template']);
        if ($result['error'] == 0) {
            session($email, ['code' => $code, 'create_time' => time()]);
            $this->success("发送成功");
        } else {
            $this->error("发送失败，请检查邮箱地址是否正确");
        }
    }

    /**
     * @函数或方法说明
     * @获取验证码
     * @return \think\Response
     *
     * @author: 郭家屯
     * @since: 2019/10/16 11:00
     */
    public function set_captcha(){
        $config =    [
            // 验证码位数
             'length'      =>    4,
            // 是否添加杂点
             'useNoise' => true,
             // 是否画混淆曲线
             'useCurve' => false,
             // 是否使用背景图片
             'useImgBg' => false,
             // 使用字体
             'codeSet' => '0123456789'
         ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }


    public function doSign(PointLogic $logicPoint)
    {
        $res = $logicPoint->sign_in(session('member_auth.user_id'));
        if($res==-1){
            $this->error('签到功能暂时关闭，请稍后再试');
        }elseif($res==-2){
            $this->error('今日已签到，请不要重复签到');
        }elseif($res==-3){
            $this->error('请先进行实名认证');
        }else{
            $this->success('签到成功');
        }
    }
    // pc官网客服
    public function kefu(){

        return $this->fetch();
    }

}
