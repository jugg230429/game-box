<?php

/**
 * 订单数据模型
 */

namespace think\pay;

class PayVo
{

    protected $_orderNo;
    protected $_ratio;
    protected $_fee;
    protected $_title;
    protected $_body;
    protected $_signtype;
    protected $_callback;
    protected $_url;
    protected $_param;
    protected $_payWay;
    protected $_gameid;
    protected $_gameName;
    protected $_gameAppid;
    protected $_serverid;
    protected $_serverName;
    protected $_gameplayerId;
    protected $_gameplayerName;
    protected $_userid;
    protected $_account;
    protected $_userNickName;
    protected $_promoteid;
    protected $_promoteName;
    protected $_extend;
    protected $_table;
    protected $_bank;
    protected $_money;
    protected $_coin;
    protected $_service;
    protected $_notifyurl;
    protected $_payMethod;
    protected $_sdkVersion;
    protected $_discount;
    protected $_uc;
    protected $_batchno;
    protected $_detaildata;
    protected $_smallid;
    protected $_smallaccount;
    protected $_smallnickname;
    protected $_sellerid;
    protected $_selleraccount;
    protected $_buyerid;
    protected $_buyeraccount;
    protected $_poundage;
    protected $_other;
    protected $_extraparam;
    protected $_roleLevel;
    protected $_cost;
    protected $_coupon_record_id;
    protected $_transaction_id;
    protected $_balance_status;
    protected $_fee_money;
    protected $_lock_time;
    protected $_discount_type;
    protected $_open_user_id;
    protected $_member_name;
    protected $_days;
    protected $_free_days;
    protected $_module;
    protected $_pay_promote_id;
    protected $_goods_reserve;


    public function getGoodsReserve()
    {
        return $this ->_goods_reserve;
    }

    /**
     * @param mixed $pay_promote_id
     */
    public function setGoodsReserve($_goods_reserve)
    {
        $this ->_goods_reserve = $_goods_reserve;
        return $this;
    }


    /**
     * @return mixed
     */
    public function getPayPromoteId()
    {
        return $this -> _pay_promote_id;
    }

    /**
     * @param mixed $pay_promote_id
     */
    public function setPayPromoteId($pay_promote_id)
    {
        $this -> _pay_promote_id = $pay_promote_id;
        return $this;
    }

    //判断是否为uc用户
    public function setUc($uc)
    {
        $this->_uc = $uc;
        return $this;
    }

    //其他数据
    public function setOther($other)
    {
        $this->_other = $other;
        return $this;
    }

    public function setGameplayerId($gameplayerId)
    {
        $this->_gameplayerId = $gameplayerId;
        return $this;
    }

    public function setGameplayerName($gameplayerName)
    {
        $this->_gameplayerName = $gameplayerName;
        return $this;
    }

    //退款批次号
    public function setBatchNo($batchno)
    {
        $this->_batchno = $batchno;
        return $this;
    }

    //单笔数据集
    public function setDetailData($detaildata)
    {
        $this->_detaildata = $detaildata;
        return $this;
    }

    /**
     * 获取uc用户
     * @return type
     */
    public function getUc()
    {
        return $this->_uc;
    }

    /**
     * 设置订单号
     * @param type $order_no
     * @return \Think\Pay\PayVo
     */
    public function setOrderNo($order_no)
    {
        $this->_orderNo = $order_no;
        return $this;
    }

    /**
     * 设置开发商返利比例
     * @param $ratio
     * @return $this
     */
    public function setRatio($ratio)
    {
        $this->_ratio = $ratio;
        return $this;
    }

    /**
     * 设置商品价格
     * @param type $fee
     * @return \Think\Pay\PayVo
     */
    public function setFee($fee)
    {
        $this->_fee = $fee;
        return $this;
    }

    /**
     * 设置商品名称
     * @param type $title
     * @return \Think\Pay\PayVo
     */
    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    /**
     * 设置商品描述
     * @param type $body
     * @return \Think\Pay\PayVo
     */
    public function setBody($body)
    {
        $this->_body = $body;
        return $this;
    }

    /**
     *签名方式
     * @param signtype
     * @return \Think\Pay\PayVo
     */
    public function setSignType($signtype)
    {
        $this->_signtype = $signtype;
        return $this;
    }

