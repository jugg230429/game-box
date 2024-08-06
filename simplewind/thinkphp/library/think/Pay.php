<?php

/**
 * 通用支付接口类
 * @author yunwuxin<448901948@qq.com>
 */

namespace think;


use app\issue\model\BalanceModel;
use app\promote\logic\CustompayLogic;

class Pay
{

    /**
     * 支付驱动实例
     * @var Object
     */
    private $payer;

    /**
     * 配置参数
     * @var type
     */
    private $config;

    /**
     * 支付类型
     * @var type
     */
    private $apitype;

    /**
     * 构造方法，用于构造上传实例
     * @param string $driver 要使用的支付驱动
     * @param array $config 配置
     */
    public function __construct($driver, $config = array())
    {
        /* 配置 */
        header("Content-Type: text/html;charset=utf-8");
        $pos = strrpos($driver, '\\');
        $pos = $pos === false ? 0 : $pos + 1;
        $apitype = strtolower(substr($driver, $pos));
        $this->apitype = $apitype;
        $this->config['notify_url'] = 'http://' . $_SERVER ['HTTP_HOST'] . "/callback/Notify/alipay_callback/apitype/" . $apitype . '/method/notify';
        $this->config['return_url'] = 'http://' . $_SERVER ['HTTP_HOST'] . "/callback/Notify/alipay_callback/apitype/" . $apitype . '/method/return/model/'.MODULE_NAME .'/c/'.CONTROLLER_NAME;
        $config = array_merge($this->config, $config);
        /* 设置支付驱动 */
        $class = strpos($driver, '\\') ? $driver : 'think\\pay\\driver\\' . ucfirst(strtolower($driver));
        $this->setDriver($class, $config);
    }

    public function buildRequestForm(Pay\PayVo $vo, $uc = 0)
    {
        $this->payer->check();
        $result = false;
        switch ($vo->getTable()) {
            case 'spend':
                $result = $this->add_spend($vo);
                break;
            case 'deposit':
                $result = $this->add_deposit($vo);
                break;
            case 'promote_deposit':
                $result = $this->add_promote_deposit($vo);
                break;
            case 'bind':
                $result = $this->add_bind_recharge($vo);
                break;
            case 'member':
                $result = $this->add_member($vo);
                break;
            case 'promote_bind':
                $result = $this->add_promote_bind($vo);
                break;
            case 'transaction':
                $result = $this->add_transaction($vo);
                break;
            case 'transaction_continue':
                $result = true;
                break;
            case 'issue_open_user_balance':
                $result = $this->add_issue_open_user_balance($vo);
                break;
            case 'sue_spend':
                $result = true;
                break;
            case 'issue_spend':
                $result = true;
                break;
            case 'withdraw':
                $result = true;
                break;
            case 'prepayment':
                $result = $this->add_promote_prepayment_recharge($vo); // 渠道预付款充值
                break;
            case 'cash_out'://平台币提现
                $result = true;
                break;
            case 'app_supersign_order'://超级签支付
                $result = true;
                break;
            case 'promote_withdraw'://渠道提现自动打款
                $result = true;
                break;
            default:
                $result = false;
                break;
        }
        if ($result !== false) {//$check !== false
            if ($this->apitype == "alipay") {
                return $this->newbuildRequestForm($vo);
            }
        } else {
            exit('数据错误');
        }
    }

