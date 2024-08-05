<?php
/**
 * 用户控制器
 * @author: 鹿文学
 * @Datetime: 2019-03-25 14:54
 */

namespace app\mobile\controller;

use app\common\controller\SmsController;
use app\common\logic\InvitationLogic;
use app\common\logic\PointTypeLogic;
use app\common\model\UserFeedbackModel;
use app\common\validate\UserFeedbackValidate;
use app\game\model\GamecollectModel;
use app\member\model\PointTypeModel;
use app\member\model\UserBehaviorModel;
use app\member\model\UserModel;
use app\member\model\UserPlayModel;
use app\member\validate\UserValidate;
use app\member\model\UserConfigModel;
use app\common\controller\BaseHomeController;
use app\recharge\model\SpendRebateRecordModel;
use app\site\model\KefuModel;
use app\site\model\KefutypeModel;
use app\site\model\TipModel;
use app\user\logic\UnsubscribeLogic;
use app\user\model\UserUnsubscribeModel;
use think\WechatAuth;
use think\thinksdk\ThinkOauth;
use think\Checkidcard;
use app\common\logic\CaptionLogic;
use app\common\logic\PointLogic;
use app\game\model\GameModel;
use app\promote\model\PromoteunionModel;
use app\member\model\UserTransactionModel;
use app\member\model\UserTransactionOrderModel;
use think\db;
use think\Request;
use think\captcha\Captcha;

class UserController extends BaseController
{

