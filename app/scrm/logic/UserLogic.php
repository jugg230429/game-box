<?php

namespace app\scrm\logic;

use app\common\controller\BaseController;
use app\member\model\UserMendModel;
use app\member\model\UserModel;
use app\member\model\UserPlayInfoModel;
use app\member\model\UserPlayModel;
use app\promote\model\PromoteModel;
use app\recharge\controller\SpendController;
use app\recharge\model\SpendModel;
use app\scrm\lib\Page;
use think\helper\Time;
use app\scrm\lib\Time as libTime;
use think\paginator\driver\Bootstrap;

class UserLogic extends BaseLogic
{


    /**
     * @获取用户数据
     *
     * @author: zsl
     * @since: 2021/8/5 9:02
     */
    public function lists($param)
    {
        try {
            //验证参数
            $page = empty($param['page']) ? '1' : $param['page'];
            $limit = empty($param['limit']) ? '10' : $param['limit'];
            $mUser = new UserModel();
            $field = "id as user_id,account as user_account,nickname as user_nickname,phone as mobile,promote_id,lock_status as status,
            register_time,register_ip,login_time as last_login_time,login_ip as last_login_ip,balance";
            $where = [];
            if (!empty($param['user_id'])) {
                $where['id'] = $param['user_id'];
            }
            $where['puid'] = 0;
            $lists = $mUser -> field($field) -> where($where) -> paginate($limit, '', ['page' => $page]);
            if (empty($lists)) {
                $this -> data = $lists;
                return true;
            }
            $mSpend = new SpendModel();
            $mUserPlay = new UserPlayModel();
            foreach ($lists as &$v) {
                //查询玩家付费金额
                $where = [];
                $where['user_id'] = $v -> user_id;
                $where['pay_status'] = 1;
                $game_order_total_amount = $mSpend -> where($where) -> sum('pay_amount');
                $game_order_total_num = $mSpend -> where($where) -> count();
                $game_order_last_amount = $mSpend -> where($where) -> order('pay_time desc') -> value('pay_time');
                //查询绑币余额
                $where = [];
                $where['user_id'] = $v -> user_id;
                $where['is_del'] = 0;
                $bind_balance = $mUserPlay -> where($where) -> sum('bind_balance');
                $v['game_order_total_amount'] = empty($game_order_total_amount) ? 0 : $game_order_total_amount;
                $v['game_order_total_num'] = empty($game_order_total_num) ? 0 : $game_order_total_num;
                $v['game_order_last_amount'] = empty($game_order_last_amount) ? '' : $game_order_last_amount;
                $v['bind_balance'] = empty($bind_balance) ? 0 : $bind_balance;

            }
            $this -> data = $lists;
            return true;

        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }

    }


    /**
     * @获取玩家绑币数据
     *
     * @author: zsl
     * @since: 2021/8/6 17:04
     */
    public function bindBalance($param)
    {
        try {
            //验证参数
            if (empty($param['user_id'])) {
                $this -> errorMsg = '参数错误';
                return false;
            }
            $page = empty($param['page']) ? '1' : $param['page'];
            $limit = empty($param['limit']) ? '10' : $param['limit'];
            $mUserPlay = new UserPlayModel;
            $field = "game_id,game_name,bind_balance";
            $where = [];
            $where['user_id'] = $param['user_id'];
            $where['bind_balance'] = ['gt', 0];
            $lists = $mUserPlay -> field($field) -> where($where) -> paginate($limit, '', ['page' => $page]);
            if (empty($lists)) {
                $this -> data = $lists;
                return true;
            }
            $this -> data = $lists;
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }

    }