    /**
     * 新版支付宝接口调用
     * */
    public function newbuildRequestForm(Pay\PayVo $vo)
    {
        Vendor('alipay.AopSdk');

        $provate_url = ROOT_PATH . "/app/sdk/secretKey/alipay/rsa2_private_key.txt";
        if(!file_exists($provate_url)){
            $provate_url = ROOT_PATH . "/app/callback/secretKey/alipay/rsa2_private_key.txt";
        }

        $public_url = ROOT_PATH . "/app/sdk/secretKey/alipay/alipay2_public_key.txt";
        if(!file_exists($public_url)){
            $public_url = ROOT_PATH . "/app/callback/secretKey/alipay/alipay2_public_key.txt";
        }
        //获取渠道自定义支付参数
        $lCustomPay = new CustompayLogic();
        $customConfig = $lCustomPay -> getPayConfig($vo -> getPayPromoteId(), $vo -> getGameId(), 'zfb',$vo->getFee());
        if (false === $customConfig) {
            $config = get_pay_type_set('zfb');
            $rsaPrivateKey = file_get_contents($provate_url);
            $rsaPublicKey = file_get_contents($public_url);
        } else {
            $config['config'] = $customConfig;
            $rsaPrivateKey = $customConfig['private_key'];
            $rsaPublicKey = $customConfig['public_key'];
        }
        $aop = new \AopClient();
        $aop -> appId = $config['config']['appid'];
        $aop -> signType = 'RSA2';
        $aop -> rsaPrivateKey = $rsaPrivateKey;
        $type = $vo -> getPayMethod();

        //file_put_contents(dirname(__FILE__) . '/newbuildRequestForm.txt',json_encode($vo->getOrderNo()));
        switch ($type) {
            case 'direct':
                $productcode = 'FAST_INSTANT_TRADE_PAY';
                $request = new \AlipayTradePagePayRequest();
                $request->setReturnUrl(cmf_get_domain() . '/callback/Notify/alipay_callback/methodtype/return/module/media');
                break;
            case 'wap':
                $productcode = 'QUICK_WAP_PAY';
                $request = new \AlipayTradeWapPayRequest();
                $module = $vo->getModule()?:'';
                if($vo->getTable() != 'sue_spend' && $module != 'issueh5' && $vo->getTable() != 'issue_spend'){
                    $request->setReturnUrl(cmf_get_domain(). "/callback/Notify/alipay_callback/methodtype/return/module/".$module);
                }
                break;
            case 'transaction':
                $productcode = 'QUICK_WAP_PAY';
                $request = new \AlipayTradeWapPayRequest();
                $request->setReturnUrl(cmf_get_domain(). "/callback/Notify/alipay_callback/methodtype/return");
                break;
            case 'f2fscan'://面对面扫码支付

                $aop->alipayrsaPublicKey = $rsaPublicKey;
                $request = new \AlipayTradePrecreateRequest();
                break;
            case 'mobile':
                $aop->alipayrsaPublicKey = $rsaPublicKey;
                $productcode = 'QUICK_MSECURITY_PAY';
                $request = new \AlipayTradeAppPayRequest();
                break;
            case 'transfer':
                $aop->alipayrsaPublicKey = $rsaPublicKey;
                $request = new \AlipayFundTransToaccountTransferRequest ();
                break;
            case 'transfer_cash_out'://平台币提现
                //提现功能需要使用公钥证书===========20210616-byh-start
                /** 初始化 **/
                $aop1 = new \AopCertClient;
                /** 支付宝网关 **/
                $aop1 -> gatewayUrl = "https://openapi.alipay.com/gateway.do";
                /** 应用id,如何获取请参考：https://opensupport.alipay.com/support/helpcenter/190/201602493024 **/
                $config = get_pay_type_set('zfb_tx');
                $aop1 -> appId = $config['config']['appid'];//提现的APPID
                /** 密钥格式为pkcs1，如何获取私钥请参考：https://opensupport.alipay.com/support/helpcenter/207/201602471154?ant_source=antsupport **/

                $provate_url = ROOT_PATH . "/app/app/secretKey/alipay_tx/private_key.txt";
                $aop1 -> rsaPrivateKey = file_get_contents($provate_url);
                /** 应用公钥证书路径，下载后保存位置的绝对路径  **/
                $appCertPath = ROOT_PATH . "/app/app/secretKey/alipay_tx/appCertPublicKey.crt";

                /** 支付宝公钥证书路径，下载后保存位置的绝对路径 **/
                $alipayCertPath = ROOT_PATH . "/app/app/secretKey/alipay_tx/alipayCertPublicKey_RSA2.crt";

                /** 支付宝根证书路径，下载后保存位置的绝对路径 **/
                $rootCertPath = ROOT_PATH . "/app/app/secretKey/alipay_tx/alipayRootCert.crt";

                /** 设置签名类型 **/
                $aop1 -> signType= "RSA2";

                /** 设置请求格式，固定值json **/
                $aop1 -> format = "json";

                /** 设置编码格式 **/
                $aop1 -> charset= "utf-8";

                /** 调用getPublicKey从支付宝公钥证书中提取公钥 **/
                $aop1 -> alipayrsaPublicKey = $aop1 -> getPublicKey($alipayCertPath);

                /** 是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内 **/
                $aop1 -> isCheckAlipayPublicCert = true;

                /** 调用getCertSN获取证书序列号 **/
                $aop1 -> appCertSN = $aop1 -> getCertSN($appCertPath);

                /** 调用getRootCertSN获取支付宝根证书序列号 **/
//                $aop1 -> alipayRootCertSN = $aop1 -> getRootCertSN($rootCertPath);//687b59193f3f462dd5336e5abf83c5d8_dca6abf620dffc0d48cf0def26e9530c
                $aop1 -> alipayRootCertSN = "687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6";//到2028年之前都不会变

                /** 实例化具体API对应的request类，类名称和接口名称对应,当前调用接口名称：alipay.fund.trans.uni.transfer(单笔转账接口) **/
                $request = new \AlipayFundTransUniTransferRequest();


                //提现功能需要使用公钥证书===========20210616-byh-end
                break;
            case 'promote_withdraw_auto_paid'://渠道提现自动打款
                //提现功能需要使用公钥证书===========20210616-byh-start
                /** 初始化 **/
                $aop1 = new \AopCertClient;
                /** 支付宝网关 **/
                $aop1 -> gatewayUrl = "https://openapi.alipay.com/gateway.do";
                /** 应用id,如何获取请参考：https://opensupport.alipay.com/support/helpcenter/190/201602493024 **/
                $config = get_pay_type_set('zfb_tx');
                $aop1 -> appId = $config['config']['appid'];//提现的APPID
                /** 密钥格式为pkcs1，如何获取私钥请参考：https://opensupport.alipay.com/support/helpcenter/207/201602471154?ant_source=antsupport **/

                $provate_url = ROOT_PATH . "/app/app/secretKey/alipay_tx/private_key.txt";
                $aop1 -> rsaPrivateKey = file_get_contents($provate_url);
                /** 应用公钥证书路径，下载后保存位置的绝对路径  **/
                $appCertPath = ROOT_PATH . "/app/app/secretKey/alipay_tx/appCertPublicKey.crt";

                /** 支付宝公钥证书路径，下载后保存位置的绝对路径 **/
                $alipayCertPath = ROOT_PATH . "/app/app/secretKey/alipay_tx/alipayCertPublicKey_RSA2.crt";

                /** 支付宝根证书路径，下载后保存位置的绝对路径 **/
                $rootCertPath = ROOT_PATH . "/app/app/secretKey/alipay_tx/alipayRootCert.crt";

                /** 设置签名类型 **/
                $aop1 -> signType= "RSA2";

                /** 设置请求格式，固定值json **/
                $aop1 -> format = "json";

                /** 设置编码格式 **/
                $aop1 -> charset= "utf-8";

                /** 调用getPublicKey从支付宝公钥证书中提取公钥 **/
                $aop1 -> alipayrsaPublicKey = $aop1 -> getPublicKey($alipayCertPath);

                /** 是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内 **/
                $aop1 -> isCheckAlipayPublicCert = true;

                /** 调用getCertSN获取证书序列号 **/
                $aop1 -> appCertSN = $aop1 -> getCertSN($appCertPath);

                /** 调用getRootCertSN获取支付宝根证书序列号 **/
//                $aop1 -> alipayRootCertSN = $aop1 -> getRootCertSN($rootCertPath);//687b59193f3f462dd5336e5abf83c5d8_dca6abf620dffc0d48cf0def26e9530c
                $aop1 -> alipayRootCertSN = "687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6";//到2028年之前都不会变

                /** 实例化具体API对应的request类，类名称和接口名称对应,当前调用接口名称：alipay.fund.trans.uni.transfer(单笔转账接口) **/
                $request = new \AlipayFundTransUniTransferRequest();


                //提现功能需要使用公钥证书===========20210616-byh-end
                break;
            default:
                $productcode = 'FAST_INSTANT_TRADE_PAY';
                $request = new \AlipayTradePagePayRequest();
                break;
        }
        $request->setNotifyUrl(cmf_get_domain() . '/callback/Notify/alipay_callback/methodtype/notify');

        switch ($type) {
            case 'direct':
                $request->setBizContent('{"product_code":"' . $productcode . '","body":"' . $vo->getBody() . '","timeout_express":"2h","subject":"' . $vo->getTitle() . '","total_amount":"' . $vo->getFee() . '","out_trade_no":"' . $vo->getOrderNo() . '"}');
                return $aop->pageExecute($request, 'POST');
                break;
            case 'wap':
                /*参数  out_trade_no：系统订单号*/
                $request->setBizContent('{"product_code":"' . $productcode . '","body":"' . $vo->getBody() . '","timeout_express":"2h","subject":"' . $vo->getTitle() . '","total_amount":"' . $vo->getFee() . '","out_trade_no":"' . $vo->getOrderNo() . '"}');
                return $aop->pageExecute($request, 'GET');
                break;
            case 'transaction':
                /*参数  out_trade_no：系统订单号*/
                $request->setBizContent('{"product_code":"' . $productcode . '","body":"' . $vo->getBody() . '","timeout_express":"2h","subject":"' . $vo->getTitle() . '","total_amount":"' . $vo->getFee() . '","time_expire":"'.$vo->getLockTime().'","out_trade_no":"' . $vo->getOrderNo() . '"}');
                return $aop->pageExecute($request, 'GET');
                break;
            case 'f2fscan':
                $bizContent = '{"out_trade_no":"'.$vo->getOrderNo().'","total_amount":"'.$vo->getFee().'","timeout_express":"2h","subject":"'.$vo->getTitle().'","body":"'.$vo->getBody().'","subject":"'.$vo->getTitle().'","quantity":1}]}';
                $request->setBizContent ( $bizContent );
                // 首先调用支付api
                $AppAuthToken='';//暂时没用
                $response = $aop->execute($request,$token,$appAuthToken);
                $response = $response->alipay_trade_precreate_response;

                $result = new \AlipayF2FPrecreateResult($response);
                if(!empty($response)&&("10000"==$response->code)){
                    $resdata['status'] = 200;
                    $resdata['msg'] = "SUCCESS";
                    $resdata['payurl'] = $response->qr_code;
                    $resdata['order_no'] = $vo->getOrderNo();
                    $resdata['fee'] = $vo->getFee();
                } elseif($this->tradeError($response)){
                    $resdata['status'] = -500;
                    $resdata['msg'] = "FAILED";
                } else {
                    $resdata['status'] = -1;
                    $resdata['msg'] = "UNKNOWN";
                }
                return $resdata;
                break;
            case 'mobile':
                /*参数  out_trade_no：系统订单号*/
                $request->setBizContent('{"body":"' . $vo->getBody() . '","subject":"' . $vo->getTitle() . '","out_trade_no":"' . $vo->getOrderNo() . '","timeout_express":"2h","total_amount":"' . $vo->getFee() . '","product_code":"' . $productcode . '"}');
                $response = $aop->sdkExecute($request);
                $sHtml['arg'] = $response['orderstr'];
                $sHtml['sign'] = $response['sign'];
                $sHtml['out_trade_no'] = $vo->getOrderNo();
                return $sHtml;
                break;
            case 'transfer':
                $widthdrawno = $vo->getOrderNo();
                $widthdraw = Db::table('tab_user_tplay_withdraw')->where('pay_order_number',$widthdrawno)->find();
                /*参数  out_biz_no：打款单号  payee_type：账户类型 payee_account：支付账号  amount：转账金额  remark：备注*/
                // var_dump('{"out_biz_no":"'.$WidthdrawNo.'","payee_type":"ALIPAY_LOGONID","payee_a1ccount":"'.$promote['alipay_account'].'","amount":"'.$widthdraw['sum_money'].'","payer_show_name":"","payee_real_name":"","remark":"'.$vo->getDetailData().'"}');exit;

                $request->setBizContent('{"out_biz_no":"'.$widthdrawno.'","timeout_express":"2h","payee_type":"ALIPAY_LOGONID","payee_account":"'.$widthdraw['money_account'].'","amount":"'.($widthdraw['money']-$widthdraw['fee']).'","payer_show_name":"","payee_real_name":"","remark":"'.$vo->getDetailData().'"}');
                $result = $aop->execute($request);
                $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
                $resultCode = $result->$responseNode->code;
                $remark = date('Y-m-d H:i:s')."  提现单号：".$widthdrawno.", 提现";
                if(!empty($resultCode)&&$resultCode == 10000){
                    $data['status'] = 1;
                    $data['end_time'] = time();
                    $data['remark'] = $remark."成功<br/>".$widthdraw['remark'];
                    Db::table('tab_user_tplay_withdraw')->where('pay_order_number',$widthdrawno)->update($data);
                    return "10000";//提现成功
                } else {
                    $msg = $result->$responseNode->sub_msg;
                    $data['status'] = 2;
                    $data['remark'] = $remark."失败:".$msg."<br/>".$widthdraw['respond'];
                    Db::table('tab_user_tplay_withdraw')->where('pay_order_number',$widthdrawno)->update($data);
                    return $msg;
                }
                break;
            case 'transfer_cash_out':
                $orderno = $vo->getOrderNo();
                $order = Db::table('tab_user_cash_out')->where('order_no',$orderno)->find();
                $request->setBizContent('{"out_biz_no":"'.$orderno.'",
                "payee_type":"ALIPAY_LOGONID",
                "payee_account":"'.$order['ali_account'].'",
                "product_code":"TRANS_ACCOUNT_NO_PWD",
                "biz_scene":"DIRECT_TRANSFER",
                "payee_info":{
                    "identity":"'.$order['ali_account'].'",
                    "identity_type":"ALIPAY_LOGON_ID",
                    "name":"'.$order['account_name'].'"
                },
                "trans_amount":"'.$order['real_amount'].'",
                "payer_show_name":"","payee_real_name":"",
                "order_title":"提现","remark":"'.$vo->getDetailData().'"}');
                /** 设置业务参数，具体接口参数传值以文档说明为准：https://opendocs.alipay.com/apis/api_28/alipay.fund.trans.uni.transfer/ **/


                $result = $aop1->execute($request);
                $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
                $resultCode = $result->$responseNode->code;
                $remark = date('Y-m-d H:i:s')."  提现单号：".$orderno.", 提现";
                if(!empty($resultCode)&&$resultCode == 10000){
                    $data['status'] = 1;
                    $data['success_time'] = time();
//                    $data['remark'] = $remark."成功<br/>".$order['remark'];
                    $data['remark'] = $remark."成功<br/>";
                    $data['payment_no'] = $result->$responseNode->order_id;
                    $data['payment_time'] = $result->$responseNode->trans_date;
                    Db::table('tab_user_cash_out')->where('order_no',$orderno)->update($data);
                    return "10000";//提现成功
                } else {
                    $msg = $result->$responseNode->sub_msg;
                    $data['status'] = -1;
                    $data['remark'] = $remark."失败:".$msg."<br/>".$order['respond'];
                    Db::table('tab_user_cash_out')->where('order_no',$orderno)->update($data);
                    if(empty($msg)){
                        $msg = '打款失败!';
                    }
                    return $msg;
                }
                break;
            case 'promote_withdraw_auto_paid':
                $orderno = $vo->getOrderNo();
                $order = Db::table('tab_promote_withdraw')->where('widthdraw_number',$orderno)->find();
                $request->setBizContent('{"out_biz_no":"'.$orderno.'",
                "payee_type":"ALIPAY_LOGONID",
                "payee_account":"'.$order['payment_account'].'",
                "product_code":"TRANS_ACCOUNT_NO_PWD",
                "biz_scene":"DIRECT_TRANSFER",
                "payee_info":{
                    "identity":"'.$order['payment_account'].'",
                    "identity_type":"ALIPAY_LOGON_ID",
                    "name":"'.$order['payment_name'].'"
                },
                "trans_amount":"'.$order['payment_money'].'",
                "payer_show_name":"","payee_real_name":"",
                "order_title":"渠道提现自动打款","remark":"'.$vo->getDetailData().'"}');
                /** 设置业务参数，具体接口参数传值以文档说明为准：https://opendocs.alipay.com/apis/api_28/alipay.fund.trans.uni.transfer/ **/

                $result = $aop1->execute($request);
                $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
                $resultCode = $result->$responseNode->code;
                $resultMsg = $result->$responseNode->sub_msg;
                if(!empty($resultCode)&&$resultCode == 10000){
                    return true;
                } else {
                    return $resultMsg;
                }
                break;
        }

    }

