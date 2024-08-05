<?php
/**
 * @属性说明
 *
 * @var ${TYPE_HINT}
 */

namespace app\issue\logic;

use app\common\controller\BaseHomeController as BaseController;
use app\issue\model\PlatformModel;
use app\issue\model\SpendModel;
use app\issue\model\UserLoginRecordModel;
use app\issue\model\UserModel;
use app\issue\model\UserPlayModel;
use app\issue\model\UserPlayRoleModel;
use cmf\controller\HomeBaseController;
use think\Db;
use think\helper\Time;
use think\paginator\driver\Bootstrap;

class StatLogic extends HomeBaseController
{
    public function overview($request)
    {
        //注册用户
        $usermap['open_user_id'] = $request['open_user_id'];
        $usermap['platform_id'] = $request['platform_id'];
        $usermap['row'] = 10000000;
        $new_user = $this->user_lists($usermap);//全部
        $data['new_user']['all'] = count($new_user);
        //今日
        $usermap['rangepickdate'] = implode('至',array_map(function($item){
            return date('Y-m-d',$item);
        },total(1,2)));
        $new_user_0 = $this->user_lists($usermap);
        $data['new_user']['today'] = count($new_user_0);
        //昨日
        $usermap['rangepickdate'] = implode('至',array_map(function($item){
            return date('Y-m-d',$item);
        },total(5,2)));
        $new_user_1 = $this->user_lists($usermap);
        $data['new_user']['yestoday'] = count($new_user_1);
        //活跃用户
        $recordmap['open_user_id'] = $request['open_user_id'];
        $recordmap['platform_id'] = $request['platform_id'];
        $recordmap['row'] = 100000000;
        $record = $this->user_record_lists($recordmap);
        $data['record']['all'] = count(array_column($record->toarray()['data'],'user_id'));
        //今日
        $recordmap['rangepickdate'] = implode('至',array_map(function($item){
            return date('Y-m-d',$item);
        },total(1,2)));
        $record0 = $this->user_record_lists($recordmap);
        $data['record']['today'] = count(array_column($record0->toarray()['data'],'user_id'));
        //昨日
        $recordmap['rangepickdate'] = implode('至',array_map(function($item){
            return date('Y-m-d',$item);
        },total(5,2)));
        $record1 = $this->user_record_lists($recordmap);
        $data['record']['yestoday'] = count(array_column($record1->toarray()['data'],'user_id'));
        //玩家充值 付费用户
        $paymap['open_user_id'] = $request['open_user_id'];
        $paymap['platform_id'] = $request['platform_id'];
        $paymap['pay_status'] = $request['pay_status'];

        $paymap['row'] = 100000000;
        $pay = $this->recharge_lists($paymap)->toarray()['data'];
        $pay_user = array_column($pay,'user_id');
        $data['pay']['all'] = array_sum(array_column($pay,'pay_amount'));
        $data['pay_user']['all'] = count(array_unique($pay_user));
        //今日
        $paymap['rangepickdate'] = implode('至',array_map(function($item){
            return date('Y-m-d',$item);
        },total(1,2)));
        $pay0 = $this->recharge_lists($paymap)->toarray()['data'];
        $pay_user0 = array_column($pay0,'user_id');
        $data['pay']['today'] = array_sum(array_column($pay0,'pay_amount'));
        $data['pay_user']['today'] = count(array_unique($pay_user0));
            //昨日
        $paymap['rangepickdate'] = implode('至',array_map(function($item){
            return date('Y-m-d',$item);
        },total(5,2)));
        $pay1 = $this->recharge_lists($paymap)->toarray()['data'];
        $pay_user1 = array_column($pay1,'user_id');
        $data['pay']['yestoday'] = array_sum(array_column($pay1,'pay_amount'));
        $data['pay_user']['yestoday'] = count(array_unique($pay_user1));
        return $data;
    }

    public function table($request)
    {
        $mUser = new UserModel();
        $first_user_time = $mUser->where('platform_id','=',$request['platform_id'])->order('create_time asc')->value('create_time');
//        if(empty($first_user_time)){
//            $data['day'] = [];
//            $data['week'] = [];
//            $data['month'] = [];
//            return $data;
//        }
        $request['begin_date'] = '2020-06-10';
//        $request['begin_date'] = date('Y-m-d',$first_user_time);
        $res = $this->table_data($request);
        return $res;
    }

