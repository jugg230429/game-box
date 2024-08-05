<?php

namespace app\user\logic;

use app\extend\model\MsgModel;
use app\user\model\UserUnsubscribeModel;
use think\Exception;
use think\xigusdk\Xigu;
use app\common\controller\SmsController;

class UnsubscribeLogic
{


    /**
     * @用户申请注销
     *
     * @author: zsl
     * @since: 2021/5/13 15:11
     */
    public function unsubscribe($user_id)
    {
        $result = ['code' => 1, 'msg' => '提交成功', 'data' => []];
        $mUserUnsub = new UserUnsubscribeModel();
        // 查询用户是否已有注销记录
        $status = $mUserUnsub -> getUnsubscribeStatus($user_id);
        if (!empty($status)) {
            $result['code'] = 0;
            $result['msg'] = '请不要重复提交';
            return $result;
        }
        // 新增注销记录
        $insRes = $mUserUnsub -> insertUnsubscribeData($user_id);
        if (false === $insRes) {
            $result['code'] = 0;
            $result['msg'] = '提交失败，请稍后再试';
            return $result;
        }
        return $result;
    }


    /**
     * @取消注销
     *
     * @author: zsl
     * @since: 2021/5/13 17:48
     */
    public function cancelUnsub($user_id)
    {
        $result = ['code' => 1, 'msg' => '取消成功', 'data' => []];
        $mUserUnsub = new UserUnsubscribeModel();
        // 查询用户是否已有注销记录
        $status = $mUserUnsub -> getUnsubscribeStatus($user_id);
        if ($status != '1') {
            $result['code'] = 0;
            $result['msg'] = '没有找到注销申请记录';
            return $result;
        }
        // 删除注销记录
        $insRes = $mUserUnsub -> delUnsubscribeData($user_id);
        if (false === $insRes) {
            $result['code'] = 0;
            $result['msg'] = '提交失败，请稍后再试';
            return $result;
        }
        return $result;
    }


    /**
     * @注销成功邮件通知
     *
     * @author: zsl
     * @since: 2021/5/14 17:17
     */
    public function sendMail($mail)
    {
        try {
            $content = "【溪谷软件】朋友，您的账号注销已完成，愿你我后会有期，有缘自会再相逢。感谢您的陪伴！";
            $result = cmf_send_email($mail, '注销账号通知', $content);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * @注销成功短信通知
     *
     * @since: 2021/5/15 10:50
     * @author: zsl
     */
    public function sendSms($phone)
    {
        try {
            $msg = new MsgModel();
            $data = $msg ::get(1);
            if (empty($data)) {
                return false;
            }
            $xigu = new Xigu($data);
            $result = json_decode($xigu -> sendSM($phone, $data['unsubscribe_tid']), true);
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 发送注销验证短信/邮件
     *
     * @param $user_id
     * @author: Juncl
     * @time: 2021/09/07 11:00
     */
    public function sendVerifySms($user_id=0)
    {
         if($user_id == 0){
             return ['status'=>0,'msg'=>'用户不存在'];
         }
        $user = get_user_entity($user_id,false,'phone,email');
        if(empty($user['phone']) && empty($user['email'])){
            return ['status'=>0,'msg'=>'手机号或者邮箱为空'];
        }
        // 优先发送短信
        if(!empty($user['phone'])){
            $sms = new \app\common\controller\SmsController;
            $result = $sms->sendSmsCode($user['phone'], '10', false);
            if($result['code'] == 200){
                $phone = substr_replace($user['phone'],'****',3,4);
                $data = ['phone'=>$user['phone'],'type'=>1];
                return ['status'=>1,'msg'=>'已向' . $phone . '发送验证码，请注意查收
','data'=>$data];
            }else{
                return ['status'=>0,'msg'=>'短信发送失败'];
            }
        }else{
            $session = session($user['email']);
            if (!empty($session) && (time() - $session['create_time']) < 60) {
                return ['status'=>0,'msg'=>'验证码发送过于频繁'];
            }
            $code = rand(100000, 999999);
            $smtpSetting = cmf_get_option('email_template_verification_code');
            $smtpSetting['template'] = str_replace('{$code}', $code, htmlspecialchars_decode($smtpSetting['template']));
            $result = cmf_send_email($user['email'], $smtpSetting['subject'], $smtpSetting['template']);
            if ($result['error'] == 0) {
                session($user['email'], ['code' => $code, 'create_time' => time()]);
                $email = substr_replace($user['email'],'****',3,4);
                $data = ['phone'=>$user['email'],'type'=>2];
                return ['status'=>1,'msg'=>'已向' . $email . '发送验证码，请注意查收
','data'=>$data];
            } else {
                return ['status'=>0,'msg'=>'发送失败，请检查邮箱地址是否正确'];
            }
        }
    }

    /**
     * 验证短信验证码
     *
     * @author: Juncl
     * @time: 2021/09/07 19:15
     */
    public function verifyCode($code='', $type=0, $phone='')
    {
        if(empty($phone)){
            return ['status'=>0,'msg'=>'手机号或者邮箱错误'];
        }
        if(empty($code)){
            return ['status'=>0,'msg'=>'验证码不能为空'];
        }
        // 短信验证
        if($type == 1){
            $sms = new \app\common\controller\SmsController;
            $smsData = $sms->verifySmsCode($phone, $code, false, false);
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
                return ['status'=>0,'msg'=>$return];
            }
            return ['status'=>1,'msg'=>'短信验证成功'];
        }elseif ($type == 2){
            $session = session($phone);
            if (empty($session)) {
                return ['status'=>0,'msg'=>'请先获取验证码'];
            } elseif ((time() - $session['create_time']) > 10 * 60) {
                return ['status'=>0,'msg'=>'验证码超时'];
            } elseif ($session['code'] != $code) {
                return ['status'=>0,'msg'=>'验证码不正确，请重新输入'];
            }
            session($phone, null);
            return ['status'=>1,'msg'=>'邮箱验证成功'];
        }else{
            return ['status'=>0,'msg'=>'验证类型错误'];
        }
    }


}