    /**
     *消费表（玩家游戏订单）添加数据
     */
    private function add_spend(Pay\PayVo $vo)
    {
        $spend_data['user_id'] = $vo->getUserId();
        $spend_data['user_account'] = $vo->getAccount();
        $spend_data['game_id'] = $vo->getGameId();
        $spend_data['game_name'] = $vo->getGameName();
        $spend_data['server_id'] = $vo->getServerId()?:0;
        $spend_data['server_name'] = $vo->getServerName() ? : '';
        $spend_data['game_player_id'] = $vo->getGameplayerId()?:0;
        $spend_data['game_player_name'] = $vo->getGameplayerName()?:'';
        $spend_data['role_level'] = $vo->getRoleLevel() ? : 0;
        $spend_data['promote_id'] = $vo->getPromoteId();
        $spend_data['promote_account'] = $vo->getPromoteName();
        $spend_data['order_number'] = "";
        $spend_data['pay_order_number'] = $vo->getOrderNo();
        $spend_data['props_name'] = $vo->getTitle()?:'';
        $spend_data['cost'] = $vo->getCost();
        $spend_data['discount'] = $vo->getDiscount()?:10;
        $spend_data['discount_type'] = $vo->getDiscount_type();
        $spend_data['pay_amount'] = $vo->getFee();
        $spend_data['pay_way'] = $vo->getPayWay();
        $spend_data['pay_time'] = time();
        $spend_data['pay_status'] = 0;
        $spend_data['pay_game_status'] = 0;
        $spend_data['extend'] = $vo->getExtend();
        $spend_data['spend_ip'] = get_client_ip();
        $spend_data['sdk_version'] = $vo->getSdkVersion();
        $spend_data['extra_param'] = $vo->getExtraparam()?:'';
        $spend_data['small_id'] = $vo->getSmallId()?:0;
        $spend_data['small_nickname'] = $vo->getSmallNickname()?:'';
        $spend_data['coupon_record_id'] = $vo->getCouponRecordId()?:0;
        $spend_data['pay_promote_id'] = $vo -> getPayPromoteId() ?: 0;
        $spend_data['goods_reserve'] = $vo->getGoodsReserve()?:'';
        // 简化版用户ID
        if(is_third_platform($spend_data['promote_id'])){
            $spend_data['webplatform_user_id'] = get_third_user_id($spend_data['user_id']);
        }
        $result = Db::table('tab_spend')->insert($spend_data);
        return $result;
    }

