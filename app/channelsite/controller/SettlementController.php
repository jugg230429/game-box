<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yyh
// +----------------------------------------------------------------------
namespace app\channelsite\controller;

use think\Db;
use app\promote\model\PromoteModel;
use app\common\controller\BaseHomeController;
use app\promote\model\PromotesettlementModel;
use app\promote\model\PromotewithdrawModel;
use think\Request;

class SettlementController extends BaseController
{
    protected function _initialize()
    {
        parent::_initialize();
        if (AUTH_GAME != 1 || AUTH_PAY != 1) {
            $this->error('请购买充值权限和游戏权限');
        }
    }

    /**
     * @函数或方法说明
     * @收益记录
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/8/6 15:46
     */
    public function profit()
    {
        $request = $this->request->param();
        $date = $request['rangepickdate'];
        if($date) {
            $dateexp = explode('至', $date);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            if(PID_LEVEL==1){
                $update_time = 'update_time';
            }elseif(PID_LEVEL==2){
                $update_time = 'update_time2';
            }else{
                $update_time = 'update_time3';
            }
            $map[$update_time] = ['between', [strtotime($starttime), strtotime($endtime)+86399]];
        }else{
            $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
            $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
        }
        if($request['pay_order_number']){
            $map['pay_order_number'] = ['like','%'.$request['pay_order_number'].'%'];
        }
        if($request['game_id']){
            $map['game_id'] = $request['game_id'];
        }
        if($request['promote_id']){
            if($request['promote_id']==PID){
                $map['promote_id'] = ['in',$request['promote_id']];
            }else{
                if(PID_LEVEL==1){
                    $this_promote_son = array_column(get_song_promote_lists($request['promote_id'],2),'id');
                    $this_promote_son[] = $request['promote_id'];
                }else{
                    $this_promote_son = $request['promote_id'];
                }
                $map['promote_id'] = ['in',$this_promote_son];
            }
        }
        if($request['user_account']){
            $map['user_account'] = ['like','%'.$request['user_account'].'%'];
        }
        if($request['user_id']){
            $map['user_id'] = ['like','%'.$request['user_id'].'%'];
        }
        if($request['pattern'] != ''){
            $map['pattern'] = $request['pattern'];
        }
        $model = new PromotesettlementModel();
        $base = new BaseHomeController;
        $exend['field'] = 'id,promote_id,parent_id,top_promote_id,promote_account,game_name,pattern,ratio,money,sum_money,ratio2,money2,sum_money2,ratio3,money3,sum_money3,user_id,user_account,is_check,parent_name,pay_order_number,pay_amount,cost,create_time,update_time,role_name,update_time2,update_time3';
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
        $list_data = $base->data_list($model,$map,$exend);
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($list_data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $list_data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
            //增加处理角色查看隐私情况
            if($ys_show_promote['role_show_promote_status'] == 1){//开启了角色查看隐私1
                $list_data[$k5]['role_name'] = get_ys_string($v5['role_name'],$ys_show_promote['role_show_promote']);
            }
        }
        // dump($model->getlastsql());exit;
        $page = $list_data->render();
        $this->assign("data_lists", $list_data);
        $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney,sum(sum_money2) as totalmoney2,sum(sum_money3) as totalmoney3')->where($map)->find();
        $this->assign('total', $total);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @兑换
     * @author: 郭家屯
     * @since: 2019/8/6 15:46
     */
    public function exchange(){
        $amount = $this->request->param('amount/d',0);
        if($amount <= 0){
            $this->error('兑换金额不能为空');
        }
        $password = $this->request->param('password/s','');
        if($password == ''){
            $this->error('兑换密码不能为空');
        }
        $promote =  get_promote_entity(PID,'id,second_pwd,account,parent_id,balance_coin,balance_profit,promote_level');
        if(empty($promote['second_pwd'])){
            $this->error('请设置二级密码');
        }
        if (!xigu_compare_password($password, $promote['second_pwd'])) {
            $this->error('兑换密码错误');
        }
        if($amount > $promote['balance_profit']){
            $this->error('收益金额不足');
        }
        $model = new PromotewithdrawModel();
        $result = $model->settlement($amount,2,$promote);
        if($result){
            $this->success('提交成功');
        }else{
            $this->error('提交失败');
        }
    }
    //驳回后重新申请
    public function reapply()
    {
        $request = $this->request->param();
        $id = $request['id'];
        if(empty($id)){
            $this->error('缺少参数');
        }
        $data = Db::table('tab_promote_withdraw')->field('sum_money,fee,promote_id,status')->where(['id'=>$id,'promote_id'=>PID,'status'=>2])->find();
        if(empty($data)){
            $this->error('数据错误');
        }else{
            $res = Db::table('tab_promote_withdraw')->where(['id'=>$id,'promote_id'=>PID,'status'=>2])->update(['status'=>0]);
            //增加功能-驳回时返还了提现金额,再申请时再次扣除
            $res2 = Db::table('tab_promote')->where('id',PID)->setDec('balance_profit',$data['sum_money']);
            if($res && $res2){
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }

    }
    /**
     * @函数或方法说明
     * @提现
     * @author: 郭家屯
     * @since: 2019/8/6 15:47
     */
    public function withdrawal(){
        $amount = $this->request->param('amount/d',0);
        if($amount <= 0){
            $this->error('提现金额不能为空');
        }
        $password = $this->request->param('password/s','');
        if($password == ''){
            $this->error('提现密码不能为空');
        }
        $promote =  get_promote_entity(PID,'id,second_pwd,account,parent_id,balance_coin,balance_profit,promote_level');
        if(empty($promote['second_pwd'])){
            $this->error('请设置二级密码');
        }
        if (!xigu_compare_password($password, $promote['second_pwd'])) {
            $this->error('提现密码错误');
        }
        if($amount > $promote['balance_profit']){
            $this->error('收益金额不足');
        }
        if(empty(cmf_get_option('cash_set')['limit_money']) && $amount <1){
            $this->error('提现金额最低1元');
        }
        if(cmf_get_option('cash_set')['limit_money'] && $amount< cmf_get_option('cash_set')['limit_money']){
            $this->error('提现金额低于提现最小金额');
        }
        $model = new PromotewithdrawModel();
        $result = $model->settlement($amount,1,$promote);
        if($result){
            $this->success('提交成功');
        }else{
            $this->error('提交失败');
        }
    }

    /**
     * @函数或方法说明
     * @支出记录
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/7/17 10:27
     */
    public function profit_record()
    {
        $request = $this->request->param();
        $date = $request['rangepickdate'];
        if($date) {
            $dateexp = explode('至', $date);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            $map['create_time'] = ['between', [strtotime($starttime), strtotime($endtime)+86399]];
        }else{
            $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
            $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
        }
        if($request['type']){
            $map['type'] = $request['type'];
        }
        if($request['status'] != ''){
            $map['status'] = $request['status'];
        }
        $map['promote_id'] = PID;
        $model = new PromotewithdrawModel();
        $base = new BaseHomeController;
        $exend['field'] = 'id,type,sum_money,fee,status,create_time';
        $exend['order'] = 'id desc';
        $list_data = $base->data_list($model,$map,$exend);
        $page = $list_data->render();
        $this->assign("data_lists", $list_data);
        $this->assign("page", $page);
        $total = $model->field('sum(fee) as totalfee,sum(sum_money) as totalmoney')->where($map)->find();
        $this->assign('total', $total);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @子渠道提现
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/7/17 10:13
     */
    public function son_withdrawal()
    {
        if (PID_LEVEL>2) {
            die('数据错误');
        }
        $base = new BaseHomeController();
        $model = new PromotewithdrawModel();
        $zipromote = get_song_promote_lists(PID);
        $map['promote_id'] = ['in',array_column($zipromote,'id')];
        $map['type'] = 1;
        $map['promote_level'] = PID_LEVEL+1;
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
        if(PID_LEVEL == 1){
            $map['promote_level'] = 2;
        }else{
            $map['promote_level'] = 3;
        }
        $map['promote_id'] = ['in',array_column($zipromote,'id')];
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
     * @子渠道兑换
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/7/17 10:13
     */
    public function son_exchange()
    {
        if (PID_LEVEL>2) {
            die('数据错误');
        }
        $base = new BaseHomeController();
        $model = new PromotewithdrawModel();
        $map['type'] = 2;
        $map['promote_level'] = PID_LEVEL+1;
        $zipromote = get_song_promote_lists(PID);
        $map['promote_id'] = ['in',array_column($zipromote,'id')];
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
        if(PID_LEVEL == 1){
            $map['promote_level'] = 2;
        }else{
            $map['promote_level'] = 3;
        }
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
     * @子渠道结算统计
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/7/17 10:13
     */
    public function son_sum()
    {
        if (PID_LEVEL>2) {
            die('数据错误');
        }
        //结算单部分
        $data = $this->request->param();
        if ($data['promote_id']) {
            $this_promote_son_arr[] = $data['promote_id'];
            if(PID_LEVEL==1){
                $this_promote_son = array_column(get_song_promote_lists($data['promote_id'],1),'id');//直属下级
                $this_promote_son_arr = array_merge($this_promote_son_arr,$this_promote_son);
            }
            $map['promote_id'] = ['in',$this_promote_son_arr];
            $map2['tab_promote.id'] = $data['promote_id'];
        }else{
            $zipromote = get_song_promote_lists(PID,1);//直属下级
            $map['promote_id'] = ['in',array_column($zipromote,'id')];
            $map2['tab_promote.id'] = ['in',array_column($zipromote,'id')];
        }
        if(PID_LEVEL == 1){
            $map['sub_status2'] = 1;
            $map2['s.sub_status2'] = 1;
        }else{
            $map['sub_status3'] = 1;
            $map2['s.sub_status3'] = 1;
        }
        $model = new PromoteModel();
        $base = new BaseHomeController();
        $exend['order'] = 'tab_promote.id desc';
        if(PID_LEVEL==1){
            $map['top_promote_id'] = PID;
            $map2['s.top_promote_id'] = PID;
        }else{
            $map['parent_id'] = PID;
            $map2['s.parent_id'] = PID;
        }
        $exend['group'] = 'tab_promote.id';
        $exend['field'] = 'tab_promote.id as promote_id,s.parent_id,s.top_promote_id,account as promote_account,sum(pay_amount) as totalamount,sum(sum_reg) as totalreg,sum(sum_money) as totalmoney,ratio,s.money,s.pattern,sum(sum_money2) as totalmoney2,ratio2,money2,sum(sum_money3) as totalmoney3,ratio3,money3';
        $exend['join1'] = [['tab_promote_settlement'=>'s'],'s.promote_id = tab_promote.id or s.parent_id = tab_promote.id','left'];
        $list_data = $base->data_list_join($model, $map2, $exend);
        $page = $list_data->render();
        $this->assign("data_lists", $list_data);
        $this->assign("page", $page);
        //收益部分
        $promotewithdrawmodel = new PromotewithdrawModel();
        $promote_ids = array_column($list_data->toarray()['data'], 'promote_id');//有结算数据的直属下级推广员
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
        $this->assign('sum', $sum);
        //汇总部分
        $mpids = [];
        foreach ($promote_ids as $pk=>$pv){
            $mpids = array_merge($mpids,array_column(get_song_promote_lists($pv),'id'));
        }
        $map['promote_id'] = ['in',array_merge($mpids,$promote_ids)];
        $settlementmodel = new PromotesettlementModel();
        $all = $settlementmodel->field('sum(pay_amount) as totalamount,sum(sum_reg) as totalreg,sum(sum_money) as totalmoney,sum(sum_money2) as totalmoney2,sum(sum_money3) as totalmoney3')->where($map)->find();
        $all_withdraw = $promotewithdrawmodel->field('sum(sum_money) as totalmoney,type')->where($map1)->group('type')->order('type asc')->select()->toArray();
        if($all_withdraw){
            if($all_withdraw[0]['type'] == 1){
                $all['withdraw'] = $all_withdraw[0]['totalmoney'];
                $all['exchange'] = $all_withdraw[1]['totalmoney'];
            }else{
                $all['withdraw'] = 0;
                $all['exchange'] = $all_withdraw[0]['totalmoney'];
            }
        }else{
            $all['withdraw'] = 0;
            $all['exchange'] = 0;
        }
        $this->assign('all', $all);
        return $this->fetch();
    }

    // 子渠道结算
    /**
     * @函数或方法说明
     * @未结算订单
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/6/27 10:13
     */
    public function settlement()
    {
        if (PID_LEVEL>2) {
            die('数据错误');
        }
        $data = $this->request->param();
        $type = $data['type'];
        $sub_status = 'sub_status'.(PID_LEVEL+1);
        if($type == 0){
            $map['pay_way'] = ['neq',1];
            $map[$sub_status] = 0;
            if($data['is_bind'] == 1){
                unset($map['pay_way']);
            }
        }elseif($type == 1){
            $map[$sub_status] = 1;
        }else{
            $map[$sub_status] = 2;
        }
        $zipromote = get_song_promote_lists(PID,2);
        $map['promote_id'] = ['in',array_column($zipromote,'id')];
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
            $this_promote_son_arr[] = $data['promote_id'];
            if(PID_LEVEL==1){
                $this_promote_son = array_column(get_song_promote_lists($data['promote_id'],2),'id');
                $this_promote_son_arr = array_merge($this_promote_son_arr,$this_promote_son);
            }
            $map['promote_id'] = ['in',$this_promote_son_arr];
        }
        if(PID_LEVEL==1){
            $map['top_promote_id'] = PID;
        }else{
            $map['parent_id'] = PID;
        }
        $date = $data['rangepickdate'];
        if($date) {
            $dateexp = explode('至', $date);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            $map['create_time'] = ['between', [strtotime($starttime), strtotime($endtime)+86399]];
        }else{
            $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
            $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
        }
        $model = new PromotesettlementModel();
        $base = new BaseHomeController();
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        $exend['order'] = 'id desc';
        $exend['field'] = 'id,promote_account,game_name,pattern,ratio,money,sum_money,ratio2,money2,sum_money2,ratio3,money3,sum_money3,promote_id,parent_id,top_promote_id,user_account,is_check,parent_name,pay_order_number,pay_amount,cost,create_time,pay_way';
        $list_data = $base->data_list($model, $map, $exend)->each(function($item, $key) use ($ys_show_promote){

            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $item['user_account'] = get_ys_string($item['user_account'],$ys_show_promote['account_show_promote']);
            }
        });
//        dump($model->getlastsql());exit;
        // 获取分页显示
        $total = $model->field('sum(cost) as totalcost,sum(pay_amount) as totalamount,sum(sum_money) as totalmoney,sum(sum_money2) as totalmoney2,sum(sum_money3) as totalmoney3')->where($map)->find();
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
    public function generatesettlement()
    {
        if (PID_LEVEL>2) {
            die('数据错误');
        }
        $data = $this->request->param();
        $sub_status = 'sub_status'.(PID_LEVEL+1);
        $map[$sub_status] = 0;
        $map['pay_way'] = ['neq',1];
        $zipromote = get_song_promote_lists(PID,2);
        $map['promote_id'] = ['in',array_column($zipromote,'id')];
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
            $this_promote_son_arr[] = $data['promote_id'];
            if(PID_LEVEL==1){
                $this_promote_son = array_column(get_song_promote_lists($data['promote_id'],2),'id');
                $this_promote_son_arr = array_merge($this_promote_son_arr,$this_promote_son);
            }
            $map['promote_id'] = ['in',$this_promote_son_arr];
        }
        if($data['is_bind'] == 1){
            unset($map['pay_way']);
        }
        if(PID_LEVEL==1){
            $map['top_promote_id'] = PID;
        }else{
            $map['parent_id'] = PID;
        }
        $group = 'promote_id';
        $date = $data['rangepickdate'];
        if($date) {
            $dateexp = explode('至', $date);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $map['create_time'] = ['between', [strtotime($starttime), strtotime($endtime)+86399]];
        }
        $model = new PromotesettlementModel();
        $listdata = $model->field('if(parent_id != ' .PID.' AND top_promote_id = '.PID.',parent_id,promote_id) as promote_id,parent_id,top_promote_id,promote_account,sum(sum_money) as money,sum(sum_money2) as money2,sum(sum_money3) as money3')->where($map)->group($group)->select()->toArray();
        if (empty($listdata)) {
            $this->error('请选择结算数据');
        }
        $promotemodel = new PromoteModel();
        Db::startTrans();
        try {
            $update_time = 'update_time'.(PID_LEVEL+1);
            $model->where($map)->update([$sub_status => 1, $update_time => time()]);
            foreach ($listdata as $key => $v) {
                $money = 'money'.(PID_LEVEL+1);
                $promotemodel->where('id', $v[$group])->setInc('balance_profit', $v[$money]);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        $this->success('结算成功');
    }


    /**
     * @函数或方法说明
     * @修改结算单状态
     * @author: 郭家屯
     * @since: 2019/8/7 13:33
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeStatus()
    {
        $id = $this->request->param('id/d',0);
        if (empty($id)) $this->error('请选择要操作的数据');
        $map['id'] = $id;
        if(PID_LEVEL==1){
            $map['top_promote_id'] = PID;
        }else{
            $map['parent_id|promote_id'] = PID;
        }
        $sub_status = 'sub_status'.(PID_LEVEL+1);
        $map[$sub_status] = ['neq',1];
        $type = $this->request->param('type/s','on');
        if($type == 'on'){
            $status = 0;
        }else{
            $status = 2;
        }
        $model = new PromotesettlementModel();
        $result = $model->where($map)->setField($sub_status,$status);
        if($result){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    //修改提现状态
    public function changeSettlementStatus_old()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        if (empty($ids)) $this->error('请选择要操作的数据');
        $ids = explode(',',$ids);
        $zipromote = get_song_promote_lists(PID);
        $save['audit_time'] = time();
        $save['status'] = $data['status'];
        $model = new PromotewithdrawModel();
        $map['id'] = ['in',$ids];
        $map['status'] = ['neq', 3];
        $map['promote_id'] = ['in',array_column($zipromote,'id')];
        $result = $model->where($map)->update($save);
        if ($result === false) {
            $this->error('操作失败');
        }
        $this->success('操作成功');
    }
    //修改提现状态-修改-byh-20210628
    public function changeSettlementStatus()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        if (empty($ids)) $this->error('请选择要操作的数据');
        $ids = explode(',',$ids);
        $zipromote = get_song_promote_lists(PID);
        $save['audit_time'] = time();
        $save['status'] = $data['status'];
        $model = new PromotewithdrawModel();
        Db::startTrans();
        foreach ($ids as $key => $id) {
            $map['id'] = $id;
            $map['promote_id'] = ['in',array_column($zipromote,'id')];
            $_promote = $model->field('sum_money,promote_id,status')->where($map)->find();
            if($_promote['status'] !=0){
                Db::rollback();
                $this->error('订单非未审核状态,不可修改');
            }
            $result = $model->where($map)->update($save);
            //增加处理驳回的数据,返还申请提现的金额-
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
     * @函数或方法说明
     * @打款
     * @author: 郭家屯
     * @since: 2019/8/7 17:04
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function paid()
    {
        $data = $this->request->param();
        $id = $data['id'];
        if (empty($id)) $this->error('请选择已通过的数据');
        $id = explode(',',$id);
        $model = new PromotewithdrawModel();
        $zipromote = get_song_promote_lists(PID);
        $map['promote_id'] = ['in',array_column($zipromote,'id')];
        $map['id'] = ['in',$id];
        $map['status'] = 1;
        $save['status'] = 3;
        $save['paid_time'] = time();
        $result = $model->where($map)->update($save);
        if ($result == false) {
            $this->error('请选择已通过的数据');
        } else {
            $this->success('打款成功');
        }
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
        if (empty($id)) $this->error('请选择已通过的数据');
        $id = explode(',',$id);
        $model = new PromotewithdrawModel();
        $promotemodel = new PromoteModel();
        $map['id'] = ['in',$id];
        $map['status'] = 1;
        $ok = 0;
        $fail = 0;
        $data = $model->where($map)->field('id,promote_id,sum_money')->select()->toArray();
        if(!$data)$this->error('请选择已通过的数据');
        foreach ($data as $key=>$v){
            $promote = get_promote_entity(PID,'balance_coin');
            if($promote['balance_coin'] < $v['sum_money']){
                $fail++;
                continue;
            }
            $where['id'] = $v['id'];
            $save['status'] = 3;
            $save['paid_time'] = time();
            Db::startTrans();
            try {
                $model->where($where)->update($save);
                $promotemodel->where('id',PID)->setDec('balance_coin', $v['sum_money']);
                $promotemodel->where('id', $v['promote_id'])->setInc('balance_coin', $v['sum_money']);
                $info = [
                        'promote_id' => $v['promote_id'],
                        'promote_type' => 1,
                        'num' => $v['sum_money'],
                        'op_id' => PID,
                        'create_time' => time(),
                        'source_id' => 1
                ];
                Db::table('tab_spend_promote_coin')->insert($info);
                // 提交事务
                Db::commit();
                $ok++;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }        }
        $this->success('发放成功:'.$ok."个，发放失败:".$fail.'个');
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
        if(PID_LEVEL==1){
            $map['top_promote_id'] = PID;
        }else{
            $map['parent_id'] = PID;
        }
        $settlement = $model->field('id,pattern,pay_amount')->where('id',$settlement_id)->where($map)->find();
        if(!$settlement){
            $this->error('结算单不存在');
        }
        $value = abs($value);
        if($settlement['pattern'] == 0){
            if(PID_LEVEL == 1){
                $save['ratio2'] = $value;
                $save['sum_money2'] = round($value*$settlement['pay_amount']/100,2);
            }else{
                $save['ratio3'] = $value;
                $save['sum_money3'] = round($value*$settlement['pay_amount']/100,2);
            }

        }else{
            if(PID_LEVEL == 1){
                $save['money2'] = $value;
                $save['sum_money2'] = $value;
            }else{
                $save['money3'] = $value;
                $save['sum_money3'] = $value;
            }
        }
        $result = $model->where('id',$settlement_id)->update($save);
        if($result){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }

    /**
     * 周期结算列表
     * created by wjd 2021-9-8 17:34:33
    */

    public function period()
    {
        $request = $this->request->param();
        $param = $request;
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $promote_id = PID;
        // exit;
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
            ->paginate($row, false, ['query' => $param]);
        $lists->each(function($item,$key){
            $item['receipt'] = cmf_get_image_url($item['receipt']);
            return $item;
        });

        $listsAll = Db::table('tab_promote_settlement_period')
            ->where($map)
            // ->whereOr($map2)
            ->select();
        $total_promoter_earn = 0; // 渠道分成总金额
        $total_plateform_earn = 0; // 甲方(平台)分成总金额
        $total_earn = 0; // 游戏实付金额
        $total_remit = 0; // 总打款金额
        $total_cps = 0;
        $total_cpa = 0;

        foreach($listsAll as $k=>$v){
            $total_promoter_earn += $v['promoter_earn'];
            $total_plateform_earn += $v['plateform_earn'];
            $total_earn += $v['total_money'];
            $total_remit += $v['remit_amount'];
            $total_cps += $v['total_cps'];
            $total_cpa += $v['total_cpa'];

        }
        $total = [
            'total_promoter_earn'=>sprintf("%.2f",$total_promoter_earn),
            'total_plateform_earn'=>sprintf("%.2f",$total_plateform_earn),
            'total_earn'=>sprintf("%.2f",$total_earn),
            'total_remit'=>sprintf("%.2f",$total_remit),
            'total_cps'=>sprintf("%.2f",$total_cps),
            'total_cpa'=>sprintf("%.2f",$total_cpa),
        ];

        $this->assign('total', $total);
        $page = $lists->render();
        $this->assign("data_lists", $lists);
        $this->assign("page", $page);
        return $this->fetch();
    }
    // 查看某个周期结算单 的详情
    public function show_detail(Request $request){
        $param = $request->param();
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
                $item['plateform_sum_money'] = sprintf("%.2f", $item['pay_amount'] - $item['sum_money']);
                $item['pay_amount'] = sprintf("%.2f",$item['pay_amount']);
                $item['sum_money'] = sprintf("%.2f",$item['sum_money']);
                if($item['pattern'] == 1){
                    // $item['cpa_money'] = sprintf("%.2f",$item['money']); // CPA分成金额
                    $item['cpa_money'] = sprintf("%.2f",$item['sum_money']); // CPA分成金额
                    $item['cps_money'] = sprintf("%.2f", 0);
                }
                if($item['pattern'] == 0){
                    $item['cpa_money'] = sprintf("%.2f",0);
                    // $item['cps_money'] = sprintf("%.2f",($item['pay_amount'] * $item['ratio'] / 100)); // CPS分成金额
                    $item['cps_money'] = sprintf("%.2f",$item['sum_money']); // CPS分成金额
                }
                
                return $item;
            });
        // var_dump($lists);exit;
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
            // $total_cpa_money += $v['money'];
            // $total_cps_money += $v['pay_amount'] * $v['ratio'];
        }
        $total = [
            'pay_amount'=>sprintf("%.2f",$total_pay_amount),
            'sum_money'=>sprintf("%.2f",$total_sum_money),
            'plateform_sum_money'=>sprintf("%.2f",$total_plateform_sum_money),
            'total_cpa_money'=>$total_cpa_money,
            'total_cps_money'=>$total_cps_money,
        ];
        // return json($total);
        $this->assign('total', $total);
        $this->assign('period_id', $period_id);
        $page = $lists->render();
        $this->assign("data_lists", $lists);
        $this->assign("page", $page);
        return $this->fetch();
    }
}
