<?php

namespace app\btwelfare\logic;

use app\btwelfare\model\BtPropModel;
use app\btwelfare\model\BtWelfareModel;
use app\btwelfare\model\BtWelfareRechargeModel;
use app\game\model\ServerModel;
use app\promote\model\PromoteModel;

/**
 * bt福利充值
 * Class RechargeLogic
 *
 * @package app\btwelfare\logic
 */
class RechargeLogic extends BaseLogic
{

    protected $mRecharge;
    protected $mBtWelfare;
    protected $mBtProp;

    public function __construct()
    {
        parent ::__construct();
        $this -> mRecharge = new BtWelfareRechargeModel();
        $this -> mBtWelfare = new BtWelfareModel();
        $this -> mBtProp = new BtPropModel();
    }


    /**
     * @充值福利管理后台列表
     *
     * @author: zsl
     * @since: 2021/1/14 17:22
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
        if ($param['status'] === '0' || $param['status'] === '1') {
            $where['status'] = $param['status'];
        }
        if (!empty($param['server_id'])) {
            $mServer = new ServerModel();
            $server_num = $mServer->where(['id'=>$param['server_id']])->value('server_num');
            if(!empty($server_num)){
                $where['server_id'] = $server_num;
            }
        }
        if ($param['start_time'] && $param['end_time']) {
            $where['pay_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time']) + 86399]];
        } elseif ($param['end_time']) {
            $where['pay_time'] = ['lt', strtotime($param['end_time']) + 86400];
        } elseif ($param['start_time']) {
            $where['pay_time'] = ['egt', strtotime($param['start_time'])];
        }
        $lists = $this -> mRecharge
                -> field('id,user_id,game_id,game_name,server_id,server_name,game_player_name,promote_id,promote_name,pay_order_number,pay_time,status,user_account,pay_time')
                -> where($where)
                -> order('create_time desc')
                -> paginate($row, false, ['query' => $param]);
        return $lists;
    }
    public function adminLists_2($param)
    {
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $where = [];
        if (!empty($param['promote_id'])) {
            $where['r.promote_id'] = $param['promote_id'];
        }
        if (!empty($param['game_id'])) {
            $where['r.game_id'] = $param['game_id'];
        }
        if (!empty($param['user_account'])) {
            $where['r.user_account'] = ['like', '%' . $param['user_account'] . '%'];
        }
        if (!empty($param['game_player_name'])) {
            $where['r.game_player_name'] = ['like', '%' . $param['game_player_name'] . '%'];
        }
        if ($param['status'] === '0' || $param['status'] === '1') {
            $where['r.status'] = $param['status'];
        }
        if (!empty($param['server_id'])) {
            $mServer = new ServerModel();
            $server_num = $mServer->where(['id'=>$param['server_id']])->value('server_num');
            if(!empty($server_num)){
                $where['r.server_id'] = $server_num;
            }
        }
        if ($param['start_time'] && $param['end_time']) {
            $where['r.pay_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time']) + 86399]];
        } elseif ($param['end_time']) {
            $where['r.pay_time'] = ['lt', strtotime($param['end_time']) + 86400];
        } elseif ($param['start_time']) {
            $where['r.pay_time'] = ['egt', strtotime($param['start_time'])];
        }
        // 渠道独占-------- START
        $promote_id = PID;
        $map_forbid = [];
        if(!empty($promote_id)){
            $onlyForPromote = game_only_for_promote($promote_id);
            $forbidGameids = $onlyForPromote['forbid_game_ids'];
            if(!empty($forbidGameids)){
                if(!empty($where['game_id'])){
                    $where['r.game_id'] = [['=',$where['r.game_id']],['not in',$forbidGameids]];
                }else{
                    $where['r.game_id'] = ['not in',$forbidGameids];
                }
            }
        }
        // 渠道独占-------- END

        $lists = $this -> mRecharge
                ->alias('r')
                ->field('r.id,r.user_id,r.game_id,r.game_name,r.server_id,r.server_name,r.game_player_name,r.promote_id,r.promote_name,r.pay_order_number,r.pay_time,r.status,r.user_account,r.pay_time,s.pay_way')
                ->join(['tab_spend' => 's'],'s.pay_order_number = r.pay_order_number','left')
                ->where($where)
                ->order('create_time desc,id desc')
                ->paginate($row, false, ['query' => $param]);
        return $lists;
    }

    /**
     * @生成充值福利待发放数据
     *
     * @author: zsl
     * @since: 2021/1/14 15:29
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
        //生成充值福利数据
        $this -> mRecharge -> user_id = $param['user_id'];
        $this -> mRecharge -> user_account = get_user_entity($param['user_id'], false, 'account')['account'];
        $this -> mRecharge -> game_id = $param['game_id'];
        $this -> mRecharge -> game_name = get_game_name($param['game_id']);
        $this -> mRecharge -> server_id = $param['server_id'];
        $this -> mRecharge -> server_name = $param['server_name'];
        $this -> mRecharge -> game_player_id = $param['game_player_id'];
        $this -> mRecharge -> game_player_name = $param['game_player_name'];
        $this -> mRecharge -> promote_id = $param['promote_id'];
        $this -> mRecharge -> promote_name = get_promote_name($param['promote_id']);
        $this -> mRecharge -> pay_time = $param['pay_time'];
        $this -> mRecharge -> pay_order_number = $param['pay_order_number'];
        //查询道具内容
        $this -> mRecharge -> send_prop = $this -> getPropContent($welfareInfo -> recharge_prop_ids);
        $this -> mRecharge -> status = 0;
        $result = $this -> mRecharge -> allowField(true) -> isUpdate(false) -> save();
        if (false === $result) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '发生错误,发放失败';
        }
        //获取
        $this -> result['msg'] = '发放成功';
        //获取记录id
        $recordId = $this -> mRecharge -> getLastInsID();
        //自动发放
        $mPromote = new PromoteModel();
        $bt_welfare_register_auto = $mPromote -> where(['id' => $param['promote_id']]) -> value('bt_welfare_recharge_auto');
        if ($bt_welfare_register_auto == '1') {
            try {
                $this -> send($recordId);
            } catch (\Exception $e) {
                //自动发放失败
            }
        }
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
                -> field('w.id,w.recharge_prop_ids')
                -> join(['tab_bt_welfare_promote' => 'wp'], 'w.id = wp.bt_welfare_id', 'left')
                -> where($where)
                -> find();
        if (empty($welfareInfo) || empty($welfareInfo['recharge_prop_ids'])) {
            return false;
        }
        //查询福利是否已存在
        $where = [];
        $where['user_id'] = $param['user_id'];
        $where['game_id'] = $param['game_id'];
        $where['game_player_id'] = $param['game_player_id'];
        $rechargeRecord = $this -> mRecharge -> where($where) -> find();
        if (!empty($rechargeRecord)) {
            return false;
        }
        return $welfareInfo;
    }


    /**
     * @发放福利
     *
     * @author: zsl
     * @since: 2021/1/19 10:13
     */
    public function send($id)
    {

        $info = $this -> mRecharge -> find($id);
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
        $this -> mRecharge -> startTrans();
        try {
            //发放道具
            foreach ($propList as $v) {
                $data = $info -> toArray();
                $data['prop_id'] = $v['prop_tag'];
                $data['prop_num'] = $v['number'];
                $sendRes = $apiObject -> send($data);
                if (false === $sendRes) {
                    $this -> mRecharge -> rollback();
                    $this -> result['code'] = 0;
                    $this -> result['msg'] = '发放失败';
                    return $this -> result;
                }
            }
            //修改发放状态
            $info -> status = 1;
            $info -> isUpdate(true) -> save();
            $this -> mRecharge -> commit();
            return $this -> result;
        } catch (\Exception $e) {
            $this -> mRecharge -> rollback();
            $this -> result['code'] = 0;
            $this -> result['msg'] = '发生错误,发放失败' . $e -> getMessage();
        }
        return $this -> result;
    }


}