<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/27
 * Time: 15:20
 */

namespace app\sdksimplify\controller;

use app\common\logic\GameLogic;
use app\common\logic\PayLogic;
use app\common\logic\PointLogic;
use app\member\model\UserBehaviorModel;
use app\member\model\UserModel;
use app\recharge\model\CouponRecordModel;
use app\site\model\KefuModel;
use app\site\model\NoticeModel;
use app\site\model\GuessModel;
use app\site\model\AdvModel;
use app\member\model\UserPlayModel;
use app\recharge\model\SpendBalanceModel;
use app\recharge\model\SpendModel;
use app\promote\model\PromoteunionModel;
use app\user\model\UserUnsubscribeModel;
use think\Db;
use think\Request;
use cmf\lib\Upload;
use app\game\logic\WelfareLogic;

class CenterController extends BaseController
{

    /**
     * [获取个人中心首页信息]
     * @author 郭家屯[gjt]
     */
    public function getUserInfo()
    {
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $user = get_user_entity($data['user_id'],false,'id,balance,account,cumulative,nickname,id,head_img,phone,email,age_status,real_name,idcard,point,vip_level,register_type,sex,third_authentication');
        $user['head_img'] = cmf_get_image_url($user['head_img']);
        $user['read_status'] = 0;
        $user['game_name'] = get_game_entity($data['game_id'],'game_name')['game_name'];
        $user['next_vip'] = get_next_vip_level($user['cumulative']);
        //获取绑币
        $user_play = get_user_play($data['user_id'],$data['game_id']);
        $user['bind_balance'] = $user_play['bind_balance'];
        //获取未读消息状态
        $model = new NoticeModel();
        $user = $model->getNoticeMark($data,$user);
        $sign_detail = (new PointLogic())->sign_detail($data['user_id']);
        if($sign_detail==-1){
            $user['sign_detail'] = ['sign_status'=>0,'today_signed'=>0];
        }else{
            $user['sign_detail'] = ['sign_status'=>$sign_detail['status'],'today_signed'=>$sign_detail['today_signed']];
        }
        //查询用户注销状态
        $mUnsub = new UserUnsubscribeModel();
        $unsubStatus = $mUnsub -> getUnsubscribeStatus($user['id']);
        $user['is_unsubscribe'] = $unsubStatus;
        $this->set_message(200,'获取成功',$user);
    }

