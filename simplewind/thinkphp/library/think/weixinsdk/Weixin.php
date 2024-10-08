<?php

namespace think\weixinsdk;

use app\promote\logic\CustompayLogic;
use think\Db;
use Think\Exception;


class Weixin
{


    public function weixin_pay($title, $order_no, $pay_amount, $trade_type = "NATIVE", $tt = 1,$wxparam_id=0,$time_expire=0,$pay_promote_id=0,$game_id = 0)
    {

        //官方

        header("Content-type:text/html;charset=utf-8");

        Vendor("wxPayPubHelper.WxPayPubHelper");

        // $data['pay_type']  = "weixin";

        $lCustomPay = new CustompayLogic();

        //使用统一支付接口

        if ($tt == 1) { //扫码
            $customConfig = $lCustomPay -> getPayConfig($pay_promote_id,$game_id, 'wxscan', $pay_amount);
            if(false===$customConfig){
                $pay_set = get_pay_type_set('wxscan');
            }else{
                $pay_set['config'] = $customConfig;
            }
            $notifyy="notify";
            $unifiedOrder = new \UnifiedOrder_pub($pay_set['config']['appid'], $pay_set['config']['pid'], $pay_set['config']['key']);

        } else if($tt==2) {     //sdk,公众号

            $customConfig = $lCustomPay -> getPayConfig($pay_promote_id,$game_id, 'wxscan', $pay_amount);
            if(false===$customConfig){
                $pay_set = get_pay_type_set('wxscan');
            }else{
                $pay_set['config'] = $customConfig;
            }
            $notifyy="notify2";
            $unifiedOrder = new \UnifiedOrder_pub($pay_set['config']['appid'], $pay_set['config']['pid'], $pay_set['config']['key']);
        }else{

            $customConfig = $lCustomPay -> getPayConfig($pay_promote_id,$game_id, 'wxapp', $pay_amount);
            if(false===$customConfig){
                if($wxparam_id > 0){
                    $param = Db::table('tab_spend_wxparam')->field('appid,partner,key')->where('id',$wxparam_id)->find();
                    $unifiedOrder = new \UnifiedOrder_pub($param['appid'],$param['partner'],$param['key']);
                }else{
                    $pay_set = get_pay_type_set('wxapp');
                    $unifiedOrder = new \UnifiedOrder_pub($pay_set['config']['appid'], $pay_set['config']['pid'], $pay_set['config']['key']);
                }
            }else{
                $pay_set['config'] = $customConfig;
                $unifiedOrder = new \UnifiedOrder_pub($pay_set['config']['appid'], $pay_set['config']['pid'], $pay_set['config']['key']);
            }
            $notifyy="notify3";//3 app支付
        }

        // $des='平台币充值';

        $unifiedOrder->setParameter("body", $title);//商品描述

        //自定义订单号，此处仅作举例

        $timeStamp = time();

        $unifiedOrder->setParameter("out_trade_no", $order_no);//商户订单号

        $unifiedOrder->setParameter("total_fee", $pay_amount * 100);//总金额

        if($time_expire > 0){
            $unifiedOrder->setParameter("time_expire", $time_expire);//到期时间
        }

        $unifiedOrder->setParameter("notify_url", cmf_get_domain(). "/callback/Notify/wxpay_callback/method/".$notifyy);//通知地址

        $unifiedOrder->setParameter("trade_type", $trade_type);//交易类型

        $unifiedOrder->setParameter("product_id", $order_no);//商品ID
        if($trade_type=="MWEB"){
            $scene_info['h5_info']=['type'=>'Wap','wap_url'=>cmf_get_domain(),'wap_name'=>'充值'];
            $unifiedOrder->setParameter("scene_info", json_encode($scene_info));//场景信息

        }

        //获取统一支付接口结果

        $unifiedOrderResult = $unifiedOrder->getResult($tt);

        //商户根据实际情况设置相应的处理流程

        if ($unifiedOrderResult["return_code"] == "FAIL") {

            //商户自行增加处理流程

            // echo base64_encode(json_encode(array('status' => 0, 'return_msg' => $unifiedOrderResult['return_msg'])));
            return json_encode(array('status' => 0, 'return_msg' => $unifiedOrderResult['return_msg']));

        } elseif ($unifiedOrderResult["result_code"] == "FAIL") {

            //商户自行增加处理流程

            // echo "错误代码：".$unifiedOrderResult['err_code']."<br>";

            // echo base64_encode(json_encode(array('status' => 0, 'return_msg' => $unifiedOrderResult['err_code_des'])));
            return json_encode(array('status' => 0, 'return_msg' => $unifiedOrderResult['err_code_des']));

        } elseif ($unifiedOrderResult["code_url"] != NULL) {


            //从统一支付接口获取到code_url

            $code_url = $unifiedOrderResult["code_url"];

            //商户自行增加处理流程

            if ($unifiedOrderResult['return_code'] !== "SUCCESS") {

                \think\Log::record($unifiedOrderResult['msg']);

                $html = '<div class="d_body" style="height:px;">

                    <div class="d_content">

                        <div class="text_center">' . $unifiedOrderResult["return_code"] . '</div>

                    </div>

                    </div>';

            } else {

                return json_encode(array("status" => 1, 'url' => $unifiedOrderResult['code_url']));

            }


        } else {
            file_put_contents(dirname(__FILE__).'/$unifiedOrderResult.txt',json_encode($unifiedOrderResult));

            if ($trade_type == "APP") {

                $app_data['appid'] = $unifiedOrderResult['appid'];

                $app_data['partnerid'] = $unifiedOrderResult['mch_id'];

                $app_data['prepayid'] = $unifiedOrderResult['prepay_id'];

                $app_data['noncestr'] = $unifiedOrder->createNoncestr();

                $app_data['timestamp'] = time();

                $app_data['package'] = "Sign=WXPay";

                $sign = $unifiedOrder->getSign($app_data);

                return json_encode(array("status" => 1, 'appid' => $unifiedOrderResult['appid'], 'mch_id' => $unifiedOrderResult['mch_id'], 'prepay_id' => $unifiedOrderResult['prepay_id'], 'time' => $app_data['timestamp'], 'noncestr' => $app_data['noncestr'], 'sign' => $sign));

            } else if ($trade_type == "MWEB") {
                return json_encode(array("status" => 1,  'mweb_url' => $unifiedOrderResult['mweb_url']));

            }


        }


    }


