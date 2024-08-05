<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/13
 * Time: 16:23
 */

namespace app\admin\controller;

use app\admin\model\UserModel;
use app\common\logic\CaptionLogic;
use app\extend\model\MsgModel;
use cmf\controller\AdminBaseController;
use cmf\controller\BaseController;
use think\Exception;
use think\Validate;
use think\xigusdk\Xigu;
use geetest\lib\GeetestLib;
use think\Db;
use think\Request;
use think\captcha\Captcha;

class VerifyController extends BaseController
{
    /**
     *短信发送
     * 修改-byh-2021-8-16 16:11:23
     */

    public function send_sms()
    {
        $result = [
            'code'=>1,
            'msg'=>'发送成功'
        ];
        $request = $this->request->param();
        $username = $request['username'];
        if(empty($username)){
            $result['code'] = 0;
            $result['msg'] = '账号不存在';
            return json($result);
        }
        $userData = (new UserModel())->checkUser(['username'=>$username]);
        if($userData['code']!=1){
            $result['code'] = 0;
            $result['msg'] = $userData['msg'];
            return json($result);
        }
        if(empty($userData['result']['mobile'])){
            $result['code'] = 0;
            $result['msg'] = '账号未绑定该手机号';
            return json($result);
        }
        $phone = $userData['result']['mobile'];
        $msg = new MsgModel();
        $data = $msg::get(1);
        $data = $data->toArray();
        if (empty($data)) {
            $this->error("没有配置短信发送");
        }
        if ($data['status'] == 1) {
            $xigu = new Xigu($data);
            $res = sdkchecksendcode($phone, $data['client_send_max'], 2);

            if($res==1072){
                $result['code'] = 0;
                $result['msg'] = '短信验证已达上限，请明天再试';
                return json($result);
            }
            if($res==1073){
                $result['code'] = 0;
                $result['msg'] = '请一分钟后再次尝试';
                return json($result);
            }
            $sms_code = session($phone);//发送没有session 每次都是新的
            $sms_rand = sms_rand($phone);
            $rand = $sms_rand['rand'];
            $new_rand = $sms_rand['rand'];
            /// 产生手机安全码并发送到手机且存到session
            $param = $rand . "," . '3';
            $res = json_decode($xigu->sendSM($phone, $data['captcha_tid'], $param), true);
//            dump($res);die;
//            $res['send_status'] = '000000';
            $res['create_time'] = time();
            $res['phone'] = $phone;
            $res['create_ip'] = get_client_ip();
            Db::table('tab_sms_log')->field(true)->insert($res);
            #TODO 短信验证数据
            if ($res['send_status'] == '000000') {
                $safe_code['code'] = $rand;
                $safe_code['phone'] = $phone;
                $safe_code['time'] = $new_rand ? time() : $sms_code['time'];
                $safe_code['delay'] = 3;
                $safe_code['create'] = $res['create_time'];
                session($phone, $safe_code);
                $result['code'] = 1;
                $result['msg'] = '短信已发放，请注意查收';
                return json($result);
            } else {
                $result['code'] = 0;
                $result['msg'] = '验证码发送失败，请重新获取';
                return json($result);
            }
        } else {
            $result['code'] = 0;
            $result['msg'] = '没有配置短信发送';
            return json($result);
        }
    }
}
