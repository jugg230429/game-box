<?php

namespace app\btwelfare\logic;

use app\btwelfare\model\BtPropModel;
use app\btwelfare\model\BtWelfareModel;
use app\btwelfare\model\BtWelfareMonthcardModel;
use app\game\model\ServerModel;
use app\promote\model\PromoteModel;

class MonthCardLogic extends BaseLogic
{


    protected $mMonthCard;
    protected $mBtWelfare;
    protected $mBtProp;

    public function __construct()
    {
        parent ::__construct();
        $this -> mMonthCard = new BtWelfareMonthcardModel();
        $this -> mBtWelfare = new BtWelfareModel();
        $this -> mBtProp = new BtPropModel();
    }


    public function adminLists($param, $summary_data = false)
    {
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $where = [];
        if (!empty($param['promote_id'])) {
            $where['promote_id'] = $param['promote_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        if (!empty($param['user_account'])) {
            $where['user_account'] = ['like', '%' . $param['user_account'] . '%'];
        }
        if (!empty($param['game_player_name'])) {
            $where['game_player_name'] = ['like', '%' . $param['game_player_name'] . '%'];
        }
        if ($param['status'] === '0' || $param['status'] === '1') {
            $where['status'] = $param['status'];
        }

        if (!empty($param['server_id'])) {
            $mServer = new ServerModel();
            $server_num = $mServer -> where(['id' => $param['server_id']]) -> value('server_num');
            if (!empty($server_num)) {
                $where['server_id'] = $server_num;
            }
        }
        if (!empty($param['game_player_id'])) {
            $where['game_player_id'] = $param['game_player_id'];
        }

        if ($param['login_start_time'] && $param['login_end_time']) {
            $where['login_time'] = ['between', [strtotime($param['login_start_time']), strtotime($param['login_end_time']) + 86399]];
        } elseif ($param['login_end_time']) {
            $where['login_time'] = ['lt', strtotime($param['login_end_time']) + 86400];
        } elseif ($param['login_start_time']) {
            $where['login_time'] = ['egt', strtotime($param['login_start_time'])];
        }
        // 渠道独占-------- START
        $promote_id = PID;
        $map_forbid = [];
        if(!empty($promote_id)){
            $onlyForPromote = game_only_for_promote($promote_id);
            $forbidGameids = $onlyForPromote['forbid_game_ids'];
            if(!empty($forbidGameids)){
                if(!empty($where['game_id'])){
                    $where['game_id'] = [['=',$where['game_id']],['not in',$forbidGameids]];
                }else{
                    $where['game_id'] = ['not in',$forbidGameids];
                }
            }
        }
        // 渠道独占-------- END

        $lists = $this -> mMonthCard
                -> field('id,user_id,game_id,game_name,server_id,server_name,game_player_id,game_player_name,promote_id,promote_name,status,total_number
                ,already_send_num,gt_six_four_eight,gt_thousand_of_day,first_six_four_eight_time,user_account,last_send_time,expire_time')
                -> where($where)
                ->where($map_forbid)  // 渠道独占
                -> order('create_time desc,id desc')
                -> paginate($row, false, ['query' => $param]);
        if (false === $summary_data) {
            //不需要汇总数据,直接返回数据列表
            return $lists;
        } else {
            //需要汇总数据
            $res_total = $this -> mMonthCard
                    -> field('sum(`total_number`) as tt_number,sum(`already_send_num`) as tt_already_send_num')
                    -> where($where)
                    ->where($map_forbid)  // 渠道独占
                    -> find();
            $res_total['total_days'] = $res_total['tt_number'] * 30;
            return [
                    'lists' => $lists,
                    'res_total' => $res_total,
            ];
        }


    }


    /**
     * @生成周卡数据
     *
     * @author: zsl
     * @since: 2021/1/15 15:27
     */
    public function buildData($param = [])
    {
        //获取渠道id
        $userInfo = get_user_entity($param['user_id'], false, 'puid,promote_id');
        if ($userInfo['puid'] == 0) {
            $param['promote_id'] = $userInfo['promote_id'];
        } else {
            $param['promote_id'] = get_user_entity($userInfo['puid'], false, 'promote_id')['promote_id'];
        }
        //检查是否需要发放
        if (!$welfareInfo = $this -> checkExist($param)) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '无需发放';
            return $this -> result;
        }
        //查询角色周卡数据
        $where = [];
        $where['user_id'] = $param['user_id'];
        $where['game_id'] = $param['game_id'];
        $where['game_player_id'] = $param['game_player_id'];
        $monthCardRecord = $this -> mMonthCard -> where($where) -> find();
        if (empty($monthCardRecord)) {
            //生成月卡数据
            $this -> mMonthCard -> user_id = $param['user_id'];
            $this -> mMonthCard -> user_account = get_user_entity($param['user_id'], false, 'account')['account'];
            $this -> mMonthCard -> game_id = $param['game_id'];
            $this -> mMonthCard -> game_name = get_game_name($param['game_id']);
            $this -> mMonthCard -> server_id = $param['server_id'];
            $this -> mMonthCard -> server_name = $param['server_name'];
            $this -> mMonthCard -> game_player_id = $param['game_player_id'];
            $this -> mMonthCard -> game_player_name = $param['game_player_name'];
            $this -> mMonthCard -> promote_id = $param['promote_id'];
            $this -> mMonthCard -> promote_name = get_promote_name($param['promote_id']);
            $this -> mMonthCard -> pay_time = $param['pay_time'];
            //查询道具内容
            $this -> mMonthCard -> send_prop = $this -> getPropContent($welfareInfo -> month_card_prop_ids);
            $this -> mMonthCard -> total_number = 1;//累计获得周卡数
            $this -> mMonthCard -> already_send_num = 0;//已发放天数
            $this -> mMonthCard -> gt_six_four_eight = $param['pay_amount'] >= 648 ? 1 : 0;//单笔大于648次数
            $this -> mMonthCard -> gt_thousand_of_day = $param['day_amount'] >= 1000 ? 1 : 0;//单笔大于1000次数
            $this -> mMonthCard -> expire_time = time() + 86400 * 30;//过期时间
            $this -> mMonthCard -> last_send_time = 0;//上次发放时间
            $this -> mMonthCard -> status = 0;
            //首次648日期
            if ($param['pay_amount'] >= 648) {
                $this -> mMonthCard -> first_six_four_eight_time = time();
            }
            $result = $this -> mMonthCard -> allowField(true) -> isUpdate(false) -> save();
            if (false === $result) {
                $this -> result['code'] = 0;
                $this -> result['msg'] = '发生错误,发放失败';
            }
            $this -> result['msg'] = '发放成功';
            //获取记录id
            $recordId = $this -> mMonthCard -> getLastInsID();
        } else {
            //判断是否过期 未过期: 从过期时间+30天  已过期: 从当前时间+30天
            if ($monthCardRecord['expire_time'] > time()) {
                //未过期
                $monthCardRecord -> expire_time = $monthCardRecord['expire_time'] + 86400 * 30;
            }
            if ($monthCardRecord['expire_time'] < time()) {
                //已过期
                $monthCardRecord -> expire_time = time() + 86400 * 30;
            }
            //更新道具内容
            $monthCardRecord -> send_prop = $this -> getPropContent($welfareInfo -> month_card_prop_ids);
            $monthCardRecord -> total_number = $monthCardRecord -> total_number + 1;//累计获得周卡数
            if ($param['pay_amount'] >= 648) {
                $monthCardRecord -> gt_six_four_eight = $monthCardRecord -> gt_six_four_eight + 1;//单笔大于648次数
            }
            if ($param['day_amount'] >= 1000) {
                $monthCardRecord -> gt_thousand_of_day = $monthCardRecord -> gt_thousand_of_day + 1;//单笔大于1000次数
            }
            //首次648日期
            if ($param['pay_amount'] >= 648 && empty($monthCardRecord['first_six_four_eight_time'])) {
                $this -> mMonthCard -> first_six_four_eight_time = time();
            }
            $monthCardRecord -> save();
            //获取记录id
            $recordId = $monthCardRecord -> id;
        }
        //自动发放
        $mPromote = new PromoteModel();
        $bt_welfare_register_auto = $mPromote -> where(['id' => $param['promote_id']]) -> value('bt_welfare_month_auto');
        if ($bt_welfare_register_auto == '1') {
            try {
                $this -> send($recordId);
            } catch (\Exception $e) {
                //自动发放失败
            }
        }
        $this -> result['msg'] = '发放成功';
        return $this -> result;
    }


    /**
     * @检查是否需要生成
     *
     * @author: zsl
     * @since: 2021/1/15 15:27
     */
    public function checkExist($param = [])
    {
        //查询该条件下是否配置游戏福利
        $where = [];
        $where['w.game_id'] = $param['game_id'];
        $where['w.status'] = 1;
        $where['w.start_time'] = ['lt', time()];
        $where['w.end_time'] = [['gt', time()], ['eq', 0], 'or'];
        $where['wp.promote_id'] = $param['promote_id'];
        $welfareInfo = $this -> mBtWelfare -> alias('w')
                -> field('w.id,w.month_card_money,w.month_card_prop_ids')
                -> join(['tab_bt_welfare_promote' => 'wp'], 'w.id = wp.bt_welfare_id', 'left')
                -> where($where)
                -> find();
        if (empty($welfareInfo) || $welfareInfo['month_card_money'] == 0 || empty($welfareInfo['month_card_prop_ids'])) {
            return false;
        }
        //查询单笔充值是否满足条件
        if ($param['pay_amount'] < $welfareInfo['month_card_money']) {
            return false;
        }
        return $welfareInfo;
    }


    public function send($id)
    {

        $info = $this -> mMonthCard -> find($id);
        if ($info['status'] == '1') {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '今日已发放';
            return $this -> result;
        }
        //判断过期时间
        if ($info['expire_time'] < time()) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '月卡已过期';
            return $this -> result;
        }
        //验证sign
        $apiPath = "\app\btwelfare\api\Game" . $info['game_id'] . "Api";
        $apiObject = new $apiPath;
        //获取待发放道具
        $propList = json_decode($info['send_prop'], true);
        if (empty($propList)) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '暂无道具';
            return $this -> result;
        }
        $this -> mMonthCard -> startTrans();
        try {
            //发放道具
            foreach ($propList as $v) {
                $data = $info -> toArray();
                $data['prop_id'] = $v['prop_tag'];
                $data['prop_num'] = $v['number'];
                $sendRes = $apiObject -> send($data);
                if (false === $sendRes) {
                    $this -> mMonthCard -> rollback();
                    $this -> result['code'] = 0;
                    $this -> result['msg'] = '发放失败';
                    return $this -> result;
                }
            }
            //修改发放状态
            $info -> status = 1;
            $info -> already_send_num = $info -> already_send_num + 1;
            $info -> last_send_time = time();
            $info -> isUpdate(true) -> save();
            $this -> mMonthCard -> commit();
            return $this -> result;
        } catch (\Exception $e) {
            $this -> mMonthCard -> rollback();
            $this -> result['code'] = 0;
            $this -> result['msg'] = '发生错误,发放失败' . $e -> getMessage();
        }
        return $this -> result;
    }


}