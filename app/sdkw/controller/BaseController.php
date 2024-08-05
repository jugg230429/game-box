<?php
/**
 * @Copyright (c) 2021  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License 江苏溪谷网络科技有限公司版权所有
 * @since 2021-04-16
 */
namespace app\sdkw\controller;

use app\sdkw\logic\BaseLogic;
use cmf\controller\HomeBaseController;
use Exception;
use think\Cache;
use think\Db;
use think\Lang;

class BaseController extends HomeBaseController
{
    /**
     * @var
     *
     * @descript 描述
     */
    protected $request_data;

    protected $lang = 'zh-cn';

    /**
     * 方法 _initialize
     *
     * @descript 描述
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/19 0019 9:21
     */
    protected function _initialize()
    {
        $action = request()->action();
        $array = [];
        if (!in_array($action, $array)) {

            $this->request_data = $data = json_decode(base64_decode(file_get_contents("php://input")), true);

            $this -> lang = $lang = self::language($data['language_type']);

            Lang::range($lang);
            Lang::load( APP_PATH .'sdkw'.DIRECTORY_SEPARATOR.'lang'. DIRECTORY_SEPARATOR .$lang.'.php', $lang);

            app_auth_value();

            if (AUTH_USER != 1 || AUTH_PAY != 1 || AUTH_GAME != 1) {
                $this->set_message(1070);
            }

            // #判断数据是否为空
            if (empty($data) || empty($data['game_id'])) {
                $this->set_message(1071);
            }
            //判断sdk对应的版本-20210717-byh 1=旗舰 2=简化,3=海外
            $_sdk_type = get_game_sdk_type($data['game_id']);
            if($_sdk_type !=3){
                $this->set_message(1071,[],0, "SDK类型错误!");
            }

            #获取游戏key
            $game_data = Cache::get('sdk_game_data'.$data['game_id']);
            if(!$game_data){
                try {
                    $game_data = Db::table('tab_game')->alias('g')
                        ->field('g.id,gs.access_key,g.game_name,g.game_appid,g.sdk_version,g.relation_game_id,g.is_force_real')
                        ->join(['tab_game_set' => 'gs'], 'g.id=gs.game_id')
                        ->where('g.game_status', 1)
                        ->where('g.id', $data['game_id'])
                        ->find();
                } catch (Exception $e) {
                    $game_data = '';
                }
                Cache::set('sdk_game_data'.$data['game_id'],$game_data);
            }

            if (empty($game_data)) {
                $this->set_message(1001);
            }

            //新版验证方式,增加时间戳验证
            $is_verify = get_game_sdk_verify($data['game_id']);
            if ($is_verify == '1') {
                $ip = get_client_ip();
                $t_tag = $ip.md5($data['t']);
                $accessRes = check_access_frequency($t_tag, 3600 * 24, 5);
                if (false === $accessRes) {
                    $this -> set_message(0);
                }
            }

            $md5Sign = $data['md5_sign'];
            unset($data['md5_sign']);

            $BaseLogic = new BaseLogic();

            $md5_sign = $BaseLogic->encrypt_md5($data, $game_data["access_key"]);
            if ($md5Sign !== $md5_sign) {
                $this->set_message(1002);
            }

            if ($data['user_id']) {
                if (empty($data['token'])) {
                    $this->set_message(1003);
                }
                $user = get_user_entity($data['user_id'],false,'register_type,password,token,lock_status');
                $this->request_data['user_token'] = $user['token'];
                if($user['lock_status']!=1){
                    $this->set_message(1004);
                }
                //验证token
                if(!password_verify($user['token'],$data['token'])){
                    $this->set_message(1101);
                }
                //验证密码
                if (in_array($user['register_type'], [0,1, 2, 7])) { //游客 普通账号 手机号 邮箱
                    $token = json_decode(think_decrypt($user['token'],1), true);
                    if ($user['password'] != $token['password']) {
                        $this->set_message(1102);
                    }
                }
            }
        }
    }

    /**
     * 方法 _empty
     *
     * @descript 空操作
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 9:43
     */
    public function _empty(){
        $this->set_message(1076);
    }

    /**
     * 方法 set_message
     *
     * @descript 响应
     *
     * @param int $code
     * @param array $data
     * @param int $type
     * @param string $message
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 11:01
     */
    protected function set_message($code=200, $data = [], $type = 0, $message = '')
    {
        $msg = array(
            "code" => self::checkSuccess($code),
            "msg" => $message?:lang(self::errorInfo($code))
        );
        if (!empty($data)) {
            $msg['data'] = $data;
        }
        if ($type == 1) {
            echo base64_encode(json_encode($msg, JSON_FORCE_OBJECT));
        } elseif ($type == 2) {
            echo base64_encode(json_encode($msg, true));
        } else {
            echo base64_encode(json_encode($msg,JSON_PRESERVE_ZERO_FRACTION));
        }
        exit;
    }

