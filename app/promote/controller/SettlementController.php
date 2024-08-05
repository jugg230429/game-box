<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\promote\controller;

use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use app\promote\model\PromoteModel;
use app\promote\model\PromotesettlementModel;
use app\promote\model\PromotewithdrawModel;
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
        $listdata = $model->field('id,parent_id,top_promote_id,parent_name,sum(sum_money) as money')->where($map)->group('top_promote_id')->select()->toArray();

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
    // 生成结算单----------------------  START
    // 进行计算符合条件的结算记录, 并生成归档记录
    private function do_period_record($promote_id=0,$needSettlementIds=[]){
        // 按照指定天数结算
        $promote_account = get_promote_name($promote_id);
        $d_time = time();
        // 计算一级渠道和所有的子渠道的 top_promote_id
        $settlementInfo = Db::table('tab_promote_settlement')
            ->where(['top_promote_id'=>$promote_id,'status'=>0,'ti_status'=>0,'is_check'=>1])
            ->where(['id'=>['in', $needSettlementIds]])
            ->select();
        $tmpIds = []; // 将需要更新的数据的id先存起来
        $t_pay_amount = 0.00; // 总实付金额
        $t_sum_money = 0.00; // 渠道得到的总的佣金
        $tmp_create_time = $d_time; // 仅用作记录最早的一条记录 用[0]
        if(empty($settlementInfo->toArray())){
            // echo '没有符合计算的记录!';
            return ['code'=>-1,'msg'=>'没有符合计算的记录!'];
        }
        $tmp_create_time = $d_time;
        $endTimeInt = 0;

        $total_cps = 0;
        $total_cpa = 0;
        $register_user_num = 0;
        $plateform_earn = 0;

        foreach($settlementInfo as $k3=>$v3){
            $tmpIds[] = $v3['id'];
            $t_pay_amount += $v3['pay_amount'];
            $t_sum_money += $v3['sum_money'];
            if($v3['create_time'] < $tmp_create_time){
                $tmp_create_time = $v3['create_time'];
            }
            if($v3['create_time'] > $endTimeInt){
                $endTimeInt = $v3['create_time'];
            }

            // 按照cps分成汇总
            if($v3['pattern'] == 0){  // cps
                $total_cps += $v3['sum_money'];
                // $plateform_earn += $v3['pay_amount']*(1-$v3['ratio']);
                $plateform_earn += ($v3['pay_amount'] - $v3['sum_money']);
            }
            // 按照cpa分成汇总
            if($v3['pattern'] == 1){  // cpa
                $total_cpa += $v3['sum_money'];
                $register_user_num ++;
            }

        }
        // 计算时间段
        $startTime = date('Y-m-d',$tmp_create_time);
        $endTimeString = date('Y-m-d',$endTimeInt);
        // 下个结算的时间点
        // 插入周期结算统计记录
        $operate1 = $period_id = Db::table('tab_promote_settlement_period')
            ->insertGetId([
                'period'=>$startTime.'至'.$endTimeString,
                'promote_id'=>$promote_id,
                'promote_account'=>$promote_account,
                'order_num'=>$this->create_order(),
                'total_money'=>$t_pay_amount,  // 总实付金额
                'promoter_earn'=>$t_sum_money,  // 渠道得到的总佣金
                'plateform_earn'=>$plateform_earn,
                'is_remit'=>0,
                'create_time'=>$d_time,
                'update_time'=>0, // 更新时间 默认为打款时间,打款操作的时候进行更新
                'period_start'=>$tmp_create_time, // 结算开始时间
                'period_end'=>$endTimeInt, // 结算结束时间
                'total_cps'=>$total_cps,
                'total_cpa'=>$total_cpa,
                'register_user_num'=>$register_user_num
            ]);
        $operate2 = Db::table('tab_promote_settlement')
            ->where(['id'=>['in',$tmpIds]])
            ->update(['status'=>1,'period_id'=>$period_id,'update_time'=>$d_time]);
        if($operate1 && $operate2){
            return ['code'=>1,'msg'=>'修改成功!'];
        }
        return ['code'=>-1,'msg'=>'修改失败'];


    }

    // 生成订单号
    private function create_order(){
        $a = uniqid(mt_rand());
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $length = strlen($str) - 1; //获取字符串的长度
        //2.字符串截取bai开始位置
        $start=rand(0,$length-6);
        //3.字符串截取长度 6 位
        $count = 6; // 取六位
        $use_str = str_shuffle($str); // 随机打乱字符串
        $b = substr($use_str, $start, $count); // 截取字符串，取其中的一部分字符串
        $final_name = 'JS-'.date('Ymd').$a.$b;
        return $final_name;
    }

    // 生成结算单----------------------  END

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
        $total_cps = 0;
        $total_cpa = 0;
        $total_register_user_num = 0;

        foreach($lists as $k=>$v){
            $total_promoter_earn += $v['promoter_earn'];
            $total_plateform_earn += $v['plateform_earn'];
            $total_earn += $v['total_money'];
            $total_remit += $v['remit_amount'];
            $total_cps += $v['total_cps'];
            $total_cpa += $v['total_cpa'];
            $total_register_user_num += $v['register_user_num'];

        }
        $total = [
            'total_promoter_earn'=>sprintf("%.2f",$total_promoter_earn),
            'total_plateform_earn'=>sprintf("%.2f",$total_plateform_earn),
            'total_earn'=>sprintf("%.2f",$total_earn),
            'total_remit'=>sprintf("%.2f",$total_remit),
            'total_cps'=>sprintf("%.2f",$total_cps),
            'total_cpa'=>sprintf("%.2f",$total_cpa),
            'total_register_user_num'=>$total_register_user_num
        ];
        $this->assign('total', $total);
        $page = $lists->render();
        $this->assign("data_lists", $lists);
        $this->assign("page", $page);
        return $this->fetch();
    }
    // 操作打款(先渲染页面, post提交之后进行保存操作)
    public function do_remit(Request $request){
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

    // 查看某个周期结算单 的详情
    public function show_detail(Request $request){
        // var_dump('查看某个周期结算单 的详情');exit;
        $param = $request->param();
        // var_dump($param);
        // exit;
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $period_id = (int) ($param['period_id'] ?? 0);
        if($period_id <= 0){
            return $this->error('缺少必要的参数');
        }
        $periodInfo = Db::table('tab_promote_settlement_period')->where(['id'=>$period_id])->find();
        $this->assign('period_info',$periodInfo);

        $lists = Db::table('tab_promote_settlement')
            ->where(['period_id'=>$period_id])
            ->paginate($row, false, ['query' => $param])
            ->each(function($item, $key){
                $item['plateform_ratio'] = 100-$item['ratio'];
                // plateform_sum_money
                if($item['pattern'] == 0){
                    $item['plateform_sum_money'] = sprintf("%.2f", $item['pay_amount'] - $item['sum_money']);
                }else{
                    $item['plateform_sum_money'] = sprintf("%.2f", 0.00);
                }
                $item['pay_amount'] = sprintf("%.2f",$item['pay_amount']);
                $item['sum_money'] = sprintf("%.2f",$item['sum_money']);
                return $item;
            });
        // var_dump($lists);exit;
        // 汇总
        $lists_total = Db::table('tab_promote_settlement')
            ->where(['period_id'=>$period_id])
            ->select()->toArray();
        $total_pay_amount = 0;
        $total_sum_money = 0;
        $total_plateform_sum_money = 0;
        foreach($lists_total as $k=>$v){
            $total_pay_amount += $v['pay_amount'];
            $total_sum_money += $v['sum_money'];
            if($v['pattern'] == 0){
                $total_plateform_sum_money += sprintf("%.2f", $v['pay_amount'] - $v['sum_money']);
            }
        }
        $total = [
            'pay_amount'=>sprintf("%.2f",$total_pay_amount),
            'sum_money'=>sprintf("%.2f",$total_sum_money),
            'plateform_sum_money'=>sprintf("%.2f",$total_plateform_sum_money)
        ];
        // return json($lists);
        $this->assign('total', $total);
        $this->assign('period_id', $period_id);
        $page = $lists->render();
        $this->assign("data_lists", $lists);
        $this->assign("page", $page);
        return $this->fetch();
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
            array('register_user_num', "新增注册用户"),
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
            psp.remit_amount,psp.accounts_of_receive,psp.name_of_receive,psp.type_of_receive,psp.operator,psp.remark,psp.total_money,psp.register_user_num')
            ->select();
        $xlsData = $lists;

        if ($xlsData) {
            $xlsData = $xlsData->toArray();

            $total_promoter_earn = 0; // 渠道分成总金额
            $total_plateform_earn = 0; // 甲方(平台)分成总金额
            $total_earn = 0; // 游戏实付金额
            $total_remit = 0; // 总打款金额
            $total_register_user_num = 0;

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
                    // $xlsData[$key]['period'] = '--';
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
                $total_register_user_num += $vo['register_user_num'];

            }
        }
        //如果为空则返回
        if(empty($xlsData)){
            // return;
            $this->error('没有数据!');
        }
        // return json($xlsData);
        // var_dump($xlsData);exit;
        write_action_log("导出渠道周期结算表");


        // 拼接最后一行(汇总)
        $tmp_append_arr = [
            'period' => "汇总",
            'promote_account' => '--',
            'total_money' => '--',
            'register_user_num'=>$total_register_user_num,
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

    // 导出某个周期下面的结算单
    public function exp_period_settlement_detail(){
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();
        $xlsCell = array(
            array('game_name', '游戏名称'),
            array('pay_amount', "累计实付金额"),
            array('money', "CPA注册单价"),
            array('register_user_num', "新增注册用户"),
            array('plateform_ratio', "甲方分成比例"),
            array('ratio', "渠道分成比例"),
            array('pattern', "分成模式"),
            array('plateform_sum_money', "甲方分成金额(元)"),
            array('sum_money', "渠道分成金额(元)"),
        );

        // 拼接搜索条件

        //数据
        $period_id = (int) ($param['period_id'] ?? 0);
        if($period_id <= 0){
            return $this->error('缺少必要的参数');
        }
        $lists = Db::table('tab_promote_settlement')
            ->where(['period_id'=>$period_id])
            ->field('game_name,pay_amount,ratio,money,sum_money,pattern')
            ->select();
        // 汇总显示
        $total_pay_amount = 0;
        $total_sum_money = 0;
        $total_plateform_sum_money = 0;
        foreach($lists as $k=>$v){
            $total_pay_amount += $v['pay_amount'];
            $total_sum_money += $v['sum_money'];
        }

        $xlsData = $lists;

        if ($xlsData) {
            $xlsData = $xlsData->toArray();
            foreach ($xlsData as $key => $vo) {
                $xlsData[$key]['plateform_ratio'] = 100-$vo['ratio'];
                if($vo['pattern'] == 0){
                    $xlsData[$key]['plateform_sum_money'] = sprintf("%.2f", $vo['pay_amount'] - $vo['sum_money']);
                }else{
                    $xlsData[$key]['plateform_sum_money'] = sprintf("%.2f", 0.00);
                }
                
                if($vo['pattern'] == 0){
                    $xlsData[$key]['pattern'] = 'CPS';
                    $xlsData[$key]['register_user_num'] = '--';
                }
                if($vo['pattern'] == 1){
                    $xlsData[$key]['pattern'] = 'CPA';
                    $xlsData[$key]['register_user_num'] = 1;
                }
                // $xlsData[$key]['plateform_sum_money'] = sprintf("%.2f", ($vo['pay_amount'] - $vo['sum_money']));

                $xlsData[$key]['sum_money'] = sprintf("%.2f", $vo['sum_money']);
                $total_plateform_sum_money += $xlsData[$key]['plateform_sum_money'];
            }
        }
        //如果为空则返回
        if(empty($xlsData)){
            // return;
            $this->error('没有数据!');
        }
        write_action_log("导出周期结算单");

        // 拼接最后一行(汇总)
        $tmp_append_arr = [
            'game_name'=>"汇总",
            'pay_amount'=>sprintf("%.2f", $total_pay_amount),
            'money'=>'--',
            'register_user_num'=>'--',
            'plateform_ratio'=>'--',
            'ratio'=>'--',
            'pattern'=>'--',
            'plateform_sum_money'=>sprintf("%.2f", $total_plateform_sum_money),
            'sum_money'=>sprintf("%.2f", $total_sum_money),
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
