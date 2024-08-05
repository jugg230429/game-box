<?php

namespace app\sdk\controller;

use geetest31\CheckGeetestStatus;
use geetest31\GeetestLib;

class GtController extends BaseController
{

    protected $geetest_id;
    protected $geetest_key;

    public function __construct()
    {
        parent ::__construct();
        $this -> geetest_id = cmf_get_option('admin_set')['auto_verify_index'];
        $this -> geetest_key = cmf_get_option('admin_set')['auto_verify_admin'];
    }

    /**
     * @极验验证初始化接口
     */
    public function init()
    {
        header("Content-type: application/json; charset=utf-8");
        /*
            必传参数
                digestmod 此版本sdk可支持md5、sha256、hmac-sha256，md5之外的算法需特殊配置的账号，联系极验客服
            自定义参数,可选择添加
                user_id 客户端用户的唯一标识，确定用户的唯一性；作用于提供进阶数据分析服务，可在register和validate接口传入，不传入也不影响验证服务的使用；若担心用户信息风险，可作预处理(如哈希处理)再提供到极验
                client_type 客户端类型，web：电脑上的浏览器；h5：手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生sdk植入app应用的方式；unknown：未知
                ip_address 客户端请求sdk服务器的ip地址
         */
        $gtLib = new GeetestLib($this -> geetest_id, $this -> geetest_key);
        $userId = "test";
        $digestmod = "md5";
        $params = [
                "digestmod" => $digestmod,
                "user_id" => $userId,
                "client_type" => "native",
                "ip_address" => get_client_ip(),
        ];
        //检查云端状态
        $this -> checkStatus();
        if (CheckGeetestStatus ::getGeetestStatus()) {
            $result = $gtLib -> register($digestmod, $params);
        } else {
            $result = $gtLib -> localInit();
        }
        echo $result -> getData();
        exit();
    }


    /**
     * @检查云端状态
     *
     */
    public function checkStatus()
    {
        $check = new CheckGeetestStatus();
        $result = $check -> checkStatus();
        echo $result;
    }


}
