<?php
namespace think;

/**
 * 新渠道支付公共类
 */

use app\issue\model\BalanceModel;
use app\promote\logic\CustompayLogic;
use app\recharge\model\SpendPromoteParamModel;
use think\Db;

class PromotePay{

    /**
     * 游戏ID
     */
    private $gameId;

    /**
     * 支付类型 1.支付宝 2.微信 3.银联 4.云闪付 5.数字人民币
     */
    private $payType;


    public function __construct($gameId = 0, $payType = 0)
    {
        /* 初始化配置 */
        header("Content-Type: text/html;charset=utf-8");
        $this->gameId = $gameId;
        $this->payType = $payType;
    }


    /**
     * 初始化订单数据对象
     * param    订单参数
     * user     用户参数
     */
    public function initParam($param = array(),$user=[])
    {
        // $pay_type = 'zfb';
        // if($param['pay_way'] == 4){
        //     $pay_type = 'wxscan';
        // }
        // //获取渠道自定义支付参数
        // $lCustomPay = new CustompayLogic();
        // $customConfig = $lCustomPay -> getPayConfig($user['promote_id'], $param['game_id'], $pay_type, $param['pay_amount']);
        // $payPromoteId = 0;
        // if (false !== $customConfig) {
        //     $payPromoteId = $user['promote_id'];
        // }

        $vo = new \think\pay\PayVo();
        $vo->setBody("充值")
            ->setFee($param['pay_amount'])//支付金额
            ->setTitle($param['title'])
            ->setOrderNo($param['pay_order_number'])
            ->setService($param['server'])
            ->setSignType($param['signtype'])
            ->setPayMethod("wap")
            ->setTable($param['table'])
            ->setPayWay($param['pay_way'])
            ->setGameId($param['game_id'])
            ->setGameName(get_game_name($param['game_id']))
            ->setGameAppid($param['game_appid'])
            ->setServerId($param['server_id'])
            ->setGameplayerId($param['game_player_id'])
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
            //支付额外角色信息
            ->setGoodsReserve($param['goods_reserve'])
            ->setPayPromoteId(0);
        return $this->buildRequestForm($vo);
    }


