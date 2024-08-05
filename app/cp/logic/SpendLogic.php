<?php
namespace app\cp\logic;

use app\cp\model\SpendModel;
use think\Db;

class SpendLogic
{
    // 获取游戏订单
    public function getGameSpend($param, $option='')
    {
        $game_id = $param['game_id'];
        $start_time = strtotime($param['start_time']);
        $end_time = strtotime($param['end_time']) + 86400;
        $map = [];
        if(!empty($game_id)){
            $map['g.id'] = $game_id;
        }
        if(!empty($start_time)){
            $map['s.pay_time'] = ['>',$start_time];
        }
        if(!empty($end_time)){
            $map['s.pay_time'] = ['<',$end_time];
        }

        if(!empty($start_time) && !empty($end_time)){
            $map['s.pay_time'] = ['between',[$start_time, $end_time]];
        }

        $map['s.cp_id'] = 0;
        $map['s.pay_status'] = 1;

        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $spendM = new SpendModel();

        if($option == 'all'){
            $spandList = $spendM->alias('s')
                ->field('s.game_id,s.pay_time,g.game_name,sum(pay_amount) as total_pay_amount,ga.cp_ratio,ga.cp_pay_ratio,sum(cost) as total_cost')
                ->group('s.game_id')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id', 'right')
                ->join(['tab_game_attr' => 'ga'], 's.game_id=ga.game_id', 'left')
                ->where($map)
                ->select();
            return $spandList;
        }
        if($option == 'page'){
            $spandList = $spendM->alias('s')
                ->field('s.game_id,s.pay_time,g.game_name,sum(pay_amount) as total_pay_amount,ga.cp_ratio,ga.cp_pay_ratio,sum(cost) as total_cost')
                ->group('s.game_id')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id', 'right')
                ->join(['tab_game_attr' => 'ga'], 's.game_id=ga.game_id', 'left')
                ->order('s.pay_time DESC')
                ->where($map)
                ->paginate($row, false, ['query' => $param]);
            return $spandList;
        }


    }
    // 获取某一汇总下的详细列表
    public function getGameSpendCollectDetail($param, $option='')
    {
        $game_id = $param['game_id'];
        $start_time = $param['start_time'];
        $end_time = $param['end_time'];
        $type = $param['type']; // 1 手游 2 H5 3 页游

        $start_time = $start_time - 8*3600; // js 转换后是从8点开始的(东八区)
        $end_time = $end_time + 16*3600 -1; // 道理同上

        $map = [];
        if(!empty($game_id)){
            $map['g.id'] = $game_id;
        }
        if(!empty($start_time) && !empty($end_time)){
            $map['s.pay_time'] = ['between',[$start_time,$end_time]];
        }
        $map['s.cp_id'] = 0;
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

        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $spendM = new SpendModel();
        if($option == 'all'){
            $spandList = $spendM->alias('s')
                ->field('s.pay_order_number,s.promote_id,s.promote_account,s.user_id,s.user_account,s.server_id,s.server_name,s.game_player_id,s.game_player_name,s.game_id,s.pay_time,g.game_name,s.pay_amount,s.spend_ip,s.pay_way,ga.cp_ratio,ga.cp_pay_ratio')
                // ->group('s.game_id')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id', 'right')
                ->join(['tab_game_attr' => 'ga'], 's.game_id=ga.game_id', 'left')
                ->where($map)
                ->select();
            return $spandList;
        }
        if($option == 'page'){
            $spandList = $spendM->alias('s')
                ->field('s.pay_order_number,s.promote_id,s.promote_account,s.user_id,s.user_account,s.server_id,s.server_name,s.game_player_id,s.game_player_name,s.game_id,s.pay_time,g.game_name,s.pay_amount,s.spend_ip,s.pay_way,ga.cp_ratio,ga.cp_pay_ratio')
                ->join(['tab_game' => 'g'], 'g.id=s.game_id', 'right')
                ->join(['tab_game_attr' => 'ga'], 's.game_id=ga.game_id', 'left')
                ->order('s.pay_time DESC')
                ->where($map)
                ->paginate($row, false, ['query' => $param]);
            return $spandList;
        }
    }
    // cp结算操作
    public function doSettlement($game_ids_arr, $start_time, $end_time)
    {
        // $spendM = new SpendModel();
        foreach($game_ids_arr as $k=>$v){
            // 启动事务
            $game_id = $v;
            Db::startTrans();
            try{
                $game_info = Db::table('tab_game')->alias('g')
                ->join(['tab_game_attr' => 'ga'], 'g.id=ga.game_id', 'left')
                ->field('g.id as game_id,g.game_name,g.cp_id,ga.cp_ratio,ga.cp_pay_ratio')
                ->where(['g.id'=>$game_id])
                ->find();

                $spend_map = [];
                $spend_map['game_id'] = $game_id;
                // $spend_map['pay_time'] = ['>',$start_time];
                // $spend_map['pay_time'] = ['<',$end_time];
                $spend_map['pay_time'] = ['between',[$start_time, $end_time]];

                $spend_map['cp_id'] = 0;
                $spend_map['pay_status'] = 1;
            
                $spendTotal = Db::table('tab_spend')
                    ->where($spend_map)
                    ->field('game_id,sum(pay_amount) as total_pay_amount,sum(cost) as total_cost')
                    ->group('game_id')
                    ->find();
                $total_amount = $spendTotal['total_pay_amount']; // 指定游戏 这个时间段的总付费
                $total_cost = $spendTotal['total_cost']; // 指定游戏 这个时间段的订单金额总和

                // 将这个游戏的结算信息写入 CP结算 记录表
                $order_num = $this->create_order();
                $d_time = time();
                $period_id = Db::table('tab_cp_settlement_period')
                    ->insertGetId([
                        'cp_id'=>$game_info['cp_id'],
                        'game_id'=>$game_id,
                        'order_num'=>$order_num,
                        'total_money'=>$total_amount,
                        'total_cost'=>$total_cost,
                        'party_ratio'=>100 - $game_info['cp_ratio'],
                        'cp_ratio'=>$game_info['cp_ratio'],
                        'party_money'=>$total_amount * (1-$game_info['cp_pay_ratio']/100) * ((100 - $game_info['cp_ratio'])/100),
                        'cp_money'=>$total_amount * (1-$game_info['cp_pay_ratio']/100) * ($game_info['cp_ratio']/100),
                        'cp_pay_ratio'=>$game_info['cp_pay_ratio'],
                        'cp_pay_money'=>$total_amount * ($game_info['cp_pay_ratio']/100),
                        'start_time'=>$start_time,
                        'end_time'=>$end_time,
                        'is_remit'=>0,
                        'remit_time'=>'',
                        'create_time'=>$d_time
                    ]);

                Db::table('tab_spend')->where($spend_map)->update(['cp_id'=>$game_info['cp_id'],'cp_settlement_period_id'=>$period_id]);

                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                // echo $e->getMessage();
                Db::rollback();
            }

        }
        return 1;
    }

    // 生成订单号
    private function create_order(){
        $a = uniqid(mt_rand());
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $length = strlen($str) - 1; //获取字符串的长度
        //2.字符串截取bai开始位置
        $start=rand(0,$length-3);
        //3.字符串截取长度 6 位
        $count = 3; // 取六位
        $use_str = str_shuffle($str); // 随机打乱字符串
        $b = substr($use_str, $start, $count); // 截取字符串，取其中的一部分字符串
        $final_name = 'JS-'.date('Ymd').$a.$b;
        return $final_name;
    }
}