    /**
     * 方法 response
     *
     * @descript 响应
     *
     * @param array $data
     * @param int $type
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 9:39
     */
    protected function response($data=[], $type=0)
    {
        if (is_array($data)) {
            $this->set_message($data['code'], $data['data'], $type, $data['msg']?:'');
        } else {
            $this->set_message($data);
        }
    }

    /**
     * 方法 input
     *
     * @descript 请求数据
     *
     * @return mixed
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 11:01
     */
    protected function input()
    {
        return json_decode(base64_decode(file_get_contents("php://input")), true);
    }

    /**
     * 方法 errorInfo
     *
     * @descript 错误信息
     *
     * @param $code
     * @return string
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/19 0019 13:32
     */
    private static function errorInfo($code)
    {
        $message = [
            0 => 'FAIL',  // 失败
            200 => 'SUCCESS', // 成功
            201 => 'SUCCESS_SENT_VERIFY', // 验证码发送成功
            202 => 'SUCCESS_GET', // 获取成功
            203 => 'SUCCESS_GET_PASSWORD', // 找回密码成功
            204 => 'SUCCESS_PASSWORD_MODIFY', // 修改密码成功
            205 => 'SUCCESS_BIND', // 绑定成功
            206 => 'SUCCESS_UPLOAD_ROLE', // 上传角色成功
            207 => 'SUCCESS_PAY', // 支付成功
            208 => 'SUCCESS_SEND_SHORT_MESSAGE', // 短信已发送，请注意查收
            209 => 'SUCCESS_SEND_CAPTCHA', // 验证码已发送，请注意查收
            1001 => 'GAME_NO_EXIST_OR_NO_APPROVED', // 游戏不存在或未通过审核
            1002 => 'CHECK_FAIL', // 验签失败，请核对游戏参数是否正确
            1003 => 'ACCOUNT_INFO_IS_EMPTY', // 账号信息丢失
            1004 => 'USER_IS_LOCKED', // 用户已被锁定
            1005 => 'USER_IS_EMPTY', // 用户不存在
            1006 => 'PASSWORD_IS_WRONG', // 密码错误
            1007 => 'ACCOUNT_IS_LOCKED', // 账号被锁定
            1008 => 'UNKNOWN_REGISTER_TYPE', // 未知注册类型
            1009 => 'ACCOUNT_IS_EMPTY', // 账号不能为空
            1010 => 'PASSWORD_IS_EMPTY', // 密码不能为空
            1011 => 'ACCOUNT_FORMAT_ERROR', // 账号名称格式错误
            1012 => 'PASSWORD_FORMAT_ERROR', // 账号密码格式错误
            1013 => 'ACCOUNT_NAME_EXIST', // 账号名称已存在
            1014 => 'MOBILE_REGISTERED_OR_BOUND', // 该手机号已被注册或绑定过
            1015 => 'FAIL_SENT_VERIFY_AGAIN', // 验证码发送失败，请重新获取
            1016 => 'SMS_NO_CONFIG', // 没有配置短信发送
            1017 => 'GET_VERIFY', // 请先获取验证码
            1018 => 'VERIFY_IS_INVALID_AGAIN', // 验证码已失效，请重新获取
            1019 => 'VERIFICATION_CODE_IS_ERROR', // 验证码错误或已过期
            1020 => 'MOBILE_FORMAT_ERROR', // 手机号码格式错误
            1021 => 'EMAIL_FORMAT_ERROR', // 邮箱地址格式错误
            1022 => 'EMAIL_REGISTERED_OR_BOUND', // 该邮箱已被注册或绑定过
            1023 => 'FAIL_SENT_EMAIL_IS_CORRECT', // 发送失败，请检查邮箱地址是否正确
            1024 => 'SENT_VERIFY_LATER', // 验证码发送过于频繁，请稍后再试
            1025 => 'MOBILE_IS_EMPTY', // 手机号不能为空
            1026 => 'CAPTCHA_TIMEOUT', // 验证码超时
            1027 => 'EMAIL_IS_EMPTY', // 邮箱不能为空
            1028 => 'REGISTER_FAIL', // 注册失败
            1029 => 'ACCOUNT_NO_BOUND_MOBILE_OR_EMAIL', // 账号未绑定手机号或邮箱
            1030 => 'UNKNOWN_GET_PASSWORD_METHOD', // 未知找回密码方式
            1031 => 'FAIL_GET_PASSWORD', // 找回密码失败
            1037 => 'FAIL_MODIFY', // 修改失败
            1038 => 'NEW_PASSWORD_NO_EQ_ORIGINAL_PASSWORD', // 新密码与原始密码不能相同
            1039 => 'ORIGINAL_PASSWORD_IS_INCORRECT', // 原始密码不正确
            1044 => 'SMS_NUMBER_IS_EMPTY', //短信数量已经使用完
            1050 => 'FAIL_PRIVILEGE_GRANT', //授权失败
            1053 => 'PARAMETER_NOT_CONFIGURED', //服务器未配置此参数
            1055 => 'DUPLICATE_ORDER_NUMBER_AGAIN', // 订单号重复，请关闭支付页面重新支付
            1056 => 'DUPLICATE_VOUCHER', // 凭证重复
            1057 => 'FAIL_PAY_STATUS', // 支付状态修改失败
            1058 => 'FAIL_PAY', // 支付失败
            1059 => 'UNKNOWN_SEND_TYPE', // 未知发送类型
            1061 => 'RECHARGE_AMOUNT_ERROR', //充值金额有误
            1062 => 'CREDIT_IS_LOW', //余额不足
            1063 => 'UNKNOWN_GET_PAYMENT_METHOD', //支付方式不明确
            1064 => 'SERVICE_NUMBER_IS_EMPTY', // 请上传区服编号
            1065 => 'SERVICE_NAME_IS_EMPTY', // 请上传区服名称
            1066 => 'ROLE_NAME_IS_EMPTY', // 请上传角色名称
            1068 => 'ROLE_LEVEL_IS_EMPTY', // 请上传角色等级
            1069 => 'FAIL_UPLOAD_ROLE', // 上传角色失败
            1070 => 'INSUFFICIENT_PURCHASE_AUTHORITY', // 购买权限不足
            1071 => 'OPERATION_DATA_IS_EMPTY', // 操作数据不能为空
            1074 => 'IP_IS_RESTRICTED', // 暂时无法注册，请联系客服
            1076 => 'OPERATION_DATA_IS_EMPTY', // 404未找到
            1079 => 'USER_DATA_IS_EMPTY', // 用户数据不能为空
            1101 => 'ACCOUNT_IS_INVALID', // 账号信息失效，请重新登录
            1102 => 'PASSWORD_IS_INVALID', // 密码错误，请重新登录
            1103 => 'CAPTCHA_VALIDATION_FAILED', // 验证码验证失败
            1104 => 'CONFIRM_PASSWORD_NOT_MATCH', // 确认密码和密码不匹配
            1105 => 'UNKNOWN_BIND_TYPE', // 未知绑定类型
            1106 => 'FAIL_BIND', // 绑定失败
            1107 => 'REQUESTS_FREQUENT_AGAIN', // 请求过于频繁,请稍后再试
            1108 => 'UNKNOWN_LOGIN_TYPE', // 未知登录类型
            1109 => 'FAIL_VERIFICATION', // 验证失败
            1110 => 'FAIL_LOGIN', // 登录失败
            1111 => 'FAIL_ORDER', // 下单失败
            1112 => 'PLEASE_INPUT_CORRECT_MOBILE_NUMBER', // 请输入正确的手机号
            1113 => 'ACCOUNT_NO_EXIST_OR_DISABLED', // 账号不存在或被禁用
            1114 => 'ACCOUNT_LETTER_OR_NUMBER_SIX_FIFTEEN', // 账号为6-15位字母或数字组合
            1115 => 'PASSWORD_LETTER_OR_NUMBER_SIX_FIFTEEN', // 密码为6-15位字母或数字组合
            1116 => 'OLD_PASSWORD_IS_EMPTY', // 原密码不能为空
            1117 => 'FAIL_PASSWORD_MODIFY', // 修改密码失败
            1118 => 'CAPTCHA_IS_EMPTY', // 验证码不能为空
            1119 => 'MOBILE_NUMBER_IS_USE', // 手机号已被使用
            1120 => 'EMAIL_IS_USE', // 电子邮件已被使用
            1121 => 'PLEASE_INPUT_CORRECT_EMAIL', // 请输入正确的电子邮件
            1122 => 'NO_APPLE_IN_APP', // 未配置苹果内购充值
            1123 => 'ACCOUNT_NO_BOUND_MOBILE_OR_EMAIL_CONTACT', // 该账号暂未绑定手机号/邮箱，请联系客服
            1124 => 'ACCOUNT_IS_USE', // 账号已被使用
            1125 => 'VOUCHER_IS_EMPTY', // 凭证不存在
            1126 => 'RECHARGE_IS_CURRENTLY_PROHIBITED', // 当前被禁止充值，请联系客服
            1127 => 'REGISTER_IS_CURRENTLY_PROHIBITED', // 当前被禁止注册，请联系客服
            1128 => 'LOGIN_IS_CURRENTLY_PROHIBITED', // 当前被禁止登录，请联系客服
        ];
        return $message[$code];
    }

    /**
     * 方法 language
     *
     * @descript 语言
     *
     * @param int $type
     * @return string
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/19 0019 13:32
     */
    private static function language($type=0)
    {
        $language = [
            0 => 'zh-cn',
            1 => 'en',
            2 => 'zh-tw',
            3 => 'jp',
            4 => 'ko'
        ];
        return $language[$type];
    }

    /**
     * 方法 checkSuccess
     *
     * @descript 归拢所有2开头的三位成功编号为200
     *
     * @param int $code
     * @return int|mixed
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 16:12
     */
    private static function checkSuccess($code=200) {
        return strlen($code) == 3 && substr($code, 0, 1) == 2 ? 200 : $code;
    }

}
