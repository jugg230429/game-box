<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-08
 */

namespace app\issuesy\logic;

use app\issue\model\IssueGameApplyModel;
use app\issue\model\SpendModel;
use cmf\controller\HomeBaseController;

class PayLogic extends  HomeBaseController
{
    /**
     * @函数或方法说明
     * @分发平台通知
     * @author: 郭家屯
     * @since: 2020/10/23 11:24
     */
    public function pay_ff_notice($spend=[])
    {
        //获取游戏设置信息
        $platform = new \app\issue\model\PlatformModel();
        $platform->where('id',$spend['platform_id']);
        $platform->field('pay_notice_url_sy,controller_name_sy');
        $platformdata = $platform->find();
        if (empty($platformdata)) {
            return false;
        }
        if (empty($platformdata->pay_notice_url_sy)) {
            return false;
        }
        //获取游戏设置信息
        $platformapplymodel = new IssueGameApplyModel();
        $platformapplymodel->field('platform_config,service_config');
        $platformapplymodel->where('game_id',$spend['game_id']);
        $platformapplymodel->where('platform_id',$spend['platform_id']);
        $platformapplymodel->where('open_user_id',$spend['open_user_id']);
        $game_data = $platformapplymodel->find();
        $game_data = json_decode($game_data->service_config,true);
        if(!file_exists(APP_PATH.'issuesy/logic/sdk/Sdk_'.$platformdata['controller_name_sy'].'Logic.php')){
           return false;
        }
        $class = '\app\\issuesy\\logic\\sdk\\Sdk_'.$platformdata['controller_name_sy'].'Logic';
        $logic = new $class();
        $game_data['game_key'] = $logic->get_game_key($game_data);
        $data = array(
                "out_trade_no" => $spend['pay_order_number'],
                'pay_extra' => request()->host(),
                "pay_status" => 1,
                "pay_game_status" => $spend['pay_game_status'],
                "price" => $spend['pay_amount'],
                "user_id" => $spend['platform_openid'],
                "pay_way" => $spend['pay_way']
        );
        $datasign = implode($data);
        $md5_sign = md5($datasign.$game_data['game_key']);
        $data['sign'] = $md5_sign;
        //$result = file_get_contents($platformdata['pay_notice_url_sy']."?".http_build_query($data));
        $result = $this->post($data, $platformdata['pay_notice_url_sy']);
        if ($result == "success"||json_decode($result,true)['status'] == "success") {
            return true;
        } else {
            return false;
        }
    }

    /**
     *post提交数据
     */
    protected function post($param, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}