    /**
     *玩家平台币充值记录
     */
    private function add_deposit(Pay\PayVo $vo)
    {
        $deposit_data['order_number'] = "";
        $deposit_data['pay_order_number'] = $vo->getOrderNo();
        $deposit_data['user_id'] = $vo->getUserId();
        $deposit_data['promote_id'] = $vo->getPromoteId();
        $deposit_data['pay_amount'] = $vo->getFee();
        $deposit_data['cost'] = $vo->getFee();
        $deposit_data['pay_status'] = 0;
        $deposit_data['pay_way'] = $vo->getPayWay();
        $deposit_data['pay_ip'] = get_client_ip();
        $deposit_data['pay_time'] = time();
        $deposit_data['pay_id'] = session('member_auth.user_id')?:$vo->getUserId();
        $deposit_data['pay_account'] = session('member_auth.account')?:$vo->getAccount();
        $deposit_data['small_id'] = $vo->getSmallId()?:0;
        $deposit_data['small_nickname'] = $vo->getSmallNickname()?:'';
        $result = Db::table('tab_spend_balance')->insert($deposit_data);
        return $result;
    }

    /**
     *用户绑币充值记录
     */
    private function add_bind_recharge(Pay\PayVo $vo)
    {
        $bind_data['order_number'] = "";
        $bind_data['pay_order_number'] = $vo->getOrderNo();
        $bind_data['game_id'] = $vo->getGameId();
        $bind_data['game_name'] = $vo->getGameName();
        $bind_data['user_id'] = $vo->getUserId();
        $bind_data['user_account'] = $vo->getAccount();
        $bind_data['promote_id'] = $vo->getPromoteId();
        $bind_data['promote_account'] = $vo->getPromoteName();
        $bind_data['pay_amount'] = $vo->getFee();
        $bind_data['cost'] = $vo->getCost();
        $bind_data['pay_status'] = 0;
        $bind_data['discount'] = $vo->getDiscount();
        $bind_data['discount_type'] = $vo->getDiscount_type();
        $bind_data['pay_way'] = $vo->getPayWay();
        $bind_data['pay_ip'] = get_client_ip();
        $bind_data['pay_time'] = time();
        $bind_data['pay_id'] = session('member_auth.user_id')?:$vo->getUserId();
        $bind_data['pay_account'] = session('member_auth.account')?:$vo->getAccount();
        $result = Db::table('tab_spend_bind')->insert($bind_data);
        return $result;
    }
     /**
     * 推广员平台币充值
     */
    private function add_promote_deposit(Pay\PayVo $vo) {
        $balance_data['order_number'] = "";
        $balance_data['pay_order_number'] = $vo->getOrderNo();
        $balance_data['promote_id'] = $vo->getPromoteId();
        $balance_data['to_id'] = $vo->getUserId();
        $balance_data['pay_amount'] = $vo->getFee();
        $balance_data['pay_status'] = 0;
        $balance_data['pay_way'] = $vo->getPayWay();
        $balance_data['type'] = $vo->getOther();
        $balance_data['create_time'] = time();
        $balance_data['spend_ip'] = get_client_ip();
        $result = Db::table("tab_promote_deposit")->insert($balance_data);
        return $result;
    }
    /**
     *用户绑币充值记录
     */
    private function add_promote_bind(Pay\PayVo $vo)
    {
        $bind_data['order_number'] = "";
        $bind_data['pay_order_number'] = $vo->getOrderNo();
        $bind_data['game_id'] = $vo->getGameId();
        $bind_data['game_name'] = get_game_entity($bind_data['game_id'],'game_name')['game_name'];
        $bind_data['user_id'] = $vo->getUserId();
        $bind_data['user_account'] = get_user_entity($bind_data['user_id'],false,'account')['account'];
        $bind_data['promote_id'] = $vo->getPromoteId();
        $bind_data['promote_account'] = get_promote_name($bind_data['promote_id']);
        $bind_data['pay_amount'] = $vo->getFee();
        $bind_data['cost'] = $vo->getCost();
        $bind_data['pay_status'] = 0;
        $bind_data['discount'] = $vo->getDiscount();
        $bind_data['pay_way'] = $vo->getPayWay();
        $bind_data['pay_ip'] = get_client_ip();
        $bind_data['pay_time'] = time();
        $result = Db::table('tab_promote_bind')->insert($bind_data);
        return $result;
    }

