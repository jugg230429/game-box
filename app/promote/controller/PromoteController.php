<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\promote\controller;

use app\business\model\PromoteBusinessModel;
use app\common\controller\BaseController;
use app\game\model\GameModel;
use app\promote\logic\PromoteLevelLogic;
use app\promote\model\PromoteLevelModel;
use app\promote\model\PromotePrepaymentSendRecordModel;
use cmf\controller\AdminBaseController;
use app\promote\model\PromoteModel;
use app\promote\logic\PromoteLogic;
use app\promote\model\PromotePrepaymentDeductRecordModel;
use app\promote\model\PromotePrepaymentRechargeModel;
use app\promote\validate\PromoteValidate;
use think\Request;
use think\Db;

class PromoteController extends AdminBaseController
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
    }
    /**
     * [渠道列表]
     * @return mixed
     * @author yyh
     */
    public function lists()
    {
        $base = new BaseController();
        $model = new PromoteModel();
        //添加搜索条件
        $data = $this->request->param();
        $promote_id = $data['promote_id'];
        if ($promote_id) {
            $map['tab_promote.id'] = $promote_id;
        }
        $account = $data['account'];
        if ($account) {
            $map['tab_promote.account'] = $account;
        }
        $busier_id = $data['busier_id'];
        if($busier_id!=''){
            $map['tab_promote.busier_id'] = $busier_id;
        }
        $parent_id = $data['parent_id'];//上线渠道
        if ($parent_id) {
            $map['tab_promote.top_promote_id|tab_promote.parent_id|tab_promote.id'] = $parent_id;
        }
        $level = $data['level'];//渠道等级
        if ($level == 1) {
            $map['tab_promote.parent_id'] = 0;
        } elseif($level!=''){
            $map['tab_promote.promote_level'] = ['eq', $level];
        }
        $status = $data['status'];
        if ($status != '') {
            $map['tab_promote.status'] = $status;
        }
        $register_type = $data['register_type'];
        if($register_type != ''){
            $map['tab_promote.register_type'] = $register_type;
        }
        $is_custom_pay = $data['is_custom_pay'];
        if($is_custom_pay == '0' || $is_custom_pay == '1'){
            $map['tab_promote.is_custom_pay'] = $is_custom_pay;
        }
        // 渠道等级
        $promote_level = $data['promote_level'];
        if($promote_level > 0){
            $PromoteLevelModel = new PromoteLevelModel();
            $promote_ids = $PromoteLevelModel->where('promote_level','egt',$promote_level)->column('promote_id');
            if(empty($promote_ids)){
                $map['tab_promote.id'] = 0;
            }else{
                $map['tab_promote.id'] = array('in',$promote_ids);
            }
        }
        $exend['order'] = 'tab_promote.create_time desc';
        $exend['group'] = 'tab_promote.id';
        $exend['field'] = 'count(u.id) as user_count,tab_promote.id,tab_promote.account,tab_promote.real_name,tab_promote.mobile_phone,tab_promote.balance_coin,tab_promote.create_time,tab_promote.last_login_time,tab_promote.parent_id,tab_promote.busier_id,tab_promote.status,tab_promote.pattern,tab_promote.game_ids,tab_promote.promote_level,tab_promote.top_promote_id,tab_promote.register_type,settlement_day_period,cash_money';
        $exend['join1'][] = ['tab_user' => "u"];
        $exend['join1'][] = 'u.promote_id = tab_promote.id and u.puid = 0';
        $exend['join1'][] = 'left';
        $data = $base->data_list_join($model, $map, $exend)->each(function ($item,$key){
            $promoteLevelLogic = new PromoteLevelLogic();
            $item['level_name'] = $promoteLevelLogic->getPromoteLevelName($item['id']);
            return $item;
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        //自动审核
        $autostatus = Db::table('tab_promote_config')->where(array('name' => 'promote_auto_audit'))->value('status');
        $this->assign('autostatus', $autostatus);

        //获取汇总数据
        $promoteIds = Db ::table('tab_promote') -> where($map) -> column('id');
        $total_balance_coin = Db ::table('tab_promote') -> where($map) -> sum('balance_coin');//总平台币余额
        $total_cash_money = Db ::table('tab_promote') -> where($map) -> sum('cash_money');//总押金
        $where = [];
        $where['promote_id'] = ['in', $promoteIds];
        $total_user_count = Db ::table('tab_user') -> where($where) -> count();//总注册
        $where['puid'] = ['eq', 0];
        $total_pay_amount = Db ::table('tab_user') -> where($where) -> sum('cumulative');

        $this -> assign('total_balance_coin', $total_balance_coin);
        $this -> assign('total_user_count', $total_user_count);
        $this -> assign('total_pay_amount', $total_pay_amount);
        $this -> assign('total_cash_money', $total_cash_money);


        return $this->fetch();
    }

    /**
     * [修改审核状态]
     * @author yyh
     */
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
        $status = $this->request->param('status', 0, 'intval');
        $save['status'] = $status == 1 ? -1 : 1;
        $model = new PromoteModel();
        Db::startTrans();
        foreach ($ids as $key => $value) {
            if ($data['type'] == 'lock' && $status == 1) {
                //锁定推广员同时锁定下级以及三级渠道
                $map['id|parent_id|top_promote_id'] = $value;
            }
//            elseif ($data['type'] == 'lock' && $status == -1) {
//                //解锁推广员,同时解锁上级渠道
//                $info = get_promote_entity($value);
//                $map['id'] = ['in', [$info['id'], $info['parent_id'], $info['top_promote_id']]];
//            }
            else {
                $map['id'] = $value;
            }
            $result = $model->where($map)->update($save);
            if (false===$result) {
                Db::rollback();
                $this->error('操作失败');
            }
        }
        Db::commit();
        $this->success('操作成功');
    }

    /**
     * [add 新增渠道]
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $businessmodel = new PromoteBusinessModel();
            $data = $this->request->param();
            $validate = new PromoteValidate();

            if (!$validate->scene('add')->check($data)) {
                $this->error($validate->getError());
            }
            $add['account'] = $data['account'];
            $add['status'] = $data['status'];
            $add['real_name'] = $data['real_name'];
            $add['mobile_phone'] = $data['mobile_phone'];
            $add['email'] = $data['email'];
            $add['password'] = cmf_password($_POST['password']);
            $add['busier_id'] = $data['busier_id']?:0;
            $add['create_time'] = time();
            $add['pattern'] = $data['pattern'];
            $add['register_type'] = $data['register_type'];
            $model = new PromoteModel();
            $model->startTrans();
            $result = $model->field(true)->insertGetId($add);
            if($result > 0){

            }

            if ($result) {
                // 结算周期
                $day_period = 7; // 刚一开始添加的时候 就默认结算周期为7天
                if($day_period > 0){
                    $d_time = time();
                    $insertRes2 = Db::table('tab_promote_settlement_time')
                        ->insert([
                            'promote_id'=>$result,
                            'promote_account'=>$data['account'],
                            'time_type'=>0,  // 归档时间类型0: 按天, 1:按每月的几号
                            'day_period'=>$day_period,
                            'create_time'=>$d_time,
                            'update_time'=>$d_time,
                            'next_count_time'=>strtotime(date('Y-m-d')) + $day_period * 24 * 60 *60,
                        ]);
                }

                $res = true;
                if($add['busier_id']){
                    $business = $businessmodel->field('promote_ids')->find($add['busier_id']);
                    $promote_ids = $business['promote_ids'].','.$result;
                    $promote_ids = trim($promote_ids,',');
                    $res = $businessmodel->where('id',$add['busier_id'])->update(['promote_ids'=>$promote_ids]);
                }
                if($res && $insertRes2){
                    $model->commit();
                    $this->success('添加成功', url('lists'));
                }else{
                    $model->rollback();
                }
            } else {
                $model->rollback();
                $this->error('添加失败');
            }


        }
        return $this->fetch();
    }

    public function edit()
    {
        $model = new PromoteModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new PromoteValidate();
            $validate->scene('edit', ['password' => 'min:6|max:30|regex:^[A-Za-z0-9]+$', 'email'=>'email','second_pwd' => 'min:6|max:30|regex:^[A-Za-z0-9]+$']);
            $valresult = $validate->scene('edit')->check($data);
            if (!$valresult) {
                $this->error($validate->getError());
            }
            $save['status'] = $data['status'];
            // $save['real_name'] = $data['real_name'];
            // $save['mobile_phone'] = $data['mobile_phone'];
            $save['pattern'] = $data['pattern'];
            $save['busier_id'] = $data['busier_id']?:0;
            $save['email'] = $data['email'];
            if ($data['password'] != '') {
                $save['password'] = cmf_password($data['password']);
            }
            if ($data['second_pwd'] != '') {
                $save['second_pwd'] = cmf_password($data['second_pwd']);
            }
            $save['mark1'] = $data['mark1'];
            $save['register_type'] = $data['register_type'];
            $save['id'] = $data['id'];
            $save['zfb'] = json_encode($data['zfb']);
            $save['wxscan'] = json_encode($data['wxscan']);
            $save['wxapp'] = json_encode($data['wxapp']);
            $save['is_custom_pay'] = $data['is_custom_pay'];
            $save['allow_check_subbox'] = $data['allow_check_subbox'];
            $model->startTrans();
            $result = $model->field(true)->update($save);
            if ($result !== false) {
                $logic = new PromoteLogic();
                $res1 = $res2 = 1;
                if($data['old_busier_id']!=0){
                    $res1 = $logic->remove_business_promote_ids($data['old_busier_id'],$save['id']);
                }
                if($data['busier_id']!=0){
                    $res2 = $logic->add_business_promote_ids($data['busier_id'],$save['id']);
                }
                if($res1&&$res2){
                    //更改二级推广员商户
                    $parent_id = $save['id'];
                    $model->where(['top_promote_id|parent_id'=>$parent_id])->update(['busier_id'=>$save['busier_id']]);
                    //修改二级结算模式
                    $model->where(['top_promote_id|parent_id'=>$parent_id])->update(['pattern'=>$save['pattern']]);
                    $model->commit();
                    $this->success('编辑成功', url('lists'));
                }else{
                    $model->rollback();
                    $this->error('编辑失败');
                }
            } else {
                $model->rollback();
                $this->error('编辑失败');
            }
        } else {
            $id = $this->request->param('id');
            if ($id > 0) {
                $data = $model->field('id,account,status,promote_level,real_name,mobile_phone,email,parent_id,create_time,last_login_time,second_pwd,balance_coin,mark1,pattern,busier_id,bank_phone,bank_card,bank_name,bank_account,account_openin,bank_area,register_type,settment_type,alipay_account,alipay_name,zfb,wxscan,wxapp,is_custom_pay,prepayment,allow_check_subbox')->where('id', $id)->find();
                if (empty($data)) {
                    $this->error('没有该数据', url('lists'));
                }
                $data['zfb'] = json_decode($data['zfb'],true);
                $data['wxscan'] = json_decode($data['wxscan'],true);
                $data['wxapp'] = json_decode($data['wxapp'],true);
                $this->assign('data', $data);
                return $this->fetch();
            } else {
                $this->error('缺少id', url('lists'));
            }
        }
    }

    /**
     * [set_config_auto_audit 设置渠道]
     * @param string $val [description]
     * @param string $config_key [description]
     * @author [yyh] <[<email address>]>
     */
    public function set_config_auto_audit($status = '')
    {
        $config['status'] = $status == 0 ? 1 : 0;
        $res = Db::table('tab_promote_config')->where(array('name' => 'promote_auto_audit'))->update($config);

        if ($res !== false) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * @函数或方法说明
     * @获取所有的游戏
     * @author: 郭家屯
     * @since: 2019/7/5 14:12
     */
    public function getPromoteGame()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('id', 0, 'intval');
            $promotemodel = new PromoteModel();
            $promote_info = $promotemodel->field('id,account,game_ids')->where('id', $id)->find();
            $promote_info = $promote_info ? $promote_info->toArray():[];
            $gamemodel = new GameModel();
            $game_list = $gamemodel -> getAllGameLists();
            if (!empty($game_list)) {
                foreach ($game_list as &$v) {
                    $v['short_f'] = substr($v['short'], 0, 1);
                }
            }
            $data['data']['game_list'] = $game_list;
            $promote_info['game_ids'] = empty($promote_info['game_ids']) ? '' : explode(',', $promote_info['game_ids']);
            $data['data']['promote_info'] = $promote_info;
            $data['code'] = 1;
            return json($data);
        }
    }

    /**
     * @函数或方法说明
     * @保存不能申请游戏信息
     * @author: 郭家屯
     * @since: 2019/7/8 9:10
     */
    public function savePromoteGame()
    {
        $data = $this->request->param();
        if (!$data['promote_id']) {
            $this->error('请选择渠道');
        }
        if (empty($data['game_ids'])) {
            $game_ids = '';
            $data['game_ids'] = [];
        } else {
            $game_ids = implode(',', $data['game_ids']);
        }
        $promote_info = get_promote_entity($data['promote_id'],'id,game_ids');
        if($promote_info['game_ids']){
            $old_game_ids = explode(',',$promote_info['game_ids']);
        }else{
            $old_game_ids = [];
        }
        $change = array_merge(array_diff($old_game_ids,$data['game_ids']),array_diff($data['game_ids'],$old_game_ids));
        $model = new PromoteModel();
        Db::startTrans();
        try{
            //修改一级渠道以及子渠道
           $model->where('id|parent_id|top_promote_id', $data['promote_id'])->setField('game_ids', $game_ids);
            foreach ($change as $key=>$v){
                $game_info = get_game_entity($v,'id,promote_ids');
                $promote_ids = $game_info['promote_ids'] ? explode(',',$game_info['promote_ids']) : [];
                if($promote_ids && in_array($data['promote_id'],$promote_ids)){
                    unset($promote_ids[array_search($data['promote_id'],$promote_ids)]);
                }else{
                    $promote_ids[] = $data['promote_id'];
                }
                $promote_ids = count($promote_ids) > 0 ? implode(',',$promote_ids) : '';
                Db::table('tab_game')->where('id',$v)->setField('promote_ids',$promote_ids);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('设置失败');
        }
        $this->success('修改成功');
    }

    // 预付款----------------------===================================================-----------------------START

    // 预付款充值记录
    public function prepayment_record(Request $request){
        // return $this->fetch();
        // echo '预付款充值记录';exit;
        $ppr_m = new PromotePrepaymentRechargeModel();
        $req = $request->param();
        $row = $req['row'] ?? 10;
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
        // $promote_id = PID;
        // $search['promote_id'] = $promote_id;
        $order = 'id desc';
        $data = $ppr_m->lists($search,$row,$order,$req);
        // 汇总
        $total_amount = $ppr_m->countPayAmount($search);

        $this->assign("total_amount", $total_amount);

        // 获取分页显示

        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        // return json($data);
        return $this->fetch();
    }
    // 预付款消费记录
    public function prepayment_deduct_record(Request $request){

        $ppdr_m = new PromotePrepaymentDeductRecordModel();
        $req = $request->param();
        $row = $req['row'] ?? 10;
        $search = [];
        $pay_order_number = $req['pay_order_number'] ?? '';
        $start_time = $req['start_time'] ?? '';
        $end_time = $req['end_time'] ?? '';
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
        if(!empty($promote_id)){
            $search['promote_id'] = $promote_id;
        }
        if(!empty($promote_account)){
            $search['promote_account'] = $promote_account;
        }
        $order = 'id desc';
        $data = $ppdr_m->lists($search,$row,$order,$req);
        // 汇总
        $total_amount = $ppdr_m->countDeductAmount($search);

        $this->assign("total_amount", $total_amount);

        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        // return json($data);
        return $this->fetch();
    }


    /**
     * @发放预付款记录
     *
     * @author: zsl
     * @since: 2021/2/1 19:49
     */
    public function sendPrepaymentRecord()
    {
        $param = $this -> request -> param();
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $mSendRecord = new PromotePrepaymentSendRecordModel();
        $where = [];
        if (!empty($param['promote_id'])) {
            $where['promote_id'] = $param['promote_id'];
        }
        if ($param['start_time'] && $param['end_time']) {
            $where['create_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time']) + 86399]];
        } elseif ($param['end_time']) {
            $where['create_time'] = ['lt', strtotime($param['end_time']) + 86400];
        } elseif ($param['start_time']) {
            $where['create_time'] = ['egt', strtotime($param['start_time'])];
        }
        $lists = $mSendRecord -> where($where) -> order('id desc') -> paginate($row, false, ['query' => $param]);
        // 汇总
        $total_amount = $mSendRecord->countSendAmount($where);

        $this->assign("total_amount", $total_amount);
        $this -> assign('lists', $lists);
        // 获取分页显示
        $page = $lists -> render();
        $this -> assign("page", $page);
        return $this -> fetch();
    }


    /**
     * @发放预付款
     *
     * @author: zsl
     * @since: 2021/2/1 16:22
     */
    public function sendPrepayment()
    {
        // 验证二级密码
        $password = $this -> request -> param('second_pwd');
        $admin = Db ::name('user') -> field('second_pass') -> where(['id' => session('ADMIN_ID')]) -> find();
        if (!xigu_compare_password($password, $admin['second_pass'])) {
            $this -> error('二级密码错误');
        }
        $id = $this -> request -> param('id', 0, 'intval');
        $prepayment = $this -> request -> param('prepayment/f');
        if ($prepayment < 0.01) {
            $this->error('请输入正确金额');
        }
        // 获取推广员信息
        $lPromote = new PromoteLogic();
        $promoteInfo = $lPromote -> getInfo($id);
        if (empty($promoteInfo)) {
            $this -> error('推广员不存在');
        }
        // 是否一级推广员
        if ($promoteInfo -> promote_level != '1') {
            $this -> error('非法操作');
        }
        try {
            $promoteInfo -> startTrans();
            // 新增推广员付余款余额
            $promoteInfo -> prepayment = $promoteInfo -> prepayment + $prepayment;
            $result = $promoteInfo -> isUpdate(true) -> save();
            if (false === $result) {
                $promoteInfo -> rollback();
                $this -> error('发放失败,请稍后重试');
            }
            // 插入记录
            $mSendRecord = new PromotePrepaymentSendRecordModel();
            $data = [];
            $data['promote_id'] = $promoteInfo -> id;
            $data['promote_account'] = $promoteInfo -> account;
            $data['send_amount'] = $prepayment;
            $data['new_amount'] = $promoteInfo -> prepayment;
            $sendResult = $mSendRecord -> addRecord($data);
            if (false === $sendResult) {
                $promoteInfo -> rollback();
                $this -> error('发放失败,请稍后重试');
            }
            $promoteInfo -> commit();
        } catch (\Exception $e) {
            $promoteInfo -> rollback();
            $this -> error('发生错误');
        }
        $this -> success('发放成功');
    }


    // 预付款---------------------===================================================------------------------END
    /**
     * 修改渠道结算周期
     * created by wjd 2021-6-29 13:53:15
    */
    public function alterPromoteSettlement(Request $request)
    {
        $param = $request->param();
        // 结算周期
        $day_period = (int) ($param['day_period'] ?? 0);
        $promote_id = $param['promote_id'];
        $promote_account = $param['promote_account'];
        $d_time = time();
        $tab_promote_settlement_time_info = Db::table('tab_promote_settlement_time')->where(['promote_id'=>$promote_id])->find();
        $operate1 = $operate2 = 0;
        // 启动事务
        Db::startTrans();
        try{
            if(empty($tab_promote_settlement_time_info)){
                // 新添加信息
                $operate1 = Db::table('tab_promote_settlement_time')
                ->insert([
                    'promote_id'=>$promote_id,
                    'promote_account'=>$promote_account,
                    'time_type'=>0,  // 归档时间类型0: 按天, 1:按每月的几号
                    'day_period'=>$day_period,
                    'create_time'=>$d_time,
                    'update_time'=>$d_time,
                    'next_count_time'=>strtotime(date('Y-m-d')) + $day_period * 24 * 60 *60,
                ]);

            }else{
                // 更新信息
                $operate1 = Db::table('tab_promote_settlement_time')
                ->where(['promote_id'=>$promote_id])
                ->update([
                    'promote_account'=>$promote_account,
                    'time_type'=>0,  // 归档时间类型0: 按天, 1:按每月的几号
                    'day_period'=>$day_period,
                    'create_time'=>$d_time,
                    'update_time'=>$d_time,
                    'next_count_time'=>strtotime(date('Y-m-d')) + $day_period * 24 * 60 *60,  // 只要修改了结算周期,都按照当前的时间重新计算下次结算时间
                ]);
            }
            // 同步更新渠道表
            $operate2 = Db::table('tab_promote')->where(['id'=>$promote_id])->update(['settlement_day_period'=>$day_period]);
            // 提交事务
            Db::commit();
            return json(['code'=>1, 'msg'=>'修改成功!','data'=>[]]);
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>-1, 'msg'=>'修改失败!请稍后再试','data'=>[]]);
        }

    }

    /**
     * 删除未审核的渠道
     * by:byh-2021-8-3 16:21:17
     */
    public function del()
    {
        $param = $this->request->param();
        if(empty($param['ids'])){
            $this -> error('发生错误');
        }
        //查询渠道账号是否未审核状态
        $model = new PromoteModel();
        $map['id'] = $param['ids'];
        $map['status'] = 0;
        $promote = $model->field('id,status')->where($map)->find();
        if(empty($promote)){
            $this -> error('渠道不存在或已审核');
        }
        //删除
        $res = $model->where($map)->delete();
        if($res){
            $this -> success('删除成功');
        }
        $this -> error('删除失败');
    }

    /**
     * 修改渠道押金
     *
     * @author: Juncl
     * @time: 2021/09/10 11:59
     */
    public function savePromoteField()
    {
        if($this->request->isPost()){
            $param = $this->request->param();
            if(empty($param['promote_id'])){
                $this->error('渠道为空');
            }
            if(empty($param['field'])){
                $this->error('修改信息不能为空');
            }
            $model = new PromoteModel();
            $map['id'] = $param['promote_id'];
            $save[$param['field']] = $param['value'];
            $result = $model->where($map)->update($save);
            if($result){
                // 同时修改渠道等级表押金
                if($param['field'] == 'cash_money'){
                      $LevelModel = new PromoteLevelModel();
                      $LevelModel->where('promote_id',$param['promote_id'])->update($save);
                      $PromoteLevelLogic = new PromoteLevelLogic();
                      $PromoteLevelLogic->updatePromoteLevel($param['promote_id'],0);
                }
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $this->error('非法请求');
        }
    }

    /**
     * 渠道等级
     *
     * @author: Juncl
     * @time: 2021/09/08 19:27
     */
    public function promote_level()
    {
        if($this->request->isPost()){
            $data = $this->request->param();
            $PromoteLevelLogic = new PromoteLevelLogic();
            $result = $PromoteLevelLogic->savePromoteLevel($data);
            if($result){
                $this->success('渠道等级设置成功');
            }else{
                $this->error('设置失败');
            }
        }
        $data = cmf_get_option('promote_level_set');
        // 默认值
        if(empty($data)){
            $data = [
                ['level_name'=>'','sum_money'=>0,'cash_money'=>0]
            ];
        }
        $this->assign('data',json_encode($data));
        return $this->fetch();
    }

    public function setLevel()
    {
        $PromoteLevelLogic = new PromoteLevelLogic();
        $id = 14;
        $amount = 10;
        $PromoteLevelLogic->setPromoteLevel($id,$amount);
    }

}
