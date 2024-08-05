<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\mobile\controller;

use app\recharge\model\SpendBalanceModel;
use app\recharge\model\SpendBindModel;
use app\member\model\UserPlayModel;
use think\Db;
use think\Request;
use think\weixinsdk\Weixin;
use app\member\model\UserTransactionModel;
use app\member\model\UserTransactionOrderModel;
use app\common\logic\PayLogic;
use app\promote\model\PromotedepositModel;
use app\promote\model\PromotebindModel;
use think\facade\View;

class PayController extends BaseController
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        if (AUTH_PAY != 1 || AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限和充值权限');
            } else {
                $this->error('请购买用户权限和充值权限', url('index/index'));
            };
        }
    }


    /**
     * @充值首页
     *
     * @return mixed
     *
     * @author: zsl
     * @since: 2019/3/29 10:41
     */
    public function pay()
    {
        if (!UID) {
            $this->error('请登录账号');
        }
        $user_info = get_user_entity(UID);
        $userplay = new UserPlayModel();
        $user_play_data = $userplay->getLists(['user_id'=>UID],'id,game_id');
        $this->assign('user_info', $user_info);
        $this->assign('user_play_game', array_column($user_play_data,'game_id'));

        //封禁判断-20210713-byh

        if(!judge_user_ban_status($user_info['promote_id'],$user_info['game_id'],UID,$user_info['equipment_num'],get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }
        return $this->fetch('pay');
    }

    public function get_user_play_game()
    {
        if($this->request->isAjax()){
            $account = $this->request->param('account');
            if (!$account) {
                $this->error('请输入账号');
            }else{
                $userplay = new UserPlayModel();
                $user_id = get_user_entity($account,true,'id')['id'];
                if(empty($user_id)){
                    $this->error('用户不存在');
                }
                $user_play_data = $userplay->getLists(['user_id'=>$user_id],'id,game_id');
                $game_ids = array_column($user_play_data,'game_id');
                if(empty($game_ids)){
                    $data = [];
                }else{
                    $map['game_status'] = 1;
                    $map['id']=['in',$game_ids];
                    //更改-获取当前登录玩家充值绑币可以享受的折扣-20210830-byh
                    $data = get_game_list('id,game_name',$map);
                    if(!empty($data)){
                        $promote_id = get_user_entity($user_id,false,'promote_id')['promote_id']??0;
                        $logic = new PayLogic();
                        foreach ($data as $k => $v){
                            $_discount = $logic->get_bind_discount($v['id'],$promote_id,$user_id);
                            $data[$k]['bind_recharge_discount'] = $_discount['discount']*10;
                        }
                    }
                }
                return json(['code'=>200,'data'=>$data]);
            }
        }
    }

    /**
     * @检查用户名
     *
     * @return \think\response\Json
     * @author: zsl
     * @since: 2019/3/29 15:00
     */
    public function checkAccount()
    {
        $account = $this->request->param('account');
        $user = get_user_entity($account, true);
        $game_id = $this -> request -> param('game_id');
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($this->request->param('promote_id'),$game_id,session('member_auth.user_id'),$this->request->param('equipment_num'),get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }
        if ($user) {
            //排除页游
            $gameInfo = get_game_entity($game_id, 'id,sdk_version');
            if ($gameInfo['sdk_version'] != 4) {
                //检查用户是否属于自定义支付渠道
                $isCustom = check_user_is_custom_pay_channel($account, true);
                if ($isCustom) {
                    $this -> error('该账号暂不支持平台币/绑币，请在游戏中支付');
                }
            }
            $this -> success('用户存在');
        } else {
            $this->error('用户不存在,请检查输入');
        }
    }

    // 处理微信浏览器屏蔽支付宝的问题
    // 照抄下面的alipay 方法  by wjd 
    public function alipay2(){
        if(cmf_is_wechat()){
            return $this->fetch();
        }
        $data = $this->request->param();
        $user = get_user_entity($data['account'], true,'id,account,promote_id,promote_account');
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($data['promote_id'],$data['game_id'],session('member_auth.user_id'),$data['equipment_num'],get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }
        if (empty($user)) {
            $this->error('用户不存在');
        }
        $data['money'] = (int)$data['money'];
        if ($data['money'] < 1) {
            $this->error('金额不能低于1元');
        } else if ($data['money'] > 50000) {
            $this->error('金额不能大于50000元');
        }
        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $data['pay_way'] = 3;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        $data['cost'] = $data['money'];
        if($data['coin_type'] == 1){
            if(empty($data['game_id'])){
                $this->error('请选择游戏');
            }
            $userplay = Db::table('tab_user_play')->field('user_id,user_account,game_id,game_name')->where('user_account',$data['account'])->where('game_id',$data['game_id'])->find();
            if(!$userplay){
                $this->error('游戏角色不存在');
            }
            //获取折扣
            $body = "绑币充值";
            $title = "绑币";
            $table = 'bind';
            $data['pay_order_number'] = create_out_trade_no("PB_");
            //获取折扣-绑币充值折扣新增-byh-20210825-start
            $lPay = new \app\common\logic\PayLogic();
            $discount_info = $lPay -> get_bind_discount($data['game_id'], $user['promote_id'], $user['id']);
            $discount = $discount_info['discount']*10;
            $data['discount_type'] = $discount_info['discount_type'];
            $data['money'] = round($data['money']*$discount_info['discount'],2);
            //获取折扣-绑币充值折扣新增-byh-20210825-end
        }else{
            $body = "平台币充值";
            $title = "平台币";
            $table = "deposit";
            $data['pay_order_number'] = create_out_trade_no("PF_");
        }
        if(session('app_user_login')==1){
            $module = "app";
        }else{
            $module = "mobile";
        }
        $data['order_no'] = $data['pay_order_number'];
        $pay = new \think\Pay($data['pay_type'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody($body)
            ->setFee($data['money'])//支付金额
            ->setTitle($title)
            ->setOrderNo($data['order_no'])
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
            ->setGameId($userplay['game_id'])
            ->setDiscount($discount)
            ->setDiscount_type($data['discount_type'])
            ->setCost($data['cost'])
            ->setModule($module)
            ->setGameName($userplay['game_name']);
//        $this->redirect($pay->buildRequestForm($vo));
        return $pay->buildRequestForm($vo);//20210628-byh
      
    }

    /**
     * @支付宝支付
     *
     * @author: zsl
     * @since: 2019/3/29 14:56
     */
    public function alipay()
    {
        $data = $this->request->param();
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($data['promote_id'],$data['game_id'],session('member_auth.user_id'),$data['equipment_num'],get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }

        //排除页游
        $gameInfo = get_game_entity($data['game_id'],'id,sdk_version');
        if($gameInfo['sdk_version']!=4){
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($data['account'], true);
            if ($isCustom) {
                $this -> error('该账号暂不支持平台币/绑币，请在游戏中支付');
            }
        }


        if(cmf_is_wechat()){
            // 处理微信浏览器中打开的问题
            $url = url('alipay2',$data,true,true);
//            return $this->redirect($url);
            $this->success('请求成功',$url);//20210628-byh
        }
        $user = get_user_entity($data['account'], true,'id,account,promote_id,promote_account');
        if (empty($user)) {
            $this->error('用户不存在');
        }
        $data['money'] = (int)$data['money'];
        if ($data['money'] < 1) {
            $this->error('金额不能低于1元');
        } else if ($data['money'] > 50000) {
            $this->error('金额不能大于50000元');
        }
        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $data['pay_way'] = 3;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        $data['cost'] = $data['money'];
        if($data['coin_type'] == 1){
            if(empty($data['game_id'])){
                $this->error('请选择游戏');
            }
            $userplay = Db::table('tab_user_play')->field('user_id,user_account,game_id,game_name')->where('user_account',$data['account'])->where('game_id',$data['game_id'])->find();
            if(!$userplay){
                $this->error('游戏角色不存在');
            }
            //获取折扣
            $body = "绑币充值";
            $title = "绑币";
            $table = 'bind';
            $data['pay_order_number'] = create_out_trade_no("PB_");
            //获取折扣-绑币充值折扣新增-byh-20210825-start
            $lPay = new \app\common\logic\PayLogic();
            $discount_info = $lPay -> get_bind_discount($data['game_id'], $user['promote_id'], $user['id']);
            $discount = $discount_info['discount']*10;
            $data['discount_type'] = $discount_info['discount_type'];
            $data['money'] = round($data['money']*$discount_info['discount'],2);
            //获取折扣-绑币充值折扣新增-byh-20210825-end
//            $data['money'] = round($data['money']*$discount/10,2);
        }else{
            $body = "平台币充值";
            $title = "平台币";
            $table = "deposit";
            $data['pay_order_number'] = create_out_trade_no("PF_");
        }
        if(session('app_user_login')==1){
            $module = "app";
        }else{
            $module = "mobile";
        }
        $data['order_no'] = $data['pay_order_number'];

        //判断后台配置支付宝使用APP支付还是wap支付-原逻辑为wap支付,如果是APP支付,需调整部分属性数据-20210626-byh
        if ($config['config']['type'] == 2 || get_devices_type()==2 || session('app_user_login')!=1) {/*支付宝 wap支付 iOS系统也走wap*/
            $pay_method = 'wap';
        }else{/* 支付宝app支付 */
            $pay_method = 'mobile';
            $data['service'] = "mobile.securitypay.pay";

        }

        $pay = new \think\Pay($data['pay_type'], $config['config']);
        $vo = new \think\pay\PayVo();

        $vo->setBody($body)
            ->setFee($data['money'])//支付金额
            ->setTitle($title)
            ->setOrderNo($data['order_no'])
            ->setService($data['service'])
            ->setSignType("MD5")
            ->setPayMethod($pay_method)
            ->setTable($table)
            ->setPayWay($data['pay_way'])
            ->setUserId($user['id'])
            ->setAccount($user['account'])
            ->setUserNickName($user['nickname'])
            ->setPromoteId($user['promote_id'])
            ->setPromoteName($user['promote_account'])
            ->setGameId($userplay['game_id'])
            ->setDiscount($discount)
            ->setDiscount_type($data['discount_type'])
            ->setCost($data['cost'])
            ->setModule($module)
            ->setGameName($userplay['game_name']);
        if($config['config']['type'] == 2|| get_devices_type()==2 || session('app_user_login')!=1){/*支付宝 wap支付 返回的链接直接跳转 */
//            $this->redirect($pay->buildRequestForm($vo));
            $pay_url = $pay->buildRequestForm($vo);
            $this->success('请求成功',$pay_url);//20210628-byh
        }else{//APP支付,获取支付信息返回20210625-byh
            $result = $pay->buildRequestForm($vo);
            $res_msg = [
//                "orderInfo" => base64_encode($result['arg']),
                "orderInfo" => $result['arg'],
                "out_trade_no" => $result['out_trade_no'],
                "order_sign" => $result['sign'],
                'ali_app'=>1
            ];
            $this->success('获取成功','',$res_msg);
        }


    }

    /**
     * @微信支付
     *
     * @return mixed
     *
     * @author: zsl
     * @since: 2019/3/29 14:57
     */
    public function weixinpay()
    {
        $data = $this->request->param();
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($data['promote_id'],$data['game_id'],session('member_auth.user_id'),$data['equipment_num'],get_client_ip(),$type=3)){
            $this -> error( "当前被禁止充值，请联系客服");
        }
        $user = get_user_entity($data['account'], true,'id,account,promote_id,promote_account');
        if (empty($user)) {
            $this->error('用户不存在');
        }
        //排除页游
        $gameInfo = get_game_entity($data['game_id'], 'id,sdk_version');
        if ($gameInfo['sdk_version'] != 4) {
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($data['account'], true);
            if ($isCustom) {
                $this -> error('该账号暂不支持平台币/绑币，请在游戏中支付');
            }
        }

        $data['money'] = (int)$data['money'];
        if ($data['money'] < 1) {
            $this->error('金额不能低于1元');
        }
        if (pay_type_status('wxscan') != 1) {
            $this->error('微信支付未开启');
        }
        $data['cost'] = $data['money'];
        if($data['coin_type'] == 1){
            if(empty($data['game_id'])){
                $this->error('请选择游戏');
            }
            $userplay = Db::table('tab_user_play')->field('user_id,user_account,game_id,game_name')->where('user_account',$data['account'])->where('game_id',$data['game_id'])->find();
            $data['game_name'] = $userplay['game_name'];
            if(!$userplay){
                $this->error('游戏角色不存在');
            }
            //获取折扣
            $title = "绑币充值";
            $data['pay_order_number'] = create_out_trade_no("PB_");
            //获取折扣-绑币充值折扣新增-byh-20210825-start
            $lPay = new \app\common\logic\PayLogic();
            $discount_info = $lPay -> get_bind_discount($data['game_id'], $user['promote_id'], $user['id']);
            $data['discount'] = $discount_info['discount']*10;
            $data['discount_type'] = $discount_info['discount_type'];
            $data['money'] = round($data['money']*$discount_info['discount'],2);
            //获取折扣-绑币充值折扣新增-byh-20210825-end
//            $data['money'] = round($data['money']*$discount/10,2);
        }else{
            $title = "平台币充值";
            $data['pay_order_number'] = create_out_trade_no("PF_");

        }
        $data['order_no'] = $data['pay_order_number'];
        $data['pay_amount'] = $data['money'];
        $data['pay_status'] = 0;
        $data['pay_way'] = 4;
        $data['user_id'] = $user['id'];
        $data['spend_ip'] = get_client_ip();
        //H5支付
        $weixn = new Weixin();
        $is_pay = json_decode($weixn->weixin_pay($title, $data['pay_order_number'], $data['money'], 'MWEB'), true);
        if ($is_pay['status'] == 1) {
            if($data['coin_type'] == 1){
                add_bind($data,$user);
            }else{
                add_deposit($data,$user);
            }
        } else {
            $this->error('支付失败');
        }
        if (!empty($is_pay['mweb_url'])) {
            if(session('app_user_login')==1){
                $url = '//' . cmf_get_option('admin_set')['web_site'] . '/mobile/pay/wechatJumpPage' . "?jump_url=" . urlencode($is_pay['mweb_url'] . "&redirect_url=" . urlencode(url('@app/pay/pay_success', ['out_trade_no'=> $data['pay_order_number']], true, true)));
            }else{
                $url = '//' . cmf_get_option('admin_set')['web_site'] . '/mobile/pay/wechatJumpPage' . "?jump_url=" . urlencode($is_pay['mweb_url'] . "&redirect_url=" . urlencode(url('User/index', [], true, true)));
            }
//            $this->redirect($url);
            $this->success('请求成功',$url);

        } else {
            $this->error('支付发生错误,请重试');
        }
    }


    /**
     * @充值记录
     *
     * @return mixed
     *
     * @author: zsl
     * @since: 2019/3/29 10:42
     */
    public function record()
    {
        //查询用户所有充值记录
        if (!UID) {
            $this->error('请登录账号');
        }
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取平台币充值记录
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2019/10/16 14:55
     */
    public function get_deposit(){
        $limit = $this->request->param('limit',10);
        $p = $this->request->param('p',1);
        $map['pay_id'] = UID;
        $model = new SpendBalanceModel();
        $data = $model->getMobileLists($map,$p,$limit);
        return json($data);
    }

    /**
     * @函数或方法说明
     * @获取平台币充值记录
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2019/10/16 14:55
     */
    public function get_bind(){
        $limit = $this->request->param('limit',10);
        $p = $this->request->param('p',1);
        $map['pay_id'] = UID;
        $model = new SpendBindModel();
        $data = $model->getMobileLists($map,$p,$limit);
        return json($data);
    }

    /**
     * 微信h5支付中间页
     */
    public function wechatJumpPage()
    {

        $jump_url = input('jump_url');
        if (!empty($jump_url)) {
            $jump_url = str_replace('&amp;', '&', $jump_url);
            $this->assign('jump_url', $jump_url);
        }

        return $this->fetch();
    }


//    /**
//     * [微信内公众号支付]
//     * @author 郭家屯[gjt]
//     */
//    public function get_wx_code()
//    {
//        $param = $this->request->param();
//        switch ($param['weixin_pay_type']){
//            case 1:
//                $data = $this->get_ptb_pay($param);
//                break;
//            case 2:
//                $data = $this->get_trade_pay($param);
//                break;
//            case 3:
//                $data = $this->get_continue_trade_pay($param);
//                break;
//            case 4:
//                $data = $this->get_promote_balance($param);
//                break;
//            default:
//                $this->error("支付方式错误");
//        }
//        Vendor("wxPayPubHelper.WxPayPubHelper");
//        // 使用jsapi接口
//
//        $pay_set = get_pay_type_set('wxscan');
//        $wx_config = get_user_config_info('wechat');
//
//        $jsApi = new \JsApi_pub($wx_config['appsecret'], $pay_set['config']['appid'], $pay_set['config']['key']);
//        //获取code码，以获取openid
//        $openid = session("wechat_token.openid");
//        $weixn = new Weixin();
//        $amount = $data['pay_amount'];
//        $out_trade_no = $data['pay_order_number'];
//        $is_pay = $weixn->weixin_jsapi($data['title'], $out_trade_no, $amount, $jsApi, $openid);
//        $this->assign('jsApiParameters', $is_pay);
//        $this->assign('hostdomain', $_SERVER['HTTP_HOST']);
//        return $this->fetch();
//    }

    /**
     * @函数或方法说明
     * @检查角色是否存在
     * @author: 郭家屯
     * @since: 2019/10/14 19:11
     */
    public function checkuserplay(){
        $account = $this->request->param('account');
        $game_id = $this->request->param('game_id');
        if(empty($account) || empty($game_id)){
            $this->error('参数错误');
        }
        $userplay = Db::table('tab_user_play')->field('id')->where('user_account',$account)->where('game_id',$game_id)->find();
        if(!$userplay){
            $this->error('游戏角色不存在');
        }
        return true;
    }

    /**
     * 查询玩家在当前游戏中享受的折扣
     * by:byh 2021-9-1 16:08:53
     */
    public function get_account_game_bind_discount()
    {
        $game_id = $this->request->param('game_id');
        $account = $this->request->param('account');
        $user = get_user_entity($account,true,'id,promote_id');
        $lPay = new \app\common\logic\PayLogic();
        $discount = $lPay -> get_bind_discount($game_id, $user['promote_id'], $user['id']);
        $discount = $discount['discount']*10;
        return json(['code'=>200,'discount'=>$discount]);
    }

//    /**
//     * @函数或方法说明
//     * @平台币充值
//     * @author: 郭家屯
//     * @since: 2020/7/11 9:23
//     */
//    protected function get_ptb_pay($data=[])
//    {
//        $user = get_user_entity($data['account'], true,'id,account,promote_id,promote_account');
//        if (empty($user)) {
//            $this->error('用户不存在');
//        }
//        $data['money'] = (int)$data['money'];
//        if ($data['money'] < 1) {
//            $this->error('金额不能低于1元');
//        }
//        if (pay_type_status('wxscan') != 1) {
//            $this->error('微信支付未开启');
//        }
//        $data['cost'] = $data['money'];
//        if($data['coin_type'] == 1){
//            if(empty($data['game_id'])){
//                $this->error('请选择游戏');
//            }
//            $userplay = Db::table('tab_user_play')->field('user_id,user_account,game_id,game_name')->where('user_account',$data['account'])->where('game_id',$data['game_id'])->find();
//            $data['game_name'] = $userplay['game_name'];
//            if(!$userplay){
//                $this->error('游戏角色不存在');
//            }
//            //获取折扣
//            $discount = get_game_entity($data['game_id'],'bind_recharge_discount')['bind_recharge_discount'];
//            $data['discount'] = $discount;
//            $title = "绑币充值";
//            $data['pay_order_number'] = create_out_trade_no("PB_");
//            $data['money'] = round($data['money']*$discount/10,2);
//        }else{
//            $title = "平台币充值";
//            $data['pay_order_number'] = create_out_trade_no("PF_");
//
//        }
//        $data['title'] = $title;
//        $data['order_no'] = $data['pay_order_number'];
//        $data['pay_amount'] = $data['money'];
//        $data['pay_status'] = 0;
//        $data['pay_way'] = 4;
//        $data['user_id'] = $user['id'];
//        $data['spend_ip'] = get_client_ip();
//        //微信内部支付
//        if($data['coin_type'] == 1){
//            add_bind($data,$user);
//        }else{
//            add_deposit($data,$user);
//        }
//        return $data;
//
//    }
//
//    /**
//     * @函数或方法说明
//     * @小号交易公众号支付
//     * @author: 郭家屯
//     * @since: 2020/7/11 9:43
//     */
//    protected function get_trade_pay($request=[])
//    {
//        $model = new UserTransactionModel();
//        $transaction = $model->where('id',$request['transaction_id'])->where('status','in',[-1,1])->find();
//        if(!$transaction){
//            $this->error('商品已出售或者已下架');
//        }
//        //锁定交易
//        if($transaction['status'] == 1){
//            $save['status'] = -1;
//            $save['lock_time'] = time();
//            $model->where('id',$request['transaction_id'])->update($save);
//        }else{
//            $ordermodel = new UserTransactionOrderModel();
//            $order = $ordermodel->where('transaction_id',$request['transaction_id'])->field('id,user_id')->order('id desc')->find();
//            if($order['user_id'] != UID){
//                $this->error('当前商品已被锁定，可购买其他商品');
//            }else{
//                $model->where('id',$request['transaction_id'])->setField('lock_time',time());
//                $ordermodel->where('id',$order['id'])->setField('pay_status',2);
//            }
//        }
//        $user = get_user_entity(UID,false,'id,account,balance');
//        if (pay_type_status('wxscan') != 1) {
//            $this->error('微信支付未开启');
//        }
//        $data['pay_order_number'] = create_out_trade_no("TO_");
//        $data['pay_amount'] = $transaction['money'];
//        $data['pay_status'] = 0;
//        $data['pay_way'] = 4;
//        $data['user_id'] = $user['id'];
//        $data['title'] = "交易订单支付";
//        $fee = cmf_get_option('transaction_set')['fee'];
//        $min_dfee = cmf_get_option('transaction_set')['min_dfee'];
//        $fee_money = 0;
//        if($fee){
//            $fee_money = $transaction['money'] * $fee/100;
//        }
//        if($min_dfee){
//            if($min_dfee > $fee_money ){
//                $fee_money = $min_dfee;
//            }
//        }
//        $data['fee'] = $fee_money;
//        if($request['is_balance']){
//            $data['pay_amount'] = $transaction['money'] - $user['balance'];
//            $data['balance_money'] = $user['balance'];
//        }
//        $data['time'] = time();
//        $data['time_expire'] = date('YmdHis',$data['time'])+301;
//
//        $logic = new PayLogic();
//        //微信内部支付
//        $logic -> add_transaction($user, $transaction, $data);
//        return $data;
//    }
//
//    /**
//     * @函数或方法说明
//     * @重新调起小号支付
//     * @author: 郭家屯
//     * @since: 2020/7/11 9:57
//     */
//    protected function get_continue_trade_pay($request=[])
//    {
//        $id = $request['id'];
//        $model = new UserTransactionOrderModel();
//        $order = $model->where('id',$id)->where('pay_status',0)->find();
//        if(!$order || (time()-$order['pay_time'])>300){
//            $this->error('订单已失效');
//        }else{
//            //更新锁定时间
//            $model = new UserTransactionModel();
//            $model->where('id',$order['transaction_id'])->setField('lock_time',time());
//        }
//        if (pay_type_status('wxscan') != 1) {
//            $this->error('微信支付未开启');
//        }
//        $data['pay_amount'] = $order['pay_amount']-$order['balance_money'];
//        $data['time_expire'] = date('YmdHis',$order['create_time'])+301;
//        $data['pay_order_number'] = $order['pay_order_number'];
//        $data['title'] = "交易订单支付";
//        return $data;
//    }
//
//    /**
//     * @函数或方法说明
//     * @推广员平台币充值
//     * @author: 郭家屯
//     * @since: 2020/7/11 10:29
//     */
//    protected function get_promote_balance($data=[])
//    {
//        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1, 'name' => 'wxscan'])->find();
//        if (empty($paytype)) {
//            $this->error('微信支付通道已关闭，请选择其他支付方式');
//        }
//        $promote_id = $data['promote_id'];
//        if (!$promote_id) {
//            $this->redirect(url('channelsite/index/index'));
//        }
//        if($data['is_bind_pay']==1){
//            return $this->weixinpay_bind($data);
//        }
//        $order_no = $data['order_no'] ?: "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
//        $order_is_ex = Db::table('tab_promote_deposit')->field('id')->where(['pay_order_number' => $order_no])->find();
//        if (!empty($order_is_ex)) {
//            $order_no = "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
//        }
//        $to_id = get_promote_list(['account' => $data['account']], 'id')[0]['id'];
//        if (!$to_id) {
//            $this->error('渠道账号不存在');
//        }
//        if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
//            $this->error('金额错误');
//        }
//        $model = new PromotedepositModel();
//        $param['pay_order_number'] = $order_no;
//        $param['promote_id'] = $promote_id;
//        $param['to_id'] = $to_id;
//        $param['pay_amount'] = $data['amount'];
//        $param['pay_way'] = 4;
//        $param['type'] = $data['type'];
//        $param['title'] = "渠道平台币充值";
//        $res = $model->add_promote_deposit($param);
//        if($res == false){
//            $this->error('订单创建失败');
//        }
//        return $param;
//    }
//
//    /**
//     * @函数或方法说明
//     * @代充
//     * @param $data
//     *
//     * @return mixed
//     *
//     * @author: 郭家屯
//     * @since: 2020/7/11 10:34
//     */
//    protected function weixinpay_bind($data=[])
//    {
//        $param = $this->bindpay($data);
//        $param['pay_way'] = 4;
//        $model = new PromotebindModel();
//        $res = $model->add_promote_bind($param);
//        if ($res == false) {
//            $this->error('订单创建失败');
//        }
//        return $param;
//    }
//
//    //绑币充值参数
//    private function bindpay($data=[])
//    {
//        $order_no = $data['order_no'] ?: "TD_" . date('Ymd') . date('His') . sp_random_string(4);//渠道平台币充值
//        $order_is_ex = Db::table('tab_promote_bind')->field('id')->where(['pay_order_number' => $order_no])->find();
//        if (!empty($order_is_ex)) {
//            $this->error('订单已存在，请重新下单');
//        }
//        $game_ids = array_column(session('bind_recharge_game'.$data['promote_id']),'id');
//        $user_ids = array_column(session('bind_recharge_user'.$data['promote_id']),'user_id');
//        if(in_array($data['game_id'],$game_ids)&&in_array($data['user_id'],$user_ids)){
//            if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
//                $this->error('金额错误');
//            }
//            $user = get_user_entity($data['user_id'],false,'promote_id');
//            if(empty($user)){
//                $this->error('用户不存在');
//            }
//            $paylogic = new PayLogic();
//            $game = $paylogic->get_agent_discount($data['game_id'],$user['promote_id']);
//            if(empty($game)){
//                $this->error('游戏不存在');
//            }
//            $request['price'] = $data['amount'];
//            $request['pay_amount'] = $data['amount'];
//            $request['title'] = '绑定平台币代充';
//            $request['body'] = '渠道绑定平台币代充';
//            $request['pay_order_number'] = $order_no;
//            $request['promote_id'] = $user['promote_id'];
//            $request['to_id'] = $data['user_id'];
//            $request['game_id'] = $data['game_id'];
//            $request['discount'] = $game['discount']==0?10:$game['discount'];
//            $request['price'] = round($request['price']*$request['discount']/10,2);
//            $request['cost'] = $data['amount'];
//            $request['table'] = 'promote_bind';
//            return $request;
//        }else{
//            $this->error('订单失效');
//        }
//    }

}