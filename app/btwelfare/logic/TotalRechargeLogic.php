<?php

namespace app\btwelfare\logic;

use app\btwelfare\model\BtPropModel;
use app\btwelfare\model\BtWelfareModel;
use app\btwelfare\model\BtWelfareTotalRechargeModel;
use app\game\model\ServerModel;
use app\promote\model\PromoteModel;

class TotalRechargeLogic extends BaseLogic
{


    protected $mTotalRecharge;
    protected $mBtWelfare;
    protected $mBtProp;

    public function __construct()
    {
        parent ::__construct();
        $this -> mTotalRecharge = new BtWelfareTotalRechargeModel();
        $this -> mBtWelfare = new BtWelfareModel();
        $this -> mBtProp = new BtPropModel();
    }


    /**
     * @管理后台列表
     *
     * @since: 2021/1/15 13:57
     * @author: zsl
     */
    public function adminLists($param)
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
        if (!empty($param['game_player_id'])) {
            $where['game_player_id'] = ['like', '%' . $param['game_player_id'] . '%'];
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
        // 渠道独占-------- START
        $promote_id = PID;
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
        $lists = $this -> mTotalRecharge
                -> field('id,user_id,game_id,game_name,server_id,server_name,game_player_id,game_player_name,promote_id,promote_name,pay_time,matched_money,status,user_account')
                -> where($where)
                -> order('create_time desc,id desc')
                -> paginate($row, false, ['query' => $param]);
        return $lists;
    }


    /**
     * @生成累计充值福利待发放数据
     *
     * @author: zsl
     * @since: 2021/1/15 10:32
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
        //生成累计充值福利数据
        foreach ($welfareInfo as $v) {
            $model = new BtWelfareTotalRechargeModel();
            $model -> user_id = $param['user_id'];
            $model -> user_account = get_user_entity($param['user_id'], false, 'account')['account'];
            $model -> game_id = $param['game_id'];
            $model -> game_name = get_game_name($param['game_id']);
            $model -> server_id = $param['server_id'];
            $model -> server_name = $param['server_name'];
            $model -> game_player_id = $param['game_player_id'];
            $model -> game_player_name = $param['game_player_name'];
            $model -> promote_id = $param['promote_id'];
            $model -> promote_name = get_promote_name($param['promote_id']);
            $model -> pay_time = $param['pay_time'];
            $model -> matched_money = $v['money'];
            $model -> total_money = $param['total_amount'];
            //查询道具内容
            $model -> send_prop = $this -> getPropContent(implode(',', $v['prop']));
            $model -> status = 0;
            $model -> allowField(true) -> isUpdate(false) -> save();
            //获取记录id
            $recordId = $model -> getLastInsID();
            //自动发放
            $mPromote = new PromoteModel();
            $bt_welfare_register_auto = $mPromote -> where(['id' => $param['promote_id']]) -> value('bt_welfare_total_auto');
            if ($bt_welfare_register_auto == '1') {
                try {
                    $this -> send($recordId);
                } catch (\Exception $e) {
                    //自动发放失败
                }
            }
        }
        //获取
        $this -> result['msg'] = '发放成功';
        return $this -> result;
    }


    /**
     * @检查是否需要生成
     *
     * @author: zsl
     * @since: 2021/1/14 15:47
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
                -> field('w.id,w.total_recharge_prop')
                -> join(['tab_bt_welfare_promote' => 'wp'], 'w.id = wp.bt_welfare_id', 'left')
                -> where($where)
                -> find();
        if (empty($welfareInfo)) {
            return false;
        }
        $total_recharge_prop = json_decode($welfareInfo['total_recharge_prop'], true);
        if (empty($total_recharge_prop)) {
            return false;
        }
        //查询当前触发累充福利规则
        $matchData = [];
        foreach ($total_recharge_prop as $v) {
            if ($param['total_amount'] >= $v['money']) {
                //过滤已生成数据
                $where = [];
                $where['user_id'] = $param['user_id'];
                $where['game_id'] = $param['game_id'];
                $where['matched_money'] = $v['money'];
                $where['game_player_id'] = $param['game_player_id'];
                $recordInfo = $this -> mTotalRecharge -> where($where) -> find();
                if (empty($recordInfo)) {
                    $matchData[] = $v;
                }
            }
        }
        if (empty($matchData)) {
            return false;
        }
        return $matchData;

    }


    public function send($id)
    {

        $info = $this -> mTotalRecharge -> find($id);
        if ($info['status'] == '1') {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '不可重复发放';
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
        $this -> mTotalRecharge -> startTrans();
        try {
            //发放道具
            foreach ($propList as $v) {
                $data = $info -> toArray();
                $data['prop_id'] = $v['prop_tag'];
                $data['prop_num'] = $v['number'];
                $sendRes = $apiObject -> send($data);
                if (false === $sendRes) {
                    $this -> mTotalRecharge -> rollback();
                    $this -> result['code'] = 0;
                    $this -> result['msg'] = '发放失败';
                    return $this -> result;
                }
            }
            //修改发放状态
            $info -> status = 1;
            $info -> isUpdate(true) -> save();
            $this -> mTotalRecharge -> commit();
            return $this -> result;
        } catch (\Exception $e) {
            $this -> mTotalRecharge -> rollback();
            $this -> result['code'] = 0;
            $this -> result['msg'] = '发生错误,发放失败' . $e -> getMessage();
        }
        return $this -> result;
    }


}
