<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link http://www.apache.org/licenses/LICENSE-2.0
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-09
 */

namespace app\sdkyy\controller;

use app\game\model\GameModel;
use app\game\model\ServerModel;
use app\member\model\UserConfigModel;
use app\member\model\UserModel;
use app\member\model\UserPlayModel;
use app\member\model\UserPlayRecordModel;
use app\sdkyy\BaseController;
use app\sdkyy\logic\GameLogic;
use app\common\logic\GameLogic as CouponLogic;
use app\common\logic\PayLogic;
use app\recharge\model\CouponRecordModel;
use app\site\service\PostService;
use app\site\model\PortalPostModel;
use app\game\model\GiftbagModel;
use think\Checkidcard;
use think\Db;
use app\common\logic\AntiaddictionLogic;

class GameController extends BaseController
{
    public function get_login_url($user_id=0,$game_id=0,$cp_game_id=0,$interface_id =0,$server_id=0,$pay_url='',$url='')
    {
        //创建游戏记录
        $this->add_user_play($user_id,$game_id,$server_id);
        $interface = Db::table('tab_game_interface')->field('tag')->where('id',$interface_id)->find();
        if(empty($interface)){
            return '';
        }
        $server = Db::table('tab_game_server')->field('server_num')->where('id',$server_id)->find();
        if(empty($server)){
            return '';
        }
        $controller_name = "\app\sdkyy\api\\".$interface['tag'];
        $gamecontroller = new $controller_name;
        $login_url = $gamecontroller->play($cp_game_id,$server['server_num'],$user_id,$pay_url,$url='');
        return $login_url;
    }