    /**
     * [VIP等级获取]
     * @author 郭家屯[gjt]
     */
    public function vip_level_list()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $vip_set = cmf_get_option('vip_set');
        $data = $vip_set['vip'] ? explode(',',$vip_set['vip']) : [];
        foreach ($data as $key=>$v){
            if($data[$key+1]>0){
                $vip[$key] = $v.'-'.($data[$key+1]-1);
            }else{
                $vip[$key] = $v."及以上";
            }
        }
        $this->set_message(200, "获取成功",$vip);
    }

    /**
     * [上传头像]
     * @author 郭家屯[gjt]
     */
    public function set_head_portrait()
    {
        header("Content-Type:text/html;charset=utf-8");
        $data = json_decode(base64_decode($this->request->param('key')), true);
        $upload = new Upload();
        $upload->setFormName('fileimg');
        $upload->setFileType('image');
        $upload->setApp('member');// 用户模块excelData
        // 上传文件
        $local=0;
        $tmp_flag=2;
        $info = $upload->upload($local,$tmp_flag);

        $cloud_res_code = $info['code'] ?? 0;
        if($cloud_res_code == -2){
            // 云上传失败
            $this->set_message(1041, '请正确配置云存储信息稍后重试.');
            exit;
            // $this->set_message(0, 1040, '上传头像失败,请配置云存储');
        }

        if (!$info) {// 上传错误提示错误信息
            $this->set_message(0, 1040, $upload->getError());
        } else {// 上传成功
            $url = cmf_get_image_url($info['url']);
            $model = new UserModel();
            $result = $model->where("id", $data['user_id'])->setField('head_img', $url);
            if ($result) {
                $this->set_message(200, "获取成功",$url);
            } else {
                $this->set_message(1041, '上传头像失败');
            }

        }
    }

    /**
     * [获取今日公告]
     * @author 郭家屯[gjt]
     */
    public function today_notice()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $model = new NoticeModel();
        $map['game_id'] = $request['game_id'];
        $lists = $model->getTodayNotice($map);

        if (!empty($lists)) {
            foreach ($lists as &$v) {
                $v['detail_url'] = url("notice_detail", ['id' => $v['id'],'game_id'=>$request['game_id'],'user_id'=>$request['user_id']],'',true);
            }
        }

        $this->set_message(200, "获取成功",$lists);
    }
    /**
     * [获取今日公告详情]
     * @author 郭家屯[gjt]
     */
    public function notice_detail()
    {
        $request = $this -> request -> param();
        $model = new NoticeModel();
        $map['id'] = $request['id'];
        $detail = $model -> getNoticeDetail($map);
        $detail['content'] = str_replace("\r\n",'<br/>',$detail['content']);
        $this -> assign('detail', $detail);
        //插入或更新用户行为，记录阅读文章的最新时间
        $behaviormodel = new UserBehaviorModel();
        $behaviormodel -> set_record($request);
        return $this -> fetch();

//        $this->set_message(200, "获取成功",$lists);
    }

    /**
     * [猜你喜欢]
     * @author 郭家屯[gjt]
     */
    public function guess_like()
    {
        $model = new GuessModel();
        $lists = $model->getLists();
        $this->set_message(200, "获取成功",$lists);
    }

    /**
     * [获取sdk广告]
     * @author 郭家屯[gjt]
     */
    public function get_adv()
    {
        $model = new AdvModel();
        $adv = $model->getAdv('simple_sdk_close_account');
        $this->set_message(200, "获取成功",$adv);
    }

    /**
     * [平台币充值记录]
     * @author 郭家屯[gjt]
     */
    public function balance_record()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $model = new SpendBalanceModel();
        $map['pay_id'] = $request['user_id'];
        $data = $model->getLists($map, $request['p'], $request['row']);
        $this->set_message(200, "获取成功",$data,2);
    }

    /**
     * [平台币充值记录]
     * @author 郭家屯[gjt]
     */
    public function spend_record()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $model = new SpendModel();
        $map['user_id'] = $request['user_id'];
        $data = $model->getLists($map, $request['p'], $request['row']);
        $this->set_message(200, "获取成功",$data,2);
    }

    /**
     * [获取客服问题  帮助中心]
     * @author 郭家屯[gjt]
     */
    public function customer_question_list()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $model = new KefuModel();
        $lists = $model->getLists();
        foreach ($lists as $k=>$v){
            $lists[$k]['second_title'] = explode(',',$v['second_title']);
            $lists[$k]['icon'] = $v['icon'] ? cmf_get_image_url($v['icon']) : '';
        }
        $this->set_message(200, "获取成功",$lists);
    }

    /**
     * [获取客服问题详情  帮助中心]
     * @author 郭家屯[gjt]
     */
    public function customer_question_detail()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $model = new KefuModel();
        $lists = $model->getTypeLists($request['mark']);
        $this->set_message(200, "获取成功",$lists);
    }

    /**
     * [获取悬浮球状态]
     * @author 郭家屯[gjt]
     */
    public function get_suspend()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $config = cmf_get_option('sdksimplify_set');
        $data['suspend_show_status'] = $config['suspend_show_status'];
        $data['suspend_icon'] = cmf_get_image_url($config['suspend_icon']);
        $config_kf = cmf_get_option('kefu_set');
        $data['APP_QQ'] = $config_kf['pc_set_server_qq'];
        //渠道悬浮球设置
        if ($request['promote_id'] > 0) {
            $model = new PromoteunionModel;
            $map['union_id'] = $request['promote_id'];
            $resule = $model->field('union_set')->where($map)->find();
            $resule = empty($resule) ? [] : $resule->toarray();
            if($resule && $resule['union_set']){
                $promote_config  = json_decode($resule['union_set'], true);
                $data['suspend_icon'] = cmf_get_image_url($promote_config['xfq']);
                $data['APP_QQ'] = $promote_config['qq'] ? :$config_kf['pc_set_server_qq'];
                $data['suspend_show_status'] = $promote_config['xfq_show_switch'];
            }
        }
        $data['loginout_status'] = $config['loginout_status'] ? : 0;//注销按钮
        $this->set_message(200, "获取成功",$data);
    }

    /**
     * [帮助中心客服]
     * @author 郭家屯[gjt]
     */
    public function customer_contact()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $config = cmf_get_option('kefu_set');
        $data['APP_QQ'] = $config['pc_set_server_qq'];
        $data['APP_TEL'] = $config['pc_set_server_tel'];
        $data['QQ_GROUP_KEY'] = $config['pc_qq_group_key'];
        $data['APP_QQ_GROUP'] = $config['pc_qq_group'];
        $data['APP_EMAIL'] = $config['pc_t_email'];
        //增加公众号和二维码字段信息返回
        $_info = cmf_get_option('media_set');
        $data['WX_GZH_NAME'] = $_info['pc_set_qrcode_name'];
        $data['WX_GZH_QRCODE'] = $_info['pc_set_qrcode'];
        //增加返回溪谷客服系统开启状态和链接-渠道不涉及-20210707-byh
        $data['APP_XGKF_SWITCH'] = get_xgkf_info(0);//溪谷客服系统0=未开启,1=开启
        if(get_xgkf_info(0)){//已开启
            $data['APP_XGKF_URL'] = get_xgkf_info(0,$request['game_id'],$request['user_id']);//获取对应游戏的溪谷客服URL
        }else{
            $data['APP_XGKF_URL'] = '';
        }

        if ($request['promote_id'] > 0) {
            $model = new PromoteunionModel;
            $map['union_id'] = $request['promote_id'];
            $resule = $model->field('union_set')->where($map)->find();
            $resule = empty($resule) ? [] : $resule->toarray();
            if($resule && $resule['union_set']){
                $promote_config  = json_decode($resule['union_set'], true);
                $data['APP_QQ'] = $promote_config['qq'] ? :$config['pc_set_server_qq'];
                $data['QQ_GROUP_KEY'] = $promote_config['qq_group'] ?: $config['pc_qq_group'];
                $data['APP_TEL'] = $promote_config['tel'] ? :$config['pc_set_server_tel'];
                $data['APP_EMAIL'] = $promote_config['mailbox'] ? :$config['pc_t_email'];
                $data['WX_GZH_NAME'] = $promote_config['app_weixin'] ? :$_info['pc_set_qrcode_name'];
                $data['WX_GZH_QRCODE'] = $promote_config['qrcode'] ? :$_info['pc_set_qrcode'];
                //如果渠道有QQ的配置,则使用渠道的QQ,溪谷客服系统返回关闭-20210707-byh
                if($promote_config['qq']){
                    $data['APP_XGKF_SWITCH'] = '0';
                    $data['APP_XGKF_URL'] = '';
                }
            }
        }
        //微信公众号二维码拼接URL
        $data['WX_GZH_QRCODE'] = $data['WX_GZH_QRCODE']?request()->domain().'/upload/'.$data['WX_GZH_QRCODE']:'';
        $this->set_message(200, "获取成功",$data);
    }

    /**
     * [获取分享信息]
     * @author 郭家屯[gjt]
     */
    public function get_sdk_share_url()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        //记录分享游戏数据
        $map['game_id'] = $request['game_id'];
        $map['user_id'] = $request['user_id'];
        $map['create_time'] = total(1, 1);
        $result = Db::table('tab_game_share_record')->where($map)->find();
        if (empty($result)) {
            $save['game_id'] = $request['game_id'];
            $save['user_id'] = $request['user_id'];
            $save['create_time'] = time();
            Db::table('tab_game_share_record')->insert($save);
        }
        //分享信息
        $game_id = $request['game_id'];
        $qqappid_set = get_game_login_param($request['game_id'], 1);
        $data['qqappid'] = $qqappid_set['openid'];
        $wxappid_set = get_game_login_param($request['game_id'], 2);
        $data['weixinappid'] = $wxappid_set['wx_appid'];
        $game = get_game_entity($game_id,'icon,game_name,features');
        $data['logo'] = cmf_get_image_url($game['icon']);
        $data['url'] = cmf_get_domain() . '/mobile/game/detail/game_id/' . $game_id;
        $data['title'] = $game['game_name'];
        $data['content'] = $game['features'];
        $this->set_message(200, "获取成功",$data);
    }

   //返利折扣
    public function get_game_welfare()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $logic = new WelfareLogic();
        $list = $logic->get_game_welfare($request['user_id'],$request['game_id']);
        $this->assign('list',$list);
        $v=\think\View::instance();
        $html = $v->fetch();
        $this->set_message(200, "获取成功",['html'=>$html]);
    }

    /**
     * @函数或方法说明
     * @获取代金券
     * @author: 郭家屯
     * @since: 2020/2/6 9:55
     */
    public function get_coupon()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $user = get_user_entity($request['user_id'],false,'promote_id,parent_id');
        $promote_id = $user['promote_id'];
        $game = get_game_entity($request['game_id'],'id,relation_game_id');
        $game_id = $game['relation_game_id'];
        if($request['type'] == 1){
            //获取可领取代金券
            $receive_coupon =  $this->get_reciver_coupon($request['user_id'],$promote_id,$game_id);
            foreach ($receive_coupon as $key=>$v){
                $receive_coupon[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('y.m.d',$v['start_time']);
                $receive_coupon[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('y.m.d',$v['end_time']);
            }
            $data = array_values($receive_coupon);
        }elseif($request['type'] == 2){
            $my_coupon = $this->get_my_coupon($request['user_id'],$game_id,1);
            foreach ($my_coupon as $key=>$v){
                $my_coupon[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('y.m.d',$v['start_time']);
                $my_coupon[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('y.m.d',$v['end_time']);
                if($v['status'] == 0 && $v['end_time'] >0 && $v['end_time'] <time()){
                    $my_coupon[$key]['status'] = 2;
                }
            }
            $data = array_values($my_coupon);
        }else{
            $my_coupon = $this->get_my_coupon($request['user_id'],$game_id,2);
            foreach ($my_coupon as $key=>$v){
                $my_coupon[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('y.m.d',$v['start_time']);
                $my_coupon[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('y.m.d',$v['end_time']);
                if($v['status'] == 0 && $v['end_time'] >0 && $v['end_time'] <time()){
                    $my_coupon[$key]['status'] = 2;
                }
            }
            $data = array_values($my_coupon);
        }

        $this->set_message(200, "获取成功",$data);
    }

    /**
     * @函数或方法说明
     * @可领取列表
     * @author: 郭家屯
     * @since: 2020/2/5 16:14
     */
    protected function get_reciver_coupon($user_id=0,$promote_id=0,$game_id=0)
    {
        $paylogic = new PayLogic();
        $coupon = $paylogic->get_coupon_lists($user_id,$promote_id,$game_id);
        return $coupon;
    }
    /**
     * @函数或方法说明
     * @我的优惠券
     * @param int $type
     *
     * @author: 郭家屯
     * @since: 2020/2/5 16:15
     */
    protected function get_my_coupon($user_id=0,$game_id=0,$type=3)
    {
        $model = new CouponRecordModel();
        $coupon = $model->get_my_coupon($user_id,$type,$game_id);
        return $coupon;
    }

    /**
     * @函数或方法说明
     * @领取代金券
     * @author: 郭家屯
     * @since: 2020/2/6 11:15
     */
    public function reciver_coupon()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $logic = new GameLogic();
        $result = $logic->getCoupon($request['user_id'],$request['coupon_id']);
        if($result){
            $this->set_message(200, "领取成功");
        }else{
            $this->set_message(1082, "领取失败");
        }
    }

    /**
     * @函数或方法说明
     * @获取可使用优惠券
     * @author: 郭家屯
     * @since: 2020/2/6 11:40
     */
    public function get_valid_coupon()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $game_id = get_game_entity($request['game_id'],'relation_game_id')['relation_game_id'];
        $model = new CouponRecordModel();
        $coupon = $model->get_my_coupon($request['user_id'],1,$game_id,$request['pay_amount']);
        foreach ($coupon as $key=>$v){
            $coupon[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('y.m.d',$v['start_time']);
            $coupon[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('y.m.dy.m.d',$v['end_time']);
        }
        $this->set_message(200, "获取成功",$coupon);
    }

    public function sign_detail(PointLogic $logicPoint)
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        if(!$request['user_id']){
            $this->set_message(1003, "账号信息丢失");
        }
        $signDetail = $logicPoint->sign_detail($request['user_id']);
        if($signDetail==-1){
            $this->set_message(1083, "签到未开启");
        }
        $this->set_message(200, "允许签到",$signDetail);
    }

    public function sign_in(PointLogic $logicPoint)
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        if(!$request['user_id']){
            $this->set_message(1003, "账号信息丢失");
        }
        $res = $logicPoint->sign_in($request['user_id']);
        if($res==-1){
            $this->set_message(1083, "签到未开启");
        }elseif($res==-2){
            $this->set_message(1084, "今日已签到，请不要重复签到");
        }elseif($res==-3){
            $this->set_message(1084, "请先进行实名认证");
        }else{
            $this->set_message(200, "恭喜您轻松地获得了".$res."积分奖励");
        }
    }

    /**
     * @函数或方法说明
     * @拆红包
     * @author: 郭家屯
     * @since: 2020/9/30 14:43
     */
    public function withdraw()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        if(empty($request['user_id'])){
            $this->set_message(1003, "账号信息丢失");
        }
        session('member_auth.user_id',$request['user_id']);
        $url = url('@recharge/tplay/task',['game_id'=>$request['game_id']],true,true);
        $this->set_message(200, "获取成功",$url);
    }
}