    /**
     *玩家尊享卡会员购买记录
     */
    private function add_member(Pay\PayVo $vo)
    {
        $member_data['order_number'] = "";
        $member_data['pay_order_number'] = $vo->getOrderNo();
        $member_data['user_id'] = $vo->getUserId();
        $member_data['user_account'] = $vo->getAccount();
        $member_data['promote_id'] = $vo->getPromoteId()?:0;
        $member_data['promote_account'] = $vo->getPromoteName()?:'官方渠道';
        $member_data['pay_amount'] = $vo->getFee();
        $member_data['pay_status'] = 0;
        $member_data['pay_way'] = $vo->getPayWay();
        $member_data['spend_ip'] = get_client_ip();
        $member_data['create_time'] = time();
        $member_data['member_name'] = $vo->getMemberName();
        $member_data['days'] = $vo->getDays();
        $member_data['free_days'] = $vo->getFreeDays()?:0;
        $result = Db::table('tab_user_member')->insert($member_data);
        return $result;
    }

    private function add_issue_open_user_balance(Pay\PayVo $vo)
    {
        $data['user_id'] = $vo->getOpenUserId();
        $data['pay_order_number'] = $vo->getOrderNo();
        $data['props_name'] = $vo->getFee().'元';
        $data['pay_amount'] = $vo->getFee();
        $data['pay_way'] = $vo->getPayWay();
        $data['pay_ip'] = get_client_ip();
        $data['pay_time'] = time();
        $res = (new BalanceModel())->add_issue_open_user_balance($data);
        return $res;
    }

