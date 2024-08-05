<?php
/**
 *
 */

namespace app\mobile\controller;

use app\common\controller\BaseHomeController;
use app\common\controller\SmsController;
use app\common\logic\CaptionLogic;
use app\member\validate\UserValidate;
use app\member\model\UserModel;
use think\Db;


class InvitationController extends BaseController
{
    /**
     *
     * @author
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @注册
     * @author: 郭家屯
     * @since: 2020/2/21 17:42
     */
    public function register()
    {
        //验证注册ip
        if(!checkregiserip()){
            $this->error('暂时无法注册，请联系客服');
        }
        $data = $this->request->param();
        if (empty($data['account'])) {
            $this->error('请输入11位手机号码');
        }

        $data['register_type'] = 2;
        $sms = new SmsController();
        $smsData = $sms->verifySmsCode($data['account'], $data['code'], false, false);
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
            $this->error($return);
        }
        $session_name = $smsData['session_name'];
        $data['phone'] = $data['account'];

        //图形验证
        $verify_tag = $data['verify_tag'];
        $verify_token = $data['verify_token'];
        $res = (new CaptionLogic()) -> checkToken($verify_token, $verify_tag,0);
        if ($res['code']!=200) {
            $this->error($res['info']);
        }
        //验证
        $validate = new UserValidate();
        if (!$validate->scene('invitation')->check($data)) {
            $this->error($validate->getError());
        }
        (new CaptionLogic()) -> clearToken($verify_tag);
        $promote_id = empty($data['promote_id']) ? 0 : $data['promote_id'];
        $data['register_way'] = 4;
        $data['type'] = 4;
        $data['promote_id'] = session('union_host') ? session('union_host.union_id') : $promote_id;
        //$data['promote_account'] = get_promote_name($data['promote_id']);
        //$data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
        //$data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
        //$data['head_img'] = cmf_get_domain() . '/upload/sdk/logoo.png';
        $data['game_id'] = empty($data['game_id']) ? 0 : $data['game_id'];
        $data['game_name'] = get_game_name($data['game_id']);
        $data['invitation_id'] = get_user_entity($data['invitation_account'],true,'id')['id'];
        $model = new UserModel();
        $result = $model->register($data);
        if ($session_name) {
            SmsController::clearSmsCodeStore($session_name);
        }
        if ($result == -1) {
            $this->error('注册失败');
        } else {
            cookie('login_account', $data['account'], parent::COOKIE_EXPIRE_TIME);
            $result['user_id'] = $result['id'];
            userInfo($result);
            $this->success('注册成功');
        }
    }

}
