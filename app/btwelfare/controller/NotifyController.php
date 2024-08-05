<?php

namespace app\btwelfare\controller;

use app\btwelfare\logic\MonthCardLogic;
use app\btwelfare\logic\RechargeLogic;
use app\btwelfare\logic\RegisterLogic;
use app\btwelfare\logic\TotalRechargeLogic;
use app\btwelfare\logic\WeekCardLogic;
use think\Controller;
use think\Request;

class NotifyController extends Controller
{

    protected $result;

    public function __construct(Request $request = null)
    {
        parent ::__construct($request);
        $this -> result = ['code' => '1', 'msg' => '请求成功', 'data' => []];
    }


    /**
     * @CP创建角色通知接口
     *
     * @author: zsl
     * @since: 2021/1/14 20:14
     */
    public function register()
    {
        $param = $this -> request -> post();
        //验证game_id
        if (empty($param['game_id'])) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = 'game_id不能为空';
            return json($this -> result);
        }
        //验证sign
        $apiPath = "\app\btwelfare\api\Game" . $param['game_id'] . "Api";
        $apiObject = new $apiPath;
        $checkSign = $apiObject -> checkSign($param);
        if (false === $checkSign) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = 'sign验证失败';
            return json($this -> result);
        }
        //生成注册福利数据
        $lRegister = new RegisterLogic();
        $lRegister -> buildData($param);
        return json($this -> result);
    }


    /**
     * @CP充值通知接口
     *
     * @author: zsl
     * @since: 2021/1/14 20:24
     */
    public function recharge()
    {

        $param = $this -> request -> param();
        //验证game_id
        if (empty($param['game_id'])) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = 'game_id不能为空';
            return json($this -> result);
        }
        //验证sign
        $apiPath = "\app\btwelfare\api\Game" . $param['game_id'] . "Api";
        $apiObject = new $apiPath;
        $checkSign = $apiObject -> checkSign($param);
        if (false === $checkSign) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = 'sign验证失败';
            return json($this -> result);
        }
        //生成充值福利数据
        $lRecharge = new RechargeLogic();
        $lRecharge -> buildData($param);
        //生成累计充值福利数据
        $lTotal = new TotalRechargeLogic();
        $lTotal -> buildData($param);
        //生成周卡数据
        $lWeekCard = new WeekCardLogic();
        $lWeekCard->buildData($param);
        //生成月卡数据
        $lMonthCard = new MonthCardLogic();
        $lMonthCard->buildData($param);

        return json($this -> result);
    }


    private function returnJson($param)
    {
        return json($param);
    }


}