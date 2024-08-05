<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\app\controller;


use think\Db;

class PayController extends BaseController
{
    /**
     * [支付成功通知]
     * @author 郭家屯[gjt]
     */
    public function pay_success()
    {
        $orderno = $this->request->param('orderno');
        $out_trade_no = $this->request->param('out_trade_no');
        $orderno = empty($orderno) ? $out_trade_no : $orderno;
        $pay_where = substr($orderno, 0, 2);
        $Scheme = cmf_get_option('app_set')['scheme']?:'mengchuang';
        $map['pay_order_number'] = $orderno;
        switch ($pay_where) {
            case 'MC':
                $result = Db::table('tab_user_member')->field("pay_status,pay_way")->where($map)->find();
                break;
            case 'PF':
                $result = Db::table('tab_spend_balance')->field('pay_status,pay_way')->where($map)->find();
                break;
            case 'PB':
                $result = Db::table('tab_spend_bind')->field('pay_status,pay_way')->where($map)->find();
                break;
        }
        $this->assign('paystatus', $result['pay_status']);
        $this->assign('pay_way',$result['pay_way']);
        $this->assign('Scheme', $Scheme);
        return $this->fetch();
    }
}