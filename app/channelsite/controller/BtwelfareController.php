<?php

# 推广员注册福利控制器 
# create at 2021-1-15 10:19:04
# wjd 
# contain methods five as follows

namespace app\channelsite\controller;

use app\btwelfare\logic\BtWelfareLogic;
use app\btwelfare\logic\MonthCardLogic;
use app\btwelfare\logic\RechargeLogic;
use app\btwelfare\logic\RegisterLogic;
use app\btwelfare\logic\TotalRechargeLogic;
use app\btwelfare\logic\WeekCardLogic;
use think\Db;
use think\Request;
use app\promote\model\PromoteModel;
use app\promote\model\PromoteapplyModel;
use app\common\controller\BaseHomeController;

class BtwelfareController extends BaseController
{   
    private $promote_id;
    public function __construct()
    {
        parent::__construct();
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('index/index'));
            };
        }
        $this->promote_id = PID;
    }
    // method one
    // 注册福利
    public function register_welfare(Request $request){
        // echo '注册福利'; exit
        
        $tmp_data = $request->param();
        $search_term = [
            'promote_id'=>$this->promote_id,
            'game_id'=>$tmp_data['game_id'],
            'user_account'=>$tmp_data['user_account'],
            'game_player_name'=>$tmp_data['role_name'],
            'login_start_time'=>$tmp_data['start_time'],
            'login_end_time'=>$tmp_data['end_time'],
            'status'=>$tmp_data['status'],
            'row'=>$tmp_data['row'],
        ];
        if(!empty($tmp_data['promote_id'])){
            $search_term['promote_id'] = $tmp_data['promote_id'];
        }
        // 根据区服id查找cp的区服信息
        $server_id = $tmp_data['server_id'];
        if(!empty($server_id)){
            $sever_info = Db::table('tab_game_server')->where(['id'=>$server_id])->find();
            $server_num = $sever_info['server_num'];  // 对接区服id
            if(!empty($server_num)){
                $search_term['server_id'] = $server_num;
            }
        }
        // end_time: "2021-01-18"
        // game_id: "2"
        // promote_id: "4"
        // role_name: "狠角色"
        // server_id: "1"
        // start_time: "2021-01-17"
        // user_account: "asdasd"

        // return json($request->param());exit;
        
        $register_c = new RegisterLogic;

        $search_data = $register_c->adminLists($search_term);
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($search_data as $k5=>$v5){

            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $search_data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
            //增加处理角色查看隐私情况
            if($ys_show_promote['role_show_promote_status'] == 1){//开启了角色查看隐私1
                $search_data[$k5]['game_player_name'] = get_ys_string($v5['game_player_name'],$ys_show_promote['role_show_promote']);
            }
        }

        // 获取分页显示
        $page = $search_data->render();
        $this->assign("page", $page);
        $this->assign("data_lists", $search_data);
        // 注册福利 是否自助发放
        
        $promote_info = Db::table('tab_promote')->where("id = ".$this->promote_id)->find();
        $bt_welfare_register_auto = $promote_info['bt_welfare_register_auto'] ?? 0;
        $this->assign("bt_welfare_register_auto", $bt_welfare_register_auto);
        // return  json($search_data);
        return $this->fetch();
        // [{"id":5,"prop_name":"\u91d1\u5e01*100000","prop_tag":"gold","number":123},{"id":6,"prop_name":"\u5143\u5b9d*100","prop_tag":"gold2","number":222}] // 道具
    }
    // method two
    // 充值福利
    public function recharge_welfare(Request $request){
        // echo '充值福利'; exit;
        $tmp_data = $request->param();
        $search_term = [
            'promote_id'=>$this->promote_id,
            'game_id'=>$tmp_data['game_id'],
            'user_account'=>$tmp_data['user_account'],
            'game_player_name'=>$tmp_data['role_name'],
            'start_time'=>$tmp_data['start_time'],
            'end_time'=>$tmp_data['end_time'],
            'status'=>$tmp_data['status'],
            'row'=>$tmp_data['row'],
        ];
        if(!empty($tmp_data['promote_id'])){
            $search_term['promote_id'] = $tmp_data['promote_id'];
        }

        // 根据区服id查找cp的区服信息
        $server_id = $tmp_data['server_id'];
        if(!empty($server_id)){
            $sever_info = Db::table('tab_game_server')->where(['id'=>$server_id])->find();
            $server_num = $sever_info['server_num'];  // 对接区服id
            if(!empty($server_num)){
                $search_term['server_id'] = $server_num;
            }
        }
        
        $recharge_c = new RechargeLogic;
        $search_data = $recharge_c->adminLists_2($search_term)->each(function($item, $key){
            $item->pay_way_name = '';
            if($item->pay_way == 1){
                $item->pay_way_name = '綁币';
            }
            if($item->pay_way == 2){
                $item->pay_way_name = '平台币';
            }
            if($item->pay_way == 3){
                $item->pay_way_name = '支付宝';
            }
            if($item->pay_way == 4){
                $item->pay_way_name = '微信';
            }
            if($item->pay_way == 5){
                $item->pay_way_name = '谷歌';
            }
            if($item->pay_way == 5){
                $item->pay_way_name = '苹果支付';
            }
        });
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($search_data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $search_data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
            //增加处理角色查看隐私情况
            if($ys_show_promote['role_show_promote_status'] == 1){//开启了角色查看隐私1
                $search_data[$k5]['game_player_name'] = get_ys_string($v5['game_player_name'],$ys_show_promote['role_show_promote']);
            }
        }
        
        // 获取分页显示
        $page = $search_data->render();
        $this->assign("page", $page);
        $this->assign("data_lists", $search_data);
        // 注册福利 是否自助发放
        $promote_info = Db::table('tab_promote')->where("id = ".$this->promote_id)->find();
        $bt_welfare_recharge_auto = $promote_info['bt_welfare_recharge_auto'] ?? 0;
        $this->assign("bt_welfare_recharge_auto", $bt_welfare_recharge_auto);
        // var_dump($search_data);exit;
        // return  json($search_data);
        return $this->fetch();
    }
    // method three
    // 累充福利
    public function total_welfare(Request $request){
        // echo '累充福利'; exit;
        $tmp_data = $request->param();
        $search_term = [
            'promote_id'=>$this->promote_id,
            'game_id'=>$tmp_data['game_id'],
            'user_account'=>$tmp_data['user_account'],
            'game_player_name'=>$tmp_data['role_name'],
            'game_player_id'=>$tmp_data['role_id'],
            'login_start_time'=>$tmp_data['start_time'],
            'login_end_time'=>$tmp_data['end_time'],
            'status'=>$tmp_data['status'],
            'row'=>$tmp_data['row'],
        ];
        if(!empty($tmp_data['promote_id'])){
            $search_term['promote_id'] = $tmp_data['promote_id'];
        }
        // 根据区服id查找cp的区服信息
        $server_id = $tmp_data['server_id'];
        if(!empty($server_id)){
            $sever_info = Db::table('tab_game_server')->where(['id'=>$server_id])->find();
            $server_num = $sever_info['server_num'];  // 对接区服id
            if(!empty($server_num)){
                $search_term['server_id'] = $server_num;
            }
        }
        
        $total_recharge_c = new TotalRechargeLogic;

        $search_data = $total_recharge_c->adminLists($search_term);
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($search_data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $search_data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
            //增加处理角色查看隐私情况
            if($ys_show_promote['role_show_promote_status'] == 1){//开启了角色查看隐私1
                $search_data[$k5]['game_player_name'] = get_ys_string($v5['game_player_name'],$ys_show_promote['role_show_promote']);
            }
        }
        

        // 获取分页显示
        $page = $search_data->render();
        $this->assign("page", $page);
        $this->assign("data_lists", $search_data);
        // 注册福利 是否自助发放
        
        $promote_info = Db::table('tab_promote')->where("id = ".$this->promote_id)->find();
        $bt_welfare_total_auto = $promote_info['bt_welfare_total_auto'] ?? 0;
        $this->assign("bt_welfare_total_auto", $bt_welfare_total_auto);
        // return  json($search_data);
        return $this->fetch();
    }
    // method four
    // 月卡福利
    public function month_card_welfare(Request $request){
        // echo '月卡福利'; exit;
        $tmp_data = $request->param();
        $search_term = [
            'promote_id'=>$this->promote_id,
            'game_id'=>$tmp_data['game_id'],
            'user_account'=>$tmp_data['user_account'],
            'game_player_name'=>$tmp_data['role_name'],
            'game_player_id'=>$tmp_data['role_id'],
            // 'login_start_time'=>$tmp_data['start_time'],
            // 'login_end_time'=>$tmp_data['end_time'],
            'status'=>$tmp_data['status'],
            'row'=>$tmp_data['row'],
        ];
        if(!empty($tmp_data['promote_id'])){
            $search_term['promote_id'] = $tmp_data['promote_id'];
        }
        // 根据区服id查找cp的区服信息
        $server_id = $tmp_data['server_id'];
        if(!empty($server_id)){
            $sever_info = Db::table('tab_game_server')->where(['id'=>$server_id])->find();
            $server_num = $sever_info['server_num'];  // 对接区服id
            if(!empty($server_num)){
                $search_term['server_id'] = $server_num;
            }
        }
        
        $register_c = new MonthCardLogic;

        $search_data = $register_c->adminLists($search_term);
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($search_data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $search_data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
            //增加处理角色查看隐私情况
            if($ys_show_promote['role_show_promote_status'] == 1){//开启了角色查看隐私1
                $search_data[$k5]['game_player_name'] = get_ys_string($v5['game_player_name'],$ys_show_promote['role_show_promote']);
            }
        }
        

        // 获取分页显示
        $page = $search_data->render();
        $this->assign("page", $page);
        // 判断今日是否已经发放 status=1 已发放 status=0 未发放
        $search_data->each(function($item, $key){
            // 总福利天数
            $item->total_days = $item->total_number * 30;
            // 判断是否已经发放, 是否过期
            $item->status = -1;
            // $time_a = date('Y-m-d', $item->last_send_time);
            // $time_b = strtotime($time_a);
            $d_time = time();
            $today_time_start = strtotime(date('Y-m-d', $d_time));
            $today_time_end = $today_time_start + 86400;

            if($item->last_send_time < $today_time_start){
                $item->status = 0; // 今日未发放.可以进行发放操作
            }

            if($item->last_send_time > $today_time_start && $item->last_send_time < $today_time_end){
                $item->status = 1; // 今日已发放
            }
            // 月卡过期时间 expire_time
            $item->expire_time;
            if($d_time > $item->expire_time ){
                $item->status = 2; // 已过期
            }
            
        });

        $this->assign("data_lists", $search_data);
        // 注册福利 是否自助发放
        $promote_info = Db::table('tab_promote')->where("id = ".$this->promote_id)->find();
        $bt_welfare_month_auto = $promote_info['bt_welfare_month_auto'] ?? 0;
        $this->assign("bt_welfare_month_auto", $bt_welfare_month_auto);
        // 汇总数据
        $map_tmp = [];
        $map_tmp['promote_id'] = $this->promote_id;
        if(!empty($tmp_data['promote_id'])){
            $map_tmp['promote_id'] = $tmp_data['promote_id'];
        }
        if(!empty($tmp_data['game_id'])){
            $map_tmp['game_id'] = $tmp_data['game_id'];
        }
        if(!empty($tmp_data['user_account'])){
            $map_tmp['user_account'] = $tmp_data['user_account'];
        }
        if(!empty($tmp_data['role_name'])){
            $map_tmp['game_player_name'] = $tmp_data['role_name'];
        }
        if(!empty($tmp_data['role_id'])){
            $map_tmp['game_player_id'] = $tmp_data['role_id'];
        }
        if(!empty($tmp_data['status'])){
            $map_tmp['status'] = $tmp_data['status'];
        }
        $res_total = Db::table('tab_bt_welfare_monthcard')->where($map_tmp)->field('sum(`total_number`) as tt_number,sum(`already_send_num`) as tt_already_send_num')->find();
        $res_total['total_days'] = $res_total['tt_number'] * 30;
        $this->assign('res_total', $res_total);
        // return  json($res_total);
        return $this->fetch();
    }
    // method five
    // 周卡福利
    public function week_card_welfare(Request $request){
        // echo '周卡福利'; exit;
        $tmp_data = $request->param();
        $search_term = [
            'promote_id'=>$this->promote_id,
            'game_id'=>$tmp_data['game_id'],
            'user_account'=>$tmp_data['user_account'],
            'game_player_name'=>$tmp_data['role_name'],
            'game_player_id'=>$tmp_data['role_id'],
            // 'login_start_time'=>$tmp_data['start_time'],
            // 'login_end_time'=>$tmp_data['end_time'],
            'status'=>$tmp_data['status'],
            'row'=>$tmp_data['row'],
        ];
        if(!empty($tmp_data['promote_id'])){
            $search_term['promote_id'] = $tmp_data['promote_id'];
        }
        // 根据区服id查找cp的区服信息
        $server_id = $tmp_data['server_id'];
        if(!empty($server_id)){
            $sever_info = Db::table('tab_game_server')->where(['id'=>$server_id])->find();
            $server_num = $sever_info['server_num'];  // 对接区服id
            if(!empty($server_num)){
                $search_term['server_id'] = $server_num;
            }
        }
        
        $register_c = new WeekCardLogic;

        $search_data = $register_c->adminLists($search_term);
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($search_data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $search_data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
            //增加处理角色查看隐私情况
            if($ys_show_promote['role_show_promote_status'] == 1){//开启了角色查看隐私1
                $search_data[$k5]['game_player_name'] = get_ys_string($v5['game_player_name'],$ys_show_promote['role_show_promote']);
            }
        }
        
        
        // 获取分页显示
        $page = $search_data->render();
        $this->assign("page", $page);
        // 判断今日是否已经发放 status=1 已发放 status=0 未发放
        $search_data->each(function($item, $key){
            // 总福利天数
            $item->total_days = $item->total_number * 7;
            // 判断是否已经发放, 是否过期
            $item->status = -1;
            // $time_a = date('Y-m-d', $item->last_send_time);
            // $time_b = strtotime($time_a);
            $d_time = time();
            $today_time_start = strtotime(date('Y-m-d', $d_time));
            $today_time_end = $today_time_start + 86400;

            if($item->last_send_time < $today_time_start){
                $item->status = 0; // 今日未发放.可以进行发放操作
            }

            if($item->last_send_time > $today_time_start && $item->last_send_time < $today_time_end){
                $item->status = 1; // 今日已发放
            }
            // 月卡过期时间 expire_time
            $item->expire_time;
            if($d_time > $item->expire_time ){
                $item->status = 2; // 已过期
            }
            
        });
        $this->assign("data_lists", $search_data);
        // 注册福利 是否自助发放
        
        $promote_info = Db::table('tab_promote')->where("id = ".$this->promote_id)->find();
        $bt_welfare_week_auto = $promote_info['bt_welfare_week_auto'] ?? 0;
        $this->assign("bt_welfare_week_auto", $bt_welfare_week_auto);
        // 汇总数据
        $map_tmp = [];
        $map_tmp['promote_id'] = $this->promote_id;
        if(!empty($tmp_data['promote_id'])){
            $map_tmp['promote_id'] = $tmp_data['promote_id'];
        }
        if(!empty($tmp_data['game_id'])){
            $map_tmp['game_id'] = $tmp_data['game_id'];
        }
        if(!empty($tmp_data['user_account'])){
            $map_tmp['user_account'] = $tmp_data['user_account'];
        }
        if(!empty($tmp_data['role_name'])){
            $map_tmp['game_player_name'] = $tmp_data['role_name'];
        }
        if(!empty($tmp_data['role_id'])){
            $map_tmp['game_player_id'] = $tmp_data['role_id'];
        }
        if(!empty($tmp_data['status'])){
            $map_tmp['status'] = $tmp_data['status'];
        }
        $res_total = Db::table('tab_bt_welfare_weekcard')->where($map_tmp)->field('sum(`total_number`) as tt_number,sum(`already_send_num`) as tt_already_send_num,sum(`gt_six_four_eight`) as gt648,sum(`gt_thousand_of_day`) as gt1000')->find();
        $res_total['total_days'] = $res_total['tt_number'] * 7;
        $this->assign('res_total', $res_total);
        // return  json($search_data);
        return $this->fetch();
    }

    // 执行发放操作 $this->promote_id
    // tmp_type 1:注册奖励,2:充值奖励,3:累充奖励,4:月卡奖励,5:周卡奖励
    public function do_grant(Request $request)
    {
        $type = (int) $request -> param('type');
        $id = $this -> request -> param('id');
        $lBtwelfare = new BtWelfareLogic();
        $result = $lBtwelfare -> send($type, $id);
        return json($result);
    }
    // 针对未发放的玩家 批量发放操作
    // tmp_type 1:注册奖励,2:充值奖励,3:累充奖励,4:月卡奖励,5:周卡奖励
    public function batch_grant(Request $request)
    {
        $type = (int) $request -> param('type');
        $ids = $this -> request -> param('tmp_ids/a');
        if (empty($ids)) {
            $this -> error('请选择要发放的数据');
        }
        foreach ($ids as $id) {
            $lBtwelfare = new BtWelfareLogic();
            $lBtwelfare -> send($type, $id);
        }
        $this -> success('操作成功');
    }

    // 调整是否自助发放  $this->promote_id
    // param tmp_type 1:自助发放注册奖励,2:自助发放充值奖励,3:自助发放累充奖励,4:自助发放月卡奖励,5:自助发放周卡奖励
    public function set_welfare_auto_grant(Request $request){
        $tmp_type = (int) $request->param('tmp_type');
        $change_auto = (int) $request->param('change_auto');
        $set_res = 0;
        // var_dump($tmp_type);
        // var_dump($change_auto);
        // exit;
        if($tmp_type == 1){
            $set_res = Db::table('tab_promote')->where('id = '.$this->promote_id)->setField('bt_welfare_register_auto', $change_auto);
        }
        if($tmp_type == 2){
            $set_res = Db::table('tab_promote')->where('id = '.$this->promote_id)->setField('bt_welfare_recharge_auto', $change_auto);
        }
        if($tmp_type == 3){
            $set_res = Db::table('tab_promote')->where('id = '.$this->promote_id)->setField('bt_welfare_total_auto', $change_auto);
        }
        if($tmp_type == 4){
            $set_res = Db::table('tab_promote')->where('id = '.$this->promote_id)->setField('bt_welfare_month_auto', $change_auto);
        }
        if($tmp_type == 5){
            $set_res = Db::table('tab_promote')->where('id = '.$this->promote_id)->setField('bt_welfare_week_auto', $change_auto);
        }

        if($set_res){
            $this->success('操作成功!');
        } else {
            $this->error('操作失败!');
        }
    }

    // 根据游戏名称获取区服列表

    public function get_server(Request $request)
    {
        $game_id = $request->param('game_id');
        $result = ['code' => 1, 'msg' => '', 'data' => []];
        if (empty($game_id)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return $result;
        }
        $where = [];
        $where['game_id'] = $game_id;
        $lists = Db ::table('tab_game_server') -> field('id,server_name,server_num') -> where($where) -> order('id desc') -> select();
        if (!empty($lists)) {
            $result['data'] = $lists;
        }
        // return $result;
        return json($result);
    }

}