    public function get_role_info()
    {
        $user_id = session('member_auth.user_id');
        if(!$user_id){
            return json(['user_id'=>0]);
        }
        $game_id = $this->request->param('game_id');
        $game = get_game_entity($game_id,'id,game_name,interface_id,cp_game_id');
        $interface = Db::table('tab_game_interface')->field('tag')->where('id',$game['interface_id'])->find();
        if(empty($interface)){
            return json(['game_id'=>0]);
        }
        $user = get_user_entity($user_id,false,'id,account,nickname,promote_id,promote_account');
        $area_id = $this->request->param('area_id');
        $server = Db::table('tab_game_server')->field('server_num,server_name')->where('id',$area_id)->find();
        if(empty($server)){
            return json(['area_id'=>0]);
        }
        $controller_name = "\app\sdkyy\api\\".$interface['tag'];
        $gamecontroller = new $controller_name;
        $role_info = $gamecontroller->get_role($game['cp_game_id'],$server['server_num'],$user_id,$server['server_name']);
        $logic = new GameLogic();
        foreach ($role_info as $key=>$v){
            $logic->save_role($game,$v,$user);
        }
        return json(['status'=>1]);
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
        $user = get_user_entity($user_id,false,'id as user_id,vip_level,head_img,balance,point,account,age_status,cumulative');
        $user['head_img'] = cmf_get_image_url($user['head_img']);
        $user['bind_balance'] = $bind_balance['bind_balance']?:0.00;
        //获取下一等级所需
        $level = get_vip_level();
        foreach ($level as $v) {
            if($user['cumulative']<$v){
                $user['next_level_all_num'] = $v;
                break;
            }
        }
        //获取距离下一级还差多少
        $user['next_level_num'] = get_next_vip_level($user['cumulative']);


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
            }
            Db::commit();
            $data['status'] = 1;
            $data['msg'] = '认证成功';
            return json($data);
        }
    }

    /**
     * @函数或方法说明
     * @实名认证接口
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
        $config = $userconfig->getSet('age');
        $userModel = new UserModel();
        if(($config['config']['can_repeat']!='1')){
            $cardd = $userModel->where('idcard', $data['idcard'])->field('id')->find();
            if ($cardd) {
                $data['status'] = 0;
                $data['msg'] = "身份证号已被认证";
                return $data;
            }
        }
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
                $data['status'] = 1;
                $data['msg'] = "提交成功，请等待认证结果";
                return $data;
            }else{//认证失败
                $data['status'] = 0;
                $data['msg'] = "认证失败，请重新提交认证";
                return $data;
            }
        }else {
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
        $map['pc_id'] = $game_id;
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
    protected function add_user_play($user_id=0,$game_id=0,$server_id=0)
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
        //写入在玩记录
        $record['user_id'] = $user['id'];
        $record['game_id'] = $game_id;
        $record['server_id'] = $server_id;
        $recordmodel = new UserPlayRecordModel();
        $recordmodel->login($record);
    }

    /**
     * @获取区服记录
     *
     * @author: zsl
     * @since: 2020/10/10 10:20
     */
    public function get_server_record()
    {
        $result = ['status' => 1, 'msg' => '获取成功', 'data' => []];
        $game_id = $this -> request -> post('game_id');
        $model = new UserPlayRecordModel();
        $user_id = session('member_auth.user_id');
        if (empty($user_id)) {
            $result['status'] = 0;
            $result['msg'] = '请登录账号';
            return json($result);
        }
        $where = [];
        $where['user_id'] = $user_id;
        $where['game_id'] = $game_id;
        $where['server_id'] = ['neq', 0];
        $lists = $model -> field('id,server_id') -> where($where) -> order('create_time desc') -> limit('3') -> select();
        if (!empty($lists)) {
            foreach ($lists as &$v) {
                $v['server_name'] = get_server_name($v['server_id']);
            }
            $result['data'] = $lists;
        }
        return json($result);
    }


    /**
     * @获取区服列表
     *
     * @author: zsl
     * @since: 2020/10/10 10:49
     */
    public function get_server_lists()
    {
        $result = ['status' => 1, 'msg' => '获取成功', 'data' => []];
        $game_id = $this -> request -> post('game_id');
        $model = new ServerModel();
        $field = 'id,game_id,server_name,start_time';
        $where = [];
        $where['game_id'] = $game_id;
        $where['status'] = 1;
        $where['sdk_version'] = 4;
        $lists = $model -> field($field) -> where($where) -> order('start_time desc,create_time desc') -> select();
        if (!empty($lists)) {
            foreach ($lists as $v) {
                $v['is_start'] = $v['start_time'] <= time() ? '1' : '0';
                $v['start_status'] = $v['start_time'] <= time() ? '火爆开启' : '即将开始';
                if($v['is_start']=='1'){
                    $v['play_url'] = url('media/game/open_ygame',['game_id'=>$game_id,'server_id'=>$v['id']]);
                }else{
                    $v['play_url'] = '#';
                }
            }
            $result['data'] = $lists;
        }
        return $result;
    }


    /**
     * @推荐游戏
     *
     * @author: zsl
     * @since: 2020/10/10 11:09
     */
    public function recommend_game()
    {
        $result = ['status' => 1, 'msg' => '获取成功', 'data' => []];
        //获取玩过的游戏
        $game_id = $this -> request -> post('game_id');
        $mGame = new GameModel();
        $gameLists = $mGame
                ->alias('g')
                ->field('g.id,g.game_name,sdk_version,game_status,recommend_status,sort,r.server_id')
                ->join(['tab_user_play_record'=>'r'],'r.game_id=g.id','left')
                ->group('g.id')
                //->where('g.id','neq',$game_id)
                ->where('g.sdk_version',4)
                ->where('g.game_status',1)
                ->where('r.user_id',session('member_auth.user_id'))
                ->order(max('r.create_time desc'))
                ->limit(5)
                ->select()->toArray();
        foreach ($gameLists as $key=>$v){
            $server = Db::table('tab_user_play_record')->where('user_id',session('member_auth.user_id'))
                    ->where('game_id',$v['id'])->order('create_time desc')->find();
            $gameLists[$key]['server_name'] = get_server_name($server['server_id']);
            $gameLists[$key]["play_url"] = cmf_get_domain()."/media/game/open_ygame/game_id/".$v['id'].'/server_id/'.$server['server_id'];
        }
        $count = 5 - ($gameLists ? count($gameLists) : 0);
        if($count > 0){
            $ids = $gameLists ? array_column($gameLists,'id') : [];
            //$ids[] = $game_id;
            $field = "g.id,g.game_name,g.sdk_version,g.game_status,g.recommend_status,g.sort,s.id as server_id,s.server_name";
            $where['g.sdk_version'] = 4;
            $where['g.game_status'] = 1;
            $where['g.id'] = ['notin',$ids];
            $where['g.recommend_status'] = ['like', '%1%'];
            $recommend = $mGame->alias('g')
                    -> field($field)
                    ->join(['tab_game_server'=>'s'],'s.game_id=g.id','left')
                    ->where('s.id','gt',0)
                    -> where($where)
                    -> order('g.sort desc,g.id desc')
                    ->limit($count)
                    ->select()->toArray();
            $gameLists = $gameLists ? array_merge($gameLists,$recommend) : $recommend;
            if (!empty($gameLists)) {
                foreach ($gameLists as $k => $v) {
                    $gameLists[$k]["play_url"] = cmf_get_domain()."/media/game/open_ygame/game_id/".$v['id'].'/server_id/'.$v['server_id'];
                }
            }
        }
        $result['data'] = $gameLists;
        return $result;
    }



}