    private function table_data($request)
    {
        $dateday = get_date_list(strtotime($request['begin_date']), '', 1);
        $dateweek = get_date_list2(strtotime($request['begin_date']), '', 3);
        $datemonth = get_date_list(strtotime($request['begin_date']), '', 2);
        $map['platform_id'] = $request['platform_id'];
        $regmap['create_time'] = ['between',[strtotime($request['begin_date']),time()]];
        //新增玩家
        $newsplayer = $this->totalNewPlayerByGroup(array_merge($map,$regmap),'newsplayer','create_time',$request['type']);
        //活跃玩家
        $activemap['create_time'] = ['between',[strtotime($request['begin_date']),time()]];
        $activeplayer = $this->totalPlayerByGroup(array_merge($map,$activemap),'activeplayer','login_time',$request['type']);
        //付费玩家
        $paymap['pay_time'] = ['between',[strtotime($request['begin_date']),time()]];
        $paymap['pay_status'] = 1;
        $payuser = $this->totalPayUserByGroup(array_merge($map,$paymap),'payuser','pay_time',$request['type']);
        //新付费玩家
        $newpayuser = [];
        foreach ($payuser as $key=>$value){
            foreach ($newsplayer as $k=>$v){
                if($value['pay_time']==$v['create_time']&&$value['payuser']==$v['newsplayer']){
                    $newpayuser[] = ['pay_time'=>$value['pay_time'],'payuser'=>$value['payuser']];
                }
            }
        }
        $totalpay = $this->totalAmountByGroup(array_merge($map,$paymap),'totalpay','pay_date',$request['type'],'pay_time');
        $date = $request['type']==1?$dateday:($request['type']==2?$datemonth:$dateweek);
        //数据初始化
        $newsplayer=array_column($newsplayer,'create_time');
        $newsplayer=array_count_values($newsplayer);

        $activeplayer=array_column($activeplayer,'login_time');
        $activeplayer=array_count_values($activeplayer);

        $payuser_ = $payuser;
        $payuser=array_column($payuser,'pay_time');
        $payuser=array_count_values($payuser);

        $newpayuser=array_column($newpayuser,'pay_time');
        $newpayuser=array_count_values($newpayuser);

        $totalpay = array_column($totalpay,null,'pay_date');
        $totalpayuser = 0;
        $payuser_ = array_group_by($payuser_,'pay_time');
        foreach ($date as $k=>$v){
            $res[$v]['newsplayer'] = $newsplayer[$v]?:0;
            $res[$v]['activeplayer'] = $activeplayer[$v]?:0;
            $res[$v]['payuser'] = $payuser[$v]?:0;
            $res[$v]['newpayuser'] = $newpayuser[$v]?:0;
            $res[$v]['totalpay'] = $totalpay[$v]['totalpay']?:'0.00';
            $res[$v]['rate'] = $res[$v]['payuser']==0||$res[$v]['newsplayer']==0?'0.00%':(round($res[$v]['payuser']/$res[$v]['activeplayer'],4)*100).'%';
            $res[$v]['totalpayuser'] = count($this->deal_totalpayuser($payuser_,$v));
        }
        krsort($res);
        return $res;

    }
    private function deal_totalpayuser($data,$date){
        $arr = [];
        foreach ($data as $key=>$value){
            if($date<$key){
                continue;
            }
            $arr = array_merge($arr,array_column($value,'payuser'));
        }
        return array_filter(array_unique($arr));
    }
    //新增玩家
    private function totalNewPlayerByGroup($where,$fieldname='',$group='',$dateflag=1) {
        if ($dateflag == 1) {$dateform = '%Y-%m-%d';}
        elseif ($dateflag == 2) {$dateform = '%Y-%m';}
        elseif ($dateflag == 3) {$dateform = '%Y-%u';}
        $mUser = new UserModel();
        $data = $mUser->field('date_format(FROM_UNIXTIME('.$group.'),"'.$dateform.'") as '.$group.',id as '.$fieldname)
                ->where($where)->group($group.',id')->select()->toArray();
        return $data;
    }
    //活跃玩家
    private function totalPlayerByGroup($where,$fieldname='',$group='',$dateflag=1) {
        if ($dateflag == 1) {$dateform = '%Y-%m-%d';}
        elseif ($dateflag == 2) {$dateform = '%Y-%m';}
        elseif ($dateflag == 3) {$dateform = '%Y-%u';}
        $mUser = new UserLoginRecordModel();
        $data = $mUser->field('distinct tab_issue_user_login_record.user_id as '.$fieldname.',date_format(FROM_UNIXTIME('.$group.'),"'.$dateform.'") as '.$group)
            ->where($where)->group($group.','.$fieldname)->order($group." asc")->select()->toArray();
        return $data;
    }
    //付费玩家
    private function totalPayUserByGroup($where,$fieldname='',$group='',$dateflag=1) {
        if ($dateflag == 1) {$dateform = '%Y-%m-%d';}
        elseif ($dateflag == 2) {$dateform = '%Y-%m';}
        elseif ($dateflag == 3) {$dateform = '%Y-%u';}
        $mPay = new SpendModel();
        $field = 'date_format(FROM_UNIXTIME('.$group.'),"'.$dateform.'") as '.$group.',user_id as '.$fieldname;
        $sql = $mPay->field($field)->where($where)->select(false);
        $d = Db::query('select  a.'.$group.',a.'.$fieldname.' from ('.$sql.') as a group by a.'.$fieldname.',a.'.$group);
        return $d;
    }
    //付费金额
    public function totalAmountByGroup($where,$fieldname='',$group='',$dateflag=1,$time_field='pay_time') {
        if ($dateflag == 1) {$dateform = '%Y-%m-%d';}
        elseif ($dateflag == 2) {$dateform = '%Y-%m';}
        elseif ($dateflag == 3) {$dateform = '%Y-%u';}
        $mPay = new SpendModel();
        $field = 'date_format(FROM_UNIXTIME('.$time_field.'),"'.$dateform.'") as '.$group.',sum(pay_amount) as '.$fieldname;
        $data = $mPay->field($field)->where($where)->group($group)->select()->toArray();
        return $data;
    }

