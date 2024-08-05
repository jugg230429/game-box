<?php

namespace app\app\controller;

use cmf\controller\HomeBaseController;
use cmf\org\RedisSDK\RedisController as Redis;
use think\Db;


class BaseController extends HomeBaseController
{
    //初始验证
    protected function _initialize()
    {
        $this->request_data = $data = $this->request->param();
        //file_put_contents(dirname(__file__).'/aaa.txt',json_encode($data));
        $sign = $data['md5_sign'];
        unset($data['md5_sign']);
        app_auth_value();
        $action = request()->action();
        $controller = request()->controller();
        if($controller == 'Pay' || ($controller == 'Article' && $action == 'detail') || $controller == 'Downfile' || ($controller == 'User' && ($action == 'protocol_html' || $action == 'privacy_html' || $action == 'imageverify' || $action == 'getimageverify' || $action == 'get_protocol'))){
        }else{
            $encrypt = $this->validation_sign($data,$sign);
            if(!$encrypt){
                $this->set_message(1001,'验签失败');
            }
        }
        $actions = [
               'one_click_login','oauth_login','is_ban','thirdparty','login','phone_login','register','send_sms','send_email','sendcode','protocol','forget_account','forget_password','gift','giftdetail','about_us','checkphonecode','privacy_html','protocol_html','imageverify','getimageverify','get_protocol'
        ];
        $controllers = ['Game','Article','Downfile','Pay'];
        if (!in_array($action, $actions) && !in_array($controller,$controllers)) {
            $this->auth($data['token']);
        }
        $this->request_data['user_token'] = $data['token'];
        $data['model'] = $data['model'] ? : 1;
        define("USER_ID",$data['user_id']);
        define("PROMOTE_ID",$data['promote_id']);

        define("MODEL",$data['model']);
    }

    /**
     * 验证用户登录token
     * @param $token
     * author: xmy 280564871@qq.com
     */
    protected function auth($token){
        $original_token = $token;
        $token = think_decrypt($token,1);
        if(empty($token)){
            $this->set_message(1002,"信息过期，请重新登录");
        }
        $info = json_decode($token,true);
        if(!$info['uid']){
            $this->set_message(1003,"信息过期，请重新登录");
        }

        $user = get_user_entity($info['uid'], false, 'password,sso');
        if($info['password'] != $user['password']){
            $this->set_message(1002,"密码不正确，请重新登录");
        }
        // 单点登录验证
        $data = cmf_get_option('admin_set');
        if ($user['sso'] == '1') {
            // 用户单点登录开关开启
            $this -> sso_check($info['uid'], $original_token);
        } elseif ($data['sso'] == '1') {
            // 全局单点登录开关开启
            $this -> sso_check($info['uid'], $original_token);
        }

    }
    /**
     * @函数或方法说明
     * @判断空操作
     * @author: 郭家屯
     * @since: 2020/1/8 10:38
     */
    public function _empty(){
        $this->set_message(1000, "404未找到");
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
            echo json_encode($msg, JSON_FORCE_OBJECT);
        } elseif ($type == 2) {
            echo json_encode($msg, JSON_PRETTY_PRINT);
        } else {
            echo json_encode($msg,true);
        }
        exit;
    }

    /**
     *对数据进行排序
     */
    protected function arrSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     *MD5验签加密
     */
    protected function encrypt_md5($param = "", $key = "")
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
        $key = 'mengchuang';
        // dump($md5_sign);
        $signString = $this -> arrSort($encrypt);
        $appSet = cmf_get_option('app_set');
        if (!empty($appSet['app_key'])) {
            $key = $appSet['app_key'];
        }
        $md5Str = $this -> encrypt_md5($signString, $key);
        // dump($md5Str);
        if ($md5Str === $md5_sign) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @单点登录验证
     *
     * @author: zsl
     * @since: 2021/5/18 10:29
     */
    private function sso_check($user_id,$original_token)
    {
        $redis = Redis ::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        $token = $redis -> get('app_token_' . $user_id);
        if ($original_token != $token) {
            $this -> set_message(1002, "信息过期，请重新登录");
        }
    }

}