    public function weixin_jsapi($title, $order_no, $pay_amount, $jsApi, $openid = "",$time_expire=0)
    {

        //官方

        header("Content-type:text/html;charset=utf-8");

        Vendor("WxPayPubHelper.WxPayPubHelper");
        $pay_set = get_pay_type_set('wxscan');
        $unifiedOrder = new \UnifiedOrder_pub($pay_set['config']['appid'], $pay_set['config']['pid'], $pay_set['config']['key']);

        // $des='平台币充值';

        $unifiedOrder->setParameter("body", $title);//商品描述

        //自定义订单号，此处仅作举例

        $timeStamp = time();

        $unifiedOrder->setParameter("openid", $openid);//商品描述

        $unifiedOrder->setParameter("out_trade_no", $order_no);//商户订单号

        $unifiedOrder->setParameter("total_fee", $pay_amount * 100);//总金额

        if($time_expire > 0){
            $unifiedOrder->setParameter("time_expire", $time_expire);//到期时间
        }

        $unifiedOrder->setParameter("notify_url", cmf_get_domain() . "/callback/Notify/wxpay_callback/method/notify");//通知地址

        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型

        $unifiedOrder->setParameter("product_id", $order_no);//商品ID

        $prepay_id = $unifiedOrder->getPrepayId();

        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        return $jsApiParameters;


    }


    /**
     * 微信退款接口
     * @param $out_trade_no 商户订单号
     * @param $out_refund_no 商户退款单号
     * @param $total_fee 订单金额
     * @param $refund_fee 退款金额
     * @param $op_user_id    操作员帐号, 默认为商户号
     */
    public function weixin_Refund_pub($out_trade_no, $out_refund_no, $total_fee, $refund_fee, $op_user_id)
    {
        //官方
        header("Content-type:text/html;charset=utf-8");
        Vendor("WxPayPubHelper.WxPayPubHelper");
        // $unifiedOrder = new \Refund_pub(C('wei_xin_app.email'), C('wei_xin_app.partner'), C('wei_xin_app.key'));
        $unifiedOrder = new \Refund_pub(C('wei_xin.email'), C('wei_xin.partner'), C('wei_xin.key'));

        $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
        $unifiedOrder->setParameter("out_refund_no", $out_refund_no);//商户退款单号
        $unifiedOrder->setParameter("total_fee", $total_fee * 100);//通知地址
        $unifiedOrder->setParameter("refund_fee", $refund_fee * 100);//通知地址
        $unifiedOrder->setParameter("op_user_id", $op_user_id);//交易类型
        //获取退款接口结果
        $unifiedOrderResult = $unifiedOrder->getResult();
        if ($unifiedOrderResult["return_code"] == "FAIL") {
            //商户自行增加处理流程
            return json_encode(array('status' => 0, 'msg' => $unifiedOrderResult['return_msg']));
        } elseif ($unifiedOrderResult["result_code"] == "FAIL") {
            //商户自行增加处理流程
            // echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
            return json_encode(array('status' => 0, 'msg' => $unifiedOrderResult['err_code_des']));

        } else {
            //商户自行增加处理流程
            if ($unifiedOrderResult['return_code'] !== "SUCCESS") {
                return json_encode(array("status" => 0, 'msg' => '未知错误'));
                \think\Log::record($unifiedOrderResult['msg']);
            } else {
                return json_encode(array("status" => 1, 'msg' => '成功'));
            }
        }

    }


