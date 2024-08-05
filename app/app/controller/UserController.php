<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\app\controller;

use app\user\logic\UnsubscribeLogic;
use app\common\logic\YiDunLoginLogic;
use app\game\model\GamecollectModel;
use app\game\model\GameModel;
use app\game\model\GiftRecordModel;
use app\member\model\UserBehaviorModel;
use app\member\model\UserConfigModel;
use app\member\model\UserModel;
use app\extend\model\MsgModel;
use app\member\model\UserPlayModel;
use app\common\controller\BaseHomeController;
use app\member\validate\UserValidate3;
use app\promote\model\PromoteunionModel;
use app\recharge\model\SpendModel;
use app\recharge\model\SpendRebateRecordModel;
use app\user\model\UserUnsubscribeModel;
use app\site\model\TipModel;
use think\captcha\Captcha;
use think\Checkidcard;
use think\WechatAuth;
use think\xigusdk\Xigu;
use cmf\lib\Storage;
use think\Db;

class UserController extends BaseController
{
    /**
     * @函数或方法说明
     * @登录接口
     * @author: 郭家屯
     * @since: 2020/2/17 10:57
     */
    public function login()
    {
        $data = $this->request->param();
        $model = new UserModel();
        $data['type'] = 4;//APP登录
        $result = $model->login($data);
        switch ($result) {
            case 1005:
                $this->set_message(1004,'账号不存在或被禁用');
                break;
            case 1006:
                $this->set_message(1005,'密码错误');
                break;
            case 1007:
                $this->set_message(1004,'账号不存在或被禁用');
                break;
            default:
                $return['user_id'] = $result['id'];
                $return['account'] = $result['account'];
                $return['nickname'] = $result['nickname'];//昵称
                $return['head_img'] = $result['head_img'];//头像
                $return['balance'] = $result['balance'];   //平台币
                $return['token'] = $result['token'];
                break;
        }
        $this->set_message(200,'登陆成功',$return);
    }

    /**
     * @函数或方法说明
     * @手机验证码登录
     * @author: 郭家屯
     * @since: 2020/2/17 11:40
     */
    public function phone_login()
    {
        $data = $this->request->param();
        $this->sms_verify($data['phone'],$data['code'],2);
        $model = new UserModel();
        $data['type'] = 4;//APP登录
        $data['account'] = $data['phone'];
        $user = $model->field('id')->where('account',$data['phone'])->find();
        if($user){
            $result = $model->login($data,1);
            switch ($result) {
                case -1:
                    $this->set_message(1015,'注册失败');
                    break;
                default:
                    $return['is_register'] = 0;
                    $return['user_id'] = $result['id'];
                    $return['account'] = $result['account'];
                    $return['nickname'] = $result['nickname'];//昵称
                    $return['head_img'] = cmf_get_image_url($result['head_img']);//头像
                    $return['balance'] = $result['balance'];   //平台币
                    $return['token'] = $result['token'];
                    break;
            }
            $this->set_message(200,'注册成功',$return);
        }else{
            if (cmf_get_option('media_set')['pc_user_allow_register'] == 0) {
                $this->set_message(1038,'用户注册入口已关闭');
            }
            //验证注册ip
            if(!checkregiserip()){
                $this->set_message(1039,'暂时无法注册，请联系客服');
            }
            $return['is_register'] = 1;
            $return['user_id'] = 0;
            $return['account'] = '';
            $return['nickname'] = '';//昵称
            $return['head_img'] = '';//头像
            $return['balance'] = 0;   //平台币
            $return['token'] = '';
            $this->set_message(200,'手机未注册',$return);
        }
    }

    /**
     * 易盾一键登录接口
     */
    public function one_click_login()
    {
        $data = $this->request->param();
        //一键登录号码处理--
        $yidun_data = [
            'yidun_token'=>$data['yidun_token'],
            'accessToken'=>$data['accessToken'],
        ];

        //一键登录号码处理--
        $yd_logic = new YiDunLoginLogic(0,$data['sdk_version']);
        $res = $yd_logic->one_click($yidun_data);
        if($res['code'] != 200 || $res['data']['phone'] == ''){
            $this->set_message(1015,'手机号获取失败');//msg:获取失败
        }

        $mobile = $res['data']['phone'];//手机号

        $model = new UserModel();
        $data['type'] = 4;//APP登录
        $data['account'] = $mobile;
        //查询手机号是否已存在,存在直接登录,如不存在,注册登录
        $user = $model->field('id')->where('account',$mobile)->find();
        if(empty($user)){//不存在
            if (cmf_get_option('media_set')['pc_user_allow_register'] == 0) {
                $this->set_message(1038,'用户注册入口已关闭');
            }
            //验证注册ip
            if(!checkregiserip()){
                $this->set_message(1039,'暂时无法注册，请联系客服');
            }

            $data['phone'] = $mobile;
            //此处增加判断手机号有没有被其他账号绑定,如被绑定,此处注册留空
            $is_bind_phone = $model->where('phone',$mobile)->count();
            if($is_bind_phone>0){
                $data['phone'] = '';
            }
            $data['register_type'] = 2;//注册方式 0游客1账号 2 手机 3微信 4QQ 5百度 6微博 7邮箱
            $data['register_way'] = 2;//注册来源 1sdk 2app 3PC 4wap

            //一键登录默认密码
            $systemSet = cmf_get_option('admin_set');
            if (!empty($systemSet['one_key_password'])) {
                $data['password'] = $systemSet['one_key_password'];
            } else {
                $data['password'] = '123456';
            }

            $result = $model->register($data);
            $is_register = 1;

        }else{
            //根据获取的$user(id)再查一遍account
            $account = $model->where('id',$user['id'])->value('account');
            $data['account'] = $account;
            $result = $model->login($data,1);
            $is_register = 0;
        }
        $return = [];
        switch ($result) {
            case -1:
            case 1005:
            case 1007:
                $this->set_message(1015,'登录失败');
                break;
            default:
                $return = [
                    'is_register'   => $is_register,//是否为注册0=否,1=是
                    'user_id'       => $result['id'],
                    'account'       => $result['account'],
                    'nickname'      => $result['nickname'],//昵称
                    'head_img'      => cmf_get_image_url($result['head_img']),//头像
                    'balance'       => $result['balance']??'',//平台币
                    'token'         => $result['token'],
                ];
                break;
        }
        $this->set_message(200,'登录成功',$return);

    }

