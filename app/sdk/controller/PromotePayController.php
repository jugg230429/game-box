<?php
namespace app\sdk\controller;

use app\callback\controller\BaseController;
use PDO;
use think\Db;

class PromotePayController{
    /**
     * 渠道支付同步回调
     */
    public function pay_return(){
        exit('no operate');
    }

    /**
    * 鼎盛渠道支付异步回调
    */
    public function ds_callback(){
        // String result = "failure";
        // try {
        // Map<String,String> requestParam = new HashMap<String,String>();
        // requestParam.put("out_trade_no", out_trade_no);
        // requestParam.put("total_fee", total_fee);
        // requestParam.put("trade_status", trade_status);
        // if(!TextUtils.isBlank(custom)) {
        // requestParam.put("custom", custom);
        // }
        // String m_sign = Md5Utils.getMd5Sign(requestParam, "kljioj*******oiwejfj");
        // if(m_sign.equals(sign)) {
        // //验签成功
        //     if(trade_status.equals("1")) {
        //         //支付成功，商户处理业务逻辑（更新数据库订单状态等）
                                        
        //         result = "success";
        // }
        // }
        $paramArray = [];
        //商家签名
        if(!isset($_REQUEST["sign"]) ){
            exit("failure(param sign not exists)");
        }
        $postSign = $_REQUEST['sign'];
        //商家订单号
        if(!isset($_REQUEST["out_trade_no"]) ){
            exit("failure(param out_trade_no not exists)");
        }
        //校验商户订单号对应订单是否存在
        $spend = Db::table('tab_spend')->where('order_number',$_REQUEST['out_trade_no'])->find();
        if(!$spend || $spend['pay_status'] >0){
            exit("failure(order not exists)");
        }
        $paramArray['out_trade_no'] = $_REQUEST['out_trade_no'];
        //支付金额
        if(!isset($_REQUEST["total_fee"]) ){
            exit("failure(param total_fee not exists)");
        }
        $paramArray['total_fee'] = $_REQUEST['total_fee'];
        //订单状态 1.成功
        if(!isset($_REQUEST["trade_status"]) ){
            exit("failure(param trade_status not exists)");
        }
        $paramArray['trade_status'] = $_REQUEST['trade_status'];
        //商家备注
        if(isset($_REQUEST["custom"]) ){
            $paramArray['custom'] = $_REQUEST["custom"];
        }
        //记录日志
        $log = [
            'config_id' => $spend['promote_param_id'],
            'pay_order_number' => $spend['pay_order_number'],
            'order_number' =>  $paramArray['out_trade_no'],
            'send_content' => json_encode($paramArray),
            'type' => 2,
            'create_time' => date("Y-m-d H:i:s")
        ];
        Db::table('tab_spend_promote_pay_log')->insert($log);
        //进行鼎盛验证签名
        $key = Db::table('tab_spend_promote_param')->where('id',$spend['promote_param_id'])->value('key');
        $promotePay = new \think\PromotePay();
        $sign = $promotePay->dsParamArraySign($paramArray,$key);  //签名
        if($postSign == $sign){
            //验签成功
            if($paramArray['trade_status'] == 1){
                //调用旧支付回调的方法
                $callBack = new BaseController();
                $data = [
                    'out_trade_no' => $spend['pay_order_number'],
                    'trade_no' => $spend['order_number'],
                    'real_amount' => $paramArray['total_fee']
                ];
                $result = $callBack->set_spend($data);
                if(!$result){
                    exit("failure(buyer business process error)");
                }
                exit("success");
            }
            exit("failure(trade_status not correct)");
        }
        exit("failure(Sign verification errors)");
    }

