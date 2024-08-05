<?php
/**
 * Created by gjt.
 * User: Administrator
 * Date: 2019/1/23
 * Time: 15:34
 */

namespace app\promote\controller;

use app\common\model\DateListModel;
use app\common\controller\BaseController;
use app\common\model\SupportModel;
use cmf\controller\AdminBaseController;
use app\promote\model\PromoteModel;
use app\promote\model\PromoteapplyModel;
use app\promote\model\PromoteunionModel;
use app\promote\model\PromotewithdrawModel;
use app\promote\model\PromotesettlementModel;
use think\Request;
use think\Db;

class ExportController extends AdminBaseController
{
    function expUser()
    {
        $id = $this->request->param('id', 0, 'intval');
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();
        switch ($id) {
            case 1://推官员列表
                $xlsCell = array(
                    array('id', 'ID'),
                    array('account', "渠道账号"),
                    array('real_name', "姓名"),
                    array('mobile_phone', "手机号"),
                    array('cash_money', "渠道押金"),
                    array('balance_coin', "平台币余额"),
                    array('total', "总流水"),
                    array('total_register', "总注册"),
                    array('pattern', "结算类型"),
                    array('level_name', "推广等级"),
                    array('register_type', "渠道类型"),
                    array('settlement_day_period', "自动结算周期"),
                    array('game_ids', "不可推广游戏"),
                    array('create_time', "注册时间"),
                    array('last_login_time', '最后登录时间'),
                    array('level', "等级"),
                    array('parent_id', '上线渠道'),
                    array('busier_id', '商务专员'),
                    array('status', "状态"),
                );
                $base = new BaseController();
                $model = new PromoteModel();
                //添加搜索条件
                $data = $this->request->param();
                $promote_id = $data['promote_id'];
                if ($promote_id) {
                    $map['id'] = $promote_id;
                }
                $account = $data['account'];
                if ($account) {
                    $map['account'] = $account;
                }
                $busier_id = $data['busier_id'];
                if($busier_id!=''){
                    $map['busier_id'] = $busier_id;
                }
                $parent_id = $data['parent_id'];//上线渠道
                if ($parent_id) {
                    $map['parent_id|id'] = $parent_id;
                }
                $level = $data['level'];//渠道等级
                if ($level == 1) {
                    $map['parent_id'] = 0;
                } elseif ($level == 2) {
                    $map['parent_id'] = ['gt', 0];
                }

                $status = $data['status'];
                if ($status != '') {
                    $map['status'] = $status;
                }
                $exend['order'] = 'create_time desc';
                $exend['field'] = 'id,account,mobile_phone,balance_coin,create_time,last_login_time,parent_id,busier_id,status,promote_level,cash_money,real_name,pattern,register_type,settlement_day_period,game_ids';
                $xlsData = $base->data_list_select($model, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    if (AUTH_USER == 1) {
                        $xlsData[$key]['total'] = get_promote_user_pay($value['id']);
                    } else {
                        $xlsData[$key]['total'] = '--';
                    }
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                    $xlsData[$key]['last_login_time'] = $value['last_login_time'] ? date('Y-m-d H:i:s', $value['create_time']) : '--';
                    $xlsData[$key]['level'] = $value['promote_level'] == 1 ? '一级渠道' : ($value['promote_level'] == 2 ?'二级渠道':'三级渠道');
                    $xlsData[$key]['parent_id'] = $value['parent_id'] == 0 ? get_promote_name($value['id']) : get_promote_name($value['parent_id']);
                    $xlsData[$key]['status'] = get_info_status($value['status'], 10);
                    $xlsData[$key]['busier_id'] = $value['busier_id']? get_business_entity($value['busier_id'],'account')['account'] : "官方";
                    // 总注册
                    $xlsData[$key]['total_register'] = get_promote_user($value['id']);
                    $xlsData[$key]['level_name'] = get_promote_level_name($value['id']);
                    $xlsData[$key]['pattern'] = get_info_status($value['pattern'],21);
                    $xlsData[$key]['register_type'] = get_info_status($value['register_type'],28);
                    if($value['promote_level'] == 1){
                        $xlsData[$key]['game_ids'] = empty($value['game_ids']) ? 0 : count(explode(',',$value['game_ids']));
                    }else{
                        $xlsData[$key]['game_ids'] = '--';
                        $xlsData[$key]['settlement_day_period'] = '--';
                    }
                }
                //获取汇总数据
                $promoteIds = Db ::table('tab_promote') -> where($map) -> column('id');
                $total_balance_coin = Db ::table('tab_promote') -> where($map) -> sum('balance_coin');//总平台币余额
                $total_cash_money = Db ::table('tab_promote') -> where($map) -> sum('cash_money');//总押金
                $where = [];
                $where['promote_id'] = ['in', $promoteIds];
                $total_user_count = Db ::table('tab_user') -> where($where) -> count();//总注册
                $where['puid'] = ['eq', 0];
                $total_pay_amount = Db ::table('tab_user') -> where($where) -> sum('cumulative');
                $xlsData[] = array(
                    'id'=>'累计汇总',
                    'cash_money'=>$total_cash_money,
                    'balance_coin'=>$total_balance_coin,
                    'total'=>$total_pay_amount,
                    'total_register'=>$total_user_count,
                );
                write_action_log("导出推广员列表");
                break;
            case 2:
                $xlsCell = array(
                    array('id', 'ID'),
                    array('promote_id', '渠道账号'),
                    array('game_name', "游戏名称"),
                    array('apply_time', "申请时间"),
                    array('status', "审核状态"),
                    array('enable_status', "打包状态"),
                    array('pack_type', "打包方式"),
                    array('dispose_time', '审核时间'),
                    array('dow_status', "下载状态"),
                    array('promote_ratio', '分成比例'),
                    array('promote_money', "注册单价"),
                    array('user_limit_discount', "玩家最低折扣/折"),
                );

                $base = new BaseController();
                $model = new PromoteapplyModel();
                //添加搜索条件
                $data = $this->request->param();
                if($data['sdk_version'] == 4){
                    $xlsCell = array(
                        array('id', 'ID'),
                        array('promote_id', '渠道账号'),
                        array('game_name', "游戏名称"),
                        array('apply_time', "申请时间"),
                        array('status', "审核状态"),
                        array('dispose_time', '审核时间'),
                        array('promote_ratio', '分成比例'),
                        array('promote_money', "注册单价"),
                        array('user_limit_discount', "玩家最低折扣/折"),
                    );
                    $xlsName = '页游分包记录';
                }
                $promote_id = $data['promote_id'];
                if ($promote_id) {
                    $map['promote_id'] = $promote_id;
                }
                $game_id = $data['game_id'];
                if ($game_id) {
                    $map['game_id'] = $game_id;
                }

                $status = $data['status'];
                if ($status != '') {
                    $map['status'] = $status;
                }

                $enable_status = $data['enable_status'];
                if ($enable_status != '') {
                    $map['enable_status'] = $enable_status;
                }

                $dow_status = $data['dow_status'];
                if ($dow_status) {
                    $map['g.dow_status'] = $dow_status;
                }
                $sdk_version = $data['sdk_version'];
                if ($sdk_version) {
                    $map['g.sdk_version'] = $sdk_version;
                }
                $exend['order'] = 'apply_time desc';
                $exend['field'] = 'tab_promote_apply.id,game_id,g.game_name,promote_id,apply_time,status,enable_status,dispose_time,promote_ratio,promote_money,g.dow_status,g.ratio,g.money,tab_promote_apply.pack_type,dow_url,and_status,ios_status,g.sdk_version,user_limit_discount';
                $exend['join1'][] = ['tab_game' => "g"];
                $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
                $xlsData = $base->data_list_join_select($model, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['promote_id'] = get_promote_name($value['promote_id']);
                    $xlsData[$key]['apply_time'] = date('Y-m-d H:i:s', $value['apply_time']);
                    $xlsData[$key]['status'] = get_info_status($value['status'], 12);
                    if($value['sdk_version'] == 3){
                        $xlsData[$key]['enable_status'] = "安卓：".get_info_status($value['and_status'], 13)."  苹果：".get_info_status($value['ios_status'], 13);
                        $xlsData[$key]['pack_type'] = '';
                    }else{
                        $xlsData[$key]['enable_status'] = get_info_status($value['enable_status'], 13);
                        $xlsData[$key]['pack_type'] = $value['pack_type'] == 0 ? '' : ($value['pack_type'] == 1 ? '渠道打包' : '免分包打包');
                    }
                    $xlsData[$key]['dispose_time'] = $value['dispose_time'] ? date('Y-m-d H:i:s', $value['dispose_time']) : '';
                    $xlsData[$key]['dow_status'] = get_info_status($value['dow_status'], 4);
                    $xlsData[$key]['promote_ratio'] = null_to_0($value['promote_ratio'] ?: $value['ratio']) . '%';
                    $xlsData[$key]['promote_money'] = null_to_0($value['promote_money'] ?: $value['money']);
                    $xlsData[$key]['user_limit_discount'] = null_to_0($value['user_limit_discount']);
                }
                write_action_log("导出游戏分包记录");
                break;
            case 3://联盟申请
                $xlsCell = array(
                    array('id', '编号'),
                    array('union_id', "渠道账号"),
                    array('apply_domain_type', "站点来源"),
                    array('domain_url', "站点链接"),
                    array('status', "审核状态"),
                    array('apply_time', "创建时间"),
                );

                $base = new BaseController();
                $model = new PromoteunionModel();
                //添加搜索条件
                $data = $this->request->param();
                $promote_id = $data['promote_id'];
                if ($promote_id) {
                    $map['union_id'] = $promote_id;
                }

                $status = $data['status'];
                if ($status != '') {
                    $map['status'] = $status;
                }
                $apply_domain_type = $data['apply_domain_type'];
                if ($apply_domain_type != '') {
                    $map['apply_domain_type'] = $apply_domain_type;
                }
                $status = $data['status'];
                if ($status != '') {
                    $map['status'] = $status;
                }
                $exend['order'] = 'apply_time desc';
                $exend['field'] = '*';
                $xlsData = $base->data_list_select($model, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['union_id'] = get_promote_name($value['union_id']);
                    $xlsData[$key]['apply_time'] = date('Y-m-d H:i:s', $value['apply_time']);
                    $xlsData[$key]['status'] = $value['status'] == 1 ? '已通过' : ($value['status'] == 0 ? '待审核' : '已驳回');
                    $xlsData[$key]['apply_domain_type'] = $value['apply_domain_type'] == 0 ? '系统分配' : '自主添加';
                }
                write_action_log("导出联盟站申请记录");
                break;
            case 4://结算记录
                $xlsCell = array(
                    array('promote_id', '渠道账号'),
                    array('starttimeendtime', "结算周期"),
                    array('settlement_number', "结算单号"),
                    array('tm', "总充值"),
                    array('tn', "总注册"),
                    array('sm', "结算金额"),
                    array('bind_coin_status', "结算范畴"),
                    array('create_time', "结算时间"),
                );

                $base = new BaseController();
                $model = new PromotesettlementModel();
                $start_time = $this->request->param('start_time', '');
                $end_time = $this->request->param('end_time', '');
                if ($start_time && $end_time) {
                    $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['create_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['create_time'] = ['egt', strtotime($start_time)];
                }
                $promote_id = $this->request->param('promote_id', 0, 'intval');
                if ($promote_id > 0) {
                    $map['promote_id'] = $promote_id;
                }
                $start_s_time = $this->request->param('start_s_time', '1971-01-01');
                $end_s_time = $this->request->param('end_s_time', date('Y-m-d', time()));
                $map['starttime'] = ['elt', strtotime($end_s_time) + 24 * 3600 - 1];
                $map['endtime'] = ['egt', strtotime($start_s_time)];
                $exend['order'] = 'create_time desc';
                $exend['field'] = '*,sum(total_money) as tm,sum(total_number) as tn,sum(sum_money) as sm';
                $exend['group'] = 'settlement_number';
                $xlsData = $base->data_list_select($model, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['promote_id'] = get_promote_name($value['promote_id']);
                    $xlsData[$key]['starttimeendtime'] = date('Y-m-d', $value['starttime']) . '至' . date('Y-m-d', $value['endtime']);
                    $xlsData[$key]['bind_coin_status'] = $value['bind_coin_status'] ? '包含绑币' : '排除绑币';
                    $xlsData[$key]['create_time'] = date('Y-m-d', $value['create_time']);
                }
                $exend['field'] = 'sum(sum_money) as total';
                $exend['group'] = null;
                //累计充值
                $total = $base->data_list_select($model, $map, $exend);
                $map = [];
                //今日充值
                $map['create_time'] = ['between', total(1, 2)];
                $today = $base->data_list_select($model, $map, $exend);
                //昨日充值
                $map['create_time'] = ['between', total(5, 2)];
                $yestoday = $base->data_list_select($model, $map, $exend);
                $xlsData[] = ['promote_id' => '汇总:', 'starttimeendtime' => '今日结算：', 'settlement_number' => null_to_0($today[0]['total']), 'tm' => '昨日结算：', 'tn' => null_to_0($yestoday[0]['total']), 'sm' => '累计结算：', 'bind_coin_status' => null_to_0($total[0]['total'])];
                break;
            case 5://提现记录
                $xlsCell = array(
                    array('widthdraw_number', '提现单号'),
                    array('sum_money', "提现金额"),
                    array('fee', "提现手续费"),
                    array('money', "打款金额"),
                    array('promote_id', "渠道账号"),
                    array('status', "提现状态"),
                    array('audit_time', "审核时间"),
                    array('withdraw_type', "打款方式"),
                    array('info', "打款信息"),
                    array('mark','备注')
                );

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
                $xlsData = $base->data_list_select($model, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['promote_id'] = get_promote_name($value['promote_id']);
                    $xlsData[$key]['create_time'] = date('Y-m-d', $value['create_time']);
                    switch ($value['status']){
                        case 1: $xlsData[$key]['status'] = '已通过';break;
                        case 2: $xlsData[$key]['status'] = '已驳回';break;
                        case 3: $xlsData[$key]['status'] = '已打款';break;
                        default:$xlsData[$key]['status'] = '待审核';break;
                    }
                    $xlsData[$key]['audit_time'] = empty($value['audit_time'])?'--':date('Y-m-d', $value['audit_time']);
                    $xlsData[$key]['withdraw_type'] = $value['status']==3?($value['withdraw_type']!=0?($value['withdraw_type']==1?'手动':'自动'):'--'):'--';
                    $xlsData[$key]['money'] = $value['sum_money'] - $value['fee'];
                    if($value['status'] == 3){
                        $xlsData[$key]['info'] = '打款方式：' . ($value['withdraw_way'] == 1 ? '支付宝' : '银行卡') . PHP_EOL;
                        $xlsData[$key]['info'] .= $value['payment_account'] . '，' . $value['payment_name'] . PHP_EOL;
                        $xlsData[$key]['info'] .= '实际打款金额：' . $value['payment_money'];
                    }else{
                        $xlsData[$key]['info'] = '--';
                    }
                    $xlsData[$key]['mark'] = empty($value['remark']) ? '--' : $value['remark'];
                    $xlsData[$key]['widthdraw_number'] = '提现单号：' . $value['widthdraw_number'].PHP_EOL;
                    $xlsData[$key]['widthdraw_number'] .= '提现时间：' . date('Y-m-d H:i:s',$value['create_time']);
                }
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
                $xlsData[] = ['widthdraw_number' => '汇总:', 'sum_money' => '今日提现：', 'fee' => null_to_0($today[0]['total'])];
                $xlsData[] = ['sum_money' => '昨日提现：', 'fee' => null_to_0($yestoday[0]['total'])];
                $xlsData[] = ['sum_money' => '累计提现：', 'fee' => null_to_0($total[0]['total'])];
                write_action_log("导出渠道提现记录");
                break;
            case 6://渠道管理_结算订单
                $xlsCell = array(
                    array('user_account', '账号'),
                    array('game_name', "游戏名称"),
                    array('pattern_name', "结算模式"),
                    array('pay_order_number', "订单号"),
                    array('cost', "订单金额"),
                    array('pay_amount', "实付金额"),
                    array('pattern_bili', "分成比例/注册单价"),
                    array('sum_money', "分成佣金"),
                    array('parent_name', "所属渠道"),
                    array('create_time', "创建时间"),
                );
                $data = $this->request->param();

                $map['status'] = $data['status'] ? $data['status'] : 0;
                $map['is_check'] = $data['is_check'] ? $data['is_check'] : 0;
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
                    $map['promote_id|parent_id'] = $data['promote_id'];
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
                $base = new BaseController();
                $model = new PromotesettlementModel();

                $exend['order'] = 'id desc';
                $exend['filed'] = 'id,promote_account,game_name,pattern,ratio,money,total_money,sum_money,user_account,is_check,parent_name,pay_order_number,pay_amount,cost,create_time';
                $xlsData = $base->data_list($model, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['pattern_name'] = $value['pattern'] == 0 ? 'CPS' : 'CPA';
                    $xlsData[$key]['pattern_bili'] = $value['pattern'] == 0 ? $value['ratio'] : $value['money'];
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                }
                //累计充值
                $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney')->where($map)->find();
                $xlsData[] = [
                    'user_account' => '汇总:',
                    'cost' => $total['totalcost'],
                    'pay_amount' => $total['totalamount'],
                    'sum_money' => $total['totalmoney']
                ];
                write_action_log("导出渠道结算记录");
                break;
            case 7://兑换记录
                $xlsCell = array(
                        array('widthdraw_number', '兑换单号'),
//                        array('settlement_number', "结算单号"),
                        array('sum_money', "兑换金额"),
                        array('promote_id', "渠道账号"),
                        array('create_time', "申请时间"),
                        array('status', "兑换状态"),
                        array('audit_time', "审核时间"),
                );

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
                $xlsData = $base->data_list_select($model, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['sum_money'] = null_to_0($value['sum_money']);
                    $xlsData[$key]['promote_id'] = get_promote_name($value['promote_id']);
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                    $xlsData[$key]['status'] = $value['status'] == 0 ? '待审核' : ($value['status'] == 1 ? '已通过' : ($value['status'] == 2 ? '已驳回' : '已发放'));
                    $xlsData[$key]['audit_time'] = $value['audit_time']?date('Y-m-d H:i:s', $value['audit_time']):'';
                    $xlsData[$key]['withdraw_type'] = $value['withdraw_type'] != 0 ? ($value['withdraw_type'] == 1 ? '手动' : '自动') : '--';
                }
                $exend['field'] = 'sum(sum_money) as total';
                //累计充值
                $total = $base->data_list_select($model, $map, $exend);
                $map = [];
                $map['type'] = 2;
                $map['promote_level'] = 1;
                //今日兑换
                $map['create_time'] = ['between', total(1, 2)];
                $today = $base->data_list_select($model, $map, $exend);
                //昨日兑换
                $map['create_time'] = ['between', total(5, 2)];
                $yestoday = $base->data_list_select($model, $map, $exend);
                $xlsData[] = [ 'widthdraw_number' => '今日兑换：', 'sum_money' => null_to_0($today[0]['total']), 'promote_id' => '昨日兑换：', 'create_time' => null_to_0($yestoday[0]['total']), 'status' => '累计兑换：', 'audit_time' => null_to_0($total[0]['total'])];
                write_action_log("导出渠道收益兑换记录");
                break;
            case 8:// 扶持记录
                $xlsCell = array(
                    array('id', '编号'),
                    array('promote_account', "申请渠道"),
                    array('user_account', "玩家账号"),
                    array('game_name', "游戏名称"),
                    array('server_name', "区服"),
                    array('role_name', "角色名"),
                    array('apply_num', "申请额度"),
                    array('support_type', "扶持类型"),
                    array('remark', "申请人备注"),
                    array('create_time', "申请时间"),
                    array('usable_num', "可用额度"),
                    array('send_num', "实际发放额度"),
                    array('status', "状态"),
                );
                $base = new BaseController();
                $model = new SupportModel();
                $data = $this -> request -> param();
                $map = [];
                if($data['promote_id'] > 0){
                    $map['promote_id'] = $data['promote_id'];
                }
                if($data['game_id'] > 0){
                    $map['game_id'] = $data['game_id'];
                }
                if($data['support_type'] != ''){
                    $map['support_type'] = $data['support_type'];
                }
                if($data['status'] != ''){
                    $map['status'] = $data['status'];
                }
                //区服筛选
                if($data['server_id'] != ''){
                    $map['server_id'] = $data['server_id'];
                }
                $exend['order'] = 'create_time desc';
                $exend['field'] = '*';
                $xlsData = $base -> data_list_select($model, $map, $exend);
                $status = [
                    '0'=>'待审核',
                    '1'=>'已通过',
                    '2'=>'已驳回',
                    '3'=>'已发放',
                ];
                foreach ($xlsData as $key => $val)
                {
                     $xlsData[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
                     $xlsData[$key]['support_type'] = $val['support_type']==0 ? '新增扶持' : '后续扶持';
                     $xlsData[$key]['status'] = $status[$val['status']];
                }
                write_action_log("导出扶持记录");
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
    /**
     * created by wjd 
     * 导出xsl
     * 2021-5-17 19:46:50
     */
    public function exportXsl()
    {
        $id = $this->request->param('id', 0, 'intval');
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();
        $xlsCell = [];
        $xlsData = [];
        if($id == 101){  // 未结算订单导出xsl
            $xlsCell = array(
                array('user_account', "账号"),
                array('game_name', "游戏名称"),
                array('pattern', "结算模式"),
                array('pay_order_number', "订单号"),
                array('cost', "订单金额"),
                array('pay_amount', "实付金额"),
                array('ratio', "分成比例/注册单价"),
                array('sum_money', "分成佣金"),
                array('pay_way', "支付方式"),
                array('promote_account', "所属渠道"),
                array('create_time', "创建日期"),
            );

            // $is_bind = $param['is_bind'] ?? 0; // 0:排除綁币, 1: 包含綁币
            $map['status'] = 0;
            $map['is_check'] = 1;
            $map['pay_way'] = ['neq',1];
            if ($param['user_account']) {
                $map['user_account'] = ['like', '%' . $param['user_account'] . '%'];
            }
            if ($param['pay_order_number']) {
                $map['pay_order_number'] = ['like', '%' . $param['pay_order_number'] . '%'];
            }
            if ($param['game_id']) {
                $map['game_id'] = $param['game_id'];
            }
            if ($param['promote_id']) {
                $map['top_promote_id'] = $param['promote_id'];
            }
            if($param['is_bind'] ==1){
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
            $xlsData = $base->data_list_select($model, $map, $exend);
            // return json($xlsData);
            $pay_way_array = [1=>'綁币', 2=>'平台币', 3=>'支付宝', 4=>'微信', 5=>'谷歌', 6=>'苹果支付'];  // 1 绑币 2:平台币,3:支付宝,4:微信 5: 谷歌 6 苹果支付',
            foreach ($xlsData as $key => $value) {
                // if (AUTH_USER == 1) {
                //     $xlsData[$key]['total'] = get_promote_user_pay($value['id']);
                // } else {
                //     $xlsData[$key]['total'] = '--';
                // }
                if($value['pattern'] == 0){
                    $xlsData[$key]['pattern'] = 'CPS';
                }else{
                    $xlsData[$key]['pattern'] = 'CPA';
                }
                $xlsData[$key]['pay_way'] = $pay_way_array[$value['pay_way']];
                $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                $xlsData[$key]['promote_account'] = get_promote_name($value['top_promote_id']);

            }
            //累计充值
            $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney')->where($map)->find();
            $xlsData[] = [
                'user_account' => '汇总:',
                'cost' => $total['totalcost'], // 订单金额
                'pay_amount' => $total['totalamount'], // 实付金额
                'sum_money' => $total['totalmoney']  //分成佣金
            ];
            write_action_log("导出未结算订单");

            // var_dump($param);exit;
        }
        if($id == 102){  // 已结算订单
            $xlsCell = array(
                array('user_account', "账号"),
                array('game_name', "游戏名称"),
                array('pattern', "结算模式"),
                array('pay_order_number', "订单号"),
                array('cost', "订单金额"),
                array('pay_amount', "实付金额"),
                array('ratio', "分成比例/注册单价"),
                array('sum_money', "分成佣金"),
                array('pay_way', "支付方式"),
                array('promote_account', "所属渠道"),
                array('update_time', "结算时间"),
            );
            $map['status'] = 1;
            $map['is_check'] = 1;
            if ($param['user_account']) {
                $map['user_account'] = ['like', '%' . $param['user_account'] . '%'];
            }
            if ($param['pay_order_number']) {
                $map['pay_order_number'] = ['like', '%' . $param['pay_order_number'] . '%'];
            }
            if ($param['game_id']) {
                $map['game_id'] = $param['game_id'];
            }
            if ($param['promote_id']) {
                $map['top_promote_id'] = $param['promote_id'];
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
            // $list_data = $base->data_list($model, $map, $exend);
            $xlsData = $base->data_list_select($model, $map, $exend);
            // return json($xlsData);
            $pay_way_array = [1=>'綁币', 2=>'平台币', 3=>'支付宝', 4=>'微信', 5=>'谷歌', 6=>'苹果支付'];  // 1 绑币 2:平台币,3:支付宝,4:微信 5: 谷歌 6 苹果支付',
            foreach ($xlsData as $key => $value) {
                // if (AUTH_USER == 1) {
                //     $xlsData[$key]['total'] = get_promote_user_pay($value['id']);
                // } else {
                //     $xlsData[$key]['total'] = '--';
                // }
                if($value['pattern'] == 0){
                    $xlsData[$key]['pattern'] = 'CPS';
                }else{
                    $xlsData[$key]['pattern'] = 'CPA';
                }
                $xlsData[$key]['pay_way'] = $pay_way_array[$value['pay_way']];
                $xlsData[$key]['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
                $xlsData[$key]['promote_account'] = get_promote_name($value['top_promote_id']);
            }
            //累计充值
            $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney')->where($map)->find();
            $xlsData[] = [
                'user_account' => '汇总:',
                'cost' => $total['totalcost'], // 订单金额
                'pay_amount' => $total['totalamount'], // 实付金额
                'sum_money' => $total['totalmoney']  //分成佣金
            ];
            write_action_log("导出已结算订单");
        }
        if($id == 103){  // 不参与结算订单
            $xlsCell = array(
                array('user_account', "账号"),
                array('game_name', "游戏名称"),
                array('pattern', "结算模式"),
                array('pay_order_number', "订单号"),
                array('cost', "订单金额"),
                array('pay_amount', "实付金额"),
                array('ratio', "分成比例/注册单价"),
                array('sum_money', "分成佣金"),
                array('pay_way', "支付方式"),
                array('promote_account', "所属渠道"),
                array('create_time', "创建时间"),
            );
            $map['status'] = 0;
            $map['is_check'] = 0;
            if ($param['user_account']) {
                $map['user_account'] = ['like', '%' . $param['user_account'] . '%'];
            }
            if ($param['pay_order_number']) {
                $map['pay_order_number'] = ['like', '%' . $param['pay_order_number'] . '%'];
            }
            if ($param['game_id']) {
                $map['game_id'] = $param['game_id'];
            }
            if ($param['promote_id']) {
                $map['top_promote_id'] = $param['promote_id'];
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
            // $list_data = $base->data_list($model, $map, $exend);
            $xlsData = $base->data_list_select($model, $map, $exend);
            // return json($xlsData);
            $pay_way_array = [1=>'綁币', 2=>'平台币', 3=>'支付宝', 4=>'微信', 5=>'谷歌', 6=>'苹果支付'];  // 1 绑币 2:平台币,3:支付宝,4:微信 5: 谷歌 6 苹果支付',
            foreach ($xlsData as $key => $value) {
                
                if($value['pattern'] == 0){
                    $xlsData[$key]['pattern'] = 'CPS';
                }else{
                    $xlsData[$key]['pattern'] = 'CPA';
                }
                $xlsData[$key]['pay_way'] = $pay_way_array[$value['pay_way']];
                $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                $xlsData[$key]['promote_account'] = get_promote_name($value['top_promote_id']);

            }
            //累计充值
            $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney')->where($map)->find();
            $xlsData[] = [
                'user_account' => '汇总:',
                'cost' => $total['totalcost'], // 订单金额
                'pay_amount' => $total['totalamount'], // 实付金额
                'sum_money' => $total['totalmoney']  //分成佣金
            ];
            write_action_log("导出不参与结算订单");
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
    /**
     * created by wjd 
     * 导出 预付款 xsl
     * 2021-6-8 16:20:49
     */
    public function exportPrepayment()
    {
        $id = $this->request->param('id', 0, 'intval');
        $xlsName = $this->request->param('xlsname');
        $req = $this->request->param();
        $xlsCell = [];
        $xlsData = [];
        if($id == 101){  // 预付款充值记录
            $xlsCell = array(
                array('id', 'ID'),
                array('order_number', "订单号"),
                array('promote_account', "账号"),
                array('create_time', "时间"),
                array('pay_amount', "充值金额"),
                array('pay_way', "充值方式"),
                array('pay_status', "充值状态"),
                
            );
            $search = [];
            $pay_order_number = $req['pay_order_number'] ?? '';
            $start_time = $req['start_time'] ?? '';
            $end_time = $req['end_time'] ?? '';
            $pay_way = $req['pay_way'] ?? '';
            $pay_status = $req['pay_status'] ?? -1;
            $promote_id = $req['promote_id'] ?? '';
            $promote_account = $req['account'] ?? '';
            if(!empty($pay_order_number)){
                $search['pay_order_number'] = ['like','%'.$pay_order_number.'%'];
            }
            if(!empty($start_time)){
                $start_time1 = strtotime($start_time);
                $search['create_time'] = ['gt', $start_time1];
            }
            if(!empty($end_time)){
                $end_time1 = strtotime($end_time);
                $search['create_time'] = ['lt',$end_time1+86399];
            }
            if(!empty($start_time) && !empty($end_time)){
                $search['create_time'] = ['between', [strtotime($start_time), strtotime($end_time)+86399]];
            }
            if(!empty($pay_way)){
                $search['pay_way'] = $pay_way;
            }
            if($pay_status === '0'){
                $search['pay_status'] = $pay_status;
            }
            if($pay_status == 1){
                $search['pay_status'] = $pay_status;
            }
            if(!empty($promote_id)){
                $search['promote_id'] = $promote_id;
            }
            if(!empty($promote_account)){
                $search['promote_account'] = $promote_account;
            }
            echo '功能完善中...';exit;
            // 后续接着改 wjd 2021-6-8 16:34:32






            $model = new PromotesettlementModel();
            $base = new BaseController();
            $exend['order'] = 'id desc';
            $exend['field'] = 'id,promote_account,top_promote_id,game_name,pattern,ratio,money,sum_money,user_account,is_check,parent_name,pay_order_number,pay_amount,cost,create_time,pay_way';
            $xlsData = $base->data_list_select($model, $map, $exend);
            // return json($xlsData);
            $pay_way_array = [1=>'綁币', 2=>'平台币', 3=>'支付宝', 4=>'微信', 5=>'谷歌', 6=>'苹果支付'];  // 1 绑币 2:平台币,3:支付宝,4:微信 5: 谷歌 6 苹果支付',
            foreach ($xlsData as $key => $value) {
                // if (AUTH_USER == 1) {
                //     $xlsData[$key]['total'] = get_promote_user_pay($value['id']);
                // } else {
                //     $xlsData[$key]['total'] = '--';
                // }
                if($value['pattern'] == 0){
                    $xlsData[$key]['pattern'] = 'CPS';
                }else{
                    $xlsData[$key]['pattern'] = 'CPA';
                }
                $xlsData[$key]['pay_way'] = $pay_way_array[$value['pay_way']];
                $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);

            }
            //累计充值
            $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney')->where($map)->find();
            $xlsData[] = [
                'id' => '汇总:',
                'cost' => $total['totalcost'], // 订单金额
                'pay_amount' => $total['totalamount'], // 实付金额
                'sum_money' => $total['totalmoney']  //分成佣金
            ];


            // var_dump($param);exit;
        }
        if($id == 102){  // 已结算订单
            $xlsCell = array(
                array('id', 'ID'),
                array('user_account', "账号"),
                array('game_name', "游戏名称"),
                array('pattern', "结算模式"),
                array('pay_order_number', "订单号"),
                array('cost', "订单金额"),
                array('pay_amount', "实付金额"),
                array('ratio', "分成比例/注册单价"),
                array('sum_money', "分成佣金"),
                array('pay_way', "支付方式"),
                array('promote_account', "所属渠道"),
                array('update_time', "结算时间"),
            );
            $map['status'] = 1;
            $map['is_check'] = 1;
            $param = $req;
            if ($param['user_account']) {
                $map['user_account'] = ['like', '%' . $param['user_account'] . '%'];
            }
            if ($param['pay_order_number']) {
                $map['pay_order_number'] = ['like', '%' . $param['pay_order_number'] . '%'];
            }
            if ($param['game_id']) {
                $map['game_id'] = $param['game_id'];
            }
            if ($param['promote_id']) {
                $map['top_promote_id'] = $param['promote_id'];
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
            // $list_data = $base->data_list($model, $map, $exend);
            $xlsData = $base->data_list_select($model, $map, $exend);
            // return json($xlsData);
            $pay_way_array = [1=>'綁币', 2=>'平台币', 3=>'支付宝', 4=>'微信', 5=>'谷歌', 6=>'苹果支付'];  // 1 绑币 2:平台币,3:支付宝,4:微信 5: 谷歌 6 苹果支付',
            foreach ($xlsData as $key => $value) {
                // if (AUTH_USER == 1) {
                //     $xlsData[$key]['total'] = get_promote_user_pay($value['id']);
                // } else {
                //     $xlsData[$key]['total'] = '--';
                // }
                if($value['pattern'] == 0){
                    $xlsData[$key]['pattern'] = 'CPS';
                }else{
                    $xlsData[$key]['pattern'] = 'CPA';
                }
                $xlsData[$key]['pay_way'] = $pay_way_array[$value['pay_way']];
                $xlsData[$key]['update_time'] = date('Y-m-d H:i:s', $value['update_time']);

            }
            //累计充值
            $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney')->where($map)->find();
            $xlsData[] = [
                'id' => '汇总:',
                'cost' => $total['totalcost'], // 订单金额
                'pay_amount' => $total['totalamount'], // 实付金额
                'sum_money' => $total['totalmoney']  //分成佣金
            ];
        }
        if($id == 103){  // 不参与结算订单
            $xlsCell = array(
                array('id', 'ID'),
                array('user_account', "账号"),
                array('game_name', "游戏名称"),
                array('pattern', "结算模式"),
                array('pay_order_number', "订单号"),
                array('cost', "订单金额"),
                array('pay_amount', "实付金额"),
                array('ratio', "分成比例/注册单价"),
                array('sum_money', "分成佣金"),
                array('pay_way', "支付方式"),
                array('promote_account', "所属渠道"),
                array('create_time', "创建时间"),
            );
            $map['status'] = 0;
            $map['is_check'] = 0;
            if ($param['user_account']) {
                $map['user_account'] = ['like', '%' . $param['user_account'] . '%'];
            }
            if ($param['pay_order_number']) {
                $map['pay_order_number'] = ['like', '%' . $param['pay_order_number'] . '%'];
            }
            if ($param['game_id']) {
                $map['game_id'] = $param['game_id'];
            }
            if ($param['promote_id']) {
                $map['top_promote_id'] = $param['promote_id'];
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
            // $list_data = $base->data_list($model, $map, $exend);
            $xlsData = $base->data_list_select($model, $map, $exend);
            // return json($xlsData);
            $pay_way_array = [1=>'綁币', 2=>'平台币', 3=>'支付宝', 4=>'微信', 5=>'谷歌', 6=>'苹果支付'];  // 1 绑币 2:平台币,3:支付宝,4:微信 5: 谷歌 6 苹果支付',
            foreach ($xlsData as $key => $value) {
                
                if($value['pattern'] == 0){
                    $xlsData[$key]['pattern'] = 'CPS';
                }else{
                    $xlsData[$key]['pattern'] = 'CPA';
                }
                $xlsData[$key]['pay_way'] = $pay_way_array[$value['pay_way']];
                $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);

            }
            //累计充值
            $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney')->where($map)->find();
            $xlsData[] = [
                'id' => '汇总:',
                'cost' => $total['totalcost'], // 订单金额
                'pay_amount' => $total['totalamount'], // 实付金额
                'sum_money' => $total['totalmoney']  //分成佣金
            ];
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