    /**
     * 订单业务处理流程 - 不熟悉
     * vo       订单对象
     */
    public function buildRequestForm(Pay\PayVo $vo, $uc = 0)
    {
        //选择支付渠道配置
        $config = $this->choosePayChannel($vo);

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
           return $this->thirdPartyOrdering($vo,$config);
        } else {
            exit(base64_encode(json_encode(['code'=>500,'msg'=>'订单业务数据错误'], JSON_FORCE_OBJECT)));
        }
    }


    /**
     *渠道支付下单流程
     *1.通过传递传输选择合适的第三方商家配置
     *2.发起支付下单
     */
    public function choosePayChannel(Pay\PayVo $vo){
        //根据游戏id和支付类型选择合适的三方支付
        $promoteModel = new SpendPromoteParamModel();
        return $promoteModel->choosePromoteConfig($this->gameId,$this->payType,$vo->getFee(),$vo->getUserId());
    }

    /**
     * 三方支付下单
     * $vo              订单对象
     * $promoteConfig   商家支付配置
     */
    public function thirdPartyOrdering(Pay\PayVo $vo,$promoteConfig){
        switch ($promoteConfig['promote_id']) {
            case 1:
                $result = $this->dingshengPay($vo,$promoteConfig);
                break;
            case 2:
                $result = $this->antPay($vo,$promoteConfig);
                break;
            case 3:
                $result = $this->rainbowPay($vo,$promoteConfig);
                break;
            case 4:
                $result = $this->hiPay($vo,$promoteConfig);
                break;
            case 5:
                $result = $this->bigHeadPay($vo,$promoteConfig);
                break;
            case 6:
                $result = $this->huijuPay($vo,$promoteConfig);
                break;
            case 7:
                $result = $this->ptPay($vo,$promoteConfig);
                break;
            case 8:
                $result = $this->ptPay($vo,$promoteConfig);
                break;
            default:
                exit(base64_encode(json_encode(['code'=>500,'msg'=>'商家支付通道异常'], JSON_FORCE_OBJECT)));
                
        }        
        return $result;
    }



    /**
     *  鼎盛支付
     *  vo 订单对象
     *  promoteConfig  支付渠道配置
     **/    
    private function dingshengPay(Pay\PayVo $vo,$promoteConfig){
        // $host = $_SERVER['HTTP_HOST'];
        // if (strpos($host, 'https') == false) {
        //     $host = 'https://' . $host;
        // }
        $host = $this->getPayDomain();
        $paramArray = [
            "partner" => $promoteConfig['partner'],                                 //商户号
            "out_trade_no" => $vo->getOrderNo(),                                    //订单号
            "total_fee" => $vo->getFee(),                                           //支付金额
            "exter_invoke_ip" => $promoteConfig['callback_ip'],                     //回调ip
            // "custom" => $vo->getTitle(),                                         //标题
            "return_url" => $host . "/sdk/promote_pay/ds_callback",                 //支付同步回调
            "notify_url" => $host . "/sdk/promote_pay/ds_callback",                 //支付异步回调
            "pay_type" => $promoteConfig['channel_coding'],	                        //支付类型
        ];

        $sign = $this->dsParamArraySign($paramArray, $promoteConfig['key']);  //签名
        $paramArray["sign"] = $sign;
        $paramArray['return_type'] = 1;
        $paramsStr = http_build_query($paramArray); //请求参数str
        //根据不同的vo->gettable记录订单日志
        $table_name = $this->getTableName($vo);
        //记录订单日志
        $log = [
            'config_id' => $promoteConfig['id'],
            'pay_order_number' => $vo->getOrderNo(),
            'send_content' => $paramsStr,
            'table' => $table_name,
            'type' => 1,
            'create_time' => date("Y-m-d H:i:s")
        ];
        $logId = Db::table('tab_spend_promote_pay_log')->insertGetId($log);
        Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['promote_param_id'=>$promoteConfig['id']]);
        $replyContent = $this->httpPost($promoteConfig['order_address'], $paramsStr);
        //更新回复记录
        //{"code":200,"msg":"请求成功","data":{"out_trade_no":"SP_20240806204728mv2n","total_fee":"60.0","pay_url":"https://p.cdd667889.xyz/pre/CDD069C77A69CA9BF1D2812A57A3A3"}}
        //处理返回json格式,取data返回
        $result = json_decode($replyContent,true);
        if($result['code'] != 200){
            Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['reply_content'=>$replyContent]);
            //新增下单错误处理逻辑
            $this->dealPromteChannelFail($vo,$promoteConfig,$replyContent);
            exit($replyContent);
        }
        else{
            $out_trade_no = $result['data']['out_trade_no'];
            Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['order_number'=>$out_trade_no,'reply_content'=>$replyContent]);
            //更新tab_spend表数据,外部订单号和支付渠道配置id
            Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['order_number'=>$out_trade_no,'promote_param_id'=>$promoteConfig['id']]);
            return $result['data'];
        }
    }


    /**
     *  蚂蚁支付
     *  vo 订单对象
     *  promoteConfig  支付渠道配置
     **/    
    private function antPay(Pay\PayVo $vo,$promoteConfig){
        $host = $this->getPayDomain();
        //处理金额
        $amount = (double)$vo->getFee() * 100;
        $paramArray = [
            "mchId" => $promoteConfig['partner'],                                       //商户号
            "productId" => $promoteConfig['channel_coding'],                            //支付产品
            "mchOrderNo" => $vo->getOrderNo(),                                          //订单号
            "amount" => $amount,                                                        //支付金额,单位:分
            "currency" => 'cny',                                                        //币种
            "notifyUrl" => $host . "/sdk/promote_pay/ant_callback",      //支付异步回调
            "subject" => $vo->getTitle(),                                               //商品主题
            "body" => '充值支付',                                                        //商品描述信息
            "reqTime" => date('YmdHis', time()).$this->get_millisecond(),               //请求时间
            "version" => '1.0',                                                         //接口版本号
        ];

        $sign = $this->antParamArraySign($paramArray, $promoteConfig['key']);  //签名
        $paramArray["sign"] = $sign;
        $paramsStr = http_build_query($paramArray); //请求参数str
        //根据不同的vo->gettable记录订单日志
        $table_name = $this->getTableName($vo);
        $log = [
            'config_id' => $promoteConfig['id'],
            'pay_order_number' => $vo->getOrderNo(),
            'send_content' => $paramsStr,
            'table' => $table_name,
            'type' => 1,
            'create_time' => date("Y-m-d H:i:s")
        ];
        $logId = Db::table('tab_spend_promote_pay_log')->insertGetId($log);
        Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['promote_param_id'=>$promoteConfig['id']]);
        $replyContent = $this->httpPost($promoteConfig['order_address'], $paramsStr);
        //更新回复记录
        //{"retCode":"0","sign":"6EB2DDB324E3793A29CB97F996E524F8","secK":"","payParams":{"payUrl":"https://openapi.alipay.com/gateway.do?alipay_sdk=alipay-sdk-net-4.7.200.ALL&app_id=2021004157628146&biz_content=%7b%22disable_pay_channels%22%3a%22honeyPay%22%2c%22out_trade_no%22%3a%22hy4846304421351839606%22%2c%22product_code%22%3a%22QUICK_WAP_WAY%22%2c%22subject%22%3a%22%e6%b8%b8%e6%88%8f%e5%85%85%e5%80%bc%22%2c%22time_expire%22%3a%222024-08-12+11%3a03%3a38%22%2c%22total_amount%22%3a%2260.00%22%7d&charset=utf-8&format=json&method=alipay.trade.wap.pay&notify_url=http%3a%2f%2fauth.cqyyc.top%2fNotify&sign_type=RSA2&timestamp=2024-08-12+11%3a00%3a38&version=1.0&sign=cQGoJXlUvrq5QfZHIiY15pW7ETq7KxX2IsKLom7XohUx3eYRb4rkJujx%2bNci4WBbWrh4gLN6Qd2iAfTm7h05j8Z7h6d6r8Vz9a%2bcwzQisTgXBZYP9SqCV23xYnTNvjLpbNLdm%2bai4ijFPAYNgkemBwyt6cMqIxxEJNJ1pB3OlA0Ueo6Wm0RitUkoQsZ%2fpYgiAr1YRxUD0AFDVGxEMyzKMAKspJYhvw%2fZmO8CCdSkX1%2feWa%2fUpS1EtIeuEU%2fdcI2qkJUjZESZKqQnzdfEy4H8hHEuixSD%2bTctZDPeppwjBXQJ97N74I6RGZbn%2fora9RVMFaikqTmbog4rK%2bTYUnExow%3d%3d"}}
        //处理返回json格式,取data返回
        $result = json_decode($replyContent,true);
        Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['reply_content'=>$replyContent]);
        if($result['retCode'] != 0){
            //新增下单错误处理逻辑
            $this->dealPromteChannelFail($vo,$promoteConfig,$replyContent);
            exit($replyContent);
        }
        else{
            $payUrls = $result['payParams']['payUrl'];
            //更新tab_spend表数据,支付渠道配置id,out_trade_no在回调的时候再更新
            Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['promote_param_id'=>$promoteConfig['id']]);
            //重新构造返回return数组,保持一致
            $return = [
                'out_trade_no' => $vo->getOrderNo(),
                'total_fee' => $vo->getFee(),
                'pay_url' => $payUrls
            ];
            return $return;
        }
    }
    

    /**
     *  彩虹易支付
     *  vo 订单对象
     *  promoteConfig  支付渠道配置
     **/    
    private function rainbowPay(Pay\PayVo $vo,$promoteConfig){
        $host = $this->getPayDomain();
        $payType = 'alipay';
        if($promoteConfig['type'] == 2){
            $payType = 'wxpay';
        }
        $paramArray = [
            "pid" => $promoteConfig['partner'],                                     //商户ID
            "type" => $payType,                                                     //支付方式
            "out_trade_no" => $vo->getOrderNo(),                                    //订单号
            "notify_url" => $host . "/sdk/promote_pay/rainbow_callback",            //支付异步回调
            "name" => $vo->getTitle(),                                              //商品名称
            "money" => $vo->getFee(),                                               //支付金额
            "clientip" => $_SERVER['REMOTE_ADDR'],                                  //ip
        ];

        $sign = $this->rainbowParamArraySign($paramArray, $promoteConfig['key']);  //签名
        $paramArray["sign"] = $sign;
        $paramArray["sign_type"] = 'MD5';
        $paramsStr = http_build_query($paramArray); //请求参数str
        //根据不同的vo->gettable记录订单日志
        $table_name = $this->getTableName($vo);
        //记录订单日志
        $log = [
            'config_id' => $promoteConfig['id'],
            'pay_order_number' => $vo->getOrderNo(),
            'send_content' => $paramsStr,
            'table' => $table_name,
            'type' => 1,
            'create_time' => date("Y-m-d H:i:s")
        ];
        $logId = Db::table('tab_spend_promote_pay_log')->insertGetId($log);
        Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['promote_param_id'=>$promoteConfig['id']]);
        $replyContent = $this->httpPost($promoteConfig['order_address'], $paramsStr);
        //更新回复记录
        //{"code":1,"trade_no":"2024091917412160414","qrcode":"https:\/\/big.kgswm.cn\/pay\/submit\/2024091917412160414\/"}
       //处理返回json格式,取data返回
        $result = json_decode($replyContent,true);
        if($result['code'] != 1){
            Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['reply_content'=>$replyContent]);
            exit($replyContent);
        }
        else{
            $out_trade_no = $result['trade_no'];
            Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['order_number'=>$out_trade_no,'reply_content'=>$replyContent]);
            //更新tab_spend表数据,外部订单号和支付渠道配置id
            Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['order_number'=>$out_trade_no,'promote_param_id'=>$promoteConfig['id']]);
             //重新构造返回return数组,保持一致
             $return = [
                'out_trade_no' => $vo->getOrderNo(),
                'total_fee' => $vo->getFee(),
                'pay_url' => $result['qrcode']
            ];
            return $return;
        }
    }


    /**
     *  hipay支付
     *  vo 订单对象
     *  promoteConfig  支付渠道配置
     **/    
    private function hiPay(Pay\PayVo $vo,$promoteConfig){
        $host = $this->getPayDomain();
        $paramArray = [
            "recvid" => $promoteConfig['partner'],                                  //商户号
            "orderid" => $vo->getOrderNo(),                                         //订单号
            "amount" => number_format((float)$vo->getFee(), 2, '.', ''),            //支付金额
            "paychannelid" => $promoteConfig['channel_coding'],	                    //通道
            "notifyurl" => $host . "/sdk/promote_pay/hipay_callback",               //支付异步回调
            "memuid" => md5((string)$vo->getUserId()),                              //用户编号
        ];

        $sign = $this->hiPayParamArraySign($paramArray, $promoteConfig['key']);  //签名
        $paramArray["sign"] = $sign;
        $paramsStr = http_build_query($paramArray); //请求参数str
        //根据不同的vo->gettable记录订单日志
        $table_name = $this->getTableName($vo);
        //记录订单日志
        $log = [
            'config_id' => $promoteConfig['id'],
            'pay_order_number' => $vo->getOrderNo(),
            'send_content' => $paramsStr,
            'table' => $table_name,
            'type' => 1,
            'create_time' => date("Y-m-d H:i:s")
        ];
        $logId = Db::table('tab_spend_promote_pay_log')->insertGetId($log);
        Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['promote_param_id'=>$promoteConfig['id']]);
        $replyContent = $this->httpJsonPost($promoteConfig['order_address'], $paramArray);
        //更新回复记录
        //{"code":200,"msg":"请求成功","data":{"out_trade_no":"SP_20240806204728mv2n","total_fee":"60.0","pay_url":"https://p.cdd667889.xyz/pre/CDD069C77A69CA9BF1D2812A57A3A3"}}
        //处理返回json格式,取data返回
        $result = json_decode($replyContent,true);
        if($result['code'] != 1){
            Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['reply_content'=>$replyContent]);
            //新增下单错误处理逻辑
            $this->dealPromteChannelFail($vo,$promoteConfig,$replyContent);
            exit($replyContent);
        }
        else{
            $order_number = $result['data']['orderid'];
            Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['order_number'=>$order_number,'reply_content'=>$replyContent]);
            //更新tab_spend表数据,外部订单号和支付渠道配置id
            Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['order_number'=>$order_number,'promote_param_id'=>$promoteConfig['id']]);
            //重新构造返回return数组,保持一致
            $return = [
                'out_trade_no' => $vo->getOrderNo(),
                'total_fee' => $vo->getFee(),
                'pay_url' => $result['data']['payurl']
            ];
            return $return;
        }
    }


    /**
     *  大头支付
     *  vo 订单对象
     *  promoteConfig  支付渠道配置
     **/    
    private function bigHeadPay(Pay\PayVo $vo,$promoteConfig){
        $host = $this->getPayDomain();
        //处理金额
        $amount = (double)$vo->getFee() * 100;
        $paramArray = [
            "mchId" => $promoteConfig['partner'],                                       //商户号
            "productId" => $promoteConfig['channel_coding'],                            //支付产品
            "mchOrderNo" => $vo->getOrderNo(),                                          //订单号
            "amount" => $amount,                                                        //支付金额,单位:分
            "currency" => 'cny',                                                        //币种
            "notifyUrl" => $host . "/sdk/promote_pay/ant_callback",                 //支付异步回调
            "subject" => $vo->getTitle(),                                               //商品主题
            "body" => '充值支付',                                                        //商品描述信息
            "reqTime" => date('YmdHis', time()).$this->get_millisecond(),               //请求时间
            "version" => '1.0',                                                         //接口版本号
        ];

        $sign = $this->antParamArraySign($paramArray, $promoteConfig['key']);  //签名
        $paramArray["sign"] = $sign;
        $paramsStr = http_build_query($paramArray); //请求参数str
        //根据不同的vo->gettable记录订单日志
        $table_name = $this->getTableName($vo);
        $log = [
            'config_id' => $promoteConfig['id'],
            'pay_order_number' => $vo->getOrderNo(),
            'send_content' => $paramsStr,
            'table' => $table_name,
            'type' => 1,
            'create_time' => date("Y-m-d H:i:s")
        ];
        $logId = Db::table('tab_spend_promote_pay_log')->insertGetId($log);
        Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['promote_param_id'=>$promoteConfig['id']]);
        $replyContent = $this->httpPost($promoteConfig['order_address'], $paramsStr);
        //处理返回json格式,取data返回
        $result = json_decode($replyContent,true);
        Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['reply_content'=>$replyContent]);
        if($result['retCode'] != 0){
            //新增下单错误处理逻辑
            $this->dealPromteChannelFail($vo,$promoteConfig,$replyContent);
            exit($replyContent);
        }
        else{
            $payUrls = $result['payUrl'];
            //更新tab_spend表数据,支付渠道配置id,out_trade_no在回调的时候再更新
            Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['promote_param_id'=>$promoteConfig['id']]);
            //重新构造返回return数组,保持一致
            $return = [
                'out_trade_no' => $vo->getOrderNo(),
                'total_fee' => $vo->getFee(),
                'pay_url' => $payUrls
            ];
            return $return;
        }
    }


     /**
     *  汇聚支付
     *  vo 订单对象
     *  promoteConfig  支付渠道配置
     **/    
    private function huijuPay(Pay\PayVo $vo,$promoteConfig){
        $host = $this->getPayDomain();
        $payType = 'alipay';
        if($promoteConfig['type'] == 2){
            $payType = 'wxpay';
        }
        $paramArray = [
            "pid" => $promoteConfig['partner'],                                     //商户ID
            "type" => $payType,                                                     //支付方式
            "out_trade_no" => $vo->getOrderNo(),                                    //订单号
            "notify_url" => $host . "/sdk/promote_pay/huiju_callback",                 //支付异步回调
            "name" => $vo->getTitle(),                                              //商品名称
            "money" => $vo->getFee(),                                               //支付金额
            "clientip" => $_SERVER['REMOTE_ADDR'],                                  //ip
        ];

        $sign = $this->dsParamArraySign($paramArray, $promoteConfig['key']);  //签名
        $paramArray["sign"] = $sign;
        $paramArray["sign_type"] = 'MD5';
        $paramsStr = http_build_query($paramArray); //请求参数str
        //根据不同的vo->gettable记录订单日志
        $table_name = $this->getTableName($vo);
        $log = [
            'config_id' => $promoteConfig['id'],
            'pay_order_number' => $vo->getOrderNo(),
            'send_content' => $paramsStr,
            'table' => $table_name,
            'type' => 1,
            'create_time' => date("Y-m-d H:i:s")
        ];
        $logId = Db::table('tab_spend_promote_pay_log')->insertGetId($log);
        Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['promote_param_id'=>$promoteConfig['id']]);
        $replyContent = $this->httpPost($promoteConfig['order_address'], $paramsStr);
        //处理返回json格式,取data返回
        $result = json_decode($replyContent,true);
        Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['reply_content'=>$replyContent]);
        if($result['code'] != 1){
            //新增下单错误处理逻辑
            $this->dealPromteChannelFail($vo,$promoteConfig,$replyContent);
            exit($replyContent);
        }
        else{
            $out_trade_no = $result['trade_no'];
            Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['order_number'=>$out_trade_no]);
            //更新tab_spend表数据,外部订单号和支付渠道配置id
            Db::table('tab_spend')->where('pay_order_number',$vo->getOrderNo())->update(['order_number'=>$out_trade_no]);
             //重新构造返回return数组,保持一致
             //判断qrcode 或者 pay_url
             if(isset($result['qrcode'])){
                $jumpUrl = $result['qrcode'];
             }else{
                $jumpUrl = $result['payurl'];
             }
             $return = [
                'out_trade_no' => $vo->getOrderNo(),
                'total_fee' => $vo->getFee(),
                'pay_url' => $jumpUrl
            ];
            return $return;
        }
    }

    /**
     *  pt支付
     *  vo 订单对象
     *  promoteConfig  支付渠道配置
     **/    
    private function ptPay(Pay\PayVo $vo,$promoteConfig){
        $host = $this->getPayDomain();
        $amount = (double)$vo->getFee() * 100;
        $paramArray = [
            "version" => '1.0',	                                                //版本号, 固定参数1.0
            "partnerid" => $promoteConfig['partner'],                           //商户ID
            "orderid" => $vo->getOrderNo(),                                     //订单号
            "payamount" => $amount,                                             //支付金额
            "payip" =>  $_SERVER['REMOTE_ADDR'],                                //ip,
            "notifyurl" => $host . "/sdk/promote_pay/pt_callback",              //支付异步回调
            "returnurl" => $host . "/sdk/promote_pay/pt_callback",              //支付异步回调
            "paytype" => $promoteConfig['channel_coding'],	                    //支付通道
        ];

        $sign = $this->ptParamArraySign($paramArray, $promoteConfig['key']);  //签名
        $paramArray["sign"] = $sign;
        $paramsStr = http_build_query($paramArray); //请求参数str
        //根据不同的vo->gettable记录订单日志
        $table_name = $this->getTableName($vo);
        $log = [
            'config_id' => $promoteConfig['id'],
            'pay_order_number' => $vo->getOrderNo(),
            'send_content' => $paramsStr,
            'table' => $table_name,
            'type' => 1,
            'create_time' => date("Y-m-d H:i:s")
        ];
        $logId = Db::table('tab_spend_promote_pay_log')->insertGetId($log);
        Db::table($table_name)->where('pay_order_number',$vo->getOrderNo())->update(['promote_param_id'=>$promoteConfig['id']]);
        $replyContent = $this->httpPost($promoteConfig['order_address'], $paramsStr);
        //处理返回json格式,取data返回
        $result = json_decode($replyContent,true);
        Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['reply_content'=>$replyContent]);
        if($result['code'] != 0){
            //新增下单错误处理逻辑
            $this->dealPromteChannelFail($vo,$promoteConfig,$replyContent);
            exit($replyContent);
        }
        else{
            $out_trade_no = $result['partnerorderid'];
            Db::table('tab_spend_promote_pay_log')->where('id',$logId)->update(['order_number'=>$out_trade_no]);
            //更新tab_spend表数据,外部订单号和支付渠道配置id
            Db::table('tab_spend')->where('pay_order_number',$vo->getOrderNo())->update(['order_number'=>$out_trade_no]);
             //重新构造返回return数组,保持一致
             $return = [
                'out_trade_no' => $vo->getOrderNo(),
                'total_fee' => $vo->getFee(),
                'pay_url' => $result['payurl']
            ];
            return $return;
        }
    }

    /**
     *  处理支付渠道失败统计操作
     *  pay_order_number 订单号
     *  promoteConfig  支付渠道配置
     **/  
    private function dealPromteChannelFail(Pay\PayVo $vo,$promoteConfig,$replyContent){
        $paramModel = new SpendPromoteParamModel();
        $insert = [
            'pay_order_number' => $vo->getOrderNo(),
            'pay_amount' => $vo->getFee(),
            'promote_param_id' => $promoteConfig['id'],
            'promote_name' => $paramModel->getPromoteBussinessNameById($promoteConfig['id']),
            'pay_type' =>  $promoteConfig['type'],
            'channel_coding' => $promoteConfig['channel_coding'],
            'content' => $replyContent,
            'status' => 0,
            'create_time' => date("Y-m-d H:i:s")
        ];
        Db::table('tab_spend_promote_fail_log')->insert($insert);
        //判断一小时内渠道支付失败次数超过10则关闭
        $count =  Db::table('tab_spend_promote_fail_log')
        ->where('promote_param_id',$promoteConfig['id'])
        ->where('create_time','>=',date("Y-m-d H:i:s", strtotime("-5 minutes")))
        ->count();
        if($count > 4){
            Db::table('tab_spend_promote_param')->where('id',$promoteConfig['id'])->update(['status'=>0]);
        }
    }

    private function getTableName(Pay\PayVo $vo){
        switch ($vo->getTable()) {
            //游戏订单
            case 'spend':
                return 'tab_spend';
            //平台币    
            case 'deposit':
                return 'tab_spend_balance';
            //尊享卡    
            case 'member':
                return 'tab_user_member';
            default:
                exit('未开发支付场景,请重试'); 
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


    function httpPost($url, $paramStr){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $paramStr,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $err;
        }
        return $response;
    }

    function httpJsonPost($url, $paramArray){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($paramArray),
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $err;
        }
        return $response;
    }

    function dsParamArraySign($paramArray, $mchKey){

        ksort($paramArray);  //字典排序
        reset($paramArray);
        $md5str = "";


        for($i = 0; $i < count($paramArray);$i++){
            $key = $paramArray;
        }
        $i = 0;
        foreach ($paramArray as $key => $val) {
            $md5str .= $key . "=";
            if(strlen($val)){
                $md5str .= $val;
            }else{
                $md5str .= '';
            }
            if($i < count($paramArray) - 1){
                $md5str .= "&";
            }
            $i++;
        }
        $str =  $md5str  . $mchKey;        
        //签名
        return strtolower(md5($str));

    }

    function antParamArraySign($paramArray, $mchKey){

        ksort($paramArray);  //字典排序
        reset($paramArray);
        $md5str = "";

        for($i = 0; $i < count($paramArray);$i++){
            $key = $paramArray;
        }
        $i = 0;
        foreach ($paramArray as $key => $val) {
            $md5str .= $key . "=";
            if(strlen($val)){
                $md5str .= $val;
            }else{
                $md5str .= '';
            }
            if($i < count($paramArray) - 1){
                $md5str .= "&";
            }
            $i++;
        }
        $str =  $md5str . '&key=' . $mchKey;        
        //签名
        return strtoupper(md5($str));
    }

    // 计算签名
	function rainbowParamArraySign($param,$mchKey){
		ksort($param);
		reset($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $k != "sign_type" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		$signstr .= $mchKey;
		$sign = md5($signstr);
		return $sign;
	}

    // 计算签名
	function huijuParamArraySign($param,$mchKey){
		ksort($param);
		reset($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $k != "sign_type" && $v!='' && $v!= null){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		$signstr .= $mchKey;
		$sign = strtolower(md5($signstr));
		return $sign;
	}


    // 计算签名
	function ptParamArraySign($param,$mchKey){
		ksort($param);
		reset($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $k != "sign_type" && $v!='' && $v!= null){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
        $signstr =  $signstr . '&key=' . $mchKey;   
		return strtolower(md5($signstr));
	}



    // 计算签名
	function hiPayParamArraySign($param,$mchKey){
        //sign=md5("pay"+recvid+orderid+amount+apikey)
		$sign = md5("pay".$param['recvid'].$param['orderid'].$param['amount'].$mchKey);
		return $sign;
	}
 
 
    function get_millisecond(){
        list($usec, $sec) = explode(" ", microtime());
        $msec=round($usec*1000);
        return $msec;
    }

    function getPayDomain(){
        return 'https://pay.ksduut.net';
    }
    
}