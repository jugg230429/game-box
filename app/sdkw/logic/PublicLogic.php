<?php
/**
 * @Copyright (c) 2021  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License 江苏溪谷网络科技有限公司版权所有
 * @since 2021-04-19
 */
namespace app\sdkw\logic;

use app\extend\model\MsgModel;
use app\member\model\UserModel;
use app\member\model\UserPlayModel;
use app\site\model\ProtocolModel;
use geetest31\GeetestLib;
use think\Cache;
use think\Db;
use think\Exception;
use think\xigusdk\Xigu;

class PublicLogic extends BaseLogic
{
    /**
     * 方法 common
     *
     * @descript 公共参数
     *
     * @param $request
     * @return array
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/19 0019 19:51
     */
    public function common($request)
    {
        $data = [];
        $set = cmf_get_option('sdkw_set');
        $fb = get_game_login_param($request['game_id'], 3);
        // 登录方式开关
        $data['login_apple_status'] = $set['login_apple_status'] ? : 0;
        $data['login_fb_status'] = !empty($fb) ? 1 : 0;
        $data['login_visitor_status'] = $set['login_visitor_status'] ? : 0;
        // 注册方式开关
        $data['register_account_status'] = $set['register_account_status'] ? : 0;
        $data['register_mobile_status'] = $set['register_mobile_status'] ? : 0;
        $data['register_email_status'] = $set['register_email_status'] ? : 0;
        // 联系客服QQ
        $data['qq'] = $set['qq'] ? : '';
        // 用户注册协议的URL和协议标题
        $ProtocolModel = new ProtocolModel();
        $title = $ProtocolModel -> getProtocolTitle(['language' => $request['language_type']]);
        $data['protocol_title'] = $title ?:'用户注册协议';
        // 悬浮球图标
        $data['suspend_show_status'] = $set['suspend_show_status'] ? : 0;
        $data['suspend_icon'] = !empty($set['suspend_icon']) ? cmf_get_asset_url($set['suspend_icon']): '';
        // 退出按钮
        $data['loginout_status'] = $set['loginout_status'] ? : 0;
        return ['code'=>200,'data'=>$data];
    }

    /**
     * 方法 login
     *
     * @descript 账密登录
     *
     * @param $data
     * @return array|int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 11:26
     */
    public function login($data)
    {
        //验证验证码
        if($this -> verifyValidate($data)) {
            return 1103;
        }
        if(empty($data['account'])) {
            return 1009;
        }
        if(empty($data['password'])) {
            return 1010;
        }
        $account = $data['account'];
        $pattern = '/^[A-Za-z0-9_]{6,15}$/';
        if (strlen($account)==11 && is_numeric($account)) {
            if (!cmf_check_mobile($account)) {
                return 1112;
            }
        } else if (strpos($account, '@') !== false) {
            if (!cmf_check_email($account) && !filter_var($account, FILTER_VALIDATE_EMAIL)) {
                return 1121;
            }
        } else {
            if(!preg_match($pattern, $account)) {
                return 1114;
            }
        }
        if(!preg_match($pattern, $data['password'])) {
            return 1115;
        }
        $model = new UserModel();
        $data['type'] = 1;//游戏登录
        $user = $this->loginDataDeal($model->login($data, 0, 'sdk'), $data);

        //封禁判断-20210713-byh
        if(!judge_user_ban_status(0,$data['game_id'],$user['id'],$data['equipment_num'],get_client_ip(),$type=1)){
            return 1128;//当前被禁止登录，请联系客服
        }
        return $user;
    }

