<?php

namespace app\issuesy\controller;

use app\issue\model\IssueGameModel;
use app\issue\model\OpenUserModel;
use app\issue\model\PlatformModel;
use app\issuesy\logic\packtool\BaseLogic;
use app\issuesy\logic\packtool\GameLogic;
use app\issuesy\logic\packtool\UserLogic;
use think\Request;

class PackController extends BaseLogic
{
    private $pack_data;
    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE");
        header("Access-Control-Allow-Headers:DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type, Accept-Language, Origin, Accept-Encoding");

        $this->pack_data = $request->param();
        if(empty($this->pack_data)){
            $this->set_message("1001","数据不能为空");
        }
        if (empty($this->pack_data['packet_os'])) {
            $this->set_message("1008", "packet_os不能为空", "", 1);
        }
//        //验签
//        $md5Sign = $this->pack_data['md5_sign'];
//        unset($this->pack_data['md5_sign']);
//        $md5_sign = $this->encrypt_md5($this->pack_data,"mengchuang");
//        if($md5Sign !== $md5_sign){
//            $this->set_message("1002","Sign验证失败");
//        }
        if(!empty($this->pack_data['user_id'])){
            $token = base64_decode($this->pack_data['token']);
            $user = get_table_entity((new OpenUserModel()),['id'=>$this->pack_data['user_id']],'password,status');
            //验证token
            if(!password_verify($user['password'],$token)){
                $this->set_message(1006, "账号信息失效，请重新登录");
            }
            if($user['status']!=1){
                $this->set_message(1005, "用户已锁定");
            }
            $platformData = get_table_entity((new PlatformModel()),['id'=>$this->pack_data['platform_id'],'open_user_id'=>$this->pack_data['user_id']],'status');
            if($platformData['status']!=1){
                    $this->set_message(1007, "平台不存在或已锁定");
            }
        }
    }
    public function user_login(UserLogic $lUser){
        //获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $data = $this->pack_data;
        return $res = $lUser->user_login($data);
    }

    /*
     * 取得游戏列表
     */
    public function get_game_lists(GameLogic $lgame)
    {
        //获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $data = $this->pack_data;
        if(empty($data['user_id'])){
            $this->set_message("1001","user_id数据不能为空");
        }
        $res = $lgame->get_game_lists($data);
        $this->set_message($res['code'],$res['msg'],$res['data']?:[]);
    }
    //打包
    public function doPack(GameLogic $lgame)
    {
        $data = $this->pack_data;
        if(empty($data['user_id'])){
            $this->set_message("1001","user_id数据不能为空");
        }
        if(empty($data['game_id'])){
            $this->set_message("1001","game_id数据不能为空");
        }
        $res = $lgame->doPack($data);
        $this->set_message($res['code'],$res['msg'],$res['data']?:[]);
    }
}