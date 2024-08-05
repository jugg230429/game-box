<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-09
 */

namespace app\sdkh5\controller;

use app\common\logic\AntiaddictionLogic;
use app\common\logic\GameLogic as CouponLogic;
use app\common\logic\PayLogic;
use app\extend\model\MsgModel;
use app\game\logic\InterflowLogic;
use app\game\model\GameModel;
use app\game\model\GiftbagModel;
use app\member\model\UserConfigModel;
use app\member\model\UserModel;
use app\member\model\UserPlayModel;
use app\member\model\UserTplayModel;
use app\recharge\model\CouponRecordModel;
use app\sdkh5\BaseController;
use app\sdkh5\logic\GameLogic;
use app\sdkh5\validate\GameValidate;
use app\site\model\PortalPostModel;
use app\site\service\PostService;
use think\Checkidcard;
use think\Db;
use think\xigusdk\Xigu;

class GameController extends BaseController
{
    public function get_play_info($user_id=0,$game_id=0,$pid=0)
    {
        $postData['user_id'] = $user_id;
        $postData['game_id'] = $game_id;
        $postData['pid'] = $pid;
        $validate = new GameValidate();
        $result = $validate->scene('login')->check($postData);
        if(!$result){
            return $this->failResult($validate->getError());
        }
        //创建游戏记录
        $this->add_user_play($user_id,$game_id);
        $logicGame = new GameLogic();
        $data['game_url'] = UID?$logicGame->get_login_url($postData):'';
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取用户信息
     * @author: 郭家屯
     * @since: 2020/6/15 14:27
     */
    public function get_user_info()
    {
        $user_id = session('member_auth.user_id');
        if(!$user_id){
            return json(['user_id'=>0]);
        }
        $game_id = $this->request->param('game_id');
        $bind_balance = Db::table('tab_user_play')->where('user_id',$user_id)->where('game_id',$game_id)->field('bind_balance')->find();
        $user = get_user_entity($user_id,false,'id as user_id,vip_level,head_img,balance,point,account,age_status,phone,real_name,idcard');
        $user['head_img'] = cmf_get_image_url($user['head_img']);
        $user['bind_balance'] = $bind_balance['bind_balance']?:0.00;
        $user['idcard'] = empty($user['idcard'])?'':substr($user['idcard'], 0, 3).'*****'.substr($user['idcard'], -2);//身份证处理
        $user['phone'] = empty($user['phone'])?'':substr($user['phone'], 0, 4).'****'.substr($user['phone'], -3);//手机号处理
        //获取试玩任务
        $model = new UserTplayModel();
        $map['status'] = 1;
        $map['start_time'] = ['lt',time()];
        $map['game_id'] = $game_id;
        $task = $model->field('id')->where($map)->find();
        $user['task_id'] = $task['id'];
        // 增加猜你喜欢开关
        $sdk_set = cmf_get_option('wap_set');
        $guess_you_like_switch = $sdk_set['guess_you_like_switch'] ?? 1; // 0 关闭 1 开启
        $user['guess_you_like_switch'] = $guess_you_like_switch;

        return json($user);
    }

    /**
     * @函数或方法说明
     * @实名认证
     * @author: 郭家屯
     * @since: 2020/6/17 11:13
     */
    public function user_auth()
    {
        $user_id = session('member_auth.user_id');
        if(!$user_id){
            return json(['user_id'=>0]);
        }
        $data['real_name'] = $this->request->param('real_name');
        $data['idcard'] = $this->request->param('idcard');
        $data['game_id'] = $this->request->param('game_id');
        $userModel = new UserModel();
        $data = $this->check_auth($data);
        $data['user_id'] =$user_id;
        if($data['status'] == 0){
            return json($data);
        }
        Db::startTrans();
        $result = $userModel->field(true)->where('id', $user_id)->update($data);
        $userModel->task_complete($user_id,'auth_idcard',$data['idcard']);//绑定任务
        if (!$result) {
            Db::rollback();
            $data['status'] = 0;
            $data['msg'] = '认证失败';
            return json($data);
        } else {
            // 简化版平台玩家认证
            $promote_id = get_user_entity($user_id,false,'promote_id')['promote_id'];;
            if(is_third_platform($promote_id) && get_third_user_id($user_id)>0){
                $logic = new \app\webplatform\logic\UserLogic($promote_id);
                $save['idcard'] = $data['idcard'];
                $save['real_name'] = $data['real_name'];
                $save['age_status'] = $data['age_status'];
                $res = $logic->update_user_info($user_id,$save);
                if($res === false){
                    Db::rollback();
                    $data['status'] = 0;
                    $data['msg'] = '认证失败';
                    return json($data);
                }else{
                    Db::commit();
                    $data['status'] = 1;
                    $data['msg'] = '认证成功';
                    return json($data);
                }
            }else{
                Db::commit();
                $data['status'] = 1;
                $data['msg'] = '认证成功';
                return json($data);
            }
        }
    }

    /**
     * @函数或方法说明
     * @$logic
     * @param array $data
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/6/17 11:21
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function check_auth($data=[])
    {
        $len = mb_strlen($data['real_name']);
        if ($len < 2 || $len > 25) {
            $data['status'] = 0;
            $data['msg'] = "姓名长度需要在2-25个字符之间";
            return $data;
        }
        if (!preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,25}$/', $data['real_name'])) {
            $data['status'] = 0;
            $data['msg'] = "姓名格式错误";
            return $data;
        }
        $data['idcard'] = strtolower($data['idcard']);
        $checkidcard = new Checkidcard();
        $invidcard = $checkidcard->checkIdentity($data['idcard']);
        if (!$invidcard) {
            $data['status'] = 0;
            $data['msg'] = "证件号码错误";
            return $data;
        }

        $userconfig = new UserConfigModel();
        //实名认证设置
        $userModel = new UserModel();
        $config = $userconfig -> getSet('age');
        if (($config['config']['can_repeat'] != '1')) {
            $cardd = $userModel -> where('idcard', $data['idcard']) -> field('id') -> find();
            if ($cardd) {
                $data['status'] = 0;
                $data['msg'] = "身份证号已被认证";
                return $data;
            }
        }
        //国家实名认证判断  //实名认证方式  1：平台   2：国家系统
        if($data['game_id'] > 0 && get_game_age_type($data['game_id']) == 2){
            $user_id = session('member_auth.user_id');
            if(!$user_id){
                $data['status'] = 0;
                $data['msg'] = "用户不存在";
                return $data;
            }
            $logic = new AntiaddictionLogic($data['game_id']);
            $re = $logic->checkUser($data['real_name'],$data['idcard'],$user_id,$data['game_id']);
            if($re['status'] == 1){//认证成功
                if (is_adult($data['idcard'])) {
                    $data['age_status'] = 2;
                } else {
                    $data['age_status'] = 3;
                }
                $data['status'] = 1;
                $data['msg'] = "实名认证成功";
            }elseif($re['status'] == 2){//认证中
                $data['age_status'] = 4;
                $data['status'] = 0;
                $data['msg'] = "提交成功，请等待认证结果";
                return $data;
            }else{//认证失败
                $data['status'] = 0;
                $data['msg'] = "认证失败，请重新提交认证";
                return $data;
            }
        }else{
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
                        $data['status'] = 0;
                        $data['msg'] = "短信数量已经使用完";
                        return $data;
                        break;
                    case -2:
                        $data['status'] = 0;
                        $data['msg'] = "连接认证接口失败";
                        return $data;
                        break;
                    case 0:
                        $data['status'] = 0;
                        $data['msg'] = "认证信息错误";
                        return $data;
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
        }
        $data['status'] = 1;
        return $data;
    }


    /**
     * @函数或方法说明
     * @退出登录
     * @author: 郭家屯
     * @since: 2020/6/15 14:53
     */
    public function logout()
    {
        session('member_auth', null);
        cookie('last_login_account', null);
        $this->success('退出成功');
    }

    /**
     * @函数或方法说明
     * @获取可领取代金券
     * @author: 郭家屯
     * @since: 2020/6/15 15:17
     */
    public function get_receive_coupon()
    {
        $user_id = session('member_auth.user_id');
        if(!$user_id){
            return json(['user_id'=>0]);
        }
        $data['user_id'] = $user_id;
        $game_id = $this->request->param('game_id');
        $amount = $this->request->param('amount');
        $user = get_user_entity($user_id,false,'promote_id,parent_id');
        $promote_id = $user['promote_id'];
        $paylogic = new PayLogic();
        $receive_coupon = $paylogic->get_coupon_lists($user_id,$promote_id,$game_id,$amount);
        foreach ($receive_coupon as $key=>$v){
            $receive_coupon[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('y.m.d',$v['start_time']);
            $receive_coupon[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('y.m.d',$v['end_time']);
        }
        $data['data'] = array_values($receive_coupon);
        return json($data);
    }

    /**
     * @函数或方法说明
     * @获取未使用列表
     * @author: 郭家屯
     * @since: 2020/6/15 15:25
     */
    public function get_unuse_coupon()
    {
        $user_id = session('member_auth.user_id');
        if(!$user_id){
            return json(['user_id'=>0]);
        }
        $data['user_id'] = $user_id;
        $game_id = $this->request->param('game_id');
        $amount = $this->request->param('amount');
        $model = new CouponRecordModel();
        $my_coupon = $model->get_my_coupon($user_id,1,$game_id,$amount);
        foreach ($my_coupon as $key=>$v){
            $my_coupon[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('y.m.d',$v['start_time']);
            $my_coupon[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('y.m.d',$v['end_time']);
            if($v['status'] == 0 && $v['end_time'] >0 && $v['end_time'] <time()){
                unset($my_coupon[$key]);
            }
        }
        $data['data'] = array_values($my_coupon);
        return json($data);
    }

    /**
     * @函数或方法说明
     * @领取代金券
     * @author: 郭家屯
     * @since: 2020/6/15 16:27
     */
    public function reciver_coupon()
    {
        $user_id = session('member_auth.user_id');
        if(!$user_id){
            return json(['user_id'=>0]);
        }
        $data['user_id'] = $user_id;
        $coupon_id = $this->request->param('coupon_id');
        $logic = new CouponLogic();
        $result = $logic->getCoupon($user_id,$coupon_id);
        if($result){
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        return json($data);
    }

    /**
     * @函数或方法说明
     * @客服信息
     * @author: 郭家屯
     * @since: 2020/6/15 17:07
     */
    public function get_service()
    {
        $weiduan_promote_id = session('device_promote_id');//微端推广信息
        $promote_id = $weiduan_promote_id ? : $this->request->param('promote_id');
        $kefu = cmf_get_option('kefu_set');
        $media = cmf_get_option('media_set');
        if($promote_id > 0){
            $union_model = new \app\promote\model\PromoteunionModel();
            $host = $union_model->where('union_id',$promote_id)->where('status',1)->find();
            $host = $host?$host->toArray():[];
        }elseif(session("union_host")){
            $host = session("union_host");
        }
        $data['qq'] = $kefu['pc_set_server_qq']?:'';
        if(cmf_is_mobile()){
            $data['qq_url'] = "mqqwpa://im/chat?chat_type=wpa&amp;uin={$data['qq']}&amp;version=1&amp;src_type=web&amp;web_src=oicqzone.com";
        }else{
            $data['qq_url'] = "http://wpa.qq.com/msgrd?v=3&amp;uin={$data['qq']}&amp;site=qq&amp;menu=yes";
        }
        //增加溪谷客服的信息判断返回
        if(get_xgkf_info(0)){
            $data['qq'] = 'service';
            $data['qq_url'] = get_xgkf_info(1);
        }
        $data['tel'] = $kefu['pc_set_server_tel']?:'';
        $data['t_email'] = $kefu['pc_t_email']?:'';
        $data['qrcode_name'] = $media['pc_set_qrcode_name']?:'';
        $data['qrcode'] = $media['pc_set_qrcode']?cmf_get_image_url($media['pc_set_qrcode']):cmf_get_domain().'/static/images/empty.jpg';
        $data['qq_group'] = $kefu['pc_qq_group']?:'';
        $data['pc_qq_group_key'] = $kefu['pc_qq_group_key']?:'';
        $data['qq_group_url'] = $kefu['pc_qq_group_url'] ? :"";
        $data['xfq'] = '';
        $url = url('media/game/open_game', ['game_id' => input('game_id'), 'promote_id' => input('promote_id')], '', true);
        $data['game_qrcode'] = url('sdkh5/game/qrcode', ['url' => base64_encode(base64_encode($url))], '', true);
        //推广员信息
        if($host){
            $union_set = json_decode($host['union_set'], true);
            $data['xfq'] = $union_set['xfq'] ? cmf_get_image_url($union_set['xfq']):'';
            $data['qq'] = $union_set['qq'] ? :$data['qq'];
            if(cmf_is_mobile()){
                $data['qq_url'] = "mqqwpa://im/chat?chat_type=wpa&amp;uin={$data['qq']}&amp;version=1&amp;src_type=web&amp;web_src=oicqzone.com";
            }else{
                $data['qq_url'] = "http://wpa.qq.com/msgrd?v=3&amp;uin={$data['qq']}&amp;site=qq&amp;menu=yes";
            }
            $data['qq_group'] = $union_set['qq_group']?:$data['qq_group'];
            $data['pc_qq_group_key'] = $union_set['qq_group_key']?:$data['pc_qq_group_key'];
            $data['qq_group_url'] = $union_set['qq_group_url']?:$data['qq_group_url'];
            $data['tel'] = $union_set['tel']?:$data['tel'];
            $data['t_email'] = $union_set['mailbox']?:$data['t_email'];
            $data['qrcode_name'] = $union_set['app_weixin']?:$data['qrcode_name'];
            $data['qrcode'] = $union_set['qrcode']?cmf_get_image_url($union_set['qrcode']):$data['qrcode'];
        }
       return json($data);
    }

    /**
     * @函数或方法说明
     * @获取文章详情
     * @author: 郭家屯
     * @since: 2020/6/16 10:32
     */
    public function get_article()
    {
        $game_id = $this->request->param('game_id');
        $postService = new PostService();
        //公告
        $map['category'] = 4;
        $map['post_status'] = 1;
        $map['game_id'] = get_promote_relation_game_id($game_id);
        $gonggao = $postService->GameArticleList($map,false,50);
        foreach ($gonggao as $k=>$v){
            $gonggao[$k]['update_time'] = date('m/d',$v['update_time']);
        }
        $data['gonggao'] = $gonggao;
        //活动
        $map['category'] = 3;
        $huodong = $postService->GameArticleList($map,false,50);
        foreach ($huodong as $k=>$v){
            $huodong[$k]['update_time'] = date('m/d',$v['update_time']);
        }
        $data['huodong'] = $huodong;
        //攻略
        $map['category'] = 5;
        $gonglue = $postService->GameArticleList($map,false,50);
        foreach ($gonglue as $k=>$v){
            $gonglue[$k]['update_time'] = date('m/d',$v['update_time']);
        }
        $data['gonglue'] = $gonglue;
        return json($data);
    }

    /**
     * @函数或方法说明
     * @文章想我那个
     * @author: 郭家屯
     * @since: 2020/6/16 10:51
     */
    public function get_article_detail()
    {
        $id = $this->request->param('id/d', 0);
        $portalPostModel = new PortalPostModel();
        $data = $portalPostModel->field('id,post_title,post_content,update_time')->where('id', $id)->find();
        if ($data) {
            $portalPostModel->where('id', $id)->setInc('post_hits');
        }
        $data['update_time'] =  date('m/d',$data['update_time']);
        return json($data);
    }

    /**
     * [礼包列表]
     * @author 郭家屯[gjt]
     */
    public function get_gift()
    {
        $user_id = session('member_auth.user_id');
        if(!$user_id){
            return json(['user_id'=>0]);
        }
        $game_id = $this->request->param('game_id');
        $model = new GiftbagModel();
        $map['and_id|ios_id|h5_id'] = $game_id;
        $list = $model->getGiftIndexLists($user_id, $map, 50);
        foreach ($list as $k => $v) {
            if ($v['surplus'] == 0 && $v['received'] == 0) {
                unset($list[$k]);
            }
        }
        $data['user_id'] = $user_id;
        $data['gift'] = array_values($list);
        return json($data);
    }

    /**
     * @函数或方法说明
     * @礼包详情
     * @author: 郭家屯
     * @since: 2020/6/16 13:52
     */
    public function get_gift_detail()
    {
        $user_id = session('member_auth.user_id');
        if(!$user_id){
            return json(['user_id'=>0]);
        }
        $data['user_id'] = $user_id;
        $gift_id = $this->request->param('gift_id');
        $model = new GiftbagModel();
        $gift = $model->getDetail($gift_id, $user_id);
        $gift['desribe'] = $gift['desribe'] == '' ? '' : explode(PHP_EOL, trim($gift['desribe']));
        $gift['notice'] = $gift['notice'] == '' ? '' : explode(PHP_EOL, trim($gift['notice']));
        $gift['digest'] = $gift['digest'] == '' ? '' : explode(PHP_EOL, trim($gift['digest']));
        $gift['end_time'] = $gift['end_time'] ? date('Y/m/d',$gift['end_time'])."过期" : '永久有效';
        $data['gift'] = $gift;
        return json($data);
    }

    /**
     * [领取礼包]
     * @return \think\response\Json
     * @author chen
     */
    public function getgift()
    {
        $user_id = session('member_auth.user_id');
        if(!$user_id){
            return json(['user_id'=>0]);
        }
        $gift_id = $this->request->param('gift_id/d');
        if (empty($gift_id)) {
            return json(['user_id'=>$user_id,'status'=>0,'msg'=>'参数错误']);
        }
        $giftbgmodel = new GiftbagModel();
        $result = $giftbgmodel->receiveGift($gift_id, session('member_auth.user_id'));
        switch ($result) {
            case -1:
                return json(['user_id'=>$user_id,'status'=>0,'msg'=>'礼包不存在或已过期']);
                break;
            case -2:
                return json(['user_id'=>$user_id,'status'=>0,'msg'=>'礼包已领取完']);
                break;
            case -3:
                return json(['user_id'=>$user_id,'status'=>0,'msg'=>'您已领取过该礼包']);
                break;
            case -4:
                return json(['user_id'=>$user_id,'status'=>0,'msg'=>'领取礼包失败']);
                break;
        }
        return json(['user_id'=>$user_id,'status'=>1,'msg'=>'领取成功','novice' => $result]);
    }

    /**
     * @函数或方法说明
     * @创建游戏账号信息
     * @param int $user_id
     * @param int $game_id
     *
     * @author: 郭家屯
     * @since: 2020/6/16 20:08
     */
    public function add_user_play($user_id=0,$game_id=0)
    {
        $model = new UserPlayModel();
        //添加数据
        $user = get_user_entity($user_id,false,'id,account,promote_id,promote_account,puid');
        $game = get_game_entity($game_id,'id,game_name,game_appid,sdk_version');
        $map['user_id'] = $user_id;
        $map['game_id'] = $game_id;
        $result = $model->login($map,$user,$game);
        //增加在玩人数
        if($result == 1){
            Db::table('tab_game')->where('id',$game_id)->setInc('dow_num');
        }
    }


    /**
     * @猜你喜欢游戏列表
     *
     * @author: zsl
     * @since: 2021/5/27 10:01
     */
    public function likegame()
    {
        $param = $this -> request -> param();
        $model = new GameModel();
        if (MOBILE_PID > 0) {
            $game_ids = get_promote_game_id(MOBILE_PID);
            $game_ids[] = $param['game_id'];
            $map['id'] = ['in', $game_ids];
        } else {
            $map['id'] = ['neq', $param['game_id']];
        }
        //获取游戏分类id
        $game_type_id = $model -> where(['id' => $param['game_id']]) -> value('game_type_id');
        $map['game_type_id'] = $game_type_id;

        if(MODEL == 1){
            $map['sdk_version'] = get_devices_type();
        }else{
            $map['sdk_version'] = 3;
        }

        $map['game_status']= 1;
        $map['test_game_status'] = 0;
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $gameLists = $model -> where($map) -> field('id,game_name,game_type_id,game_type_name,icon') -> order('sort desc,id desc') -> select();
        if (!empty($gameLists)) {
            foreach ($gameLists as &$v) {
                $v['icon'] = cmf_get_image_url($v['icon']);
                $v['url'] = url('media/game/open_game', ['game_id' => $v['id']]);
            }
        }
        $this -> success('获取成功', '', $gameLists);
    }


    /**
     * [生成二维码]
     * @param string $url
     * @param int $level
     * @param int $size
     * @author 郭家屯[gjt]
     */
    public function qrcode($url = 'pc.vlcms.com', $level = 3, $size = 4)
    {
        $url = base64_decode(base64_decode($url));
        Vendor('phpqrcode.phpqrcode');
        $errorCorrectionLevel = intval($level);//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        //生成二维码图片
        //echo $_SERVER['REQUEST_URI'];
        ob_clean();
        $object = new \QRcode();
        echo $object->png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }


    /**
     * @获取小号列表
     *
     * @author: zsl
     * @since: 2021/8/21 11:42
     */
    public function smallLists()
    {
        if (empty(UID)) {
            $this -> error('登录信息已过期');
        }
        $param = $this -> request -> param();
        //判断是否为互通游戏
        $lInterflow = new InterflowLogic();
        $game_ids = $lInterflow -> getInterflowGameIds(['game_id' => $param['game_id']]);
        $mUser = new UserModel();
        $where = [];
        $where['puid'] = UID;
        $where['lock_status'] = 1;
        if (empty($game_ids)) {
            $where['fgame_id'] = $param['game_id'];
        } else {
            $where['fgame_id'] = ['in', $game_ids];
        }
        $whereOr = [];
        $whereOr['id'] = UID;
        $lists = $mUser -> field('id,account,nickname') -> where($where) -> whereOr($whereOr) ->order('id desc')->
        select();
        $new_list = [];
        if (!empty($lists)) {
            $lists = $lists->toArray();
            foreach ($lists as $k=>&$v) {
                if(!empty($param['user_token'])){
                    $v['url'] = url('mobile/game/open_game', ['game_id' => $param['game_id'],'pid'=>$param['pid'], 'small_id' => $v['id'],'user_token'=>$param['user_token']]);
                }else{
                    $v['url'] = url('mobile/game/open_game', ['game_id' => $param['game_id'],'pid'=>$param['pid'], 'small_id' => $v['id']]);
                }
                if($v['id']==UID){
                    $v['account_tag'] = '<span style="color:#666666">[不可交易]</span>';
                    $new_list[] = $v;
                    unset($lists[$k]);
                }else{
                    $v['account_tag'] = '';
                }
            }
        }
        $this -> success('获取成功', '', array_merge($new_list,$lists));
    }

    /**
     * @添加小号
     *
     * @author: zsl
     * @since: 2021/8/23 15:23
     */
    public function addSmall()
    {
        if (empty(UID)) {
            $this -> error('登录信息已过期');
        }
        $param = $this -> request -> param();
        if (empty($param['small_account'])) {
            $this -> error('小号名不能为空');
        }
        //获取当前账号已经有小号数量
        $mUser = new UserModel();
        $where = [];
        $where['puid'] = UID;
        $where['lock_status'] = 1;
        $where['fgame_id'] = $param['game_id'];
        $smallCount = $mUser -> where($where) -> count();
        if ($smallCount >= 20) {
            $this -> error('小号个数已满20，不可继续添加');
        }
        //新增小号
        $userInfo = $mUser -> field('id,account,promote_id,promote_account,parent_id,parent_name') -> where(['id' => UID]) -> find();
        $smallAccount = $userInfo['account'] . '_xh_' . mt_rand(100000,999999);
        $data = [];
        $data['account'] = $smallAccount;
        $data['promote_id'] = $userInfo['promote_id'];
        $data['promote_account'] = $userInfo['promote_account'];
        $data['parent_id'] = $userInfo['parent_id'];
        $data['parent_name'] = $userInfo['parent_name'];
        $data['fgame_id'] = $param['game_id'];
        $data['fgame_name'] = get_game_name($param['game_id']);
        $data['nickname'] = trim($param['small_account']);
        $data['lock_status'] = 1;
        $data['register_time'] = time();
        $data['register_ip'] = get_client_ip();
        $data['puid'] = $userInfo['id'];
        $result = $mUser -> isUpdate(false) -> save($data);
        if (false === $result) {
            $this -> error('添加失败');
        } else {
            $this -> success('添加成功');
        }
    }

    /**
     * 悬浮球-设置-修改密码
     * by:byh 2021-9-13 15:50:38
     */
    public function modifyPassword()
    {
        $user = get_user_entity(UID,false,'id,account,nickname,head_img,register_type,password,vip_level');
        if (!in_array($user['register_type'], [0, 1, 2, 7])) {
            $this->error('您的账号不支持修改密码');
        }
        $data = $this->request->param();
        if (empty($data['pas1'])) $this->error('请输入原密码');
        if (empty($data['pas2'])) $this->error('请输入新密码');
        if (check_password($data['pas1']) == 1012 || check_password($data['pas2']) == 1012) {
            $this->error('请输入6~15位字符的密码');
        }
        if ($data['pas2'] != $data['pas3']) {
            $this->error('两次输入的密码不一致');
        }
        if (!xigu_compare_password($data['pas1'], $user['password'])) {
            $this->error('原密码错误');
        }
        if ($data['pas1'] == $data['pas2']) {
            $this->error('新密码与原始密码不能相同');
        }
        $save['password'] = cmf_password($data['pas2']);
        $model = new UserModel();
        $result = $model->update_user_info(UID, $save);
        if ($result) {
            session('member_auth.password', $save['password']);
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

    /**
     * 玩家设置-发送短信-手机绑定/解绑
     * by:byh 2021-9-14 10:03:39
     */
    public function sendSms()
    {
        $phone = $this->request->param('phone','');
        $type = $this->request->param('type',0);

        $user_id = session('member_auth.user_id');

        if(!$user_id){
            return json(['code'=>0,'msg'=>'请重新登录']);
        }
        if (!cmf_check_mobile($phone) && $type == 1) {
            $this->error( "手机号码格式错误");
        }
        $userModel = new UserModel();
        if ($type == 1) {/* 绑定手机 */
            $user = $userModel->field('id')->where('phone', $phone)->find();
            if ($user) {
                $this->error( "该手机号已被绑定过");
            }
        }elseif ($type == 2) {/* 解绑绑定 */
            //查询之前绑定的手机号
            $phone = get_user_entity($user_id,false,'phone')['phone']??'';
            if (empty($phone)) {
                $this->error( "未绑定手机");
            }
        } else {
            $this->error( "未知发送类型");
        }
        $msg = new MsgModel();
        $data = $msg::get(1);
        if (empty($data)) {
            $this->error( "没有配置短信发送");
        }
        $data = $data->toArray();
        if ($data['status'] == 1) {
            $xigu = new Xigu($data);
            sdkchecksendcode($phone, $data['client_send_max'], 2);
            $sms_code = session($phone);//发送没有session 每次都是新的
            $sms_rand = sms_rand($phone);
            $rand = $sms_rand['rand'];
            $new_rand = $sms_rand['rand'];
            /// 产生手机安全码并发送到手机且存到session
            $param = $rand . "," . '3';
            $result = json_decode($xigu->sendSM( $phone, $data['captcha_tid'], $param), true);
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
                $this->success( "验证码发送成功");
            } else {
                $this->error("验证码发送失败，请重新获取");
            }
        } else {
            $this->error( "没有配置短信发送");
        }
    }

    /**
     * 玩家设置-绑定/解绑手机号
     * by:byh 2021-9-14 10:45:44
     */
    public function modifyPhone()
    {
        $user_id = session('member_auth.user_id');

        if(!$user_id){
            return json(['code'=>0,'msg'=>'请重新登录']);
        }
        $type = $this->request->param('type',0);//1=绑定手机号 2=解绑手机号
        $phone = $this->request->param('phone','');
        $code = $this->request->param('yzm','');
        if(empty($code)){
            $this->error('请输入验证码');
        }
        $userModel = new UserModel();
        if($type ==1){//绑定手机
            if (!cmf_check_mobile($phone) && $type == 1) {
                $this->error( "手机号码格式错误");
            }
            //先查询手机号
            $user = $userModel->field('id')->where('phone', $phone)->find();
            if ($user) {
                $this->error( "该手机号已被绑定过");
            }
            //验证验证码
            $this->sms_verify($phone, $code);
            //绑定
            $save['phone'] = $phone;
            $res = $userModel->where('id',$user_id)->update($save);
            if($res){
                $this->success('绑定成功');
            }
            $this->error('绑定失败!请稍后再试');

        }elseif($type ==2){//解绑手机
            //查询用户的手机信息是否存在
            $phone = get_user_entity($user_id,false,'phone')['phone']??'';
            if(empty($phone)){
                $this->error('手机号不存在');
            }
            //验证码验证
            $this->sms_verify($phone, $code);
            //解除绑定-删除手机号
            $res = $userModel->where('id',$user_id)->update(['phone'=>'']);
            if($res){
                $this->success('解绑成功');
            }
            $this->error('解绑失败!请稍后再试');

        }

    }

    /**
     *  验证码短信验证
     *  by:byh 2021-9-14 11:17:13
     */
    private function sms_verify($phone = "", $code = "", $type = 2)
    {
        $session = session($phone);
        if (empty($session)) {
            $this->error("请先获取验证码");
        }
        #验证码是否超时
        $time = time() - session($phone . ".create");

        if ($time > 180) {//$tiem > 60
            $this->error("验证码已失效，请重新获取");
        }
        #验证短信验证码
        if (session($phone . ".code") != $code) {
            $this->error("验证码不正确，请重新输入");
        }
        if ($type == 1) {
            $this->success("正确");
        } else {
            return true;
        }

    }



}
