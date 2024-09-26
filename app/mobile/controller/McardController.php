<?php
/**
 *
 * @author: 鹿文学
 * @Datetime: 2019-03-25 10:41
 */

namespace app\mobile\controller;
use think\weixinsdk\Weixin;
use think\Db;


class McardController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        if(cmf_get_option('mcard_set')['status'] != 1){
            $this->error('尊享卡未开启');
        }
    }

    /**
     * @函数或方法说明
     * @代金券列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/6 16:56
     */
    public function index()
    {
        $config = cmf_get_option('mcard_set');
        //代金券信息查询
        $coupon_id = $config['coupon_id'];
        $coupon = get_coupon_info($coupon_id);
        $this->assign('coupon',$coupon);
        //尊享卡信息查询
        $user = get_user_entity(UID,false,'id,account,head_img,vip_level,phone,balance,nickname,point,member_days,end_time');
        //会员状态
        if($user['end_time'] > time()){
            $user['member_status'] = 1;
        }elseif($user['end_time']){
            $user['member_status'] = 2;
        }else{
            $user['member_status'] = 0;
        }
        $mcard_set = cmf_get_option('mcard_set');
        if($mcard_set['config1']['card_name']){
            $card[$mcard_set['config1']['days']] = $mcard_set['config1'];
        }
        if($mcard_set['config2']['card_name']){
            $card[$mcard_set['config2']['days']] = $mcard_set['config2'];
        }
        if($mcard_set['config3']['card_name']){
            $card[$mcard_set['config3']['days']] = $mcard_set['config3'];
        }
        krsort($card);
        //获取尊享卡名称
        if($user['end_time']){
            foreach ($card as $key=>$v){
                if($user['member_days'] >= $key){
                    $user['mcard_name'] = $v['card_name'];
                    break;
                }
            }
        }
        $this->assign('card',end($card));
        $this->assign('user',$user );
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @尊享卡规则
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/8/14 14:04
     */
	public function rule()
    {
        //代金券信息查询
        $coupon_id = cmf_get_option('mcard_set')['coupon_id'];
        $coupon = get_coupon_info($coupon_id);
        $this->assign('coupon',$coupon);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @购买尊享卡
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/8/14 14:26
     */
	public function buy()
    {
        $this->isLogin();//登录验证
        $mcard_set = cmf_get_option('mcard_set');
        if($mcard_set['status'] == 0){
            $this->error('尊享卡未开启');
        }
        //代金券信息查询
        $coupon_id = $mcard_set['coupon_id'];
        $coupon = get_coupon_info($coupon_id);
        $this->assign('coupon',$coupon);
        //尊享卡信息查询
        $user = get_user_entity(UID,false,'id,account,head_img,vip_level,phone,balance,nickname,point,member_days,end_time');
        //会员状态
        if($user['end_time'] > time()){
            $user['member_status'] = 1;
        }elseif($user['end_time']){
            $user['member_status'] = 2;
        }else{
            $user['member_status'] = 0;
        }
        if($mcard_set['config1']['card_name']){
            $card[$mcard_set['config1']['days']] = $mcard_set['config1'];
        }
        if($mcard_set['config2']['card_name']){
            $card[$mcard_set['config2']['days']] = $mcard_set['config2'];
        }
        if($mcard_set['config3']['card_name']){
            $card[$mcard_set['config3']['days']] = $mcard_set['config3'];
        }
        krsort($card);
        //获取尊享卡名称
        if($user['end_time']){
            foreach ($card as $key=>$v){
                if(empty($v['price']) || empty($v['days'])){
                    continue;
                }
                if($user['member_days'] >= $key){
                    $user['mcard_name'] = $v['card_name'];
                    break;
                }

            }
        }
        ksort($card);
        $this->assign('card',$card);
        $this->assign('user',$user );
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @支付宝支付
     * @author: 郭家屯
     * @since: 2020/8/17 14:23
     */
    public function alipay()
    {
        $this->isLogin();
        if(!$this->request->isPost()){
            $this->error('请求失败');
        }
        $data = $this->request->param();

        if(cmf_is_wechat()){
            // 返回跳转链接
            $url = url('alipay2',$data,true,true);
            return json([
                    'code'=>1,
                    'url'=>$url,
                ]
            );
            exit;
            // return $this->fetch();
        }
        //获取会员卡类型
        $mcard_set = cmf_get_option('mcard_set');
        if($mcard_set['status'] == 0){
            $this->error('尊享卡未开启');
        }
        if($mcard_set['config1']['card_name'] == $data['card_name']){
            $card = $mcard_set['config1'];
        }elseif($mcard_set['config2']['card_name'] == $data['card_name']){
            $card = $mcard_set['config2'];
        }elseif($mcard_set['config3']['card_name'] == $data['card_name']){
            $card = $mcard_set['config3'];
        }else{
            $this->error('会员卡类型不存在');
        }
        if(empty($card['price']) || empty($card['days'])){
            $this->error('尊享卡配置错误，请联系客服');
        }
        if ($card['price'] < 0.01) {
            $this->error('金额不能为0');
        }
        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $data['pay_way'] = 3;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        $body = "尊享卡会员充值";
        $title = "尊享卡会员充值";
        $table = 'member';
        $data['pay_order_number'] = create_out_trade_no("MC_");
        $user = get_user_entity(UID,false,'id,account,nickname,promote_id,promote_account');
        if(session('app_user_login')==1){
            $module = "app";
        }else{
            $module = "mobile";
        }
    
        $vo = new \think\pay\PayVo();
        $vo->setBody($body)
                ->setFee($card['price'])//支付金额
                ->setTitle($title)
                ->setOrderNo($data['pay_order_number'])
                ->setService($data['service'])
                ->setSignType("MD5")
                ->setPayMethod("wap")
                ->setTable($table)
                ->setPayWay($data['pay_way'])
                ->setUserId($user['id'])
                ->setAccount($user['account'])
                ->setUserNickName($user['nickname'])
                ->setPromoteId($user['promote_id'])
                ->setPromoteName($user['promote_account'])
                ->setMemberName($card['card_name'])
                ->setDays($card['days'])
                ->setModule($module)
                ->setFreeDays($card['free_days']);

        //新渠道支付流程
        $promotePay = new \think\PromotePay(0,1);
        $result = $promotePay->buildRequestForm($vo);
        $this->success('请求成功',$result['pay_url']);

        // $pay = new \think\Pay($data['pay_type'], $config['config']);
        // $url = $pay->buildRequestForm($vo);
        // return json(['code'=>1,'url'=>$url]);
    }
    // 微信内用支付宝支付
    // 处理微信浏览器屏蔽支付宝的问题
    // 照抄上面的alipay 方法  by wjd 
    public function alipay2()
    {
        if(cmf_is_wechat()){
            return $this->fetch();
        }
        $data = $this->request->param();
        $user_id = (int) $data['user_id'];
        if($user_id <= 0){
            $this->error('请您先登录!');
        }
        //获取会员卡类型
        $mcard_set = cmf_get_option('mcard_set');
        if($mcard_set['status'] == 0){
            $this->error('尊享卡未开启');
        }
        if($mcard_set['config1']['card_name'] == $data['card_name']){
            $card = $mcard_set['config1'];
        }elseif($mcard_set['config2']['card_name'] == $data['card_name']){
            $card = $mcard_set['config2'];
        }elseif($mcard_set['config3']['card_name'] == $data['card_name']){
            $card = $mcard_set['config3'];
        }else{
            $this->error('会员卡类型不存在');
        }
        if(empty($card['price']) || empty($card['days'])){
            $this->error('尊享卡配置错误，请联系客服');
        }
        if ($card['price'] < 0.01) {
            $this->error('金额不能为0');
        }
        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $data['pay_way'] = 3;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        $body = "尊享卡会员充值";
        $title = "尊享卡会员充值";
        $table = 'member';
        $data['pay_order_number'] = create_out_trade_no("MC_");
        $user = get_user_entity($user_id,false,'id,account,nickname,promote_id,promote_account');
        if(session('app_user_login')==1){
            $module = "app";
        }else{
            $module = "mobile";
        }
        $vo = new \think\pay\PayVo();
        $vo->setBody($body)
                ->setFee($card['price'])//支付金额
                ->setTitle($title)
                ->setOrderNo($data['pay_order_number'])
                ->setService($data['service'])
                ->setSignType("MD5")
                ->setPayMethod("wap")
                ->setTable($table)
                ->setPayWay($data['pay_way'])
                ->setUserId($user['id'])
                ->setAccount($user['account'])
                ->setUserNickName($user['nickname'])
                ->setPromoteId($user['promote_id'])
                ->setPromoteName($user['promote_account'])
                ->setMemberName($card['card_name'])
                ->setDays($card['days'])
                ->setModule($module)
                ->setFreeDays($card['free_days']);

        //新渠道支付流程
        $promotePay = new \think\PromotePay(0,1);
        $result = $promotePay->buildRequestForm($vo);
        $this->success('请求成功',$result['pay_url']);        
    
        // $pay = new \think\Pay($data['pay_type'], $config['config']);
        // $this->redirect($pay->buildRequestForm($vo));
        // return json(['code'=>1,'url'=>$url]); // 原
    }

    /**
     * @函数或方法说明
     * @尊享卡微信支付
     * @author: 郭家屯
     * @since: 2020/8/17 15:42
     */
    public function weixinpay()
    {
        $this->isLogin();
        if(!$this->request->isPost()){
            $this->error('请求失败');
        }
        if (pay_type_status('wxscan') != 1) {
            $this->error('微信支付未开启');
        }
        $data = $this->request->param();
        //获取会员卡类型
        $mcard_set = cmf_get_option('mcard_set');
        if($mcard_set['status'] == 0){
            $this->error('尊享卡未开启');
        }
        if($mcard_set['config1']['card_name'] == $data['card_name']){
            $card = $mcard_set['config1'];
        }elseif($mcard_set['config2']['card_name'] == $data['card_name']){
            $card = $mcard_set['config2'];
        }elseif($mcard_set['config3']['card_name'] == $data['card_name']){
            $card = $mcard_set['config3'];
        }else{
            $this->error('会员卡类型不存在');
        }
        if(empty($card['price']) || empty($card['days'])){
            $this->error('尊享卡配置错误，请联系客服');
        }
        if ($card['price'] < 0.01) {
            $this->error('金额不能为空');
        }
        $data['pay_amount'] = $card['price'];
        $data['days'] = $card['days'];
        $data['free_days'] = $card['free_days'];
        $data['member_name'] = $card['card_name'];
        $data['pay_way'] = 4;
        $body = "尊享卡会员充值";
        $title = "尊享卡会员充值";
        $table = 'member';
        $data['pay_order_number'] = create_out_trade_no("MC_");
        $user = get_user_entity(UID,false,'id,account,nickname,promote_id,promote_account');
        if(session('app_user_login')==1){
            $module = "app";
        }else{
            $module = "mobile";
        }

        $vo = new \think\pay\PayVo();
        $vo->setBody($body)
                ->setFee($card['price'])//支付金额
                ->setTitle($title)
                ->setOrderNo($data['pay_order_number'])
                ->setService($data['service'])
                ->setSignType("MD5")
                ->setPayMethod("wap")
                ->setTable($table)
                ->setPayWay($data['pay_way'])
                ->setUserId($user['id'])
                ->setAccount($user['account'])
                ->setUserNickName($user['nickname'])
                ->setPromoteId($user['promote_id'])
                ->setPromoteName($user['promote_account'])
                ->setMemberName($card['card_name'])
                ->setDays($card['days'])
                ->setModule($module)
                ->setFreeDays($card['free_days']);

        //新渠道支付流程
        $promotePay = new \think\PromotePay(0,2);
        $result = $promotePay->buildRequestForm($vo);
        $this->success('请求成功',$result['pay_url']);



        // $weixn = new Weixin();
        // $is_pay = json_decode($weixn->weixin_pay($title, $data['pay_order_number'], $data['pay_amount'], 'MWEB'), true);
        // if ($is_pay['status'] == 1) {
        //     add_member($data,$user);
        // } else {
        //     $this->error('支付失败');
        // }
        // if (!empty($is_pay['mweb_url'])) {
        //     if(session('app_user_login')==1){
        //         $url = '//' . cmf_get_option('admin_set')['web_site'] . '/mobile/pay/wechatJumpPage' . "?jump_url=" . urlencode($is_pay['mweb_url'] . "&redirect_url=" . urlencode(url('@app/pay/pay_success', ['out_trade_no'=>$data['pay_order_number']], true, true)));
        //     }else{
        //         $url = '//' . cmf_get_option('admin_set')['web_site'] . '/mobile/pay/wechatJumpPage' . "?jump_url=" . urlencode($is_pay['mweb_url'] . "&redirect_url=" . urlencode(url('user/index', [], true, true)));
        //     }

        // } else {
        //     $this->error('支付发生错误,请重试');
        // }
        // return json(['code'=>1,'url'=>$url]);
    }

}