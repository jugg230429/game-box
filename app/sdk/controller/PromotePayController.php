<?php
namespace app\sdk\controller;

use AlibabaCloud\Client\Support\Sign;
use app\callback\controller\BaseController;
use PDO;
use think\Db;
use think\Request;

class PromotePayController{
     /**
     * 测试接口访问
     */
    public function netstat(){
        exit('pay success');
    }
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
        $log = Db::table('tab_spend_promote_pay_log')->where('order_number',$_REQUEST['out_trade_no'])->find();
        if(!$log){
            exit("failure(log not exists)");
        }
        $table_name = $log['table'];
        $spend = Db::table($table_name)->where('order_number',$_REQUEST['out_trade_no'])->find();
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
            'table' => $table_name,
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
                //处理业务回调
                $this->sepndCallback($spend['pay_order_number'],$spend['order_number'],$paramArray['total_fee'],$table_name);
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
        //通过查询支付日志表订单是否存在
        $log = Db::table('tab_spend_promote_pay_log')->where('pay_order_number',$_REQUEST['mchOrderNo'])->find();
        if(!$log){
            exit("failure(log not exists)");
        }
        $table_name = $log['table'];
        $spend =  Db::table($table_name)->where('pay_order_number',$_REQUEST['mchOrderNo'])->find();
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
            'table' => $table_name,
            'send_content' => json_encode($paramArray),
            'type' => 2,
            'create_time' => date("Y-m-d H:i:s")
        ];
        Db::table('tab_spend_promote_pay_log')->insert($log);
        unset($paramArray['sign']);
        //进行蚂蚁验证签名
        $key = Db::table('tab_spend_promote_param')->where('id',$spend['promote_param_id'])->value('key');
        $promotePay = new \think\PromotePay();
        $sign = $promotePay->antParamArraySign($paramArray,$key);  //签名
        if($postSign == $sign){
            //验签成功
            if($paramArray['status'] == 2){
                 //回写spend表的order_number字段
                 Db::table($table_name)->where('id',$spend['id'])->update(['order_number'=>$paramArray['payOrderId']]);

                //处理业务回调
                $this->sepndCallback($spend['pay_order_number'],$spend['order_number'],$paramArray['income'] / 100,$table_name);
                exit("success");
            }
            exit("failure(status not correct)");
        }
        exit("failure(Sign verification errors)");
    }

    public function rainbow_callback(){    
        //商户号
        if(!isset($_REQUEST["pid"]) ){
            exit("failure(param pid not exists)");
        }
        //易支付平台订单号
        if(!isset($_REQUEST["trade_no"]) ){
            exit("failure(param trade_no not exists)");
        }
        //我们订单号
        if(!isset($_REQUEST["out_trade_no"]) ){
            exit("failure(param out_trade_no not exists)");
        }

        //通过查询支付日志表订单是否存在
        $log = Db::table('tab_spend_promote_pay_log')->where('pay_order_number',$_REQUEST['out_trade_no'])->find();
        if(!$log){
            exit("failure(log not exists)");
        }
        $table_name = $log['table'];
        $spend =  Db::table($table_name)->where('pay_order_number',$_REQUEST['out_trade_no'])->find();
        if(!$spend || $spend['pay_status'] >0){
            exit("failure(order not exists)");
        }
        //支付方式
        if(!isset($_REQUEST["type"]) ){
            exit("failure(param type not exists)");
        }
        //实际支付金额分
        if(!isset($_REQUEST["money"]) ){
            exit("failure(param money not exists)");
        }     
        //订单状态 只有TRADE_SUCCESS是成功
        if(!isset($_REQUEST["trade_status"]) ){
            exit("failure(param trade_status not exists)");
        }
        //商家签名
        if(!isset($_REQUEST["sign"]) ){
            exit("failure(param sign not exists)");
        }
        $postSign = $_REQUEST['sign'];
        //签名类型
        if(!isset($_REQUEST["sign_type"]) ){
            exit("failure(param sign_type not exists)");
        }

        //构造验签数组
        $paramArray = [
            "pid" => $_REQUEST['pid'],
            "trade_no" => $_REQUEST['trade_no'], 
            "out_trade_no" => $_REQUEST['out_trade_no'],  
            "type" => $_REQUEST['type'], 
            "money" => $_REQUEST['money'], 
            "trade_status" => $_REQUEST['trade_status'], 
            "sign" => $_REQUEST['sign'], 
            "sign_type" => $_REQUEST['sign_type']
        ];
        //记录日志
        $log = [
            'config_id' => $spend['promote_param_id'],
            'pay_order_number' => $paramArray['out_trade_no'],
            'order_number' =>  $paramArray['trade_no'],
            'send_content' => json_encode($paramArray),
            'table' => $table_name,
            'type' => 2,
            'create_time' => date("Y-m-d H:i:s")
        ];
        Db::table('tab_spend_promote_pay_log')->insert($log);
        //进行蚂蚁验证签名
        $key = Db::table('tab_spend_promote_param')->where('id',$spend['promote_param_id'])->value('key');
        $promotePay = new \think\PromotePay();
        $sign = $promotePay->rainbowParamArraySign($paramArray,$key);  //签名
        if($postSign == $sign){
            //验签成功
            if($paramArray['trade_status'] == 'TRADE_SUCCESS'){
                 //回写spend表的order_number字段
                 Db::table($table_name)->where('id',$spend['id'])->update(['order_number'=>$paramArray['trade_no']]);

                //处理业务回调
                $this->sepndCallback($spend['pay_order_number'],$spend['order_number'],$paramArray['money'],$table_name);
                exit("success");
            }
            exit("failure(status not correct)");
        }
        exit("failure(Sign verification errors)");
    }


    public function hipay_callback(){  

        $return = Request()->post();
        if($return['code'] != 1){
             //记录日志
            $log = [
                'config_id' => 0,
                'pay_order_number' => 'hipay',
                'order_number' =>  'hipay',
                'send_content' => json_encode($return),
                'table' => 'hipay',
                'type' => 2,
                'create_time' => date("Y-m-d H:i:s")
            ];
            Db::table('tab_spend_promote_pay_log')->insert($log);
            exit("failure(param code status not error)");
        }
        $data = str_replace("&quot;", '"', $return['data']);
        $info = json_decode($data,true);
        //我们订单号
        if(!isset($info["orderid"]) ){
            exit("failure(param orderid not exists)");
        }
        //通过查询支付日志表订单是否存在
        $log = Db::table('tab_spend_promote_pay_log')->where('pay_order_number',$info["orderid"])->find();
        if(!$log){
            exit("failure(log not exists)");
        }
        $table_name = $log['table'];
        $spend =  Db::table($table_name)->where('pay_order_number',$info["orderid"])->find();
        if(!$spend || $spend['pay_status'] >0){
            exit("failure(order not exists)");
        }
        
        //签名
        if(!isset($info["sign"]) ){
            exit("failure(param sign not exists)");
        }
        //加密签名
        if(!isset($info["resign"]) ){
            exit("failure(param resign not exists)");
        }
        //实际支付金额分
        if(!isset($info["amount"]) ){
            exit("failure(param amount not exists)");
        }     
        //订单状态 只有5是成功
        if(!isset($info["state"]) ){
            exit("failure(param state not exists)");
        }

        //记录日志
        $log = [
            'config_id' => $spend['promote_param_id'],
            'pay_order_number' => $spend['pay_order_number'],
            'order_number' =>  $spend['order_number'],
            'send_content' => json_encode($return),
            'table' => $table_name,
            'type' => 2,
            'create_time' => date("Y-m-d H:i:s")
        ];
        Db::table('tab_spend_promote_pay_log')->insert($log);
      
        //进行验证签名
        //resign=md5(sign + apikey)
        $apiKey = Db::table('tab_spend_promote_param')->where('id',$spend['promote_param_id'])->value('key');
        $sign = md5($info['sign'] . $apiKey);
        if($info['resign'] == $sign){
            //验签成功
            if($info['state'] == '5'){
                //处理业务回调
                $this->sepndCallback($spend['pay_order_number'],$spend['order_number'],$info['amount'],$table_name);
                exit("success");
            }
            exit("failure(state not correct)");
        }
        exit("failure(Sign verification errors)");
    }

    public function huiju_callback(){   
        $get = Request()->get();
        //记录日志
        $log = [
            'config_id' => 0,
            'pay_order_number' => 'huiju',
            'order_number' =>  'huiju',
            'send_content' => json_encode($get),
            'table' => 'huiju',
            'type' => 2,
            'create_time' => date("Y-m-d H:i:s")
        ];
        Db::table('tab_spend_promote_pay_log')->insert($log); 
        //商户号
        if(!isset($_REQUEST["pid"]) ){
            exit("failure(param pid not exists)");
        }
        //支付平台订单号
        if(!isset($_REQUEST["trade_no"]) ){
            exit("failure(param trade_no not exists)");
        }
        //我们订单号
        if(!isset($_REQUEST["out_trade_no"]) ){
            exit("failure(param out_trade_no not exists)");
        }
        //通过查询支付日志表订单是否存在
        $log = Db::table('tab_spend_promote_pay_log')->where('pay_order_number',$_REQUEST["out_trade_no"])->find();
        if(!$log){
            exit("failure(log not exists)");
        }
        $table_name = $log['table'];
        $spend =  Db::table($table_name)->where('pay_order_number',$_REQUEST["out_trade_no"])->find();
        if(!$spend || $spend['pay_status'] >0){
            exit("failure(order not exists)");
        }
        //支付方式
        if(!isset($_REQUEST["type"]) ){
            exit("failure(param type not exists)");
        }
        //名称
        if(!isset($_REQUEST["name"]) ){
            exit("failure(param name not exists)");
        }
        //实际支付金额分
        if(!isset($_REQUEST["money"]) ){
            exit("failure(param money not exists)");
        }     
        //订单状态 只有TRADE_SUCCESS是成功
        if(!isset($_REQUEST["trade_status"]) ){
            exit("failure(param trade_status not exists)");
        }
        //商家签名
        if(!isset($_REQUEST["sign"]) ){
            exit("failure(param sign not exists)");
        }
        $postSign = $_REQUEST['sign'];
        //签名类型
        if(!isset($_REQUEST["sign_type"]) ){
            exit("failure(param sign_type not exists)");
        }

        //构造验签数组
        $paramArray = [
            "pid" => $_REQUEST['pid'],
            "trade_no" => $_REQUEST['trade_no'], 
            "out_trade_no" => $_REQUEST['out_trade_no'],  
            "type" => $_REQUEST['type'], 
            "name" => $_REQUEST['name'], 
            "money" => $_REQUEST['money'], 
            "trade_status" => $_REQUEST['trade_status'], 
        ];
        //记录日志
        $log = [
            'config_id' => $spend['promote_param_id'],
            'pay_order_number' => $paramArray['out_trade_no'],
            'order_number' =>  $paramArray['trade_no'],
            'send_content' => json_encode($paramArray),
            'table' => $table_name,
            'type' => 2,
            'create_time' => date("Y-m-d H:i:s")
        ];
        Db::table('tab_spend_promote_pay_log')->insert($log);
        //进行蚂蚁验证签名
        $key = Db::table('tab_spend_promote_param')->where('id',$spend['promote_param_id'])->value('key');
        $promotePay = new \think\PromotePay();
        $sign = $promotePay->huijuParamArraySign($paramArray,$key);  //签名
        if($postSign == $sign){
            //验签成功
            if($paramArray['trade_status'] == 'TRADE_SUCCESS'){
                //回写spend表的order_number字段
                Db::table('tab_spend')->where('id',$spend['id'])->update(['order_number'=>$paramArray['trade_no']]);
                //处理业务回调
                $this->sepndCallback($spend['pay_order_number'],$spend['order_number'],$_REQUEST["money"],$table_name);
                exit("success");
            }
            exit("failure(status not correct)");
        }
        exit("failure(Sign verification errors)");
    }

    public function pt_callback(){   
        $body = file_get_contents("php://input");
        $body = $this->pt_str_change($body);  //接收到的数据转换为数组
        //记录日志
        $log = [
            'config_id' => 0,
            'pay_order_number' => 'pt',
            'order_number' =>  'pt',
            'send_content' => json_encode($body),
            'table' => 'pt',
            'type' => 2,
            'create_time' => date("Y-m-d H:i:s")
        ];
        Db::table('tab_spend_promote_pay_log')->insert($log); 

        //版本号
        if(!isset($body["version"]) ){
            exit("failure(param version not exists)");
        }
        //商户号
        if(!isset($body["partnerid"]) ){
            exit("failure(param partnerid not exists)");
        }
        //支付平台订单号
        if(!isset($body["orderno"]) ){
            exit("failure(param orderno not exists)");
        }
        //我们订单号
        if(!isset($body["partnerorderid"]) ){
            exit("failure(param partnerorderid not exists)");
        }
        //通过查询支付日志表订单是否存在
        $log = Db::table('tab_spend_promote_pay_log')->where('pay_order_number',$body["partnerorderid"])->find();
        if(!$log){
            exit("failure(log not exists)");
        }
        $table_name = $log['table'];
        $spend =  Db::table($table_name)->where('pay_order_number',$body["partnerorderid"])->find();
        if(!$spend || $spend['pay_status'] >0){
            exit("failure(order not exists)");
        }
        //支付方式
        if(!isset($body["paytype"]) ){
            exit("failure(param paytype not exists)");
        }
        //message
        if(!isset($body["message"]) ){
            exit("failure(param message not exists)");
        }
        //实际支付金额分
        if(!isset($body["payamount"]) ){
            exit("failure(param payamount not exists)");
        }     
        //订单状态 只有TRADE_SUCCESS是成功
        if(!isset($body["orderstatus"]) ){
            exit("failure(param orderstatus not exists)");
        }
        //商家签名
        if(!isset($body["sign"]) ){
            exit("failure(param sign not exists)");
        }
        $postSign = $body['sign'];

        
        //构造验签数组
        $paramArray = [
            "version" => $body['version'],
            "partnerid" => $body['partnerid'],
            "partnerorderid" => $body['partnerorderid'], 
            "payamount" => $body['payamount'],  
            "orderstatus" => $body['orderstatus'], 
            "orderno" => $body['orderno'], 
            "okordertime" => $body['okordertime'], 
            "paytype" => $body['paytype'], 
            "sign" => $body['sign'], 
            "message" => $body['message'], 
        ];
        //记录日志
        $log = [
            'config_id' => $spend['promote_param_id'],
            'pay_order_number' => $paramArray['partnerorderid'],
            'order_number' =>  $paramArray['orderno'],
            'send_content' => json_encode($paramArray),
            'table' => $table_name,
            'type' => 2,
            'create_time' => date("Y-m-d H:i:s")
        ];
        Db::table('tab_spend_promote_pay_log')->insert($log);
        //进行验证签名
        $key = Db::table('tab_spend_promote_param')->where('id',$spend['promote_param_id'])->value('key');
        $promotePay = new \think\PromotePay();
        $sign = $promotePay->ptParamArraySign($paramArray,$key);  //签名
        if($postSign == $sign){
            //验签成功
            if($paramArray['orderstatus'] == 1 || $paramArray['orderstatus'] == 4){
                //回写spend表的order_number字段
                Db::table('tab_spend')->where('id',$spend['id'])->update(['order_number'=>$paramArray['orderno']]);
                //处理业务回调
                $this->sepndCallback($spend['pay_order_number'],$spend['order_number'],$body["payamount"] / 100,$table_name);
                exit("success");
            }
            exit("failure(status not correct)");
        }
        exit("failure(Sign verification errors)");
    }
    

    function sepndCallback($pay_order_number,$order_number,$acctual_pay,$table_name){
         //调用旧支付回调的方法
         $callBack = new BaseController();
         $data = [
             'out_trade_no' => $pay_order_number,
             'trade_no' => $order_number,
             'real_amount' => $acctual_pay
         ];

        //判断$table_name分开处理
        if($table_name == 'tab_spend'){
            $result = $callBack->set_spend($data);
            if(!$result){
                exit("failure(spend business process error)");
            }
        }
        if($table_name == 'tab_spend_balance'){
            $result = $callBack->set_deposit($data);
            if(!$result){
                exit("failure(balance business process error)");
            }
        }
        if($table_name == 'tab_user_member'){
            $result = $callBack->set_member($data);
            if(!$result){
                exit("failure(member business process error)");
            }
        }
    }

    function pt_str_change($data)
    {
        if ($data == null) return $data;
        //去除一个字符串反斜杠，
        $data = stripslashes($data);
        //去除一个字符串两端空格，
        $data = trim($data);
        //解码
        $data = json_decode($data, true);
        return $data;
    }
}