<?php

namespace app\promote\logic;

use app\promote\model\PromoteapplyModel;
use app\promote\model\PromoteModel;

/**
 * @渠道自定义支付
 *
 * @Class CustompayLogic
 *
 * @package app\promote\logic
 */
class CustompayLogic
{

    private $promoteInfo;

    public function __construct()
    {
        $this -> promoteInfo = [];
    }


    /**
     * @获取渠道自定义支付
     *
     * @param $promote_id : 推广员id
     * @param $game_id : 游戏id
     * @param $pay_type : 支付方式 zfb wxscan wxapp
     *
     * @author: zsl
     * @since: 2021/1/26 14:43
     */
    public function getPayConfig($promote_id, $game_id, $pay_type, $pay_amount)
    {
        //检查渠道是否存在
        $result = $this -> getPromoteInfo($promote_id);
        if (false === $result) {
            return false;
        }
        //检查是否开启自定义通道
        if (false === $this -> checkIsCustomPay($this -> promoteInfo['id'])) {
            return false;
        }
        //检查渠道是否申请游戏
        if (!empty($game_id)) {
            if (false === $this -> checkApplyGame($promote_id, $game_id)) {
                return false;
            }
        }
        //检查支付通道开启状态
        if (false === $this -> checkPayStatus($this -> promoteInfo['id'], $pay_type)) {
            return false;
        }
        //检查保证金余额是否足够扣除本次支付
        if (false === $this -> checkPrepayment($this -> promoteInfo['id'], $game_id, $pay_amount)) {
            return false;
        }
        return json_decode($this -> promoteInfo[$pay_type], true);
    }


    /**
     * @验证渠道是否开启自定义支付
     *
     * @author: zsl
     * @since: 2021/1/26 14:34
     */
    private function checkIsCustomPay($promote_id)
    {
        if (empty($this -> promoteInfo)) {
            $this -> getPromoteInfo($promote_id);
        }
        if ($this -> promoteInfo['is_custom_pay'] != '1') {
            return false;
        }
        return true;
    }

    /**
     * @检查该渠道是否申请游戏
     *
     * @param $promote_id
     * @param $game_id
     *
     * @author: zsl
     * @since: 2021/1/26 15:06
     */
    public function checkApplyGame($promote_id, $game_id)
    {
        $mPromoteApply = new PromoteapplyModel();
        $where = [];
        $where['game_id'] = $game_id;
        $where['promote_id'] = $promote_id;
        $where['status'] = 1;
        $applyCount = $mPromoteApply -> where($where) -> count();
        return !!$applyCount;
    }

    /**
     * @获取支付参数
     *
     * @author: zsl
     * @since: 2021/1/26 15:12s
     */
    public function checkPayStatus($promote_id, $pay_type)
    {
        if (empty($this -> promoteInfo)) {
            $this -> getPromoteInfo($promote_id);
        }
        $payConfig = json_decode($this -> promoteInfo[$pay_type], true);
        return !!$payConfig['status'];
    }

    /**
     * @获取推广员信息
     *
     * @author: zsl
     * @since: 2021/1/26 14:33
     */
    private function getPromoteInfo($promote_id)
    {
        $info = get_promote_entity($promote_id, 'id,promote_level,top_promote_id,is_custom_pay,prepayment,zfb,wxscan,wxapp');
        if (empty($info)) {
            return false;
        }
        //获取顶级渠道配置
        if ($info['promote_level'] != '1') {
            $info = get_promote_entity($info['top_promote_id'], 'id,promote_level,top_promote_id,is_custom_pay,prepayment,zfb,wxscan,wxapp');
        }
        $this -> promoteInfo = $info;
        return $info;
    }

    /**
     * @检查预付款是否足够
     *
     * @author: zsl
     * @since: 2021/1/27 14:06
     */
    private function checkPrepayment($promote_id, $game_id, $pay_amount)
    {
        $deductAmont = $this -> getDeductAmount($promote_id, $game_id, $pay_amount);
        if ($deductAmont > $this -> promoteInfo['prepayment']) {
            return false;
        }
        return true;
    }


    /**
     * @获取应扣除金额
     *
     * @author: zsl
     * @since: 2021/1/27 15:17
     */
    public function getDeductAmount($promote_id, $game_id, $pay_amount)
    {
        $mPromoteApply = new PromoteapplyModel();
        $where = [];
        $where['game_id'] = $game_id;
        $where['promote_id'] = $promote_id;
        $where['status'] = 1;
        $promoteRatio = $mPromoteApply -> where($where) -> value('promote_ratio');
        //应扣除金额
        $deductAmont = $pay_amount * ((100 - $promoteRatio) / 100);
        return $deductAmont;
    }


}