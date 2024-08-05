<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\promote\model\PromoteModel;
use app\promote\model\PromotesettlementModel;
use app\recharge\model\PromoteBindRecordModel;
use app\recharge\model\SpendModel;
use app\common\model\DateListModel;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use app\api\GameApi;
use think\Request;
use think\Db;

//该控制器必须以下3个权限
class SpendController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_USER != 1) {
            $this->error('请购买用户权限', url('admin/main/index'));
        }
        if (AUTH_PAY != 1) {
            $this->error('请购买充值权限', url('admin/main/index'));
        }
        if (AUTH_GAME != 1) {
            $this->error('请购买游戏权限', url('admin/main/index'));
        }
    }

//    public function lists()
//    {
//        $spend = new SpendModel;
//        $base = new BaseController;
//        $account = $this->request->param('user_account', '');
//        if ($account != '') {
//            $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
//        }
//        $promote_account = $this->request->param('promote_account', '');
//        //优先级 渠道-上线渠道
//        if ($promote_account != '') {
//            if($promote_account == 'is_gf'){
//                $map['promote_id'] = 0;
//            }else{
//                $map['promote_account'] = ['eq', $promote_account];
//            }
//        }else{
//            $top_promote = $this->request->param('top_promote', '');
//            if ($top_promote != ''&&$top_promote!='is_gf') {
//                $pmap['id|parent_id|top_promote_id'] = $top_promote;
//                $promote_ids = get_promote_list($pmap);
//                $map['promote_account'] = ['in',array_column($promote_ids,'account')];
//            }elseif($top_promote=='is_gf'){
//                $map['promote_id'] = 0;
//            }
//        }
//        $pay_order_number = $this->request->param('pay_order_number', '');
//        if ($pay_order_number != '') {
//            $map['pay_order_number'] = ['like', "%" . addcslashes($pay_order_number, '%') . '%'];
//        }
//        $cpextend = $this->request->param('extend', '');
//        if ($cpextend != '') {
//            $map['extend'] = ['like', "%" . addcslashes($cpextend, '%') . '%'];
//        }
//        $spend_ip = $this->request->param('spend_ip', '');
//        if ($spend_ip != '') {
//            $map['spend_ip'] = ['like', '%' . addcslashes($spend_ip, '%') . '%'];
//        }
//        $start_time = $this->request->param('start_time', '');
//        $end_time = $this->request->param('end_time', '');
//        if ($start_time && $end_time) {
//            $map['pay_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
//        } elseif ($end_time) {
//            $map['pay_time'] = ['lt', strtotime($end_time) + 86400];
//        } elseif ($start_time) {
//            $map['pay_time'] = ['egt', strtotime($start_time)];
//        }
//
//        $game_id = $this->request->param('game_id', 0, 'intval');
//        if ($game_id > 0) {
//            $map['game_id'] = $game_id;
//        }
//
//        $server_id = $this->request->param('server_id', '');
//        if ($server_id != '') {
//            $map['server_name'] = $server_id;
//        }
//        $pay_way = $this->request->param('pay_way', '');
//        if ($pay_way != '') {
//            $map['pay_way'] = $pay_way;
//        }
//
//        $pay_status = $this->request->param('pay_status', '');
//        if ($pay_status != '') {
//            $map['pay_status'] = $pay_status;
//        }
//        $pay_game_status = $this->request->param('pay_game_status', '');
//        if ($pay_game_status != '') {
//            $map['pay_game_status'] = $pay_game_status;
//        }
//        // 根据CP查询订单
//        $cp_id = $this->request->param('cp_id',0,'intval');
//        if(!empty($cp_id)){
//            $map['game_id'] = ['in',get_cp_game_ids($cp_id)];
//        }
//        $sort_type = $this->request->param('sort_type', '');
//        $sort = $this->request->param('sort', 1, 'intval');
//        //排序
//        if ($sort == 1) {
//            $exend['order'] = 'pay_time desc';
//        } elseif ($sort == 2) {
//            $exend['order'] = "$sort_type desc";
//        } else {
//            $exend['order'] = "$sort_type asc";
//        }
//        $exend['field'] = 'id,user_id,user_account,game_id,game_name,promote_id,promote_account,pay_time,pay_status,pay_order_number,extend,pay_amount,pay_game_status,pay_way,spend_ip,server_id,server_name,game_player_id,game_player_name,cost,is_check,role_level,discount_type,discount,coupon_record_id,currency_code,us_cost,currency_cost,area';
//        $data = $base->data_list($spend, $map, $exend)->each(function ($item, $key) {
//            $item['is_check_name'] = $item['is_check'] == 1 ? "参与" : "不参与";
//            return $item;
//        });
//        $exend['field'] = 'sum(pay_amount) as total,sum(us_cost) as us_total';
//        //累计充值
//        $map['pay_status'] = 1;
//        $payusermap = $map;
//        $total = $base->data_list_select($spend, $map, $exend);
//       //今日充值
//        $map['pay_time'] = ['between', total(1, 2)];
//        $today = $base->data_list_select($spend, $map, $exend);
//        //昨日充值
//        $map['pay_time'] = ['between', total(5, 2)];
//        $yestoday = $base->data_list_select($spend, $map, $exend);
//        //累计充值人数
//        $exend['field'] = 'user_id,count(user_id) as group_num';
//        $exend['group'] = 'user_id';
//        $totaluser = $base->data_list_select($spend, $payusermap, $exend);
//
//        // 是否海外支付
//        $pay_oversea = 0;
//        if (empty($pay_way)) {
//            $count = $spend->where('area', '=', 1)->count();
//            if ($count>0) {
//                $pay_oversea = 1;
//            }
//        } elseif ($pay_way == 6) {
//            $pay_oversea = 1;
//        }
//
//        // 获取分页显示
//        $page = $data->render();
//        $this->assign("pay_oversea", $pay_oversea);
//        $this->assign("data_lists", $data);
//        $this->assign("page", $page);
//        $this->assign("total", $total[0]);//累计充值
//        $this->assign("totaluser", count(array_column($totaluser,'user_id')));//累计充值
//        $this->assign("today", $today[0]);//今日充值
//        $this->assign("yestoday", $yestoday[0]);//累计充值
//        return $this->fetch();
//    }

    public function lists()
    {
        $spend = new SpendModel;
        $base = new BaseController;
        $account = $this->request->param('user_account', '');
        if ($account != '') {
            $map['tab_spend.user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        $promote_account = $this->request->param('promote_account', '');
        //优先级 渠道-上线渠道
        if ($promote_account != '') {
            if($promote_account == 'is_gf'){
                $map['tab_spend.promote_id'] = 0;
            }else{
                $map['tab_spend.promote_account'] = ['eq', $promote_account];
            }
        }else{
            $top_promote = $this->request->param('top_promote', '');
            if ($top_promote != ''&&$top_promote!='is_gf') {
                $pmap['id|parent_id|top_promote_id'] = $top_promote;
                $promote_ids = get_promote_list($pmap);
                $map['tab_spend.promote_account'] = ['in',array_column($promote_ids,'account')];
            }elseif($top_promote=='is_gf'){
                $map['tab_spend.promote_id'] = 0;
            }
        }
        $pay_order_number = $this->request->param('pay_order_number', '');
        if ($pay_order_number != '') {
            $map['tab_spend.pay_order_number'] = ['like', "%" . addcslashes($pay_order_number, '%') . '%'];
        }
        $cpextend = $this->request->param('extend', '');
        if ($cpextend != '') {
            $map['tab_spend.extend'] = ['like', "%" . addcslashes($cpextend, '%') . '%'];
        }
        $spend_ip = $this->request->param('spend_ip', '');
        if ($spend_ip != '') {
            $map['tab_spend.spend_ip'] = ['like', '%' . addcslashes($spend_ip, '%') . '%'];
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['tab_spend.pay_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['tab_spend.pay_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['tab_spend.pay_time'] = ['egt', strtotime($start_time)];
        }

        $game_id = $this->request->param('game_id', 0, 'intval');

        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $game_id = get_admin_view_game_ids(session('ADMIN_ID'),$game_id);
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end

        $server_name = $this->request->param('server_name', '');
        if ($server_name != '') {
            $map['tab_spend.server_name'] = ['like', '%' . addcslashes($server_name, '%') . '%'];
        }
        $pay_way = $this->request->param('pay_way', '');
        if ($pay_way != '') {
            $map['tab_spend.pay_way'] = $pay_way;
        }

        $pay_status = $this->request->param('pay_status', '');
        if ($pay_status != '') {
            $map['tab_spend.pay_status'] = $pay_status;
        }
        $pay_game_status = $this->request->param('pay_game_status', '');
        if ($pay_game_status != '') {
            $map['tab_spend.pay_game_status'] = $pay_game_status;
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
        if ($game_id > 0) {
            $map['tab_spend.game_id'] = ['IN',$game_id];
        }

        $sort_type = $this->request->param('sort_type', '');
        $sort = $this->request->param('sort', 1, 'intval');
        //排序
        if ($sort == 1) {
            $exend['order'] = 'tab_spend.pay_time desc';
        } elseif ($sort == 2) {
            $exend['order'] = "tab_spend.$sort_type desc";
        } else {
            $exend['order'] = "tab_spend.$sort_type asc";
        }
        $promoteSettlement = new PromotesettlementModel();
        $exend['field'] = 'tab_spend.id,tab_spend.user_id,tab_spend.user_account,tab_spend.game_id,tab_spend.game_name,tab_spend.promote_id,tab_spend.promote_account,tab_spend.pay_time,tab_spend.pay_status,tab_spend.pay_order_number,tab_spend.extend,tab_spend.pay_amount,tab_spend.pay_game_status,tab_spend.pay_way,tab_spend.spend_ip,tab_spend.server_id,tab_spend.server_name,tab_spend.game_player_id,tab_spend.game_player_name,tab_spend.cost,tab_spend.is_check,tab_spend.role_level,tab_spend.discount_type,tab_spend.discount,tab_spend.coupon_record_id,tab_spend.currency_code,tab_spend.us_cost,tab_spend.currency_cost,tab_spend.area,tab_spend.goods_reserve';
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        $data = $base -> data_list_join($spend, $map, $exend) -> each(function($item, $key) use ($promoteSettlement, $ys_show_admin){
            $item['is_check_name'] = $item['is_check'] == 1 ? "参与" : "不参与";
            $status = $promoteSettlement -> where(['pay_order_number' => $item['pay_order_number']]) -> value('status');
            $item['status'] = $status ? $status : 0;

            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $item['user_account'] = get_ys_string($item['user_account'],$ys_show_admin['account_show_admin']);
            }
            if($ys_show_admin['role_show_admin_status'] == 1){//开启了角色查看隐私
                $item['game_player_name'] = get_ys_string($item['game_player_name'],$ys_show_admin['role_show_admin']);
            }
            return $item;
        });
        $exend['field'] = 'sum(tab_spend.pay_amount) as total,sum(tab_spend.us_cost) as us_total,sum(tab_spend.cost) as all_cost';
        //累计充值
        $map['tab_spend.pay_status'] = 1;
        $payusermap = $map;
        $total = $base->data_list_select($spend, $map, $exend);
        // 累计订单金额
        $order_total_money = $base->data_list_select($spend, $map, $exend);

        //今日充值
        $map['tab_spend.pay_time'] = ['between', total(1, 2)];
        $today = $base->data_list_select($spend, $map, $exend);
        //昨日充值
        $map['tab_spend.pay_time'] = ['between', total(5, 2)];
        $yestoday = $base->data_list_select($spend, $map, $exend);
        //累计充值人数
        $exend['field'] = 'tab_spend.user_id,count(tab_spend.user_id) as group_num';
        $exend['group'] = 'tab_spend.user_id';
        $totaluser = $base->data_list_select($spend, $payusermap, $exend);

        // 是否海外支付
        $pay_oversea = 0;
        if (empty($pay_way)) {
            $count = $spend->where('area', '=', 1)->count();
            if ($count>0) {
                $pay_oversea = 1;
            }
        } elseif ($pay_way == 6) {
            $pay_oversea = 1;
        }

        // 获取分页显示
        $page = $data->render();
        $this->assign("pay_oversea", $pay_oversea);
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        $this->assign("total", $total[0]);//累计充值
        $this->assign("order_total_money", $order_total_money[0]);//累计订单金额
        $this->assign("totaluser", count(array_column($totaluser,'user_id')));//累计充值
        $this->assign("today", $today[0]);//今日充值
        $this->assign("yestoday", $yestoday[0]);//累计充值
        return $this->fetch();
    }

    /**
     * [补单]
     * @author 郭家屯[gjt]
     */
    public function repair()
    {
        $orderno = $this->request->param('orderno');
        $model = new SpendModel();
        $order = $model->where('pay_order_number', $orderno)->find();
        if (empty($order)) {
            $this->error('订单不存在');
        }
        $order = $order->toArray();
        // 第三方平台订单补单
        if($order['platform_id']>0){
            if(empty($order['extend'])){
                $this->error('CP订单不存在');
            }
            $UserLogic = new \app\thirdgame\logic\ThirdGameApiLogic($order['platform_id']);
            $res = $UserLogic->updatePayStatus($order['extend']);
            if($res){
                $model->where('pay_order_number', $orderno)->update(['pay_game_status'=>1]);
                $this->success('处理成功');
            }else{
                $this->error('处理失败');
            }
        }
        $user_entity = get_user_entity($order["user_id"],false,'id,account,promote_id,promote_account,parent_name,parent_id,cumulative');
        $game = new GameApi();
        if ($order['pay_status'] == 2) {
            Db::startTrans();
            try {
                //修改订单状态
                $model->where('pay_order_number', $orderno)->where('pay_way', 6)->setField('pay_status', 1);
                //更新VIP等级和充值总金额
                set_vip_level($order["user_id"], $order['pay_amount'],$user_entity['cumulative']);
                //添加结算订单
                if ($order['promote_id']) {
                    $promote = get_promote_entity($order['promote_id'],'pattern');
                    if ($promote['pattern'] == 0) {
                        set_promote_radio($order,$user_entity);
                    }
                }
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error('处理失败');
            }
        }
        $order['out_trade_no'] = $order['pay_order_number'];
        $res = $game->game_pay_notify($order);
        write_action_log('订单号【'.$orderno.'】补单');
        if($res===false){
            $this->error('处理失败');
        }else{
            $this->success('处理成功');
        }
    }


    /**
     * @获取回调信息
     *
     * @author: zsl
     * @since: 2021/2/4 19:32
     */
    public function getNotifyInfo()
    {
        $result = ['code' => 1, 'msg' => '获取成功', 'data' => []];
        $pay_order_number = $this -> request -> post('pay_order_number');
        $mSpend = new SpendModel();
        $where = [];
        $where['pay_order_number'] = $pay_order_number;
        $game_notify_info = $mSpend -> where($where) -> value('game_notify_info');
        $result['data'] = ['game_notify_info' => $game_notify_info];
        return $result;
    }

    public function bind()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        $promoteId = $data['promote_id'];
        static $err = 0;
        static $ok = 0;
        if (!$ids) $this->error('请选择你要操作的数据');
        if ($promoteId == '') $this->error('请选择渠道');
        $map['tab_spend.id'] = ['in', $ids];
        $base = new BaseController;
        $spend = new SpendModel();
        $exend['field'] = 'tab_spend.id as spend_id, ps.id as ps_id,tab_spend.promote_id,tab_spend.promote_account,tab_spend.pay_order_number,ps.status as ps_status,tab_spend.pay_status';
        $exend['join1'][] = ['tab_promote_settlement' => 'ps'];
        $exend['join1'][] = 'ps.pay_order_number = tab_spend.pay_order_number and ps.status=0';
        $exend['join1'][] = 'left';
        $data = $base->data_list_join_select($spend, $map, $exend);
        if (!empty($data)) {

            foreach ($data as $key => $value) {
                $result = $this->deal_promote_info($promoteId,$value);
                if($result){
                    $ok++;
                }else{
                    $err++;
                }
            }

            if ($err) {
                $this->error('绑定成功' . $ok . '笔，失败' . $err . '笔');
            }
            $this->success('绑定成功' . $ok . '笔，失败' . $err . '笔');
        } else {
            $this->error('数据有误');
        }
    }

    /**
     *  * 处理方法bind()中部分条件-开启事务处理逻辑
     *  * by:20210429-byh
     * @param $promoteId
     * @param $promote_account
     * @param $value
     * @param $num
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deal_promote_info($promoteId,$value)
    {

        //判断订单表订单状态不是付款状态或者结算表是已结算状态的数据,不处理跳过
        if ($value['ps_status'] == 1 || $value['pay_status'] != 1) {
            return false;
        }
        if ($value['promote_id'] == $promoteId) { // 相同-不操作
            return true;
        }
        $num = 0;
        if ($value['promote_id'] > 0 && $promoteId > 0) {
            $num = 1;
        } elseif ($value['promote_id'] > 0 && $promoteId == 0) {//个人转给官方
            $num = 2;
        } elseif ($value['promote_id'] == 0 && $promoteId > 0) {//官方转给个人
            $num = 3;
        }

        $promote_account = get_promote_name($promoteId);
        $spend = new SpendModel();
        $promote = new PromoteModel();
        $psettlement = new PromotesettlementModel();
        Db::startTrans();
        switch ($num){
            case 1://个人转给个人
                //先修改订单的promote_id和promote_account
                $res = $spend->where('id',$value['spend_id'])->update(['promote_id'=>$promoteId,'promote_account'=>$promote_account]);
                //修改tab_promote_settlement的信息
                $res2 = $psettlement->where('pay_order_number',$value['pay_order_number'])->where('status',0)->value('id');
                $res3 = true;
                if($res2>0){//存在则修改
                    //需修改渠道ID,渠道账号,顶级渠道ID和账号,以及父级渠道ID
                    //查询此渠道信息
                    $promote_info = $promote->field('parent_id,parent_name,top_promote_id')->where('id',$promoteId)->find();
                    //父级ID为0,则顶级也肯定为0,此渠道为顶级渠道
                    $parent_id = $promoteId;
                    $parent_name = $promote_account;
                    $top_promote_id = $promoteId;
                    if($promote_info['parent_id'] > 0){//父级不为0,赋值父级信息
                        $parent_id = $promote_info['parent_id'];
                        $parent_name = $promote_info['parent_name'];
                        if ($promote_info['top_promote_id'] > 0){//顶级不为0,赋值顶级ID
                            $top_promote_id = $promote_info['top_promote_id'];
                        }
                    }
                    $data = [
                        'promote_id'=>$promoteId,
                        'promote_account'=>$promote_account,
                        'parent_id'=>$parent_id,//上级渠道
                        'parent_name'=>$parent_name,//上级推广员账号
                        'top_promote_id'=>$top_promote_id,//顶级渠道
                    ];
                    $res3 = $psettlement->where('id',$res2)->update($data);

                }
                break;
            case 2://个人转给官方
                //先修改订单的promote_id和promote_account
                $res = $spend->where('id',$value['spend_id'])->update(['promote_id'=>$promoteId,'promote_account'=>$promote_account]);
                //修改tab_promote_settlement的信息
                $res2 = $psettlement->where('pay_order_number',$value['pay_order_number'])->where('status',0)->value('id');
                $res3 = true;
                if($res2>0){//存在则删除
                    $res3 = $psettlement->where('id',$res2)->delete();
                }
                break;
            case 3://官方转给个人
                //先修改订单的promote_id和promote_account
                $res = $spend->where('id',$value['spend_id'])->update(['promote_id'=>$promoteId,'promote_account'=>$promote_account]);
                //tab_promote_settlement表新增数据
                //获取订单信息
                $spend_data = $spend->where('id',$value['spend_id'])->find()->toArray();
                $user_entity = get_user_entity($spend_data['user_id'],false,'id,account,nickname,balance,cumulative,parent_id,parent_name,invitation_id');
                $user_entity['promote_id'] = $promoteId;
                $user_entity['promote_account'] = $promote_account;

                $res3 = set_promote_radio($spend_data,$user_entity);//生成充值结算单
                break;
            default:
                $res = $res3 = false;
        }
        if($res && $res3){
            // 写入补单记录
            $this -> insert_promote_bind_update_record($value, $promoteId);
            // 提交事务
            Db::commit();
            return true;
        }
        //回滚
        Db::rollback();
        return false;

    }

    public function insert_promote_bind_update_record($data,$promoteId)
    {
        //操作成功添加修改绑定记录
        //根据原渠道id查询账号
        if($data['promote_id']==0){
            $old_promote_account = '官方渠道';
        }else{
            $old_promote_account = Db::table('tab_promote')->where('id',$data['promote_id'])->value('account');
        }
        //获取操作者
        $record = [
            'old_promote_id'=>$data['promote_id'],
            'old_promote_account'=>$old_promote_account,
            'promote_id'=>$promoteId,
            'promote_account'=>get_promote_name($promoteId),
            'pay_order_number'=>$data['pay_order_number'],
            'create_user_id'=>cmf_get_current_admin_id(),
            'create_user_account'=>cmf_get_current_admin_name(),
            'create_time'=>time(),
            'update_time'=>time(),
            'status'=>1,
        ];
        return Db::table('tab_promote_bind_update_record')->insertGetId($record);
    }


    public function get_promote_lists()
    {
        $this->success('','', get_promote_list(['status'=>1]));
    }


    /**
     * @订单绑定记录
     *
     * @author: zsl
     * @since: 2021/4/29 20:44
     */
    public function binglog()
    {
        $base = new BaseController();
        $model = new PromoteBindRecordModel();
        $map = [];
        $extend['order'] = 'id desc';
        $data = $base -> data_list($model, $map, $extend);
        // 获取分页显示
        $page = $data -> render();
        $this -> assign('data_lists', $data);
        $this -> assign("page", $page);
        return $this -> fetch();
    }

}