    /**
     * @用户付费排行榜
     *
     * @author: zsl
     * @since: 2021/8/9 15:43
     */
    public function payRank($param)
    {
        //验证参数
        $page = empty($param['page']) ? '1' : $param['page'];
        $limit = empty($param['limit']) ? '10' : $param['limit'];
        $choose = empty($param['sort']) ? 'today' : $param['sort'];
        $order = empty($param['order']) ? 'asc' : $param['order'];
        $promote_type = empty($param['promote_type']) ? 0 : $param['promote_type'];
        $sum = ['today', 'yesterday', 'week', 'month', 'total'];
        if (!in_array($choose, $sum)) {
            $this -> errorMsg = '排序字段错误';
            return false;
        }
        $mSpend = new SpendModel();
        $field = "user_id,user_account,promote_id,promote_account,sum(pay_amount) as total_pay_amount";
        $where = [];
        $where['pay_status'] = 1;
        if ($promote_type == '1') {
            //渠道充值用户
            $where['promote_id'] = ['neq', 0];
        } elseif ($promote_type == '2') {
            //官方充值用户
            $where['promote_id'] = 0;
        }
        //获取总排行
        $totalList = $mSpend -> field($field) -> where($where) -> group('user_id') -> order('total_pay_amount desc') -> limit(100) -> select();
        //获取今日排行
        $today = Time ::today();
        $where['pay_time'] = ['between', [$today[0], $today[1]]];
        $todayList = $mSpend -> field($field) -> where($where) -> group('user_id') -> order('total_pay_amount desc') -> limit(100) -> select();
        //获取昨日排行
        $yesterday = Time ::yesterday();
        $where['pay_time'] = ['between', [$yesterday[0], $yesterday[1]]];
        $yesterdayList = $mSpend -> field($field) -> where($where) -> group('user_id') -> order('total_pay_amount desc') -> limit(100) -> select();
        //获取本周排行
        $week = Time ::week();
        $where['pay_time'] = ['between', [$week[0], $week[1]]];
        $weekList = $mSpend -> field($field) -> where($where) -> group('user_id') -> order('total_pay_amount desc') -> limit(100) -> select();
        //获取本月排行
        $month = Time ::month();
        $where['pay_time'] = ['between', [$month[0], $month[1]]];
        $monthList = $mSpend -> field($field) -> where($where) -> group('user_id') -> order('total_pay_amount desc') -> limit(100) -> select();
        //生成排行榜
        $var = $choose . 'List';
        foreach ($$var as $k => &$v) {
            foreach ($sum as $s) {
                $vars = $s . 'List';
                if($$vars->isEmpty()){
                    $v['' . $s . '_rank'] = '--';
                }
                foreach ($$vars as $key => $vos) {
                    if ($v['user_id'] == $vos['user_id']) {
                        $v['' . $s . '_rank'] = $key + 1;
                        break;
                    } else {
                        $v['' . $s . '_rank'] = '--';
                    }
                }
            }
        }
        unset($v);
        if (!empty($param['keywords'])) {
            foreach ($$var as $k => $v) {
                if (strpos($v['user_id'], $param['keywords']) === false && strpos($v['user_account'], $param['keywords']) === false) {
                    unset($$var[$k]);
                }
            }
        }
//        $$var = array_merge($$var);
        //数据分页处理
        $lists = Page ::pageByArr($$var -> toArray(), $limit, $page, $order, $choose . '_rank');
        $pageLists = Bootstrap ::make($lists, $limit, $page, count($$var));
        $this -> data = $pageLists;
        return true;
    }


