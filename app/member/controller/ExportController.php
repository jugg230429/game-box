<?php
/**
 * Created by gjt.
 * User: Administrator
 * Date: 2019/1/23
 * Time: 15:34
 */

namespace app\member\controller;

use app\member\model\UserLoginRecordModel;
use app\member\model\UserModel;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use app\member\model\UserBalanceEditModel;
use think\Request;
use think\Db;

class ExportController extends AdminBaseController
{
    /**
     * @函数或方法说明
     * @导出方法
     * @author: 郭家屯
     * @since: 2019/3/29 9:34
     */
    function expUser()
    {
        $id = $this->request->param('id', 0, 'intval');
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();
        switch ($id) {
            case 1://用户列表导出
                $xlsCell = array(
                    array('id', '账号信息'),
                    array('account', "消费信息"),
                    array('cumulative', "VIP信息"),
                    array('count', "实名信息"),
                    array('last_pay_time', "注册信息"),
                    array('count_day', "最后登录信息"),
                );

                $user = new UserModel;
                $base = new BaseController;
                $user_id = $this->request->param('user_id', 0, 'intval');
                if ($user_id > 0) {
                    $map['tab_user.id'] = $user_id;
                }
                $account = $this->request->param('account', '');
                if ($account != '') {
                    $map['account'] = ['like', '%' . $account . '%'];
                }
                $small_nickname = $this->request->param('small_nickname', '');
                if ($small_nickname != '') {
                    $small = get_user_lists_info('puid',['puid'=>['gt',0],'nickname'=>['like', '%' . $small_nickname . '%']]);
                    if(!empty($small)){
                        $puid = implode(',',array_unique(array_column($small,'puid')));
                        $map['tab_user.id'] = ['in',$puid];
                    }else{
                        $map['tab_user.id'] = 0;//返回空数据
                    }
                }
                $equipment_num = trim($this->request->param('equipment_num'));
                if($equipment_num != ''){
                    $map['equipment_num'] = ['like', '%' . $equipment_num . '%'];
                }
                $register_ip = trim($this->request->param('register_ip'));
                if($register_ip != ''){
                    $map['register_ip'] = ['like', '%' . $register_ip . '%'];
                }
                $user_status = $this->request->param('user_status', '');
                if ($user_status==='1' || $user_status==='0') {
                    $map['lock_status'] = $user_status;
                }
                if ($user_status === '2') {
                    $map['un.status'] = 1;
                }
                $start_time = $this->request->param('start_time', '');
                $end_time = $this->request->param('end_time', '');
                if ($start_time && $end_time) {
                    $map['register_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['register_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['register_time'] = ['egt', strtotime($start_time)];
                }
                $login_start_time = $this->request->param('login_start_time', '');
                $login_end_time = $this->request->param('login_end_time', '');
                if ($login_start_time && $login_end_time) {
                    $map['login_time'] = ['between', [strtotime($login_start_time), strtotime($login_end_time) + 86399]];
                } elseif ($login_end_time) {
                    $map['login_time'] = ['lt', strtotime($login_end_time) + 86400];
                } elseif ($login_start_time) {
                    $map['login_time'] = ['egt', strtotime($login_start_time)];
                }
                $register_type = $this->request->param('register_type', '');
                if ($register_type != '') {
                    if($register_type=='catch_create'){
                        $map['is_batch_create'] = 1;
                    }else{
                        $map['register_type'] = $register_type;
                        $map['is_batch_create'] = 0;
                    }
                }
                $age_status = $this->request->param('age_status', '');
                if ($age_status != '') {
                    $map['age_status'] = $age_status;
                }
                $viplevel = $this->request->param('viplevel', '');
                if ($viplevel != '') {
                    $map['vip_level'] = $viplevel;
                }
                $cumulative = $this->request->param('cumulative');
                if($cumulative > 0){
                    switch ($cumulative){
                        case 1:
                            $map['cumulative'] = ['elt',100];
                            break;
                        case 2:
                            $map['cumulative'] = ['between',[101,1000]];
                            break;
                        case 3:
                            $map['cumulative'] = ['between',[1001,10000]];
                            break;
                        case 4:
                            $map['cumulative'] = ['between',[10001,50000]];
                            break;
                        case 5:
                            $map['cumulative'] = ['between',[50001,100000]];
                            break;
                        case 6:
                            $map['cumulative'] = ['gt',100000];
                            break;
                    }
                }
                $map['puid'] = 0;//只显示大号
                $sort_type = $this->request->param('sort_type', '');
                $sort = $this->request->param('sort', 1, 'intval');

                //数据
                if (AUTH_PAY == 1) {
                    //排序
                    if ($sort == 1) {
                        $exend['order'] = 'id desc';
                    } elseif ($sort == 2) {
                        $exend['order'] = "$sort_type desc";
                    } else {
                        $exend['order'] = "$sort_type asc";
                    }
                    $promote_id = $this->request->param('promote_id', '');
                    if ($promote_id != '') {
                        $promote_ids = get_zi_promote_id($promote_id);
                        array_push($promote_ids,$promote_id);
                        $map['tab_user.promote_id'] = ['in',$promote_ids];
                    }
                    $extend['row'] = $this->request->param('page');
                    $exend['field'] = 'tab_user.id,count(s.user_id) as count,max(s.pay_time) as last_pay_time,point,account,
            cumulative,tab_user.promote_id,tab_user.promote_account,balance,register_type,vip_level,register_time,
            login_time,login_ip,lock_status,equipment_num,fgame_name,member_days,end_time,register_ip,device_name,
            age_status,is_unsubscribe,un.status as unsubscribe_status,gold_coin,tab_user.is_batch_create,tab_user.login_equipment_num,tab_user.head_img';
                    $exend['group'] = 'tab_user.id';
                    $exend['join1'] = [['tab_spend' => 's'], 's.user_id=tab_user.id and s.pay_status=1', 'left'];
                    $exend['join2'] = [['tab_user_unsubscribe' => 'un'], 'un.user_id=tab_user.id', 'left'];
                    $data = $base->data_list_join($user, $map, $exend);
                } else {
                    $promote_id = $this->request->param('promote_id', '');
                    if ($promote_id != '') {
                        $map['promote_id'] = $promote_id;
                    }
                    $exend['order'] = 'id desc';
                    $exend['field'] = 'id,account,cumulative,promote_id,promote_account,balance,register_type,vip_level,register_time,login_time,login_ip,lock_status,is_batch_create';
                    $exend['group'] = 'id';
                    $data = $base->data_list($user, $map, $exend);
                }

                $user_statuses=array("0"=>'已锁定',"1"=>'正常');
                // return json($xlsData);
                if ($data) {
                    // $xlsData = $xlsData->toArray();
                    $xlsData = [];
                    foreach ($data as $key => $vo) {

                        $smallfield = 'id';
                        $smallmap['puid'] = $vo['id'];
                        $smallorder = 'id desc';
                        $vo['small_count'] = count(get_user_lists_info($smallfield, $smallmap, $smallorder));

                        $vo['last_pay_time'] = $vo['last_pay_time'] ? date('Y-m-d', $vo['last_pay_time']) : '--';

                        $xlsData[$key]['id'] = " 账号ID：{$vo['id']}".PHP_EOL."账号：{$vo['account']}".PHP_EOL."小号：{$vo['small_count']}".PHP_EOL;
                        if(!empty($vo['equipment_num'])){
                            $xlsData[$key]['id'].= "设备号：{$vo['equipment_num']}".PHP_EOL;
                        }else{
                            $xlsData[$key]['id'].= "设备号：--".PHP_EOL;
                        }
                        $xlsData[$key]['id'].= "所属渠道：".get_promote_name($vo['promote_id']).PHP_EOL;
                        if($vo['is_unsubscribe']=='1'){
                            $xlsData[$key]['id'].="账号状态：已注销".PHP_EOL;
                        }elseif($vo['unsubscribe_status']=='1'){
                            $xlsData[$key]['id'].="账号状态：待注销".PHP_EOL;
                        }else{
                            $xlsData[$key]['id'].= "账号状态：".$user_statuses[$vo['lock_status']].PHP_EOL;
                        }
                        $xlsData[$key]['account'] = "累计消费：{$vo['cumulative']}".PHP_EOL;
                        $xlsData[$key]['account'] .= "付费记录：{$vo['count']}".PHP_EOL;
                        $xlsData[$key]['account'] .= "上次消费：{$vo['last_pay_time']}".PHP_EOL;
                        $xlsData[$key]['account'] .= "平台币余额：{$vo['balance']}".PHP_EOL;
                        $xlsData[$key]['account'] .= "金币余额：{$vo['gold_coin']}".PHP_EOL;
                        $xlsData[$key]['cumulative'] = "VIP等级：{$vo['vip_level']}".PHP_EOL;
                        if($vo['member_days']){
                            $xlsData[$key]['cumulative'] .= "尊享卡：{$vo['member_days']}天（剩余{$vo['valid_days']}天）".PHP_EOL;
                        }else{
                            $xlsData[$key]['cumulative'] .= "尊享卡：无".PHP_EOL;
                        }
                        $xlsData[$key]['cumulative'] .= "剩余积分：{$vo['point']}".PHP_EOL;
                        $xlsData[$key]['count'] = get_user_age_status($vo['age_status']);
                        $xlsData[$key]['last_pay_time'] = "";
                        if ($vo['is_batch_create'] == 1) {
                            $xlsData[$key]['last_pay_time'] .= "注册方式：系统创建" . PHP_EOL;
                        } else {
                            $xlsData[$key]['last_pay_time'] .= "注册方式：" . get_user_register_type($vo['register_type']) . PHP_EOL;
                        }
                        if ($vo['register_time'] == '') {
                            $xlsData[$key]['last_pay_time'] .= "注册时间：--" . PHP_EOL;
                        } else {
                            $xlsData[$key]['last_pay_time'] .= "注册时间：" . date('Y-m-d H:i:s', $vo['register_time']) . PHP_EOL;
                        }
                        $xlsData[$key]['last_pay_time'] .= "注册游戏：{$vo['fgame_name']}".PHP_EOL;
                        $xlsData[$key]['last_pay_time'] .= "注册IP：{$vo['register_ip']}".PHP_EOL;
                        if(!empty($vo['device_name'])){
                            $xlsData[$key]['last_pay_time'] .= " 注册设备：{$vo['device_name']}".PHP_EOL;
                        }else{
                            $xlsData[$key]['last_pay_time'] .= " 注册设备：--".PHP_EOL;
                        }
                        if(!empty($vo['equipment_num'])){
                            $xlsData[$key]['last_pay_time'] .= "设备码：{$vo['equipment_num']}".PHP_EOL;
                        }else{
                            $xlsData[$key]['last_pay_time'] .= "设备码：--".PHP_EOL;
                        }
                        if($vo['login_time']==''){
                            $xlsData[$key]['count_day'] = '最后登录时间：--'.PHP_EOL;
                        }else{
                            $xlsData[$key]['count_day'] = "最后登录时间：".get_login_time($vo['login_time']).PHP_EOL;
                        }
                        $xlsData[$key]['count_day'] .= "最后登录IP：{$vo['login_ip']}".PHP_EOL;
                        if (!empty($vo['login_equipment_num'])) {
                            $xlsData[$key]['count_day'] .= "设备码：{$vo['login_equipment_num']}" . PHP_EOL;
                        } else {
                            $xlsData[$key]['count_day'] .= "设备码：--" . PHP_EOL;
                        }
                    }
                }
                $tmp_start_time = $start_time;
                $tmp_end_time = $end_time;
                if(empty($tmp_start_time)){
                    $tmp_start_time = '2019-08-01';
                }
                if(empty($tmp_end_time)){
                    $tmp_end_time = date('Y-m-d');
                }

                $xlsName_2 = $xlsName.'_'.$tmp_start_time.'到'.$tmp_end_time;
                $xlsName = $xlsName_2;
                write_action_log("导出用户列表");
                break;
            case 2://用户消费记录表
                if (AUTH_PAY == 1) {
                    $xlsCell = array(
                        array('pay_order_number', '订单号'),
                        array('game_name', "游戏名称"),
                        array('server_name', "游戏区服"),
                        array('game_player_name', "角色名"),
                        array('pay_way', "消费方式"),
                        array('pay_amount', "消费金额"),
                        array('pay_time', '消费时间'),
                        array('spend_ip', "消费IP"),
                    );
                    $map['pay_status'] = 1;
                    $map['user_id'] = $param['user_id'];
                    $xlsData = Db::table('tab_spend')->field('pay_order_number,game_name,server_name,game_player_name,pay_way,pay_amount,pay_time,spend_ip')->where($map)->select()
                        ->each(function ($item, $key) {
                            $item['pay_way'] = get_info_status($item['pay_way'], 1);
                            $item['pay_time'] = date('Y-m-d H:i:s', $item['pay_time']);
                            return $item;
                        });
                    //今日充值
                    $todaytotal = Db::table('tab_spend')->where($map)->where('pay_time', 'BETWEEN', total(1, 2))->sum('pay_amount');
                    //昨日充值
                    $yestodaytotal = Db::table('tab_spend')->where($map)->where('pay_time', 'BETWEEN', total(5, 2))->sum('pay_amount');
                    //总充值
                    $total = Db::table('tab_spend')->where($map)->sum('pay_amount');
                    $xlsData[] = ['pay_order_number' => '汇总', 'game_name' => '今日充值：', 'server_name' => $todaytotal, 'game_player_name' => '昨日充值：', 'pay_way' => $yestodaytotal, 'pay_amount' => '总充值：', 'pay_time' => $total];
                }
                write_action_log("导出用户游戏消费记录");
                break;
            case 3:
                $xlsCell = array(
                    array('game_name', "游戏名称"),
                    // array('server_name', "游戏区服"),
                    // array('game_player_name', "角色名"),
                    array('login_time', '登录时间'),
                    array('login_ip', "登录IP"),
                );
                $map['login_time'] = ['gt', 0];
                $map['user_id'] = $param['user_id'];
                $map['game_id'] = ['gt', 0];

                $mUserLoginRecord = new UserLoginRecordModel();
                $xlsData = $mUserLoginRecord
                    ->field('game_name,server_name,game_player_name,login_time,login_ip')
                    ->order('login_time DESC')
                    ->where($map)
                    ->select()
                    ->each(function ($item, $key) {
                        $item['login_time'] = date('Y-m-d H:i:s', $item['login_time']);
                        return $item;
                    });
                write_action_log("导出用户游戏登录记录");
                break;
            case 4:
                $xlsCell = array(
                    array('user_account', "玩家账号"),
                    array('promote_account', "补链前渠道"),
                    array('promote_account_to', "补链后渠道"),
                    array('cut_time', "补链生效时间"),
                    array('op_account', '操作人员'),
                    array('create_time', "操作时间"),
                    array('remark', "备注"),
                );
                $account = $param['account'];
                if ($account) {
                    $map['user_account'] = ['like', '%' . $account . '%'];
                }
                $xlsData = Db::table('tab_user_mend')->field('user_account,promote_account,promote_account_to,op_account,create_time,remark,cut_time')->where($map)->order('create_time desc')->select()->each(function ($item, $key) {
                    $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                    $item['cut_time'] = empty($item['cut_time']) ? '--' : date('Y-m-d H:i:s', $item['cut_time']);
                    return $item;
                });
                write_action_log("导出补链记录");
                break;
            case 5://账户修改记录
                $xlsCell = array(
                    array('user_account', "玩家账号"),
                    array('promote_account', "所属渠道"),
                    array('type', "货币类型"),
                    array('prev_amount', "修改前数量"),
                    array('amount', "修改后数量"),
                    array('real_amount', "余额变动"),
                    array('create_time', "修改时间"),
                    array('op_account', '操作人员'),
                );
                $model = new UserBalanceEditModel;
                $base = new BaseController;
                $account = $this->request->param('user_account', '');
                if ($account != '') {
                    $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
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
                $type = $this->request->param('type', '');
                if ($type != '') {
                    $map['type'] = $type;
                }

                $op_id = $this->request->param('admin_id', '');
                if ($op_id != '') {
                    $map['op_id'] = $op_id;
                }
                $exend['order'] = 'create_time desc';
                $xlsData = $base->data_list_select($model, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['game_name'] = $value['game_name'] ?: '--';
                    $xlsData[$key]['type'] = $value['type'] == 0 ? '平台币' : '绑币';
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                    $xlsData[$key]['real_amount'] = ($value['amount']-$value['prev_amount']>0?' +':'-') . (round(abs($value['amount']-$value['prev_amount']),2));
                }
                write_action_log("导出用户修改记录");
                break;
            case 6:
                $xlsCell = array(
                    array('account', "玩家账号"),
                    array('id', "账号ID"),
                    array('user_stage_name', "玩家阶段"),
                    array('user_score', "玩家评分"),
                    array('promote_account', "所属渠道"),
                    array('register_time', "注册时间"),
                    array('user_stage_remark', '备注'),
                );
                $user = new UserModel;;
                $base = new BaseController;
                $user_id = $this->request->param('user_id', 0, 'intval');
                if ($user_id > 0) {
                    $map['tab_user.id'] = $user_id;
                }
                $account = $this->request->param('account', '');
                if ($account != '') {
                    $map['account'] = ['like', '%' . $account . '%'];
                }
                // user_stage_id
                $user_stage_id = $this->request->param('user_stage_id', 0);
                if(!empty($user_stage_id) && $user_stage_id != 0){
                    $map['user_stage_id'] = $user_stage_id;
                }
                //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
                $view_game_ids = get_admin_view_game_ids(session('ADMIN_ID'));
                if(!empty($view_game_ids)){
                    $map['tab_user.fgame_id'] = ['IN',$view_game_ids];
                }
                //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
                $promote_id = $this->request->param('promote_id', '');
                if ($promote_id != '') {
                    $map['promote_id'] = $promote_id;
                }
                $user_score = $this->request->param('user_score', '');
                if(!empty($user_score) || $user_score === '0'){
                    $map['user_score'] = $user_score;
                }

                $exend['order'] = 'user_score desc,id desc';
                $exend['field'] = 'tab_user.id,account,promote_id,promote_account,lock_status,register_time,user_score,user_stage_id,user_stage_remark,us.name as user_stage_name';
                // $exend['group'] = 'id'; // cmf_get_image_url($user['head_img']);
                $exend['join1'][] = ['tab_user_stage'=>'us'];
                $exend['join1'][] = ['tab_user.user_stage_id=us.id'];
                // $exend['join1'][] = ['tab_user_stage'=>'us'];
                // ,'tab_user.user_stage_id=us.id','left'];
                $xlsData = $base->data_list_join_select($user, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['register_time'] = date('Y-m-d H:i:s', $value['register_time']);
                }
                write_action_log("导出玩家阶段列表");
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

    // 备份
    function expUser__bak()
    {
        $id = $this->request->param('id', 0, 'intval');
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();
        switch ($id) {
            case 1://用户列表导出
                $xlsCell = array(
                    array('id', '账号ID'),
                    array('account', "账号"),
                    array('cumulative', "累计消费"),
                    array('count', "付费记录"),
                    array('last_pay_time', "上次消费"),
                    array('count_day', "间隔天数"),
                    array('promote_account', '所属渠道'),
                    array('balance', "平台币余额"),
                    array('register_type', '注册方式'),
                    array('vip_level', "VIP等级"),
                    array('register_time', '注册时间'),
                    array('login_time', "最后登录时间"),
                    array('login_ip', '最后登录IP'),
                    array('lock_status', '账号状态'),
                );
                $user_id = $param['user_id'];
                if ($user_id > 0) {
                    $map['id'] = $user_id;
                }
                $account = $param['account'];
                if ($account != '') {
                    $map['account'] = ['like', '%' . $account . '%'];
                }
                $user_status = $param['user_status'];
                if ($user_status != '') {
                    $map['lock_status'] = $user_status;
                }
                $start_time = $param['start_time'];
                $end_time = $param['end_time'];
                if ($start_time && $end_time) {
                    $map['register_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['register_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['register_time'] = ['egt', strtotime($start_time)];
                }
                $register_type = $param['register_type'];
                if ($register_type != '') {
                    $map['register_type'] = $register_type;
                }
                $promote_id = $param['promote_id'];
                if ($promote_id != '') {
                    $map['promote_id'] = $promote_id;
                }
                $viplevel = $param['viplevel'];
                if ($viplevel != '') {
                    $map['vip_level'] = $viplevel;
                }
                $map['puid'] = 0;//只显示大号
                $order = 'id desc';
                //数据
                if (AUTH_PAY == 1) {
                    $xlsData = Db::table('tab_user')->alias('u')->field('u.id,count(s.user_id) as count,max(s.pay_time) as last_pay_time,account,balance,u.promote_account,register_time,lock_status,register_way,register_type,register_ip,login_time,login_ip,cumulative,vip_level')
                        ->join(['tab_spend' => 's'], 'u.id=s.user_id and s.pay_status=1', 'left')
                        ->where($map)
                        ->order($order)
                        ->group('u.id')
                        ->select();
                } else {
                    $xlsData = Db::table('tab_user')->alias('u')->field('u.id,account,balance,u.promote_account,register_time,lock_status,register_way,register_type,register_ip,login_time,login_ip,cumulative,vip_level')
                        ->where($map)
                        ->order($order)
                        ->group('u.id')
                        ->select();
                }

                $tmp_start_time = '';
                if ($xlsData) {
                    $xlsData = $xlsData->toArray();
                    foreach ($xlsData as $key => $vo) {
                        $xlsData[$key]['register_type'] = get_user_register_type($vo['register_type']);
                        $xlsData[$key]['register_time'] = date('Y-m-d H:i:s', $vo['register_time']);
                        $xlsData[$key]['login_time'] = date('Y-m-d H:i:s', $vo['login_time']);
                        $xlsData[$key]['lock_status'] = get_info_status($vo['lock_status'], 20);
                        if (AUTH_PAY == 1) {
                            $xlsData[$key]['count_day'] = $vo['last_pay_time'] ? (int)((time() - $vo['last_pay_time']) / 86400) : '';
                            $xlsData[$key]['last_pay_time'] = $vo['last_pay_time'] ? date('Y-m-d H:i:s', $vo['last_pay_time']) : '';
                        }
                        $tmp_start_time = $xlsData[$key]['register_time'];
                    }
                }
                $tmp_end_time = $xlsData[0]['register_time'] ?? '';
                $xlsName_2 = $xlsName.'_'.$tmp_start_time.'到'.$tmp_end_time;
                $xlsName = $xlsName_2;
                break;
            case 2://用户消费记录表
                if (AUTH_PAY == 1) {
                    $xlsCell = array(
                        array('pay_order_number', '订单号'),
                        array('game_name', "游戏名称"),
                        array('server_name', "游戏区服"),
                        array('game_player_name', "角色名"),
                        array('pay_way', "消费方式"),
                        array('pay_amount', "消费金额"),
                        array('pay_time', '消费时间'),
                        array('spend_ip', "消费IP"),
                    );
                    $map['pay_status'] = 1;
                    $map['user_id'] = $param['user_id'];
                    $xlsData = Db::table('tab_spend')->field('pay_order_number,game_name,server_name,game_player_name,pay_way,pay_amount,pay_time,spend_ip')->where($map)->select()
                        ->each(function ($item, $key) {
                            $item['pay_way'] = get_info_status($item['pay_way'], 1);
                            $item['pay_time'] = date('Y-m-d H:i:s', $item['pay_time']);
                            return $item;
                        });
                    //今日充值
                    $todaytotal = Db::table('tab_spend')->where($map)->where('pay_time', 'BETWEEN', total(1, 2))->sum('pay_amount');
                    //昨日充值
                    $yestodaytotal = Db::table('tab_spend')->where($map)->where('pay_time', 'BETWEEN', total(5, 2))->sum('pay_amount');
                    //总充值
                    $total = Db::table('tab_spend')->where($map)->sum('pay_amount');
                    $xlsData[] = ['pay_order_number' => '汇总', 'game_name' => '今日充值：', 'server_name' => $todaytotal, 'game_player_name' => '昨日充值：', 'pay_way' => $yestodaytotal, 'pay_amount' => '总充值：', 'pay_time' => $total];
                }
                break;
            case 3:
                $xlsCell = array(
                    array('game_name', "游戏名称"),
                    array('server_name', "游戏区服"),
                    array('game_player_name', "角色名"),
                    array('login_time', '登录时间'),
                    array('login_ip', "登录IP"),
                );
                $map['login_time'] = ['gt', 0];
                $map['user_id'] = $param['user_id'];
                $mUserLoginRecord = new UserLoginRecordModel();
                $xlsData = $mUserLoginRecord->field('game_name,server_name,game_player_name,login_time,login_ip')->where($map)->select()
                    ->each(function ($item, $key) {
                        $item['login_time'] = date('Y-m-d H:i:s', $item['login_time']);
                        return $item;
                    });
                break;
            case 4:
                $xlsCell = array(
                    array('user_account', "玩家账号"),
                    array('promote_account', "补链前渠道"),
                    array('promote_account_to', "补链后渠道"),
                    array('op_account', '操作人员'),
                    array('create_time', "操作时间"),
                    array('remark', "备注"),
                );
                $account = $param['account'];
                if ($account) {
                    $map['user_account'] = ['like', '%' . $account . '%'];
                }
                $xlsData = Db::table('tab_user_mend')->field('user_account,promote_account,promote_account_to,op_account,create_time,remark')->where($map)->select()->each(function ($item, $key) {
                    $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                    return $item;
                });
                break;
            case 5://账户修改记录
                $xlsCell = array(
                    array('user_account', "玩家账号"),
                    array('game_name', "游戏名称"),
                    array('type', "货币类型"),
                    array('prev_amount', "修改前数量"),
                    array('amount', "修改后数量"),
                    array('create_time', "修改时间"),
                    array('op_account', '操作人员'),
                );
                $model = new UserBalanceEditModel;
                $base = new BaseController;
                $account = $this->request->param('user_account', '');
                if ($account != '') {
                    $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
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
                $type = $this->request->param('type', '');
                if ($type != '') {
                    $map['type'] = $type;
                }

                $op_id = $this->request->param('admin_id', '');
                if ($op_id != '') {
                    $map['op_id'] = $op_id;
                }
                $exend['order'] = 'create_time desc';
                $xlsData = $base->data_list_select($model, $map, $exend);
                foreach ($xlsData as $key => $value) {
                    $xlsData[$key]['game_name'] = $value['game_name'] ?: '--';
                    $xlsData[$key]['type'] = $value['type'] == 0 ? '平台币' : '绑币';
                    $xlsData[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
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
        $this->exportExcel($xlsName, $xlsCell, $xlsData);
    }

}