    /**
     * 方法 third
     *
     * @descript 第三方登录（苹果登录，脸书登录）
     *
     * @param $request
     * @return array|int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/25 0025 9:39
     */
    public function third($request)
    {
        //验证验证码
        if($this -> verifyValidate($request)) {
            return 1103;
        }
        $game_data = Cache::get('sdk_game_data'.$request['game_id']);
        switch ($request['login_type']) {
            case 'ap':
                $result = $this -> apple($request);
                if (!$result) {
                    return 1109;
                }
                $unionid = $request['userID'];
                $register_type = 8;
                break;
            case 'fb':
                $result = $this -> facebook($request);
                if (!is_array($result) || !$result['is_valid']) {
                    return 1109;
                }
                $unionid = $request['userID'];
                $register_type = 9;
                break;
            default:
                return 1108;
        }

        $map['unionid'] = $unionid;
        $map['register_type'] = $register_type;

        $model = new UserModel();
        try {
            $data = $model->field('id,account')->where($map)->find();
        } catch (\Exception $e) {
            $data = [];
        }
        if (empty($data)) {// 注册
            //封禁判断-20210713-byh
            if(!judge_user_ban_status(0,$request['game_id'],'',$request['equipment_num'],get_client_ip(),$type=1)){
                return 1128;//当前被禁止登录，请联系客服
            }
            if (!empty($request['email'])) {
                $data['account'] = $request['email'];
            } else {
                do {
                    try {
                        $data['account'] = $request['login_type'] . '_' . sp_random_string();
                        $account = $model->field('id')->where(['account' => $data['account']])->find();
                    } catch (\Exception $e) {
                        $account = '';
                    }
                } while (!empty($account));
            }
            try {
                $data['password'] = sp_random_string(8);
                $data['nickname'] = !empty($request['fullName']) ? $request['fullName'] : $data['account'];
                $data['unionid'] = $unionid;
                $data['game_id'] = $request['game_id'];
                $data['head_img'] = !empty($head_img) ? $head_img : '';//头像
                $data['game_name'] = $game_data['game_name'];
                $data['promote_id'] = !empty($request['promote_id']) ? $request['promote_id'] : 0;
                $data['register_way'] = 1;
                $data['register_type'] = $register_type;
                $data['type'] = 1;
                $data['equipment_num'] = $request['equipment_num'];
                $data['device_name'] = $request['device_name'] ?: '';
                return $this->registerDataDeal($data);
            } catch (\Exception $e) {
                return 1110;
            }
        } else {// 登录
            try {
                $request['type'] = 1;
                if (empty($request['account'])) {
                    $request['account'] = $data['account'];
                }
                $user = $this->loginDataDeal($model->oauth_login($request, 'sdk'), $request);
                //封禁判断-20210713-byh
                if(!judge_user_ban_status(0,$request['game_id'],$user['id'],$request['equipment_num'],get_client_ip(),$type=1)){
                    return 1128;//当前被禁止登录，请联系客服
                }
            } catch (\Exception $e) {
                return 1110;
            }
        }
    }

