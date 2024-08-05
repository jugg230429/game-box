<?php

namespace app\issue\controller;

use app\issue\logic\StatLogic;

class StatController extends ManagementBaseController
{
    public function user_lists(StatLogic $lStat)
    {
        $getData = $this->request->param();
        $getData['open_user_id'] = OID;
        $getData['platform_id'] = PID;
        $data = $lStat->user_lists($getData);
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }
    public function recharge_lists(StatLogic $lStat)
    {
        $getData = $this->request->param();
        $getData['open_user_id'] = OID;
        $getData['platform_id'] = PID;
        $getData['pay_status'] = '1';
        $data = $lStat->recharge_lists($getData);
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }
}