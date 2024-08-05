<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\api\controller;


use cmf\controller\HomeBaseController;
use think\Request;
use think\weixinsdk\Weixin;
class PayController extends HomeBaseController
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    /**
     * [微信内公众号支付]
     * @author 郭家屯[gjt]
     */
    public function get_wx_code()
    {
        $param = $this->request->param();
        switch ($param['weixin_pay_type']){
            case 1:
                $data = $this->get_ptb_pay($param);
                break;
            case 2:
                $data = $this->get_trade_pay($param);
                break;
            case 3:
                $data = $this->get_continue_trade_pay($param);
                break;
            case 4:
                $data = $this->get_promote_balance($param);
                break;
            case 5:
                $data = $this->get_member_pay($param);
                break;
            case 6:
                $data = $this->get_yygame_pay($param);
                break;
            case 7:
                $data = $this->get_ff_yygame_pay($param);
                break;
            default:
                $this->error("支付方式错误");
        }
        if($data['err'] == 1){
            $this->error($data['msg']);
        }
        Vendor("wxPayPubHelper.WxPayPubHelper");
        // 使用jsapi接口

        $pay_set = get_pay_type_set('wxscan');
        $wx_config = get_user_config_info('wechat');
        $jsApi = new \JsApi_pub($wx_config['appsecret'], $pay_set['config']['appid'], $pay_set['config']['key']);
        //获取code码，以获取openid
        $openid = session("wechat_token.openid");
        $weixn = new Weixin();
        $amount = $data['pay_amount'];
        $out_trade_no = $data['pay_order_number'];
        $is_pay = $weixn->weixin_jsapi($data['title'], $out_trade_no, $amount, $jsApi, $openid);
        $this->assign('jsApiParameters', $is_pay);
        $this->assign('hostdomain', $_SERVER['HTTP_HOST']);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @平台币充值
     * @author: 郭家屯
     * @since: 2020/7/11 11:38
     */
    protected function get_ptb_pay($data=[])
    {
        $logic = new \app\mobile\logic\PayLogic();
        $data = $logic->get_ptb_pay($data);
        return $data;
    }


    /**
     * @函数或方法说明
     * @交易订单支付
     * @author: 郭家屯
     * @since: 2020/7/11 11:38
     */
    protected function get_trade_pay($data=[])
    {
        $logic = new \app\mobile\logic\PayLogic();
        $data = $logic->get_trade_pay($data);
        return $data;
    }

    /**
     * @函数或方法说明
     * @重新调起交易订单支付
     * @author: 郭家屯
     * @since: 2020/7/11 11:38
     */
    protected function get_continue_trade_pay($data=[])
    {
        $logic = new \app\mobile\logic\PayLogic();
        $data = $logic->get_continue_trade_pay($data);
        return $data;
    }

    /**
     * @函数或方法说明
     * @推广员平台币充值
     * @author: 郭家屯
     * @since: 2020/7/11 10:29
     */
    protected function get_promote_balance($data=[])
    {
        $logic = new \app\channelwap\logic\PayLogic();
        $data = $logic->get_promote_balance($data);
        return $data;
    }

    /**
     * @函数或方法说明
     * @尊享卡支付
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/8/17 16:54
     */
    protected function get_member_pay($data=[])
    {
        $logic = new \app\mobile\logic\PayLogic();
        $data = $logic->get_member_pay($data);
        return $data;
    }

    /**
     * @函数或方法说明
     * @页游扫码公众号支付
     * @author: 郭家屯
     * @since: 2020/9/25 14:50
     */
    protected function get_yygame_pay($data=[])
    {
        $logic = new \app\mobile\logic\PayLogic();
        $data = $logic->get_yygame_pay($data);
        return $data;
    }

    protected function get_ff_yygame_pay($data=[])
    {
        $logic = new \app\issueyy\logic\PayLogic();
        $data = $logic->get_ff_yygame_pay($data);
        return $data;
    }

}