    /**
     * 方法 visitor
     *
     * @descript 遊客登錄
     *
     * @param $request
     * @return array|int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 10:53
     */
    public function visitor($request)
    {
        //验证验证码
        if($this -> verifyValidate($request)) {
            return 1103;
        }

        //验证注册ip
        if(!checkregiserip()){
            return 1074;
        }

        $model = new UserModel();

        if (!empty($request['account'])) {
            if(empty($request['account'])) {
                return 1009;
            }
            if(empty($request['password'])) {
                return 1010;
            }
            $reg = '/^[A-Za-z0-9_]{6,15}$/';
            if(!preg_match($reg, $request['account'])) {
                return 1114;
            }
            if(!preg_match($reg, $request['password'])) {
                return 1115;
            }
            $game_id = $request['game_id'];
            $game_data = Cache::get('sdk_game_data'.$game_id);
            try {
                $data['account'] = $request['account'];
                $account = $model->field('id')->where(['account' => $data['account']])->find();
                //封禁判断-20210713-byh
                if(!judge_user_ban_status(0,$request['game_id'],$account['id'],$request['equipment_num'],get_client_ip(),$type=1)){
                    return 1128;//当前被禁止登录，请联系客服
                }
                if (empty($account)) {
                    $data['password'] = $request['password'];
                    $data['nickname'] = $request['account'];
                    $data['game_id'] = $game_id;
                    $data['head_img'] = !empty($head_img) ? $head_img : '';//头像
                    $data['game_name'] = $game_data['game_name'];
                    $data['promote_id'] = !empty($request['promote_id']) ? $request['promote_id'] : 0;
                    $data['register_way'] = 1;
                    $data['register_type'] = 0;
                    $data['type'] = 1;
                    $data['equipment_num'] = $request['equipment_num'];
                    $data['device_name'] = $request['device_name']?:'';
                    return $this -> registerDataDeal($data);
                } else {
                    return 1013;
                }
            } catch (Exception $e) {
                return 1003;
            }
        } else { // 生成账密
            //封禁判断-20210713-byh
            if(!judge_user_ban_status(0,$request['game_id'],'',$request['equipment_num'],get_client_ip(),$type=1)){
                return 1128;//当前被禁止登录，请联系客服
            }
            $data['password'] = sp_random_string(8);
            do {
                $data['account'] = 'yk_' . sp_random_string();
                try {
                    $account = $model->field('id')->where(['account' => $data['account']])->find();
                } catch (\Exception $e) {
                    $account = '';
                }
            } while (!empty($account));
            return [
                'code' => 200,
                'data' => $data
            ];
        }
    }

    /**
     * 方法 register
     *
     * @descript 注册
     *
     * @param $data
     * @return array|int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 17:46
     */
    public function register($data)
    {
        //验证验证码
        if($this -> verifyValidate($data)) {
            return 1103;
        }
        //封禁判断-20210713-byh-海外注册没有渠道参数,默认都是0
        if(!judge_user_ban_status(0,$data['game_id'],'',$data['equipment_num'],get_client_ip(),$type=2)){
            return 1127;//当前被禁止注册，请联系客服
        }

        //验证注册ip
        if(!checkregiserip()){
            return 1074;
        }
        $reg = '/^[A-Za-z0-9]{6,15}$/';
        switch ($data['type']) {
            case 1:
                if (empty($data['account'])) {
                    return 1009;
                }
                if (!preg_match($reg, $data['account'])) {
                    return 1114;
                }
                if (checkAccount($data['account'])) {
                    return 1124;
                }
                $data['register_type'] = 1;
                break;
            case 2:
                if (empty($data['account'])) {
                    return 1025;
                }
                if (!cmf_check_mobile($data['account'])) {
                    return 1112;
                }
                if (checkAccount($data['account'])) {
                    return 1119;
                }
                //手机号注册
                $code = $this -> smsVerify($data['account'], $data['code']);
                if ($code) {
                    return $code;
                }
                $data['phone'] = $data['account'];
                $data['register_type'] = 2;
                break;
            case 3:
                if (empty($data['account'])) {
                    return 1027;
                }
                if (!cmf_check_email($data['account']) && !filter_var($data['account'], FILTER_VALIDATE_EMAIL)) {
                    return 1121;
                }
                if (checkAccount($data['account'])) {
                    return 1120;
                }
                //邮箱注册
                $code = $this -> emailVerify($data['account'], $data['code']);
                if ($code) {
                    return $code;
                }
                $data['email'] = $data['account'];
                $data['register_type'] = 7;
                break;
            default:
                return 1008;
        }
        if (empty($data['password'])) {
            return 1010;
        }
        if(!preg_match($reg, $data['password'])) {
            return 1115;
        }

        $data['register_way'] = 1;
        $game = Cache::get('sdk_game_data'.$data['game_id']);
        $data['game_name'] = $game['game_name'];
        $data['type'] = 1;
        return $this -> registerDataDeal($data);
    }

