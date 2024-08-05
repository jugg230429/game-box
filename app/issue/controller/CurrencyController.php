<?php

namespace app\issue\controller;

use app\issue\logic\CurrencyLogic;
use think\Db;

class CurrencyController extends ManagementBaseController
{


    /**
     * @联运币管理
     *
     * @author: zsl
     * @since: 2020/7/15 16:50
     */
    public function index()
    {
        $paytype = Db ::table('tab_spend_payconfig') -> field('name') -> where(['status' => 1]) -> select() -> toarray();
        $this -> assign('paytype', $paytype);
        return $this -> fetch();
    }


    /**
     * @联运币订单列表
     *
     * @author: zsl
     * @since: 2020/7/15 17:07
     */
    public function orders()
    {

        $param = $this -> request -> param();
        $lCurrency = new CurrencyLogic();
        $param['open_user_id'] = OID;
        $lists = $lCurrency -> orders($param);
        $this -> assign('lists', $lists);
        return $this -> fetch();
    }


    /**
     * @提现记录
     *
     * @author: zsl
     * @since: 2020/7/15 17:14
     */
    public function withdraw()
    {

        return $this -> fetch();
    }



    /**
     * @descript author
     * @return mixed
     * @author yyh
     * @since 2020-07-16
     */
    //获取订单号
    public function get_order()
    {
        $order_no = create_out_trade_no('LY_');
        echo json_encode(['order_no' => $order_no]);
    }
    //检查平台币充值结果
    //yyh
    public function check_order_status()
    {
        $order_no = $this -> request -> param('order_no');
        if (empty($order_no)) {
            $this -> error('无订单数据');
        }
        $res = Db ::table('tab_issue_open_user_balance') -> where(['user_id' => OID, 'pay_order_number' => $order_no]) -> find();
        if (request() -> isAjax()) {
            if ($res['pay_status'] == 1) {
                $this -> success('充值成功', cmf_get_domain() . '/issue/currency/check_order_status/order_no/' . $order_no);
            } else {
                $this -> error('未充值成功');
            }
        } else {
            $this -> assign('data', $res);
            if (empty($res)) {
                $this -> error('数据错误');
            } elseif ($res['pay_status'] == 1) {
                return $this -> fetch('pay_success');
            } else {
                return $this -> fetch('pay_failure');
            }
        }
    }

    //生成二维码
    function create_qrcode($url, $level = 3, $size = 4)
    {
        $errorCorrectionLevel = intval($level);//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        vendor('phpqrcode');//导入类库
        $QRcode = new \cmf\phpqrcode\QRcode();//实例化，注意加\
        ob_clean();
        echo $QRcode -> png(base64_decode(base64_decode($url)), false, $errorCorrectionLevel, $matrixPointSize);
    }


}