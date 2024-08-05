<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-09
 */

namespace app\issueyy\controller;

use app\issue\model\SpendModel;
use app\issueyy\logic\PayLogic;
use app\issueyy\validate\PayValidate;
use cmf\controller\HomeBaseController;
use think\Db;

class PayController extends HomeBaseController
{
    //分发订单初始化
    public function pay_init(PayLogic $logicPay)
    {
        $postData = $this -> request -> post();
        $validate = new PayValidate();
        $result = $validate -> scene('init') -> check($postData);
        if (!$result) {
            return $this -> failResult($validate -> getError());
        }
        $result = $logicPay -> pay_init($postData);
        return json($result);
    }

    public function callback(PayLogic $logicPay)
    {
        $postData = $this -> request -> param();
        $postData['channel_code'] = $this->request->param('channel_code');
        $postData['game_id'] = $this->request->param('game_id');
        $validate = new PayValidate();
        $result = $validate -> scene('callback') -> check($postData);
        if (!$result) {
            return json(['code'=>1000,'msg'=>$validate -> getError()]);
        }
        $result = $logicPay->callback($postData);
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
            case 'FF':
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


}