<?php
namespace app\sdk\controller;

class PromotePayController{
    /**
     * 渠道支付同步回调
     */
    public function pay_return(){
        exit('no operate');
    }

    /**
    * 渠道支付异步回调
    */
    public function pay_callback(){
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
        

        exit('success');
    }
}