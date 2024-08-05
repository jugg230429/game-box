<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\cp\controller;

use app\common\controller\BaseController;
use app\cp\logic\CpSettlementPeriodLogic;
use app\cp\logic\SpendLogic;
use app\cp\model\CpSettlementPeriodModel;
use app\cp\model\SpendModel;
use cmf\controller\AdminBaseController;

use think\Request;
use think\Db;

class SettlementController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_PROMOTE != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买渠道权限');
            } else {
                $this->error('请购买渠道权限', url('admin/main/index'));
            };
        }
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
        if (AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('admin/main/index'));
            };
        }
        if (AUTH_PAY != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买充值权限');
            } else {
                $this->error('请购买充值权限', url('admin/main/index'));
            };
        }
    }

    /**
     * 游戏结算
     * created by wjd 2021-9-2 21:10:04
    */
    public function game_settlement(Request $request)
    {
        $param = $request->param();
        $game_id = $param['game_id'] ?? 0;
        $start_time = $param['start_time'] ?? 0;
        $end_time = $param['end_time'] ?? 0;
        if(empty($start_time) && !empty($end_time)){
            return $this->error("请选择开始时间!");
        }
        if(!empty($start_time) && empty($end_time)){
            return $this->error("请选择结束时间!");
        }
        if(empty($game_id) &&  empty($start_time) && empty($end_time)){
            return $this->fetch();
        }
        if(!empty($game_id) && (empty($end_time) || empty($start_time))){
            return $this->error("请选择时间段!");
        }

        $spendList = (new SpendLogic())->getGameSpend($param, 'page');
        $spendListAll = (new SpendLogic())->getGameSpend($param, 'all');

        // 甲方分成金额 = 累计充值 *（1-CP通道费率）*甲方分成比例
        // CP分成金额 = 累计充值 *（1-CP通道费率）*CP分成比例

        $spendList->each(function($item, $key) use($start_time, $end_time){

            $item['period_str'] = ''.$start_time.' 至 '.$end_time.'';
            $item['party_ratio'] = 100 - $item['cp_ratio'];
            $item['party_money'] = $item['total_pay_amount'] * (1-$item['cp_pay_ratio']/100) * ((100 - $item['cp_ratio'])/100);
            $item['cp_money'] = $item['total_pay_amount'] * (1-$item['cp_pay_ratio']/100) * ($item['cp_ratio']/100);
            $item['cp_pay_money'] = $item['total_pay_amount'] * ($item['cp_pay_ratio']/100);
            $item['party_ratio'] = 100 - $item['cp_ratio'];
            return $item;
        });
        $page = $spendList->render();
        $this->assign("data_lists", $spendList);
        $this->assign("page", $page);
        // 汇总
        $total_pay_money = 0;
        $total_party_money = 0;
        $total_cp_money = 0;
        $total_cp_pay_money = 0;
        $total_cost = 0;
        foreach($spendListAll as $k=>$v){
            $total_pay_money += $v['total_pay_amount'];
            $total_cost += $v['total_cost'];
            $total_party_money += $v['total_pay_amount'] * (1-$v['cp_pay_ratio']/100) * ((100 - $v['cp_ratio'])/100);
            $total_cp_money += $v['total_pay_amount'] * (1-$v['cp_pay_ratio']/100) * ($v['cp_ratio']/100);
            $total_cp_pay_money += $v['total_pay_amount'] * ($v['cp_pay_ratio']/100);
        }
        $total_tmp = [
            'total_pay_money'=>$total_pay_money,
            'total_party_money'=>$total_party_money,
            'total_cp_money'=>$total_cp_money,
            'total_cp_pay_money'=>$total_cp_pay_money,
            'total_cost'=>$total_cost,
        ];
        $this->assign("total_tmp", $total_tmp);
        return $this->fetch();

        // return json($spendList);

    }
    // 查看某个周期结算单 的详情
    public function show_detail(Request $request){

        $param = $request->param();
        $game_id = (int) ($param['game_id'] ?? 0);
        $start_time = $param['start_time'] ?? 0;
        $end_time = $param['end_time'] ?? 0;

        if($game_id <= 0){
            return $this->error('缺少必要的参数');
        }
        if((empty($end_time) || empty($start_time))){
            return $this->error("请选择时间段!");
        }

        $spendCollectDetail = (new SpendLogic())->getGameSpendCollectDetail($param, 'page');

        // return json($spendCollectDetail);

        $this->assign('game_id', $game_id);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $page = $spendCollectDetail->render();
        $this->assign("data_lists", $spendCollectDetail);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * 结算记录
     * created by wjd 2021-9-2 21:10:04
    */
    public function settlement_record(Request $request)
    {
        $param = $request->param();
        $type = $param['type'] ?? 1; // 1手游 2 H5 3页游
        $this->assign('type',$type);

        // 获取CP结算记录
        $recordList = (new CpSettlementPeriodLogic())->getSettlementRecord($param, 'page');

        $recordList->each(function($item, $key) {

            $item['period_str'] = ''.date('Y-m-d', $item['start_time']).' 至 '.date('Y-m-d', $item['end_time']).'';
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            return $item;
        });

        $recordListAll = (new CpSettlementPeriodLogic())->getSettlementRecord($param, 'all');


        $total_pay_money = 0;
        $total_party_money = 0;
        $total_cp_money = 0;
        $total_cp_pay_money = 0;
        $total_cost = 0;
        foreach ($recordListAll as $key => $vo) {
            // $vo['period_str'] = ''.date('Y-m-d', $vo['start_time']).' 至 '.date('Y-m-d', $vo['end_time']).'';
            // $vo['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
            $total_pay_money += $vo['total_money'];
            $total_party_money += $vo['party_money'];
            $total_cp_money += $vo['cp_money'];
            $total_cp_pay_money += $vo['cp_pay_money'];
            $total_cost += $vo['total_cost'];
        }
        $total_tmp = [
            'total_pay_money'=>$total_pay_money,
            'total_party_money'=>$total_party_money,
            'total_cp_money'=>$total_cp_money,
            'total_cp_pay_money'=>$total_cp_pay_money,
            'total_cost'=>$total_cost,
        ];
        $this->assign("total_tmp", $total_tmp);
        // return json($recordList);

        $page = $recordList->render();
        $this->assign("data_lists", $recordList);
        $this->assign("page", $page);

        return $this->fetch();
    }
    /**
     * 结算记录详情
     * created by wjd 2021-9-4 09:53:04
    */
    public function show_settlement_period_record(Request $request)
    {
        $param = $request->param();
        $cp_settlement_period_id = (int) ($param['cp_settlement_period_id'] ?? 0);

        if($cp_settlement_period_id <= 0){
            return $this->error('缺少必要的参数');
        }

        $settlement_period_record_list = (new CpSettlementPeriodLogic())->getSettlementPeriodRecordList($param, 'page');

        // return json($spendCollectDetail);

        $this->assign('cp_settlement_period_id', $cp_settlement_period_id);
        $page = $settlement_period_record_list->render();
        $this->assign("data_lists", $settlement_period_record_list);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * 给CP结算生成结算单
     * created by wjd 2021-9-2 21:10:04
    */
    public function create_settlement(Request $request)
    {
        $param = $request->param();
        $ids_str = $param['id'];
        $ids_arr = explode(',', $ids_str);

        // var_dump($ids_arr);exit;
        if(empty($param['start_date']) || empty($param['end_date'])){
            return $this->error("请选择时间段!");
        }
        $start_time = strtotime($param['start_date']);
        $end_time = strtotime($param['end_date']) + 86399;

        if(empty($ids_arr)){
            return $this->error("请选择要结算的数据!");
        }

        $settlementRes = (new SpendLogic())->doSettlement($ids_arr, $start_time, $end_time);

        // $this->success('操作成功');
        $this->success('操作已提交!',url('game_settlement'));

    }

    /**
     * 进行打款
     * created by wjd 2021-9-4 10:51:09
    */
    public function do_remit(Request $request)
    {
        $param = $request->param();
        $id = (int) ($param['id'] ?? 0); // tab_cp_settlement_period
        if($id <= 0){
            $this->error('缺少必要的参数!');
        }
        $cpSettlementPeriod_M = new CpSettlementPeriodModel();
        // 添加数据
        if($request->isPost()){
            // 获取单号 settlement_period_order_num
            $settlement_period_order_num = $param['settlement_period_order_num'];
            if(empty($settlement_period_order_num)){
                $this->error('请选择要操作的单号!');
            }
            $cp_id = $param['cp_id'] ?? '';
            if(empty($cp_id)) { $this->error('请选择CP商!'); }
            // var_dump($param);exit;
            // 修改打款状态
            $d_time = time();
            $updateData = [
                'is_remit'=>1,
                'update_time'=>$d_time,
                'remit_time'=>strtotime($param['remit_time']),
                'remit_amount'=>$param['remit_amount'],
                'name_of_receive'=>$param['name_of_receive'],
                'type_of_receive'=>$param['type_of_receive'],
                'accounts_of_receive'=>$param['accounts_of_receive'],
                'operator'=>$param['operator'],
                'receipt'=>$param['receipt'],
                'remark'=>$param['remark'],

            ];
            $update_res = $cpSettlementPeriod_M
                ->where(['order_num'=>['in', $settlement_period_order_num], 'cp_id'=>$cp_id])
                ->update($updateData);
            if($update_res){
                $this->success('打款成功!',url('settlement_record'));
            }else{
                $this->error('打款失败!');
            }
        }

        // 页面展示
        $cp_settlement_period_info = $cpSettlementPeriod_M->where(['id'=>$id,'is_remit'=>0])->find();

        // $map_settlement_period['promote_id'] = $param['promote_id'];
        // $map_settlement_period['is_remit'] = 0;
        $settlement_period_info = $cpSettlementPeriod_M
            ->where(['is_remit'=>0])
            ->select()
            ->toArray();
        // 收款账户
        $cpInfo = Db::table('tab_game_cp')
            ->where(['id'=>$cp_settlement_period_info['cp_id']])
            ->field('id,settlement_type,bank_info,alipay_info')
            ->find();
        // var_dump($cpInfo);exit;
        $bank_info = json_decode($cpInfo['bank_info'], true);
        $alipay_info = json_decode($cpInfo['alipay_info'], true);

        $cpReceiveName = '';
        $cpReceiveAccount = '';
        if($cpInfo['settlement_type'] == 1){ // 打款方式 1银行卡 2支付宝
            $cpReceiveName = $bank_info['cp_bank_username'];
            $cpReceiveAccount = $bank_info['cp_bank_num'];
            $cpReceiveType = 1;
        }
        if($cpInfo['settlement_type'] == 2){
            $cpReceiveName = $alipay_info['alipay_name'];
            $cpReceiveAccount = $alipay_info['alipay_account'];
            $cpReceiveType = 2;
        }

        $returnData = [
            'remit_time'=>time(),
            'cp_id'=>$cp_settlement_period_info['cp_id'],
            'cp_receive_name'=>$cpReceiveName,
            'cp_receive_account'=>$cpReceiveAccount,
            'cp_receive_type'=>$cpReceiveType,
            'id'=>$id,
            'order_num'=>$cp_settlement_period_info['order_num'],
        ];
        // var_dump($returnData);exit;
        $this->assign('settlement_period_info', $settlement_period_info);
        $this->assign('data', $returnData);
        return $this->fetch();

    }

    /**
     * 编辑打款
     * created by wjd 2021-9-4 10:51:09
    */
    public function edit_remit(Request $request)
    {
        $param = $request->param();
        $id = (int) ($param['id'] ?? 0); // tab_cp_settlement_period
        if($id <= 0){
            $this->error('缺少必要的参数!');
        }
        $cpSettlementPeriod_M = new CpSettlementPeriodModel();
        // 添加数据
        if($request->isPost()){
            // 获取单号 settlement_period_order_num
            $settlement_period_order_num = $param['settlement_period_order_num'];
            if(empty($settlement_period_order_num)){
                $this->error('请选择要操作的单号!');
            }
            $cp_id = $param['cp_id'] ?? '';
            if(empty($cp_id)) { $this->error('请选择CP商!'); }
            // var_dump($param);exit;
            // 修改打款状态
            $d_time = time();
            $updateData = [
                'is_remit'=>1,
                'update_time'=>$d_time,
                'remit_time'=>strtotime($param['remit_time']),
                'remit_amount'=>$param['remit_amount'],
                'name_of_receive'=>$param['name_of_receive'],
                'type_of_receive'=>$param['type_of_receive'],
                'accounts_of_receive'=>$param['accounts_of_receive'],
                'operator'=>$param['operator'],
                'receipt'=>$param['receipt'],
                'remark'=>$param['remark'],

            ];
            $update_res = $cpSettlementPeriod_M
                ->where(['order_num'=>['in', $settlement_period_order_num], 'cp_id'=>$cp_id])
                ->update($updateData);
            if($update_res){
                $this->success('修改成功!',url('remit_record'));
            }else{
                $this->error('修改失败!');
            }
        }

        // 页面展示
        $cp_settlement_period_info = $cpSettlementPeriod_M->where(['id'=>$id,'is_remit'=>1])->find();

        $settlement_period_info = $cpSettlementPeriod_M
            ->where(['is_remit'=>1])
            ->select()
            ->toArray();

        // 收款账户
        $cpInfo = Db::table('tab_game_cp')
            ->where(['id'=>$cp_settlement_period_info['cp_id']])
            ->field('id,settlement_type,bank_info,alipay_info')
            ->find();
        // var_dump($cpInfo);exit;
        $bank_info = json_decode($cpInfo['bank_info'], true);
        $alipay_info = json_decode($cpInfo['alipay_info'], true);

        $cpReceiveName = '';
        $cpReceiveAccount = '';
        if($cpInfo['settlement_type'] == 1){ // 打款方式 1银行卡 2支付宝
            $cpReceiveName = $bank_info['cp_bank_username'];
            $cpReceiveAccount = $bank_info['cp_bank_num'];
            $cpReceiveType = 1;
        }
        if($cpInfo['settlement_type'] == 2){
            $cpReceiveName = $alipay_info['alipay_name'];
            $cpReceiveAccount = $alipay_info['alipay_account'];
            $cpReceiveType = 2;
        }

        $returnData = [
            'remit_time'=>time(),
            'cp_id'=>$cp_settlement_period_info['cp_id'],
            'cp_receive_name'=>$cpReceiveName,
            'cp_receive_account'=>$cpReceiveAccount,
            'cp_receive_type'=>$cpReceiveType,
            'id'=>$id,
            'order_num'=>$cp_settlement_period_info['order_num'],
            'remit_amount'=>$cp_settlement_period_info['remit_amount'],
            'operator'=>$cp_settlement_period_info['operator'],
            // 'receipt'=>cmf_get_image_url($cp_settlement_period_info['receipt']),
            'receipt'=>$cp_settlement_period_info['receipt'],
            'remark'=>$cp_settlement_period_info['remark'],
        ];
        // // var_dump($returnData);exit;
        $this->assign('settlement_period_info', $settlement_period_info);
        $this->assign('data', $returnData);
        return $this->fetch();

    }

    // 查看选中CP商下面的未结算订单
    public function get_cp_period_settlement_order(Request $request)
    {
        $param = $request->param();
        $map_['cp_id'] = $param['cp_id'];
        $map_['is_remit'] = 0;
        $settlement_period_info = (new CpSettlementPeriodModel())
            ->field('id,cp_id,order_num,is_remit')
            ->where($map_)
            ->select()
            ->toArray();

        // 获取渠道的收款信息 settment_type 结算方式 0银行卡 1支付宝
        $cpInfo = Db::table('tab_game_cp')
            ->where(['id'=>$param['cp_id']])
            ->field('id,settlement_type,bank_info,alipay_info')
            ->find();

        $bank_info = json_decode($cpInfo['bank_info'], true);
        $alipay_info = json_decode($cpInfo['alipay_info'], true);

        $receiveName = '';
        $receiveAccount = '';

        $settlement_type = $cpInfo['settlement_type'] ?? 0;
        if($settlement_type == 1){  // 银行卡
            $receiveName = $bank_info['cp_bank_username'];
            $receiveAccount = $bank_info['cp_bank_num'];
        }
        if($settlement_type == 2){ // 支付宝
            $receiveName = $alipay_info['alipay_name'];
            $receiveAccount = $alipay_info['alipay_account'];
        }
        $returnData = [
            'settlement_period_info'=>$settlement_period_info,
            'receive_info'=>[
                'receive_name'=>$receiveName,
                'receive_account'=>$receiveAccount,
                'receive_type'=>$settlement_type
            ]
        ];

        return json(['code'=>1, 'msg'=>'请求成功!', 'data'=>$returnData]);
    }

    /**
     * 打款记录
     * created by wjd 2021-9-2 21:10:04
    */
    public function remit_record(Request $request)
    {
        $param = $request->param();

        // 获取CP结算记录
        $recordList = (new CpSettlementPeriodLogic())->getRemitRecord($param, 'page');
        // 本页打款
        $d_page_remit_amount = 0;
        $recordList->each(function($item, $key) {

            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['remit_time'] = date('Y-m-d H:i:s', $item['remit_time']);
            $item['receipt'] = cmf_get_image_url($item['receipt']);

            return $item;
        });

        foreach($recordList as $v6) {
            $d_page_remit_amount += $v6['remit_amount'];
        }

        // 今日
        $today_time_start = strtotime(date('Y-m-d'));
        $today_time_end = $today_time_start + 86399;
        // 本月打款 $d_month_time
        $d_month_start = strtotime(date('Y-m').'-01'); // 本月开始
        $today_amount = 0;
        $d_month_amount = 0;
        $all_amount = 0;
        // 累计打款 $total_remit_amount
        $recordListAll = (new CpSettlementPeriodLogic())->getRemitRecord($param, 'all');
        foreach($recordListAll as $k5=>$v5){
            if($v5['remit_time'] > $today_time_start){
                $today_amount += $v5['remit_amount'];
            }
            if($v5['remit_time'] > $d_month_start){
                $d_month_amount += $v5['remit_amount'];
            }

            $all_amount += $v5['remit_amount'];
        }

        $total_tmp = [
            'd_page_remit_amount'=>$d_page_remit_amount,
            'today_amount'=>$today_amount,
            'd_month_amount'=>$d_month_amount,
            'all_amount'=>$all_amount,
        ];
        // return json($recordList);

        $page = $recordList->render();
        $this->assign("data_lists", $recordList);
        $this->assign("total_tmp", $total_tmp); // 汇总
        $this->assign("page", $page);

        return $this->fetch();
    }

    // 导出某个周期下面的结算单(未结算)
    public function exp_settlement_detail(){
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();

        $xlsCell = array(
            array('pay_order_number', '订单号'),
            array('pay_time', "充值时间"),
            array('promote_account', "所属渠道"),
            array('user_account', "玩家账号"),
            array('server_name', "游戏区服"),
            array('game_player_name', "角色名"),
            array('game_name', "充值游戏"),
            array('pay_amount', "充值金额"),
            array('spend_ip', "充值ip"),
            array('pay_way', "支付方式"),
        );
        // 拼接搜索条件
        //数据
        $game_id = (int) ($param['game_id'] ?? 0);
        $start_time = $param['start_time'] ?? 0;
        $end_time = $param['end_time'] ?? 0;

        if($game_id <= 0){
            return $this->error('缺少必要的参数');
        }
        if((empty($end_time) || empty($start_time))){
            return $this->error("请选择时间段!");
        }

        $lists = (new SpendLogic())->getGameSpendCollectDetail($param, 'all');
        // return json($lists);
        // 汇总显示
        // $total_pay_amount = 0;
        // $total_sum_money = 0;
        // $total_plateform_sum_money = 0;
        // foreach($lists as $k=>$v){
        //     $total_pay_amount += $v['pay_amount'];
        //     $total_sum_money += $v['sum_money'];
        // }
        // $total_plateform_sum_money = $total_pay_amount - $total_sum_money;
        $pay_way_name_arr = [
            1=>'绑币',
            2=>'平台币',
            3=>'支付宝',
            4=>'微信',
            5=>'谷歌',
            6=>'苹果支付'
        ];
        $xlsData = $lists;

        if ($xlsData) {
            $xlsData = $xlsData->toArray();
            foreach ($xlsData as $key => $vo) {
                $xlsData[$key]['pay_time'] = date('Y-m-d',$vo['pay_time']);
                $xlsData[$key]['pay_way'] = $pay_way_name_arr[$vo['pay_way']];
                // $xlsData[$key]['plateform_ratio'] = 100-$vo['ratio'];
                // $xlsData[$key]['plateform_sum_money'] = sprintf("%.2f", ($vo['pay_amount'] - $vo['sum_money']));

                // $xlsData[$key]['sum_money'] = sprintf("%.2f", $vo['sum_money']);
            }
        }
        //如果为空则返回
        if(empty($xlsData)){
            // return;
            $this->error('没有数据!');
        }

        // 拼接最后一行(汇总)
        // $tmp_append_arr = [
        //     'game_name'=>"汇总",
        //     'pay_amount'=>sprintf("%.2f", $total_pay_amount),
        //     'plateform_ratio'=>'--',
        //     'plateform_sum_money'=>sprintf("%.2f", $total_plateform_sum_money),
        //     'ratio'=>'--',
        //     'sum_money'=>sprintf("%.2f", $total_sum_money),
        // ];
        // $xlsData[$key + 1] = $tmp_append_arr;  // 最后一行 汇总

        write_action_log("导出结算单(未结算)列表");
        $xlsName_2 = $xlsName;
        $xlsName = $xlsName_2;
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
    // 导出某个周期下面的结算单(已结算)
    public function exp_settlement_period_detail(){
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();

        $xlsCell = array(
            array('pay_order_number', '订单号'),
            array('pay_time', "充值时间"),
            array('promote_account', "所属渠道"),
            array('user_account', "玩家账号"),
            array('server_name', "游戏区服"),
            array('game_player_name', "角色名"),
            array('game_name', "充值游戏"),
            array('pay_amount', "充值金额"),
            array('spend_ip', "充值ip"),
            array('pay_way', "支付方式"),
        );
        // 拼接搜索条件
        //数据
        $cp_settlement_period_id = (int) ($param['cp_settlement_period_id'] ?? 0);
        // $game_id = (int) ($param['game_id'] ?? 0);

        if($cp_settlement_period_id <= 0){
            return $this->error('缺少必要的参数');
        }
        // if((empty($end_time) || empty($start_time))){
        //     return $this->error("请选择时间段!");
        // }

        $lists = (new CpSettlementPeriodLogic())->getSettlementPeriodRecordList($param, 'all');

        // return json($lists);

        $pay_way_name_arr = [
            1=>'绑币',
            2=>'平台币',
            3=>'支付宝',
            4=>'微信',
            5=>'谷歌',
            6=>'苹果支付'
        ];
        $xlsData = $lists;

        if ($xlsData) {
            $xlsData = $xlsData->toArray();
            foreach ($xlsData as $key => $vo) {
                $xlsData[$key]['pay_time'] = date('Y-m-d',$vo['pay_time']);
                $xlsData[$key]['pay_way'] = $pay_way_name_arr[$vo['pay_way']];
            }
        }
        //如果为空则返回
        if(empty($xlsData)){
            // return;
            $this->error('没有数据!');
        }
        write_action_log("导出结算单(已结算)列表");

        $xlsName_2 = $xlsName;
        $xlsName = $xlsName_2;
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

    // 导出游戏结算列表(未结算)
    public function exp_game_settlement(Request $request)
    {
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();

        $xlsCell = array(
            array('period_str', '结算周期'),
            array('game_name', "游戏"),
            array('total_pay_amount', "实付流水"),
            array('party_ratio', "甲方分成 %"),
            array('cp_ratio', "CP分成 %"),
            array('party_money', "甲方分成金额(元)"),
            array('cp_money', "CP分成金额(元)"),
            array('cp_pay_ratio', "CP通道费率 %"),
            array('cp_pay_money', "CP通道费"),
        );

        $param = $request->param();
        $game_id = $param['game_id'] ?? 0;
        $start_time = $param['start_time'] ?? 0;
        $end_time = $param['end_time'] ?? 0;
        if(empty($start_time) && !empty($end_time)){
            return $this->error("请选择开始时间!");
        }
        if(!empty($start_time) && empty($end_time)){
            return $this->error("请选择结束时间!");
        }
        if(empty($game_id) &&  empty($start_time) && empty($end_time)){
            $this->error('没有数据!');
        }
        if(!empty($game_id) && (empty($end_time) || empty($start_time))){
            return $this->error("请选择时间段!");
        }

        $lists = (new SpendLogic())->getGameSpend($param, 'all');

        $xlsData = $lists;

        if ($xlsData) {
            // 汇总
            $total_pay_money = 0;
            $total_party_money = 0;
            $total_cp_money = 0;
            $total_cp_pay_money = 0;

            $xlsData = $xlsData->toArray();
            foreach ($xlsData as $key => $vo) {
                // $xlsData[$key]['pay_time'] = date('Y-m-d',$vo['pay_time']);
                // $xlsData[$key]['pay_way'] = $pay_way_name_arr[$vo['pay_way']];
                // $xlsData[$key]['plateform_ratio'] = 100-$vo['ratio'];
                // $xlsData[$key]['plateform_sum_money'] = sprintf("%.2f", ($vo['pay_amount'] - $vo['sum_money']));
                // $xlsData[$key]['sum_money'] = sprintf("%.2f", $vo['sum_money']);
                $xlsData[$key]['period_str'] = ''.$start_time.' 至 '.$end_time.'';
                $xlsData[$key]['party_ratio'] = 100 - $vo['cp_ratio'];
                $xlsData[$key]['party_money'] = sprintf("%.2f",($vo['total_pay_amount'] * (1-$vo['cp_pay_ratio']/100) * ((100 - $vo['cp_ratio'])/100)));
                $xlsData[$key]['cp_money'] = sprintf("%.2f",($vo['total_pay_amount'] * (1-$vo['cp_pay_ratio']/100) * ($vo['cp_ratio']/100)));
                $xlsData[$key]['cp_pay_money'] = sprintf("%.2f",($vo['total_pay_amount'] * ($vo['cp_pay_ratio']/100)));
                $xlsData[$key]['party_ratio'] = 100 - $vo['cp_ratio'];

                $total_pay_money += $vo['total_pay_amount'];
                $total_party_money += $vo['total_pay_amount'] * (1-$vo['cp_pay_ratio']/100) * ((100 - $vo['cp_ratio'])/100);
                $total_cp_money += $vo['total_pay_amount'] * (1-$vo['cp_pay_ratio']/100) * ($vo['cp_ratio']/100);
                $total_cp_pay_money += $vo['total_pay_amount'] * ($vo['cp_pay_ratio']/100);

            }
        }
        //如果为空则返回
        if(empty($xlsData)){
            // return;
            $this->error('没有数据!');
        }
        write_action_log("导出游戏结算列表(未结算)列表");

        // 拼接最后一行(汇总)
        $tmp_append_arr = [
            'period_str'=>"汇总",
            'game_name'=>' ',
            'total_pay_amount'=>$total_pay_money,
            'party_ratio'=>' ',
            'cp_ratio'=>' ',
            'party_money'=>sprintf("%.2f",$total_party_money),
            'cp_money'=>sprintf("%.2f",$total_cp_money),
            'cp_pay_ratio'=>' ',
            'cp_pay_money'=>sprintf("%.2f",$total_cp_pay_money),
        ];
        $xlsData[$key + 1] = $tmp_append_arr;  // 最后一行 汇总

        $xlsName_2 = $xlsName;
        $xlsName = $xlsName_2;
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
    // 导出结算记录
    public function exp_settlement_record(Request $request)
    {
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();

        $xlsCell = array(
            array('period_str', '结算周期'),
            array('game_name', "游戏名称"),
            array('order_num', "结算单号"),
            array('total_money', "实付流水"),
            array('party_ratio', "甲方分成 %"),
            array('cp_ratio', "CP分成 %"),
            array('party_money', "甲方分成金额(元)"),
            array('cp_money', "CP分成金额(元)"),
            // array('cp_pay_ratio', "CP通道费率 %"),
            array('cp_pay_money', "CP通道费"),
            array('create_time', "结算时间"),
        );

        $param = $request->param();
        $type = $param['type'] ?? 1; // 1手游 2 H5 3页游

        // 获取CP结算记录
        $recordList = (new CpSettlementPeriodLogic())->getSettlementRecord($param, 'all');
        // return json($recordList);

        $xlsData = $recordList;

        if ($xlsData) {
            // 汇总
            $total_pay_money = 0;
            $total_party_money = 0;
            $total_cp_money = 0;
            $total_cp_pay_money = 0;

            $xlsData = $xlsData->toArray();
            foreach ($xlsData as $key => $vo) {
                $xlsData[$key]['period_str'] = ''.date('Y-m-d', $vo['start_time']).' 至 '.date('Y-m-d', $vo['end_time']).'';
                $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
                $total_pay_money += $vo['total_money'];
                $total_party_money += $vo['party_money'];
                $total_cp_money += $vo['cp_money'];
                $total_cp_pay_money += $vo['cp_pay_money'];
            }
        }
        //如果为空则返回
        if(empty($xlsData)){
            // return;
            $this->error('没有数据!');
        }

        // 拼接最后一行(汇总)
        $tmp_append_arr = [
            'period_str'=>"汇总",
            'game_name'=>' ',
            'order_num'=>' ',
            'total_money'=>$total_pay_money,
            'party_ratio'=>' ',
            'cp_ratio'=>' ',
            'party_money'=>$total_party_money,
            'cp_money'=>$total_cp_money,
            // 'cp_pay_ratio'=>' ',
            'cp_pay_money'=>$total_cp_pay_money,
            'create_time'=>' '
        ];
        $xlsData[$key + 1] = $tmp_append_arr;  // 最后一行 汇总

        $xlsName_2 = $xlsName;
        $xlsName = $xlsName_2;

        write_action_log("导出结算记录列表");

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

    // 导出打款记录
    public function exp_remit_record(Request $request)
    {
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();

        $xlsCell = array(
            array('remit_time', '打款时间'),
            array('order_num', "结算单号"),
            array('remit_amount', "打款金额"),
            array('cp_name', "CP名称"),
            array('operator', "创建人"),
            array('create_time', "创建时间"),
            array('cp_bank_info', "打款信息"),
            array('remark', "备注"),
        );

        $param = $request->param();
        // 获取CP结算记录
        $recordList = (new CpSettlementPeriodLogic())->getRemitRecord($param, 'all');
        $all_amount = 0;
        $xlsData = $recordList;
        $pay_type = [
            1=>'银行卡',
            2=>'支付宝',
            0=>'--',
            -1=>'--',
        ];
        if ($xlsData) {
            // 汇总
            $total_pay_money = 0;
            $total_party_money = 0;
            $total_cp_money = 0;
            $total_cp_pay_money = 0;

            $xlsData = $xlsData->toArray();
            foreach ($xlsData as $key => $vo) {
                $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
                $xlsData[$key]['remit_time'] = date('Y-m-d H:i:s', $vo['remit_time']);
                if(empty($vo['operator'])){
                    $vo['operator'] = '--';
                }
                $vo['name_of_receive'] = empty($vo['name_of_receive']) ? '--' : $vo['name_of_receive'];
                $vo['accounts_of_receive'] = empty($vo['accounts_of_receive']) ? '--' : $vo['accounts_of_receive'];

                $xlsData[$key]['cp_bank_info'] = '打款时间:'.$xlsData[$key]['remit_time'].' 打款人: '.$vo['operator'].' 打款金额:'.$vo['remit_amount'].' 打款方式: '.$pay_type[$vo['type_of_receive']].' 打款账户: '.$vo['name_of_receive'].$vo['accounts_of_receive'].'';
                $all_amount += $vo['remit_amount'];
            }
        }
        //如果为空则返回
        if(empty($xlsData)){
            // return;
            $this->error('没有数据!');
        }
        // 拼接最后一行(汇总)
        $tmp_append_arr = [
            'remit_time' => '汇总',
            'order_num' => " ",
            'remit_amount' => $all_amount,
            'cp_name' => " ",
            'operator' => " ",
            'create_time' => " ",
            'cp_bank_info'=> " ",
            'remark' => " ",
        ];
        $xlsData[$key + 1] = $tmp_append_arr;  // 最后一行 汇总

        $xlsName_2 = $xlsName;
        $xlsName = $xlsName_2;
        write_action_log("导出打款记录列表");
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


    /**
     * @函数或方法说明
     * @未结算订单
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/6/27 10:13
     */
    public function promote_settlement()
    {
        $data = $this->request->param();
        $map['status'] = 0;
        $map['is_check'] = 1;
        $map['pay_way'] = ['neq',1];
        if ($data['user_account']) {
            $map['user_account'] = ['like', '%' . $data['user_account'] . '%'];
        }
        if ($data['pay_order_number']) {
            $map['pay_order_number'] = ['like', '%' . $data['pay_order_number'] . '%'];
        }
        if ($data['game_id']) {
            $map['game_id'] = $data['game_id'];
        }
        if ($data['promote_id']) {
            $map['top_promote_id'] = $data['promote_id'];
        }
        if($data['is_bind'] ==1){
            unset($map['pay_way']);
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
        $model = new PromotesettlementModel();
        $base = new BaseController();
        $exend['order'] = 'id desc';
        $exend['field'] = 'id,promote_account,top_promote_id,game_name,pattern,ratio,money,sum_money,user_account,is_check,parent_name,pay_order_number,pay_amount,cost,create_time,pay_way';
        $list_data = $base->data_list($model, $map, $exend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($list_data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $list_data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }

        // 获取分页显示
        $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney')->where($map)->find();
        $this->assign('total', $total);
        $page = $list_data->render();
        $this->assign("data_lists", $list_data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @已结算订单
     * @return mixed
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019/6/27 14:33
     * @author: 郭家屯
     */
    public function promote_settlement_already()
    {
        $data = $this->request->param();
        $map['status'] = 1;
        $map['is_check'] = 1;
        if ($data['user_account']) {
            $map['user_account'] = ['like', '%' . $data['user_account'] . '%'];
        }
        if ($data['pay_order_number']) {
            $map['pay_order_number'] = ['like', '%' . $data['pay_order_number'] . '%'];
        }
        if ($data['game_id']) {
            $map['game_id'] = $data['game_id'];
        }
        if ($data['promote_id']) {
            $map['top_promote_id'] = $data['promote_id'];
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
        $model = new PromotesettlementModel();
        $base = new BaseController();
        $exend['order'] = 'id desc';
        $exend['field'] = 'id,promote_account,top_promote_id,game_name,pattern,ratio,money,sum_money,user_account,is_check,parent_name,pay_order_number,pay_amount,cost,create_time,update_time,pay_way';
        $list_data = $base->data_list($model, $map, $exend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($list_data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $list_data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }

        // return json($list_data);
        // 获取分页显示
        $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney')->where($map)->find();
        $this->assign('total', $total);
        $page = $list_data->render();
        $this->assign("data_lists", $list_data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @不参与结算订单
     * @return mixed
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @since: 2019/6/27 14:33
     * @author: 郭家屯
     */
    public function promote_settlement_never()
    {
        $data = $this->request->param();
        $map['status'] = 0;
        $map['is_check'] = 0;
        if ($data['user_account']) {
            $map['user_account'] = ['like', '%' . $data['user_account'] . '%'];
        }
        if ($data['pay_order_number']) {
            $map['pay_order_number'] = ['like', '%' . $data['pay_order_number'] . '%'];
        }
        if ($data['game_id']) {
            $map['game_id'] = $data['game_id'];
        }
        if ($data['promote_id']) {
            $map['top_promote_id'] = $data['promote_id'];
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
        $model = new PromotesettlementModel();
        $base = new BaseController();
        $exend['order'] = 'id desc';
        $exend['field'] = 'id,promote_account,top_promote_id,game_name,pattern,ratio,money,sum_money,user_account,is_check,parent_name,pay_order_number,pay_amount,cost,create_time,update_time,pay_way';
        $list_data = $base->data_list($model, $map, $exend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($list_data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $list_data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }

        // 获取分页显示
        $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney')->where($map)->find();
        $this->assign('total', $total);
        $page = $list_data->render();
        $this->assign("data_lists", $list_data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @批量结算
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException、
     * @since: 2019/6/27 14:34
     * @author: 郭家屯
     */
    public function generatesettlementAll()
    {
        $data = $this->request->param();
        $map['status'] = 0;
        $map['is_check'] = 1;
        $map['pay_way'] = ['neq',1];
        $id = $this->request->param('id');
        if (empty($id)) $this->error('数据传入失败');
        $ids = explode(',', $id);
        $map['id'] = ['in', $ids];

        // if ($data['user_account']) {
        //     $map['user_account'] = ['like', '%' . $data['user_account'] . '%'];
        // }
        // if ($data['pay_order_number']) {
        //     $map['pay_order_number'] = ['like', '%' . $data['pay_order_number'] . '%'];
        // }
        // if ($data['game_id']) {
        //     $map['game_id'] = $data['game_id'];
        // }
        // if ($data['promote_id']) {
        //     $map['top_promote_id'] = $data['promote_id'];
        // }
        if($data['is_bind'] == 1){
            unset($map['pay_way']);
        }
        // $start_time = $this->request->param('start_time', '');
        // $end_time = $this->request->param('end_time', '');
        // if ($start_time && $end_time) {
        //     $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        // } elseif ($end_time) {
        //     $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        // } elseif ($start_time) {
        //     $map['create_time'] = ['egt', strtotime($start_time)];
        // }
        $model = new PromotesettlementModel();
        $listdata = $model->field('id,parent_id,top_promote_id,parent_name,sum(promote_settlement) as money')->where($map)->group('top_promote_id')->select()->toArray();

        $allSettlementLists = $model->where($map)->field('id,sum_money')->select()->toArray();
        if (empty($listdata)) {
            $this->error('请选择结算数据');
        }
        foreach($allSettlementLists as $key9=>$val9){
            $needSettlementIds[] = $val9['id'];

        }
        $flag = 1; // $flag:0 不提交数据 $flag:1 提交数据
        $promotemodel = new PromoteModel();
        Db::startTrans();
        try {
            foreach ($listdata as $key => $v) {
                $promotemodel->where('id', $v['top_promote_id'])->setInc('balance_profit', $v['money']);
                $topPromoteIds[] = $v['top_promote_id'];

            }
            // var_dump($needSettlementIds);exit;
            // 生成结算记录
            if(!empty($topPromoteIds)){
                foreach($topPromoteIds as $k2=>$v2){
                    $do_period_result = $this->do_period_record($v2,$needSettlementIds);
                    if($do_period_result['code'] == -1){
                        $flag = 0;
                    }
                }
            }
            $model->where($map)->update(['status' => 1, 'update_time' => time()]);
            // 提交事务
            // Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $flag = 0;
            Db::rollback();
        }
        if($flag == 1){
            Db::commit();
            $this->success('结算成功');
        }else{
            $this->error('系统繁忙!');
        }

    }

    //推广提现 yyh
    public function promote_withdraw()
    {
        $base = new BaseController();
        $model = new PromotewithdrawModel();
        $map['type'] = 1;
        $map['promote_level'] = 1;
        $promote_id = $this->request->param('promote_id', 0, 'intval');
        if ($promote_id > 0) {
            $map['promote_id'] = $promote_id;
        }
        $status = $this->request->param('status');
        if ($status != '') {
            $map['status'] = $status;
        }
        $widthdraw_number = $this->request->param('widthdraw_number', '');
        if ($widthdraw_number != '') {
            $map['widthdraw_number'] = ['like', '%' . addcslashes($widthdraw_number, '%') . '%'];
        }
        $exend['order'] = 'create_time desc';
        $exend['field'] = '*';
        $data = $base->data_list($model, $map, $exend);
        $exend['field'] = 'sum(sum_money) as total';
        //累计充值
        $total = $base->data_list_select($model, $map, $exend);
        $map = [];
        $map['type'] = 1;
        $map['promote_level'] = 1;
        //今日提现
        $map['create_time'] = ['between', total(1, 2)];
        $today = $base->data_list_select($model, $map, $exend);
        //昨日提现
        $map['create_time'] = ['between', total(5, 2)];
        $yestoday = $base->data_list_select($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("total", $total[0]['total']);
        $this->assign("today", $today[0]['total']);
        $this->assign("yestoday", $yestoday[0]['total']);
        $this->assign("page", $page);
        return $this->fetch();
    }


    //修改提现状态
    public function changeStatus()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        if (empty($ids)) $this->error('请选择要操作的数据');
        if (!is_array($ids)) {
            $id = $ids;
            $ids = [];
            $ids[] = $id;
        }
        $save['audit_time'] = time();
        $save['status'] = $data['status'];
        $model = new PromotewithdrawModel();
        Db::startTrans();
        foreach ($ids as $key => $value) {
            $map['id'] = $value;
            $_promote = $model->field('sum_money,promote_id,status')->where($map)->find();
            if($_promote['status'] !=0){
                Db::rollback();
                $this->error('订单非未审核状态,不可修改');
            }
//            $map['status'] = ['neq', 3];//更改,只有未审核的才可以操作(因涉及驳回退款)
            $result = $model->where($map)->update($save);
            //增加处理驳回的数据,返还申请提现的金额-20210628-byh
            $res2 = true;
            if($data['status']==2){
                $res2 = Db::table('tab_promote')->where('id',$_promote['promote_id'])->setInc('balance_profit',$_promote['sum_money']);
            }

            if ($result === false || $res2 === false) {
                Db::rollback();
                $this->error('操作失败');
            }
        }
        Db::commit();
        $this->success('操作成功');
    }

    /**
     * 获取渠道的打款信息-银行卡/支付宝
     * by:byh-2021-7-17 11:16:12
     */
    public function get_promote_payment_info()
    {
        $promote_id = $this->request->param('promote_id',0,'intval');
        if(empty($promote_id)) return json(['code'=>0,'msg'=>'数据错误!']);
        //查询渠道的打款信息
        $data = Db::table('tab_promote')->field('bank_card,bank_name,bank_account,alipay_account,alipay_name')->where('id',$promote_id)->find();
        if(empty($data)) return json(['code'=>0,'msg'=>'未查询到渠道的收款信息']);
        return json(['code'=>1,'data'=>$data]);
    }

    /**
     * 保存打款数据的备注信息-收益提现-结算打款
     */
    public function save_remark()
    {

        $data = $this->request->param();

        if(empty($data['id'])) $this->error('请选择要操作的数据');
        if($data['remark'] == '点击输入'){
            $data['remark'] = '';
        }
        switch ($data['type']){
            case 'pww':
                $model = new PromotewithdrawModel();
                $model->where('id',$data['id'])->update(['remark'=>$data['remark']]);
                break;
            case 'ppst':
                Db::table('tab_promote_settlement_period')->where('id',$data['id'])->update(['remark'=>$data['remark']]);
                break;
            default:
                $this->error('操作失败');
        }
        $this->success('操作成功');
    }

    //打款
    public function paid()
    {
        $data = $this->request->param();
        $id = $data['ids'];
        if (empty($id)) $this->error('请选择要操作的数据');
        $model = new PromotewithdrawModel();
//        if(!is_array($id)){
//            $status = $model->field('status')->find($id)->toarray();
//            if ($status['status'] != 1) {
//                $this->error('请先通过申请');
//            }
//            $id = [$id];
//        }

        $okdata = $model->where(['status'=>1,'id'=>$id])->find();
        if(empty($okdata)){
            $this->error('请选择已通过的数据');
        }
        //增加对弹出层数据的判断-20210717-byh
        if(isset($data['payment_money']) && empty($data['payment_money'])) $this->error('请输入打款金额');
        if(isset($data['payment_type']) && !in_array($data['payment_type'],[1,3])) $this->error('打款类型错误');
        if(isset($data['promote_id']) && empty($data['promote_id'])) $this->error('参数错误');
        $withdraw_way = isset($data['payment_type'])?$data['payment_type']:1;
        $payment_money = $payment_account = $payment_name = $payment_bank = '';
        if(isset($data['payment_type'])){
            $payment_money = $data['payment_money'];
            //根据渠道查询打款账户信息
            $promote_info = Db::table('tab_promote')->field('bank_card,bank_name,bank_account,alipay_account,alipay_name')->where('id',$data['promote_id'])->find();
//            dump($data);die;
            if($data['payment_type'] == 1){//1支付宝
                if(empty($promote_info['alipay_account']) || empty($promote_info['alipay_name'])){
                    $this->error('渠道未完整配置支付宝信息');
                }
                $payment_account = $promote_info['alipay_account'];
                $payment_name    = $promote_info['alipay_name'];
                $withdraw_type = !empty($data['withdraw_type'])?$data['withdraw_type']:'1';

                $pay_type = cmf_get_option('ptb_cash_out_set')['pay_type'];
                if($withdraw_type=='2' && $pay_type=='1'){
                    //支付宝自动打款
                    $okdata -> payment_account = $payment_account;
                    $okdata -> payment_name = $payment_name;
                    $okdata -> payment_bank = $payment_bank;
                    $okdata -> payment_money = $payment_money;
                    if (false===$okdata -> isUpdate(true) -> save()) {
                        $this -> error('订单信息更新失败,请重试');
                    }
                    //支付宝自动打款
                    $autoPaidRes = $this->_alipayAutoPaid($okdata->widthdraw_number,$okdata);
                    if(true!==$autoPaidRes){
                        $this->error($autoPaidRes);
                    }
                }

            }else{//3银行卡
                if(empty($promote_info['bank_card']) || empty($promote_info['bank_name']) || empty($promote_info['bank_account'])){
                    $this->error('渠道未完整配置银行卡信息');
                }
                $payment_account = $promote_info['bank_card'];
                $payment_name    = $promote_info['bank_account'];
                $payment_bank = $promote_info['bank_name'];
                $withdraw_type = 1;
            }
        }

        Db ::startTrans();
        $okdata -> withdraw_type = $withdraw_type;
        $okdata -> withdraw_way = $withdraw_way;
        $okdata -> status = 3;
        $okdata -> paid_time = time();
        $okdata -> payment_account = $payment_account;
        $okdata -> payment_name = $payment_name;
        $okdata -> payment_bank = $payment_bank;
        $okdata -> payment_money = $payment_money;
        $result = $okdata -> isUpdate(true) -> save();
        if ($result === false) {
            Db ::rollback();
            $this -> error('操作失败');
        }
        Db ::commit();
        $this -> success('打款成功');
    }

    /**
     * @函数或方法说明
     * @设置不参与结算
     * @author: 郭家屯
     * @since: 2019/6/27 11:38
     */
    public function changeCheckStatus()
    {
        $id = $this->request->param('id');
        if (empty($id)) $this->error('数据传入失败');
        $ids = explode(',', $id);
        $error_num = 0;
        $model = new PromotesettlementModel();
        foreach ($ids as $key => $value) {
            $is_check = $model->field('is_check')->where('id',$value)->find();
            $is_check_val = $is_check['is_check'] == 1 ? 0 : 1;
            $result = $model->where(["id" => $value])->setField('is_check', $is_check_val);
            if ($result !== false) {
            } else {
                // 计算错误行数
                $error_num++;
            }
        }
        if ($error_num == 0) {
            $this->success('操作成功');
        } else {
            $this->success('有'.$error_num.'条数据操作失败');
        }
    }

    /**
     * @函数或方法说明
     * @
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/6/27 15:53
     */
    public function promote_exchange()
    {
        $base = new BaseController();
        $model = new PromotewithdrawModel();
        $map['type'] = 2;
        $map['promote_level'] = 1;
        $promote_id = $this->request->param('promote_id', 0, 'intval');
        if ($promote_id > 0) {
            $map['promote_id'] = $promote_id;
        }
        $status = $this->request->param('status');
        if ($status != '') {
            $map['status'] = $status;
        }
        $widthdraw_number = $this->request->param('widthdraw_number', '');
        if ($widthdraw_number != '') {
            $map['widthdraw_number'] = ['like', '%' . addcslashes($widthdraw_number, '%') . '%'];
        }
        $exend['order'] = 'create_time desc';
        $exend['field'] = '*';
        $data = $base->data_list($model, $map, $exend);
        $exend['field'] = 'sum(sum_money) as total';
        //累计充值
        $total = $base->data_list_select($model, $map, $exend);
        $map = [];
        $map['type'] = 2;
        $map['promote_level'] = 1;
        //今日提现
        $map['create_time'] = ['between', total(1, 2)];
        $today = $base->data_list_select($model, $map, $exend);
        //昨日提现
        $map['create_time'] = ['between', total(5, 2)];
        $yestoday = $base->data_list_select($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("total", $total[0]['total']);
        $this->assign("today", $today[0]['total']);
        $this->assign("yestoday", $yestoday[0]['total']);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @发放平台币
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: 郭家屯
     * @since: 2019/6/27 16:08
     */
    public function grant()
    {
        $data = $this->request->param();
        $id = $data['id'];
        if (empty($id)) $this->error('请选择要操作的数据');
        $model = new PromotewithdrawModel();
        $promotemodel = new PromoteModel();
        $data = $model->find($id)->toarray();
        if ($data['status'] != 1) {
            $this->error('请先通过申请');
        } else {
            $map['id'] = $id;
            $save['withdraw_type'] = 1;
            $save['status'] = 3;
            $save['paid_time'] = time();
            Db::startTrans();
            try {
                $model->where($map)->update($save);
                $promotemodel->where('id', $data['promote_id'])->setInc('balance_coin', $data['sum_money']);
                $info = [
                    'promote_id' => $data['promote_id'],
                    'promote_type' => 1,
                    'num' => $data['sum_money'],
                    'op_id' => cmf_get_current_admin_id(),
                    'create_time' => time(),
                    'source_id' => 1
                ];
                Db::table('tab_spend_promote_coin')->insert($info);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error('操作失败');
            }
            $this->success('发放成功');
        }
    }

    /**
     * @函数或方法说明
     * @渠道结算汇总
     * @author: 郭家屯
     * @since: 2019/6/27 16:53
     */
    public function promote_summary()
    {
        $data = $this->request->param();
        if ($data['promote_id']) {
            $withmap['promote_id'] = $map['parent_id'] = $data['promote_id'];
        }
        $map['status'] = 1;
        $model = new PromotesettlementModel();
        $base = new BaseController();
        $exend['order'] = 'parent_id desc';
        $exend['group'] = 'top_promote_id';
        $exend['field'] = 'parent_id,top_promote_id,parent_name,sum(pay_amount) as totalamount,sum(sum_reg) as totalreg,sum(sum_money) as totalmoney';
        $list_data = $base->data_list($model, $map, $exend);
        $page = $list_data->render();
        $this->assign("data_lists", $list_data);
        $this->assign("page", $page);
        //收益部分
        $promote_ids = array_column($list_data->toarray()['data'], 'top_promote_id');
        $promotewithdrawmodel = new PromotewithdrawModel();
        $map1['promote_id'] = ['in', $promote_ids];
        $data = $promotewithdrawmodel->field('sum(sum_money) as totalmoney,sum(fee) as totalfee,promote_id,type')->where(['status'=>['in','3']])->where($map1)->group('promote_id,type')->select()->toArray();
        $sum = array();
        foreach ($data as $key => $v) {
            $sum[$v['promote_id'] . '_' . $v['type']]['money'] = $v['totalmoney'];
            $sum[$v['promote_id'] . '_' . $v['type']]['fee'] = $v['totalfee'];
        }
        $this->assign('sum', $sum);
        //汇总部分
        $all = $model->field('sum(pay_amount) as totalamount,sum(sum_reg) as totalreg,sum(sum_money) as totalmoney')->where($map)->find();
        $all_withdraw = $promotewithdrawmodel->field('sum(sum_money) as totalmoney,sum(fee) as totalfee,type')->where(['status'=>['in','3']])->where('promote_level',1)->where($withmap)->group('type')->order('type asc')->select()->toArray();
        $all['withdraw'] = 0;
        $all['exchange'] = 0;
        if(count($all_withdraw)==1){
            if($all_withdraw[0]['type']==1){
                $all['withdraw'] = $all_withdraw[0]['totalmoney'];
            }else{
                $all['exchange'] = $all_withdraw[0]['totalmoney'];
            }
        }else{
            $all['withdraw'] = $all_withdraw[0]['totalmoney'];
            $all['exchange'] = $all_withdraw[1]['totalmoney'];
        }
        $all['totalfee'] = $all_withdraw[0]['totalfee'] + $all_withdraw[1]['totalfee'];
        $this->assign('all', $all);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @设置分成比例
     * @author: 郭家屯
     * @since: 2020/10/10 14:32
     */
    public function setratio()
    {
        $settlement_id = $this->request->param('settlement_id');
        $value = $this->request->param('value');
        $model = new PromotesettlementModel();
        $settlement = $model->field('id,pattern,pay_amount')->where('id',$settlement_id)->find();
        if(!$settlement){
            $this->error('结算单不存在');
        }
        $value = abs($value);
        if($settlement['pattern'] == 0){
            $save['ratio'] = $value;
            $save['sum_money'] = round($value*$settlement['pay_amount']/100,2);
        }else{
            $save['money'] = $value;
            $save['sum_money'] = $value;
        }
        $result = $model->where('id',$settlement_id)->update($save);
        if($result){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }
    /*
        查看 渠道周期结算单(按周期自动结算)
        created by wjd
        2021-3-25 19:22:18
    */
    public function promote_period_settlement(Request $request){
        // var_dump('渠道周期结算单(按周期自动结算)');exit;
        $param = $request->param();
        // var_dump($param);exit;
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        // 条件
        $map = [];
        if ($param['promote_id']) {
            $map['promote_id'] = $param['promote_id'];
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86399];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }

        $start_time2 = $this->request->param('start_time2', '');
        $end_time2 = $this->request->param('end_time2', '');
        if ($start_time2 && $end_time2) {
            $map['update_time'] = ['between', [strtotime($start_time2), strtotime($end_time2) + 86399]];
        } elseif ($end_time2) {
            $map['update_time'] = ['lt', strtotime($end_time2) + 86399];
        } elseif ($start_time2) {
            $map['update_time'] = ['egt', strtotime($start_time2)];
        }

        $start_time3 = $this->request->param('start_time3', '');
        $end_time3 = $this->request->param('end_time3', '');
        $map2 = [];
        if ($start_time3 && $end_time3) {
            $map['period_start'] = ['>=', strtotime($start_time3)];
            $map['period_end'] = ['<', strtotime($end_time3) + 86399];
        } elseif ($end_time3) {
            $map['period_end'] = ['lt', strtotime($end_time3) + 86399];
        } elseif ($start_time3) {
            $map['period_start'] = ['egt', strtotime($start_time3)];
        }
        if($param['remit_status'] === '0' || $param['remit_status'] === '1'){
            $map['is_remit'] = $param['remit_status'];
        }
        $lists = Db::table('tab_promote_settlement_period')
            ->where($map)
            // ->whereOr($map2)
            ->order('id desc')
            ->paginate($row, false, ['query' => $param]);
        $lists->each(function($item,$key){
            $item['receipt'] = cmf_get_image_url($item['receipt']);
            return $item;
        });

        $total_promoter_earn = 0; // 渠道分成总金额
        $total_plateform_earn = 0; // 甲方(平台)分成总金额
        $total_earn = 0; // 游戏实付金额
        $total_remit = 0; // 总打款金额
        foreach($lists as $k=>$v){
            $total_promoter_earn += $v['promoter_earn'];
            $total_plateform_earn += $v['plateform_earn'];
            $total_earn += $v['total_money'];
            $total_remit += $v['remit_amount'];

        }
        $total = [
            'total_promoter_earn'=>sprintf("%.2f",$total_promoter_earn),
            'total_plateform_earn'=>sprintf("%.2f",$total_plateform_earn),
            'total_earn'=>sprintf("%.2f",$$total_earn),
            'total_remit'=>sprintf("%.2f",$total_remit)
        ];
        $this->assign('total', $total);
        $page = $lists->render();
        $this->assign("data_lists", $lists);
        $this->assign("page", $page);
        return $this->fetch();
    }
    // 操作打款(先渲染页面, post提交之后进行保存操作)
    public function do_remit22222222(Request $request){
        $param = $request->param();
        $id = (int) ($param['id'] ?? 0);
        if($id <= 0){
            $this->error('缺少必要的参数!');
        }

        // var_dump($param);exit;
        // 添加数据
        if($this->request->isPost()){
            // 获取单号 settlement_period_order_num
            $settlement_period_order_num = $param['settlement_period_order_num'];
            if(empty($settlement_period_order_num)){
                $this->error('请选择要操作的单号!');
            }
            // 修改打款状态
            $d_time = time();
            $updateData = [
                'is_remit'=>1,
                'update_time'=>$d_time,
                'remit_time'=>strtotime($param['remit_time']),
                'remit_amount'=>$param['remit_amount'],
                'name_of_receive'=>$param['name_of_receive'],
                'type_of_receive'=>$param['type_of_receive'],
                'accounts_of_receive'=>$param['accounts_of_receive'],
                'operator'=>$param['operator'],
                'receipt'=>$param['receipt'],
                'remark'=>$param['remark'],

            ];
            $update_res = Db::table('tab_promote_settlement_period')
                ->where(['order_num'=>['in', $settlement_period_order_num]])
                ->update($updateData);
            if($update_res){
                $this->success('打款成功!',url('promote_period_settlement'));
            }else{
                $this->error('打款失败!');
            }
        }
        // 展示
        $map_settlement_period['promote_id'] = $param['promote_id'];
        $map_settlement_period['is_remit'] = 0;
        $settlement_period_info = Db::table('tab_promote_settlement_period')
            ->where($map_settlement_period)
            ->select()
            ->toArray();
        // 收款账户
        $promoteInfo = Db::table('tab_promote')
            ->where(['id'=>$param['promote_id']])
            ->field('id,alipay_account,alipay_name,bank_name,bank_card,settment_type,bank_account')
            ->find();
        // var_dump($promoteInfo);exit;
        $promoteReceiveName = '';
        $promoteReceiveAccount = '';
        if($promoteInfo['settment_type'] == 0){ // 打款方式 0银行卡 1支付宝
            $promoteReceiveName = $promoteInfo['bank_account'];
            $promoteReceiveAccount = $promoteInfo['bank_card'];
            $promoteReceiveType = 0;
        }else{
            $promoteReceiveName = $promoteInfo['alipay_name'];
            $promoteReceiveAccount = $promoteInfo['alipay_account'];
            $promoteReceiveType = 1;
        }

        $returnData = [
            'remit_time'=>time(),
            'promote_id'=>$param['promote_id'],
            'promote_receive_name'=>$promoteReceiveName,
            'promote_receive_account'=>$promoteReceiveAccount,
            'promote_receive_type'=>$promoteReceiveType,
            'id'=>$id,
            'order_num'=>$param['order_num'],
        ];
        // var_dump($returnData);exit;
        $this->assign('settlement_period_info', $settlement_period_info);
        $this->assign('data', $returnData);
        return $this->fetch();
    }
    // 查看选中渠道下面的未结算订单
    public function get_promote_period_settlement_order(Request $request)
    {
        $param = $request->param();
        $map_settlement_period['promote_id'] = $param['promote_id'];
        $map_settlement_period['is_remit'] = 0;
        $settlement_period_info = Db::table('tab_promote_settlement_period')
            ->field('id,promote_id,order_num,is_remit')
            ->where($map_settlement_period)
            ->select()
            ->toArray();

        // 获取渠道的收款信息 settment_type 结算方式 0银行卡 1支付宝
        $promoteInfo = Db::table('tab_promote')
            ->where(['id'=>$param['promote_id']])
            ->field('id,bank_name,bank_card,bank_phone,bank_area,account_openin,bank_account,alipay_account,alipay_name,settment_type')
            ->find();
        $settment_type = $promoteInfo['settment_type'] ?? 0;
        if($settment_type == 0){  // 银行卡
            $receiveName = $promoteInfo['bank_account'];
            $receiveAccount = $promoteInfo['bank_card'];
        }
        if($settment_type == 1){ // 支付宝
            $receiveName = $promoteInfo['alipay_name'];
            $receiveAccount = $promoteInfo['alipay_account'];
        }
        $returnData = [
            'settlement_period_info'=>$settlement_period_info,
            'receive_info'=>[
                'receive_name'=>$receiveName,
                'receive_account'=>$receiveAccount,
                'receive_type'=>$settment_type
            ]
        ];

        return json(['code'=>1, 'msg'=>'请求成功!', 'data'=>$returnData]);
    }


    // 导出渠道周期结算表 单元格内换行  PHP_EOL
    public function exp_promote_period_settlement(){
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();
        $xlsCell = array(
            array('period', '结算信息'),
            // array('order_num', "结算单号"),
            array('promote_account', "渠道"),
            array('total_money', "游戏实付金额"),
            array('minus_promoter_earn', "甲方分成金额(元)"),
            array('promoter_earn', "渠道分成金额(元)"),

            // array('promote_nickname', "公会名称"),
            // array('promote_real_name', "收款人姓名"),
            // array('promote_bank_card', "收款银行账户"),
            // array('promote_alipay_account', "收款支付宝"),

            // array('plateform_earn', "业绩金额"),
            // array('promoter_earn', "分成总金额"),
            // array('create_time', "结算时间"),
            array('is_remit_string', "打款状态"),
            array('remit_info', "打款信息"),
            array('remark', "备注"),
            // array('sum_money', "是否已打款"),
        );

        // 拼接搜索条件
        $map = [];
        // if ($param['promote_account']) {
        //     $map['promote_account'] = ['like', '%' . $param['promote_account'] . '%'];
        // }
        if ($param['promote_id']) {
            $map['promote_id'] = $param['promote_id'];
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
        // 打款时间
        $start_time2 = $this->request->param('start_time2', '');
        $end_time2 = $this->request->param('end_time2', '');
        if ($start_time2 && $end_time2) {
            $map['update_time'] = ['between', [strtotime($start_time2), strtotime($end_time2) + 86399]];
        } elseif ($end_time2) {
            $map['update_time'] = ['lt', strtotime($end_time2) + 86400];
        } elseif ($start_time2) {
            $map['update_time'] = ['egt', strtotime($start_time2)];
        }

        $start_time3 = $this->request->param('start_time3', '');
        $end_time3 = $this->request->param('end_time3', '');
        $map2 = [];
        if ($start_time3 && $end_time3) {
            $map['period_start'] = ['>=', strtotime($start_time3)];
            $map['period_end'] = ['<', strtotime($end_time3) + 86399];
        } elseif ($end_time3) {
            $map['period_end'] = ['<', strtotime($end_time3) + 86399];
        } elseif ($start_time3) {
            $map['period_start'] = ['>=', strtotime($start_time3)];
        }
        if($param['remit_status'] === '0' || $param['remit_status'] === '1'){
            $map['is_remit'] = $param['remit_status'];
        }
        // 单个渠道导出
        $promote_id = $param['promote_id'] ?? 0;
        if($promote_id > 0){
            $map['promote_id'] = $promote_id;
        }
        //数据
        $lists = Db::table('tab_promote_settlement_period')
            ->alias('psp')
            ->join(['tab_promote' => 'p'], 'p.id=psp.promote_id', 'left')
            ->where($map)
            // ->whereOr($map2)
            ->order('psp.id desc')
            ->field('psp.period,psp.order_num,psp.promote_account,p.nickname as promote_nickname,
            p.real_name as promote_real_name,p.bank_card as promote_bank_card,p.alipay_account as promote_alipay_account,
            psp.plateform_earn,psp.promoter_earn,psp.create_time,psp.update_time,psp.is_remit,psp.remit_time,
            psp.remit_amount,psp.accounts_of_receive,psp.name_of_receive,psp.type_of_receive,psp.operator,psp.remark,psp.total_money')
            ->select();
        $xlsData = $lists;

        if ($xlsData) {
            $xlsData = $xlsData->toArray();

            $total_promoter_earn = 0; // 渠道分成总金额
            $total_plateform_earn = 0; // 甲方(平台)分成总金额
            $total_earn = 0; // 游戏实付金额
            $total_remit = 0; // 总打款金额

            foreach ($xlsData as $key => $vo) {
                // $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
                if($vo['is_remit'] == 0){
                    $xlsData[$key]['is_remit_string'] = '未打款';
                }else{
                    $xlsData[$key]['is_remit_string'] = '已打款';
                }
                if($vo['type_of_receive'] == 1){
                    $remit_type = '支付宝';
                }else{
                    $remit_type = '银行卡';
                }
                $xlsData[$key]['period'] = '结算单号: '.$vo['order_num'].' '.'结算周期: '.$vo['period'].' '.'结算时间: '.date('Y-m-d H:i:s', $vo['create_time']);

                if($vo['is_remit'] != 0){

                    if(empty($vo['name_of_receive']) && empty($vo['accounts_of_receive'])){
                        $xlsData[$key]['remit_info'] = '打款时间: '.date('Y-m-d H:i:s', $vo['remit_time']).' '.'打款人: '.$vo['operator'].' '.'打款金额: '.$vo['remit_amount'].'打款方式: '.$remit_type.' '.'打款账户: --';
                    }else{
                        $xlsData[$key]['remit_info'] = '打款时间: '.date('Y-m-d H:i:s', $vo['remit_time']).' '.'打款人: '.$vo['operator'].' '.'打款金额: '.$vo['remit_amount'].'打款方式: '.$remit_type.' '.'打款账户: '.$vo['name_of_receive'].' '.$vo['accounts_of_receive'];
                    }

                }else{
                    $xlsData[$key]['remit_info'] = '--';
//                    $xlsData[$key]['period'] = '--';
                }
                // $vo['promote_bank_card'] = ' '.$vo['promote_bank_card'];
                $xlsData[$key]['promote_bank_card'] = ' '.$vo['promote_bank_card'];
                $xlsData[$key]['promote_alipay_account'] = ' '.$vo['promote_alipay_account'];
                // $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
                $xlsData[$key]['minus_promoter_earn'] = sprintf("%.2f",($vo['total_money'] - $vo['promoter_earn']));

                $total_promoter_earn += $vo['promoter_earn'];
                $total_plateform_earn += $vo['plateform_earn'];
                $total_earn += $vo['total_money'];
                $total_remit += $vo['remit_amount'];

            }
        }
        //如果为空则返回
        if(empty($xlsData)){
            // return;
            $this->error('没有数据!');
        }
        write_action_log("导出渠道周期结算表");
        // return json($xlsData);
        // var_dump($xlsData);exit;


        // 拼接最后一行(汇总)
        $tmp_append_arr = [
                'period' => "汇总",
                'promote_account' => '--',
                'total_money' => '--',
                'minus_promoter_earn' => sprintf("%.2f",$total_plateform_earn),
                'promoter_earn' => sprintf("%.2f",$total_promoter_earn),
                'is_remit_string' => '--',
                'remit_info' => $total_remit,
                'remark' => '--',
        ];
        $xlsData[$key + 1] = $tmp_append_arr;  // 最后一行 汇总


        $xlsName_2 = $xlsName;
        $xlsName = $xlsName_2;
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


    // 查看渠道的账户信息 银行卡 支付宝等信息
    public function show_promote_bank(Request $request){
        $param = $request->param();
        $promote_id = $param['promote_id'];
        $promoteInfo = Db::table('tab_promote')->where(['id'=>$promote_id])->find();
        if(empty($promoteInfo)){
            return json(['code'=>-1,'msg'=>'当前渠道异常','data'=>[]]);
        }
        $returnData = [
            'promote_id'=>$promoteInfo['id'],
            'promote_account'=>$promoteInfo['account'],
            'bank_name'=>$promoteInfo['bank_name'],
            'bank_card'=>$promoteInfo['bank_card'],
            'bank_phone'=>$promoteInfo['bank_phone'],
            'alipay_account'=>$promoteInfo['alipay_account'],  // 支付宝账号
            'real_name'=>$promoteInfo['real_name'],  // 真实姓名
            'account_openin'=>$promoteInfo['account_openin'],// account_openin  开户行(开户网点)
        ];
        return json(['code'=>1,'msg'=>'当前渠道异常','data'=>$returnData]);

    }


    /**
     * @支付宝自动打款
     *
     * @author: zsl
     * @since: 2021/8/2 9:45
     */
    private function _alipayAutoPaid($orderNo,$order)
    {
        //支付宝提现配置
        $config = get_pay_type_set('zfb_tx');
        $pay = new \think\Pay('alipay', $config['config']);
        $vo   = new \think\pay\PayVo();
        $vo->setOrderNo($orderNo)
                ->setTable('promote_withdraw')
                ->setPayMethod("promote_withdraw_auto_paid")
                ->setDetailData('渠道提现自动打款');
        return $pay->buildRequestForm($vo);
    }



}