    /**
     * [找回密码]
     * [优先返回短信验证]
     * @author 郭家屯[gjt]
     */
    public function forget_account()
    {
        $data = $this->request->param();
        $model = new UserModel();
        $user = $model->where('account', $data['account'])->field('id,account,phone,email,lock_status')->find();
        if (empty($user)) {
            $this->set_message(1004,'账号不存在或被禁用');
        }
        if($user['lock_status'] == 0){
            $this->set_message(1004,'账号不存在或被禁用');
        }
        $user = $user->toArray();
        if (empty($user['phone']) && empty($user['email'])) {
            $this->set_message(1017, '账号未绑定手机号或邮箱');
        }
        $res_msg = array(
                "user_id" => $user["id"],
                "account" => $user["account"],
                "phone" => $user['phone'],
                "email" => $user['email']
        );
        $this->set_message(200,"获取成功",$res_msg);
    }
    /**
     * [修改忘记密码接口]
     * @author 郭家屯[gjt]
     */
    public function forget_password()
    {
        $data = $this->request->param();
        $validate = new UserValidate3();
        if(!$validate->scene('password')->check($data)){
            $this->set_message(1019,$validate->getError());
        }
        $user = get_user_entity($data['user_id'],false,'phone,email');
        if($data['phone'] == $user['phone']){
            #验证短信验证码
            $this->sms_verify($data['phone'],$data['code'],2);
        }else{
            #验证邮箱验证码
            $this->email_verify($data['phone'],$data['code'],2);
        }
        $model = new UserModel();
        $result = $model->updatePassword($data['user_id'], $data['password']);
        if ($result == true) {
            session($data['phone'],null);
            $this->set_message(200, "找回密码成功");
        } else {
            $this->set_message(1019, "找回密码失败");
        }

    }
    /**
     * 用户协议
     *
     * @return mixed
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 15:20
     */
    public function protocol()
    {
        $portalPostModel = new \app\site\model\PortalPostModel;
        $data = $portalPostModel->field('post_title')->where('id', 27)->find();
        $data['url'] = url('User/protocol_html','',true,true);
        $privacy_data = $portalPostModel->field('post_title')->where('id', 16)->find();
        $data['privacy_title'] = $privacy_data['post_title'];
        $data['privacy_url'] = url('User/privacy_html','',true,true);
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * @函数或方法说明
     * @用户注册协议界面
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/27 14:44
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function privacy_html(){
        $portalPostModel = new \app\site\model\PortalPostModel;
        $data = $portalPostModel->field('post_title,post_content')->where('id', 16)->find();
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @用户注册协议界面
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/27 14:44
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function protocol_html(){
        $portalPostModel = new \app\site\model\PortalPostModel;
        $data = $portalPostModel->field('post_title,post_content')->where('id', 27)->find();
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @检查手机验证码
     * @author: 郭家屯
     * @since: 2020/2/18 16:18
     */
    public function checkphonecode()
    {
        $data = $this->request->param();
        if(cmf_check_mobile($data['phone'])){
            $this->sms_verify($data['phone'],$data['code'],1);
        }else{
            $this->email_verify($data['phone'],$data['code'],1);
        }
    }

    /**
     * @函数或方法说明
     * @是否禁止注册
     * @author: 郭家屯
     * @since: 2020/3/27 10:29
     */
    public function is_ban()
    {
        if (cmf_get_option('media_set')['pc_user_allow_register'] == 0) {
            $this->set_message(1038,'用户注册入口已关闭');
        }
        //验证注册ip
        if(!checkregiserip()){
            $this->set_message(1039,'暂时无法注册，请联系客服');
        }
        $this->set_message(200,'正常注册',1);
    }

    /**
     * @函数或方法说明
     * @手机/账号注册
     * @author: 郭家屯
     * @since: 2020/2/17 11:40
     */
    public function register()
    {
        if (cmf_get_option('media_set')['pc_user_allow_register'] == 0) {
            $this->set_message(1038,'用户注册入口已关闭');
        }
        //验证注册ip
        if(!checkregiserip()){
            $this->set_message(1039,'暂时无法注册，请联系客服');
        }
        $data = $this->request->param();

        if($data['type']!='1'){
            //账号注册,验证图片验证码
            if (empty($data['img_verify_code'])) {
                $this -> set_message(1040, '图片验证码不能为空');
            }
            $captcha = new Captcha();
            if (!$captcha -> check($data['img_verify_code'])) {
                $this -> set_message(1040, '验证码错误');
            }
        }

        //实名认证处理
        if(get_user_config_info('age')['real_register_status'] > 0){
            $data = $this->check_auth($data);
        }
        $data['account'] = $data['phone'];
        //手机号注册
        if($data['type'] == 1){
            $this->sms_verify($data['phone'],$data['code']);
            $data['register_type'] = 2;
        }else{
            //账号注册
            $data['register_type'] = 1;
            unset($data['phone']);
        }
        $model = new UserModel();
        $data['register_way'] = 2;
        //$data['promote_account'] = get_promote_name($data['promote_id']);
        //$data['parent_id'] = empty($data['promote_id']) ? 0 : get_fu_id($data['promote_id']);
        //$data['parent_name'] = empty($data['promote_id']) ? '' : get_parent_name($data['promote_id']);
        $data['type'] = 4;
        $validate = new UserValidate3();
        if(!$validate->check($data)){
            $this->set_message(1015,$validate->getError());
        }
        $result = $model->register($data);
        switch ($result) {
            case -1:
                $this->set_message(1015,'注册失败');
                break;
            case 1005:
                $this->set_message(1004,'账号不存在或被禁用');
                break;
            case 1006:
                $this->set_message(1005,'密码错误');
                break;
            case 1007:
                $this->set_message(1004,'账号不存在或被禁用');
                break;
            default:
                session($data['phone'],null);
                $return['user_id'] = $result['id'];
                $return['account'] = $result['account'];
                $return['nickname'] = $result['nickname'];//昵称
                $return['head_img'] = cmf_get_image_url($result['head_img']);//头像
                $return['balance'] = $result['balance']??'';   //平台币
                $return['token'] = $result['token'];
                break;
        }
        $this->set_message(200,'登陆成功',$return);
    }

    //实名验证
    protected function check_auth($data=[])
    {
        //非必填信息
        if(get_user_config_info('age')['real_register_status'] == 2 && (empty($data['real_name']) || empty($data['idcard']))){
            unset($data['real_name'],$data['idcard']);
            return $data;
        }
        if (!preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,25}$/', $data['real_name'])) {
            $this->set_message(1086,'姓名格式错误');
        }
        $len = mb_strlen($data['real_name']);
        if ($len < 2 || $len > 25) {
            $this->set_message(1087,'姓名长度需要在2-25个字符之间');
        }
        $data['idcard'] = strtolower($data['idcard']);
        $checkidcard = new Checkidcard();
        $invidcard = $checkidcard->checkIdentity($data['idcard']);
        if (!$invidcard) {
            $this->set_message(1088,"证件号码错误！");
        }

        $userconfig = new UserConfigModel();
        //实名认证设置
        $config = $userconfig -> getSet('age');
        $userModel = new UserModel();
        if ($config['config']['can_repeat'] != '1') {
            $cardd = $userModel -> where('idcard', $data['idcard']) -> field('id') -> find();
            if ($cardd) {
                $this -> set_message(1091, "身份证号码已被使用！");
            }
        }

        if (($config['status'] == 0) || ($config['status'] == 1 && $config['config']['ali_status'] == 0)) {
            //判断年龄是否大于16岁
            if (is_adult($data['idcard'])) {
                $data['age_status'] = 2;
            } else {
                $data['age_status'] = 3;
            }
        } else {
            //真实判断身份证是否有效
            $re = age_verify($data['idcard'], $data['real_name'], $config['config']['appcode']);
            switch ($re) {
                case -1:
                    $this->set_message(1088,"短信数量已经使用完！");
                    break;
                case -2:
                    $this->set_message(1089,"连接接口失败");
                    break;
                case 0:
                    $this->set_message(1090,"认证信息错误");
                    break;
                case 1://成年
                    $data['age_status'] = 2;
                    $data['anti_addiction'] = 1;
                    break;
                case 2://未成年
                    $data['age_status'] = 3;
                    break;
                default:
            }
        }
        return $data;
    }
    /**
     * @函数或方法说明
     * @上传头像
     * @author: 郭家屯
     * @since: 2020/3/25 16:26
     */
    public function head_imag(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('head_imag');
        // 移动到框架应用根目录/uploads/ 目录下
        $path = ROOT_PATH.'/public/upload/default/'.date('Ymd').'/';
        $info = $file->rule('uniqid')->validate(['ext'=>'jpg,png,gif'])->move($path);
        if($info){
            $img = 'default/'.date('Ymd').'/'.$info->getFilename();
            //oss上传
            $tmp_flag=1;
            $upload_res = $this->uploadcloud($img, './upload/' . $img, 'image', false,$tmp_flag);

            $upload_code = $upload_res['code'] ?? 0;
            if($upload_code == -1){
                // 上传失败
                $this->set_message(1041, "头像上传失败");
                exit;
            }

            $model = new UserModel();
            $result = $model->where('id',USER_ID)->setField('head_img',$img);
            $data['url'] = cmf_get_image_url($img);
            if ($result == true) {
                $this->set_message(200, "头像上传成功",$data);
            } else {
                $this->set_message(1041, "头像上传失败");
            }
        }else{
           $this->set_message(1040,'上传失败');
        }
    }

    /**
     * @函数或方法说明
     * @个人信息中心
     * @author: 郭家屯
     * @since: 2020/2/19 13:55
     */
    public function userinfo()
    {
        $request = $this->request->param();
        $user = get_user_entity(USER_ID,false,'id as user_id,account,qq,vip_level,head_img,nickname,phone,email,real_name,idcard,balance,receive_address,point,member_days,end_time');
        $user['bind_balance'] = Db::table('tab_user_play')->where('user_id',USER_ID)->sum('bind_balance');
        $model = new TipModel();
        $receive_address = explode('|!@#%-|',$user['receive_address']);
        $receive_address = count($receive_address)!=4?[]:['address_name'=>$receive_address[0],'address_phone'=>$receive_address[1],'consignee_address'=>$receive_address[2],'address_detail'=>$receive_address[3]];
        $user['receive_address'] = $receive_address;
        $user['head_img'] = cmf_get_image_url($user['head_img']);
        if($request['sdk_version'] == 1){
            $user['tip_status'] = $model->get_tip_status(USER_ID);
        }else{
            $user['tip_count'] = $model->get_app_tip_status(USER_ID);
        }
        $user['vip_status'] = cmf_get_option('vip_set')['vip']?1:0;
        if(empty($user['end_time'])){
            $user['member_status'] = 0;
        }elseif($user['end_time']>time()){
            $user['member_status'] = 1;
        }else{
            $user['member_status'] = 2;
        }
        //获取尊享卡名称
        if($user['end_time']){
            $mcard_set = cmf_get_option('mcard_set');
            if($mcard_set['config1']['card_name']){
                $card[$mcard_set['config1']['days']] = $mcard_set['config1']['card_name'];
            }
            if($mcard_set['config2']['card_name']){
                $card[$mcard_set['config2']['days']] = $mcard_set['config2']['card_name'];
            }
            if($mcard_set['config3']['card_name']){
                $card[$mcard_set['config3']['days']] = $mcard_set['config3']['card_name'];
            }
            krsort($card);
            foreach ($card as $key=>$v){
                if($user['member_days'] >= $key){
                    $user['mcard_name'] = $v;
                    break;
                }
            }
        }else{
            $user['mcard_name'] = "尊享卡";
        }
        //查询用户注销状态
        $mUnsub = new UserUnsubscribeModel();
        $unsubStatus = $mUnsub -> getUnsubscribeStatus(USER_ID);
        $user['is_unsubscribe'] = $unsubStatus;
        $user['end_time'] = $user['end_time'] ? date('Y-m-d',$user['end_time']) : '';
        $this->set_message(200,'获取成功',$user,1);
    }

    /**
     * 获取消息
     */
    public function get_tip_lists()
    {
        $request = $this->request->param();
        //写入读操作
        $behavior_model = new UserBehaviorModel();
        $map['user_id'] = USER_ID;
        $map['game_id'] = 0;
        $read_time = $behavior_model->set_record($map);
        $p = $request['page'] ? : 1;
        $limit = $request['limit'] ? : 10;
        $model = new TipModel();
        $data = $model->getPageLists(USER_ID,$p,$limit);
        foreach ($data as $key=>$v){
            if($read_time > $v['create_time']){
                $data[$key]['read_status'] = 1;
            }else{
                $data[$key]['read_status'] = 0;
            }
            if(strpos($v['content'],'<a') !== false){//礼包过期提醒消息
                $data[$key]['jump_event'] = 1;
                $data[$key]['content'] = explode('，<a',$v['content'])[0];
            }elseif($v['game_id']>0){//区服通知消息
                $data[$key]['jump_event'] = 2;
            }elseif($v['comment_id']>0){//评论消息
                $data[$key]['jump_event'] = 3;
            }else{
                $data[$key]['jump_event'] = 0;
            }
            $data[$key]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
        }
        $this->set_message(200,'获取成功',$data,2);
    }

    /**
     * @函数或方法说明
     * @绑定余额列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/18 16:04
     */
    public function bind_list(){
        $request = $this->request->param();
        $base = new BaseHomeController();
        $model = new UserPlayModel();
        $map['user_id'] = USER_ID;
        $map['bind_balance'] = ['gt',0];
        $extend['field'] = 'g.game_name,bind_balance,g.icon,g.sdk_version';
        $extend['join1'] = [['tab_game'=>'g'],'g.id = tab_user_play.game_id','left'];
        $list = $base->data_list_join_select($model,$map,$extend);
        foreach ($list as $key=>$v){
            $list[$key]['icon'] = cmf_get_image_url($v['icon']);
        }
        $this->set_message(200,'获取成功',$list);
    }

    /**
     * [修改用户信息]
     * @author 郭家屯[gjt]
     */
    public function user_update_data()
    {
        $data = $this->request->param();
        $model = new UserModel();
        switch ($data['code_type']) {
            case 'nickname':
                if(mb_strlen($data['nickname']) > 24 || mb_strlen($data['nickname'])<1){
                    $this->set_message(1025, '昵称为1-24位字符');
                }
                $nk = $model->where('nickname', $data['nickname'])->field('id')->find();
                if ($nk) {
                    $this->set_message(1026, '昵称已被使用');
                }
                $save['nickname'] = $data['nickname'];
                break;
            case 'qq':
                if(empty($data['qq'])){
                    $this->set_message(1025,'qq号不能为空');
                }
                if(!is_numeric($data['qq'])){
                    $this->set_message(1026,'qq号格式错误');
                }
                $save['qq'] = $data['qq'];
                break;
            case 'pay_password':
                $user = get_user_entity($data['user_id'],false,'password');
                if (!xigu_compare_password($data['old_password'], $user['password'])) {
                    $this->set_message(1028, '登录密码错误');
                }
                $this->sms_verify($data['phone'],$data['code']);
                $data['password'] = $data['pay_password'];
                $validate = new UserValidate3();
                if(!$validate->scene('password')->check($data)){
                    $this->set_message(1019,$validate->getError());
                }
                $save['pay_password'] = cmf_password($data['pay_password']);
                break;
            case 'pwd' :
                if ($data['old_password'] == $data['password']) {
                    $this->set_message(1027, '新密码与原始密码不能相同');
                }
                $user = get_user_entity($data['user_id'],false,'password');
                if (!xigu_compare_password($data['old_password'], $user['password'])) {
                    $this->set_message(1028, '原密码错误');
                }
                $validate = new UserValidate3();
                if(!$validate->scene('password')->check($data)){
                    $this->set_message(1019,$validate->getError());
                }
                $save['password'] = cmf_password($data['password']);
                $save['token'] = think_encrypt(json_encode(array('uid' => $data['user_id'], 'password' => cmf_password($data['password']))),1);
                break;
            default:
                $this->set_message(1029, "修改信息不明确");
                break;
        }
        $result = $model->update_user_info($data['user_id'], $save);
        if ($result) {
            $json = [];
            if ($data['code_type'] == 'pwd') {
                $json['token'] = $save['token'];
            }
            $this->set_message(200,"修改成功",$json);
        } else {
            $this->set_message(1037, '修改失败');
        }
    }

    /**
     * @函数或方法说明
     * @实名认证
     * @author: 郭家屯
     * @since: 2020/8/18 17:16
     */
    public function realauth()
    {
        $request = $this->request->param();
        $data['real_name'] = $request['real_name'];
        $data['idcard'] = $request['idcard'];
        $data = $this->check_auth($data);
        $userModel = new UserModel();
        $result = $userModel->where('id', $request['user_id'])->update($data);
        if (!$result) {
            $this->set_message(1037,"实名认证失败");
        } else {
            $userModel->task_complete($request['user_id'],'auth_idcard',$data['idcard']);//绑定任务
            $this->set_message(200,'实名认证成功');
        }
    }

    /**
     * 绑定收货地址
     * @descript author
     * @return mixed
     * @author yyh
     * @since 2020-05-27
     */
    public function receive_address_edit(){
        $postData = $this->request->post();
        if (!preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,25}$/', $postData['address_name'])) {
            $this->set_message(1049, '收货人姓名长度需要在2-25个字符之间，不可包含非法字符');
        }
        $len = mb_strlen($postData['address_name']);
        if ($len < 2 || $len > 25) {
            $this->set_message(1049, '收货人长度需要在2-25个字符之间');
        }
        if(strlen($postData['address_phone']) != 11){
            $this->set_message(1050, '请输入11位手机号码');
        }
        if (!cmf_check_mobile($postData['address_phone'])) {
            $this->set_message(1050, '请输入正确手机号');
        }
        if(empty($postData['consignee_address'])){
            $this->set_message(1051, '请选择所在地区');
        }
        $len = mb_strlen($postData['address_detail']);
        if ($len<=0) {
            $this->set_message(1052, '详细地址不能为空');
        }
        $model = new UserModel();
        $address  = [
            'address_name'=>$postData['address_name'],
            'address_phone'=>$postData['address_phone'],
            'consignee_address'=>$postData['consignee_address'],
            'address_detail'=>$postData['address_detail'],
        ];
        $save['receive_address'] = implode('|!@#%-|',$address);
        $result = $model->update_user_info(USER_ID, $save);
        $model->task_complete(USER_ID,'improve_address',$save['receive_address']);//绑定任务
        $this->set_message(200, '保存成功');
    }
    /**
     * @函数或方法说明
     * @我的礼包
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/18 17:40
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function gift()
    {
        $model = new GiftRecordModel();
        $received = $model->record(USER_ID, 1);
        foreach ($received as $key=>$v){
            $received[$key]['icon'] = cmf_get_image_url($v['icon']);
            $received[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('Y-m-d',$v['start_time']);
            $received[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('Y-m-d',$v['end_time']);
        }
        $overdue = $model->record(USER_ID, 2);
        foreach ($overdue as $key=>$v){
            $overdue[$key]['icon'] = cmf_get_image_url($v['icon']);
            $overdue[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('Y-m-d',$v['start_time']);
            $overdue[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('Y-m-d',$v['end_time']);
        }
        $data['received'] = $received;
        $data['overdue'] = $overdue;
        $this->set_message(200,'获取成功',$data);
    }

    /**
     * [游戏账单]
     * @author 郭家屯[gjt]
     */
    public function myorder()
    {
        $request = $this->request->param();
        $base = new BaseHomeController();
        $model = new SpendModel();
        $map['user_id'] = USER_ID;
        $map['pay_status'] = 1;
        $extend['order'] = 'pay_time desc';
        $extend['row'] = $request['limit']?:10;
        $extend['field'] = 'game_name,pay_amount,pay_way,pay_time';
        $list = $base->data_list($model, $map, $extend)->each(function($item,$key){
            $item['pay_time'] = date('Y-m-d H:i:s',$item['pay_time']);
            $item['pay_way'] = get_info_status($item['pay_way'],1);
            return $item;
        });
        //总充值
        $total = $model->where($map)->sum('pay_amount');
        $data['data'] = $list?$list->toarray()['data']:[];
        $data['total'] = $total;
        $this->set_message(200,'获取成功',$data);
    }
    /**
     * @函数或方法说明
     * @短信验证
     * @param string $phone
     * @param string $code
     * @param int $type
     *
     * @return bool
     *
     * @author: 郭家屯
     * @since: 2020/2/17 11:45
     */
    private function  sms_verify($phone = "", $code = "", $type = 2)
    {
        $session = session($phone);
        if (empty($session)) {
            $this->set_message(1012, "请先获取验证码");
        }
        #验证码是否超时
        $time = time() - session($phone . ".create");

        if ($time > 180) {//$tiem > 60
            $this->set_message(1013, "验证码已失效，请重新获取");
        }
        #验证短信验证码
        if (session($phone . ".code") != $code) {
            $this->set_message(1014, "验证码错误");
        }
        if ($type == 1) {
            $this->set_message(200, "正确");
        } else {
            return true;
        }
    }

    /**
     * 发送验证码
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @since: 2019\3\27 0027 11:18
     * @author: fyj301415926@126.com
     */
    public function sendCode()
    {
        $request = $this->request->param();
        $phone = $request['phone'];
        $account = $request['account'];
        $user = get_user_entity($account,true,'phone,email');
        if($user['phone'] && $user['phone'] == $phone){
            $this->send_sms();
        }elseif($user['email'] && $user['email'] == $phone ){
            $this->send_email();
        }
        else{
            $this->set_message(1042,'手机号或邮箱地址错误');
        }
    }

    /**
     * @函数或方法说明
     * @发送邮件
     *
     * @author: 郭家屯
     * @since: 2020/3/5 10:32
     */
    public function send_email()
    {
        $request = $this->request->param();
        $email = $request['email']?:$request['phone'];
        $account = $request['account'];
        $user_id = $request['user_id'];
        if (!cmf_check_email($email)) {
            $this->set_message(1006, "邮箱号码格式错误");
        }
        //验证图片验证码
        if (empty($request['img_verify_code'])) {
            $this -> set_message(1040, '图片验证码不能为空');
        }
        $captcha = new Captcha();
        if (!$captcha -> check($request['img_verify_code'])) {
            $this -> set_message(1040, '验证码错误');
        }
        $userModel = new UserModel();
        if ($request['reg'] == 1) { /* 注册检查 */
            $user = $userModel->where('email|account', $email)->find();
            if ($user) {
                $this->set_message(1007, "该邮箱已被注册或绑定过");
            }
            $msg = "注册";
        } elseif ($request['reg'] == 2) {/* 忘记密码检查 */
            $user = $userModel->where('account', $account)->where('email', $email)->find();
            if (empty($user)) {
                $this->set_message(1008, "邮箱号码错误");
            }
            $msg = "找回密码";
        } elseif ($request['reg'] == 3) {/* 解绑绑定检查 */
            $user = $userModel->where('id', $user_id)->where('email', $email)->find();
            if (empty($user)) {
                $this->set_message(1008, "用户名不存在");
            }
            $msg = "解绑";
        } elseif ($request['reg'] == 4) {/* 绑定邮箱检查  */
            $user = $userModel->where('email', $email)->find();
            if ($user) {
                $this->set_message(1007, "该邮箱已被注册或绑定过");
            }
            $msg = "绑定";
        }else {
            $this->set_message(1009, "未知发送类型");
        }
        $session = session($email);
        if (!empty($session) && (time() - $session['create_time']) < 60) {
            $this->set_message(1043,"验证码发送过于频繁，请稍后再试");
        }
        $code = rand(100000, 999999);
        $smtpSetting = cmf_get_option('email_template_verification_code');
        $smtpSetting['template'] = str_replace('{$code}', $code, htmlspecialchars_decode($smtpSetting['template']));
        $result = cmf_send_email($email, str_replace('注册', $msg, $smtpSetting['subject']), $smtpSetting['template']);
        if ($result['error'] == 0) {
            session($email, ['code' => $code, 'create_time' => time()]);
            $this->set_message(200,"验证码发放成功，请注意查收");
        } else {
            $this->set_message(1044,"发送失败，请检查邮箱地址是否正确");
        }
    }
    /**
     * @函数或方法说明
     * @发送短信
     * @author: 郭家屯
     * @since: 2020/2/17 11:26
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function send_sms()
    {
        $request = $this->request->param();
        $phone = $request['phone'];
        $account = $request['account'];
        $user_id = $request['user_id'];
        if (!cmf_check_mobile($phone)) {
            $this->set_message(1006, "手机号码格式错误");
        }

        //验证图片验证码
        if (empty($request['img_verify_code'])) {
            $this -> set_message(1040, '图片验证码不能为空');
        }
        $captcha = new Captcha();
        if (!$captcha -> check($request['img_verify_code'])) {
            $this -> set_message(1040, '验证码错误');
        }

        $userModel = new UserModel();
        if ($request['reg'] == 1) { /* 注册检查 */
            $user = $userModel->where('phone|account', $phone)->find();
            if ($user) {
                $this->set_message(1007, "该手机号已被注册或绑定过");
            }
        } elseif ($request['reg'] == 2) {/* 忘记密码检查 */
            $user = $userModel->where('account', $account)->where('phone', $phone)->find();
            if (empty($user)) {
                $this->set_message(1008, "手机号错误");
            }

        } elseif ($request['reg'] == 3) {/* 解绑绑定检查 */
            $user = $userModel->where('id', $user_id)->where('phone', $phone)->find();
            if (empty($user)) {
                $this->set_message(1008, "解绑手机号不存在");
            }
        } elseif ($request['reg'] == 4) {/* 绑定手机检查  */
            $user = $userModel->where('phone', $phone)->find();
            if ($user) {
                $this->set_message(1007, "该手机号已被绑定过");
            }
        }elseif ($request['reg'] == 5){/* 登录检查 */
            $user = $userModel->where('phone', $phone)->where('account','neq',$phone)->find();
            if ($user) {
                $this->set_message(1007, "该手机号已被注册或绑定过");
            }
        }elseif ($request['reg'] == 6) {/* 交易密码验证手机号 */
            $user = $userModel->where('id', $user_id)->where('phone', $phone)->find();
            if (empty($user)) {
                $this->set_message(1008, "请输入正确手机号");
            }
        }  else {
            $this->set_message(1009, "未知发送类型");
        }
        $msg = new MsgModel();
        $data = $msg::get(1);
        if (empty($data)) {
            $this->set_message(1010, "没有配置短信发送");
        }
        $data = $data->toArray();
        if ($data['status'] == 1) {
            $xigu = new Xigu($data);
            $res = sdkchecksendcode($phone, $data['client_send_max'], 2);
            if(!empty($res)){
                $this->set_message(1011, $res);
            }
            $sms_code = session($phone);//发送没有session 每次都是新的
            $sms_rand = sms_rand($phone);
            $rand = $sms_rand['rand'];
            $new_rand = $sms_rand['rand'];
            /// 产生手机安全码并发送到手机且存到session
            $param = $rand . "," . '3';
            $result = json_decode($xigu->sendSM($phone, $data['captcha_tid'], $param), true);
            $result['create_time'] = time();
            $result['pid'] = 0;
            $result['phone'] = $phone;
            $result['create_ip'] = get_client_ip();
            Db::table('tab_sms_log')->field(true)->insert($result);
            #TODO 短信验证数据
            if ($result['send_status'] == '000000') {
                $safe_code['code'] = $rand;
                $safe_code['phone'] = $phone;
                $safe_code['time'] = $new_rand ? time() : $sms_code['time'];
                $safe_code['delay'] = 3;
                $safe_code['create'] = $result['create_time'];
                session($phone, $safe_code);
                $this->set_message(200, "发送成功");
            } else {
                $this->set_message(1011, "验证码发送失败，请重新获取");
            }
        } else {
            $this->set_message(1010, "没有配置短信发送");
        }
    }

    /**
     * [验证邮箱]
     * @author 郭家屯[gjt]
     */
    protected function email_verify($email, $code, $type = 2)
    {
        $code_result = $this->emailVerify($email, $code);
        if ($code_result == 1) {
            if ($type == 1) {
                $this->set_message(200,"验证成功");
            } else {
                return true;
            }
        } else {
            if ($code_result == 2) {
                $this->set_message(1045,"请先获取验证码");
            } elseif ($code_result == -98) {
                $this->set_message(1046,"验证码超时");
            } elseif ($code_result == -97) {
                $this->set_message(1047,"验证码不正确，请重新输入");
            }
        }
    }
    /**
     * @param $email
     * @param $code
     * @param int $time
     * @return int
     * 验证 邮箱验证码
     */
    protected function emailVerify($email, $code, $time = 30)
    {
        $session = session($email);
        if (empty($session)) {
            return 2;
        } elseif ((time() - $session['create_time']) > $time * 60) {
            return -98;
        } elseif ($session['code'] != $code) {
            return -97;
        }
        return 1;
    }


    /**
     * @函数或方法说明
     * @关于我们
     * @param int $promote_id
     *
     * @author: 郭家屯
     * @since: 2020/2/21 11:40
     */
    public function about_us()
    {
        $request = $this->request->param();
        $kefu = cmf_get_option('kefu_set');
        $app = cmf_get_option('app_set');
        $data = array(
                'qq' => $kefu['pc_set_server_qq']?:'',
                'qq_group' => $kefu['pc_qq_group']?:'',
                'qq_group_key'  =>  $kefu['pc_qq_group_key']?:'',
                'network' => $kefu['pc_work_time']?:'',
                'icon' => $app['app_logo']?cmf_get_image_url($app['app_logo']):'',
                'version' => $app['and_version']?:'',
                'version_name' => $app['version_name']?:'',
                'app_name' => $app['app_name']?:'',
                'app_copyright'  =>  $app['app_copyright']?:'',
                'app_copyright_en'  =>  $app['app_copyright_en']?:'',
                'guide_1' => $app['guide_1'] ? cmf_get_image_url($app['guide_1']):'',
                'guide_2' => $app['guide_2'] ? cmf_get_image_url($app['guide_2']):'',
                'guide_3' => $app['guide_3'] ? cmf_get_image_url($app['guide_3']):'',
                'about_icon' => $app['about_icon'] ? cmf_get_image_url($app['about_icon']):'',
                'app_backgroup' => $app['app_backgroup'] ? cmf_get_image_url($app['app_backgroup']):'',
                'ios_version' => $app['ios_version']?:'',
        );

        //增加返回溪谷客服系统开启状态和链接-渠道不涉及-20210707-byh
        $data['app_xgkf_switch'] = get_xgkf_info(0);//溪谷客服系统0=未开启,1=开启
        if(get_xgkf_info(0)){//已开启
            $data['app_xgkf_url'] = get_xgkf_info(1);//获取溪谷客服URL
        }else{
            $data['app_xgkf_url'] = '';
        }

        //增加一键登录的开关和iOS一键登录的对应business_id参数-byh-20210624-start
        $data['yidun_login_switch'] = $app['yidun_login_switch']??0;
        if($request['sdk_version'] == 2){
            $data['yidun_business_id'] = $app['yidun_business_id']??'';//下方需要继续查询渠道的配置
        }
        //增加对登录logo字段返回
        $data['app_login_logo'] = cmf_get_image_url($app['app_login_logo']);
        //增加一键登录的开关和iOS一键登录的对应business_id参数-byh-20210624-end

        //渠道信息
        if ($request['promote_id'] > 0) {
            $model = new PromoteunionModel();
            $map['union_id'] = $request['promote_id'];
            $resule = $model->field('union_set')->where($map)->find();
            $resule = empty($resule) ? [] : $resule->toarray();
            if($resule && $resule['union_set']){
                $promote_config  = json_decode($resule['union_set'], true);
                $data['qq'] = $promote_config['qq']?:$data['qq'];
                $data['qq_group'] = $promote_config['qq_group']?:$data['qq_group'];
                $data['qq_group_key'] = $promote_config['qq_group_key']?:$data['qq_group_key'];
                $data['app_name'] = $promote_config['app_name_ico']?:$data['app_name'];
                $data['app_login_logo'] = empty($promote_config['app_login_logo'])?cmf_get_image_url($data['app_login_logo']):cmf_get_image_url($promote_config['app_login_logo']);//渠道的登录logo

                //如果渠道有QQ的配置,则使用渠道的QQ,溪谷客服系统返回关闭-20210707-byh
                if($promote_config['qq']){
                    $data['app_xgkf_switch'] = '0';
                    $data['app_xgkf_url'] = '';
                }
            }
        }
        if($request['sdk_version'] == 1){
            $and = Db::table('tab_app')->where('version',1)->field('file_url,remark')->find();
            $data['remark'] = $and['remark'];
            if(PROMOTE_ID > 0){
                $and = Db::table('tab_promote_app')->where('promote_id',PROMOTE_ID)->where('app_version',1)->field('dow_url')->find();
                if(!empty($and['dow_url'])){
                    $and['file_url'] = $and['dow_url'];
                }
            }
            $data['app_download'] = cmf_get_domain() . '/upload/' .$and['file_url'];
        }else{
            $ios = Db::table('tab_app')->where('version',2)->field('plist_url,remark,type,file_url')->find();
            $data['remark'] = $ios['remark'];
            if(PROMOTE_ID > 0){
                $ios = Db::table('tab_promote_app')->where('promote_id',PROMOTE_ID)->where('app_version',2)->field('plist_url,type,dow_url as file_url,ios_version,super_url,yidun_business_id')->find();
            } else {
                $ios['super_url'] = $ios['file_url'];
            }
            // 渠道自定义APP版本号
            if(!empty($ios['ios_version'])){
                $data['ios_version'] = $ios['ios_version'];
            }
            if($ios['type'] == 1){
                $data['ios_app_download'] = $ios['super_url']; // 超级签地址
            }else{
                $data['ios_app_download'] = $ios['plist_url'] ? cmf_get_file_download_url($ios['plist_url']) : '';
            }
            //强制https
            if (strpos($ios['plist_url'], 'https://') === false) {
                $data['ios_app_download'] = str_replace("http://", "https://", $data['ios_app_download']);
            }
            //渠道自定义易盾business_id-20210625-byh
            if(!empty($ios['yidun_business_id'])){
                $data['yidun_business_id'] = $ios['yidun_business_id'];
            }
        }
        $this->set_message(200,"成功",$data);
    }
    /**
     * @函数或方法说明
     * @福利列表
     * @author: 郭家屯
     * @since: 2019/7/8 16:49
     */
    public function user_rebate()
    {
        $model = new SpendRebateRecordModel();
        $map['user_id'] = USER_ID;
        $p = $this->request->param('page');
        $limit = $this->request->param('limit',10);
        $record = $model->get_my_rebate($map,$p,$limit);
        //总返利
        $total = $model->where($map)->sum('ratio_amount');
        $data['data'] = $record;
        $data['total'] = $total;
        $this->set_message(200,'获取成功',$data);
    }
    /**
     * 获取授权码
     * @descript author
     * @author yyh
     * @since 2020-05-26
     */
    public function get_auth_code()
    {
        if(!$this->request_data['user_id']){
            $this->set_message(1003, "账号信息丢失");
        }
        $code = $this->request_data['user_token'].'_xigu_'.$this->request_data['game_id'].'_xigu_'.$this->request->ip().'_xigu_'.sp_random_string(10);
        $data['code'] = $code;
        $data['ip'] = $this->request->ip();
        $data['create_time'] = time();
        $data['update_time'] = time();
        $res = Db::table('tab_user_auth_code')->insertGetId($data);
        if($res){
            $this->set_message(200,'授权码获取成功',$code);
        }else{
            $this->set_message(1085,'授权码获取失败');
        }
    }

    public function my_down_game()
    {
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $request = $this->request->param();
        $model = new \app\game\model\GameModel();
        $map['user_id']=$request['user_id'];
        $map['is_del'] =0;
        //$map['sdk_version'] = 3;
        $list = $model->get_play_game($map,100);
        foreach ($list as $key=>$value){
            $list[$key]['icon'] = cmf_get_image_url($value['icon']);
            $list[$key]['play_time'] = date($value['play_time'],'Y-m-d H:i:s');
            //$list[$key]['game_name'] = $value['relation_game_name'];
        }
        $this->set_message(200,'获取数据成功',$list);
    }

    public function delete_down_game()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        $ids = explode(',',$ids);
        if (empty($ids)) {
            $this->set_message(1053,'请求数据不完整');
        }
        $user_id = $data['user_id'];
        $model = new \app\game\model\GameModel;
        $result = $model->deleteMyPlayGame(['user_id' => $user_id, 'id' => ['in', $ids]]);
        if($result){
            $this->set_message(200,'删除成功');
        }else{
            $this->set_message(1032,'删除失败');
        }
    }

    public function my_collect_game(){

        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $request = $this->request->post('');
        $model = new GameModel();
        $map['g.sdk_version'] = ['in',[$request['sdk_version'],3]];
        $map['g.game_status'] = 1;
        $list = $model->getMyCollectList(USER_ID,$map);
        $data_key = [];
        foreach ($list as $key => &$vo) {
            $data_key[] = $vo['create_time'];
            $vo['icon'] = cmf_get_image_url($vo['icon']);
            $vo['game_name'] = $vo['relation_game_name'];
        }
        unset($vo);
        $data_key =array_unique($data_key);
        rsort($data_key);
        $new_list = [];
        foreach ($data_key as $k=>$v){
            $new_list[$k]['date'] = $v;
            $new_list[$k]['list'] = [];
            foreach ($list as $key => &$vo) {
                if($v==$vo['create_time']){
                    $new_list[$k]['list'][] = $vo;
                    continue;
                }
            }
        }
        $this->set_message(200,'获取数据成功',$new_list);
    }

    public function delete_collect_game()
    {
        $ids = $this->request->post('ids');
        $ids = explode(',',$ids);
        if (empty($ids)) {
            $this->set_message(1053,'请求数据不完整');
        }
        $model = new GamecollectModel();
        $result = $model->where('id', 'in', $ids)->delete();
        if($result){
            $this->set_message(200,'删除成功');
        }else{
            $this->set_message(1032,'删除失败');
        }
    }

    /**
     * @函数或方法说明
     * @绑定或解绑邮箱
     * @author: 郭家屯
     * @since: 2020/8/18 16:10
     */
    public function email()
    {
        $data = $this->request->param();
        if (!cmf_check_email($data['email'])) {
            $this->set_message(1092,"邮箱地址格式错误");
        }
        $this->email_verify($data['email'], $data['code']);
        if($data['is_bind'] == 1){//绑定
            $save['email'] = $data['email'];
        }else{//解绑
            $save['email'] = '';
        }
        $model = new UserModel();
        $result = $model->update_user_info($data['user_id'], $save);
        if ($result) {
            if($data['is_bind']==1) {
                $model -> task_complete($data['user_id'], 'bind_email', $save['email']);//绑定任务
            }
            $this->set_message(200,$data['is_bind']==1?'绑定成功':'解绑成功');
        } else {
            $this->set_message(1037,$data['is_bind']==1?'绑定失败':'解绑失败');
        }
    }

    /**
     * @函数或方法说明
     * @绑定或解绑手机
     * @author: 郭家屯
     * @since: 2020/8/18 16:10
     */
    public function phone()
    {
        $data = $this->request->param();
        if (empty($data['phone'])) {
            $this->set_message(1050,'请输入11位手机号码');
        }
        if (!cmf_check_mobile($data['phone'])) {
            $this->set_message(1051,"手机号码格式错误");
        }
        $this->sms_verify($data['phone'], $data['code']);
        session($data['phone'],null);
        if($data['is_bind'] == 1){//绑定
            $save['phone'] = $data['phone'];
        }else{//解绑
            $save['phone'] = '';
        }
        $model = new UserModel();
        $result = $model->update_user_info($data['user_id'], $save);
        if ($result) {
            if($data['is_bind']==1){
                //绑定任务
                $model->task_complete($data['user_id'],'bind_phone',$save['phone']);
            }
            $this->set_message(200,$data['is_bind']==1?'绑定成功':'解绑成功');
        } else {
            $this->set_message(1037,$data['is_bind']==1?'绑定失败':'解绑失败');
        }
    }

    /**
     * [第三方登录方式]
     * @author 郭家屯[gjt]
     */
    public function thirdparty()
    {
        $model = new UserConfigModel();
        $qqData = $model->getSet('app_qq_login');
        $data['qq'] = $qqData['status'] ==1?1:0;
        $wxData = $model->getSet('app_weixin_login');
        $data['wx'] = $wxData['status'] ==1?1:0;
        $this->set_message(200,"获取成功",$data);
    }

    /**
     * [第三方登录]
     * @author 郭家屯[gjt]
     */
    public function oauth_login()
    {
        $request = $this->request->param();
        $openid = $request['openid'];
        if ($request['login_type'] == "wx") {
            $param_set = (new UserConfigModel())->getSet('app_weixin_login');
            $config = $param_set['config'];
            if (empty($config)) {
                $this->set_message(1051, '微信登录appid/appsecret为空');
            }
            Vendor("wxPayPubHelper.WxPayPubHelper");
            // 使用jsapi接口
            $jsApi = new \JsApi_pub($config['appid'], $config['appsecret'], $request['code']);
            $wx = $jsApi->create_openid($config['appid'], $config['appsecret'], $request['code']);
            //unionid如果开发者有在多个公众号，或在公众号、移动应用之间统一用户帐号的需求，需要前往微信开放平台（open.weixin.qq.com）绑定公众号后，才可利用UnionID机制来满足上述需
            if (empty($wx['unionid'])) {
                $this->set_message(1052, '请到微信开放平台（open.weixin.qq.com）绑定公众号');
            }
            $unionid = $wx['unionid'];
            $auth = new WechatAuth($config['appid'], $config['appsecret'], $wx['access_token']);
            $userInfo = $auth->getUserInfo($wx['openid']);
            $register_type = 3;
            $head_img = $userInfo['headimgurl'];
        } elseif ($request['login_type'] == "qq") {
            $register_type = 4;
            $userconfig = new UserConfigModel();
            $config = $userconfig->getSet('app_qq_login');
            $qq_parm['access_token'] = $request['accessToken'];
            $qq_parm['oauth_consumer_key'] = $config['config']['appid'];
            $qq_parm['openid'] = $config['config']['key'];
            $qq_parm['format'] = "json";
            $unionid = get_union_id($request['accessToken']);
            if (empty($unionid)) {
                $this->set_message(1054, '腾讯公司应用未打通 未将所有appid设置统一unionID');
            }
            $url = "https://graph.qq.com/user/get_user_info?" . http_build_query($qq_parm);
            $qq_url = json_decode(file_get_contents($url), true);
            $head_img = $qq_url['figureurl_qq_1 '];
        }else{
            $this->set_message(1055,'登录方式不存在');
        }

        //查询用户名是否存在
        $map['unionid'] = $unionid;
        $usermodel = new UserModel();
        $data = $usermodel->field('id,account')->where($map)->find();
        $data = empty($data)?[]:$data->toArray();
        if (empty($data)) {
            //注册
            do {
                $data['account'] = $request['login_type'] . '_' . sp_random_string();
                $account = $usermodel->where(['account' => $data['account']])->find();
            } while (!empty($account));
            $data['password'] = sp_random_string(8);
            $data['nickname'] = $data['account'];
            $data['unionid'] = $unionid;
            $data['head_img'] = !empty($head_img) ? $head_img : '';//头像
            $data['promote_id'] = !PROMOTE_ID? 0 : PROMOTE_ID;
            $data['promote_account'] = PROMOTE_ID?get_promote_name(PROMOTE_ID):'';
            $data['parent_id'] = !PROMOTE_ID ? 0 : get_fu_id(PROMOTE_ID);
            $data['parent_name'] = !PROMOTE_ID ? '' : get_parent_name(PROMOTE_ID);
            $data['register_way'] = 2;
            $data['register_type'] = $register_type;
            $data['equipment_num'] = $request['equipment_num'];
            $data['type'] = 4;
            $result = $usermodel->register($data);
            if ($result == -1) {
                $this->set_message(1028, '注册失败');
            } else {
                $return['user_id'] = $result['id'];
                $return['account'] = $result['account'];
                $return['nickname'] = $result['nickname'];//昵称
                $return['head_img'] = cmf_get_image_url($result['head_img']);//头像
                $return['balance'] = $result['balance']??'';   //平台币
                $return['token'] = $result['token'];
            }
        } else {
            //第三方登录
            $request['type'] = 4;
            if (empty($request['account'])) {
                $request['account'] = $data['account'];
            }
            $result = $usermodel->oauth_login($request);
            switch ($result) {
                case 1005:
                    $this->set_message(1005, '用户名不存在');
                    break;
                case 1007:
                    $this->set_message(1007, '账号被锁定');
                    break;
                default:
                    $return['user_id'] = $result['id'];
                    $return['account'] = $result['account'];
                    $return['nickname'] = $result['nickname'];//昵称
                    $return['head_img'] = cmf_get_image_url($result['head_img']);//头像
                    $return['balance'] = $result['balance'];   //平台币
                    $return['token'] = $result['token'];
                    break;
            }
        }
        $this->set_message(200,"登录成功",$return);
    }

    /**
     * [非本地上传]
     * @author 郭家屯[gjt]
     */
    protected function uploadcloud($file_url = '', $upload_url = '', $filetype = '', $suffix = '',$tmp_flag=0)
    {
        $storage = cmf_get_option('storage');
        if ($storage['type'] != 'Local') { //  增加存储驱动
            $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
            session_write_close();
            $upload_res = $storage->upload($file_url, './upload/' . $file_url, $filetype,'',$tmp_flag);
            return $upload_res;
        }
    }


    /**
     * @获取图片验证码
     *
     * @author: zsl
     * @since: 2021/7/29 9:59
     */
    public function imageVerify()
    {
        $captcha = new Captcha();
        $captcha -> length = 4;
        $captcha -> codeSet = '0123456789';
        return $captcha -> entry();
    }

    /**
     * @获取图片验证码base64
     *
     * @author: zsl
     * @since: 2021/8/4 10:25
     */
    public function getImageVerify()
    {
        $captcha = new Captcha();
        $captcha -> length = 4;
        $captcha -> codeSet = '0123456789';
        $imageBase64 = $captcha -> entry('', false);
        $this -> set_message(200, "获取成功", $imageBase64);
    }

    /**
     * @注销账号
     *
     * @author: Juncl
     * @time: 2021/09/07 10:46
     */
    public function unsubscribe()
    {
        $request = $this->request->param();
        if(empty($request['user_id'])){
            $this -> set_message(3001,'用户信息不能为空');
        }
        $lUnsubscribe = new UnsubscribeLogic();
        if ($request['type'] == '1') {
            //注销
            $result = $lUnsubscribe -> unsubscribe($request['user_id']);
        } else {
            //取消注销账号
            $result = $lUnsubscribe -> cancelUnsub($request['user_id']);
        }
        if ($result['code'] == 0) {
            $this -> set_message(3002, $result['msg']);
        } else {
            $this -> set_message(200, $result['msg']);
        }
    }

    /**
     * 发送短信验证码
     *
     * @author: Juncl
     * @time: 2021/09/07 10:46
     */
    public function verifyUnsub()
    {
        $request = $this->request->param();
        if(empty($request['user_id'])){
            $this -> set_message(3001,'用户信息不能为空');
        }
        $lUnsubscribe = new UnsubscribeLogic();
        $userInfo = get_user_entity($request['user_id'],false,'phone,email');
        // 手机号和邮箱都未绑定则直接注销
        if(empty($userInfo['phone']) &&empty($userInfo['email'])){
            $result = $lUnsubscribe -> unsubscribe($request['user_id']);
            if ($result['code'] == 0) {
                $this -> set_message(3002, $result['msg']);
            } else {
                $this -> set_message(200, $result['msg'],['type'=>3]);
            }
        }else{
            $result = $lUnsubscribe->sendVerifySms($request['user_id']);
            if($result['status']){
                $this -> set_message(200, $result['msg'],$result['data']);
            }else{
                $this -> set_message(3003, $result['msg']);
            }
        }
    }

    /**
     * 验证发送邮箱/短信验证码
     *
     * @author: Juncl
     * @time: 2021/09/07 19:14
     */
    public function verifyCode()
    {
        $request = $this->request->param();
        if(empty($request['user_id'])){
            $this -> set_message(3001,'用户信息不能为空');
        }
        $lUnsubscribe = new UnsubscribeLogic();
        $result = $lUnsubscribe->verifyCode($request['code'],$request['type'],$request['phone']);
        if($result['status']){
            // 短信验证成功则直接注销
            $res = $lUnsubscribe -> unsubscribe($request['user_id']);
            if ($res['code'] == 0) {
                $this -> set_message(3002, $res['msg']);
            } else {
                $this -> set_message(200, $res['msg']);
            }
        }else{
            $this -> set_message(3003, $result['msg']);
        }
    }

    /**
     * @获取用户注册协议,隐私协议,用户注销协议
     *
     * @author: zsl
     * @since: 2021/7/30 19:48
     */
    public function get_protocol()
    {
        $ProtocolModel = new \app\site\model\PortalPostModel();
        $data['protocol_title'] = $ProtocolModel -> where('id', '=', 27) -> value('post_title');
        $data['protocol_url'] = url('mobile/user/protocol', ['issdk' => 1], true, true);
        $data['privacy_title'] = $ProtocolModel -> where('id', '=', 16) -> value('post_title');
        $data['privacy_url'] = url('mobile/user/privacy', ['issdk' => 1], true, true);
        $data['unsubscribe_protocol_title'] = $ProtocolModel -> where('id', '=', 2) -> value('post_title');
        $data['unsubscribe_protocol_url'] = url('mobile/user/unsubscribe_protocol', ['issdk' => 1], true, true);
        $this -> set_message(200, '请求成功',$data);
    }

}
