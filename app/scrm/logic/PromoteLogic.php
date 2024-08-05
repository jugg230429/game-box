<?php

namespace app\scrm\logic;

use app\datareport\event\PromoteController as Promote;
use app\promote\model\PromoteModel;
use app\recharge\model\SpendModel;
use think\helper\Time;
use app\scrm\lib\Page;
use think\paginator\driver\Bootstrap;

class PromoteLogic extends BaseLogic
{


    /**
     * @获取推广员数据
     *
     * @author: zsl
     * @since: 2021/8/5 10:00
     */
    public function lists($param)
    {
        try {
            //验证参数
            $page = empty($param['page']) ? '1' : $param['page'];
            $limit = empty($param['limit']) ? '10' : $param['limit'];
            $mPromote = new PromoteModel();
            $field = "id as promote_id,account as promote_account,mobile_phone as mobile,busier_id,status,create_time as register_time,
            last_login_time,promote_level,pattern,register_type,email,balance_coin as balance,real_name";
            $where = [];
            if (!empty($param['promote_id'])) {
                $where['id'] = $param['promote_id'];
            }
            $lists = $mPromote -> field($field) -> where($where) -> paginate($limit, false, ['page' => $page]);
            if (empty($lists)) {
                $this -> data = $lists;
                return true;
            }
            foreach ($lists as &$v) {
                if ($v['status'] == '-1') {
                    $v['status'] = 0;
                }
            }
            $this -> data = $lists;
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }


    /**
     * @获取推广员最近7天数据
     *
     * @author: zsl
     * @since: 2021/8/6 17:59
     */
    public function sevenDayData($param)
    {

        try {
            if (empty($param['promote_id'])) {
                $this -> errorMsg = '参数错误';
                return false;
            }
            $starttime = date("Y-m-d", mktime(0, 0, 0, date('m'), date('d') - 6, date('Y')));
            $endtime = date("Y-m-d", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
            $promote_id = array_column(get_song_promote_lists($param['promote_id']), 'id');
            $promote_id[] = $param['promote_id'];
            $promoteevent = new Promote();
            $new_data = $promoteevent -> promote_data($starttime, $endtime, $promote_id)['total_data'];
            $data = [];
            if (!empty($new_data)) {
                $data['new_user'] = $new_data['new_register_user'];
                $data['active_user'] = $new_data['active_user'];
                $data['pay_user'] = $new_data['pay_user'];
                $data['new_pay_user'] = $new_data['new_pay_user'];
                $data['total_amount'] = $new_data['total_pay'];
                if (!empty($new_data['new_register_user'])) {
                    $data['total_rate'] = (round($new_data['pay_user'] / $new_data['new_register_user'], 2) * 100) . '%';
                } else {
                    $data['total_rate'] = '0%';
                }
            } else {
                $data['new_user'] = 0;
                $data['active_user'] = 0;
                $data['pay_user'] = 0;
                $data['new_pay_user'] = 0;
                $data['total_amount'] = 0;
                $data['total_rate'] = '0%';
            }
            $this -> data = $data;
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }

    }


    /**
     * @修改渠道密码
     *
     * @author: zsl
     * @since: 2021/8/20 11:57
     */
    public function changePassword($param)
    {
        if (empty($param['promote_id'])) {
            $this -> errorMsg = '参数promote_id不能为空';
            return false;
        }
        if (empty($param['password']) || strlen($param['password']) < 6 || strlen($param['password']) > 32) {
            $this -> errorMsg = '请输入长度为6-32位密码';
            return false;
        }
        try {
            $mPromote = new PromoteModel();
            $promotInfo = $mPromote -> where(['id' => $param['promote_id']]) -> find();
            if (empty($promotInfo)) {
                $this -> errorMsg = '推广员不存在';
                return false;
            }
            $promotInfo -> password = cmf_password($param['password']);
            $res = $promotInfo -> isUpdate(true) -> save();
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
     * @渠道付费排行榜
     *
     * @author: zsl
     * @since: 2021/8/9 15:43
     */
    public function payRank($param)
    {
        //验证参数
        $page = empty($param['page']) ? '1' : $param['page'];
        $limit = empty($param['limit']) ? '10' : $param['limit'];
        $choose = empty($param['sort']) ? 'total' : $param['sort'];
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
        $where['promote_id'] = ['neq', 0];
        //获取总排行
        $totalList = $mSpend -> field($field) -> where($where) -> group('promote_id') -> order('total_pay_amount desc')
            ->
        limit(100) -> select();
        //获取今日排行
        $today = Time ::today();
        $where['pay_time'] = ['between', [$today[0], $today[1]]];
        $todayList = $mSpend -> field($field) -> where($where) -> group('promote_id') -> order('total_pay_amount desc') -> limit(100) -> select();
        //获取昨日排行
        $yesterday = Time ::yesterday();
        $where['pay_time'] = ['between', [$yesterday[0], $yesterday[1]]];
        $yesterdayList = $mSpend -> field($field) -> where($where) -> group('promote_id') -> order('total_pay_amount desc') -> limit(100) -> select();
        //获取本周排行
        $week = Time ::week();
        $where['pay_time'] = ['between', [$week[0], $week[1]]];
        $weekList = $mSpend -> field($field) -> where($where) -> group('promote_id') -> order('total_pay_amount desc') -> limit(100) -> select();
        //获取本月排行
        $month = Time ::month();
        $where['pay_time'] = ['between', [$month[0], $month[1]]];
        $monthList = $mSpend -> field($field) -> where($where) -> group('promote_id') -> order('total_pay_amount desc') -> limit(100) -> select();
        //生成排行榜
        $var = $choose . 'List';
        foreach ($$var as $k => &$v) {
            foreach ($sum as $s) {
                $vars = $s . 'List';
                if($$vars->isEmpty()){
                    $v['' . $s . '_rank'] = '--';
                }
                foreach ($$vars as $key => $vos) {
                    if ($v['promote_id'] == $vos['promote_id']) {
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
                if (strpos($v['promote_id'], $param['keywords']) === false && strpos($v['promote_account'], $param['keywords']) === false) {
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

}