    /**
     * 方法 sms
     *
     * @descript 发送短信
     *
     * @param $request
     * @return int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 15:57
     */
    public function sms($request)
    {
        try {
            $phone = $request['phone'];
            $account = $request['account'];
            if (!cmf_check_mobile($phone)) {
                return 1112;
            }
            $userModel = new UserModel();
            if ($request['reg'] == 1) { /* 注册检查 */
                $user = $userModel->where('phone|account', $phone)->find();
                if ($user) {
                    return 1014;
                }
            } elseif ($request['reg'] == 2) {/* 忘记密码检查 */
                $user = $userModel->where('account', $account)->where('phone', $phone)->find();
                if (empty($user)) {
                    return 1005;
                }

            } elseif ($request['reg'] == 3) {/* 解绑绑定检查 */
                $user = $userModel->where('account', $account)->where('phone', $phone)->find();
                if (empty($user)) {
                    return 1005;
                }
            } elseif ($request['reg'] == 4) {/* 绑定手机检查  */
                $user = $userModel->where('phone', $phone)->find();
                if ($user) {
                    return 1014;
                }
            } else {
                return 1059;
            }
            $msg = new MsgModel();
            $data = $msg::get(1);
            if (empty($data)) {
                return 1016;
            }
            $data = $data->toArray();
            if ($data['status'] == 1) {
                $xigu = new Xigu($data);
                sdkchecksendcode($phone, $data['client_send_max'], 2);
                $sms_code = session($phone);//发送没有session 每次都是新的
                $sms_rand = sms_rand($phone);
                $rand = $sms_rand['rand'];
                $new_rand = $sms_rand['rand'];
                /// 产生手机安全码并发送到手机且存到session
                $param = $rand . "," . '3';
                $result = json_decode($xigu->sendSM($phone, $data['captcha_tid'], $param), true);
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
//                    Cache::set($phone, $safe_code);
                    return 208;
                } else {
                    return 1015;
                }
            } else {
                return 1016;
            }
        } catch (Exception $e) {
            return  1015;
        }
    }

    /**
     * 方法 email
     *
     * @descript 发送邮箱验证码
     *
     * @param $data
     * @return int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 16:00
     */
    public function email($data)
    {
        $code_type = $data['code_type'];
        $email = $data['email'];
        $account = $data['account'];

        if (!cmf_check_email($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 1121;
        }
        try {
            $usermodel = new UserModel();
            if ($code_type == 1) {/* 注册 */
                $user = $usermodel->where('email|account', $email)->find();
                if ($user) {
                    return 1022;
                }
            } elseif ($code_type == 2) {/* 忘记密码 */
                $user = $usermodel->where('account', $account)->where('email', $email)->find();
                if (empty($user)) {
                    return 1005;
                }
            } elseif ($code_type == 3) {/* 解绑 */
                $user = $usermodel->where('account', $account)->where('email', $email)->find();
                if (empty($user)) {
                    return 1005;
                }
            } elseif ($code_type == 4) {/* 绑定 */
                $user = $usermodel->where('email|account', $email)->find();
                if ($user) {
                    return 1022;
                }
            }
            $session = session($data['email']);
            if (!empty($session) && (time() - $session['create_time']) < 60) {
                return 1024;
            }
            $code = rand(100000, 999999);
            $smtpSetting = cmf_get_option('email_template_verification_code');
            $smtpSetting['template'] = str_replace('{$code}', $code, htmlspecialchars_decode($smtpSetting['template']));
            $result = cmf_send_email($data['email'], $smtpSetting['subject'], $smtpSetting['template']);
            if ($result['error'] == 0) {
                session($email, ['code' => $code, 'create_time' => time()]);
//                Cache::set($email, ['code' => $code, 'create_time' => time()]);
                return 209;
            } else {
                return 1023;
            }
        } catch (\Exception $e) {
            return 1015;
        }
    }

    /**
     * 方法 verify
     *
     * @descript 驗證驗證碼
     *
     * @param $request
     * @return int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/27 0027 15:48
     */
    public function verify($request)
    {
        if (empty($request['code'])) {
            return 1118;
        }
        switch ($request['verify_type']) {
            case 1:
                $code = $this -> smsVerify($request['account'], $request['code']);
                if ($code) {
                    return $code;
                }
                break;
            case 2:
                $code = $this -> emailVerify($request['email'], $request['code']);
                if ($code) {
                    return $code;
                }
                break;
            default:
                return 1030;
        }
        return 200;
    }

    /**
     * 方法 forget
     *
     * @descript 忘记密码-查询当前账号是否绑定手机号/邮箱
     *
     * @param $data
     * @return array|int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 17:26
     */
    public function forget($data)
    {
        $account = $data['account'];
        if (empty($account)) {
            return 1009;
        }
        $pattern = '/^[A-Za-z0-9]{6,15}$/';
        if (strlen($account)==11 && is_numeric($account)) {
            if (!cmf_check_mobile($account)) {
                return 1112;
            }
        } else if (strpos($account, '@') !== false) {
            if (!cmf_check_email($account) && !filter_var($account, FILTER_VALIDATE_EMAIL)) {
                return 1121;
            }
        } else {
            if(!preg_match($pattern, $account)) {
                return 1114;
            }
        }
        $model = new UserModel();
        try {
            $user = $model->where('account', $account)
                ->field('id,account,phone,email,lock_status')
                ->find();
        } catch (\Exception $e) {
            $user = '';
        }
        if (empty($user)) {
            return 1113;
        }
        if($user['lock_status'] == 0){
            return 1113;
        }
        if (empty($user['phone']) && empty($user['email'])) {
            return 1123;
        }
        $res_msg = array(
            "phone" => $user['phone'],
            "email" => $user['email'],
        );
        return [
            'code' => 202,
            'data' => $res_msg
        ];
    }

    /**
     * 方法 password
     *
     * @descript 忘记密码-重置密码
     *
     * @param $user
     * @return int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 17:43
     */
    public function password($user)
    {
        if (empty($user['account'])) {
            return 1009;
        }
        if (empty($user['password'])) {
            return 1010;
        }
        $reg = '/^[A-Za-z0-9]{6,15}$/';
        if(!preg_match($reg, $user['password'])) {
            return 1115;
        }
        $model = new UserModel();
        $user['user_id'] = get_user_entity($user['account'],true,'id')['id'];
        $result = $model->updatePassword($user['user_id'], $user['password']);
        if ($result == true) {
            return 203;
        } else {
            return 1031;
        }
    }

    /**
     * 方法 add_user_play
     *
     * @descript 添加游戏信息
     *
     * @param array $user
     * @param array $request
     * @param int $register
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 11:34
     */
    private function add_user_play($user = array(), $request = array(),$register = 0)
    {
        //查询是否存在账号
        $game = Cache::get('sdk_game_data'.$request['game_id']);
        $map["game_id"] = $request["game_id"];
        $map["user_id"] = $user["id"];
        $model = new UserPlayModel();
        $model->login($map,$user,$game,$register);
    }

    /**
     * 方法 verifyValidate
     *
     * @descript 验证验证码
     *
     * @param $data
     * @return bool
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 14:22
     */
    private function verifyValidate($data)
    {
        $sdk_verify = get_game_sdk_verify($data['game_id']);
        if ($sdk_verify) {
            $vResult = $this -> secondValidate($data[GeetestLib::GEETEST_CHALLENGE], $data[GeetestLib::GEETEST_VALIDATE], $data[GeetestLib::GEETEST_SECCODE]);
            if (false === $vResult) {
                return true;
            }
        }
        return false;
    }

    /**
     * 方法 loginDataDeal
     *
     * @descript 登录数据处理
     *
     * @param $result
     * @param $data
     * @return array
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 13:55
     */
    private function loginDataDeal($result, $data)
    {
        if (is_array($result)) {
            $this->add_user_play($result, $data);//大号也创建玩家 统计时不计算小号的
            return [
                'code' => 200,
                'data' => [
                    "user_id" => $result["id"],
                    "account" => $result["account"],
                    "nickname" => $result["nickname"] ? $result["nickname"] : $result["account"],
                    "token" => password_hash($result['token'],PASSWORD_DEFAULT),
                ]
            ];
        } else {
            return $result == 1006? $result: 1113;
        }
    }

    /**
     * 方法 registerDataDeal
     *
     * @descript 注册数据处理
     *
     * @param $data
     * @return array|int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/25 0025 9:28
     */
    private function registerDataDeal($data)
    {
        $model = new UserModel();
        $result = $model->register($data, 'sdk');
        if ($result == -1) {
            return 1028;
        } else {
            $this->add_user_play($result, $data, 1);
            $return = [
                "user_id" => $result["id"],
                "account" => $result["account"],
                "nickname" => $result["nickname"] ? $result["nickname"] : $result["account"],
                "token" => password_hash($result['token'], PASSWORD_DEFAULT),
            ];
            return [
                'code' => 200,
                'data' => $return
            ];
        }
    }

    /**
     * 方法 apple
     *
     * @descript 苹果登录验签
     *
     * @param $request
     * @return int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/25 0025 9:39
     */
    private function apple($request)
    {
        try {
            $userId = $request['userID'];
            $identityToken = $request['identityToken'];
            $appleSignInPayload = \app\sdkw\service\ASDecoder::getAppleSignInPayload($identityToken);
            return $appleSignInPayload->verifyUser($userId);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 方法 facebook
     *
     * @descript 脸书登录
     *
     * @param $request
     * @return bool|int|string
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/25 0025 9:28
     */
    private function facebook($request)
    {
        $fb = get_game_login_param($request['game_id'], 3);
        if (empty($fb)) {
            return 1109;
        }
        $appToken = $fb['wx_appid'] . '%7C' . $fb['appsecret'];

        $url = '//graph.facebook.com/debug_token?input_token='.$request['accessToken'].'&access_token=';

//        $url = 'https://graph.facebook.com/'.$request['userID'].'?access_token='.$appToken;
//        $result = $this->sendGet($url);
//        file_put_contents(__DIR__ . '/fb4.txt', json_encode($result));
//        return $result;

        //$url = 'https://graph.facebook.com/v10.0/debug_token?access_token='.$appToken.'&input_token='.$request['accessToken'];
//var_dump($this->sendGet($url));die();

        //$access_token_info = json_decode(file_get_contents($url));
        //var_dump($access_token_info);die();
        /*$url = 'https://graph.facebook.com/oauth/access_token?client_id='.$fb['wx_appid'].'&client_secret='.$fb['appsecret'].' &grant_type=client_credentials';
        $access_token_info = json_decode(file_get_contents($url));
        var_dump($access_token_info);*/
//            $url='https://graph.facebook.com/'.$request['accessToken'].'&access_token='.$appToken;
//            var_dump($url);die();
//        $access_token_info = json_decode(file_get_contents($url));
//        var_dump($access_token_info);
//        $access_token = $request['accessToken'];
//        $graph_url = 'https://graph.facebook.com/oauth/access_token_info?'
//            . 'client_id='.$fb['wx_appid'].'&access_token=' . $access_token;
//        $access_token_info = json_decode(file_get_contents($graph_url));
//        var_dump($access_token_info);

//        //$url = 'https://graph.facebook.com/debug_token?access_token='.$appToken.'&input_token='.$request['accessToken'];
//        $url = 'https://graph.facebook.com/v10.0/oauth/access_token?client_id='.$fb['wx_appid'].'&client_secret='.$fb['appsecret'].'&code='.$request['accessToken'];
//        try {
//            return $this->sendGet($url);
//        } catch (\Exception $e) {
//            return 1109;
//        }
    }

}
