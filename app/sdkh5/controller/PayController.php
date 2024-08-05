<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */

namespace app\sdkh5\controller;

use app\recharge\model\SpendModel;
use app\sdkh5\logic\PayLogic;
use app\sdkh5\validate\PayValidate;
use app\sdkh5\BaseController;
use think\Db;

class PayController extends BaseController
{
    public function _initialize()
    {
        if(!UID){
            return $this->failResult(1003,'账号信息丢失');
        }
    }

    //拉起支付初始化
    public function pay_init(PayLogic $logicPay)
    {
        $postData = $this -> request -> post();
        $validate = new PayValidate();
        $result = $validate -> check($postData);
        if (!$result) {
            return $this -> failResult($validate -> getError());
        }

        $puid = get_user_puid($postData['user_id']);//获取大号id
        if($puid != UID){
            return $this -> failResult('登录状态已丢失，请刷新重新登录');
        }
        //封禁判断-20210713-byh
        //根据game_appid 查询游戏id
        $game_id = Db::table('tab_game')->where('game_appid',$postData['game_appid'])->value('id');
        if(!judge_user_ban_status($postData['promote_id'],$game_id,UID,$postData['equipment_num'],get_client_ip(),$type=3)){
            return $this -> failResult('当前被禁止充值，请联系客服');
        }

        $result = $logicPay -> pay_init($postData);
        if ($result['status'] == 200) {
            return $this -> successResult($result['data'], $result['message']);
        }elseif($result['status'] == -100){//实名充值提示
            return json($result);
        } else {
            return $this -> failResult($result['message']);
        }
    }


    /**
     * @支付宝支付
     *
     * @author: zsl
     * @since: 2020/6/17 15:59
     */
    public function alipay(PayLogic $logicPay)
    {
        $postData = $this -> request -> post();
        $postData['user_id'] = SMALL_UID;
        $result = $logicPay -> alipay($postData);
        return json($result);
    }

    /**
     * @函数或方法说明
     * @微信支付
     * @param PayLogic $logicPay
     *
     * @author: 郭家屯
     * @since: 2020/7/7 17:49
     */
    public function weixinpay(PayLogic $logicPay)
    {
        $postData = $this -> request -> post();
        $postData['user_id'] = SMALL_UID;
        $result = $logicPay -> weixinpay($postData);
        return json($result);
    }

    /**
     * @函数或方法说明
     * @平台币支付
     * @param PayLogic $logicPay
     *
     * @author: 郭家屯
     * @since: 2020/7/7 19:48
     */
    public function platformcoinpay(PayLogic $logicPay){
        $postData = $this -> request -> post();
        $postData['user_id'] = SMALL_UID;
        $result = $logicPay -> platformcoinpay($postData);
        return json($result);
    }

    /**
     * [检查充值状态]
     * @author 郭家屯[gjt]
     */
    public function check_status()
    {
        $order_no = $this->request->param('out_trade_no');
        $pay_where = substr($order_no, 0, 2);
        switch ($pay_where){
            case 'SP':
                $model = new SpendModel();
                $result = $model->where('pay_order_number', $order_no)->field('id,pay_status')->find();
                break;
        }
        if (empty($result)) {
            return json(['code' => 0]);
        } else {
            $result = $result->toArray();
            if ($result['pay_status'] == 1) {
                return json(['code' => 1]);
            } else {
                return json(['code' => 0]);
            }
        }
    }

    /**
     * [生成二维码]
     * @param string $url
     * @param int $level
     * @param int $size
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
        echo $object->png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
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
        $result = Db::table('tab_spend')->field("pay_status,pay_way,game_id")->where($map)->find();
        $this->assign('paystatus', $result['pay_status']);
        $this->assign('payway',$result['pay_way']);
        $this->assign('game_id',$result['game_id']);
        return $this->fetch('pay_success');
    }
}
