<?php

namespace app\sdksimplify\controller;

use cmf\controller\HomeBaseController;
use app\recharge\model\SpendBalanceModel;
use app\recharge\model\SpendModel;
use geetest31\CheckGeetestStatus;
use geetest31\GeetestLib;
use think\Cache;
use think\Db;


class BaseController extends HomeBaseController
{
    //初始验证
    protected function _initialize()
    {
        $action = request()->action();
        $array = ['get_user_status','welfare_detail','apple_alipay_pay', 'apple_weixin_pay', 'get_alipay_zmxy_return', 'apple_platform_pay', 'notice', 'pay_way', 'pay_success2', 'pay_success','protocol','coupon','get_pay_coupon','init','notice_detail'];
        if (!in_array($action, $array)) {
            if($action == 'set_head_portrait'){
                $this->request_data = $data = json_decode(base64_decode($this->request->param('key')), true);
            }else{
                $this->request_data = $data = json_decode(base64_decode(file_get_contents("php://input")), true);
            }
            app_auth_value();
            if (AUTH_USER != 1 || AUTH_PAY != 1 || AUTH_GAME != 1) {
                $this->set_message(1070, "购买权限不足");
            }
            // #判断数据是否为空
            if (empty($data) || empty($data['game_id'])) {
                $this->set_message(1071, "操作数据不能为空");
            }
            //判断sdk对应的版本-20210717-byh 1=旗舰 2=简化,3=海外
            $_sdk_type = get_game_sdk_type($data['game_id']);
            if($_sdk_type !=2){
                $this->set_message(1071, "SDK类型错误!");
            }
            #获取游戏key
            $game_data = Cache::get('sdk_game_data'.$data['game_id']);
            if(!$game_data){
                $game_data = Db::table('tab_game')->alias('g')
                        ->field('g.id,gs.access_key,g.game_name,g.game_appid,g.sdk_version,g.relation_game_id,g.is_force_real')
                        ->join(['tab_game_set' => 'gs'], 'g.id=gs.game_id')
                        ->where('g.game_status', 1)
                        ->where('g.id', $data['game_id'])
                        ->find();
                Cache::set('sdk_game_data'.$data['game_id'],$game_data);
            }
            if (empty($game_data)) {
                $this->set_message(1001, "游戏不存在或未通过审核");
            }

            //新版验证方式,增加时间戳验证
            $is_verify = get_game_sdk_verify($data['game_id']);
            if ($is_verify == '1') {
                $ip = get_client_ip();
                $t_tag = $ip.md5($data['t']);
                $accessRes = check_access_frequency($t_tag, 3600 * 24, 5);
                if (false === $accessRes) {
                    $this -> set_message(0, "fail", "请求失效,请重新发起请求");
                }
            }

            $md5Sign = $data['md5_sign'];
            unset($data['md5_sign']);
            $md5_sign = $this->encrypt_md5($data, $game_data["access_key"]);
            if ($md5Sign !== $md5_sign) {
                $this->set_message(1002, "验签失败，请核对游戏参数是否正确");
            }
            if ($data['user_id']) {
                if (empty($data['token'])) {
                    $this->set_message(1003, "账号信息丢失");
                }
                $user = get_user_entity($data['user_id'],false,'register_type,password,token,lock_status');
                $this->request_data['user_token'] = $user['token'];
                if($user['lock_status']!=1){
                    $this->set_message(1004, "用户已被锁定");
                }
                //验证token
                if(!password_verify($user['token'],$data['token'])){
                    $this->set_message(1004, "账号信息失效，请重新登录");
                }
                //验证密码
                if (in_array($user['register_type'], [0,1, 2, 7])) { //游客 普通账号 手机号 邮箱
                    $token = json_decode(think_decrypt($user['token'],1), true);
                    if ($user['password'] != $token['password']) {
                        $this->set_message(1004, "密码错误，请重新登录");
                    }
                }
            }
        }
    }

    /**
     * @函数或方法说明
     * @判断空操作
     * @author: 郭家屯
     * @since: 2020/1/8 10:38
     */
    public function _empty(){
        $this->set_message(1076, "404未找到");
    }
    /**
     * 返回输出
     * @param int $status 状态
     * @param string $return_msg 错误信息
     * @param array $data 返回数据
     * author: gjt 280564871@qq.com
     */
    public function set_message($code=200, $msg = '', $data = [], $type = 0)
    {
        $msg = array(
            "code" => $code,
            "msg" => $msg,
            "data" => $data
        );
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
     *对数据进行排序
     */
    private function arrSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     *MD5验签加密
     */
    public function encrypt_md5($param = "", $key = "")
    {
        #对数组进行排序拼接
        if (is_array($param)) {
            $md5Str = implode($this->arrSort($param));
        } else {
            $md5Str = $param;
        }
        $md5 = md5($md5Str . $key);
        return '' === $param ? 'false' : $md5;
    }

    /**
     *验证签名
     */
    public function validation_sign($encrypt = "", $md5_sign = "")
    {
        $signString = $this->arrSort($encrypt);
        $md5Str = $this->encrypt_md5($signString, $key = "");
        if ($md5Str === $md5_sign) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @极验验证码二次验证
     *
     */
    public function secondValidate($challenge, $validate, $seccode)
    {
        $gtLib = new GeetestLib(cmf_get_option('admin_set')['auto_verify_index'], cmf_get_option('admin_set')['auto_verify_admin']);
        $result = null;
        if (CheckGeetestStatus ::getGeetestStatus()) {
            $result = $gtLib -> successValidate($challenge, $validate, $seccode, null);
        } else {
            $result = $gtLib -> failValidate($challenge, $validate, $seccode);
        }
        // 注意，不要更改返回的结构和值类型
        if ($result -> getStatus() === 1) {
            return true;
        } else {
            return false;
        }
    }


}