    public function user_lists($request)
    {
        $base = new BaseController();
        $model = new UserModel();
        $map = [];
        if (!empty($request['id'])) {
            $map['id'] = $request['id'];
        }
        if (!empty($request['account'])) {
            $map['account'] = ['like', '%' . $request['account'] . '%'];
        }
        if (!empty($request['openid'])) {
            $map['openid'] = $request['openid'];
        }
        if(!empty($request['open_user_id'])){
            $map['open_user_id'] = $request['open_user_id'];
        }

        if(!empty($request['platform_id'])){
            $map['platform_id'] = $request['platform_id'];
        }
        if (!empty($request['game_id'])) {
            $map['game_id'] = $request['game_id'];
        }
        if ($request['status'] === '1' || $request['status'] === '0') {
            $map['lock_status'] = $request['status'];
        }

        $rangepickdate = $request['rangepickdate'];
        if($rangepickdate) {
            $dateexp = explode('至', $rangepickdate);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            $map['create_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
        }else{

            if ($request['start_time'] || $request['end_time']) {
                $start_time = $request['start_time'];
                $end_time = $request['end_time'];
                if ($start_time && $end_time) {
                    $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['create_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['create_time'] = ['egt', strtotime($start_time)];
                }
                $this -> assign('start', $start_time);
                $this -> assign('end', $end_time);
            } else {
                $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
                $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
            }
        }
        //查询字段
        $exend['field'] = 'id,parent_id,account,openid,game_id,cumulative,create_time,register_ip,last_login_time,open_user_id,platform_id,lock_status';
        //排序优先，ID在后
        $exend['order'] = 'create_time desc,id desc';
        $exend['row'] = $request['row'] ?: config('paginate.list_rows');//每页数量
        //关联游戏类型表
        $data = $base -> data_list($model, $map, $exend);
        return $data;
    }
    public function recharge_lists($request)
    {
        $base = new BaseController();
        $model = new SpendModel();
        $map = [];
        if (!empty($request['user_id'])) {
            $map['user_id'] = $request['user_id'];
        }
        if (!empty($request['user_account'])) {
            $map['user_account'] = ['like', '%' . $request['user_account'] . '%'];
        }
        if (!empty($request['pay_order_number'])) {
            $map['pay_order_number'] = ['like', '%' . $request['pay_order_number'] . '%'];
        }
        if (!empty($request['order_number'])) {
            $map['order_number'] = ['like', '%' . $request['order_number'] . '%'];
        }
        if (!empty($request['spend_ip'])) {
            $map['spend_ip'] = ['like', '%' . $request['spend_ip'] . '%'];
        }
        if (!empty($request['game_id'])) {
            $map['game_id'] = $request['game_id'];
        }
        if(!empty($request['open_user_id'])){
            $map['open_user_id'] = $request['open_user_id'];
        }

        if(!empty($request['platform_id'])){
            $map['platform_id'] = $request['platform_id'];
        }
        if(!empty($request['sdk_version'])){
            $map['sdk_version'] = $request['sdk_version'];
        }
        if($request['pay_status']==='1' || $request['pay_status']==='0'){
            $map['pay_status'] = $request['pay_status'];
        }
        if($request['pay_game_status']==='1' || $request['pay_game_status']==='0'){
            $map['pay_game_status'] = $request['pay_game_status'];
        }

        if ($request['is_admin']) {
            $start_time = $request['start_time'];
            $end_time = $request['end_time'];
            if ($start_time && $end_time) {
                $map['pay_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
            } elseif ($end_time) {
                $map['pay_time'] = ['lt', strtotime($end_time) + 86400];
            } elseif ($start_time) {
                $map['pay_time'] = ['egt', strtotime($start_time)];
            }
        } else {
            $rangepickdate = $request['rangepickdate'];
            if ($rangepickdate) {
                $dateexp = explode('至', $rangepickdate);
                $starttime = $dateexp[0];
                $endtime = $dateexp[1];
                $this -> assign('start', $starttime);
                $this -> assign('end', $endtime);
                $map['pay_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
            } else {
                $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
                $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
            }
        }

        //查询字段
        $exend['field'] = 'id,user_id,user_account,platform_openid,order_number,pay_order_number,game_name,pay_amount,pay_time,dec_balance,spend_ip,extend,
        platform_account,server_name,game_player_name,pay_status,pay_game_status,sdk_version,pay_ff_status,ratio,ratio_money,is_check,props_name,open_user_id';
        //排序优先，ID在后
        $exend['order'] = 'pay_time desc,id desc';
        $exend['row'] = $request['row'] ?: config('paginate.list_rows');//每页数量
        //关联游戏类型表
        $data = $base -> data_list($model, $map, $exend);

        $payusermap = $map;
        if ($map['pay_status'] === '1') {
            $payusermap['pay_status'] = '1';
        } elseif ($map['pay_status'] === '0') {
            $payusermap['pay_status'] = '2';
        } else {
            $payusermap['pay_status'] = '1';
        }
        $map = [];
        $exend['row'] = 99999999;
        $exend['no_paginate'] = 1;
        //累计充值
        $exend['field'] = 'sum(pay_amount) as total,sum(if(is_check=1,ratio_money,0)) as total_ratio_money,sum(dec_balance) as total_dec_balance';
        $map['pay_status'] = 1;
        $map['sdk_version'] = $payusermap['sdk_version'];
        $total = $base->data_list($model, $payusermap, $exend);
        //今日充值
        $map['pay_time'] = ['between', total(1, 2)];
        $today = $base->data_list($model, $map, $exend);
        //昨日充值
        $map['pay_time'] = ['between', total(5, 2)];
        $yestoday = $base->data_list($model, $map, $exend);
        $this->assign("total", $total[0]);//累计充值
        $this->assign("today", $today[0]);//今日充值
        $this->assign("yestoday", $yestoday[0]);//累计充值

        return $data;
    }

    public function user_record_lists($request)
    {
        $base = new BaseController();
        $model = new UserLoginRecordModel();
        $map = [];
        if(!empty($request['open_user_id'])){
            $map['open_user_id'] = $request['open_user_id'];
        }

        if(!empty($request['platform_id'])){
            $map['platform_id'] = $request['platform_id'];
        }
        $rangepickdate = $request['rangepickdate'];
        if($rangepickdate) {
            $dateexp = explode('至', $rangepickdate);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            $map['login_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
        }else{
            $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
            $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
        }
        //查询字段
        $exend['field'] = 'user_id';
        $exend['row'] = $request['row'] ?: config('paginate.list_rows');//每页数量
        $exend['group'] = 'user_id';
        //关联游戏类型表
        $data = $base -> data_list($model, $map, $exend);
        return $data;
    }



    /**
     * @充值汇总
     *
     * @author: zsl
     * @since: 2020/7/21 17:31
     */
    public function paySummary($param)
    {
        $result = ['code' => 1, 'msg' => '请求成功', 'data' => []];
        $mUser = new UserModel();
        $mSpend = new SpendModel();
        $timeArr = Time ::getDates($param['start_time'], $param['end_time']);
        $timeArr = array_reverse($timeArr);
        $data = [];
        foreach ($timeArr as $k => $time) {
            $start = strtotime($time);
            $end = strtotime($time) + 86399;
            //新增玩家
            $newUser = $mUser -> newAddUser($start, $end, $param);
            $newUserNum = count($newUser);//新增玩家数量
            //活跃玩家
            $activeUser = $mUser -> activeUser($start, $end, $param);
            $activeNum = count($activeUser);//活跃玩家数量
            //新增玩家付费
            $param['user_id'] = $newUser;
            $newUserAmount = $mSpend -> totalAmount($start, $end, $param);
            //累计充值
            unset($param['user_id']);
            $totalAmount = $mSpend -> totalAmount($start, $end, $param);
            //累计结算金额
            $totalFenfa = $mSpend -> totalFenfa($start, $end, $param);
            $data[$k]['date'] = $time;
            $data[$k]['newUser'] = $newUser;
            $data[$k]['newUserNum'] = $newUserNum;
            $data[$k]['activeUser'] = $activeUser;
            $data[$k]['activeNum'] = $activeNum;
            $data[$k]['newUserAmount'] = $newUserAmount;
            $data[$k]['totalAmount'] = $totalAmount;
            $data[$k]['totalFenfa'] = $totalFenfa;
        }
        $result['data'] = $data;
        return $result;
    }


}
