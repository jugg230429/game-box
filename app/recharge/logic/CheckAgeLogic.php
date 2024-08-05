<?php

namespace app\recharge\logic;

use app\member\model\UserModel;
use app\recharge\model\SpendModel;
use think\helper\Time;

/**
 * Class CheckAgeLogic
 *
 * （1）未满8周岁的用户禁止充值；
 * （2）8周岁以上未满16周岁的用户，单次充值上限50元；
 * （3）8周岁以上未满16周岁的用户，单月累计充值上限200元；
 * （4）16周岁以上未满18周岁的用户，单次充值上限100元；
 * （5）16周岁以上未满18周岁的用户，单月累计充值上限400元。
 *
 * @package app\recharge\logic
 */
class CheckAgeLogic
{


    private $errorMsg = '';


    /**
     * @充值年龄检查
     *
     * @param $user_id : 用户id
     * @param $pay_amount : 本次充值金额
     *
     * @return boolean
     *
     * @author: zsl
     * @since: 2021/9/17 20:20
     */
    public function run($user_id, $pay_amount)
    {
        //获取用户身份证号
        $mUser = new UserModel();
        $idcard = $mUser -> where(['id' => $user_id]) -> value('idcard');
        if (empty($idcard)) {
            $this -> errorMsg = '请进行实名认证';
            return false;
        }
        //获取身份证年龄
        $age = $this -> _getAge($idcard);
        //未满8周岁的用户禁止充值
        if ($age < 8) {
            $this -> errorMsg = '根据健康系统限制，您暂时无法进行充值/购买行为，感谢您的理解。';
            return false;
        }
        if ($age >= 18) {
            return true;
        }
        //获取用户本周充值
        $week_amount = $this -> _getUserWeekAmount($user_id);
        //获取用户本月充值
        $total_amount = $this -> _getUserMonthAmount($user_id);
        //8周岁以上未满16周岁的用户
        if ($age >= 8 && $age < 16) {
            //判断单次充值金额
            if ($pay_amount > 50) {//单次充值上限50元；
                $this -> errorMsg = '根据健康系统限制，您本次的充值/购买金额已达系统上限，请降低金额或选择低价商品。';
                return false;
            }
            //判断本周充值总额
            if (($week_amount + $pay_amount) > 50) {//单周累计充值上限50元
                $this -> errorMsg = '根据健康系统限制，您本次的充值/购买金额已达系统上限，将无法继续，请下周重试。';
                return false;
            }
            //判断本月充值总额
            if (($total_amount + $pay_amount) > 200) {//单月累计充值上限200元
                $this -> errorMsg = '根据健康系统限制，您本次的充值/购买金额已达系统上限，将无法继续，请次月重试。';
                return false;
            }
        }
        //16周岁以上未满18周岁的用户
        if ($age >= 16 && $age < 18) {
            //判断单次充值金额
            if ($pay_amount > 100) {//单次充值上限100元；
                $this -> errorMsg = '根据健康系统限制，您本次的充值/购买金额已达系统上限，请降低金额或选择低价商品。';
                return false;
            }
            //判断本周充值总额
            if (($week_amount + $pay_amount) > 100) {//单周累计充值上限100元
                $this -> errorMsg = '根据健康系统限制，您本次的充值/购买金额已达系统上限，将无法继续，请下周重试。';
                return false;
            }
            //判断本月充值总额
            if (($total_amount + $pay_amount) > 400) {//单月累计充值上限200元
                $this -> errorMsg = '根据健康系统限制，您本次的充值/购买金额已达系统上限，将无法继续，请次月重试。';
                return false;
            }
        }
        return true;
    }


    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorMsg()
    {
        return $this -> errorMsg;
    }


    /**
     * @获取年龄
     *
     * @author: zsl
     * @since: 2021/9/17 20:39
     */
    private function _getAge($idcard)
    {
        $id = substr($idcard, 6, 8);
        $year = substr($id, 0, 4);
        $month = substr($id, 4, 2);
        $day = substr($id, 6, 2);
        $old = (time() - strtotime($year . '-' . $month . '-' . $day)) / 31536000;
        return $old;
    }

    /**
     * @获取用户本周充值
     *
     * @author: zsl
     * @since: 2021/9/18 16:14
     */
    private function _getUserWeekAmount($user_id)
    {
        $mSpend = new SpendModel();
        list($weekStart, $weekEnd) = Time ::week();
        $where = [];
        $where['user_id'] = $user_id;
        $where['pay_status'] = 1;
        $where['pay_time'] = ['between', [$weekStart, $weekEnd]];
        $total_amount = $mSpend -> where($where) -> sum('pay_amount');
        return $total_amount;
    }

    /**
     * @获取用户当月总充值
     *
     * @author: zsl
     * @since: 2021/9/17 20:55
     */
    private function _getUserMonthAmount($user_id)
    {
        $mSpend = new SpendModel();
        list($monthStart, $monthEnd) = Time ::month();
        $where = [];
        $where['user_id'] = $user_id;
        $where['pay_status'] = 1;
        $where['pay_time'] = ['between', [$monthStart, $monthEnd]];
        $total_amount = $mSpend -> where($where) -> sum('pay_amount');
        return $total_amount;
    }

}