    /**
     * 设置支付完成后的后续操作接口
     * @param type $callback
     * @return \Think\Pay\PayVo
     */
    public function setCallback($callback)
    {
        $this->_callback = $callback;
        return $this;
    }

    /**
     * 设置支付完成后的跳转地址
     * @param type $url
     * @return \Think\Pay\PayVo
     */
    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }

    /**
     * 设置订单的额外参数
     * @param type $param
     * @return \Think\Pay\PayVo
     */
    public function setParam($param)
    {
        $this->_param = $param;
        return $this;
    }

    /**
     * 设置游戏充值方式
     * @param type $payway
     * @return \Think\Pay\PayVo
     */
    public function setPayWay($payway)
    {
        $this->_payWay = $payway;
        return $this;
    }

    /**
     * 设置游戏gameid
     * @param type $gameid
     * @return \Think\Pay\PayVo
     */
    public function setGameId($gameid)
    {
        $this->_gameid = $gameid;
        return $this;
    }

    /**
     * 设置游戏名称gamename
     * @param type $gamename
     * @return \Think\Pay\PayVo
     */
    public function setGameName($gamename)
    {
        $this->_gameName = $gamename;
        return $this;
    }

    /**
     *游戏APPID
     * @param type $gameappid
     * @return \Think\Pay\PayVo
     */
    public function setGameAppid($gameappid)
    {
        $this->_gameAppid = $gameappid;
        return $this;
    }

    /**
     * 设置游戏区服serverid
     * @param type $serverid
     * @return \Think\Pay\PayVo
     */
    public function setServerId($serverid)
    {
        $this->_serverid = $serverid;
        return $this;
    }

    /**
     * 设置游戏区服名称servername
     * @param type $servername
     * @return \Think\Pay\PayVo
     */
    public function setServerName($servername)
    {
        $this->_serverName = $servername;
        return $this;
    }

    /**
     * 设置用户账号uid
     * @param type $userid
     * @return \Think\Pay\PayVo
     */
    public function setUserId($userid)
    {
        $this->_userid = $userid;
        return $this;
    }

    /**
     * 设置用户账号
     * @param type $url
     * @return \Think\Pay\PayVo
     */
    public function setAccount($account)
    {
        $this->_account = $account;
        return $this;
    }

    /**
     * 设置用户账号昵称
     * @param type $url
     * @return \Think\Pay\PayVo
     */
    public function setUserNickName($usernickname)
    {
        $this->_userNickName = $usernickname;
        return $this;
    }

    /**
     *设置渠道id
     * @param promoteid
     * @return \Think\Pay\PayVo
     */
    public function setPromoteId($promoteid)
    {
        $this->_promoteid = $promoteid;
        return $this;
    }

    /**
     *设置渠道账号
     * @param promotename
     * @return \Think\Pay\PayVo
     */
    public function setPromoteName($promotename)
    {
        $this->_promoteName = $promotename;
        return $this;
    }

    /**
     *CP方扩展透传信息
     * @param extend
     * @return \Think\Pay\PayVo
     */
    public function setExtend($extend)
    {
        $this->_extend = $extend;
        return $this;
    }

    /**
     *要插入的表
     */
    public function setTable($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * 设置充值银行
     * @param type $param
     * @return \Think\Pay\PayVo
     */
    public function setBank($bank)
    {
        $this->_bank = $bank;
        return $this;
    }

    /**
     * 设置充值实际金额（除去手续费）
     * @param type $param
     * @return \Think\Pay\PayVo
     */
    public function setMoney($money)
    {
        $this->_money = $money;
        return $this;
    }

    /**
     * 设置充值游戏币数量
     * @param type $param
     * @return \Think\Pay\PayVo
     */
    public function setCoin($coin)
    {
        $this->_coin = $coin;
        return $this;
    }

    /**
     * 设置支付服务类型
     * @param type $param
     * @return \Think\Pay\PayVo
     */
    public function setService($service)
    {
        $this->_service = $service;
        return $this;
    }

    /**
     *支付异步通知地址
     */
    public function setNotifyUrl($notifyurl)
    {
        $this->_notifyurl = $notifyurl;
        return $this;
    }

    /**
     *支付方法(第三方支付选择例如1：支付宝 2：微信)
     */
    public function setPayMethod($payMethod)
    {
        $this->_payMethod = $payMethod;
        return $this;
    }

    /**
     *sdk版本安卓 苹果
     */
    public function setSdkVersion($SdkVersion)
    {
        $this->_sdkVersion = $SdkVersion;
        return $this;
    }

    public function setDiscount($Discount)
    {
        $this->_discount = $Discount;
        return $this;
    }
    public function setDiscount_type($Discount_type){
        $this->_discount_type= $Discount_type;
        return $this;
    }

    /**
     * 设置小号
     * @author 鹿文学
     */
    public function setSmallId($smallid)
    {
        $this->_smallid = $smallid;
        return $this;
    }

    public function setSmallAccount($smallaccount)
    {
        $this->_smallaccount = $smallaccount;
        return $this;
    }

    public function setSmallNickname($smallnickname)
    {
        $this->_smallnickname = $smallnickname;
        return $this;
    }

    /**
     * 设置卖家
     * @author 鹿文学
     */
    public function setSellerId($sellerid)
    {
        $this->_sellerid = $sellerid;
        return $this;
    }

    public function setSellerAccount($selleraccount)
    {
        $this->_selleraccount = $selleraccount;
        return $this;
    }

    /**
     * 设置买主
     * @author 鹿文学
     */
    public function setBuyerId($buyerid)
    {
        $this->_buyerid = $buyerid;
        return $this;
    }

    public function setBuyerAccount($buyeraccount)
    {
        $this->_buyeraccount = $buyeraccount;
        return $this;
    }

    /**
     * 设置手续费
     * @author 鹿文学
     */
    public function setPoundage($poundage)
    {
        $this->_poundage = $poundage;
        return $this;
    }

    public function setExtraparam($extraparam)
    {
        $this->_extraparam = $extraparam;
        return $this;
    }

    public function setRoleLevel($rolelevel)
    {
        $this->_roleLevel = $rolelevel;
        return $this;
    }

    public function setCost($cost)
    {
        $this->_cost = $cost;
        return $this;
    }

    public function setCouponRecordId($couponRecordId)
    {
        $this->_coupon_record_id = $couponRecordId;
        return $this;
    }

    public function setTransactionId($transactionid)
    {
        $this->_transaction_id = $transactionid;
        return $this;
    }

    public function setBalanceStatus($balancestatus)
    {
        $this->_balance_status = $balancestatus;
        return $this;
    }

    public function setFeeMoney($feemoney)
    {
        $this->_fee_money=$feemoney;
        return $this;
    }
    public function setLockTime($locktime)
    {
        $this->_lock_time=$locktime;
        return $this;
    }

    public function setOpenUserId($open_user_id)
    {
        $this->_open_user_id = $open_user_id;
        return $this;
    }

    public function setMemberName($member_name)
    {
        $this->_member_name = $member_name;
        return $this;
    }

    public function setDays($days)
    {
        $this->_days = $days;
        return $this;
    }

    public function setFreeDays($free_days)
    {
        $this->_free_days = $free_days;
        return $this;
    }

    public function setModule($module){
        $this->_module = $module;
        return $this;
    }

    //其他数据
    public function getOther()
    {
        return $this->_other;
    }

    public function getGameplayerId()
    {
        return $this->_gameplayerId;
    }

    public function getGameplayerName()
    {
        return $this->_gameplayerName;
    }


    /**
     * 获取游戏充值方式
     * @return type
     */
    public function getPayWay()
    {
        return $this->_payWay;
    }

    //获取退款批次号
    public function getBatchNo()
    {
        return $this->_batchno;
    }

    //单笔数据集
    public function getDetailData()
    {

        return $this->_detaildata;
    }

    /**
     * 获取游戏gid
     * @return type
     */
    public function getGameId()
    {
        return $this->_gameid;
    }

    /**
     * 获取游戏名称
     * @return type
     */
    public function getGameName()
    {
        return $this->_gameName;
    }

    /**
     * 获取游戏appid
     */
    public function getGameAppid()
    {
        return $this->_gameAppid;
    }

    /**
     * 获取游戏区服id
     * @return type
     */
    public function getServerId()
    {
        return $this->_serverid;
    }

    /**
     * 获取游戏区服名称
     * @return type
     */
    public function getServerName()
    {
        return $this->_serverName;
    }

    /**
     * 获取账号uid
     * @return type
     */
    public function getUserId()
    {
        return $this->_userid;
    }

    /**
     * 获取用户账号
     * @return type
     */
    public function getAccount()
    {
        return $this->_account;
    }

    /**
     * 获取用户昵称
     * @return type
     */
    public function getUserNickName()
    {
        return $this->_userNickName;
    }

    /**
     * 获取渠道id
     */
    public function getPromoteId()
    {
        return $this->_promoteid;
    }

    /**
     * 获取渠道名称
     */
    public function getPromoteName()
    {
        return $this->_promoteName;
    }

    /**
     * 获取CP方扩展透传信息
     */
    public function getExtend()
    {
        return $this->_extend;
    }

    /*
    * 获取要插入的表
    */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * 获取充值银行
     * @return type
     */
    public function getBank()
    {
        return $this->_bank;
    }

    /**
     * 获取充值实际金额（除去手续费）
     * @return type
     */
    public function getMoney()
    {
        return $this->_money;
    }

    /**
     * 获取充值游戏币数量
     * @return type
     */
    public function getCoin()
    {
        return $this->_coin;
    }

    /**
     * 获取订单号
     * @return type
     */
    public function getOrderNo()
    {
        return $this->_orderNo;
    }

    /**获取开发商返利比例
     * @return mixed
     */
    public function getRatio()
    {
        return $this->_ratio;
    }

    /**
     * 获取商品价格
     * @return type
     */
    public function getFee()
    {
        return $this->_fee;
    }

    /**
     * 获取商品名称
     * @return type
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * 获取验签方式
     */
    public function getSignType()
    {
        return $this->_signtype;
    }

    /**
     * 获取支付完成后的后续操作接口
     * @return type
     */
    public function getCallback()
    {
        return $this->_callback;
    }

    /**
     * 获取支付完成后的跳转地址
     * @return type
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * 获取商品描述
     * @return type
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * 获取订单的额外参数
     * @return type
     */
    public function getParam()
    {
        return $this->_param;
    }

    /**
     *支付服务类型
     * @return type
     */
    public function getService()
    {
        return $this->_service;
    }

    /**
     *支付异步通知地址
     */
    public function getNotifyUrl()
    {
        return $this->_notifyurl;
    }

    /**
     *支付方法
     */
    public function getPayMethod()
    {
        return $this->_payMethod;
    }

    /**
     *SDK版本苹果安卓
     */
    public function getSdkVersion()
    {
        return $this->_sdkVersion;
    }

    public function getDiscount()
    {
        return $this->_discount;
    }
    public function getDiscount_type(){
        return $this->_discount_type;
    }

    /**
     * 获取小号
     * @author 鹿文学
     */
    public function getSmallId()
    {
        return $this->_smallid;
    }

    public function getSmallAccount()
    {
        return $this->_smallaccount;
    }

    public function getSmallNickname()
    {
        return $this->_smallnickname;
    }

    /**
     * 获取卖家
     * @author 鹿文学
     */
    public function getSellerId()
    {
        return $this->_sellerid;
    }

    public function getSellerAccount()
    {
        return $this->_selleraccount;
    }

    /**
     * 获取买主
     * @author 鹿文学
     */
    public function getBuyerId()
    {
        return $this->_buyerid;
    }

    public function getBuyerAccount()
    {
        return $this->_buyeraccount;
    }

    /**
     * 获取手续费
     * @author 鹿文学
     */
    public function getPoundage()
    {
        return $this->_poundage;
    }

    public function getExtraparam()
    {
        return $this->_extraparam;
    }

    public function getRoleLevel()
    {
        return $this->_roleLevel;
    }

    public function getCost()
    {
        return $this->_cost;
    }

    public function getCouponRecordId()
    {
        return $this->_coupon_record_id;
    }

    public function getTransactionId()
    {
        return $this->_transaction_id;
    }
    public function getBalanceStatus()
    {
        return $this->_balance_status;
    }
    public function getFeeMoney()
    {
        return $this->_fee_money;
    }
    public function getLockTime()
    {
        return $this->_lock_time;
    }

    public function getOpenUserId()
    {
        return $this->_open_user_id;
    }

    public function getMemberName()
    {
        return $this->_member_name;
    }
    public function getDays()
    {
        return $this->_days;
    }
    public function getFreeDays()
    {
        return $this->_free_days;
    }
    public function getModule()
    {
        return $this->_module;
    }
}
