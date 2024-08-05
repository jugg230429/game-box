<?php

namespace app\sdksimplify\controller;

use app\api\GameApi;
use app\common\logic\PayLogic;
use app\member\model\UserModel;
use app\promote\logic\CustompayLogic;
use app\recharge\logic\CheckAgeLogic;
use app\recharge\model\SpendBalanceModel;
use app\recharge\model\SpendModel;
use cmf\controller\HomeBaseController;
use think\Cache;
use think\Db;
use think\weixinsdk\Weixin;

class ScanpayController extends HomeBaseController
{


    /**
     * @打开弹窗
     *
     * @author: zsl
     * @since: 2021/1/4 17:23`
     */
    public function order()
    {


        return $this -> fetch();
    }

    /**
     * @苹果支付宝扫码支付
     *
     * @author: zsl
     * @since: 2021/1/5 14:47
     */
    public function iosAlipay($user_id = '', $game_id = '')
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $file = file_get_contents(ROOT_PATH . "/app/sdk/orderno/" . $user_id . "-" . $game_id . ".txt");
        $request = json_decode(think_decrypt($file), true);
        $request['coupon_id'] = $this -> request -> param('coupon_id');
        if (empty($request)) {
            echo json_encode(['code' => 0, 'msg' => '登录数据不能为空']);
            exit;
        }
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$game_id,$user_id,$request['equipment_num'],get_client_ip(),$type=3)){
            echo json_encode(['code' => 0, 'msg' => '您当前被禁止充值，请联系客服']);
            exit;
        }
        if ($request['code'] != '1') {
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($request['user_id']);
            if ($isCustom) {
                return json(['code' => 0, 'msg' => '该账号暂不支持平台币/绑币，请在游戏中支付']);
            }
        }
        if ($request['price'] <= 0) {
            echo json_encode(['code' => 0, 'msg' => '充值金额有误']);
            exit;
        }
        $request['pay_way'] = 3;
        $request['table'] = $request['code'] == 1 ? "spend" : "deposit";
        $user = get_user_entity($request['user_id'], false, 'id,account,nickname,promote_id,promote_account,parent_id');
        $new_price = $request['price'];
        if ($request['code'] == 1) {
            $promote_id = $user['promote_id'];
            $paylogic = new PayLogic();
            //去除代金券金额
            if ($request['coupon_id']) {
                $coupon_money = $paylogic -> get_use_coupon($request['user_id'], $request['price'], $request['coupon_id']);
                if ($coupon_money) {
                    $new_price = $request['price'] - $coupon_money;
                } else {
                    return redirect(url('Pay/notice', array('user_id' => $request['user_id'], 'game_id' => $request['game_id'], 'msg' => urlencode('优惠券使用失败'))));
                    exit;
                }
            }
            $discount_info = $paylogic -> get_discount($request['game_id'], $promote_id, $request['user_id']);
            $request['pay_amount'] = round($discount_info['discount'] * $new_price, 2);
            $request['discount_type'] = $discount_info['discount_type'];
            $request['discount'] = $discount_info['discount'];
            $request['pay_order_number'] = create_out_trade_no("SP_");
        } else {
            $request['pay_amount'] = $request['price'];
            $request['pay_order_number'] = create_out_trade_no("PF_");

        }

        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($request['user_id'], $request['pay_amount']);
            if (false === $checkAgeRes) {
                return json(['code' => 0, 'msg' => $lCheckAge -> getErrorMsg()]);
            }
        }

        if ($request['coupon_id'] > 0 && $new_price <= 0) {

            return json(['code' => 0, 'msg' => '支付金额不满足扫码付款条件，请选择其他付款方式']);

//            $result = $this -> coupon_pay($request, $user);
//            if ($result) {
//                $url = cmf_get_domain() . "/sdk/Pay/pay_success/orderno/" . $request['pay_order_number'];
//                echo json_encode(['code' => 200, 'msg' => '支付成功', 'data' => ['url' => $url, 'wap' => 3]]);
//                exit;
//            } else {
//                echo json_encode(['code' => 0, 'msg' => '支付失败', 'data' => ['url' => 'coupon', 'wap' => 3]]);
//                exit;
//            }
        } elseif (pay_type_status('zfb') == 1) {
            $request['apitype'] = "alipay";
            $request['config'] = "alipay";
            $request['signtype'] = "RSA2";
            $request['server'] = "create_direct_pay_by_user";
            $request['method'] = "f2fscan";
            $request['payway'] = 3;
            $request['title'] = $request['price'];
            $request['body'] = $request['price'];
            $url = $this -> pay($request, $user);
            $returnData = [];
            $returnData['code'] = 200;
            $returnData['amount'] = $url['fee'];
            $returnData['out_trade_no'] = $url['order_no'];
            $returnData['qrcode_url'] = url('sdk/Scanpay/qrcode', ['level' => 3, 'size' => 4, 'url' => base64_encode(base64_encode($url['payurl']))], true, true);
            return json($returnData);
        }

    }


    /**
     * @苹果微信扫码支付
     *
     * @author: zsl
     * @since: 2021/1/5 14:47
     */
    public function iosWechat($user_id = '', $game_id = '')
    {
        $file = file_get_contents(ROOT_PATH . "/app/sdk/orderno/" . $user_id . "-" . $game_id . ".txt");
        $request = json_decode(think_decrypt($file), true);
        $request['coupon_id'] = $this -> request -> param('coupon_id');
        if (empty($request)) {
            echo json_encode(['code' => 0, 'msg' => '登录数据不能为空']);
            exit;
        }
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$game_id,$user_id,$request['equipment_num'],get_client_ip(),$type=3)){
            echo json_encode(['code' => 0, 'msg' => '您当前被禁止充值，请联系客服']);
            exit;
        }
        if ($request['price'] <= 0) {
            echo json_encode(['code' => 0, 'msg' => '充值金额有误']);
            exit;
        }

        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($user_id, $request['price']);
            if (false === $checkAgeRes) {
                return json(['code' => 0, 'msg' => $lCheckAge -> getErrorMsg()]);
            }
        }

        if ($request['code'] == 1) {
            $request['pay_order_number'] = create_out_trade_no("SP_");
        } else {
            $request['pay_order_number'] = create_out_trade_no("PF_");
        }
        $request['pay_way'] = 4;
        return $this -> weixin_pay($request);
    }


    public function adrAlipay()
    {

        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$request['game_id'],$request['user_id'],$request['equipment_num'],get_client_ip(),$type=3)){
            $this -> set_message(1061, "您当前被禁止充值，请联系客服");
        }
        if ($request['price'] < 0) {
            $this -> set_message(1061, "充值金额有误");
        }
        if ($request['code'] != '1') {
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($request['user_id']);
            if ($isCustom) {
                $this -> set_message(1061, "该账号暂不支持平台币/绑币，请在游戏中支付");
            }
        }
        $game_data = Cache ::get('sdk_game_data' . $request['game_id']);
        $request['game_name'] = $game_data['game_name'];
        //实名充值
        $user_entity = get_user_entity($request['user_id'], false, 'account,nickname,age_status');
        if (empty($user_entity['age_status']) && get_user_config_info('age')['real_pay_status'] == 1) {
            $this -> set_message(1075, get_user_config_info('age')['real_pay_msg'] ?: '根据国家关于《网络游戏管理暂行办法》要求，平台所有玩家必须完成实名认证后才可以进行游戏充值！');
        }
        $usermodel = new UserModel();
        //判断是否是所属小号
        $isusersmall = $usermodel -> is_user_small($request['user_id'], $request['small_id']);
        if (!$isusersmall) {
            $this -> set_message(1080, "小号不属于该账户");
        } else {
            $request['small_nickname'] = get_user_entity($request['small_id'], false, 'nickname')['nickname'];
        }
        if ($request['code'] == 1) {
            $spendmodel = new SpendModel();
            $extend_data = $spendmodel -> field('id') -> where(array('extend' => $request['extend'], 'game_id' => $request['game_id'], 'pay_status' => 1)) -> find();
            if ($extend_data) {
                $this -> set_message(1055, "订单号重复，请关闭支付页面重新支付");
            }
        }
        $request['pay_way'] = 3;
        $request['payway'] = 3;
        $prefix = $request['code'] == 1 ? "SP_" : "PF_";
        $request['pay_order_number'] = create_out_trade_no($prefix);
        $game_set_data = Db ::table('tab_game_set') -> where('game_id', $request['game_id']) -> field('access_key') -> find();
        $request['table'] = $request['code'] == 1 ? "spend" : "deposit";
        $user = get_user_entity($request['user_id'], false, 'account,nickname,promote_id,promote_account,parent_id');
        $discount = 0;
        if ($request['code'] == 1) {
            $promote_id = $user['promote_id'];
            $paylogic = new PayLogic();
            //去除代金券金额
            if ($request['coupon_id']) {
                $coupon_money = $paylogic -> get_use_coupon($request['user_id'], $request['price'], $request['coupon_id']);
                if ($coupon_money) {
                    $new_price = $request['price'] - $coupon_money;
                } else {
                    return redirect(url('Pay/notice', array('user_id' => $request['user_id'], 'game_id' => $request['game_id'], 'msg' => urlencode('优惠券使用失败'))));
                    exit;
                }
            } else {
                $new_price = $request['price'];
            }
            $discount_info = $paylogic -> get_discount($request['game_id'], $promote_id, $request['user_id']);
            $request['pay_amount'] = round($discount_info['discount'] * $new_price, 2);
            $request['discount_type'] = $discount_info['discount_type'];
            $request['discount'] = $discount_info['discount'];
        } else {
            $request['pay_amount'] = $request['price'];
        }

        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($request['user_id'], $request['pay_amount']);
            if (false === $checkAgeRes) {
                $this -> set_message(1056, $lCheckAge -> getErrorMsg());
            }
        }

        if ($request['coupon_id'] > 0 && $new_price <= 0) {

            $this -> set_message(1058, "支付金额不满足扫码付款条件，请选择其他付款方式");

//            $result = $this -> coupon_pay($request, $user);
//            if ($result) {
//                $res_msg = array(
//                        "url" => cmf_get_domain() . "/sdk/Pay/pay_success/orderno/" . $request['pay_order_number'],
//                        "wap" => 1,
//                        "out_trade_no" => $request['pay_order_number']
//                );
//                $this -> set_message(9999, "支付成功", $res_msg);
//            } else {
//                $this -> set_message(1058, "支付失败");
//            }
        } elseif (pay_type_status('zfb') == 1) {

            $request['apitype'] = "alipay";
            $request['signtype'] = "RSA2";
            $request['server'] = "create_direct_pay_by_user";
            $request['method'] = "f2fscan";
            $request['title'] = $request['price'];
            $request['body'] = $request['price'];
            $request['config'] = "alipay";
            $pay_url = $this -> pay($request, $user);
            $res_msg = array(
                    "qrcode_url" =>url('sdk/Scanpay/qrcode', ['level' => 3, 'size' => 4, 'url' => base64_encode(base64_encode($pay_url['payurl']))], true, true),
                    "orderno" => $request['pay_order_number'],
                    "code" => 200,
                    "cal_url" => cmf_get_domain(),
                    "pay_type" => 'zfb',
            );
            $this -> set_message(200, "获取成功", $res_msg);

        }
        $this -> set_message(1058, "支付失败");

    }

    public function adrWechat()
    {

        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$request['game_id'],$request['user_id'],$request['equipment_num'],get_client_ip(),$type=3)){
            $this -> set_message(1061, "当前被禁止充值，请联系客服");
        }
        if ($request['code'] != '1') {
            //检查用户是否属于自定义支付渠道
            $isCustom = check_user_is_custom_pay_channel($request['user_id']);
            if ($isCustom) {
                $this -> set_message(1061, "该账号暂不支持平台币/绑币，请在游戏中支付");
            }
        }
        $game_data = Cache::get('sdk_game_data'.$request['game_id']);
        $request['game_name'] = $game_data['game_name'];
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
            $extend_data = $spendmodel -> field('id') -> where(array('extend' => $request['extend'], 'game_id' => $request['game_id'], 'pay_status' => 1)) -> find();
            if ($extend_data) {
                $this -> set_message(1055, "订单号重复，请关闭支付页面重新支付");
            }
        }
        //检查未成年用户是否满足充值条件
        if (get_user_config_info('age')['real_pay_status'] == 1) {
            $lCheckAge = new CheckAgeLogic();
            $checkAgeRes = $lCheckAge -> run($request['user_id'], $request['price']);
            if (false === $checkAgeRes) {
                $this -> set_message(1056, $lCheckAge -> getErrorMsg());
            }
        }
        $prefix = $request['code'] == 1 ? "SP_" : "PF_";
        $request['pay_order_number'] = create_out_trade_no($prefix);
        $request['pay_way'] = 4;
        $result = $this -> weixin_pay($request, 1);
        return $this -> set_message(200, "请求成功", $result);

    }


    private function pay($param = array(), $user = [])
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
        $vo -> setBody("充值")
                -> setFee($param['pay_amount'])//支付金额
                -> setTitle($param['title'])
                -> setOrderNo($param['pay_order_number'])
                -> setService($param['server'])
                -> setSignType($param['signtype'])
                -> setPayMethod($param['method'])
                -> setTable($param['table'])
                -> setPayWay($param['payway'])
                -> setGameId($param['game_id'])
                -> setGameName(get_game_name($param['game_id']))
                -> setGameAppid($param['game_appid'])
                -> setServerId($param['server_id'])
                -> setGameplayerName($param['game_player_name'])
                -> setServerName($param['server_name'])
                -> setUserId($param['user_id'])
                -> setAccount($user['account'])
                -> setUserNickName($user['nickname'])
                -> setSmallId($param['small_id'])
                -> setSmallNickname($param['small_nickname'])
                -> setPromoteId($user['promote_id'])
                -> setPromoteName($user['promote_account'])
                -> setExtend($param['extend'])
                -> setSdkVersion($param['sdk_version'])
                -> setDiscount($param['discount'] * 10)
                -> setDiscount_type($param['discount_type'])
                -> setCost($param['price'])
                -> setRoleLevel($param['role_level'])
                -> setCouponRecordId($param['coupon_id'])
                -> setPayPromoteId($payPromoteId)
                -> setExtraparam($param['extra_param']);
        $url = $pay -> buildRequestForm($vo);
        return $url;
    }


    /**
     * @代金券完全支付
     *
     * @param $param
     * @param $user
     */
    private function coupon_pay($request, $user)
    {
        $request['cost'] = $request['price'];
        $request['pay_amount'] = 0;
        $request['pay_status'] = 1;
        $result = add_spend($request, $user);
        if ($result) {
            //任务
            $usermodel = new UserModel();
            if (user_is_paied($request['user_id']) == 0) {
                $usermodel -> task_complete($request['user_id'], 'first_pay', $request['cost']);//首冲
            }
            //代金券使用
            $coupon_data['status'] = 1;
            $coupon_data['cost'] = $request['cost'];
            $coupon_data['update_time'] = time();
            $coupon_data['pay_amount'] = 0;
            Db ::table('tab_coupon_record') -> where('id', $request['coupon_id']) -> update($coupon_data);
            $request['out_trade_no'] = $request['pay_order_number'];
            $game = new GameApi();
            $game -> game_pay_notify($request);
            return true;
        } else {
            return false;
        }

    }


    /**
     * [生成二维码]
     *
     * @param string $url
     * @param int $level
     * @param int $size
     *
     * @author 郭家屯[gjt]
     */
    public function qrcode($url = '', $level = 3, $size = 4)
    {
        $url = base64_decode(base64_decode($url));
        Vendor('phpqrcode.phpqrcode');
        $errorCorrectionLevel = intval($level);//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        //生成二维码图片
        //echo $_SERVER['REQUEST_URI'];
        ob_clean();
        $object = new \QRcode();
        echo $object -> png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
        die();
    }


    private function weixin_pay($request, $and = 0)
    {
        $user = get_user_entity($request['user_id'], false, 'id,account,promote_id,promote_account,parent_id');
        //官方微信支付
        $json_data['paytype'] = "wx";
        $json_data['orderno'] = $request['pay_order_number'];
        $json_data['cal_url'] = cmf_get_domain();
        if ($request['price'] <= 0) {
            if ($and) {
                $this -> set_message(1061, "充值金额有误");
            } else {
                return redirect(url('Pay/notice', array('user_id' => $request['user_id'], 'game_id' => $request['game_id'], 'msg' => urlencode('充值金额有误'))));
            }
        }
        $request['cost'] = $request['price'];
        if ($request['code'] == 1) {
            $promote_id = $user['promote_id'];
            $paylogic = new PayLogic();
            //去除代金券金额
            if ($request['coupon_id']) {
                $coupon_money = $paylogic -> get_use_coupon($request['user_id'], $request['price'], $request['coupon_id']);
                if ($coupon_money) {
                    $new_price = $request['price'] - $coupon_money;
                } else {
                    if($and){
                        $this->set_message(1083, "优惠券使用失败");
                    }else {
                        return redirect(url('Pay/notice', array('user_id' => $request['user_id'], 'game_id' => $request['game_id'], 'msg' => urlencode('优惠券使用失败'))));
                    }
                }
            }
            $discount_info = $paylogic -> get_discount($request['game_id'], $promote_id, $request['user_id']);
            if ($new_price) {
                $request['pay_amount'] = round($discount_info['discount'] * $new_price, 2);
            } else {
                $request['pay_amount'] = round($discount_info['discount'] * $request['price'], 2);
            }
            $request['discount_type'] = $discount_info['discount_type'];
            $request['discount'] = $discount_info['discount'];
        } else {
            $request['pay_amount'] = $request['price'];
        }
        if ($request['coupon_id'] > 0 && $new_price <= 0) {

            if($and=='1'){
                $this->set_message(1058,'支付金额不满足扫码付款条件，请选择其他付款方式');
            }else{
                return json(['code' => 0, 'msg' => '支付金额不满足扫码付款条件，请选择其他付款方式']);
            }
//            $result = $this -> coupon_pay($request, $user);
//            if ($result) {
//                $url = cmf_get_domain() . "/sdk/Pay/pay_success/orderno/" . $request['pay_order_number'];
//                return json(['code' => 200, 'msg' => '支付成功', 'data' => ['url' => $url, 'wap' => 3]]);
//            } else {
//                return json(['code' => 0, 'msg' => '支付失败', 'data' => ['url' => $json_data['url'], 'wap' => 1]]);
//            }
        } elseif (pay_type_status('wxscan') == 1) {

            //查询是否开启自定义支付
            $lCustomPay = new CustompayLogic();
            $customConfig = $lCustomPay -> getPayConfig($user['promote_id'], $request['game_id'], 'wxscan', $request['pay_amount']);
            if (false !== $customConfig) {
                $request['pay_promote_id'] = $user['promote_id'];
            }

            $weixn = new Weixin();
            $is_pay = json_decode($weixn -> weixin_pay("充值", $request['pay_order_number'], $request['pay_amount'],
                    'NATIVE',1,0,0,$user['promote_id'],$request['game_id']), true);
            if ($is_pay['status'] == 1) {
                if ($request['code'] == 1) {
                    add_spend($request, $user);
                } else {
                    add_deposit($request, $user);
                }
                $json_data['code'] = 200;
                $json_data['qrcode_url'] = url('sdk/Scanpay/qrcode', array('level' => 3, 'size' => 4, 'url' => base64_encode(base64_encode($is_pay['url']))), true, true);
            } else {
                $json_data['status'] = 500;
                $json_data['url'] = url('pay/notice', array('user_id' => $request['user_id'], 'game_id' => $request['game_id'], 'msg' => urlencode('支付失败')));
            }
        }
        if($and=='1'){
            return $json_data;
        }else{
            return json($json_data);
        }

    }


    /**
     * @检查订单状态
     *
     * @author: zsl
     * @since: 2021/1/5 15:30s
     */
    public function check_status()
    {
        $order_no = $this -> request -> param('out_trade_no');
        if (empty($order_no)) {
            $request = json_decode(base64_decode(file_get_contents("php://input")), true);
            $order_no = $request['out_trade_no'];
        }
        $pay_where = substr($order_no, 0, 2);
        switch ($pay_where) {
            case 'SP':
                $model = new SpendModel();
                $result = $model -> where('pay_order_number', $order_no) -> field('id,pay_status') -> find();
                break;
            case 'PF':
                $model = new SpendBalanceModel();
                $result = $model -> where('pay_order_number', $order_no) -> field('id,pay_status') -> find();
                break;
        }
        if (empty($result)) {
            return json(['code' => 0]);
        } else {
            $result = $result -> toArray();
            if ($result['pay_status'] == 1) {
                if ($request['sdk_version'] == '1') {
                    $this -> set_message(200, '请求成功');
                } else {
                    return json(['code' => 200]);
                }
            } else {
                if ($request['sdk_version'] == '1') {
                    $this -> set_message(0, '请求失败');
                } else {
                    return json(['code' => 0]);
                }
            }
        }


    }


    private function set_message($code = 200, $msg = '', $data = [], $type = 0)
    {
        $msg = array(
                "code" => $code,
                "msg" => $msg,
                "data" => $data
        );
        if ($type == 1) {
            echo base64_encode(json_encode($msg, JSON_FORCE_OBJECT));
        } elseif ($type == 2) {
            echo base64_encode(json_encode($msg, true));
        } else {
            echo base64_encode(json_encode($msg, JSON_PRESERVE_ZERO_FRACTION));
        }
        exit;
    }

}