    /**
     * @修改用户密码
     *
     * @author: zsl
     * @since: 2021/8/20 9:07
     */
    public function changePassword($param)
    {
        if (empty($param['user_id'])) {
            $this -> errorMsg = '参数user_id不能为空';
            return false;
        }
        if (empty($param['password']) || strlen($param['password']) < 6 || strlen($param['password']) > 32) {
            $this -> errorMsg = '请输入长度为6-32位密码';
            return false;
        }
        try {
            $mUser = new UserModel();
            $userInfo = $mUser -> where(['id' => $param['user_id'], 'puid' => 0]) -> find();
            if (empty($userInfo)) {
                $this -> errorMsg = '用户不存在';
                return false;
            }
            $userInfo -> password = cmf_password($param['password']);
            $res = $userInfo -> isUpdate(true) -> save();
            if (false === $res) {
                $this -> errorMsg = '修改失败,请稍后重试';
                return false;
            }
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }


    /**
     * @修改用户手机号
     *
     * @author: zsl
     * @since: 2021/8/20 9:43
     */
    public function changeMobile($param)
    {
        if (empty($param['user_id'])) {
            $this -> errorMsg = '参数user_id不能为空';
            return false;
        }
        if (empty($param['mobile']) || !cmf_check_mobile($param['mobile'])) {
            $this -> errorMsg = '请输入正确的手机号';
            return false;
        }
        try {
            $mUser = new UserModel();
            $userInfo = $mUser -> where(['id' => $param['user_id'], 'puid' => 0]) -> find();
            if (empty($userInfo)) {
                $this -> errorMsg = '用户不存在';
                return false;
            }
            $userInfo -> phone = $param['mobile'];
            $res = $userInfo -> isUpdate(true) -> save();
            if (false === $res) {
                $this -> errorMsg = '修改失败,请稍后重试';
                return false;
            }
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }


    /**
     * @用户补链
     *
     * @author: zsl
     * @since: 2021/8/20 10:08
     */
    public function mend($param)
    {

        if (empty($param['user_id']) || empty($param['promote_id'])) {
            $this -> errorMsg = '参数错误';
            return false;
        }
        $mUser = new UserModel();
        $mUser -> startTrans();
        try {
            $where = [];
            $where['id'] = $param['user_id'];
            $where['puid'] = 0;
            $userInfo = $mUser -> where($where) -> find();
            if (empty($userInfo)) {
                $this -> errorMsg = '用户不存在';
                return false;
            }
            $mPromote = new PromoteModel();
            $promoteInfo = $mPromote -> field('id,account,parent_id,parent_name') -> where(['id' => $param['promote_id']]) -> find();
            if (empty($promoteInfo)) {
                $this -> errorMsg = '推广员不存在';
                return false;
            }
            //写入补单记录
            $mUserMend = new UserMendModel();
            $mUserMend -> user_id = $userInfo -> id;
            $mUserMend -> user_account = $userInfo -> account;
            $mUserMend -> promote_id = $userInfo -> promote_id;
            $mUserMend -> promote_account = $userInfo -> promote_account;
            $mUserMend -> promote_id_to = $promoteInfo['id'];
            $mUserMend -> promote_account_to = $promoteInfo['account'];
            $mUserMend -> remark = $param['remark'];
            $mUserMend -> create_time = time();
            $mUserMend -> op_id = 0;
            $mUserMend -> op_account = 'scrm';
            $effective_time = $param['effective_time'];
            $mUserMend -> cut_time = $effective_time;
            $mUserMend -> isUpdate(false) -> save();
            //更新用户表
            $save['promote_id'] = $promoteInfo['id'];
            $save['promote_account'] = $promoteInfo['account'];
            $save['parent_id'] = $promoteInfo['parent_id'];
            $save['parent_name'] = $promoteInfo['parent_name'];
            $mUser -> save($save, ['id' => $userInfo -> id]);
            //更新user_play表
            $mUserPlay = new UserPlayModel();
            $mUserPlay -> allowField(true) -> save($save, ['user_id' => $userInfo -> id]);
            //更新user_play_info表
            $mUserPlayInfo = new UserPlayInfoModel();
            $mUserPlayInfo -> allowField(true) -> save($save, ['puid' => $userInfo -> id]);
            //根据分割时间补单订单
            if (!empty($effective_time)) {
                $base = new BaseController;
                $spend = new SpendModel();
                $map = [];
                $map['tab_spend.pay_time'] = ['gt', $effective_time];
                $map['tab_spend.pay_status'] = 1;
                $map['tab_spend.user_id'] = $userInfo -> id;
                $map['ps.status'] = 0;
                $exend['field'] = 'tab_spend.id as spend_id, ps.id as ps_id,tab_spend.promote_id,tab_spend.promote_account,
                            tab_spend.pay_order_number,ps.status as ps_status,tab_spend.pay_status';
                $exend['join1'][] = ['tab_promote_settlement' => 'ps'];
                $exend['join1'][] = 'ps.pay_order_number = tab_spend.pay_order_number and ps.status=0';
                $exend['join1'][] = 'left';
                $orderLists = $base -> data_list_join_select($spend, $map, $exend);
                if (!empty($orderLists)) {
                    $cSpend = new SpendController();
                    foreach ($orderLists as $orderKey => $orderVal) {
                        if ($orderVal['ps_status'] != '1') {
                            $cSpend -> deal_promote_info($promoteInfo['id'], $orderVal);
                        }
                    }
                }
            }
            $mUser -> commit();
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            $mUser -> rollback();
            return false;
        }
    }

    public function userPay($param)
    {
        try {
            $year = $param['year'];
            $user_ids = $param['user_ids'];
            if (empty($year) || empty($user_ids)) {
                $this -> errorMsg = '参数错误';
                return false;
            }
            $monthArr = libTime ::everyMonth($year);
            $data = [];
            foreach ($user_ids as $k => $user_id) {
                $data[$k]['user_id'] = $user_id;
                $data[$k]['user_account'] = get_user_name($user_id);
                $data[$k]['year'] = $year;
                //获取每月充值
                $mSpend = new SpendModel();
                $field = "user_id,user_account,sum(pay_amount) as total_pay_amount";
                $where['user_id'] = $user_id;
                foreach ($monthArr as $month => $timestamp) {
                    $where['pay_time'] = ['between', [$timestamp['start'], $timestamp['end']]];
                    $monthList = $mSpend -> field($field) -> where($where) -> group('user_id') -> order('total_pay_amount desc') -> find();
                    $data[$k]['recharge'][] = $monthList['total_pay_amount']?:'0.00';
                }
                //累计
                $where['pay_time'] = ['>',0];
                $totalList = $mSpend -> field($field) -> where($where) -> order('total_pay_amount desc') -> find();
                $data[$k]['total_recharge'] = $totalList['total_pay_amount']?:'0.00';
                //今日
                $where['pay_time'] = ['between', [strtotime(date('Y-m-d')), strtotime(date('Y-m-d'))+86399]];
                $todayList = $mSpend -> field($field) -> where($where) -> group('user_id') -> order('total_pay_amount desc') -> find();
                $data[$k]['today_recharge'] = $todayList['total_pay_amount']?:'0.00';
            }
            $this -> data = $data;
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }

}
