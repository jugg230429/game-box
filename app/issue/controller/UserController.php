<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */

namespace app\issue\controller;

use app\issue\logic\StatLogic;
use app\issue\logic\UserLogic;
use app\issue\model\SpendModel;
use app\issue\model\UserModel;
use app\issue\validate\OpenUserValidate as UserValidate;
use cmf\controller\AdminBaseController;
use app\issue\logic\OpenUserLogic as logicUser;
use app\api\GameApi;
use think\Db;
use think\helper\Time;

class UserController extends AdminBaseController
{
    //判断权限
    public function __construct()
    {
        parent ::__construct();
//        if (AUTH_ISSUE != 1) {
//            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
//                $this->error('请购买分发权限');
//            } else {
//                $this->error('请购买分发权限', url('admin/main/index'));
//            };
//        }
    }

    public function lists(logicUser $userLogic)
    {
        $getData = request() -> get();
        $data = $userLogic -> page($getData);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['account'] = get_ys_string($v5['account'],$ys_show_admin['account_show_admin']);
            }
        }

        // 获取分页显示
        $page = $data -> render();
        $this -> assign('page', $page);
        $this -> assign("data_lists", $data);
        return $this -> fetch();
    }

    public function add(logicUser $userLogic)
    {
        if (request() -> isPost()) {
            $postData = request() -> post();
            $validate = new UserValidate();
            $result = $validate -> scene('create') -> check($postData);
            if ($result == false) {
                $this -> error($validate -> getError());
            }
            $userLogic -> add($postData);
            $this -> success('添加成功', url('lists'));
        } else {
            return $this -> fetch();
        }
    }


    public function edit(logicUser $userLogic)
    {
        $param = $this -> request -> param();
        if (request() -> isPost()) {
            if (!empty($param['password'])) {
                if (!ctype_alnum($param['password']) || strlen($param['password']) < 6 || strlen($param['password']) > 30) {
                    $this -> error('密码为6-30位字母或数字组合');
                }
            }
            $result = $userLogic -> edit($param);
            if (false === $result) {
                $this -> error('保存失败');
            }
            $this -> success('保存成功');

        } else {
            if (empty($param['id'])) {
                $this -> error('参数错误');
            }
            $info = $userLogic -> info($param['id']);
            if (empty($info)) {
                $this -> error('用户不存在');
            }

            // 判断当前管理员是否有权限显示完成整手机号或完整账号
            $ys_show_admin = get_admin_privicy_two_value();
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $info['account'] = get_ys_string($info['account'],$ys_show_admin['account_show_admin']);
            }
            $this -> assign('info', $info);
            return $this -> fetch();
        }
    }


    public function changestatus()
    {

        $data = $this -> request -> param();
        $where = [];
        $where['id'] = $data['ids'];
        if (isset($data['status'])) {
            $result = Db ::table('tab_issue_open_user') -> where($where) -> setField('status', $data['status']);
        }
        if (isset($data['auth_status'])) {
            $result = Db ::table('tab_issue_open_user') -> where($where) -> setField('auth_status', $data['auth_status']);
        }
        if (false === $result) {
            $this -> error('操作失败');
        }
        $this -> success('操作成功');

    }

    /**
     * @玩家注册
     *
     * @author: zsl
     * @since: 2020/7/21 11:07
     */
    public function register(StatLogic $lStat)
    {
        $param = $this -> request -> param();
        $data = $lStat -> user_lists($param);
        $page = $data -> render();
        $this -> assign("data_lists", $data);
        $this -> assign("page", $page);
        return $this -> fetch();
    }


    /**
     * @修改玩家状态
     *
     * @author: zsl
     * @since: 2020/7/21 14:15
     */
    public function changeUserStatus()
    {
        $data = $this -> request -> param();
        $where = [];
        $where['id'] = $data['id'];
        if (isset($data['lock_status'])) {
            $result = Db ::table('tab_issue_user') -> where($where) -> setField('lock_status', $data['lock_status']);
        }
        if (false === $result) {
            $this -> error('操作失败');
        }
        $this -> success('操作成功');
    }


    /**
     * @玩家充值列表
     *
     * @author: zsl
     * @since: 2020/7/21 14:16
     */
    public function recharge(StatLogic $lStat)
    {
        $getData = $this -> request -> param();
        if(empty($getData['type'])){
            if(true === is_buy_h5_issue()){
                $type = input('type', 1);
            }elseif(true === is_buy_sy_issue()){
                $type = input('type', 2);
            }else{
                $type = input('type', 3);
            }
        }else{
            $type = $getData['type'];
        }
        $getData['sdk_version'] = $type == '1' ? 3 :($type==2?['lt', 3]:4) ;
        $getData['is_admin'] = 1;
        $data = $lStat -> recharge_lists($getData);
        $page = $data -> render();

        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach ($data as $k5 => $v5) {
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
            //平台结算模式
            $data[$k5]['settle_type'] = get_user_settle_type($v5['open_user_id']);
        }

        $this -> assign("data_lists", $data);
        $this -> assign("page", $page);
        $this -> assign("type", $type);
        return $this -> fetch();
    }


    /**
     * @充值汇总
     *
     * @author: zsl
     * @since: 2020/7/21 17:12
     */
    public function paySummary()
    {
        $param = $this -> request -> param();
        $lStat = new StatLogic();
        if (empty($param['start_time'])) {
            $param['start_time'] = date("Y-m-d", Time ::daysAgo(10));
        }
        if (empty($param['end_time'])) {
            $param['end_time'] = date("Y-m-d", Time ::daysAgo(1));
        }
        if(empty($param['type'])){
            if(true === is_buy_h5_issue()){
                $param['type'] = 1;
            }elseif(true === is_buy_sy_issue()){
                $param['type'] = 2;
            }else{
                $param['type'] = 3;
            }
        }
        $result = $lStat -> paySummary($param);
        $this -> assign('start_time', $param['start_time']);
        $this -> assign('end_time', $param['end_time']);
        $this -> assign('data_lists', $result['data']);
        $this -> assign('type', $param['type']);
        return $this -> fetch();
    }

    /**
     * @函数或方法说明
     * @补发CP
     * @author: 郭家屯
     * @since: 2020/10/23 13:30
     */
    public function repair_cp()
    {
        $id = $this->request->param('id');
        $model = new SpendModel();
        $model->where('id','=',$id);
        $spend = $model->find();
        $param['out_trade_no'] = $spend->pay_order_number;
        $game = new GameApi();
        $result = $game->game_ff_pay_notify($param);
        if($result){
            $spend->pay_game_status = 1;
        }
        if($spend->sdk_version == 3){
            //通知分发
            $logic = new \app\issueh5\logic\PayLogic();
        }elseif($spend->sdk_version == 4){
            //通知分发
            $logic = new \app\issueyy\logic\PayLogic();
        }else{
            //通知分发
            $logic = new \app\issuesy\logic\PayLogic();
        }
        $notice_status = $logic->pay_ff_notice($spend->toArray());
        if($notice_status){
            $spend->pay_ff_status = 1;
        }
        $result = $spend->save();
        if($result !== false){
            $this->success('通知成功');
        }else{
            $this->error('通知失败');
        }
    }

    /**
     * @函数或方法说明
     * @补发分发
     * @author: 郭家屯
     * @since: 2020/10/23 13:30
     */
    public function repair_ff()
    {
        $id = $this->request->param('id');
        $model = new SpendModel();
        $model->where('id','=',$id);
        $spend = $model->find();
        if($spend->sdk_version == 3){
            $logic = new \app\issueh5\logic\PayLogic();
        }elseif($spend->sdk_version == 4){
            $logic = new \app\issueyy\logic\PayLogic();
        }else{
            $logic = new \app\issuesy\logic\PayLogic();
        }
        $notice_status = $logic->pay_ff_notice($spend->toArray());
        if($notice_status){
            $spend->pay_ff_status = 1;
            $result = $spend->save();
        }else{
            $result = false;
        }
        if($result !== false){
            $this->success('补单成功');
        }else{
            $this->error('补单失败');
        }
    }

    /**
     * @函数或方法说明
     * @设置分成比例
     * @author: 郭家屯
     * @since: 2020/10/27 10:07
     */
    public function setRatio()
    {
        $id = $this->request->param('id');
        $value = $this->request->param('value');
        if($value < 0 || $value > 100){
            $this->error('分成比例设置错误');
        }
        $model = new SpendModel();
        $model->field('id,pay_amount');
        $model->where('id',$id);
        $spend = $model->find();
        if(!$id || !$spend){
            $this->error('订单不存在');
        }
        $spend->ratio = $value;
        $spend->ratio_money = round($spend->pay_amount * $value /100 ,2);
        $result = $spend->save();
        if($result !== false){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }

    /**
     * @函数或方法说明
     * @设置参与分成
     * @author: 郭家屯
     * @since: 2020/10/27 15:34
     */
    public function changeCheck()
    {
        $ids = $this->request->param('ids/a');
        $status = $this->request->param('status');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        $model = new SpendModel();
        $model->where('id','in',$ids);
        if($status == 1){
            $model->where('is_check','in',[0,2]);
            $model->is_check = 1;
        }else{
            $model->where('is_check',0);
            $model->is_check = 2;
        }
        $result = $model->isUpdate(true)->save();
        if($result !== false){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }


    /**
     * @用户详情
     *
     * @author: zsl
     * @since: 2021/8/12 19:16
     */
    public function detail()
    {
        $param = $this -> request -> param();
        $lUser = new UserLogic();
        $info = $lUser -> info($param);
        if (empty($info)) {
            $this -> error('用户不存在');
        }
        $this -> assign('info', $info);
        return $this -> fetch();
//        //激活游戏记录
//        $activationLists = $lUser -> activationRecord($param);
//        $this -> assign('activation_lists', $activationLists);
//        //近期登陆记录
//        $loginRecordLists = $lUser -> loginRecord($param);
//        $this -> assign('login_record_lists', $loginRecordLists);
//        //用户付费记录
//        $spendRecordLists = $lUser -> spendRecord($param);
//        $this -> assign('spend_record_lists', $spendRecordLists);
//        return $this -> fetch('issue_user_detail');
    }


    /**
     * @激活游戏记录
     *
     * @author: zsl
     * @since: 2021/8/12 21:27
     */
    public function activationRecord()
    {
        $param = $this -> request -> param();
        $lUser = new UserLogic();
        $lists = $lUser -> activationRecord($param);
        $this -> assign('data_lists', $lists);
        return $this -> fetch();
    }


    /**
     * @近期登陆记录
     *
     * @author: zsl
     * @since: 2021/8/12 21:37
     */
    public function loginRecord()
    {

        $param = $this -> request -> param();
        $lUser = new UserLogic();
        $lists = $lUser -> loginRecord($param);
        $this -> assign('data_lists', $lists);
        return $this -> fetch();
    }


    /**
     * @用户付费记录
     *
     * @author: zsl
     * @since: 2021/8/13 9:28
     */
    public function spendRecord()
    {
        $param = $this -> request -> param();
        $lUser = new UserLogic();
        $lists = $lUser -> spendRecord($param);
        $this -> assign('data_lists', $lists);
        return $this -> fetch();
    }


    /**
     * @分发用户绑定
     *
     * @author: zsl
     * @since: 2021/8/18 20:42
     */
    public function bind()
    {
        $param = $this -> request -> param();
        $lUser = new UserLogic();
        if ($this -> request -> isPost()) {
            $result = $lUser -> bind($param);
            if ($result['code'] == 0) {
                $this -> error($result['msg']);
            }
            $this -> success($result['msg'], url('issue/user/register'));
            return $result;
        }
        //查询绑定用户列表
        $lists = $lUser -> get_bind_lists($param);
        $this -> assign('lists', $lists);
        //获取用户信息
        $info = UserModel ::get($param['user_id']);
        $this -> assign('info', $info);
        return $this -> fetch();
    }



}