    // 订单写入订单记录 渠道预付款
    private function add_promote_prepayment_recharge(Pay\PayVo $vo){
        $time  = time();
        $data['promote_id'] = $vo->getPromoteId();
        $data['promote_account'] = get_promote_name($data['promote_id']);
        $data['pay_order_number'] = $vo->getOrderNo();
        $data['pay_amount'] = $vo->getFee();
        $data['pay_way'] = $vo->getPayWay();
        $data['pay_ip'] = get_client_ip();
        $data['pay_time'] = $time;
        $data['create_time'] = $time;

        $res = Db::table('tab_promote_prepayment_recharge')->insert($data);
        return $res;
    }

    /**
     * @函数或方法说明
     * @添加商品订单
     * @param Pay\PayVo $vo
     *
     * @author: 郭家屯
     * @since: 2020/3/11 10:28
     */
    private function add_transaction(Pay\PayVo $vo)
    {
        $user = get_user_entity($vo->getUserId(),false,'id,account,balance');
        $transaction = Db::table('tab_user_transaction')->where('id',$vo->getTransactionId())->find();
        $data['user_id'] =  $vo->getUserId();
        $data['user_account'] = $user['account'];
        $data['game_id'] = $transaction['game_id'];
        $data['game_name'] = $transaction['game_name'];
        $data['server_name'] = $transaction['server_name'];
        $data['title'] = $transaction['title'];
        $data['screenshot'] = $transaction['screenshot'];
        $data['dec'] = $transaction['dec'];
        $data['cumulative'] = $transaction['cumulative'];
        $data['pay_order_number'] = $vo->getOrderNo();
        $data['transaction_number'] = $transaction['order_number'];
        if($vo->getBalanceStatus() == 1){
            $data['balance_money'] = $user['balance'];
        }
        $data['pay_amount'] = $transaction['money'];
        $data['fee'] = $vo->getFeeMoney()?:0;
        $data['pay_time'] = time();
        $data['pay_way'] = $vo->getPayWay();
        $data['pay_ip'] = get_client_ip();
        $data['sell_id'] = $transaction['user_id'];
        $data['sell_account'] = $transaction['user_account'];
        $data['small_id'] = $transaction['small_id'];
        $data['phone'] = $transaction['phone'];
        $data['transaction_id'] = $transaction['id'];
        $result = Db::table('tab_user_transaction_order')->insert($data);
        if($result){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 设置支付驱动
     * @param string $class 驱动类名称
     */
    private function setDriver($class, $config)
    {
        $this->payer = new $class($config);
        if (!$this->payer) {
            exit("不存在支付驱动：{$class}");
        }
    }

    public function __call($method, $arguments)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array(array(&$this, $method), $arguments);
        } elseif (!empty($this->payer) && $this->payer instanceof Pay\Pay && method_exists($this->payer, $method)) {
            return call_user_func_array(array(&$this->payer, $method), $arguments);
        }
    }

}
