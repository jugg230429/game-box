<?php
/**
 * Created by gjt.
 * User: Administrator
 * Date: 2019/1/23
 * Time: 15:34
 */

namespace app\recharge\controller;

use app\common\controller\BaseController;
use app\recharge\logic\RebateLogic;
use app\recharge\model\SpendBalanceModel;
use app\recharge\model\SpendModel;
use app\recharge\model\SpendpromotecoinModel;
use app\recharge\model\SpendprovideModel;
use cmf\controller\AdminBaseController;
use app\promote\model\PromotebindModel;
use think\Db;

class ExportController extends AdminBaseController
{
    function expUser()
    {
        $id = $this->request->param('id', 0, 'intval');
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();
        switch ($id) {
            case 1://充值列表
                $xlsCell = array(
                    array('pay_order_number', '订单号'),
                    array('extend', 'CP订单号'),
                    array('pay_time', "时间"),
                    array('user_account', "账号"),
                    array('game_name', "游戏名称"),
                    array('promote_id', "所属渠道"),
                    array('server_name', '游戏区服'),
                    array('game_player_name', "角色名称"),
                    array('cost', "订单金额"),
                    array('pay_amount', '实付金额/￥'),
                    array('coupon_record_id', "代金券抵扣"),
                    array('pay_way', "充值方式"),
                    array('discount', "折扣"),
                    array('pay_status', '订单状态'),
                    array('pay_game_status', "游戏通知状态"),
                );
                $spend = new SpendModel;
                $base = new BaseController;
                $account = $this->request->param('user_account', '');
                if ($account != '') {
                    $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
                }
                $pay_order_number = $this->request->param('pay_order_number', '');
                if ($pay_order_number != '') {
                    $map['pay_order_number'] = ['like', "" . addcslashes($pay_order_number, '%') . '%'];
                }
				 $paccount = $this->request->param('promote_account', '');
                if ($paccount != '') {
                    $map['promote_account'] = ['like', '%' . addcslashes($paccount, '%') . '%'];
                }
                $spend_ip = $this->request->param('spend_ip', '');
                if ($spend_ip != '') {
                    $map['spend_ip'] = ['like', '%' . addcslashes($spend_ip, '%') . '%'];
                }
                $start_time = $this->request->param('start_time', '');
                $end_time = $this->request->param('end_time', '');
                if ($start_time && $end_time) {
                    $map['pay_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['pay_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['pay_time'] = ['egt', strtotime($start_time)];
                }

                $game_id = $this->request->param('game_id', 0, 'intval');
                if ($game_id > 0) {
                    $map['game_id'] = $game_id;
                }

                $server_id = $this->request->param('server_id', 0, 'intval');
                if ($server_id > 0) {
                    $map['server_id'] = $server_id;
                }
                $pay_way = $this->request->param('pay_way', '');
                if ($pay_way != '') {
                    $map['pay_way'] = $pay_way;
                }

                $pay_status = $this->request->param('pay_status', '');
                if ($pay_status != '') {
                    $map['pay_status'] = $pay_status;
                }
                $pay_game_status = $this->request->param('pay_game_status', '');
                if ($pay_game_status != '') {
                    $map['pay_game_status'] = $pay_game_status;
                }

                // 根据CP查询订单
                $cp_id = $this->request->param('cp_id',0,'intval');
                if(!empty($cp_id)){
                    $map['tab_spend.game_id'] = ['in',get_cp_game_ids($cp_id)];
                }

                // 是否使用代金券
                $use_coupon = $this->request->param('use_coupon');
                if($use_coupon==='1' || $use_coupon==='0'){
                    if($use_coupon==='1'){
                        $map['coupon_record_id'] = ['neq',0];
                    }else{
                        $map['coupon_record_id'] = 0;
                    }
                }

                $exend['order'] = 'pay_time desc';
                $exend['field'] = 'id,user_id,user_account,game_id,game_name,promote_id,promote_account,pay_time,pay_status,pay_order_number,extend,pay_amount,pay_game_status,pay_way,spend_ip,server_id,server_name,game_player_id,game_player_name,cost,is_check,role_level,discount_type,discount,coupon_record_id';
                $xlsData = $base->data_list_select($spend, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['pay_time'] = date('Y-m-d H:i:s', $value['pay_time']);
                    $xlsData[$key]['promote_id'] = AUTH_PROMOTE == 1 ? get_promote_name($value['promote_id']) : '请购买渠道权限';
                    $xlsData[$key]['pay_way'] = get_pay_way($value['pay_way']);
                    $xlsData[$key]['pay_status'] = get_info_status($value['pay_status'], 2);
                    $xlsData[$key]['pay_game_status'] = get_info_status($value['pay_game_status'], 3);
                    if($value['pay_status'] == 1){
                        if(!empty($value['coupon_record_id'])){
                            $xlsData[$key]['coupon_record_id'] = get_coupon_entity($value['coupon_record_id'],'money')['money'];
                        }else{
                            $xlsData[$key]['coupon_record_id'] = '0.00';
                        }
                    }else{
                        $xlsData[$key]['coupon_record_id'] = '--';
                    }
                    $xlsData[$key]['discount'] = $value['discount'].'折';
                }
                $exend['field'] = 'sum(pay_amount) as total';
                //累计充值
                $map['pay_status'] = 1;
                $total = $base->data_list_select($spend, $map, $exend);

                //今日充值
                $map['pay_status'] = 1;
                $map['pay_time'] = ['between', total(1, 2)];
                $today = $base->data_list_select($spend, $map, $exend);
                //昨日充值
                $map['pay_status'] = 1;
                $map['pay_time'] = ['between', total(5, 2)];
                $yestoday = $base->data_list_select($spend, $map, $exend);
                $xlsData[] = ['pay_order_number' => '汇总', 'pay_time' => '今日充值：', 'user_account' => null_to_0($today[0]['total']), 'game_name' => '昨日充值：', 'promote_id' => null_to_0($yestoday[0]['total']), 'spend_ip' => '总充值：', 'server_name' => null_to_0($total[0]['total'])];
                write_action_log("导出游戏订单记录");
                break;
            case 3://平台币充值
                $xlsCell = array(
                    array('pay_order_number', "订单号"),
                    array('account', "账号"),
                    array('promote_id', "所属渠道"),
                    array('pay_amount', '充值数量'),
                    array('pay_way', "充值方式"),
                    array('pay_ip', "充值IP"),
                    array('pay_time', "充值时间"),
                    array('pay_status', "订单状态"),
                );
                $spend = new SpendBalanceModel;
                $base = new BaseController;
                $account = $this->request->param('user_account', '');
                if ($account != '') {
                    $usermap['account'] = ['like', '%' . addcslashes($account, '%') . '%'];
                    $user_lists = Db::table('tab_user')->where($usermap)->column('id');
                    $user_ids = empty($usermap) ? -1 : implode(',', $user_lists);
                    $map['user_id'] = ['in', $user_ids];
                }
                $pay_order_number = $this->request->param('pay_order_number', '');
                if ($pay_order_number != '') {
                    $map['pay_order_number'] = ['like', "" . addcslashes($pay_order_number, '%') . '%'];
                }
                $spend_ip = $this->request->param('pay_ip', '');
                if ($spend_ip != '') {
                    $map['pay_ip'] = ['like', '%' . addcslashes($spend_ip, '%') . '%'];
                }
                $start_time = $this->request->param('start_time', '');
                $end_time = $this->request->param('end_time', '');
                if ($start_time && $end_time) {
                    $map['pay_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['pay_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['pay_time'] = ['egt', strtotime($start_time)];
                }


                $pay_way = $this->request->param('pay_way', '');
                if ($pay_way != '') {
                    $map['pay_way'] = $pay_way;
                }

                $pay_status = $this->request->param('pay_status', '');
                if ($pay_status != '') {
                    $map['pay_status'] = $pay_status;
                }

                $exend['order'] = 'pay_time desc';
                $exend['field'] = '*';
                $xlsData = $base->data_list_select($spend, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['account'] = get_user_info('account', ['id' => $value['user_id']])['account'];
                    $xlsData[$key]['promote_id'] = AUTH_PROMOTE == 1 ? get_promote_name($value['promote_id']) : '请购买渠道权限';
                    $xlsData[$key]['pay_way'] = get_pay_way($value['pay_way']);
                    $xlsData[$key]['pay_time'] = date('Y-m-d H:i:s', $value['pay_time']);
                    $xlsData[$key]['pay_status'] = get_info_status($value['pay_status'], 2);
                }
                $exend['field'] = 'sum(pay_amount) as total';
                //累计充值
                $map['pay_status'] = 1;
                $total = $base->data_list_select($spend, $map, $exend);
                $today[0] = 0;
                $yestoday[0] = 0;
                if ((empty($start_time) || ($start_time <= (date('Y-m-d')))) && (empty($end_time) || ($end_time >= (date('Y-m-d'))))) {
                    //今日充值
                    $map['pay_time'] = ['between', total(1, 2)];
                    $today = $base->data_list_select($spend, $map, $exend);
                }

                if ((empty($start_time) || ($start_time <= date("Y-m-d", strtotime("-1 day")))) && (empty($end_time) || ($end_time >= date("Y-m-d", strtotime("-1 day"))))) {
                    //昨日充值
                    $map['pay_time'] = ['between', total(5, 2)];
                    $yestoday = $base->data_list_select($spend, $map, $exend);
                }
                $xlsData[] = ['pay_order_number' => '汇总', 'account' => '今日充值：', 'promote_id' => null_to_0($today[0]['total']), 'pay_amount' => '昨日充值：', 'pay_way' => null_to_0($yestoday[0]['total']), 'pay_ip' => '总充值：', 'pay_time' => null_to_0($total[0]['total'])];
                write_action_log("导出平台币充值记录");
                break;
            case 4://后台发放（玩家）
                $xlsCell = array(
                    array('pay_order_number', "订单号"),
                    array('user_account', "玩家账号"),
                    array('type', "发放类型"),
                    array('amount', '发放数量'),
                    array('op_account', "操作人员"),
                    array('create_time', "发放时间"),
                    array('update_time', "充值时间"),
                    array('status', "状态"),
                );
                $base = new BaseController();
                $spend = new SpendprovideModel;
                $map['coin_type'] = 0 ;
                $account = $this->request->param('user_account', '');
                if ($account != '') {
                    $usermap['account'] = ['like', '%' . addcslashes($account, '%') . '%'];
                    $user_lists = Db::table('tab_user')->where($usermap)->column('id');
                    $user_ids = empty($usermap) ? -1 : implode(',', $user_lists);
                    $map['user_id'] = ['in', $user_ids];
                }

                $start_time = $this->request->param('start_time', '');
                $end_time = $this->request->param('end_time', '');
                if ($start_time && $end_time) {
                    $map['update_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['update_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['update_time'] = ['egt', strtotime($start_time)];
                }

                $op_id = $this->request->param('admin_id', '');
                if ($op_id != '') {
                    $map['op_id'] = $op_id;
                }
                $status = $this->request->param('status', '');
                if ($status != '') {
                    $map['status'] = $status;
                }
                $map['type'] = 1;
                $exend['order'] = 'create_time desc';
                $exend['field'] = 'id,pay_order_number,user_id,user_account,cost,amount,status,op_id,op_account,create_time,update_time';
                $xlsData = $base->data_list_select($spend, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['type'] = '平台币';
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                    $xlsData[$key]['update_time'] = empty($value['update_time']) ? '~' : date('Y-m-d H:i:s', $value['update_time']);
                    $xlsData[$key]['status'] = get_info_status($value['status'], 9);
                }

                $exend['field'] = 'sum(amount) as total';
                //累计充值
                $map['status'] = 1;
                $total = $base->data_list_select($spend, $map, $exend);
                // 今日充值
                $map['create_time'] = ['between', total(1, 2)];
                $today = $base->data_list_select($spend, $map, $exend);
                // 昨日充值
                $map['create_time'] = ['between', total(5, 2)];
                $yestoday = $base->data_list_select($spend, $map, $exend);
                $xlsData[] = ['pay_order_number' => '汇总', 'type' => '总充值：', 'amount' => null_to_0($total[0]['total'])];
                $xlsData[] = ['type' => '今日充值：', 'amount' => null_to_0($today[0]['total'])];
                $xlsData[] = ['type' => '昨日充值：', 'amount' => null_to_0($yestoday[0]['total'])];
                write_action_log("导出后台发放玩家平台币记录");
                break;
            case 5://后台发放（渠道）
                $xlsCell = array(
                    array('promote_id', "渠道账号"),
                    array('promote_type', "渠道等级"),
                    array('type', "发放类型"),
                    array('num', '发放数量'),
                    array('op_id', "操作人员"),
                    array('create_time', "发放时间"),
                    array('aa', " "),
                );
                $base = new BaseController();
                $spend = new SpendpromotecoinModel;
                $map['type'] = 1;
                $account = $this->request->param('account', '');
                if ($account != '') {
                    $usermap['account'] = ['like', '%' . addcslashes($account, '%') . '%'];
                    $user_lists = Db::table('tab_promote')->where($usermap)->column('id');
                    $user_ids = empty($usermap) ? -1 : implode(',', $user_lists);
                    $map['promote_id'] = ['in', $user_ids];
                }

                $start_time = $this->request->param('start_time', '');
                $end_time = $this->request->param('end_time', '');
                if ($start_time && $end_time) {
                    $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['create_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['create_time'] = ['egt', strtotime($start_time)];
                }

                $op_id = $this->request->param('admin_id', '');
                if ($op_id != '') {
                    $map['op_id'] = $op_id;
                }


                $exend['order'] = 'create_time desc';
                $exend['field'] = '*';
                $xlsData = $base->data_list_select($spend, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['promote_id'] = get_promote_name($value['promote_id']);
                    $xlsData[$key]['type'] = '平台币';
                    $xlsData[$key]['op_id'] = get_admin_name($value['op_id']);
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                }
                $exend['field'] = 'sum(num) as total';
                //累计充值
                $total = $base->data_list_select($spend, $map, $exend);
                $today[0] = 0;
                $yestoday[0] = 0;
                if ((empty($start_time) || ($start_time <= (date('Y-m-d')))) && (empty($end_time) || ($end_time >= (date('Y-m-d'))))) {
                    //今日充值
                    $map['create_time'] = ['between', total(1, 2)];
                    $today = $base->data_list_select($spend, $map, $exend);
                }
                if ((empty($start_time) || ($start_time <= date("Y-m-d", strtotime("-1 day")))) && (empty($end_time) || ($end_time >= date("Y-m-d", strtotime("-1 day"))))) {
                    //昨日充值
                    $map['create_time'] = ['between', total(5, 2)];
                    $yestoday = $base->data_list_select($spend, $map, $exend);
                }
                $xlsData[] = ['promote_id' => '汇总', 'promote_type' => '今日充值：', 'type' => null_to_0($today[0]['total']), 'num' => '昨日充值：', 'op_id' => null_to_0($yestoday[0]['total']), 'create_time' => '总充值：', 'aa' => null_to_0($total[0]['total'])];
                write_action_log("导出后台发放渠道平台币记录");
                break;
            case 6://平台币收回
                if ($this->request->param('type') != 2) {
                    $xlsCell = array(
                        array('t1', "玩家账号"),
                        array('t2', "收回类型"),
                        array('t3', "收回数量"),
                        array('t4', '收回时间'),
                        array('t5', "操作人员"),
                        array('t6', " "),
                        array('t7', " "),
                    );
                } else {
                    $xlsCell = array(
                        array('t1', "渠道账号"),
                        array('t2', "渠道等级"),
                        array('t3', '收回数量'),
                        array('t4', "收回时间"),
                        array('t5', "操作人员"),
                        array('t6', " "),
                        array('t7', " "),
                    );
                }
                $base = new BaseController();
                if ($this->request->param('type') != 2) {
                    $spend = new SpendprovideModel;
                    $account = $this->request->param('user_account', '');
                    if ($account != '') {
                        $usermap['account'] = ['like', '%' . addcslashes($account, '%') . '%'];
                        $user_lists = Db::table('tab_user')->where($usermap)->column('id');
                        $user_ids = empty($usermap) ? -1 : implode(',', $user_lists);
                        $map['user_id'] = ['in', $user_ids];
                    }
                } else {
                    $spend = new SpendpromotecoinModel;
                    $promote_id = $this->request->param('promote_id', '');
                    if ($promote_id) {
                        $map['promote_id'] = $promote_id;
                    }
                    $level = $this->request->param('level', '');//渠道等级
                    if ($level != '') {
                        $map['promote_type'] = $level;
                    }
                }
                $start_time = $this->request->param('start_time', '');
                $end_time = $this->request->param('end_time', '');
                if ($start_time && $end_time) {
                    $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['create_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['create_time'] = ['egt', strtotime($start_time)];
                }
                $map['type'] = 2;
                $exend['order'] = 'create_time desc';
                $exend['field'] = '*';
                $xlsData = $base->data_list_select($spend, $map, $exend);
                if ($this->request->param('type') != 2) {
                    foreach ($xlsData as $key => $value) {
                        $xlsData[$key]['t1'] = $value['user_account'];
                        $xlsData[$key]['t2'] = '平台币';
                        $xlsData[$key]['t3'] = $value['amount'];
                        $xlsData[$key]['t4'] = date('Y-m-d H:i:s', $value['create_time']);
                        $xlsData[$key]['t5'] = $value['op_account'];
                    }
                } else {
                    foreach ($xlsData as $key => $value) {
                        $xlsData[$key]['t1'] = get_promote_name($value['promote_id']);
                        $xlsData[$key]['t2'] = $value['promote_type'] == 1 ? '一级' : ($value['promote_type'] == 2 ?'二级':'三级');
                        $xlsData[$key]['t3'] = $value['num'];
                        $xlsData[$key]['t4'] = date('Y-m-d H:i:s', $value['create_time']);
                        $xlsData[$key]['t5'] = get_admin_name($value['op_id']);
                    }
                }
                if ($this->request->param('type') != 2) {
                    $exend['field'] = 'sum(amount) as total';
                } else {
                    $exend['field'] = 'sum(num) as total';
                }
                //累计收回
                $total = $base->data_list_select($spend, $map, $exend);
                $map['type'] = 2;
                $today[0] = 0;
                $yestoday[0] = 0;
                if ((empty($start_time) || ($start_time <= (date('Y-m-d')))) && (empty($end_time) || ($end_time >= (date('Y-m-d'))))) {
                    //今日收回
                    $map['create_time'] = ['between', total(1, 2)];
                    $today = $base->data_list_select($spend, $map, $exend);
                }
                if ((empty($start_time) || ($start_time <= date("Y-m-d", strtotime("-1 day")))) && (empty($end_time) || ($end_time >= date("Y-m-d", strtotime("-1 day"))))) {
                    //昨日收回
                    $map['create_time'] = ['between', total(5, 2)];
                    $yestoday = $base->data_list_select($spend, $map, $exend);
                }
                $xlsData[] = ['t1' => '汇总', 't2' => '今日收回：', 't3' => null_to_0($today[0]['total']), 't4' => '昨日收回：', 't5' => null_to_0($yestoday[0]['total']), 't6' => '总收回：', 't7' => null_to_0($total[0]['total'])];
                write_action_log("导出平台币收回记录");
                break;
            case 7://代金券领取记录
                $xlsCell = array(
                        array('user_account', "玩家账号"),
                        array('mold', "类型"),
                        array('game_name', "游戏名称"),
                        array('coupon_name', "代金券名称"),
                        array('money', "优惠金额"),
                        array('limit_money', "使用条件"),
                        array('get_way', "获取途径"),
                        array('create_time', "获取时间"),
                        array('update_time', "使用时间"),
                        array('cost', "订单金额"),
                        array('pay_amount', "实付金额"),
                        array('status', "状态"),
                );
                $logic = new RebateLogic();
                $request = $this -> request -> param();
                $map['pid'] = 0;
                $xlsData = $logic -> getCouponRecord($request, $map);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['mold'] = $value['mold'] == '0' ? '通用' : '游戏';
                    $xlsData[$key]['limit_money'] = empty($value['limit_money']) ? '无门槛' : '满减：满' . $value['limit_money'];
                    $xlsData[$key]['get_way'] = $value['get_way'] == '0' ? '领取' : '发放';
                    $xlsData[$key]['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
                    if(!empty($value['update_time'])){
                        $xlsData[$key]['update_time'] = date("Y-m-d H:i:s", $value['update_time']);
                    }else{
                        $xlsData[$key]['update_time'] = '--';
                    }
                    $xlsData[$key]['cost'] = $value['cost'] == '0.00' ? '--' : $value['cost'];
                    $xlsData[$key]['pay_amount'] = $value['pay_amount'] == '0.00' ? '--' : $value['pay_amount'];
                    if ($value['is_delete'] == '1') {
                        $xlsData[$key]['status'] = '已回收';
                    } else {
                        if ($value['status'] == '1') {
                            $xlsData[$key]['status'] = '已使用';
                        } elseif ($value['end_time'] < time() && $value['end_time'] > 0) {
                            $xlsData[$key]['status'] = '已过期';
                        } else {
                            $xlsData[$key]['status'] = '未使用';
                        }
                    }
                }

                $total = $logic -> get_coupon_total($request, $map);
                $xlsData[] = [
                        'user_account' => '汇总',
                        'mold' => '',
                        'game_name' => '',
                        'coupon_name' => '',
                        'money' => '',
                        'limit_money' => '',
                        'get_way' => '',
                        'create_time' => '',
                        'update_time' => '',
                        'cost' => $total[0]['totalcost'],
                        'pay_amount' => $total[0]['total'],
                        'status' => '',
                ];
                write_action_log("导出代金券领取记录");
                break;
            case 8://会长代充记录
                $xlsCell = array(
                    array('promote_account', "渠道账号"),
                    array('user_account', "玩家账号"),
                    array('game_name', "游戏名称"),
                    array('pay_order_number', '订单号'),
                    array('promote_id', "上线渠道"),
                    array('cost', "代充金额(元)"),
                    array('discount', "折扣比例"),
                    array('pay_amount', "实付金额(元)"),
                    array('pay_status', "代充状态"),
                    array('pay_way', "支付方式"),
                    array('pay_time', "代充时间"),
                );
                $base = new BaseController();
                $spend = new PromotebindModel;
                $promote_id = $this->request->param('promote_id', '');
                if ($promote_id != '') {
                    $map['promote_id'] = $promote_id;
                }
                $account = $this->request->param('user_account', '');
                if ($account != '') {
                    $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
                }
                $game_id = $this->request->param('game_id', '');
                if($game_id){
                    $map['game_id'] = $game_id;
                }
                $start_time = $this->request->param('start_time', '');
                $end_time = $this->request->param('end_time', '');
                if ($start_time && $end_time) {
                    $map['pay_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['pay_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['pay_time'] = ['egt', strtotime($start_time)];
                }

                $pay_way = $this->request->param('pay_way', '');
                if ($pay_way != '') {
                    $map['pay_way'] = $pay_way;
                }
                $pay_status = $this->request->param('pay_status', '');
                if ($pay_status != '') {
                    $map['pay_status'] = $pay_status;
                }
                $exend['order'] = 'pay_time desc';
                $exend['field'] = '*';
                $xlsData = $base->data_list_select($spend, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    // 上线渠道
                    $xlsData[$key]['promote_id'] = get_parent_name($value['promote_id']) ? get_parent_name($value['promote_id']) : $value['promote_account'] ;
                    $xlsData[$key]['pay_status'] = get_info_status($value['pay_status'],2);
                    $xlsData[$key]['pay_way'] = get_pay_way($value['pay_way']);
                    $xlsData[$key]['pay_time'] = date('Y-m-d H:i:s', $value['pay_time']);
                }
                $exend['field'] = 'sum(pay_amount) as total';
                //累计充值
                $map['pay_status'] = 1;
                $total = $base->data_list_select($spend, $map, $exend);
                $today[0] = 0;
                $yestoday[0] = 0;
                if ((empty($start_time) || ($start_time <= (date('Y-m-d')))) && (empty($end_time) || ($end_time >= (date('Y-m-d'))))) {
                    //今日充值
                    $map['pay_time'] = ['between', total(1, 2)];
                    $today = $base->data_list_select($spend, $map, $exend);
                }

                if ((empty($start_time) || ($start_time <= date("Y-m-d", strtotime("-1 day")))) && (empty($end_time) || ($end_time >= date("Y-m-d", strtotime("-1 day"))))) {
                    //昨日充值
                    $map['pay_time'] = ['between', total(5, 2)];
                    $yestoday = $base->data_list_select($spend, $map, $exend);
                }
                $xlsData[] = ['promote_account' => '汇总', 'user_account' => '今日充值：', 'game_name' => null_to_0($today[0]['total']), 'pay_order_number' => '昨日充值：', 'promote_id' => null_to_0($yestoday[0]['total']), 'cost' => '总充值：', 'discount' => null_to_0($total[0]['total'])];
                write_action_log("导出会长代充记录");
                break;


        }
        foreach ($xlsData as $key => $val) {
            foreach ($xlsCell as $k => $v) {
                if (isset($v[2])) {
                    $ar_k = array_search('*', $v);
                    if ($ar_k !== false) {
                        $v[$ar_k] = $val[$v[0]];
                    }
                    $fun = $v[2];
                    $param = $v;
                    unset($param[0], $param[1], $param[2]);
                    $xlsData[$key][$v[0]] = call_user_func_array($fun, $param);
                }
            }
        }
        $this->exportExcel($xlsName, $xlsCell, $xlsData);
    }

}
