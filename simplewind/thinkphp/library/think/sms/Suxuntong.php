<?php

namespace think\sms;

use Exception;

class Suxuntong  {

    /**
     * 企业id
     */
    private $userid;
    /**
     * 帐号
     */
    private $account;
    /**
     * 密码
     */
    private $password;
     /**
     * 请求地址
     */
    private $sendUrl;

    /**
     * @param $options 数组参数必填
     * $options = array(
     *
     * )
     * @throws Exception
     */
    public function  __construct()
    {
        $this->userid = 535;
        $this->account = 'TBB';
        $this->password = 1597234;
        $this->sendUrl = 'http://www.xt1069.vip/sms.aspx?action=send';
    }

    public function sendSM($mobile,$code,$reg){
        $paramArray = [
            "userid" => $this->userid,
            "account" => $this->account,   
            "password" => $this->password,       
            "mobile" => $mobile,                                           
            "content" => $this->dealSmsContent($code,$reg),                                                                            
            "sendTime" => date('Y-m-d H:i:s'),
            "action" => 'send',          
            "extno" => ''                                        
        ];
        $paramsStr = http_build_query($paramArray); //请求参数str
        $result = $this->httpPost($this->sendUrl, $paramsStr);
        // object(SimpleXMLElement)#23 (6) {
        //     ["returnstatus"] => string(7) "Success"
        //     ["message"] => string(2) "ok"
        //     ["remainpoint"] => string(1) "7"
        //     ["taskID"] => string(7) "2600202"
        //     ["successCounts"] => string(1) "1"
        //     ["actualCounts"] => string(1) "1"
        // }
        return simplexml_load_string($result);
    }


    function dealSmsContent($code,$reg){
        switch($reg){
            //注册检查
            case 1: 
                $content = "【TBB平台】验证码：".$code."。您正在使用注册账号功能，该验证码仅用于身份验证，请勿泄漏给他人使用。";
                break;
            //忘记密码
            case 2: 
                $content = "【TBB平台】验证码：".$code."。您正在使用找回密码功能，该验证码仅用于身份验证，请勿泄漏给他人使用。";
                break;
            //解绑
            case 3: 
                $content = "【TBB平台】验证码：".$code."。您正在使用解绑账号功能，该验证码仅用于身份验证，请勿泄漏给他人使用。";
                break;
            //绑定
            case 4: 
                $content = "【TBB平台】验证码：".$code."。您正在使用绑定账号功能，该验证码仅用于身份验证，请勿泄漏给他人使用。";
                break;
            //出售账号    
            case 5: 
                $content = "【TBB平台】验证码：".$code."。您正在使用出售账号功能，该验证码仅用于身份验证，请勿泄漏给他人使用。";
                break; 
            //注销账号       
            default :
                $content = "【TBB平台】验证码：".$code."。您正在使用注销账号功能，该验证码仅用于身份验证，请勿泄漏给他人使用。";
        }
        return $content;
    }

    function httpPost($url, $paramStr){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $paramStr,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $err;
        }
        return $response;
    }
} 