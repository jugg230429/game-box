<?php
namespace app\cp\logic;

use app\cp\model\CpSettlementPeriodModel;
use app\cp\model\SpendModel;
use think\Db;

class CpSettlementPeriodLogic
{
    /**
     * 获取CP结算记录
     * created by wjd 2021-9-3 21:21:53
    */
    public function getSettlementRecord($param, $option='')
    {
        $game_id = $param['game_id'];
        $start_time = strtotime($param['start_time']);
        $end_time = strtotime($param['end_time']);

        $start_time2 = strtotime($param['start_time2']);
        $end_time2 = strtotime($param['end_time2']);
        $type = $param['type'] ?? 1; // 1手游 2 H5 3页游
        
        $map = [];
        if(!empty($game_id)){
            $map['s.game_id'] = $game_id;
        }
        // g.sdk_version  区别版本   1安卓 2苹果 3 H5 4页游
        if($type==1){ $map['g.sdk_version'] = ['in', [1,2]]; }
        if($type==2){ $map['g.sdk_version'] = 3; }
        if($type==3){ $map['g.sdk_version'] = 4; }
        
        if ($start_time && $end_time) {
            $map['s.start_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['s.end_time'] = ['<', ($end_time) + 86399];
        } elseif ($start_time) {
            $map['s.end_time'] = ['>=', ($start_time)];
        }

        if ($start_time2 && $end_time2) {
            $map['s.create_time'] = ['between', [($start_time2), ($end_time2) + 86399]];
        } elseif ($end_time2) {
            $map['s.create_time'] = ['lt', ($end_time2) + 86399];
        } elseif ($start_time2) {
            $map['s.create_time'] = ['egt', ($start_time2)];
        }

        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $cpSettlementRecord_M = new CpSettlementPeriodModel();
        
        if($option == 'all'){
            $recordList = $cpSettlementRecord_M
                ->alias('s')
                ->field('s.*,g.game_name')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id', 'INNER')
                ->order('create_time DESC')
                ->where($map)
                ->select();
            return $recordList;
        }

        if($option == 'page'){
            $recordList = $cpSettlementRecord_M
                ->alias('s')
                ->field('s.*,g.game_name')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id', 'INNER')
                ->order('create_time DESC')
                ->where($map)
                ->paginate($row, false, ['query' => $param]);
            return $recordList;
        }
    }

    /**
     * 获取某一个结算记录的详情列表
     * created by wjd 2021-9-4 10:03:58
    */
    public function getSettlementPeriodRecordList($param, $option='',$other=[])
    {
        $cp_settlement_period_id = $param['cp_settlement_period_id'];
        $type = $param['type']; // 1 手游 2 H5 3 页游

        $periodRecordInfo = (new CpSettlementPeriodModel())
            ->field('id,cp_id,game_id,start_time,end_time')
            ->where(['id'=>$cp_settlement_period_id])
            ->find();
        // var_dump($periodRecordInfo);exit;
        $start_time = $periodRecordInfo['start_time'];
        $end_time = $periodRecordInfo['end_time'];
        $cp_id = $periodRecordInfo['cp_id'];
        
        $map = [];
        if(!empty($start_time)){
            $map['s.pay_time'] = ['>',$start_time];
        }
        if(!empty($end_time)){
            $map['s.pay_time'] = ['<',$end_time];
        }
        $map['s.cp_id'] = $cp_id;
        $map['s.pay_status'] = 1;
        if($type == 1){
            $map['g.sdk_version'] = ['in', [1,2]];
        }
        if($type == 2){
            $map['g.sdk_version'] = 3;
        }
        if($type == 3){
            $map['g.sdk_version'] = 4;
        }
        $map['cp_settlement_period_id'] = $cp_settlement_period_id;  //(2021-9-17 16:47:41, 有了这一个条件 上面的 start_time end_time  cp_id 三个条件应该都可以去掉)
        
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $spendM = new SpendModel();
        if($option == 'all'){
            $periodRecordList = $spendM->alias('s')
                ->field('s.pay_order_number,s.promote_id,s.promote_account,s.user_id,s.user_account,s.server_id,s.server_name,s.game_player_id,s.game_player_name,s.game_id,s.pay_time,g.game_name,s.pay_amount,s.spend_ip,s.pay_way,ga.cp_ratio,ga.cp_pay_ratio')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id', 'right')
                ->join(['tab_game_attr' => 'ga'], 's.game_id=ga.game_id', 'left')
                ->order('s.pay_time DESC')
                ->where($map)
                ->select();
            return $periodRecordList;
        }
        if($option == 'page'){
            $periodRecordList = $spendM->alias('s')
                ->field('s.pay_order_number,s.promote_id,s.promote_account,s.user_id,s.user_account,s.server_id,s.server_name,s.game_player_id,s.game_player_name,s.game_id,s.pay_time,g.game_name,s.pay_amount,s.spend_ip,s.pay_way,ga.cp_ratio,ga.cp_pay_ratio')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id', 'right')
                ->join(['tab_game_attr' => 'ga'], 's.game_id=ga.game_id', 'left')
                ->order('s.pay_time DESC')
                ->where($map)
                ->paginate($row, false, ['query' => $param]);
            return $periodRecordList;
        }
    }

    /**
     * 获取已打款记录
    */
    public function getRemitRecord($param, $option='',$other=[])
    {
        $game_id = $param['game_id'];
        $start_time = strtotime($param['start_time']);
        $end_time = strtotime($param['end_time']);
        $cp_id = $param['cp_id'];
        $order_num = $param['order_num'];
        
        $map = [];
        if(!empty($game_id)){
            $map['s.game_id'] = $game_id;
        }

        if(!empty($cp_id)){
            $map['s.cp_id'] = $cp_id;
        }

        if(!empty($order_num)){
            $map['s.order_num'] = ['like', '%'.$order_num.'%'];
        }
        
        if ($start_time && $end_time) {
            $map['s.remit_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['s.remit_time'] = ['<', ($end_time) + 86399];
        } elseif ($start_time) {
            $map['s.remit_time'] = ['>=', ($start_time)];
        }
        $map['is_remit'] = 1;

        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $cpSettlementRecord_M = new CpSettlementPeriodModel();
        
        if($option == 'all'){
            $recordList = $cpSettlementRecord_M
                ->alias('s')
                ->field('s.*,c.cp_name')
                ->join(['tab_game_cp' => 'c'], 'c.id=s.cp_id', 'left')
                ->order('create_time DESC')
                ->where($map)
                ->select();
            return $recordList;
        }

        if($option == 'page'){
            $recordList = $cpSettlementRecord_M
                ->alias('s')
                ->field('s.*,c.cp_name')
                ->join(['tab_game_cp' => 'c'], 'c.id=s.cp_id', 'left')
                ->order('create_time DESC')
                ->where($map)
                ->paginate($row, false, ['query' => $param]);
            return $recordList;
        }
    }
}
