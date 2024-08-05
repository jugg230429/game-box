<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/1
 * Time: 11:40
 */

namespace app\sdksimplify\controller;

use app\common\logic\GameLogic;
use app\common\logic\InvitationLogic;
use app\common\logic\PayLogic;
use app\promote\logic\CustompayLogic;
use app\recharge\logic\CheckAgeLogic;
use app\recharge\model\CouponRecordModel;
use app\recharge\model\SpendModel;
use app\member\model\UserModel;
use app\member\model\UserPlayModel;
use app\api\GameApi;
use think\Db;
use think\weixinsdk\Weixin;
use think\goldpig\GoldPig;
use think\Cache;

class PayController extends BaseController
{
    /**
     * [苹果第三方支付]
     * @author 郭家屯[gjt]
     */
    public function pay_way()
    {
        $user_id = $this->request->param('user_id', 0, 'intval');
        $game_id = $this->request->param('game_id', 0, 'intval');
        $sdk_version = $this->request->param('sdk_version', 0, 'intval');
        //安卓参数写入文件
        if ($sdk_version == 1) {
            $request = $this->request->param();
            $prefix = $request['code'] == 1 ? "SP_" : "PF_";
            $request['pay_order_number'] = create_out_trade_no($prefix);
            file_put_contents(ROOT_PATH . "/app/sdk/orderno/" . $request['user_id'] . "-" . $request['game_id'] . ".txt", think_encrypt(json_encode($request)));
        }
        $file = file_get_contents(ROOT_PATH . "/app/sdk/orderno/" . $user_id . "-" . $game_id . ".txt");
        $request = json_decode(think_decrypt($file), true);
        $data = array(
            'coin' => $request['body'],
            'price' => $request['price'],
            'game_name' => $request['game_name'],
            'code' => $request['code'],
            'sdk_version' => $request['sdk_version'],
            'pay_order_number'=>$request['pay_order_number']
        );

        $user = get_user_entity($user_id,false,'balance,parent_id,promote_id');
        $this->assign('balance', $user['balance']);
        if($data['code'] == 1){
            $promote_id = $user['promote_id'];
            //获取代金券
            $game_id = get_game_entity($request['game_id'],'relation_game_id')['relation_game_id'];
            $coupon = $this->get_valid_coupon($request['user_id'],$game_id,$request['price']);
            $this->assign('coupon',$coupon);
            $paylogic = new PayLogic();
            $discount_info = $paylogic->get_discount($request['game_id'],$promote_id,$request['user_id']);
            $data['discount_price'] = round($discount_info['discount']*$request['price'],2);
            $userplay = Db::table('tab_user_play')->field('user_id,bind_balance')->where('user_id',$request['user_id'])->where('game_id',$request['game_id'])->find();
            $this->assign('bind_balance',$userplay['bind_balance']);
            $this->assign('discount',$discount_info['discount']*10);

        }
        $this->assign('data', $data);
        $this->assign('btncolor', $request['btncolor']);
        //检查用户是否属于自定义支付渠道
        $isCustom = check_user_is_custom_pay_channel($request['user_id']);
        if ($isCustom) {
            $this -> assign('is_custom', 1);
        } else {
            $this -> assign('is_custom', 0);
        }
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @领取代金券
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/10 16:07
     */
    public function coupon(){
        $request['user_id'] = $this->request->param('user_id/d');
        $request['game_id'] = $this->request->param('game_id/d');
        $user = get_user_entity($request['user_id'],false,'promote_id,parent_id');
        $promote_id = $user['promote_id'];
        //未领取
        $game = get_game_entity($request['game_id'],'id,relation_game_id');
        $game_id = $game['relation_game_id'];
        $coupon = $this->get_reciver_coupon($request['user_id'],$promote_id,$game_id);
        foreach ($coupon as $key=>$v){
            $coupon[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('y.m.d',$v['start_time']);
            $coupon[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('y.m.d',$v['end_time']);
        }
        $this->assign('coupon',$coupon);
        //获取已领取
        $model = new CouponRecordModel();
        $my_coupon = $model->get_my_coupon($request['user_id'],3,$game_id);
        foreach ($my_coupon as $key=>$v){
            $my_coupon[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('y.m.d',$v['start_time']);
            $my_coupon[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('y.m.d',$v['end_time']);
            if($v['status'] == 0 && $v['end_time'] >0 && $v['end_time'] <time()){
                $my_coupon[$key]['status'] = 2;
            }
        }
        $this->assign('my_coupon',$my_coupon);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @领取代金券
     * @author: 郭家屯
     * @since: 2020/2/6 11:15
     */
    public function get_pay_coupon()
    {
        $request['user_id'] = $this->request->param('user_id/d');
        $request['game_id'] = $this->request->param('game_id/d');
        $request['coupon_id'] = $this->request->param('coupon_id/d');
        $logic = new GameLogic();
        $result = $logic->getCoupon($request['user_id'],$request['coupon_id']);
        if($result){
            $this->success("领取成功");
        }else{
            $this->error("领取失败");
        }
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
    //使用优惠券
     public function pay_coupon(){
      $user_id = $this->request->param('user_id', 0, 'intval');
         $game_id = $this->request->param('game_id', 0, 'intval');
         $sdk_version = $this->request->param('sdk_version', 0, 'intval');
         //安卓参数写入文件
         if ($sdk_version == 1) {
             $request = $this->request->param();
             $prefix = $request['code'] == 1 ? "SP_" : "PF_";
             $request['pay_order_number'] = create_out_trade_no($prefix);
             file_put_contents(ROOT_PATH . "/app/sdk/orderno/" . $request['user_id'] . "-" . $request['game_id'] . ".txt", think_encrypt(json_encode($request)));
         }
         $file = file_get_contents(ROOT_PATH . "/app/sdk/orderno/" . $user_id . "-" . $game_id . ".txt");
         $request = json_decode(think_decrypt($file), true);

         $data = array(
             'coin' => $request['body'],
             'price' => $request['price'],
             'game_name' => $request['game_name'],
             'code' => $request['code'],
             'sdk_version' => $request['sdk_version'],
         );
         $user = get_user_entity($user_id,false,'balance,parent_id,promote_id');
         $this->assign('balance', $user['balance']);
         if($data['code'] == 1){
             $promote_id = $user['promote_id'];
             $paylogic = new PayLogic();
             $discount_info = $paylogic->get_discount($request['game_id'],$promote_id,$request['user_id']);
             $data['discount_price'] = round($discount_info['discount']*$request['price'],2);
             $userplay = Db::table('tab_user_play')->field('user_id,bind_balance')->where('user_id',$request['user_id'])->where('game_id',$request['game_id'])->find();
             $this->assign('bind_balance',$userplay['bind_balance']);
         }
         $this->assign('data', $data);
         $this->assign('btncolor', $request['btncolor']);
         return $this->fetch();
     }
    /**
     * @函数或方法说明
     * @获取可使用优惠券
     * @author: 郭家屯
     * @since: 2020/2/6 11:40
     */
    protected function get_valid_coupon($user_id=0,$game_id=0,$pay_amount=0)
    {
        $model = new CouponRecordModel();
        $coupon = $model->get_my_coupon($user_id,1,$game_id,$pay_amount);
        foreach ($coupon as $key=>$v){
            $coupon[$key]['start_time'] = $v['start_time'] == 0 ? "永久" : date('Y.d.m',$v['start_time']);
            $coupon[$key]['end_time'] = $v['end_time'] == 0 ? "永久" : date('Y.d.m',$v['end_time']);
        }
        return $coupon;
    }

    /**
     * [微信支付]
     * @param $user_id
     * @param $game_id
     * @author 郭家屯[gjt]
     */
    public function apple_weixin_pay($user_id = '', $game_id = '')
    {
        $file = file_get_contents(ROOT_PATH . "/app/sdk/orderno/" . $user_id . "-" . $game_id . ".txt");
        $request = json_decode(think_decrypt($file), true);
        $request['coupon_id'] = $this->request->param('coupon_id');
        if (empty($request)) {
            echo json_encode(['code' => 0, 'msg' => '登录数据不能为空']);
            exit;
        }
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$game_id,$user_id,$request['equipment_num'],get_client_ip(),$type=3)){
            echo json_encode(['code' => 0, 'msg' => '当前被禁止充值，请联系客服']);
            exit;
        }

        if ($request['code'] != '1') {
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($request['user_id']);
            if ($isCustom) {
                echo json_encode(['code' => 0, 'msg' => '该账号暂不支持平台币/绑币，请在游戏中支付']);
                exit;
            }
        }

        if ($request['price'] <= 0) {
            echo json_encode(['code' => 0, 'msg' => '充值金额有误']);
            exit;
        }
        if ($request['code'] == 1) {
            $spendmodel = new SpendModel();
            $extend_data = $spendmodel->field('id')->where(array('extend' => $request['extend'], 'game_id' => $request['game_id']))->find();
            if ($extend_data) {
                echo json_encode(['code' => 0, 'msg' => '订单号重复，请关闭支付页面重新支付']);
                exit;
            }
        }
        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($request['user_id'], $request['price']);
            if (false === $checkAgeRes) {
                echo json_encode(['code' => 0, 'msg' => $lCheckAge -> getErrorMsg()]);
                exit;
            }
        }
        $request['pay_way'] = 4;
        $this->weixin_pay($request);
    }

    /**
     * [微信支付]
     * @param $request
     * @author 郭家屯[gjt]
     */
    private function weixin_pay($request,$and=0)
    {
        $user = get_user_entity($request['user_id'],false,'id,account,promote_id,promote_account,parent_id');
        //官方微信支付
        $json_data['paytype'] = "wx";
        $json_data['orderno'] = $request['pay_order_number'];
        $json_data['cal_url'] = cmf_get_domain();
        if ($request['price'] <= 0) {
            if($and){
                $this->set_message(1061, "充值金额有误");
            }else{
                return redirect(url('Pay/notice', array('user_id' => $request['user_id'], 'game_id' => $request['game_id'], 'msg' => urlencode('充值金额有误'))));
                exit;
            }
        }
        $request['cost'] = $request['price'];
        if($request['code'] == 1){
            $promote_id = $user['promote_id'];
            $paylogic = new PayLogic();
            //去除代金券金额
            if($request['coupon_id']){
                $coupon_money = $paylogic->get_use_coupon($request['user_id'],$request['price'],$request['coupon_id']);
                if($coupon_money){
                    $new_price = $request['price'] - $coupon_money;
                }else{
                    if($and){
                        $this->set_message(1083, "优惠券使用失败");
                    }else{
                        return redirect(url('Pay/notice', array('user_id' => $request['user_id'], 'game_id' => $request['game_id'], 'msg' => urlencode('优惠券使用失败'))));
                        exit;
                    }
                }
            }
            $discount_info = $paylogic->get_discount($request['game_id'],$promote_id,$request['user_id']);
            if($new_price){
                $request['pay_amount'] = round($discount_info['discount']*$new_price,2);
            }else{
                $request['pay_amount'] = round($discount_info['discount']*$request['price'],2);
            }
            $request['discount_type'] = $discount_info['discount_type'];
            $request['discount'] = $discount_info['discount'];
        }else{
            $request['pay_amount'] = $request['price'];
        }

        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($request['user_id'], $request['pay_amount']);
            if (false === $checkAgeRes) {
                $this -> set_message(1084, $lCheckAge -> getErrorMsg());
            }
        }

        if($request['coupon_id'] > 0 && $new_price <= 0){
            $result = $this->coupon_pay($request,$user);
            if($result){
                $url = cmf_get_domain() . "/sdk/Pay/pay_success/orderno/" . $request['pay_order_number'];
                if ($request['sdk_version'] == 2) {
                    echo json_encode(['code' => 200, 'msg' => '支付成功', 'data' => ['url' => $url,'wap'=>3]]);
                    exit;
                } else {
                    $this->set_message(200, "支付成功",['url'=>$url,'status'=>200,'paytype'=>'wft']);
                }
            }else{
                if ($request['sdk_version'] == 2) {
                    echo json_encode(['code' => 0, 'msg' => '支付失败', 'data' => ['url' => $json_data['url'], 'wap' => 1]]);
                    exit;
                } else {
                    $this->set_message(500, "支付失败");
                }
            }
        }elseif (pay_type_status('wxscan') == 1) {

            //查询是否开启自定义支付
            $lCustomPay = new CustompayLogic();
            $customConfig = $lCustomPay -> getPayConfig($user['promote_id'], $request['game_id'], 'wxscan', $request['pay_amount']);
            if (false !== $customConfig) {
                $request['pay_promote_id'] = $user['promote_id'];
            }

            $weixn = new Weixin();
            $is_pay = json_decode($weixn->weixin_pay("充值", $request['pay_order_number'], $request['pay_amount'], 'MWEB',
                    1,0,0,$user['promote_id'],$request['game_id']), true);
            if ($is_pay['status'] == 1) {
                if ($request['code'] == 1) {
                    add_spend($request,$user);
                } else {
                    add_deposit($request,$user);
                }
                $json_data['paytype'] = 'wx';
                $json_data['status'] = 200;
                if (!empty($request['scheme']) || $request['sdk_version'] == 1) {
                    if($and == 1){
                        $json_data['url'] = $is_pay['mweb_url'];
                    }else{
                        $json_data['url'] = $is_pay['mweb_url'] . '&redirect_url=' . (is_ssl() ? 'https%3A%2F%2F' : 'http%3A%2F%2F') . $_SERVER ['HTTP_HOST'] . "%2Fsdk%2Fpay%2Fpay_success2%2Forderno%2F" . $request['pay_order_number'] . '%2Fgame_id%2F' . $request['game_id'];
                    }
               } else {
                    $json_data['url'] = cmf_get_domain() . "/sdk/Pay/pay_success/orderno/" . $request['pay_order_number'];
                }
            } else {
                $json_data['status'] = 500;
                $json_data['url'] = url('pay/notice', array('user_id' => $request['user_id'], 'game_id' => $request['game_id'], 'msg' => urlencode('支付失败')));
            }
        }
        if ($request['sdk_version'] == 2) {
            echo json_encode(['code' => 200, 'msg' => '', 'data' => ['url' => $json_data['url'], 'wap' => 1]]);
            exit;
        } else {
            $this->set_message(200, "获取成功",$json_data);
        }
    }

    /**
     * [苹果支付宝WAP支付]
     * @param $user_id
     * @param $game_id
     * @author 郭家屯[gjt]
     */
    public function apple_alipay_pay($user_id = '', $game_id = '')
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $file = file_get_contents(ROOT_PATH . "/app/sdk/orderno/" . $user_id . "-" . $game_id . ".txt");
        $request = json_decode(think_decrypt($file), true);
        $request['coupon_id'] = $this->request->param('coupon_id');
        if (empty($request)) {
            echo json_encode(['code' => 0, 'msg' => '登录数据不能为空']);
            exit;
        }
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$game_id,$user_id,$request['equipment_num'],get_client_ip(),$type=3)){
            echo json_encode(['code' => 0, 'msg' => '当前被禁止充值，请联系客服']);
            exit;
        }
        if ($request['code'] != '1') {
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($request['user_id']);
            if ($isCustom) {
                echo json_encode(['code' => 0, 'msg' => '该账号暂不支持平台币/绑币，请在游戏中支付']);
                exit;
            }
        }

        if ($request['price'] <= 0) {
            echo json_encode(['code' => 0, 'msg' => '充值金额有误']);
            exit;
        }
        if ($request['code'] == 1) {
            $spendmodel = new SpendModel();
            $extend_data = $spendmodel->field('id')->where(array('extend' => $request['extend'], 'game_id' => $request['game_id']))->find();
            if ($extend_data) {
                echo json_encode(['code' => 0, 'msg' => '订单号重复，请关闭支付页面重新支付']);
                exit;
            }
        }
        $request['pay_way'] = 3;
        $request['table'] = $request['code'] == 1 ? "spend" : "deposit";
        $user = get_user_entity($request['user_id'],false,'id,account,nickname,promote_id,promote_account,parent_id');
        $new_price = $request['price'];
        if($request['code'] == 1){
            $promote_id = $user['promote_id'];
            $paylogic = new PayLogic();
            //去除代金券金额
            if($request['coupon_id']){
                $coupon_money = $paylogic->get_use_coupon($request['user_id'],$request['price'],$request['coupon_id']);
                if($coupon_money){
                    $new_price = $request['price'] - $coupon_money;
                }else{
                    return redirect(url('Pay/notice', array('user_id' => $request['user_id'], 'game_id' => $request['game_id'], 'msg' => urlencode('优惠券使用失败'))));
                    exit;
                }
            }
            $discount_info = $paylogic->get_discount($request['game_id'],$promote_id,$request['user_id']);
            $request['pay_amount'] = round($discount_info['discount']*$new_price,2);
            $request['discount_type'] = $discount_info['discount_type'];
            $request['discount'] = $discount_info['discount'];
        }else{
            $request['pay_amount'] = $request['price'];
        }
        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($request['user_id'], $request['pay_amount']);
            if (false === $checkAgeRes) {
                echo json_encode(['code' => 0, 'msg' => $lCheckAge -> getErrorMsg()]);
                exit;
            }
        }
        if($request['coupon_id'] > 0 && $new_price <= 0){
            $result = $this->coupon_pay($request,$user);
            if($result){
                $url = cmf_get_domain() . "/sdk/Pay/pay_success/orderno/" . $request['pay_order_number'];
                echo json_encode(['code' => 200, 'msg' => '支付成功', 'data' => ['url' => $url,'wap'=>3]]);
                exit;
            }else{
                echo json_encode(['code' => 0, 'msg' => '支付失败', 'data' => ['url' => 'coupon','wap'=>3]]);
                exit;
            }
        }elseif (pay_type_status('zfb') == 1) {
            $config = get_pay_type_set('zfb');
            if ($config['config']['type'] == 2) {/* wap支付 */
                $request['apitype'] = "alipay";
                $request['config'] = "alipay";
                $request['signtype'] = "MD5";
                $request['server'] = "alipay.wap.create.direct.pay.by.user";
                $request['payway'] = 3;
                $request['title'] = $request['price'];
                $request['body'] = $request['price'];
                $pay_url = $this->pay($request,$user);
                echo json_encode(['code' => 200, 'msg' => '', 'data' => ['url' => $pay_url['url'], 'wap' => 1]]);
                exit;
            } else {/* app支付 */
                $game_set_data = Db::table('tab_game_set')->field('access_key')->where('game_id', $request['game_id'])->find();
                $request['apitype'] = "alipay";
                $request['config'] = "alipay";
                $request['signtype'] = "MD5";
                $request['server'] = "mobile.securitypay.pay";
                $request['payway'] = 3;
                $data = $this->alipay_app_pay($request,$user);
                $md5_sign = $this->encrypt_md5(base64_encode($data['arg']), $game_set_data["access_key"]);
                $data = array("orderInfo" => base64_encode($data['arg']), "out_trade_no" => $data['out_trade_no'], "order_sign" => $data['sign'], "md5_sign" => $md5_sign);

                echo json_encode(['code' => 200, 'msg' => '', 'data' => ['url' => json_encode($data), 'wap' => 0]]);
                exit;
            }
        }
    }

    /**
     * [支付宝wap支付]
     * @param array $param
     * @return \SimpleXMLElement|string
     * @author 郭家屯[gjt]
     */
    private function pay($param = array(),$user=[])
    {

        //获取渠道自定义支付参数
        $lCustomPay = new CustompayLogic();
        $customConfig = $lCustomPay -> getPayConfig($user['promote_id'], $param['game_id'], 'zfb', $param['pay_amount']);
        if(false===$customConfig){
            $config = get_pay_type_set('zfb');
            $payPromoteId = 0;
        }else{
            $config['config'] = $customConfig;
            $payPromoteId = $user['promote_id'];
        }

        $pay = new \think\Pay($param['apitype'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody("充值")
            ->setFee($param['pay_amount'])//支付金额
            ->setTitle($param['title'])
            ->setOrderNo($param['pay_order_number'])
            ->setService($param['server'])
            ->setSignType($param['signtype'])
            ->setPayMethod("wap")
            ->setTable($param['table'])
            ->setPayWay($param['payway'])
            ->setGameId($param['game_id'])
            ->setGameName(get_game_name($param['game_id']))
            ->setGameAppid($param['game_appid'])
            ->setServerId($param['server_id'])
            ->setGameplayerName($param['game_player_name'])
            ->setServerName($param['server_name'])
            ->setUserId($param['user_id'])
            ->setAccount($user['account'])
            ->setUserNickName($user['nickname'])
            ->setSmallId($param['small_id'])
            ->setSmallNickname($param['small_nickname'])
            ->setPromoteId($user['promote_id'])
            ->setPromoteName($user['promote_account'])
            ->setExtend($param['extend'])
            ->setSdkVersion($param['sdk_version'])
            ->setDiscount($param['discount']*10)
            ->setDiscount_type($param['discount_type'])
            ->setCost($param['price'])
            ->setRoleLevel($param['role_level'])
            ->setCouponRecordId($param['coupon_id'])
            ->setExtraparam($param['extra_param'])
            ->setPayPromoteId($payPromoteId);
        $pay_['url'] = $pay->buildRequestForm($vo);
        $pay_['out_trade_no'] = $param['pay_order_number'];
        return $pay_;
    }

    /**
     * [支付宝app支付]
     * @param array $param
     * @return \SimpleXMLElement|string
     * @author 郭家屯[gjt]
     */
    private function alipay_app_pay($param = array(),$user=[])
    {

        //获取渠道自定义支付参数
        $lCustomPay = new CustompayLogic();
        $customConfig = $lCustomPay -> getPayConfig($user['promote_id'], $param['game_id'], 'zfb', $param['pay_amount']);
        if(false===$customConfig){
            $config = get_pay_type_set('zfb');
            $payPromoteId = 0;
        }else{
            $config['config'] = $customConfig;
            $payPromoteId = $user['promote_id'];
        }

        $pay = new \think\Pay($param['apitype'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody("充值记录描述")
            ->setFee($param['pay_amount'])//支付金额
            ->setTitle($param['title'])
            ->setBody($param['body'])
            ->setOrderNo($param['pay_order_number'])
            ->setRatio(0)
            ->setService($param['server'])
            ->setSignType($param['signtype'])
            ->setPayMethod('mobile')
            ->setTable($param['table'])
            ->setPayWay($param['payway'])
            ->setGameId($param['game_id'])
            ->setGameName(get_game_name($param['game_id']))
            ->setGameAppid($param['game_appid'])
            ->setServerId($param['server_id'])
            ->setGameplayerName($param['game_player_name'])
            ->setServerName($param['server_name'])
            ->setUserId($param['user_id'])
            ->setAccount($user['account'])
            ->setUserNickName($user['nickname'])
            ->setSmallId($param['small_id'])
            ->setSmallNickname($param['small_nickname'])
            ->setPromoteId($user['promote_id'])
            ->setPromoteName($user['promote_account'])
            ->setExtend($param['extend'])
            ->setSdkVersion($param['sdk_version'])
            ->setDiscount($param['discount']*10)
            ->setDiscount_type($param['discount_type'])
            ->setCost($param['price'])
            ->setCouponRecordId($param['coupon_id'])
            ->setPayPromoteId($payPromoteId)
            ->setExtraparam($param['extra_param']);
        return $pay->buildRequestForm($vo);
    }

    /**
     * [平台币支付]
     * @param $user_id
     * @param $game_id
     * @author 郭家屯[gjt]
     */
    public function apple_platform_pay($user_id = '', $game_id = '')
    {
        $file = file_get_contents(ROOT_PATH . "/app/sdk/orderno/" . $user_id . "-" . $game_id . ".txt");
        $request = json_decode(think_decrypt($file), true);
        $code = $this->request->param('way');
        $coupon_id = $this->request->param('coupon_id');
        if (empty($request)) {
            return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('参数错误'))));
        }
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$game_id,$user_id,$request['equipment_num'],get_client_ip(),$type=3)){
            return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('当前被禁止充值，请联系客服'))));
        }
        //检查用户是否属于自定义支付渠道
        $isCustom = check_user_is_custom_pay_channel($request['user_id']);
        if ($isCustom) {
            return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('该账号暂不支持平台币/绑币，请在游戏中支付'))));
        }
        if ($request['price'] <= 0) {
            return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('充值金额有误'))));
        }
        $model = new SpendModel();
        $usermodel = new UserModel();
        $extend_data = $model->field('id')->where(array('extend' => $request['extend'], 'game_id' => $request['game_id']))->find();
        if ($extend_data) {
            return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('订单号重复，请关闭支付页面重新支付'))));
        }
        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($user_id, $request['price']);
            if (false === $checkAgeRes) {
                return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode($lCheckAge -> getErrorMsg()))));
            }
        }
        $request['out_trade_no'] = $request['pay_order_number'];
        $request['pay_status'] = 1;
        $request['pay_way'] = 2;
        $user_entity = get_user_entity($request['user_id'],false,'id,account,nickname,promote_id,promote_account,balance,cumulative,parent_id,parent_name,invitation_id');
        $paylogic = new PayLogic();
        switch ($code) {
            case 1:#平台币
                //计算折扣
                $promote_id = $user_entity['promote_id'];
                //去除代金券金额
                if($coupon_id){
                    $coupon_money = $paylogic->get_use_coupon($request['user_id'],$request['price'],$coupon_id);
                    if($coupon_money){
                        $pay_amount = $request['price'] - $coupon_money;
                        $request['coupon_id'] = $coupon_id;
                    }else{
                        return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('优惠券使用失败'))));
                    }
                }else{
                    $pay_amount = $request['price'];
                }
                $discount_info = $paylogic->get_discount($request['game_id'],$promote_id,$request['user_id']);
                $real_price = round($discount_info['discount']*$pay_amount,2);
                $request['discount_type'] = $discount_info['discount_type'];
                $request['discount'] = $discount_info['discount'];
                if($request['coupon_id'] && $real_price <= 0){
                    $result = $this->coupon_pay($request,$user_entity);
                    if($result){
                        $url = cmf_get_domain() . "/sdk/Pay/pay_success/orderno/" . $request['pay_order_number'];
                        return redirect($url);
                    }else{
                        return redirect(url('pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('支付失败'))));
                    }
                }
                if ($user_entity['balance'] < $real_price) {
                    return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('余额不足'))));
                }
                Db::startTrans();
                try {
                    #扣除平台币
                    $usermodel->where("id", $request["user_id"])->setDec("balance", $real_price);
                    //任务
                    if(user_is_paied($request['user_id'])==0){
                        $usermodel->task_complete($request['user_id'],'first_pay',$real_price);//首冲
                    }
                    $usermodel->auto_task_complete($request['user_id'],'pay_award',$real_price);//充值送积分
                    $usermodel->first_pay_every_day($request["user_id"],$real_price);//每日首充积分奖励
                    //发放邀请人充值代金券
                    if($user_entity['invitation_id'] >0){
                        $money = $real_price+$user_entity['cumulative'];
                        $logic = new InvitationLogic();
                        $logic->send_spend_coupon($user_entity['invitation_id'],$user_entity['id'],$money);
                    }
                    //更新VIP等级和充值总金额
                    set_vip_level($request["user_id"], $real_price,$user_entity['cumulative']);
                    if($request['user_id']!=$request['small_id']&&$request['small_id']!=''){//更新小号累计充值
                        $small_entity = get_user_entity($request['small_id'],false,'id,account,nickname,promote_id,promote_account,balance,cumulative,parent_id,parent_name');
                        set_vip_level($request["small_id"], $real_price,$small_entity['cumulative']);
                    }
                    #TODO 添加绑定平台币消费记录
                    if ($user_entity['promote_id']) {
                        $promote = get_promote_entity($user_entity['promote_id'],'pattern');
                        if ($promote['pattern'] == 0) {
                            $request['is_check'] = 1;
                        }
                    }
                    $request['cost'] = $request['price'];
                    $request['pay_amount'] = $real_price;
                    $spendid = add_spend($request,$user_entity);
                    $spend = Db::table('tab_spend')->where('id', $spendid)->find();
                    if ($user_entity['promote_id']) {
                        if ($promote['pattern'] == 0) {
                             set_promote_radio($spend,$user_entity);
                        }
                    }
                    //代金券使用
                    if($coupon_id){
                        $coupon_data['status'] = 1;
                        $coupon_data['cost'] = $request['cost'];
                        $coupon_data['update_time'] = time();
                        $coupon_data['pay_amount'] = $real_price;
                        Db::table('tab_coupon_record')->where('id',$coupon_id)->update($coupon_data);
                    }
                    //异常预警提醒
                    if($request['cost'] >= 2000){
                        $warning = [
                                'type'=>3,
                                'user_id'=>$request['user_id'],
                                'user_account'=>$user_entity['account'],
                                'pay_order_number'=>$request['pay_order_number'],
                                'target'=>3,
                                'record_id'=>$spendid,
                                'pay_amount'=>$request['price'],
                                'create_time'=>time()
                        ];
                        Db::table('tab_warning')->insert($warning);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return redirect(url('pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('支付失败'))));

                }
                $type = 0;

                // 用户阶段更改
                try{

                    $systemSet = cmf_get_option('admin_set');
                    if (empty($systemSet['task_mode'])) {
                        (new \app\common\task\HandleUserStageTask()) -> changeUserStage1(['user_id' => $request['user_id']]);
                    } else {
                        (new \app\common\task\HandleUserStageTask()) -> saveOperation('', $request['user_id'], 0);
                    }

                }catch(\Exception $e){

                }
                break;
            case 2://绑币充值
                $real_price = $request['price'];
                unset($coupon_id);
                //去除代金券金额
//                if($coupon_id){
//                    $coupon_money = $paylogic->get_use_coupon($request['user_id'],$request['price'],$coupon_id);
//                    if($coupon_money){
//                        $real_price = $real_price - $coupon_money;
//                    }else{
//                        return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('优惠券使用失败'))));
//                    }
//                }
                $user_play = get_user_play($request['user_id'],$request['game_id']);
                if ($user_play['bind_balance'] < $real_price) {
                    return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('余额不足'))));
                }
                $request['pay_way'] = 1;
                Db::startTrans();
                try {
                    #扣除绑币
                    $userplaymodel = new UserPlayModel();
                    $userplaymodel->where("id", $user_play["id"])->setDec("bind_balance", $real_price);
                    //任务
                    if(user_is_paied($request['user_id'])==0){
                        $usermodel->task_complete($request['user_id'],'first_pay',$real_price);//首冲
                    }
                    $usermodel->auto_task_complete($request['user_id'],'pay_award',$real_price);//充值送积分
                    $usermodel->first_pay_every_day($request["user_id"],$real_price);//每日首充积分奖励
                    //发放邀请人充值代金券
                    if($user_entity['invitation_id'] >0){
                        $money = $real_price+$user_entity['cumulative'];
                        $logic = new InvitationLogic();
                        $logic->send_spend_coupon($user_entity['invitation_id'],$user_entity['id'],$money);
                    }
                    //更新VIP等级和充值总金额
                    set_vip_level($request["user_id"], $real_price,$user_entity['cumulative']);
                    if($request['user_id']!=$request['small_id']&&$request['small_id']!=''){//更新小号累计充值
                        $small_entity = get_user_entity($request['small_id'],false,'id,account,nickname,promote_id,promote_account,balance,cumulative,parent_id,parent_name');
                        set_vip_level($request["small_id"], $real_price,$small_entity['cumulative']);
                    }
                    #TODO 添加绑定平台币消费记录
                    if ($user_entity['promote_id']) {
                        $promote = get_promote_entity($user_entity['promote_id'],'pattern');
                        if ($promote['pattern'] == 0) {
                            $request['is_check'] = 1;
                        }
                    }
                    $request['cost'] = $request['price'];
                    $request['pay_amount'] = $real_price;
                    $spendid = add_spend($request,$user_entity);
                    $spend = Db::table('tab_spend')->where('id', $spendid)->find();
                    if ($user_entity['promote_id']) {
                        if ($promote['pattern'] == 0) {
                            set_promote_radio($spend,$user_entity);
                        }
                    }
                    //代金券使用
//                    if($coupon_id){
//                        $coupon_data['status'] = 1;
//                        $coupon_data['cost'] = $request['cost'];
//                        $coupon_data['update_time'] = time();
//                        $coupon_data['pay_amount'] = $real_price;
//                        Db::table('tab_coupon_record')->where('id',$coupon_id)->update($coupon_data);
//                    }
                    //异常预警提醒
                    if($request['cost'] >= 2000){
                        $warning = [
                                'type'=>3,
                                'user_id'=>$request['user_id'],
                                'user_account'=>$user_entity['account'],
                                'pay_order_number'=>$request['pay_order_number'],
                                'target'=>3,
                                'record_id'=>$spendid,
                                'pay_amount'=>$request['price'],
                                'create_time'=>time()
                        ];
                        Db::table('tab_warning')->insert($warning);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return redirect(url('Pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('支付失败'))));
                }
                $type = 1;

                // 用户阶段更改
                try{

                    $systemSet = cmf_get_option('admin_set');
                    if (empty($systemSet['task_mode'])) {
                        (new \app\common\task\HandleUserStageTask()) -> changeUserStage1(['user_id' => $request['user_id']]);
                    } else {
                        (new \app\common\task\HandleUserStageTask()) -> saveOperation('', $request['user_id'], 0);
                    }

                }catch(\Exception $e){

                }
                break;
            default:
                return redirect(url('pay/notice', array('user_id' => $user_id, 'game_id' => $game_id, 'msg' => urlencode('支付方式不明确'))));
                break;
        }
        $param['out_trade_no'] = $request['pay_order_number'];
        $game = new GameApi();
        $game->game_pay_notify($param);
        $paylogic->set_ratio($spend,$user_entity,$type);
        $url = cmf_get_domain() . "/sdk/Pay/pay_success/orderno/" . $request['pay_order_number'];
        return redirect($url);
    }

    /**
     * [安卓平台币支付]
     * @author 郭家屯[gjt]
     */
    public function platform_coin_pay()
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $game_data = Cache::get('sdk_game_data'.$request['game_id']);
        $request['game_name'] = $game_data['game_name'];
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$request['game_id'],$request['user_id'],$request['equipment_num'],get_client_ip(),$type=3)){
            $this -> set_message(1061, "当前被禁止充值，请联系客服");
        }
        //实名充值
        $user_entity = get_user_entity($request['user_id'],false,'id,account,nickname,promote_id,promote_account,balance,cumulative,parent_id,parent_name,age_status,invitation_id');
        if(empty($user_entity['age_status']) && get_user_config_info('age')['real_pay_status']==1){
            $this->set_message(1075, get_user_config_info('age')['real_pay_msg']?:'根据国家关于《网络游戏管理暂行办法》要求，平台所有玩家必须完成实名认证后才可以进行游戏充值！');
        }
        $usermodel = new UserModel();
        //判断是否是所属小号
        $isusersmall = $usermodel->is_user_small($request['user_id'],$request['small_id']);
        if(!$isusersmall){
            $this -> set_message(1080, "小号不属于该账户");
        }else{
            $request['small_nickname'] = get_user_entity($request['small_id'],false,'nickname')['nickname'];
        }
        if ($request['price'] * 1 <= 0) {
            $this->set_message(1061, "充值金额有误");
        }
        $model = new SpendModel();
        $usermodel = new UserModel();
        $extend_data = $model->field('id')->where(array('extend' => $request['extend'], 'game_id' => $request['game_id'], 'pay_status' => 1))->find();
        if ($extend_data) {
            $this->set_message(1055, "订单号重复，请关闭支付页面重新支付");
        }
        $out_trade_no = create_out_trade_no('SP_');
        $request['pay_order_number'] = $out_trade_no;
        $request['out_trade_no'] = $out_trade_no;
        $request['pay_status'] = 1;
        $request['pay_way'] = 2;
        $paylogic = new PayLogic();
        switch ($request['code']) {
            case 1:#平台币
                //计算折扣
                $promote_id = $user_entity['promote_id'];
                //去除代金券金额
                if($request['coupon_id']){
                    $coupon_money = $paylogic->get_use_coupon($request['user_id'],$request['price'],$request['coupon_id']);
                    if($coupon_money){
                        $pay_amount  = $request['price'] - $coupon_money;
                    }else{
                        $this->set_message(1083, "优惠券使用失败");
                    }
                }else{
                    $pay_amount = $request['price'];
                }


                //检查未成年用户是否满足充值条件
                if (get_user_config_info('age')['real_pay_status'] == 1) {
                    $lCheckAge = new CheckAgeLogic();
                    $checkAgeRes = $lCheckAge -> run($request['user_id'], $pay_amount);
                    if (false === $checkAgeRes) {
                        $this -> set_message(1084, $lCheckAge -> getErrorMsg());
                    }
                }

                $discount_info = $paylogic->get_discount($request['game_id'],$promote_id,$request['user_id']);
                $real_price = round($discount_info['discount']*$pay_amount,2);
                $request['discount_type'] = $discount_info['discount_type'];
                $request['discount'] = $discount_info['discount'];
                if($request['coupon_id'] && $real_price <= 0){
                    $result = $this->coupon_pay($request,$user_entity);
                    if($result){
                        $this->set_message(200, "支付成功",["out_trade_no" => $out_trade_no]);
                    }else{
                        $this->set_message(1058, '支付失败');
                    }
                }
                if ($user_entity['balance'] < $real_price) {
                    $this->set_message(1062, '余额不足');
                }
                Db::startTrans();
                try {
                    #扣除平台币
                    $usermodel->where("id", $request["user_id"])->setDec("balance", $real_price);
                    //任务
                    if(user_is_paied($request['user_id'])==0){
                        $usermodel->task_complete($request['user_id'],'first_pay',$real_price);//首冲
                    }
                    $usermodel->auto_task_complete($request['user_id'],'pay_award',$real_price);//充值送积分
                    $usermodel->first_pay_every_day($request["user_id"],$real_price);//每日首充积分奖励
                    //发放邀请人充值代金券
                    if($user_entity['invitation_id'] >0){
                        $money = $real_price+$user_entity['cumulative'];
                        $logic = new InvitationLogic();
                        $logic->send_spend_coupon($user_entity['invitation_id'],$user_entity['id'],$money);
                    }
                    //更新VIP等级和充值总金额
                    set_vip_level($request["user_id"], $real_price,$user_entity['cumulative']);
                    if($request['user_id']!=$request['small_id']&&$request['small_id']!=''){//更新小号累计充值
                        $small_entity = get_user_entity($request['small_id'],false,'id,account,nickname,promote_id,promote_account,balance,cumulative,parent_id,parent_name');
                        set_vip_level($request["small_id"], $real_price,$small_entity['cumulative']);
                    }
                    #TODO 添加绑定平台币消费记录
                    if ($user_entity['promote_id']) {
                        $promote = get_promote_entity($user_entity['promote_id'],'pattern');
                        if ($promote['pattern'] == 0) {
                            $request['is_check'] = 1;
                        }
                    }
                    $request['cost'] = $request['price'];
                    $request['pay_amount'] = $real_price;
                    $spendid = add_spend($request,$user_entity);
                    $spend = Db::table('tab_spend')->where('id', $spendid)->find();
                    if ($user_entity['promote_id']) {
                        if ($promote['pattern'] == 0) {
                            set_promote_radio($spend,$user_entity);
                        }
                    }
                    //代金券使用
                    if($request['coupon_id']){
                        $coupon_data['status'] = 1;
                        $coupon_data['cost'] = $request['cost'];
                        $coupon_data['update_time'] = time();
                        $coupon_data['pay_amount'] = $real_price;
                        Db::table('tab_coupon_record')->where('id',$request['coupon_id'])->update($coupon_data);
                    }
                    //异常预警提醒
                    if($request['cost'] >= 2000){
                        $warning = [
                                'type'=>3,
                                'user_id'=>$request['user_id'],
                                'user_account'=>$user_entity['account'],
                                'pay_order_number'=>$request['pay_order_number'],
                                'target'=>3,
                                'record_id'=>$spendid,
                                'pay_amount'=>$request['price'],
                                'create_time'=>time()
                        ];
                        Db::table('tab_warning')->insert($warning);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->set_message(1058, '支付失败');
                }
                $type = 0;

                // 用户阶段更改
                try{

                    $systemSet = cmf_get_option('admin_set');
                    if (empty($systemSet['task_mode'])) {
                        (new \app\common\task\HandleUserStageTask()) -> changeUserStage1(['user_id' => $request['user_id']]);
                    } else {
                        (new \app\common\task\HandleUserStageTask()) -> saveOperation('', $request['user_id'], 0);
                    }

                }catch(\Exception $e){

                }
                break;
            case 2:#绑币
                $real_price = $request['price'];
                unset($request['coupon_id']);
//                if($request['coupon_id']){
//                    $coupon_money = $paylogic->get_use_coupon($request['user_id'],$request['price'],$request['coupon_id']);
//                    if($coupon_money){
//                        $real_price = $real_price - $coupon_money;
//                    }else{
//                        $this->set_message(1083, "优惠券使用失败");
//                    }
//                }

                //检查未成年用户是否满足充值条件
                if (get_user_config_info('age')['real_pay_status'] == 1) {
                    $lCheckAge = new CheckAgeLogic();
                    $checkAgeRes = $lCheckAge -> run($request['user_id'], $real_price);
                    if (false === $checkAgeRes) {
                        $this -> set_message(1084, $lCheckAge -> getErrorMsg());
                    }
                }

                $user_play = get_user_play($request['user_id'],$request['game_id']);
                if ($user_play['bind_balance'] < $real_price) {
                    $this->set_message(1062, '余额不足');
                }
                $request['pay_way'] = 1;
                Db::startTrans();
                try {
                    #扣除绑币
                    $userplaymodel = new UserPlayModel();
                    $userplaymodel->where("id", $user_play["id"])->setDec("bind_balance", $real_price);
                    //任务
                    if(user_is_paied($request['user_id'])==0){
                        $usermodel->task_complete($request['user_id'],'first_pay',$real_price);//首冲
                    }
                    $usermodel->auto_task_complete($request['user_id'],'pay_award',$real_price);//充值送积分
                    $usermodel->first_pay_every_day($request["user_id"],$real_price);//每日首充积分奖励
                    //发放邀请人充值代金券
                    if($user_entity['invitation_id'] >0){
                        $money = $real_price+$user_entity['cumulative'];
                        $logic = new InvitationLogic();
                        $logic->send_spend_coupon($user_entity['invitation_id'],$user_entity['id'],$money);
                    }
                    //更新VIP等级和充值总金额
                    set_vip_level($request["user_id"], $real_price,$user_entity['cumulative']);
                    if($request['user_id']!=$request['small_id']&&$request['small_id']!=''){//更新小号累计充值
                        $small_entity = get_user_entity($request['small_id'],false,'id,account,nickname,promote_id,promote_account,balance,cumulative,parent_id,parent_name');
                        set_vip_level($request["small_id"], $real_price,$small_entity['cumulative']);
                    }
                    #TODO 添加绑定平台币消费记录
                    if ($user_entity['promote_id']) {
                        $promote = get_promote_entity($user_entity['promote_id'],'pattern');
                        if ($promote['pattern'] == 0) {
                            $request['is_check'] = 1;
                        }
                    }
                    $request['cost'] = $request['price'];
                    $request['pay_amount'] = $real_price;
                    $spendid = add_spend($request,$user_entity);
                    $spend = Db::table('tab_spend')->where('id', $spendid)->find();
                    if ($user_entity['promote_id']) {
                        if ($promote['pattern'] == 0) {
                            set_promote_radio($spend,$user_entity);
                        }
                    }
                    //代金券使用
//                    if($request['coupon_id']){
//                        $coupon_data['status'] = 1;
//                        $coupon_data['cost'] = $request['cost'];
//                        $coupon_data['update_time'] = time();
//                        $coupon_data['pay_amount'] = $real_price;
//                        Db::table('tab_coupon_record')->where('id',$request['coupon_id'])->update($coupon_data);
//                    }
                    //异常预警提醒
                    if($request['cost'] >= 2000){
                        $warning = [
                                'type'=>3,
                                'user_id'=>$request['user_id'],
                                'user_account'=>$user_entity['account'],
                                'pay_order_number'=>$request['pay_order_number'],
                                'target'=>3,
                                'record_id'=>$spendid,
                                'pay_amount'=>$request['price'],
                                'create_time'=>time()
                        ];
                        Db::table('tab_warning')->insert($warning);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->set_message(1058, '支付失败');
                }
                $type = 1;

                // 用户阶段更改
                try{

                    $systemSet = cmf_get_option('admin_set');
                    if (empty($systemSet['task_mode'])) {
                        (new \app\common\task\HandleUserStageTask()) -> changeUserStage1(['user_id' => $request['user_id']]);
                    } else {
                        (new \app\common\task\HandleUserStageTask()) -> saveOperation('', $request['user_id'], 0);
                    }

                }catch(\Exception $e){

                }
                break;
            default:
                $this->set_message(1063, '支付方式不明确');
                break;
        }
        $param['out_trade_no'] = $request['pay_order_number'];
        $game = new GameApi();
        $game->game_pay_notify($param);
        $paylogic->set_ratio($spend,$user_entity,$type);
        $this->set_message(200, "支付成功",["out_trade_no" => $out_trade_no]);
    }

    /**
     * [苹果支付成功通知]
     * @author 郭家屯[gjt]
     */
    public function pay_success2()
    {
        $orderno = $this->request->param('orderno');
        $out_trade_no = $this->request->param('out_trade_no');
        $orderno = empty($orderno) ? $out_trade_no : $orderno;
        $pay_where = substr($orderno, 0, 2);
        $Scheme = file_get_contents(ROOT_PATH . "./app/sdk/scheme/" . $this->request->param('game_id') . ".txt");
        $map['pay_order_number'] = $orderno;
        switch ($pay_where) {
            case 'SP':
                $result = Db::table('tab_spend')->field("pay_status,pay_way")->where($map)->find();
                break;
            case 'PF':
                $result = Db::table('tab_spend_balance')->field('pay_status,pay_way')->where($map)->find();
                break;
            case 'PB':
                $result = Db::table('tab_spend_bind')->field('pay_status,pay_way')->where($map)->find();
                break;
        }
        $this->assign('paystatus', $result['pay_status']);
        $this->assign('pay_way',$result['pay_way']);
        $this->assign('Scheme', $Scheme);
        return $this->fetch();
    }

    /**
     * [支付成功提示]
     * @author 郭家屯[gjt]
     */
    public function pay_success()
    {
        $orderno = $this->request->param('orderno');
        $out_trade_no = $this->request->param('out_trade_no');
        $orderno = empty($orderno) ? $out_trade_no : $orderno;
        $map['pay_order_number'] = $orderno;
        $pay_where = substr($orderno, 0, 2);
        switch ($pay_where) {
            case 'SP':
                $result = Db::table('tab_spend')->field("pay_status,pay_way")->where($map)->find();
                break;
            case 'MC'://尊享卡
                $result = Db::table('tab_user_member')->field("pay_status,pay_way")->where($map)->find();
                break;
            case 'PF'://平台币
                $result = Db::table('tab_spend_balance')->field('pay_status,pay_way')->where($map)->find();
                break;
            case 'PB'://绑币
                $result = Db::table('tab_spend_bind')->field('pay_status,pay_way')->where($map)->find();
                break;
        }
        $this->assign('paystatus', $result['pay_status']);
        $this->assign('payway',$result['pay_way']);
        return $this->fetch('pay_success');
    }

    /**
     * [通知中心]
     * @param int $user_id
     * @param int $game_id
     * @param string $msg
     * @author 郭家屯[gjt]
     */
    public function notice($user_id = 0, $game_id = 0, $msg = '')
    {
        return $this->fetch();
    }

    /**
     * [安卓获取支付方式]
     * @author 郭家屯[gjt]
     */
    public function get_pay_server()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $weixin = pay_type_status('wxscan');
        $alipay = pay_type_status('zfb');
        if ($weixin == 1 ) {
            $data['wx_game'] = 1;
        } else {
            $data['wx_game'] = 0;
        }
        if ($alipay == 1) {
            $data['zfb_game'] = 1;
        } else {
            $data['zfb_game'] = 0;
        }
        //1 app支付  2 wap支付
        $alipay_set = get_pay_type_set('zfb');
        if ($alipay == 1) {
            $data['zfb_type'] = $alipay_set['config']['type'];
        } else {
            $data['zfb_type'] = 0;
        }

        //检查用户是否属于自定义支付渠道
        $isCustom = check_user_is_custom_pay_channel($request['user_id']);
        if ($isCustom) {
            $data['ptb_game'] = 0;
            $data['bind_game'] = 0;
        }else{

            //平台币开关
            $ptb_set = pay_type_status('ptb_pay');
            if ($ptb_set == 1) {
                $data['ptb_game'] = 1;
            } else {
                $data['ptb_game'] = 0;
            }
            //绑币开关
            $bind_set = pay_type_status('bind_pay');
            if ($bind_set == 1) {
                $data['bind_game'] = 1;
            } else {
                $data['bind_game'] = 0;
            }

        }
        //SDK扫码支付开关
        if($weixin==0 && $alipay==0){
            $data['scan_pay'] = 0;
        }else{
            $data['scan_pay'] = 1;
        }

        $this->set_message(200, "获取成功",$data);
    }

    /**
     *微信支付
     */
    public function and_weixin_pay()
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $game_data = Cache::get('sdk_game_data'.$request['game_id']);
        $request['game_name'] = $game_data['game_name'];
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$request['game_id'],$request['user_id'],$request['equipment_num'],get_client_ip(),$type=3)){
            $this -> set_message(1061, "当前被禁止充值，请联系客服");
        }
        //实名充值
        $user_entity = get_user_entity($request['user_id'],false,'account,nickname,age_status');
        if(empty($user_entity['age_status']) && get_user_config_info('age')['real_pay_status']==1){
            $this->set_message(1075, get_user_config_info('age')['real_pay_msg']?:'根据国家关于《网络游戏管理暂行办法》要求，平台所有玩家必须完成实名认证后才可以进行游戏充值！');
        }
        $usermodel = new UserModel();
        //判断是否是所属小号
        $isusersmall = $usermodel->is_user_small($request['user_id'],$request['small_id']);
        if(!$isusersmall){
            $this -> set_message(1080, "小号不属于该账户");
        }else{
            $request['small_nickname'] = get_user_entity($request['small_id'],false,'nickname')['nickname'];
        }
        if ($request['price'] * 1 <= 0) {
            $this->set_message(1061, "充值金额有误");
        }
        if ($request['code'] == 1) {
            $spendmodel = new SpendModel();
            $extend_data = $spendmodel->field('id')->where(array('extend' => $request['extend'], 'game_id' => $request['game_id'], 'pay_status' => 1))->find();
            if ($extend_data) {
                $this->set_message(1055, "订单号重复，请关闭支付页面重新支付");
            }
        }else{
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($request['user_id']);
            if ($isCustom) {
                $this->set_message(1055, "该账号暂不支持平台币/绑币，请在游戏中支付");
            }
        }
        $prefix = $request['code'] == 1 ? "SP_" : "PF_";
        $request['pay_order_number'] = create_out_trade_no($prefix);
        $request['pay_way'] = 4;
        $this->weixin_pay($request,1);
    }

    /**
     * 安卓查询订单状态
     * @return [type] [description]
     */
    public function get_orderno_restart()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $pay_where = substr($request['orderno'], 0, 2);
        $map['pay_order_number'] = $request['orderno'];
        switch ($pay_where) {
            case 'SP':
                $result = Db::table('tab_spend')->field("pay_status")->where($map)->find();
                break;
            case 'PF':
                $result = Db::table('tab_spend_balance')->field('pay_status')->where($map)->find();
                break;
        }
        if ($result['pay_status'] == 1) {
            $this->set_message(200, '支付成功');
        } else {
            $this->set_message(1058, '支付失败');
        }
    }

    /**
     * [安卓支付宝移动支付]
     * @author 郭家屯[gjt]
     */
    public function and_alipay_pay()
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        if ($request['price'] < 0) {
            $this->set_message(1061, "充值金额有误");
        }
        $game_data = Cache::get('sdk_game_data'.$request['game_id']);
        $request['game_name'] = $game_data['game_name'];
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$request['game_id'],$request['user_id'],$request['equipment_num'],get_client_ip(),$type=3)){
            $this -> set_message(1061, "当前被禁止充值，请联系客服");
        }
        //实名充值
        $user_entity = get_user_entity($request['user_id'],false,'account,nickname,age_status');
        if(empty($user_entity['age_status']) && get_user_config_info('age')['real_pay_status']==1){
            $this->set_message(1075, get_user_config_info('age')['real_pay_msg']?:'根据国家关于《网络游戏管理暂行办法》要求，平台所有玩家必须完成实名认证后才可以进行游戏充值！');
        }
        $usermodel = new UserModel();
        //判断是否是所属小号
        $isusersmall = $usermodel->is_user_small($request['user_id'],$request['small_id']);
        if(!$isusersmall){
            $this -> set_message(1080, "小号不属于该账户");
        }else{
            $request['small_nickname'] = get_user_entity($request['small_id'],false,'nickname')['nickname'];
        }
        if ($request['code'] == 1) {
            $spendmodel = new SpendModel();
            $extend_data = $spendmodel->field('id')->where(array('extend' => $request['extend'], 'game_id' => $request['game_id'], 'pay_status' => 1))->find();
            if ($extend_data) {
                $this->set_message(1055, "订单号重复，请关闭支付页面重新支付");
            }
        }
        $request['pay_way'] = 3;
        $prefix = $request['code'] == 1 ? "SP_" : "PF_";
        $request['pay_order_number'] = create_out_trade_no($prefix);
        $game_set_data = Db::table('tab_game_set')->where('game_id', $request['game_id'])->field('access_key')->find();
        $request['table'] = $request['code'] == 1 ? "spend" : "deposit";
        $user = get_user_entity($request['user_id'],false,'account,nickname,promote_id,promote_account,parent_id');
        $discount = 0;
        if($request['code'] == 1){
            $promote_id = $user['promote_id'];
            $paylogic = new PayLogic();
            //去除代金券金额
            if($request['coupon_id']){
                $coupon_money = $paylogic->get_use_coupon($request['user_id'],$request['price'],$request['coupon_id']);
                if($coupon_money){
                    $new_price = $request['price'] - $coupon_money;
                }else{
                    return redirect(url('Pay/notice', array('user_id' => $request['user_id'], 'game_id' => $request['game_id'], 'msg' => urlencode('优惠券使用失败'))));
                    exit;
                }
            }else{
                $new_price = $request['price'];
            }
            $discount_info = $paylogic->get_discount($request['game_id'],$promote_id,$request['user_id']);
            $request['pay_amount'] = round($discount_info['discount']*$new_price,2);
            $request['discount_type'] = $discount_info['discount_type'];
            $request['discount'] = $discount_info['discount'];
        }else{
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($request['user_id']);
            if ($isCustom) {
                $this -> set_message(1058, "该账号暂不支持平台币/绑币，请在游戏中支付");
            }
            $request['pay_amount'] = $request['price'];
        }

        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($request['user_id'], $request['pay_amount']);
            if (false === $checkAgeRes) {
                $this -> set_message(1059, $lCheckAge -> getErrorMsg());
            }
        }

        if($request['coupon_id'] > 0 && $new_price <= 0){
            $result = $this->coupon_pay($request,$user);
            if($result){
                $res_msg = array(
                        "url" =>cmf_get_domain() . "/sdk/Pay/pay_success/orderno/" . $request['pay_order_number'],
                        "wap" => 1,
                        "out_trade_no" => $request['pay_order_number']
                );
                $this->set_message(9999, "支付成功",$res_msg);
            }else{
                $this->set_message(1058, "支付失败");
            }
        }elseif (pay_type_status('zfb') == 1) {
            $config = get_pay_type_set('zfb');
            if ($config['config']['type'] == 2) {/*支付宝 wap支付 */
                $request['apitype'] = "alipay";
                $request['config'] = "alipay";
                $request['signtype'] = "MD5";
                $request['server'] = "alipay.wap.create.direct.pay.by.user";
                $request['payway'] = 3;
                $pay_url = $this->pay($request,$user);
                $res_msg = array(
                    "url" => $pay_url['url'],
                    "wap" => 1,
                    "out_trade_no" => $request['pay_order_number']
                );
                $this->set_message(200, "获取成功",$res_msg);
            } else {/* 支付宝app支付 */
                $request['apitype'] = "alipay";
                $request['config'] = "alipay";
                $request['signtype'] = "MD5";
                $request['server'] = "mobile.securitypay.pay";
                $request['payway'] = 3;
                $data = $this->alipay_app_pay($request,$user);
                $md5_sign = $this->encrypt_md5(base64_encode($data['arg']), $game_set_data["access_key"]);
                $result = [
                    "orderInfo" => base64_encode($data['arg']),
                    "out_trade_no" => $data['out_trade_no'],
                    "order_sign" => $data['sign'],
                    "md5_sign" => $md5_sign,
                    'wap'=>0
                ];
                $this->set_message(200, "获取成功",$result);
            }
        }
        $this->set_message(1058, "支付失败");
    }

    /**
     * [返回平台币余额]
     * @author 郭家屯[gjt]
     */
    public function user_platform_coin()
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $user = get_user_entity($request['user_id'],false,'balance');
        $user_play = get_user_play($request['user_id'],$request['game_id']);
        $user['bind_balance'] = $user_play['bind_balance'];
        $this->set_message(200, "获取成功",$user);
    }

    /**
     * @函数或方法说明
     * @代金券完全支付
     * @param $param
     * @param $user
     *
     * @author: 郭家屯
     * @since: 2020/9/15 16:44
     */
    public function coupon_pay($request,$user)
    {
        $request['cost'] = $request['price'];
        $request['pay_amount'] = 0;
        $request['pay_status'] = 1;
        $result = add_spend($request,$user);
        if($result){
            //任务
            $usermodel = new UserModel();
            if(user_is_paied($request['user_id'])==0){
                $usermodel->task_complete($request['user_id'],'first_pay',$request['cost']);//首冲
            }
            //代金券使用
            $coupon_data['status'] = 1;
            $coupon_data['cost'] = $request['cost'];
            $coupon_data['update_time'] = time();
            $coupon_data['pay_amount'] = 0;
            Db::table('tab_coupon_record')->where('id',$request['coupon_id'])->update($coupon_data);
            $request['out_trade_no'] = $request['pay_order_number'];
            $game = new GameApi();
            $game->game_pay_notify($request);
            return true;
        }else{
            return false;
        }

    }
}
