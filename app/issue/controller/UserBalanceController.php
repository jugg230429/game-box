<?php

namespace app\issue\controller;

use app\common\controller\BaseController;
use app\issue\model\BalanceModel;
use cmf\controller\AdminBaseController;

class UserBalanceController extends AdminBaseController
{


    /**
     * @用户充值平台币订单列表
     *
     * @author: zsl
     * @since: 2021/4/26 11:38
     */
    public function lists()
    {
        $mBalance = new BalanceModel();
        $base = new BaseController;
        $param = $this -> request -> param();
        $map = [];
        if (!empty($param['user_id'])) {
            $map['user_id'] = $param['user_id'];
        }
        if ($param['pay_status']==='0' || $param['pay_status']==='1') {
            $map['pay_status'] = $param['pay_status'];
        }
        $start_time = $param['start_time'];
        $end_time = $param['end_time'];
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }
        $exend['field'] = 'id,user_id,order_number,pay_order_number,pay_amount,pay_way,pay_time,pay_status,pay_ip,create_time';
        $data = $base -> data_list($mBalance, $map, $exend) -> each(function($item, $key){
            return $item;
        });
        // 获取分页显示
        $page = $data -> render();
        $this -> assign("data_lists", $data);
        $this -> assign("page", $page);
        return $this -> fetch();
    }


}
