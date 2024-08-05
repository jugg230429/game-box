<?php
/**
 * @Copyright (c) 2021  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License 江苏溪谷网络科技有限公司版权所有
 * @since 2021-04-20
 */
namespace app\sdkw\logic;

use geetest31\CheckGeetestStatus;
use geetest31\GeetestLib;
use think\Cache;

class BaseLogic
{
    /**
     * 方法 encrypt_md5
     *
     * @descript MD5验签加密
     *
     * @param string $param
     * @param string $key
     * @return string
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 9:35
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
     * 方法 validation_sign
     *
     * @descript 验证签名
     *
     * @param string $encrypt
     * @param string $md5_sign
     * @return bool
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 9:35
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
     * 方法 secondValidate
     *
     * @descript 极验验证码二次验证
     *
     * @param $challenge
     * @param $validate
     * @param $seccode
     * @return bool
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 9:35
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

    /**
     * 方法 arrSort
     *
     * @descript 数据排序
     *
     * @param $para
     * @return mixed
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 9:34
     */
    private function arrSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 方法 smsVerify
     *
     * @descript 短信验证
     *
     * @param string $phone
     * @param string $code
     * @return false|int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 15:19
     */
    protected function smsVerify($phone = "", $code = "")
    {
        $session = session($phone);
//        $session = Cache::get($phone);
        if (empty($session)) {
            return 1017;
        }
        #验证码是否超时
        $time = time() - session($phone . ".create");

        if ($time > 180) {
            return 1018;
        }
        #验证短信验证码
        if (session($phone . ".code") != $code) {
            return 1019;
        }
        return false;
    }

    /**
     * 方法 emailVerify
     *
     * @descript 邮箱验证
     *
     * @param $email
     * @param $code
     * @param int $time 分钟
     * @return false|int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/20 0020 15:31
     */
    protected function emailVerify($email, $code, $time = 30)
    {
        $session = session($email);
//        $session = Cache::get($email);
        if (empty($session)) {
            return 1017;
        } elseif ((time() - $session['create_time']) > $time * 60) {
            return 1026;
        } elseif ($session['code'] != $code) {
            return 1019;
        }
        //session($email, null);
        return false;
    }

    protected function sendGet($endpoint)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $response = curl_exec($curl);
        $errno = curl_errno($curl);
        $errmsg = curl_error($curl);

        file_put_contents(__DIR__ . '/fb1.txt', json_encode($response));
        file_put_contents(__DIR__ . '/fb2.txt', json_encode($errno));
        file_put_contents(__DIR__ . '/fb3.txt', json_encode($errmsg));

        //关闭URL请求
        curl_close($curl);
        //判断时候出错，抛出异常
        if ($errno != 0) {
            throw new \Exception($errmsg, $errno);
        }
        return $response;
    }

}
