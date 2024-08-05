<?php
/**
 * Created by gjt.
 * User: Administrator
 * Date: 2019/1/23
 * Time: 15:34
 */

namespace app\channelsite\controller;

use app\common\controller\BaseHomeController;
use app\promote\model\PromoteModel;
use app\promote\model\PromoteunionModel;
use app\promote\model\PromotewithdrawModel;
use app\promote\model\PromotesettlementModel;
use app\recharge\model\SpendModel;
use app\member\model\UserModel;
use app\datareport\event\PromoteController as Promote;
use think\Request;
use think\Db;


class ExportController extends BaseController
{
    /**
     * @函数或方法说明
     * @导出方法
     * @author: 郭家屯
     * @since: 2019/3/29 9:35
     */
    function expUser()
    {
        $id = $this->request->param('id', 0, 'intval');
        $pid = PID;
        $base = new BaseHomeController();
        if ($pid == 'PID') {
            $this->error('请登录推广平台');
        }
        $param = $this->request->param();
        switch ($id) {
            case 1://子渠道列表
                $xlsCell = array(
                    array('id', '账号ID'),
                    array('account', "子渠道账号"),
                    array('create_time', "创建时间"),
                    array('domain_url', "联盟站地址"),
                    array('union_status', "审核状态"),
                    array('create_time', "创建时间"),
                    array('status', "状态"),
                );
                if(PID_LEVEL == 1){
                    $xlsName = '管理中心-二级渠道';
                }else{
                    $xlsName = '管理中心-三级渠道';
                }
                $model = new PromoteModel();
                $map['parent_id'] = $pid;
                $extend['field'] = 'id,account,create_time,last_login_time,status';
                $xlsData = $base->data_list_select($model, $map, $extend);
                // 联盟站设置
                $mUnion = new PromoteunionModel();
                foreach ($xlsData as &$v) {
                    $unionInfo = $mUnion -> field('id,union_id,status,domain_url') ->where(['union_id'=>$v['id']]) -> find();
                    $v['domain_url'] = $unionInfo['domain_url']?$unionInfo['domain_url']:'--';
                    if($unionInfo['status']=='-1'){
                        $v['union_status'] = '驳回';
                    }elseif($unionInfo['status']=='0'){
                        $v['union_status'] = '待审核';
                    }elseif($unionInfo['status']=='1'){
                        $v['union_status'] = '已通过';
                    }else{
                        $v['union_status'] = '--';
                    }
                }
                unset($v);
                foreach ($xlsData as $key => $v) {
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                    $xlsData[$key]['last_login_time'] = date('Y-m-d H:i:s', $v['last_login_time']);
                    $xlsData[$key]['status'] = get_info_status($v['status'], 10);
                }
                break;
            case 2://数据汇总
                $xlsCell = array(
                    array('promote_id', '渠道ID'),
                    array('promote_account', "渠道帐号"),
                    array('count_new_register_user', "新增用户"),
                    array('count_active_user', "活跃用户"),
                    array('count_pay_user', "付费用户"),
                    array('count_new_pay_user', "新增付费用户"),
                    array('total_pay', "总付费额")
                );
                $xlsName = '数据管理-数据汇总';
                $request = $this->request->param();
                $date = $request['rangepickdate'];
                if($date) {
                    $dateexp = explode('至', $date);
                    $starttime = $dateexp[0];
                    $endtime = $dateexp[1];
                }
                $promote_id = $this->request->param('promote_id', '');
                if(empty($promote_id)){
                    $promote_id =  array_column(get_song_promote_lists(PID),'id');
                    $promote_id[] = PID;
                }else{
                    $promote_id = [$promote_id];
                }
                $promoteevent = new Promote();
                $new_data = $promoteevent->promote_data($starttime, $endtime, $promote_id);
                $list_data = $new_data['data'];
                $last_pids = array_column($list_data,'promote_id');//直属下级渠道id
                foreach ($list_data as $lk=>$lv){
                    if($lv['promote_id']==PID){
                        continue;
                    }else{
                        $zi_promote_ids = array_column(get_song_promote_lists($lv['promote_id']),'id');
                        if(!$zi_promote_ids){
                            continue;
                        }
                        $zi_data = $promoteevent->promote_data($starttime, $endtime, $zi_promote_ids)['data'];
                        $last_pids = array_merge($last_pids,$zi_promote_ids);
                        foreach ($list_data[$lk] as $llk=>$llv){
                            if(in_array($llk,['new_register_user','active_user','pay_user','new_pay_user','total_pay'])){
                                if($llk == 'total_pay'){
                                    $list_data[$lk][$llk] = $llv+array_sum(array_column($zi_data,$llk));
                                }else{
                                    $list_data[$lk][$llk] = $llv.','.implode(',',array_column($zi_data,$llk));
                                    $list_data[$lk][$llk] = trim($list_data[$lk][$llk],',');
                                    $value = str_unique($list_data[$lk][$llk], 1);
                                    $list_data[$lk]['count_'.$llk] = arr_count($value);
                                }
                            }
                        }
                    }
                }
                $xlsData = $list_data;
                $promote_id = $last_pids;
                $new_data = $promoteevent->promote_data($starttime, $endtime, $promote_id);
                $total_data = $new_data['total_data'];
                $total_data['promote_id'] = '汇总';
                $total_data['count_new_register_user'] = $total_data['new_register_user'];
                $total_data['count_active_user'] = $total_data['active_user'];
                $total_data['count_pay_user'] = $total_data['pay_user'];
                $total_data['count_new_pay_user'] = $total_data['new_pay_user'];
                $xlsData[] = $total_data;
                $xlsData = array_values($xlsData);
                break;
            case 3://注册明细
                $xlsCell = array(
                        array('id', '账号ID'),
                        array('user_account', "账号"),
                        array('bind_balance', '绑币余额'),
                        array('total_cost', "累计充值"),
                        array('total_pay', "累计实付"),
                        array('fgame_name', "游戏名称"),
                        array('register_time', '注册时间'),
                        array('register_ip', '注册IP'),
                        array('device_name', "注册机型"),
                        array('account', '所属渠道'),
                );
                $xlsName = '数据管理-注册明细';
                $model = new \app\member\model\UserModel();
                $base = new BaseHomeController;
                //条件
                $account = $this->request->param('user_account', '');
                if ($account != '') {
                    $map['tab_user.account'] = ['like', '%' . addcslashes($account, '%') . '%'];
                }
                $user_id = $this->request->param('user_id');
                if($user_id > 0){
                    $map['tab_user.id'] = $user_id;
                }
                $rangepickdate = $this->request->param('rangepickdate');
                if($rangepickdate) {
                    $dateexp = explode('至', $rangepickdate);
                    $starttime = $dateexp[0];
                    $endtime = $dateexp[1];
                    $this -> assign('start', $starttime);
                    $this -> assign('end', $endtime);
                    $map['register_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
                }else{
                    $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
                    $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
                }
                $game_id = $this->request->param('game_id', 0, 'intval');
                if ($game_id > 0) {
                    $map['fgame_id'] = $game_id;
                }
                $promote_id = $this->request->param('promote_id', 0, 'intval');
                if ($promote_id > 0) {
                    if ($promote_id == PID) {
                        $map['p.id'] = $promote_id;
                    } else {
                        $map['p.id|p.parent_id'] = $promote_id;
                    }
                } else {
                    $map['p.id|p.parent_id|p.top_promote_id'] = PID;
                }
                $promote_zi = get_promote_list(['id|parent_id|top_promote_id'=>PID],'id');
                if($promote_zi){
                    $promote_zi = array_column($promote_zi,'id');
                }
                $promote_zi[] = PID;
                $promote_zi = implode(',',$promote_zi);
                $map['tab_user.puid'] = 0;
                $exend['field'] = 'p.id as pid,sum(s.cost) as total_cost,sum(s.pay_amount) as total_pay,promote_level,tab_user.id,tab_user.account as user_account,p.account,fgame_id,fgame_name,register_time,register_ip,device_name';
                $exend['order'] = 'register_time desc';
                $exend['join1'][] = ['tab_promote' => 'p'];
                $exend['join1'][] = 'tab_user.promote_id=p.id';
                $exend['join1'][] = 'left';
                $exend['join2'][] = ['tab_spend' => 's'];
                $exend['join2'][] = 'tab_user.id=s.user_id and s.pay_status=1 and s.promote_id in ('.$promote_zi.')';
                $exend['join2'][] = 'left';
                $exend['group'] = 'tab_user.id';
                $data = $base->data_list_join_select($model, $map, $exend);
                foreach ($data as $key=>$vo){
                    $data[$key]['bind_balance'] = get_user_bind_total($vo['id']);
                    $data[$key]['register_time'] = date('Y-m-d H:i:s',$vo['register_time']);
                }
                //累计充值
                $exend['order'] = null;
                $exend['group'] = null;
                $exend['field'] = 'count(tab_user.id) as ucount,sum(s.cost) as total_cost,sum(s.pay_amount) as total_pay';
                $total = $base->data_list_join_select($model, $map, $exend);
                $xlsData = $data;
                $total_data['id'] = '汇总';
                $total_data['total_cost'] = $total[0]['total_cost'];
                $total_data['total_pay'] = $total[0]['total_pay'];
                $xlsData[] = $total_data;
                $xlsData = array_values($xlsData);
                break;

            case 4:
                $xlsCell = array(
                        array('user_id', "ID"),
                        array('user_account', "账号"),
                        array('pay_order_number', '订单号'),
                        array('game_name', "游戏名称"),
                        array('server_name', '游戏区服'),
                        array('game_player_name', "角色名称"),
                        array('role_level', "等级"),
                        array('cost', "订单金额"),
                        array('pay_amount', '实付金额/￥'),
                        array('pay_time', "充值时间"),
                        array('pay_way', "充值方式"),
                        array('pay_status', '订单状态'),
                        array('spend_ip', "充值IP"),
                        array('account', "所属渠道"),
                );
                $xlsName = '数据管理-充值明细';
                $base = new BaseHomeController;
                //条件
                $account = $this->request->param('user_account', '');
                if ($account != '') {
                    $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
                }
                $pay_order_number = $this->request->param('pay_order_number', '');
                if ($pay_order_number != '') {
                    $map['pay_order_number'] = ['like', "%" . addcslashes($pay_order_number, '%') . '%'];
                }
                $server_id = $this->request->param('server_id');
                if($server_id){
                    $map['server_name'] = $server_id;
                }
                $rangepickdate = $this->request->param('rangepickdate');
                $rangepickdate = urldecode($rangepickdate);
                if($rangepickdate) {
                    $dateexp = explode('至', $rangepickdate);
                    $starttime = $dateexp[0];
                    $endtime = $dateexp[1];
                    $this -> assign('start', $starttime);
                    $this -> assign('end', $endtime);
                    $map['pay_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
                }else{
                    $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
                    $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
                }
                $game_id = $this->request->param('game_id', 0, 'intval');
                if ($game_id > 0) {
                    $map['game_id'] = $game_id;
                }
                $promote_id = $this->request->param('promote_id', 0, 'intval');
                if ($promote_id > 0) {
                    if ($promote_id == PID) {
                        $map['p.id'] = $promote_id;
                    } else {
                        $map['p.id|p.parent_id'] = $promote_id;
                    }
                } else {
                    $map['p.id|p.parent_id|top_promote_id'] = PID;
                }
                $pay_way = $this->request->param('pay_way', '');
                if ($pay_way != '') {
                    $map['pay_way'] = $pay_way;
                }
                $pay_status = $this->request->param('pay_status');
                if($pay_status != ''){
                    $map['pay_status'] = $pay_status;
                }
                $model = new SpendModel();
                $exend['field'] = 'p.id as pid,p.parent_id,p.promote_level,tab_spend.id,p.account,user_id,user_account,game_id,game_name,server_id,server_name,game_player_name,role_level,pay_order_number,cost,pay_amount,pay_time,pay_way,spend_ip,pay_status';
                $exend['order'] = 'pay_time desc';
                $exend['join1'][] = ['tab_promote' => 'p'];
                $exend['join1'][] = 'tab_spend.promote_id=p.id';
                $xlsData = $base->data_list_join_select($model, $map, $exend);
                foreach ($xlsData as $key=>$v){
                    $xlsData[$key]['pay_time'] = date('Y-m-d H:i:s',$v['pay_time']);
                    $xlsData[$key]['pay_way'] = get_info_status($v['pay_way'],1);
                    $xlsData[$key]['pay_status'] = get_info_status($v['pay_status'],2);
                }
                //累计充值
                $map['pay_status'] = 1;
                $exend['order'] = null;
                $exend['field'] = 'sum(cost) as scost,sum(pay_amount) as spay_amount';
                $total = $base->data_list_join_select($model, $map, $exend);
                $totaldata['user_id'] =  '汇总：';
                $totaldata['cost'] = '应付总计：'.$total[0]['scost'];
                $totaldata['pay_amount'] = '实付总计：'.$total[0]['spay_amount'];
                $xlsData[] = $totaldata;
                $xlsData = array_values($xlsData);
                break;
            case 5://我的结算
                $xlsName = '我的收益-收益记录';
                $xlsCell = array(
                    array('promote_account', '渠道账号'),
                    array('pay_order_number', "订单号"),
                    array('create_time', "时间"),
                    array('game_name', "来源游戏"),
                    array('user_account', "玩家账号"),
                    array('user_id', "玩家ID"),
                    array('role_name', "角色信息"),
                    array('cost', "游戏金额"),
                    array('pay_amount', "实付金额"),
                    array('pattern', "结算模式"),
                    array('sum_money', "结算佣金"),
                );
                $date = $param['rangepickdate'];
                if($date) {
                    $dateexp = explode('至', $date);
                    $starttime = $dateexp[0];
                    $endtime = $dateexp[1];
                    $map['create_time'] = ['between', [strtotime($starttime), strtotime($endtime)+86399]];
                }
                if($param['pay_order_number']){
                    $map['pay_order_number'] = ['like','%'.$param['pay_order_number'].'%'];
                }
                if($param['game_id']){
                    $map['game_id'] = $param['game_id'];
                }
                if($param['promote_id']){
                    if($param['promote_id']==PID){
                        $map['promote_id'] = ['in',$param['promote_id']];
                    }else{
                        if(PID_LEVEL==1){
                            $this_promote_son = array_column(get_song_promote_lists($param['promote_id'],2),'id');
                        }else{
                            $this_promote_son = $param['promote_id'];
                        }
                        $map['promote_id'] = ['in',$this_promote_son];
                    }
                }
                if($param['user_account']){
                    $map['user_account'] = ['like','%'.$param['user_account'].'%'];
                }
                if($param['user_id']){
                    $map['user_id'] = ['like','%'.$param['user_id'].'%'];
                }
                if($param['pattern'] != ''){
                    $map['pattern'] = $param['pattern'];
                }
                $model = new PromotesettlementModel();
                $base = new BaseHomeController;
                $exend['field'] = 'id,promote_id,parent_id,top_promote_id,promote_account,game_name,pattern,ratio,money,sum_money,ratio2,money2,sum_money2,ratio3,money3,sum_money3,user_id,user_account,is_check,parent_name,pay_order_number,pay_amount,cost,create_time,update_time,role_name';
                $exend['order'] = 'id desc';
                if(PID_LEVEL == 1){
                    $map['top_promote_id'] = PID;
                    $map['status'] = 1;
                }elseif(PID_LEVEL == 2){
                    $map['promote_id|parent_id'] = PID;
                    $map['sub_status'.(PID_LEVEL)] = 1;
                }else{
                    $map['promote_id'] = PID;
                    $map['sub_status'.(PID_LEVEL)] = 1;
                }
                $xlsData = $base->data_list_select($model, $map, $exend);
                $cost = 0;
                $paymoney = 0;
                $summoney = 0;
                foreach ($xlsData as $key => $vo) {
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
                    $xlsData[$key]['pattern'] = $vo['pattern'] == 0 ? 'CPS' : 'CPA';
                    if($vo['promote_id'] == PID || $vo['parent_id']==$vo['top_promote_id']){
                        $xlsData[$key]['promote_account'] = get_promote_name($vo['promote_id']);
                    }else{
                        $xlsData[$key]['promote_account'] = get_promote_name($vo['parent_id']);
                    }
                    switch (PID_LEVEL){
                        case 1:
                            $xlsData[$key]['sum_money'] = $vo['sum_money'] = null_to_0($vo['sum_money']);
                            break;
                        case 2:
                            $xlsData[$key]['sum_money'] = $vo['sum_money'] = null_to_0($vo['sum_money2']);
                            break;
                        case 3:
                            $xlsData[$key]['sum_money'] = $vo['sum_money'] = null_to_0($vo['sum_money3']);
                            break;
                    }
                    $cost += $vo['cost'];
                    $paymoney += $vo['pay_amount'];
                    $summoney += $vo['sum_money'];
                }
                $xlsData[] = ['promote_account' => '汇总', 'cost' => $cost,'pay_amount'=>$paymoney,'sum_money'=>$summoney];
                break;
            case 6://结算记录
                $xlsName = '我的收益-支出记录';
                $xlsCell = array(
                    array('create_time', '日期'),
                    array('type', '类型'),
                    array('sum_money', "支出金额"),
                    array('money', "付款金额"),
                    array('fee', "扣税金额"),
                    array('status', "状态"),
                );
                $date = $param['rangepickdate'];
                if($date) {
                    $dateexp = explode('至', $date);
                    $starttime = $dateexp[0];
                    $endtime = $dateexp[1];
                    $map['create_time'] = ['between', [strtotime($starttime), strtotime($endtime)+86399]];
                }
                if($param['type']){
                    $map['type'] = $param['type'];
                }
                if($param['status'] != ''){
                    $map['status'] = $param['status'];
                }
                $map['promote_id'] = PID;
                $model = new PromotewithdrawModel();
                $base = new BaseHomeController;
                $exend['field'] = 'id,type,sum_money,fee,status,create_time';
                $exend['order'] = 'id desc';
                $xlsData = $base->data_list_select($model, $map, $exend);
                $totalmoney = 0;
                $totalfee = 0;
                foreach ($xlsData as $key => $vo) {
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
                    $xlsData[$key]['type'] = $vo['type'] ==1 ? '提现' : '兑换';
                    $xlsData[$key]['money'] = $vo['sum_money'] - $vo['fee'];
                    if($vo['status'] == 0){
                        $xlsData[$key]['status'] = '待审核';
                    }elseif($vo['status'] == 1){
                        $xlsData[$key]['status'] = '已通过';
                    } elseif($vo['status'] == 2){
                        $xlsData[$key]['status'] = '已驳回';
                    }else{
                        $xlsData[$key]['status'] = '已打款';
                    }
                    $totalmoney += $vo['sum_money'];
                    $totalfee += $vo['fee'];
                }
                $xlsData[] = ['create_time' => '汇总', 'sum_money' => $totalmoney,'money'=>$totalmoney-$totalfee,'fee'=>$totalfee];
                break;
            case 7://平台币记录
                $xlsName = '我的收益-我的平台币-平台币记录';
                $xlsCell = array(
                    array('pay_order_number', '订单号'),
                    array('promote_id', '账号'),
                    array('create_time', "时间"),
                    array('pay_amount', "数量"),
                    array('type', "途径"),
                );
                $dpmodel = new \app\promote\model\PromotedepositModel();
                $czmap['pay_status'] = 1;
                $czmap['to_id'] = PID;
                $cz = $dpmodel->lists($czmap)->toarray();//平台币充值
                foreach ($cz as $key=>$v){
                    if($v['promote_id'] == $v['to_id']){
                        $cz[$key]['promote_id'] = '自己';
                        $cz[$key]['type'] = 1;
                    }else{
                        $cz[$key]['to_id'] = $v['promote_id'];
                        $cz[$key]['type'] = 2;
                    }
                }
                //平台币转移扣除
                $zymodel = new \app\promote\model\PromotecoinModel();
                $zymap['promote_id'] = PID;
                $zymap['type'] = 1;
                $zy = $zymodel->lists($zymap, 'promote_id,source_id as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 1 as ptbtype')->toarray();
                //平台币转移获得
                $zymodel = new \app\promote\model\PromotecoinModel();
                $zymap['promote_id'] = PID;
                $zymap['type'] = 2;
                $zyj = $zymodel->lists($zymap, 'promote_id,source_id as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 2 as ptbtype')->toarray();

                //后台发放
                $pcmodel = new \app\recharge\model\SpendpromotecoinModel;
                $pcmap['promote_id'] = PID;
                $pcmap['type'] = 1;
                $pc = $pcmodel->lists($pcmap, '"自己" as promote_id,"自己" as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 2 as ptbtype')->toarray();

                //后台回收
                $hsmap['promote_id'] = PID;
                $hsmap['type'] = 2;
                $hs = $pcmodel->lists($hsmap, '"自己" as promote_id,"自己" as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 3 as ptbtype')->toarray();
                $alldata = $xlsData = my_sort(array_merge($cz, $zy,$zyj, $pc,$hs), 'create_time', SORT_DESC);
                foreach ($xlsData as $key => &$value) {
                    //数据整理
                    if ($value['ptbtype'] == 1) {
                        $alldata[$key]['type'] = $value['type'] = 3;
                    } elseif ($value['ptbtype'] == 2) {
                        $alldata[$key]['type'] = $value['type'] = 4;
                    }elseif ($value['ptbtype'] == 3) {
                        $alldata[$key]['type'] = $value['type'] = 5;
                    }
                    $pay_order_number = $param['pay_order_number'];
                    if ($pay_order_number != '') {
                        if ($value['pay_order_number'] != $pay_order_number) {
                            unset($xlsData[$key]);
                            continue;
                        }
                    }
                    $to_id = $param['to_id'];
                    if ($to_id != '' && $to_id != '自己') {
                        if ($value['to_id'] != $to_id) {
                            unset($xlsData[$key]);
                            continue;
                        }
                    } elseif ($to_id == '自己') {
                        if ($value['promote_id'] != $to_id) {
                            unset($xlsData[$key]);
                            continue;
                        }
                    }
                    $type = $param['type'];
                    if ($type != '') {
                        if ($value['type'] != $type) {
                            unset($xlsData[$key]);
                            continue;
                        }
                    }
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                    if ($value['promote_id'] != '自己') {
                        $xlsData[$key]['promote_id'] = get_promote_name($value['to_id']);
                    }
                    if ($value['type'] == 1) {
                        $xlsData[$key]['type'] = '充值渠道';
                        $xlsData[$key]['pay_amount'] = ' +'.$value['pay_amount'];
                    } elseif ($value['type'] == 2) {
                        $xlsData[$key]['type'] = '渠道充值';
                        $xlsData[$key]['pay_amount'] = ' +'.$value['pay_amount'];
                    } elseif ($value['type'] == 3) {
                        $xlsData[$key]['type'] = '平台币转移';
                        $xlsData[$key]['pay_amount'] = ' -'.$value['pay_amount'];
                    } elseif ($value['type'] == 4) {
                        $xlsData[$key]['pay_amount'] = ' +'.$value['pay_amount'];
                        $xlsData[$key]['type'] = '发放/转移';
                    } elseif($value['type'] == 5) {
                        $xlsData[$key]['pay_amount'] = ' -'.$value['pay_amount'];
                        $xlsData[$key]['type'] = '后台收回';
                    }
                    if($value['pay_way']>0){
                        $xlsData[$key]['pay_way'] = get_pay_way($value['pay_way']);
                    }
                }
                $xlsData[] = ['pay_order_number' => '汇总', 'promote_id' => '累计收入：','create_time'=>deposit_record($alldata,1),'pay_amount'=>'累计支出：','type'=>deposit_record($alldata,2)];
                $xlsData = array_values($xlsData);
                break;
            case 8://子渠道结算统计
                $xlsName = '子渠道结算-子渠道结算统计';
                $xlsCell = array(
                    array('promote_account', '子渠道账号'),
                    array('totalamount', '总充值'),
                    array('totalreg', "总注册"),
                    array('totalmoney', "已结算佣金"),
                    array('sum_money', "已发放佣金（收益提现/收益兑换）"),
                );
                //结算单部分
                $data = $this->request->param();
                if ($data['promote_id']) {
                    $this_promote_son_arr[] = $data['promote_id'];
                    if(PID_LEVEL==1){
                        $this_promote_son = array_column(get_song_promote_lists($data['promote_id'],1),'id');//直属下级
                        $this_promote_son_arr = array_merge($this_promote_son_arr,$this_promote_son);
                    }
                    $map['promote_id'] = ['in',$this_promote_son_arr];
                }else{
                    $zipromote = get_song_promote_lists(PID,1);//直属下级
                    $map['promote_id'] = ['in',array_column($zipromote,'id')];
                }
                $sub_status = 'sub_status'.(PID_LEVEL+1);
                $map[$sub_status] = 1;
                $model = new PromotesettlementModel();
                $base = new BaseHomeController();
                $exend['order'] = 'promote_id desc';
                if(PID_LEVEL==1){
                    $map['top_promote_id'] = PID;
                }else{
                    $map['parent_id'] = PID;
                }
                $group = 'promote_id';
                $exend['group'] = $group;
                $exend['field'] = 'promote_id,parent_id,top_promote_id,promote_account,sum(pay_amount) as totalamount,sum(sum_reg) as totalreg,sum(sum_money) as totalmoney,ratio,money,pattern,sum(sum_money2) as totalmoney2,ratio2,money2,sum(sum_money3) as totalmoney3,ratio3,money3';
                $exend['row'] = 100000000;
                $exend['p'] = 1;
                $xlsData = $base->data_list($model, $map, $exend)->each(function($item){
                    $model = new PromotesettlementModel();
                    $base = new BaseHomeController();
                    $sub_status = 'sub_status'.(PID_LEVEL+1);
                    $map[$sub_status] = 1;
                    if(PID_LEVEL==1){
                        $map['top_promote_id'] = PID;
                        $map['parent_id'] = ['eq',$item['promote_id']];
                    }else{
                        $map['parent_id'] = ['neq',PID];
                    }
                    $exend['field'] = 'id,promote_id,parent_id,top_promote_id,promote_account,sum(pay_amount) as totalamount,sum(sum_reg) as totalreg,sum(sum_money) as totalmoney,ratio,money,pattern,sum(sum_money2) as totalmoney2,ratio2,money2,sum(sum_money3) as totalmoney3,ratio3,money3';
                    $zidata = $base->data_list($model, $map, $exend)->toarray()['data'][0];
                    $item['totalamount'] = $item['totalamount']+$zidata['totalamount'];
                    $item['totalreg'] = $item['totalreg']+$zidata['totalreg'];
                    $item['totalmoney'] = $item['totalmoney']+$zidata['totalmoney'];
                    $item['totalmoney2'] = $item['totalmoney2']+$zidata['totalmoney2'];
                    $item['totalmoney3'] = $item['totalmoney3']+$zidata['totalmoney3'];
                });
                //收益部分
                $promotewithdrawmodel = new PromotewithdrawModel();
                $promote_ids = array_column($xlsData->toarray()['data'], 'promote_id');//有结算数据的直属下级推广员
                $map1['promote_id'] = ['in', $promote_ids];
                $map1['tab_promote_withdraw.status'] = 3;//打款才计算
                $wfields = 'sum(sum_money) as totalmoney,type';
                if(PID_LEVEL==1){
                    $wfields .= ',IF(p.promote_level="2",p.id,parent_id) as promote_id';
                }else{
                    $wfields .= ',promote_id';
                }
                $data = $promotewithdrawmodel->field($wfields)->join(['tab_promote'=>'p'],'p.id=tab_promote_withdraw.promote_id')->where($map1)->group('promote_id,type')->select()->toArray();
                $sum = array();
                foreach ($data as $key => $v) {
                    $sum[$v['promote_id'] . '_' . $v['type']]['money'] = $sum[$v['promote_id'] . '_' . $v['type']]['money']+$v['totalmoney'];
                }
                foreach ($xlsData as $key=>$vo){
                    $xlsData[$key]['sum_money'] = ($sum[$vo['promote_id'].'_1']['money']+$sum[$vo['promote_id'].'_2']['money']).'('.($sum[$vo['promote_id'].'_1']['money']?:0).'/'.($sum[$vo['promote_id'].'_2']['money']?:0).')';
                }
                //汇总部分
                $mpids = [];
                foreach ($promote_ids as $pk=>$pv){
                    $mpids = array_merge($mpids,array_column(get_song_promote_lists($pv),'id'));
                }
                $map['promote_id'] = ['in',array_merge($mpids,$promote_ids)];
                $all = $model->field('sum(pay_amount) as totalamount,sum(sum_reg) as totalreg,sum(sum_money) as totalmoney,sum(sum_money2) as totalmoney2,sum(sum_money3) as totalmoney3')->where($map)->find();
                $all_withdraw = $promotewithdrawmodel->field('sum(sum_money) as totalmoney,type')->where($map1)->group('type')->order('type asc')->select()->toArray();
                if($all_withdraw){
                    if($all_withdraw[0]['type'] == 1){
                        $all['withdraw'] = $all_withdraw[0]['totalmoney']?:0;
                        $all['exchange'] = $all_withdraw[1]['totalmoney']?:0;
                    }else{
                        $all['withdraw'] = 0;
                        $all['exchange'] = $all_withdraw[0]['totalmoney']?:0;
                    }
                }else{
                    $all['withdraw'] = 0;
                    $all['exchange'] = 0;
                }
                $all['sum_money'] =  ($all['withdraw'] + $all['exchange']).'('.$all['withdraw'].'/'.$all['exchange'].')';
                $all['promote_account'] = '汇总';
                $xlsData[] = $all;
                foreach ($xlsData as $pk=>$pv){
                    if(PID_LEVEL==1){
                        $xlsData[$pk]['totalmoney'] = $xlsData[$pk]['totalmoney2'];
                    }else{
                        $xlsData[$pk]['totalmoney'] = $xlsData[$pk]['totalmoney3'];
                    }
                }
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
        $base->exportExcel($xlsName, $xlsCell, $xlsData);
    }

    /**
     * 导出周期结算打款列表
     * created by wjd 2021-9-9 09:44:39
    */
    public function expSettlementPeriod()
    {
        $pid = PID;
        $base = new BaseHomeController();
        if ($pid == 'PID') {
            $this->error('请登录推广平台');
        }
        $param = $this->request->param();

        $xlsName = '周期结算打款';

        $xlsCell = array(
            array('order_num', '结算单号'),
            array('period', '结算周期'),
            array('create_time', "结算时间"),
            array('total_money', "CPS实付流水"),
            array('total_cps', "CPS分成金额"),
            array('total_cpa', "CPA分成金额"),
            array('is_remit', "打款状态"),
            array('remit_info', "打款信息"),
        );

        $promote_id = PID;
        // 条件
        $map = [];

        $map['promote_id'] = $promote_id;


        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        $map2 = [];

        if ($start_time && $end_time) {
            $map['period_start'] = ['>=', strtotime($start_time)];
            $map['period_end'] = ['<', strtotime($end_time) + 86399];
        } elseif ($end_time) {
            $map['period_end'] = ['lt', strtotime($end_time) + 86399];
        } elseif ($start_time) {
            $map['period_start'] = ['egt', strtotime($start_time)];
        }

        $start_time_1 = $this->request->param('start_time_1', '');
        $end_time_1 = $this->request->param('end_time_1', '');
        if ($start_time_1 && $end_time_1) {
            $map['create_time'] = ['between', [strtotime($start_time_1), strtotime($end_time_1) + 86399]];
        } elseif ($end_time_1) {
            $map['create_time'] = ['lt', strtotime($end_time_1) + 86399];
        } elseif ($start_time_1) {
            $map['create_time'] = ['egt', strtotime($start_time_1)];
        }

        $start_time_2 = $this->request->param('start_time_2', '');
        $end_time_2 = $this->request->param('end_time_2', '');
        if ($start_time_2 && $end_time_2) {
            $map['update_time'] = ['between', [strtotime($start_time_2), strtotime($end_time_2) + 86399]];
        } elseif ($end_time_2) {
            $map['update_time'] = ['lt', strtotime($end_time_2) + 86399];
        } elseif ($start_time_2) {
            $map['update_time'] = ['egt', strtotime($start_time_2)];
        }


        if($param['remit_status'] === '0' || $param['remit_status'] === '1'){
            $map['is_remit'] = $param['remit_status'];
        }

        $lists = Db::table('tab_promote_settlement_period')
            ->where($map)
            // ->whereOr($map2)
            ->order('id desc')
            ->select()->toArray();

        $total_promoter_earn = 0; // 渠道分成总金额
        $total_plateform_earn = 0; // 甲方(平台)分成总金额
        $total_earn = 0; // 游戏实付金额
        $total_remit = 0; // 总打款金额
        $total_cps = 0;
        $total_cpa = 0;

        foreach($lists as $k=>$v){
            $total_promoter_earn += $v['promoter_earn'];
            $total_plateform_earn += $v['plateform_earn'];
            $total_earn += $v['total_money'];
            $total_remit += $v['remit_amount'];
            $total_cps += $v['total_cps'];
            $total_cpa += $v['total_cpa'];

        }

        $xlsData = $lists;
        foreach ($xlsData as $key => $vo) {
            $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
            $xlsData[$key]['is_remit'] = $vo['is_remit'] ==1 ? '已打款' : '未打款';
            // remit_info 打款信息 a b c d 临时处理数据变量
            $a = date('Y-m-d H:i:s', $vo['remit_time']);
            $b = $vo['operator'];
            $c = $vo['remit_amount'];
            $d = $vo['type_of_receive'] == 0 ? '银行卡' : '支付宝';
            $e = (empty($vo['name_of_receive']) ? '--' : $vo['name_of_receive']).' '.(empty($vo['accounts_of_receive']) ? '--' : $vo['accounts_of_receive']);

            if($vo['is_remit'] == 1){
                $xlsData[$key]['remit_info'] = '打款时间: '.$a.', 打款人: '.$b.', 打款金额: '.$c.', 打款方式: '.$d.', 打款账户: '.$e.'';
            }else{
                $xlsData[$key]['remit_info'] = ' -- ';
            }

        }
        $xlsData[] = ['order_num' => '汇总', 'period' =>' ','create_time'=>' ','total_money'=>$total_earn,'total_cps'=>$total_cps,'total_cpa'=>$total_cpa,'is_remit'=>' ','remit_info'=>' '];

        $base = new BaseHomeController();
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
        $base->exportExcel($xlsName, $xlsCell, $xlsData);
    }
    /**
     * 导出周期结算打款列表中某一条详情
     * created by wjd 2021-9-9 09:44:39
    */
    public function expSettlementOnePeriodDetail()
    {
        $pid = PID;
        $base = new BaseHomeController();
        if ($pid == 'PID') {
            $this->error('请登录推广平台');
        }
        $param = $this->request->param();

        $xlsName = '周期结算打款';

        $xlsCell = array(
            array('game_name', '游戏名称'),
            array('pay_amount', 'CPS实付流水'),
            array('ratio', "CPS分成比例 % "),
            array('cps_money', "CPS分成金额"),
            array('cpa_money', "CPA分成金额"),
        );
        $promote_id = PID;
        // 条件
        // var_dump($param);
        // exit;
        $period_id = (int) ($param['period_id'] ?? 0);
        if($period_id <= 0){
            return $this->error('缺少必要的参数');
        }
        $periodInfo = Db::table('tab_promote_settlement_period')->where(['id'=>$period_id])->find();
        if(!empty($periodInfo)){
            $xlsName = '结算周期:'.$periodInfo['period'].'结算单号:'.$periodInfo['order_num'];
        }

        $lists = Db::table('tab_promote_settlement')
            ->where(['period_id'=>$period_id])
            ->select()->toArray();

        foreach($lists as $key=>$val){
            $lists[$key]['plateform_ratio'] = 100-$val['ratio'];
            $lists[$key]['plateform_sum_money'] = sprintf("%.2f", $val['pay_amount'] - $val['sum_money']);
            $lists[$key]['pay_amount'] = sprintf("%.2f",$val['pay_amount']);
            $lists[$key]['sum_money'] = sprintf("%.2f",$val['sum_money']);
            if($val['pattern'] == 1){
                $lists[$key]['cpa_money'] = sprintf("%.2f",$val['sum_money']); // CPA分成金额
                $lists[$key]['cps_money'] = 0.00;
            }
            if($val['pattern'] == 0){
                $lists[$key]['cpa_money'] = 0.00;
                $lists[$key]['cps_money'] = sprintf("%.2f",($val['pay_amount'] * $val['ratio'] / 100)); // CPS分成金额
            }

        }
        // 汇总 最后一行
        $lists_total = Db::table('tab_promote_settlement')
            ->where(['period_id'=>$period_id])
            ->select()->toArray();
        $total_pay_amount = 0;
        $total_sum_money = 0;
        $total_plateform_sum_money = 0;
        $total_cpa_money = 0;
        $total_cps_money = 0;
        foreach($lists_total as $k=>$v){
            $total_pay_amount += $v['pay_amount'];
            $total_sum_money += $v['sum_money'];
            $total_plateform_sum_money += ($v['pay_amount'] - $v['sum_money']);
            if ($v['pattern'] == 0) {
                $total_cps_money += $v['sum_money'];
            }
            if ($v['pattern'] == 1) {
                $total_cpa_money += $v['sum_money'];
            }
        }
        $xlsData = $lists;

        // foreach ($xlsData as $key => $vo) {

        // }

        $xlsData[] = ['game_name' => '汇总', 'pay_amount' =>$total_pay_amount,'ratio'=>' ','cps_money'=>$total_cps_money,'cpa_money'=>$total_cpa_money];

        $base = new BaseHomeController();
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
        $base->exportExcel($xlsName, $xlsCell, $xlsData);

    }

}