    /**
     * 微信退款查询接口
     * @param $out_trade_no 商户订单号
     * @param $out_refund_no 商户退款单号
     * @param $total_fee 订单金额
     * @param $refund_fee 退款金额
     * @param $op_user_id    操作员帐号, 默认为商户号
     */
    public function weixin_refundquery($out_trade_no)
    {
        //官方
        header("Content-type:text/html;charset=utf-8");
        Vendor("WxPayPubHelper.WxPayPubHelper");
        // $unifiedOrder = new \RefundQuery_pub(C('wei_xin_app.email'), C('wei_xin_app.partner'), C('wei_xin_app.key'));
        $unifiedOrder = new \RefundQuery_pub(C('wei_xin.email'), C('wei_xin.partner'), C('wei_xin.key'));

        $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
        //获取退款接口结果
        $unifiedOrderResult = $unifiedOrder->getResult();
        if ($unifiedOrderResult["return_code"] == "FAIL") {
            //商户自行增加处理流程
            return $unifiedOrderResult['return_msg'];
        } elseif ($unifiedOrderResult["result_code"] == "FAIL") {
            //商户自行增加处理流程
            return $unifiedOrderResult['err_code_des'];

        } else {
            return $unifiedOrderResult['return_code'];
        }

    }
		
		/**
     * 微信查询接口
     * @param $out_trade_no 商户订单号
		 * @return string
		 * @author 鹿文学
     */
    public function weixin_orderquery($out_trade_no) {
        header("Content-type:text/html;charset=utf-8");
        Vendor("WxPayPubHelper.WxPayPubHelper");
        $orderquery = new \OrderQuery_pub(C('wei_xin.email'), C('wei_xin.partner'),C('wei_xin.key'));
        $orderquery->setParameter('out_trade_no',$out_trade_no);//商户订单号
        $orderqueryresult = $orderquery->getResult();
        if($orderqueryresult['return_code']=='FAIL') {
            return $orderqueryresult['return_msg'];
        } elseif ($orderqueryresult["result_code"] == "FAIL") {
            //商户自行增加处理流程
            return $orderqueryresult['err_code_des'];
        } elseif ($orderqueryresult['trade_state'] == 'FAIL') {
            return '支付失败';
        } else {
            if($orderqueryresult['trade_state']=='SUCCESS') {
                return $orderqueryresult['out_trade_no'];
            } else {
                return $orderqueryresult['trade_state_desc'];
            }
        }
    }


    /**
     * @函数或方法说明
     * @微信自动打款
     * @param $out_trade_no
     * @param $money
     *
     * @author: 郭家屯
     * @since: 2020/9/27 17:11
     */
    public function weixin_transfers($title, $order_no, $pay_amount, $openid = "",$time_expire=0)
    {
        header("Content-type:text/html;charset=utf-8");
//        Vendor("WxPayPubHelper.WxPayPubHelper");
        Vendor("wxPayPubHelper.WxPayPubHelper");
        $pay_set = get_pay_type_set('wxscan');
        $unifiedOrder = new \UnifiedTransfers_pub($pay_set['config']['appid'], $pay_set['config']['pid'], $pay_set['config']['key']);
        $unifiedOrder->setParameter("desc", $title);//商品描述

        //自定义订单号，此处仅作举例

        $timeStamp = time();

        $unifiedOrder->setParameter("partner_trade_no", $order_no);//商户订单号

        $unifiedOrder->setParameter("amount", $pay_amount * 100);//总金额

        $unifiedOrder->setParameter("openid", $openid);//微信opendid

        //$unifiedOrder->setParameter("product_id", $order_no);//商品ID

        //获取统一支付接口结果
        $unifiedOrderResult = $unifiedOrder->getResult();

        //商户根据实际情况设置相应的处理流程
        if ($unifiedOrderResult["result_code"] == "SUCCESS") {

            //商户自行增加处理流程

            // echo base64_encode(json_encode(array('status' => 0, 'return_msg' => $unifiedOrderResult['return_msg'])));
            return array('code' => 1, 'msg' => $unifiedOrderResult['payment_no']);

        } else{

            //商户自行增加处理流程

            // echo "错误代码：".$unifiedOrderResult['err_code']."<br>";

            // echo base64_encode(json_encode(array('status' => 0, 'return_msg' => $unifiedOrderResult['err_code_des'])));
            return array('code' => 0, 'msg' => $unifiedOrderResult['err_code_des']);

        }
    }


}