    public $pc_param = [];

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        /*
         * 用户权限判断
         */
        if (AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('admin/main/index'));
            };
        }

        $this->pc_param = [
            "user_id" => "test", # 这个是用户的标识，或者说是给极验服务器区分的标识，如果你项目没有预先设置，可以先这样设置：
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
        ];
    }
    public function user_address(){
        $this->isLogin();
        $address = get_user_entity(UID,false,'receive_address')['receive_address'];
        $address = explode('|!@#%-|',$address);
        $address = count($address)!=4?[]:$address;
        $this->assign('address',$address);
        return $this->fetch();
    }
	 public function user_address_edit(){
         $this->isLogin();
         if($this->request->isPost()){
             $postData = $this->request->post();
             if (!preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,25}$/', $postData['address_name'])) {
                 $this->error('收货人姓名长度需要在2-25个字符之间，<br>不可包含非法字符');
             }
             $len = mb_strlen($postData['address_name']);
             if ($len < 2 || $len > 25) {
                 $this->error('收货人姓名长度需要在2-25个字符之间，<br>不可包含非法字符');
             }
             if(strlen($postData['address_phone']) != 11){
                 $this->error("请输入11位手机号码");
             }
             if (!cmf_check_mobile($postData['address_phone'])) {
                 $this->error("请输入正确手机号");
             }
             if(empty($postData['consignee_address'])){
                 $this->error("请选择所在地区");
             }
             $len = mb_strlen($postData['address_detail']);
             if ($len<=0) {
                 $this->error('详细地址不能为空');
             }
             $model = new UserModel();
             $save['receive_address'] = implode('|!@#%-|',$postData);
             $result = $model->update_user_info(session('member_auth.id'), $save);
             $model->task_complete(session('member_auth.id'),'improve_address',$save['receive_address']);//绑定任务
             $this->success('保存成功');
         }
         $address = get_user_entity(UID,false,'receive_address')['receive_address'];
         $address = explode('|!@#%-|',$address);
         $address = count($address)!=4?[]:$address;
         $this->assign('address',$address);
         return $this->fetch();
    }
	 public function user_vip(){
         $this->isLogin();
         $res = (new PointTypeLogic())->user_vip(UID);
         $this->assign('vip_upgrade',$res['vip_upgrade']);
         $this->assign('next_pay',$res['next_pay']);
         $this->assign('data',$res['data']);
         return $this->fetch();
    }

    /**
     * 登录
     * @author: 鹿文学
     * @Datetime: 2019-03-26 9:29
     */
    public function login()
    {
        $data['account'] = $this->request->param('account/s');
        if (empty($data['account'])) $this->error('请输入用户名');
        $data['password'] = $this->request->param('password/s');
        if (empty($data['password'])) $this->error('请输入密码');

        $model = new UserModel();
        $data['type'] = 3;//wap登录
        $result = $model->login($data);
        switch ($result) {
            case 1005:/* 用户名不存在 */
                $this->error('账号不存在或被禁用');
                break;
            case 1006:
                $this->error('密码错误');
                break;
            case 1007:/* 账户被锁定 */
                $this->error('账号不存在或被禁用');
                break;
            default:
                save_cookie_account($data['account'], $data['password']);
                cookie('login_account', $data['account'], parent::COOKIE_EXPIRE_TIME);
                $result['user_id'] = $result['id'];
                session('member_auth', $result);
                break;
        }
        return json(['code'=>1,'msg'=>'登录成功','wait'=>1]);
    }

    /**
     * @函数或方法说明
     * @清除COOKIE账号信息
     * @author: 郭家屯
     * @since: 2019/5/21 15:55
     */
    public function clearaccount()
    {
        $index = $this->request->param('index', 0, 'intval');
        $cookie_account = json_decode(cookie('cookie_account'), true);
        if (count($cookie_account) == 1) {
            cookie('cookie_account', null);
        } else {
            unset($cookie_account[$index]);
            array_values($cookie_account);
            cookie('cookie_account', json_encode($cookie_account));
        }
        $this->success('成功');
    }

    /**
     * 第三方登录
     *
     * @return mixed
     *
     * @author: fyj301415926@126.com
     * @since: 2019\3\27 0027 14:31
     */
    public function thirdLogin()
    {
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
            if(is_weixin()){
                session('wechat_token', null);
                $this->wechat_login();
            }else{
                $this->wechat_code_login();
            }
        } else {
            //加载ThinkOauth类并实例化一个对象
            $sns = ThinkOauth::getInstance($type);
            if (!empty($sns)) {
                //跳转到授权页面
                $gid = $this->request->param('gid', 0, 'intval');
                if(MOBILE_PID >0 ){
                    $pid = MOBILE_PID;
                }else{
                    $pid = $this->request->param('pid', 0, 'intval');
                }
                $this->redirect($sns->getRequestCodeURL($gid, $pid));
            }
        }
    }
    /**
     * [第三方微信登录]
     * @author 郭家屯[gjt]
     */
    public function wechat_code_login()
    {
        $userinfo = userInfo();
        if (!$userinfo["user_id"]) {
            $config = get_user_config_info('weixin_login');
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
            $this->redirect($auth->getQrconnectURL($redirect_uri, $state));
        }
    }

    /**
     * QQ回调
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019\4\1 0001 13:51
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     */
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
        session('redirect_url')?:session('redirect_url',url('Index/index'));
        if (is_array($token)) {
            $controller = new LoginTypeController();
            $user_info = $controller->qq($token);
           // $user_info['openid'] = $token['access_token'];
            $data['head_img'] = $user_info['headpic'];
            if (empty($user_info['openid'])) {
                $this->error('腾讯公司应用未打通 未将所有appid设置统一unionID 请到http://wiki.connect.qq.com/%E7%89%B9%E6%AE%8A%E9%97%AE%E9%A2%98-top10参考第一天');
                exit;
            }

            $model = new UserModel();
            $user = $model->where('unionid', $user_info['openid'])->find();
            if ($user) {
                if($user['lock_status']!=1){
                    $this->error('用户已被锁定，无法登录',url('user/index'));
                }
                $data['type'] = 4;//wap登录
                $data['id'] = $user['id'];
                $data['account'] = $user['account'];
                $data['promote_id'] = $user['promote_id'];
                $model->auth_login($data);
                $user['user_id'] = $user['id'];
                userInfo($user);
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
                $data['register_way'] = 4;
                $data['type'] = 4;
                $data['promote_id'] = session('union_host') ? session('union_host.union_id') : ($pid ? $pid : (session('device_promote_id')?:0));
                //$data['promote_account'] = get_promote_name($data['promote_id']);
                //$data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
                //$data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
                $data['game_id'] = $gid ? $gid : (session('device_game_id')?:0);
                $data['game_name'] = $gid ? get_game_name($gid) : '';
                $data['equipment_num'] = session('device_code')?:'';
                $model = new UserModel();
                $result = $model->register($data);
                if ($result == -1) {
                    $this->redirect(session('redirect_url'));
                } else {
                    $result['user_id'] = $result['id'];
                    userInfo($result);
                    $this->redirect(session('redirect_url'));
                }
            }

        }
    }


    /**
     * 注册
     * @author: 鹿文学
     * @Datetime: 2019-03-26 9:30
     */
    public function register()
    {
        if($this->request->param('gid')>0){
            //封禁判断-20210712-byh
            if(!judge_user_ban_status($this->request->param('pid'),$this->request->param('gid'),'',session('device_code'),get_client_ip(),$type=2)){
                $this->error('您当前被禁止注册，请联系客服');
            }
        }
        //验证注册ip
        if(!checkregiserip()){
            $this->error('暂时无法注册，请联系客服');
        }
        $data = $this->request->param();
        $session_name = '';
        if ($data['type'] == 1) {
            if (empty($data['account'])) {
                $this->error('请输入11位手机号码');
            }
            $data['register_type'] = 2;
            $sms = new \app\common\controller\SmsController;
            $smsData = $sms->verifySmsCode($data['account'], $data['code'], false, false);
            if ($smsData['code'] != SmsController::$error_info['success']) {
                switch ($smsData['code']) {
                    case SmsController::$error_info['code_empty']:
                        $return = $smsData['msg'];
                        break;
                    case SmsController::$error_info['code_input_error']:
                    case SmsController::$error_info['code_overtime']:
                        $return = '短信验证码错误或已过期';
                        break;
                }
                $this->error($return);
            }
            $session_name = $smsData['session_name'];
            $data['phone'] = $data['account'];
        } else {
            if (empty($data['account'])) {
                $this->error('账号为6-15位字母或数字组合');
            }
            $data['register_type'] = 1;
        }
        //图形验证
        $verify_tag = $data['verify_tag'];
        $verify_token = $data['verify_token'];
        $res = (new CaptionLogic()) -> checkToken($verify_token, $verify_tag,0);
        if ($res['code']!=200) {
            $this->error($res['info']);
        }
        //验证
        $validate = new UserValidate();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if(get_user_config_info('age')['real_register_status'] > 0 && $data['promote_type'] != 1){
            $data = $this->check_auth($data);
        }
        (new CaptionLogic()) -> clearToken($verify_tag);
        $pid = $this->request->param('pid');
        $promote_id = empty($data['promote_id']) ? ($pid ? : 0) : $data['promote_id'];
        $data['register_way'] = 4;
        $data['type'] = 4;
        $data['promote_id'] = session('union_host') ? session('union_host.union_id') : ($promote_id ?: (session('device_promote_id')?:0));
        //$data['promote_account'] = get_promote_name($data['promote_id']);
        //$data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
        //$data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
        $data['game_id'] = empty($data['game_id']) ? (session('device_game_id')?:0) : $data['game_id'];
        $data['game_name'] = get_game_name($data['game_id']);
        $data['equipment_num'] = session('device_code')?:'';
        $model = new UserModel();
        $result = $model->register($data);
        if ($session_name) {
            SmsController::clearSmsCodeStore($session_name);
        }
        if ($result == -1) {
            $this->error('注册失败');
        } else {
            cookie('login_account', $data['account'], parent::COOKIE_EXPIRE_TIME);
            $result['user_id'] = $result['id'];
            userInfo($result);
            $this->success('注册成功');
        }
    }
    public function nextBtnCheck()
    {
        //验证注册ip
        if(!checkregiserip()){
            $this->error('暂时无法注册，请联系客服');
        }
        $data = $this->request->param();
        //验证
        $validate = new UserValidate();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        $this->success('符合规则');
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
            session('m_gtserver', $status);
            echo $geetest->get_response_str();
            exit;
        }
    }

    /**
     * 发送验证码
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @since: 2019\3\27 0027 11:18
     * @author: fyj301415926@126.com
     */
    public function sendSms()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $phone = $request['phone'];
            $account = $request['account'];
            $userModel = new UserModel();
            switch ($request['reg']) {
                case 1: /*注册检查*/
                    if (empty($phone)) {
                        $this->error('请输入手机号码');
                    }
                    if (strlen($phone) != 11) {
                        $this->error('请输入11位手机号码');
                    }
                    if (!cmf_check_mobile($phone)) {
                        $this->error("请输入正确手机号");
                    }
                    $verify_token = $request['verify_token'];
                    $verify_tag = $request['verify_tag'];
                    $res = (new CaptionLogic()) -> checkToken($verify_token, $verify_tag,0);
                    if($res['code']!=200){
                        $this->error($res['info']);
                    }
                    $user = $userModel
                        ->where('phone|account', $phone)
                        ->find();
                    if ($user) {
                        $this->error("手机号已被使用");
                    }
                    break;
                case 2:/*忘记密码检查*/
                    if (empty($phone)) {
                        $this->error('绑定手机号不能为空');
                    }
                    if (strlen($phone) != 11) {
                        $this->error('请输入11位手机号码');
                    }
                    if (!cmf_check_mobile($phone)) {
                        $this->error("请输入正确手机号");
                    }
                    $account = $request['account'];
                    $user = $userModel
                        ->where('account', $account)
                        ->where('phone', $phone)
                        ->find();
                    if (empty($user)) {
                        $this->error("号码错误，请重新输入");
                    }
                    break;
                case 3:/* 解绑绑定检查 */
                    if (strlen($phone) != 11) {
                        $this->error('请输入11位手机号码');
                    }
                    if (!cmf_check_mobile($phone)) {
                        $this->error("请输入正确手机号");
                    }
                    $user = $userModel->field('id')->where('account', $account)->where('phone', $phone)->find();
                    if (empty($user)) {
                        $this->error("用户名不存在");
                    }
                    break;
                case 4:/* 绑定手机检查  */
                    if (strlen($phone) != 11) {
                        $this->error('请输入11位手机号码');
                    }
                    if (!cmf_check_mobile($phone)) {
                        $this->error("请输入正确手机号");
                    }
                    $user = $userModel->field('id')->where('phone', $phone)->find();
                    if ($user) {
                        $this->error("手机号已被使用");
                    }
                    break;
            }
            $sms = new \app\common\controller\SmsController;
            $data = $sms->sendSmsCode($phone, '3', false);
            if ($data['code'] == 200) {
                $this->success($data['msg'], '', $data['data']);
            } else {
                $this->error($data['msg']);
            }
        } else {
            $this->error('提交失败');
        }

    }

    /**
     * 发送邮箱验证码
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019\3\28 0028 11:52
     * @author: fyj301415926@126.com
     */
    public function sendEmailCodeSend()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $email = $request['email'];
            $account = $request['account'];
            $userModel = new UserModel();
            switch ($request['reg']) {
                case 2:/*忘记密码检查*/
                    if (empty($email)) {
                        $this->error('绑定邮箱不能为空');
                    }
                    if (!preg_match('/^[a-z0-9]+([._-][a-z0-9]+)*@([0-9a-z]+\.[a-z]{2,14}(\.[a-z]{2})?)$/i', $email)) {
                        $this->error("号码错误，请重新输入");
                    }
                    $account = $request['account'];
                    $user = $userModel
                        ->where('account', $account)
                        ->where('email', $email)
                        ->find();
                    if (empty($user)) {
                        $this->error("号码错误，请重新输入");
                    }
                    $msg = "忘记密码";
                    break;
                case 3://解绑
                    $user = $userModel->where('account', $account)->where('email', $email)->find();
                    if (empty($user)) {
                        $this->error("用户名不存在");
                        die;
                    }
                    $msg = "解绑邮箱";
                    break;
                case 4://绑定
                    $user = $userModel->where('email|account', $email)->find();
                    if ($user) {
                        $this->error("邮箱已被使用");
                    }
                    $msg = "绑定邮箱";
                    break;
            }
            $session = session($email);
            if (!empty($session) && (time() - $session['create_time']) < 60) {
                $this->error("验证码发送过于频繁，请稍后再试");
            }
            $code = rand(100000, 999999);
            $smtpSetting = cmf_get_option('email_template_verification_code');
            $smtpSetting['template'] = str_replace('{$code}', $code, htmlspecialchars_decode($smtpSetting['template']));
            $result = cmf_send_email($email, str_replace('注册', $msg, $smtpSetting['subject']), $smtpSetting['template']);
            if ($result['error'] == 0) {
                session($email, ['code' => $code, 'create_time' => time()]);
                $this->success("验证码发放成功，请注意查收");
            } else {
                $this->error("发送失败，请检查邮箱地址是否正确");
            }
        } else {
            $this->error('提交失败');
        }
    }

    /**
     * 验证邮箱验证码
     *
     * @param $email
     * @param $code
     * @param int $type
     *
     * @return bool
     *
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 11:53
     */
    public function verifyEmailCode($email, $code, $type = 2)
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
     * 邮箱验证码验证
     *
     * @param $email
     * @param $code
     * @param int $time
     *
     * @return int
     *
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 11:53
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
     * 退出
     * @author: 鹿文学
     * @Datetime: 2019-03-26 9:28
     * @change yyh
     */
    public function logout()
    {
        $this->isLogin();
        session('member_auth', null);
        cookie('last_login_account', null);
        $this->success('退出成功');
    }

    /**
     * 忘记密码：验证用户名是否可用
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: fyj301415926@126.com
     * @since: 2019\3\27 0027 15:39
     */
    public function forgetOnCheckAccount()
    {
        if ($this->request->isPost()) {
            $account = $this->request->post('account/s');
            if (empty($account)) {
                $this->error('账号不能为空');
            }
            $userModel = new UserModel();

            $user = $userModel->checkAccount($account);

            if (is_array($user)) {
                if (empty($user['phone']) && empty($user['email'])) {
                    $this->error('未绑定手机号或邮箱，请联系客服找回');
                }
                $this->success('验证成功', '', ['account' => $account, 'phone' => $user['phone'], 'email' => $user['email']]);
            } else {
                $this->error('账号不存在或被禁用');
            }
        } else {
            $this->error('提交失败');
        }
    }

    /**
     * 忘记密码：获取电话或邮箱信息
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 10:55
     */
    public function forgetOnCheckUserInfo()
    {
        if ($this->request->isPost()) {
            $account = $this->request->post('account/s');
            $type = $this->request->post('type/s');
            if (empty($account)) {
                $this->error('账号不能为空');
            }
            $userModel = new UserModel();

            $user = $userModel->checkAccount($account);

            if (is_array($user) && $user[$type]) {
                $this->success('成功', '', ['account' => $account, 'phone' => $user['phone'], 'email' => $user['email']]);
            } else {
                $this->error('失败');
            }
        } else {
            $this->error('提交失败');
        }
    }

    /**
     * 忘记密码：验证短信
     *
     * @author: fyj301415926@126.com
     * @since: 2019\3\27 0027 16:08
     */
    public function forgetOnCheckSms()
    {
        if ($this->request->isPost()) {
            $request = $this->request->post();
            if(empty($request['phone'])){
                $this->error('绑定手机号不能为空');
            }
            $sms = new \app\common\controller\SmsController;

            $smsData = $sms->verifySmsCode($request['phone'], $request['code'], false);

            if ($smsData['code'] != SmsController::$error_info['success']) {
                switch ($smsData['code']) {
                    case SmsController::$error_info['code_input_empty']:
                        $return = '验证码不能为空';
                        break;
                    case SmsController::$error_info['code_empty']:
                        $return = $smsData['msg'];
                        break;
                    case SmsController::$error_info['code_input_error']:
                    case SmsController::$error_info['code_overtime']:
                        $return = '验证码错误或已过期';
                        break;
                }
                $this->error($return);
            }

            $this->success('验证成功', '', ['account' => $request['account']]);

        } else {
            $this->error('提交失败');
        }
    }

    /**
     * 忘记密码：验证邮箱验证码
     *
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 15:19
     */
    public function forgetOnCheckEmailCode()
    {
        if ($this->request->isPost()) {
            $request = $this->request->post();
            if($request['email']==''){
                $this->error('绑定邮箱不能为空');
            }
            if($request['code']==''){
                $this->error('验证码不能为空');
            }
            $this->verifyEmailCode($request['email'], $request['code']);

            $this->success('验证成功', '', ['account' => $request['account']]);

        } else {
            $this->error('提交失败');
        }
    }

    /**
     * 忘记密码：更改密码
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 9:16
     */
    public function forgetOnChangePassword()
    {
        if ($this->request->isPost()) {
            $request = $this->request->post();

            if (empty($request['password'])) {
                $this->error('密码不能为空');
            }
            if (!preg_match('/^[a-zA-Z0-9]{6,15}$/', $request['password'])) {
                $this->error('密码为6-15位字母或数字组合');
            }
            if (empty($request['repassword'])) {
                $this->error('确认密码不能为空');
            }
            if ($request['password'] !== $request['repassword']) {
                $this->error('两次输入的密码不一致');
            }


            $model = new UserModel();

            $data = $model->checkAccount($request['account']);

            if ($model->updatePassword($data['id'], $request['password'])) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        } else {
            $this->error('提交失败');
        }
    }

    /**
     * 我的（用户中心）
     * @return mixed
     * @author: 鹿文学
     * @Datetime: 2019-03-26 9:28
     */
    public function index()
    {
        $user = get_user_entity(UID,false,'id,account,head_img,vip_level,phone,balance,nickname,point,member_days,end_time');
        $user['bind_balance'] = Db::table('tab_user_play')->where('user_id',UID)->sum('bind_balance');
        $user['bind_balance'] = sprintf("%.2f",$user['bind_balance']);
        //会员状态
        if($user['end_time'] > time()){
            $user['member_status'] = 1;
        }elseif($user['end_time']){
            $user['member_status'] = 2;
        }else{
            $user['member_status'] = 0;
        }
        //获取尊享卡名称
        if($user['end_time']){
            $mcard_set = cmf_get_option('mcard_set');
            if($mcard_set['config1']['card_name']){
                $card[$mcard_set['config1']['days']] = $mcard_set['config1']['card_name'];
            }
            if($mcard_set['config2']['card_name']){
                $card[$mcard_set['config2']['days']] = $mcard_set['config2']['card_name'];
            }
            if($mcard_set['config3']['card_name']){
                $card[$mcard_set['config3']['days']] = $mcard_set['config3']['card_name'];
            }
            krsort($card);
            foreach ($card as $key=>$v){
                if($user['member_days'] >= $key){
                    $user['mcard_name'] = $v;
                    break;
                }
            }
        }

        $this->assign('user',$user );
        $userConfig = cmf_get_option('vip_set');
        $vipLevel = empty($userConfig['vip'])?'':explode(',',$userConfig['vip']);
        $this->assign('vipLevel',$vipLevel );
        // 增加账号注册开关
        $sdk_set = cmf_get_option('wap_set');
        $account_register_switch = $sdk_set['account_register_switch'] ?? 1; // 0 关闭 1 开启
        $user['account_register_switch'] = $account_register_switch;
        // account_register_switch
        $this->assign('account_register_switch', $account_register_switch);

        //获取消息状态
        $model = new TipModel();
        $tip_status = $model->get_tip_status(UID);
        $this->assign('tip_status',$tip_status);
        return $this->fetch();

    }

    /**
     * 用户设置
     * @return mixed
     * @author: 鹿文学
     * @Datetime: 2019-03-26 9:29
     */
    public function set()
    {
        $this->isLogin();
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level,phone,email,real_name,idcard,receive_address,qq');
        $address = explode('|!@#%-|',$user['receive_address']);
        $address = count($address)!=4?[]:$address;
        $user['receive_address'] = $address;
        // 是否绑定手机或者邮箱
        if(!empty($user['phone']) || !empty($user['email'])){
            $this->assign('bindPhone',1);
        }
        $this->assign('user', $user);
        //查询用户注销状态
        $mUnsub = new UserUnsubscribeModel();
        $unsubStatus = $mUnsub -> getUnsubscribeStatus($user['id']);
        $this -> assign('unsub_status', $unsubStatus);

        //增加返回用户注销协议的标题名称
        $portalPostModel = new \app\site\model\PortalPostModel;
        $zhguxiao_title = $portalPostModel -> where('id', 2) -> value('post_title');
        $this -> assign('zhguxiao_title', $zhguxiao_title);
        return $this->fetch('user_set');

    }

    /**
     * @函数或方法说明
     * @绑定qq号
     * @author: 郭家屯
     * @since: 2020/10/9 15:22
     */
    public function bind_qq()
    {
        $this->isLogin();
        $qq = $this->request->param('qq');
        if(empty($qq)){
            $this->error('请输入QQ号码');
        }
        if(!is_numeric($qq)){
            $this->error('QQ号码格式错误');
        }
        $model = new UserModel();
        $result = $model->where('id',session('member_auth.user_id'))->setField('qq',$qq);
        if($result){
            $this->success('绑定成功');
        }else{
            $this->error('绑定失败');
        }
    }

    /**
     * 修改昵称
     *
     * @return mixed
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019\3\28 0028 16:08
     * @author: fyj301415926@126.com
     */
    public function nickname()
    {
        $this->isLogin();
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        if ($this->request->isPost()) {

            $nickname = $this->request->param('nickname/s');

            $len = mb_strlen($nickname, 'utf-8');

            if ($nickname == $user['nickname']) {
                return json_encode(['code'=>-2]);
            }

            if ($len < 1 || $len > 24) {
                $this->error('昵称为1-24位字符');
            }

            $model = new UserModel();

            $nicknames = $model
                ->where(['id' => ['neq', $user['id']], 'nickname' => $nickname])
                ->find();
            if (!empty($nicknames)) {
                $this->error('昵称已被使用');
            }

            $result = $model->update_user_info($user['id'], ['nickname' => $nickname]);
            if ($result) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }

        } else {

            $this->assign('user', $user);

            return $this->fetch('update_nickname');

        }
    }

    /**
     * 修改密码
     *
     * @return mixed
     *
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 20:26
     */
    public function password()
    {
        $this->isLogin();

        if ($this->request->isPost()) {
            $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level,password');
            $request = $this->request->post();
            $oldpassword = $request['oldpassword'];
            $password = $request['password'];
            $repassword = $request['repassword'];
            if (empty($oldpassword)) {
                $this->error('请输入原密码');
            }
            if (!xigu_compare_password($oldpassword, $user['password'])) {
                $this->error('原密码错误');
            }
            if (empty($password)) {
                $this->error('请输入新密码');
            }
            if (!preg_match('/^[a-zA-Z0-9]{6,15}$/', $password)) {
                $this->error('密码为6-15位字母或数字组合');
            }
            if (empty($repassword)) {
                $this->error('请输入确认密码');
            }
            if ($password !== $repassword) {
                $this->error('两次输入的密码不一致');
            }

            $model = new UserModel();
            if ($model->updatePassword(UID, $password)) {
                $user['user_id'] = $user['id'];
                userinfo($user);

                $this->success('密码重置成功');

            } else {
                $this->error('密码重置失败');
            }

        } else {

            return $this->fetch();
        }
    }

    /**
     * 实名认证
     *
     * @return mixed
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019\3\28 0028 17:14
     * @author: fyj301415926@126.com
     */
    public function realname()
    {
        $this->isLogin();

        if ($this->request->isPost()) {
            $data['real_name'] = $this->request->param('real_name/s');
            $data['idcard'] = $this->request->param('idcard/s');
            $userModel = new UserModel();
            $data = $this->check_auth($data);
            $result = $userModel->where('id', UID)->update($data);
            $userModel->task_complete(UID,'auth_idcard',$data['idcard']);//绑定任务
            if (!$result) {
                $this->error("认证失败！");
            } else {
                $this->success('认证成功！');
            }
        }
        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level,password,real_name,idcard');
        $this->assign('user', $user);

        return $this->fetch('ready_name');

    }

    protected function check_auth($data=[])
    {
        //非必填信息
        if(get_user_config_info('age')['real_register_status'] == 2 && (empty($data['real_name']) || empty($data['idcard']))){
            unset($data['real_name'],$data['idcard']);
            return $data;
        }
        if (!preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,25}$/', $data['real_name'])) {
            $this->error('姓名格式错误');
        }
        $len = mb_strlen($data['real_name']);
        if ($len < 2 || $len > 25) {
            $this->error('姓名长度需要在2-25个字符之间');
        }
        $data['idcard'] = strtolower($data['idcard']);
        $checkidcard = new Checkidcard();
        $invidcard = $checkidcard->checkIdentity($data['idcard']);
        if (!$invidcard) {
            $this->error("证件号码错误！");
        }

        $userconfig = new UserConfigModel();
        //实名认证设置
        $config = $userconfig->getSet('age');

        $userModel = new UserModel();
        if ($config['config']['can_repeat'] != '1') {
            $cardd = $userModel -> where('idcard', $data['idcard']) -> field('id') -> find();
            if ($cardd) {
                $this -> error("身份证号码已被使用！");
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


    public function phone() {
        $this->isLogin();
        if($this->request->isPost()){
            $data = $this->request->param();
            if (empty($data['phone'])) {
                $this->error('请输入11位手机号码');
            }
            if (!cmf_check_mobile($data['phone'])) {
                $this->error("手机号码格式错误");
            }
            if(!$data['code']){
                $this->error('验证码不能为空');
            }
            //图形验证
            $verify_tag = $data['verify_tag'];
            $verify_token = $data['verify_token'];
            $res = (new CaptionLogic()) -> checkToken($verify_token, $verify_tag,0);
            if ($res['code']!=200) {
                $this->error($res['info']);
            }
            $sms = new \app\common\controller\SmsController;
            $smsData = $sms->verifySmsCode($data['phone'], $data['code'], false, false);
            if ($smsData['code'] != SmsController::$error_info['success']) {
                switch ($smsData['code']) {
                    case SmsController::$error_info['code_empty']:
                        $return = $smsData['msg'];
                        break;
                    case SmsController::$error_info['code_input_error']:
                    case SmsController::$error_info['code_overtime']:
                        $return = '短信验证码错误或已过期';
                        break;
                }
                $this->error($return);
            }
            $session_name = $smsData['session_name'];
            (new CaptionLogic()) -> clearToken($verify_tag);
            if ($session_name) {
                SmsController::clearSmsCodeStore($session_name);
            }
            $save['phone'] = $data['is_unbind']?'':$data['phone'];
            $model = new UserModel();
            $result = $model->update_user_info(session('member_auth.id'), $save);
            $model->task_complete(session('member_auth.id'),'bind_phone',$save['phone']);//绑定任务
            $tip = $data['is_unbind']?'解绑':'绑定';
            $small_id = $this->request->param('small_id');
            if ($result) {
                if($small_id > 0){
                    $url = url('trade/transaction',['small_id'=>$small_id]);
                }else{
                    $url = url('index');
                }
                $this->success($tip.'成功',$url);
            } else {
                $this->error($tip.'失败，请重新提交');
            }
        }else{
            $phone = get_user_entity(session('member_auth.id'),false,'phone')['phone'];
            $this->assign('phone',$phone);
	    	if(empty($union_set['qq'])){
                $kefuQQ = cmf_get_option('kefu_set')['pc_set_server_qq'];
            }else{
                $kefuQQ = $union_set['qq'];
            }
            $this->assign('kefuQQ',$kefuQQ);
            return $this->fetch();
        }
    }

    public function email() {
        $this->isLogin();
        if($this->request->isPost()){
            $data = $this->request->param();
            if (!cmf_check_email($data['email'])) {
                $this->error("邮箱地址格式错误");
            }
            $this->verifyEmailCode($data['email'],$data['code']);
            $save['email'] = $data['is_unbind']?'':$data['email'];
            $model = new UserModel();
            $result = $model->update_user_info(session('member_auth.id'), $save);
            $model->task_complete(session('member_auth.id'),'bind_email',$save['email']);//绑定任务
            $tip = $data['is_unbind']?'解绑':'绑定';
            if ($result) {
                $this->success($tip.'成功',url('phone'));
            } else {
                $this->error($tip.'失败，请重新提交',url('phone'));
            }
        }else{
            $email = get_user_entity(session('member_auth.id'),false,'email')['email'];
            $this->assign('email',$email);
            return $this->fetch();
        }
    }

    /**
     * 用户协议
     *
     * @return mixed
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 15:20
     */
    public function protocol()
    {
        $param = $this ->request -> param('language_type');

        if (is_numeric($param) && $param >= 0) {

           $ProtocolModel =  new \app\site\model\ProtocolModel;

           $data = $ProtocolModel -> field('title as post_title, content as post_content')
               -> where('language', $param)
               -> order('update_time desc')
               -> find();
           $data['post_content'] = htmlspecialchars_decode($data['post_content']);
        } else {

            $portalPostModel = new \app\site\model\PortalPostModel;

            $data = $portalPostModel->where('id', 27)->find();
        }

        $this->assign('data', $data);

        return $this->fetch();

    }

    /**
     * 隐私协议
     *
     * @return mixed
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 15:20
     */
    public function privacy()
    {

        $portalPostModel = new \app\site\model\PortalPostModel;

        $data = $portalPostModel->where('id', 16)->find();

        $this->assign('data', $data);

        return $this->fetch();

    }


    /**
     * @用户注销协议
     *
     * @author: zsl
     * @since: 2021/7/29 19:01
     */
    public function unsubscribe_protocol()
    {
        $portalPostModel = new \app\site\model\PortalPostModel;
        $data = $portalPostModel -> where('id', 2) -> find();
        $this -> assign('data', $data);
        return $this -> fetch();
    }


    /**
     * 头像上传
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\3\29 0029 17:57
     */
    public function headImg()
    {
        $this->isLogin();

        if ($this->request->isPost()) {
            $head_img = $this->request->param('head_img/s');

            $model = new UserModel();

            if ($model->update_user_info(UID, ['head_img' => $head_img])) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        } else {
            $this->error('提交失败');
        }
    }

    /**
     * 我的游戏
     *
     * @return mixed
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019\3\29 0029 17:57
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     */
    public function game()
    {
        $this->isLogin();

        $this->isGameAuth();

        $model = new \app\game\model\GameModel();
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $map['user_id']=UID;
        $map['is_del'] =0;
        $game = $model->get_play_game($map);
        $this->assign('list', $game);

        return $this->fetch();

    }

    /**
     * @函数或方法说明
     * @获取收藏游戏
     * @return mixed
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019/7/9 11:51
     * @author: 郭家屯
     */
    public function mycollect()
    {
        $this->isLogin();

        $this->isGameAuth();

        $model = new GameModel();
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $map['g.sdk_version'] = ['in',[get_devices_type(),3]];
        $map['g.game_status'] = 1;
        $list = $model->getMyCollectList(UID,$map);
        $lists = [];
        foreach ($list as $key => $vo) {
            $lists[$vo['create_time']][] = $vo;
        }
        // 增加账号注册开关
        $sdk_set = cmf_get_option('wap_set');
        $account_register_switch = $sdk_set['account_register_switch'] ?? 1; // 0 关闭 1 开启
        $user['account_register_switch'] = $account_register_switch;
        // account_register_switch
        $this->assign('account_register_switch', $account_register_switch);


        $this->assign('list', $lists);

        return $this->fetch();

    }

    /**
     * 删除我的游戏
     *
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\3\29 0029 17:57
     */
    public function deleteGame()
    {
        $this->isLogin();

        $this->isGameAuth();

        if ($this->request->isPost()) {
            $ids = $this->request->post('ids/a');
            if (empty($ids)) {
                $this->error('请选择要删除的数据');
            }

            $model = new \app\game\model\GameModel;

            $result = $model->deleteMyPlayGame(['user_id' => UID, 'id' => ['in', $ids]]);

            if ($result) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }

        } else {
            $this->error('提交失败');
        }
    }

    /**
     * @函数或方法说明
     * @删除收藏游戏
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author: 郭家屯
     * @since: 2019/7/9 15:04
     */
    public function deleteCollect()
    {
        $this->isLogin();

        $this->isGameAuth();

        if ($this->request->isPost()) {
            $ids = $this->request->post('ids/a');
            if (empty($ids)) {
                $this->error('请选择要删除的数据');
            }

            $model = new GamecollectModel();

            $result = $model->where('id', 'in', $ids)->delete();

            if ($result) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }

        } else {
            $this->error('删除失败');
        }
    }

    /**
     * @函数或方法说明
     * @福利列表
     * @author: 郭家屯
     * @since: 2019/7/8 16:49
     */
    public function user_rebate()
    {
        $this->isLogin();
        $this->isGameAuth();
        $model = new SpendRebateRecordModel();
        $map['user_id'] = session('member_auth.user_id');
        //总返利
        $total = $model->where($map)->sum('ratio_amount');
        $this->assign('total', $total);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @返利数据
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/2/7 17:50
     */
    public function get_rebate()
    {
        $this->isLogin();
        $model = new SpendRebateRecordModel();
        $map['user_id'] = session('member_auth.user_id');
        $p = $this->request->param('p');
        $limit = $this->request->param('limit',10);
        $data = $model->get_my_rebate($map,$p,$limit);
        return json($data);
    }

    /**
     * 我的礼包
     *
     * @return mixed
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019\3\30 0030 14:22
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     */
    public function gift()
    {
        $this->isLogin();

        $this->isGameAuth();

        $model = new \app\game\model\GiftRecordModel;

        $received = $model->record(UID, 1);

        $overdue = $model->record(UID, 2);


        $this->assign('received', $received);
        $this->assign('overdue', $overdue);

        return $this->fetch();

    }

    /**
     * 删除我的已过期礼包
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\3\30 0030 14:22
     */
    public function deleteGift()
    {
        $this->isLogin();

        $this->isGameAuth();

        if ($this->request->isPost()) {
            $ids = $this->request->post('ids');
            if (empty($ids)) {
                $this->error('请选择要删除的数据');
            }
            $map['user_id'] = UID;
            if (is_numeric($ids)) {
                $map['id'] = $ids;
            } elseif (is_array($ids)) {
                $map['id'] = ['in', $ids];
            } elseif (is_string($ids) && $ids = 'all') {
                $map['end_time'] = array(['elt', time()], ['neq', 0]);
            } else {
                $this->error('请选择要删除的数据');
            }

            $model = new \app\game\model\GiftRecordModel;

            $result = $model->deleteMyGift($map);

            if ($result) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }


        } else {
            $this->error('提交失败');
        }
    }

    /**
     * 我的游戏账单
     *
     * @return mixed
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019\3\30 0030 15:30
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     */
    public function bill()
    {
        $this -> isLogin();
        $this -> isPayAuth();
        $model = new \app\recharge\model\SpendModel;
        $list = $model -> getMyGameBill(UID);
        $total = 0;
        foreach ($list as $item) {
            $total += $item['pay_amount'];
        }
        $this -> assign('list', $list);
        $this -> assign('total', $total);
        return $this -> fetch();

    }

    // 投诉反馈
    public function feedback()
    {
        $this -> isLogin();
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            $vUserFeedback = new UserFeedbackValidate();
            if (!$vUserFeedback -> scene('add') -> check($param)) {
                $this -> error($vUserFeedback -> getError());
            }
            if(count($param['images'])<3 || count($param['images'])>5){
                $this->error('请上传3-5张图片');
            }
            $mUserFeedback = new UserFeedbackModel();
            $param['user_id'] = UID;
            $res = $mUserFeedback -> addFeedback($param);
            if (false === $res) {
                $this -> error('提交失败');
            }
            $this -> success('提交成功');
        }
        return $this -> fetch();
    }

    // 游戏搜索/游戏列表页
    public function game_list()
    {

        $param = $this -> request -> param();
        $model = new GameModel();
        $map = [];
        if (!empty($param['game_name'])) {
            $map['game_name'] = ['like', '%' . $param['game_name'] . '%'];
        }
        $map['sdk_version'] = ['in', [get_devices_type(), 3]];
        $data = $model -> getRecommendGameLists($map, 9999) -> each(function($item, $key){
            $item['icon'] = cmf_get_image_url($item['icon']);
            return $item;
        });
        $this -> assign('game_list', $data);
        return $this -> fetch();
    }


    //绑定余额页面
    public function bind_lists(){
        $this->isLogin();
        $base = new BaseHomeController();
        $model = new UserPlayModel();
        $map['user_id'] = UID;
        $extend['field'] = 'g.game_name,bind_balance,g.icon,g.sdk_version';
        $extend['join1'] = [['tab_game'=>'g'],'g.id = tab_user_play.game_id','left'];
        $list = $base->data_list_join_select($model,$map,$extend);
        $this->assign('list',$list);
        $user = get_user_entity(UID,false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
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
    //小号管理
    public function user_trumpet(){
        $this->isLogin();
        $model = new UserModel();
        $data = $model->get_new_trumpet_list(session('member_auth.user_id'));
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @修改小号名称
     * @author: 郭家屯
     * @since: 2020/2/7 20:53
     */
    public function update_small_account()
    {
        $this->isLogin();
        if($this->request->isPost()){

            $user_id = $this->request->param('user_id/d');
            $account = $this->request->param('account/s');
            $userModel = new UserModel();
            $save['nickname'] = $account;
            $result = $userModel->update_user_info($user_id, $save);
            if ($result) {
                $this->success("修改成功");
            } else {
                $this->error('修改失败');
            }
        }
    }

    /**
     * 检测密码是否正确
     */
    public function checkPassword($is_json = 1)
    {
        if($this->request->isPost()){
            $this->isLogin();
            $password = $this->request->param('password');
            $user_id = session('member_auth.id');
            $userModel = new UserModel();
            $cmfpasword = $userModel->where('id',$user_id)->value('password');
            if (!xigu_compare_password($password, $cmfpasword)) {
                return $is_json?json(['code'=>0,'msg'=>'密码验证失败']):false;
            }else{
                return $is_json?json(['code'=>1,'msg'=>'密码验证成功']):true;
            }
        }
    }
     //我的消息
    public function user_tip(){
        $req_data = $this->request->param();
        $type = $req_data['type'] ?? 1;
        $this->isLogin();
        $tip_model = new TipModel();
        $user_id = UID;
        $map = [];
        // 通知
        // type 字段 1:代金券到期通知, 2:邀请奖励,注册奖励发放通知, 3:试玩任务通知, 4:开服提醒, 5:评论回复通知',6:充值返利到账消息
        if($type == 1){
            $map['type'] = ['in', [1,2,3,4,6]];
        }
        // 互动
        if($type == 2){
            $map['type'] = 5;
        }
        // 系统消息
        if($type == 0){
            $map['type'] = 666;  // 后期系统消息的时候用,暂时用不到
        }
        // 系统消息是否有未读消息
        $unread_num1 = 0;
        // 通知大类里面是未读消息数
        $map2['type'] = ['in', [1,2,3,4,6]];
        $unread_num2 = $tip_model->getUnreadMsgNum($user_id, $map2);
        // 互动是未读消息数
        $map3['type'] = 5;
        $unread_num3 = $tip_model->getUnreadMsgNum($user_id, $map3);


        $data = $tip_model->getTipList($user_id, $map);
        foreach($data as $key=>$val){
            if($val['type'] == 6){
                // 返利到账提醒通知
                if(!empty($val['content'])){
                    $append_string = ', <a style="color:blue;text-decoration:underline;" href="/mobile/user/user_rebate.html">点击查看></a>';
                    $data[$key]['content'] = $val['content'].$append_string;
                }
            }
            // 代金券到期提醒链接 是写进数据库的, 这里不需要拼接
            // 开服提醒在页面上做了判断,可以跳转到游戏详情页
        }

        $this->assign('data',$data);
        $this->assign('unread_num2',$unread_num2); // 通知未读消息数
        $this->assign('unread_num3',$unread_num3); // 互动未读消息数

        //添加查看行为
        $behavior_model = new UserBehaviorModel();
        $map['user_id'] = UID;
        $map['game_id'] = 0;
        $read_time = $behavior_model->set_record($map);

        $this->assign('read_time',$read_time);
        return $this->fetch();
    }
    //邀请好友
    public function invite_friends(){
        $this->isLogin();
        $logic = new InvitationLogic();
        //注册奖励
        $map['coupon_type'] = 1;
        $reg_award = $logic->get_one_award($map);
        $this->assign('reg_award',$reg_award);
        //注册奖励
        $map['coupon_type'] = 2;
        $sp_award = $logic->get_one_award($map);
        $this->assign('sp_award',$sp_award);
        //他人奖励
        $map['coupon_type'] = 3;
        $other_award = $logic->get_award($map);
        $this->assign('other_award',$other_award);
        //邀请链接
        $share_url = url('Invitation/index',['account'=>ACCOUNT],'',true);
        $this->assign('share_url',$share_url);
        return $this->fetch();
    }

    /**
     * 分享到微信朋友圈 或者 分享到QQ空间 完成分享任务
     * created by wjd
     * 2021-6-17 10:28:42
    */
    public function share_out_complete_task(Request $request)
    {
        $param = $request->param();
        $key = $param['key'] ?? '';
        $allowKeyArr = ['wx_friends_share', 'qq_zone_share'];
        if(empty($key) || !in_array($key, $allowKeyArr)){
            return json(['code'=>-1, 'msg'=>'缺少必要的参数!', 'data'=>[]]);
        }
        $user_id = UID;
        $pointLogic = new PointLogic();
        // 完成任务
        $grantRes = $pointLogic->grantShareOutTask($user_id, $key);
        $grantResCode = $grantRes['code'] ?? -1;
        $grantResMsg = $grantRes['msg'] ?? '未知返回结果!';
        if($grantResCode == 1){
            return json(['code'=>1, 'msg'=>$grantResMsg]);
        }else{
            return json(['code'=>-1, 'msg'=>$grantResMsg]);
        }

    }

    /**
     * @函数或方法说明
     * @获取邀请人
     * @author: 郭家屯
     * @since: 2020/2/21 16:00
     */
    public function get_invitation()
    {
        $this->isLogin();
        $logic = new InvitationLogic();
        $data = $this->request->param();
        $p = $data['p'] ? :1;
        $limit = $data['limit'] ?:10;
        $data = $logic->get_invitation_data(UID,$p,$limit);
        foreach ($data as $key=>$v){
            $data[$key]['invitation_account'] = hideStar($v['invitation_account']);
        }
        return json($data);
    }
   //邀请规则
    public function invite_rules(){
        $this->isLogin();
        $logic = new InvitationLogic();
        //注册奖励
        $map['coupon_type'] = 1;
        $reg_award = $logic->get_one_award($map);
        $this->assign('reg_award',$reg_award);
        //注册奖励
        $map['coupon_type'] = 2;
        $sp_award = $logic->get_one_award($map);
        $this->assign('sp_award',$sp_award);
        //他人奖励
        $map['coupon_type'] = 3;
        $other_award = $logic->get_award($map);
        $this->assign('other_award',$other_award);
        return $this->fetch();
    }
    public function check_auth_code()
    {
        $request = $this->request->param();
        if(empty($request)){
            $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        }
        $auth_code = base64_decode($request['auth_code']);
        $redirect_url = base64_decode($request['redirect_url']);

        if(empty($auth_code)||empty($redirect_url)){
            $status = 0;
        }
        $authData = Db::table('tab_user_auth_code')->where('code','=',$auth_code)->find();
        if(empty($authData)){
            $status = -1;//不存在
        }elseif($authData['status']!=0){
            $status = -2;//已使用
        }elseif(time()-8>$authData['create_time']){
            $status = -3;//已过期
        }else{
            Db::startTrans();
            $save['id'] = $authData['id'];
            $save['status'] = 1;
            $save['update_time'] = time();
            $res =  Db::table('tab_user_auth_code')->update($save);
            if($res===false){
                $status = -4;//更新失败
            }else{
                $token = explode('_xigu_',$auth_code)[0];
                $token = json_decode(think_decrypt($token,1), true);
                $modelUser = new UserModel();
                $user = $modelUser->where('id', '=',$token['uid'])->where('puid',0)->find();
                if(empty($user)){
                    $status = -5;
                }else{
                    $user->user_id = $user['id'];
                    if($request['request_source'] == 'app'){
                        $user->request_source = 'app';
                        session('app_user_login',1);
                        $user->app_user_login = 1;
                    }else{
                        $user->request_source = 'sdk';
                    }
                    session('member_auth', $user->toArray());
                    $this->redirect($redirect_url);
                    exit;
                }
            }
        }
        $this->assign('status',$status);
        return $this->fetch();
    }

    /**
     * 保存渠道信息
     * @return [type] [description]
     */
    public function  h5shell_imei(){
        $request = $this->request->param();
        $device_code = $request['imei'];
        $promote_id = $request['promote_id'];
        $game_id = $request['game_id'];
        $sdk_version = $request['sdk_version'];
        if($promote_id > 0) {
            $promote = get_promote_entity($promote_id);
            if ($promote['game_ids'] === 0) {
                return json(['status' => 0]);
            }
            if ($promote['game_ids'] && in_array($game_id, explode(',', $promote['game_ids']))) {
                return json(['status' => 0]);
            }
            $apply = Db::table('tab_promote_apply')->where(['game_id' => $game_id, 'promote_id' => $promote_id, 'status' => 1])->field('id')->find();
            if (!$apply) {
                return json(['status' => 0]);
            }
            session('device_promote_id',$promote_id);
        }
        session('device_game_id',$game_id);
        session('device_sdk_version',$sdk_version);
        if($device_code=='' ||  $game_id == 0){
            return json(['status'=>0]);
        }
        session('device_code',$device_code);
        return json(['status'=>1]);
    }

    /**
     * h5打包app的微信登录和qq登录
     */
    public function app_third_login(){
        $request = $this->request->param();
        switch ($request['logintype']) {
            case 'qq':
                $prefix="qq_";
                $data['register_type'] = 4;
                break;
            default :
                $prefix="wx_";
                $data['register_type'] = 3;
                break;
        }

        $openarr = explode(',',$request['openID']);
        $openID = $openarr[0];
        $model = new UserModel();
        $user = $model->where('unionid',$openID)->find();
        if($user){
            $request['type'] = 3;//wap登录
            $request['id'] = $user['id'];
            $model->auth_login($request);
            $user['user_id'] = $user['id'];
        }else{
            do {
                $data['account'] = $prefix . sp_random_string();
                $account = $model->field('id')->where('account', $data['account'])->find();
            } while (!empty($account));
            $data['nickname'] = $request['nickName'] ? : $data['account'];
            $data['unionid'] = $openID;
            $data['register_way'] = 4;
            $data['type'] = 4;
            $data['head_img'] = $request['icon'];
            $data['promote_id'] = $request['pid'] ? : 0;
            $data['game_id'] = $request['game_id'] ? : 0;
            $data['game_name'] = $data['game_id'] ? get_game_name($data['game_id']) : '';
            $model = new UserModel();
            $user = $model->register($data);
            if ($user == -1) {
                $this->error('登录失败');
            }
            $user['user_id'] = $user['id'];
        }
        userInfo($user);
        $this->success('登录成功');
    }


    /**
     * @客服中心
     *
     * @author: zsl
     * @since: 2020/12/9 15:43
     */
    public function service()
    {
        //获取分类
        $mKefutype = new KefutypeModel();
        $typeLists = $mKefutype -> getPcTypeLists(6);
        $this -> assign('typeLists', $typeLists);
        //增加判断是否为APP内打开-20210629-byh
        $is_app = $this->request->param('is_app','0');
        $promote_id = $this->request->param('promote_id');
        if ($promote_id > 0) {
            $model = new PromoteunionModel();
            $map['union_id'] = $promote_id;
            $resule = $model->field('union_set')->where($map)->find();
            $resule = empty($resule) ? [] : $resule->toarray();
            if($resule && $resule['union_set']){
                $union_set = json_decode($resule['union_set'], true);
                $this->assign('union_set', $union_set);
            }
        }
        $this -> assign('is_app', $is_app);
        //判断处理在线客服为qq还是溪谷系统客服链接-20210707-byh
        if(get_xgkf_info(0)){
            $userSession = self::$userSession;
            $xgkf_ = get_xgkf_info(1);
//            $xgkf_url = $xgkf_."&uid=".$userSession['login_user_id']."&name=".$userSession['login_account']."&avatar=".$userSession['login_head_img'];
            $xgkf_url = $xgkf_;
            $this -> assign('xgkf_url', $xgkf_url);
        }
        return $this -> fetch();
    }

    /**
     * @客服中文文章
     *
     * @since: 2021/4/21 10:11
     * @author: zsl
     */
    public function serviceArticle()
    {
        $result = ['code' => 200, 'msg' => '请求成功', 'data' => []];
        $type = $this -> request -> param('type');
        //获取分类详情
        $mKefutype = new KefutypeModel();
        $type_name = $mKefutype -> where(['id' => $type]) -> value('name');
        $result['type_name'] = $type_name;
        //获取分类下文章
        $mKefu = new KefuModel();
        $field = "id,zititle,content";
        $where = [];
        $where['type'] = $type;
        $where['status'] = 1;
        $lists = $mKefu -> field($field) -> where($where) -> order('sort desc') -> select();
        $result['data'] = $lists ? $lists : [];
        return json($result);
    }


    // 小号购买 微信内使用支付宝支付
    // 微信内使用支付宝 支付
    // public function alipay2($user=[],$transaction=[],$request=[])
    public function alipay3()
    {
        if(cmf_is_wechat()){
            return $this->fetch();
        }
        $request = $this->request->param();
        $user_id = (int) $request['user_id'];

        if($user_id <= 0){
            $this->error('请您先登录!');
        }
        $model = new UserTransactionModel();

        $transaction = $model->where('id',$request['transaction_id'])->where('status','in',[-1,1])->find();
        if(!$transaction){
            $this->error('商品已出售或者已下架');
        }
        //锁定交易
        if($transaction['status'] == 1){
            $save['status'] = -1;
            $save['lock_time'] = time();
            $model->where('id',$request['transaction_id'])->update($save);
        }else{
            $ordermodel = new UserTransactionOrderModel();
            $order = $ordermodel->where('transaction_id',$request['transaction_id'])->field('id,user_id')->order('id desc')->find();
            if($order['user_id'] != $user_id){
                $this->error('当前商品已被锁定，可购买其他商品');
            }else{
                $model->where('id',$request['transaction_id'])->setField('lock_time',time());
                $ordermodel->where('id',$order['id'])->setField('pay_status',2);
            }
        }
        $user = get_user_entity($user_id,false,'id,account,balance');

        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $data['pay_way'] = 3;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        $body = "购买小号";
        $title = "交易订单支付";
        $table = "transaction";
        $data['pay_order_number'] = create_out_trade_no("TO_");
        if($request['is_balance']){
            $pay_amount = $transaction['money'] - $user['balance'];
        }else{
            $pay_amount = $transaction['money'];
        }
        $fee = cmf_get_option('transaction_set')['fee'];
        $min_dfee = cmf_get_option('transaction_set')['min_dfee'];
        $fee_money = 0;
        if($fee){
            $fee_money = $transaction['money'] * $fee/100;
        }
        if($min_dfee){
            if($min_dfee > $fee_money ){
                $fee_money = $min_dfee;
            }
        }
        $lock_time = date('Y-m-d H:i:s', strtotime('+5minute'));
        $pay = new \think\Pay($data['pay_type'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody($body)
                ->setFee($pay_amount)//支付金额
                ->setTitle($title)
                ->setOrderNo($data['pay_order_number'])
                ->setService($data['service'])
                ->setSignType("MD5")
                ->setPayMethod("transaction")
                ->setTable($table)
                ->setPayWay($data['pay_way'])
                ->setUserId($user['id'])
                ->setAccount($user['account'])
                ->setFeeMoney($fee_money)
                ->setTransactionId($transaction['id'])
                ->setLockTime($lock_time)
                ->setBalanceStatus($request['is_balance']);
        // return $pay->buildRequestForm($vo);
        $this->redirect($pay->buildRequestForm($vo));
    }


    /**
     * @注销账号
     *
     * @author: zsl
     * @since: 2021/5/13 13:57
     */
    public function unsubscribe()
    {
        $this -> isLogin();
        $lUnsubscribe = new UnsubscribeLogic();
        $result = $lUnsubscribe -> unsubscribe(UID);
        if ($result['code'] == 0) {
            $this -> error($result['msg']);
        } else {
            $this -> success($result['msg']);
        }
    }


    /**
     * @取消注销
     *
     * @author: zsl
     * @since: 2021/5/13 17:45s
     */
    public function cancelUnsub()
    {
        $this -> isLogin();
        $lUnsubscribe = new UnsubscribeLogic();
        $result = $lUnsubscribe -> cancelUnsub(UID);
        if ($result['code'] == 0) {
            $this -> error($result['msg']);
        } else {
            $this -> success($result['msg']);
        }
    }

    /**
     * 发送短信验证码
     *
     * @author: Juncl
     * @time: 2021/09/07 10:46
     */
    public function verifyUnsub()
    {
        if(!$this->request->isPost()){
            $this->error('操作异常', 'Index/index');
        }
        $this->isLogin();
        $lUnsubscribe = new UnsubscribeLogic();
        $result = $lUnsubscribe->sendVerifySms(UID);
        if($result['status']){
            $this->success($result['msg'],'',$result['data']);
        }else{
            $this->error($result['msg']);
        }
    }

    /**
     * 验证发送邮箱/短信验证码
     *
     * @author: Juncl
     * @time: 2021/09/07 19:14
     */
    public function verifyCode()
    {
        if(!$this->request->isPost()){
            $this->error('非法操作');
        }
        $this->isLogin();
        $param = $this->request->param();
        $lUnsubscribe = new UnsubscribeLogic();
        $result = $lUnsubscribe->verifyCode($param['code'],$param['type'],$param['phone']);
        if($result['status']){
            $this->success($result['msg']);
        }else{
            $this->error($result['msg']);
        }
    }

}