    public function ant_callback(){
        //商家签名
        if(!isset($_REQUEST["sign"]) ){
            exit("failure(param sign not exists)");
        }
        $postSign = $_REQUEST['sign'];
        //支付平台订单号
        if(!isset($_REQUEST["payOrderId"]) ){
            exit("failure(param payOrderId not exists)");
        }
        //商户号
        if(!isset($_REQUEST["mchId"]) ){
            exit("failure(param mchId not exists)");
        }
        //支付产品ID
        if(!isset($_REQUEST["productId"]) ){
            exit("failure(param productId not exists)");
        }
        //商户订单号
        if(!isset($_REQUEST["mchOrderNo"]) ){
            exit("failure(param mchOrderNo not exists)");
        }
        //校验商户订单号对应订单是否存在
        $spend = Db::table('tab_spend')->where('pay_order_number',$_REQUEST['mchOrderNo'])->find();
        if(!$spend || $spend['pay_status'] >0){
            exit("failure(order not exists)");
        }
        //订单金额分
        if(!isset($_REQUEST["amount"]) ){
            exit("failure(param amount not exists)");
        }
        //实际支付金额分
         if(!isset($_REQUEST["income"]) ){
            exit("failure(param income not exists)");
        }
        //订单状态 支付状态,-2:订单已关闭,0-订单生成,1-支付中,2-支付成功,3-业务处理完成,4-已退款（2和3都表示支付成功,3表示支付平台回调商户且返回成功后的状态）
        if(!isset($_REQUEST["status"]) ){
            exit("failure(param status not exists)");
        }
        //支付成功时间
        if(!isset($_REQUEST["paySuccTime"]) ){
            exit("failure(param paySuccTime not exists)");
        }
        //通知类型
        if(!isset($_REQUEST["backType"]) ){
            exit("failure(param backType not exists)");
        }
        //通知请求时间
        if(!isset($_REQUEST["reqTime"]) ){
            exit("failure(param reqTime not exists)");
        }
        //构造验签数组
        $paramArray = [
            "payOrderId" => $_REQUEST['payOrderId'],
            "mchId" => $_REQUEST['mchId'], 
            "productId" => $_REQUEST['productId'],  
            "mchOrderNo" => $_REQUEST['mchOrderNo'], 
            "amount" => $_REQUEST['amount'], 
            "income" => $_REQUEST['income'], 
            "status" => $_REQUEST['status'], 
            "paySuccTime" => $_REQUEST['paySuccTime'], 
            "backType" => $_REQUEST['backType'], 
            "reqTime" => $_REQUEST['reqTime'],
            "sign" => $_REQUEST['sign']
        ];
        //记录日志
        $log = [
            'config_id' => $spend['promote_param_id'],
            'pay_order_number' => $paramArray['mchOrderNo'],
            'order_number' =>  $paramArray['payOrderId'],
            'send_content' => json_encode($paramArray),
            'type' => 2,
            'create_time' => date("Y-m-d H:i:s")
        ];
        Db::table('tab_spend_promote_pay_log')->insert($log);
        unset($paramArray['sign']);
        //进行蚂蚁验证签名
        $key = Db::table('tab_spend_promote_param')->where('id',$spend['promote_param_id'])->value('key');
        $promotePay = new \think\PromotePay();
        $sign = $promotePay->anutParamArraySign($paramArray,$key);  //签名
        if($postSign == $sign){
            //验签成功
            if($paramArray['status'] == 2){
                //回写spend表的order_number字段
                Db::table('tab_spend')->where('id',$spend['id'])->update(['order_number'=>$paramArray['payOrderId']]);
                //调用旧支付回调的方法
                $callBack = new BaseController();
                $data = [
                    'out_trade_no' => $paramArray['mchOrderNo'],
                    'trade_no' => $paramArray['payOrderId'],
                    'real_amount' => $paramArray['income'] / 100
                ];
                $result = $callBack->set_spend($data);
                if(!$result){
                    exit("failure(mch business process error)");
                }
                exit("success");
            }
            exit("failure(status not correct)");
        }
        exit("failure(Sign verification errors)");
    }
}