<?php

namespace app\scrm\controller;

use app\scrm\logic\BusinessLogic;
use app\scrm\logic\OrderLogic;
use app\scrm\logic\PromoteLogic;
use app\scrm\logic\UserLogic;
use app\scrm\logic\SupportLogic;

class ApiController extends ApiBaseController
{
    /**
     * @获取用户数据
     *
     * @author: zsl
     * @since: 2021/8/4 16:36
     */
    public function user()
    {
        //查询用户信息
        $lUser = new UserLogic();
        if (false === $lUser -> lists($this -> param)) {
            $this -> response(0, $lUser -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lUser -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lUser -> getData())));
        }
    }


    /**
     * @获取推广员数据
     *
     * @author: zsl
     * @since: 2021/8/5 9:59
     */
    public function promote()
    {
        $lPromote = new PromoteLogic();
        if (false === $lPromote -> lists($this -> param)) {
            $this -> response(0, $lPromote -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lPromote -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lPromote -> getData())));
        }
    }


    /**
     * @获取商务专员数据
     *
     * @author: zsl
     * @since: 2021/8/5 10:22
     */
    public function business()
    {
        $lBusiness = new BusinessLogic();
        if (false === $lBusiness -> lists($this->param)) {
            $this -> response(0, $lBusiness -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lBusiness -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lBusiness -> getData())));
        }
    }


    /**
     * @获取玩家订单数据
     *
     * @author: zsl
     * @since: 2021/8/5 9:18
     */
    public function order()
    {
        $lOrder = new OrderLogic();
        if (false === $lOrder -> lists($this -> param)) {
            $this -> response(0, $lOrder -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lOrder -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lOrder -> getData())));
        }
    }


    /**
     * @获取扶持数据
     *
     * @author: zsl
     * @since: 2021/8/5 11:39
     */
    public function support()
    {
        $lSupport = new SupportLogic();
        if (false === $lSupport -> lists($this -> param)) {
            $this -> response(0, $lSupport -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lSupport -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lSupport -> getData())));
        }
    }


    /**
     * @获取商务专员业绩完成度
     *
     * @author: zsl
     * @since: 2021/8/6 15:03
     */
    public function businessPerformance()
    {
        $lBusiness = new BusinessLogic();
        if (false === $lBusiness -> performance($this->param)) {
            $this -> response(0, $lBusiness -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lBusiness -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lBusiness -> getData())));
        }
    }

    /**
     * @descript 获取商务专员业绩
     * @author  yyh <1010707499@qq.com>
     * @since    2021/9/3 11:29
     */
    public function businessAchievement()
    {
        $lBusiness = new BusinessLogic();
        if (false === $lBusiness -> achievement($this->param)) {
            $this -> response(0, $lBusiness -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lBusiness -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lBusiness -> getData())));
        }
    }


    /**
     * @获取用户绑币数据
     *
     * @author: zsl
     * @since: 2021/8/6 17:02
     */
    public function userBindBalance()
    {
        $lUser = new UserLogic();
        if (false === $lUser -> bindBalance($this -> param)) {
            $this -> response(0, $lUser -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lUser -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lUser -> getData())));
        }
    }


    /**
     * @推广员七日数据
     *
     * @author: zsl
     * @since: 2021/8/6 17:49
     */
    public function promoteSevenDayData()
    {
        $lPromote = new PromoteLogic();
        if (false === $lPromote -> sevenDayData($this -> param)) {
            $this -> response(0, $lPromote -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lPromote -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lPromote -> getData())));
        }
    }


    /**
     * @用户付费排行
     *
     * @author: zsl
     * @since: 2021/8/9 15:41
     */
    public function userPayRank()
    {
        $lUser = new UserLogic();
        if (false === $lUser -> payRank($this -> param)) {
            $this -> response(0, $lUser -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lUser -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lUser -> getData())));
        }
    }

    /**
     * @descript 渠道付费排行榜（用户消费）
     * @author  yyh <1010707499@qq.com>
     * @since    2021/9/3 11:30
     */
    public function promoteUserPayRank()
    {
        $lPromote = new PromoteLogic();
        if (false === $lPromote -> payRank($this -> param)) {
            $this -> response(0, $lPromote -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lPromote -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lPromote -> getData())));
        }
    }


    /**
     * @修改用户密码
     *
     * @author: zsl
     * @since: 2021/8/20 9:06
     */
    public function changeUserPassword()
    {
        $lUser = new UserLogic();
        if (false === $lUser -> changePassword($this -> param)) {
            $this -> response(0, $lUser -> getErrorMsg());
        }
        $this -> response(200, '修改成功');
    }

    /**
     * @修改用户
     *
     * @author: zsl
     * @since: 2021/8/20 9:39
     */
    public function changeUserMobile()
    {
        $lUser = new UserLogic();
        if (false === $lUser -> changeMobile($this -> param)) {
            $this -> response(0, $lUser -> getErrorMsg());
        }
        $this -> response(200, '修改成功');
    }


    /**
     * @用户补链
     *
     * @author: zsl
     * @since: 2021/8/20 10:07
     */
    public function userMend()
    {
        $lUser = new UserLogic();
        if (false === $lUser -> mend($this -> param)) {
            $this -> response(0, $lUser -> getErrorMsg());
        }
        $this -> response(200, '补链成功');
    }


    /**
     * @修改渠道密码
     *
     * @author: zsl
     * @since: 2021/8/20 11:56
     */
    public function changePromotePassword()
    {
        $lPromote = new PromoteLogic();
        if (false === $lPromote -> changePassword($this -> param)) {
            $this -> response(0, $lPromote -> getErrorMsg());
        }
        $this -> response(200, '修改成功');
    }

    /**
     * @descript 获取玩家一年内每月充值金额 、累计充值、今日充值
     * @author  yyh <1010707499@qq.com>
     * @since    2021/9/3 13:57
     */
    public function userPay()
    {
        $lUser = new UserLogic();
        if (false === $lUser -> userPay($this->param)) {
            $this -> response(0, $lUser -> getErrorMsg());
        }
        if (true === config('scrm.debug')) {
            $this -> response(200, '请求成功', $lUser -> getData());
        } else {
            $this -> response(200, '请求成功', $this -> s -> encrypt(json_encode($lUser -> getData())));
        }
    }

}
